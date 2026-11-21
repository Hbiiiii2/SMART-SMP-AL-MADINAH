<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Siswa;
use App\Services\Examination\ExamFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruMonitoringController extends Controller
{
    public function index(Request $request, ExamFilterService $examFilterService): View
    {
        $guruId = $request->user()->guru?->id;
        abort_unless($guruId, 403);

        $examFilterService->syncExpiredExams();

        $queryExamId = $request->input('exam_id');
        $searchSiswa = trim($request->string('search_siswa')->toString());

        $baseOwnedExamQuery = Exam::where(function ($q) use ($guruId) {
            $q->where('guru_id', $guruId)
                ->orWhere('guru_pengawas_id', $guruId);
        });

        // Ambil seluruh daftar ujian guru SEBELUM memfilter berdasarkan exam_id
        $ownedExamsListQuery = (clone $baseOwnedExamQuery)
            ->with(['mataPelajaran:id,nama', 'kelasData:id,nama_kelas', 'guru:id,nama'])
            ->latest('created_at')
            ->orderBy('nama');
        $examFilterService->withExamCounts($ownedExamsListQuery);
        $ownedExamsList = $ownedExamsListQuery->get();
        $examPickerItems = $examFilterService->formatExamPickerItems($ownedExamsList);

        $filteredExamQuery = clone $baseOwnedExamQuery;
        if ($queryExamId) {
            $filteredExamQuery->where('id', (int) $queryExamId);
        }
        $ownedExamIds = $filteredExamQuery->pluck('id');

        $base = ExamAttempt::whereIn('exam_id', $ownedExamIds);
        if ($searchSiswa !== '') {
            $term = $examFilterService->likeTerm($searchSiswa);
            $base->whereHas('siswa', function (Builder $siswa) use ($term): void {
                $siswa->whereRaw('LOWER(nama) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(nis, '')) LIKE ?", [$term])
                    ->orWhereRaw("LOWER(COALESCE(nisn, '')) LIKE ?", [$term]);
            });
        }

        $counts = [
            'online' => (clone $base)->where('status', 'in_progress')->where('updated_at', '>=', now()->subMinutes(5))->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed' => (clone $base)->whereIn('status', ['submitted', 'completed', 'needs_grading', 'expired'])->count(),
            'not_started' => $this->notStartedQuery($ownedExamIds, $searchSiswa, $examFilterService)->count(),
        ];

        // Auto-select the first non-empty tab if the user didn't explicitly request a specific status
        if (! $request->has('status')) {
            $status = 'online'; // default
            if ($counts['online'] === 0) {
                foreach (['in_progress', 'completed', 'not_started'] as $s) {
                    if ($counts[$s] > 0) {
                        $status = $s;
                        break;
                    }
                }
            }
        } else {
            $status = $request->string('status')->toString();
            $allowedStatuses = ['online', 'in_progress', 'completed', 'not_started'];
            $status = in_array($status, $allowedStatuses, true) ? $status : 'online';
        }

        if ($status === 'not_started') {
            $attempts = $this->notStartedQuery($ownedExamIds, $searchSiswa, $examFilterService)
                ->paginate(20)
                ->withQueryString();
        } else {
            $attemptsQuery = ExamAttempt::with([
                'exam' => fn ($query) => $query->with('kelasData')->withCount('questions'),
                'siswa',
            ])->withCount('answers')
                ->whereIn('exam_id', $ownedExamIds)
                ->when($status === 'online', fn ($query) => $query->where('status', 'in_progress')->where('updated_at', '>=', now()->subMinutes(5)))
                ->when($status === 'in_progress', fn ($query) => $query->where('status', 'in_progress'))
                ->when($status === 'completed', fn ($query) => $query->whereIn('status', ['submitted', 'completed', 'needs_grading', 'expired']));

            if ($searchSiswa !== '') {
                $term = $examFilterService->likeTerm($searchSiswa);
                $attemptsQuery->whereHas('siswa', function (Builder $siswa) use ($term): void {
                    $siswa->whereRaw('LOWER(nama) LIKE ?', [$term])
                        ->orWhereRaw("LOWER(COALESCE(nis, '')) LIKE ?", [$term])
                        ->orWhereRaw("LOWER(COALESCE(nisn, '')) LIKE ?", [$term]);
                });
            }

            $attempts = $attemptsQuery->latest()->paginate(20)->withQueryString();
        }

        return view('guru.monitoring.index', [
            'attempts' => $attempts,
            'status' => $status,
            'counts' => $counts,
            'ownedExams' => $ownedExamsList,
            'examPickerItems' => $examPickerItems,
            'queryExamId' => $queryExamId,
        ]);
    }

    private function notStartedQuery($examIds, string $searchSiswa, ExamFilterService $examFilterService)
    {
        $query = Siswa::query()
            ->select('siswas.*', 'exams.id as exam_id', 'exams.nama as exam_nama', 'exams.kelas as exam_kelas', 'exams.tanggal_mulai', 'exams.tanggal_selesai')
            ->join('exams', function ($join) {
                $join->on(function ($query) {
                    $query->on('exams.kelas_id', '=', 'siswas.kelas_id')
                        ->orWhere(function ($legacy) {
                            $legacy->whereNull('exams.kelas_id')->whereColumn('exams.kelas', 'siswas.kelas');
                        });
                });
            })
            ->whereIn('exams.id', $examIds)
            ->where('siswas.status_aktif', true)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('exam_attempts')
                    ->whereColumn('exam_attempts.exam_id', 'exams.id')
                    ->whereColumn('exam_attempts.siswa_id', 'siswas.id');
            });

        if ($searchSiswa !== '') {
            $term = $examFilterService->likeTerm($searchSiswa);
            $query->where(function (Builder $q) use ($term): void {
                $q->whereRaw('LOWER(siswas.nama) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(siswas.nis, '')) LIKE ?", [$term])
                    ->orWhereRaw("LOWER(COALESCE(siswas.nisn, '')) LIKE ?", [$term]);
            });
        }

        return $query->latest('exams.tanggal_mulai');
    }
}
