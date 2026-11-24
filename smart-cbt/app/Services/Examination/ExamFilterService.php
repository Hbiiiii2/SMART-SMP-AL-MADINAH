<?php

namespace App\Services\Examination;

use App\Models\Exam;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExamFilterService
{
    /**
     * Automatically mark expired active exams as completed.
     */
    public function syncExpiredExams(): void
    {
        Exam::query()
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_selesai')
            ->where('tanggal_selesai', '<=', now())
            ->update([
                'status' => 'selesai',
                'token_aktif' => false,
            ]);
    }

    /**
     * Attach standard question and attempt aggregate counts to an Exam query.
     */
    public function withExamCounts(Builder $query): Builder
    {
        return $query->withCount([
            'questions',
            'examAttempts',
            'examAttempts as finished_attempts_count' => fn (Builder $q) => $q->whereIn(
                'status',
                ['submitted', 'completed', 'needs_grading', 'expired']
            ),
            'examAttempts as needs_grading_count' => fn (Builder $q) => $q->where('status', 'needs_grading'),
            'examAttempts as in_progress_attempts_count' => fn (Builder $q) => $q->where('status', 'in_progress'),
            'syncedQuestionBanks',
        ]);
    }

    /**
     * Apply search, readiness, execution, metadata filters, and sorting to an Exam query.
     *
     * @param  array{allowGuruFilter?: bool, allowGuruSearch?: bool, defaultSort?: string}  $options
     */
    public function applyExamFilters(Builder $query, Request $request, array $options = []): Builder
    {
        $allowGuruFilter = $options['allowGuruFilter'] ?? false;
        $allowGuruSearch = $options['allowGuruSearch'] ?? true;
        $defaultSort = $options['defaultSort'] ?? 'latest';

        if ($request->filled('q')) {
            $term = $this->likeTerm($request->string('q')->toString());
            $query->where(function (Builder $q) use ($term, $allowGuruSearch): void {
                $q->whereRaw('LOWER(exams.nama) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(exams.kode_ujian) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(exams.ruangan, '')) LIKE ?", [$term])
                    ->orWhereRaw("LOWER(COALESCE(exams.kelas, '')) LIKE ?", [$term])
                    ->orWhereHas('mataPelajaran', fn (Builder $mp) => $mp->whereRaw('LOWER(nama) LIKE ?', [$term]))
                    ->orWhereHas('kelasData', fn (Builder $k) => $k->whereRaw('LOWER(nama_kelas) LIKE ?', [$term]));

                if ($allowGuruSearch) {
                    $q->orWhereHas('guru', fn (Builder $g) => $g->whereRaw('LOWER(nama) LIKE ?', [$term]))
                        ->orWhereHas('guruPengawas', fn (Builder $g) => $g->whereRaw('LOWER(nama) LIKE ?', [$term]));
                }
            });
        }

        if ($request->filled('mata_pelajaran_id')) {
            $query->where('exams.mata_pelajaran_id', $request->integer('mata_pelajaran_id'));
        }

        if ($request->filled('kelas_id')) {
            $query->where('exams.kelas_id', $request->integer('kelas_id'));
        }

        if ($allowGuruFilter && $request->filled('guru_id')) {
            $guruId = $request->integer('guru_id');
            $query->where(function (Builder $q) use ($guruId): void {
                $q->where('exams.guru_id', $guruId)
                    ->orWhere('exams.guru_pengawas_id', $guruId);
            });
        }

        if ($request->filled('tahun_ajaran')) {
            $query->where('exams.tahun_ajaran', $request->string('tahun_ajaran')->trim()->toString());
        }

        if ($request->filled('semester')) {
            $query->where('exams.semester', $request->string('semester')->trim()->toString());
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->trim()->toString();
            match ($status) {
                'draft' => $query->where(fn (Builder $q) => $q->where('exams.status', 'draft')->orWhereNull('exams.status')),
                'aktif' => $query->where('exams.status', 'aktif'),
                'berlangsung' => $query->where('exams.status', 'aktif')
                    ->where(fn (Builder $q) => $q->whereNull('exams.tanggal_mulai')->orWhere('exams.tanggal_mulai', '<=', now())),
                'terjadwal' => $query->where('exams.status', 'aktif')
                    ->where('exams.tanggal_mulai', '>', now()),
                'selesai' => $query->where('exams.status', 'selesai'),
                default => null,
            };
        }

        if ($request->filled('soal_status')) {
            $soalStatus = $request->string('soal_status')->trim()->toString();
            if ($soalStatus === 'sudah_ada') {
                $query->whereHas('questions');
            } elseif ($soalStatus === 'belum_ada') {
                $query->whereDoesntHave('questions');
            }
        }

        if ($request->filled('pelaksanaan')) {
            $pelaksanaan = $request->string('pelaksanaan')->trim()->toString();
            if ($pelaksanaan === 'sudah_ujian') {
                $query->whereHas('examAttempts');
            } elseif ($pelaksanaan === 'belum_ujian') {
                $query->whereDoesntHave('examAttempts');
            } elseif ($pelaksanaan === 'selesai_ujian') {
                $query->whereHas('examAttempts', fn (Builder $q) => $q->whereIn('status', ['submitted', 'completed', 'needs_grading', 'expired']));
            }
        }

        $sort = $request->string('sort')->trim()->toString() ?: $defaultSort;
        match ($sort) {
            'upcoming' => $query->orderByRaw('CASE WHEN exams.tanggal_mulai >= ? THEN 0 ELSE 1 END', [now()])
                ->orderBy('exams.tanggal_mulai')
                ->latest('exams.id'),
            'jadwal_asc' => $query->orderByRaw('CASE WHEN exams.tanggal_mulai IS NULL THEN 1 ELSE 0 END')
                ->orderBy('exams.tanggal_mulai')
                ->latest('exams.id'),
            'jadwal_desc' => $query->orderByRaw('CASE WHEN exams.tanggal_mulai IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('exams.tanggal_mulai')
                ->latest('exams.id'),
            'peserta_desc' => $query->orderByDesc('exam_attempts_count')->latest('exams.id'),
            'soal_desc' => $query->orderByDesc('questions_count')->latest('exams.id'),
            'nama_asc' => $query->orderBy('exams.nama'),
            default => $query->latest('exams.created_at')->latest('exams.id'),
        };

        return $query;
    }

    /**
     * Summarize exam readiness and execution metrics for an unscoped base Exam query.
     *
     * @return array<string, int>
     */
    public function summarizeExams(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'aktif' => (clone $baseQuery)->where('status', 'aktif')->count(),
            'draft' => (clone $baseQuery)->where(fn (Builder $q) => $q->where('status', 'draft')->orWhereNull('status'))->count(),
            'selesai' => (clone $baseQuery)->where('status', 'selesai')->count(),
            'sudah_ada_soal' => (clone $baseQuery)->whereHas('questions')->count(),
            'belum_ada_soal' => (clone $baseQuery)->whereDoesntHave('questions')->count(),
            'sudah_pernah_ujian' => (clone $baseQuery)->whereHas('examAttempts')->count(),
            'belum_pernah_ujian' => (clone $baseQuery)->whereDoesntHave('examAttempts')->count(),
        ];
    }

    /**
     * Load dropdown options for exam filters.
     *
     * @return array<string, Collection>
     */
    public function examFilterOptions(?int $guruId = null): array
    {
        $tahunAjaranQuery = Exam::query()
            ->whereNotNull('tahun_ajaran')
            ->where('tahun_ajaran', '!=', '');

        if ($guruId !== null) {
            $tahunAjaranQuery->where(function (Builder $q) use ($guruId): void {
                $q->where('guru_id', $guruId)
                    ->orWhere('guru_pengawas_id', $guruId);
            });
        }

        return [
            'mataPelajarans' => MataPelajaran::query()->orderBy('nama')->get(['id', 'nama']),
            'kelasData' => Kelas::query()->orderBy('tingkat')->orderBy('nama_kelas')->get(['id', 'nama_kelas']),
            'gurus' => $guruId === null ? Guru::query()->orderBy('nama')->get(['id', 'nama']) : collect(),
            'tahunAjarans' => $tahunAjaranQuery->distinct()->orderByDesc('tahun_ajaran')->pluck('tahun_ajaran'),
        ];
    }

    /**
     * Apply filters and sorting to ExamAttempt queries (shared by ResultController and ExportController).
     */
    public function applyAttemptFilters(Builder $query, Request $request, bool $isGuru = false, ?int $guruId = null): Builder
    {
        if ($isGuru) {
            $query->whereHas('exam', function (Builder $exam) use ($guruId): void {
                $exam->where(fn (Builder $q) => $q->where('guru_id', $guruId)->orWhere('guru_pengawas_id', $guruId));
            });
        }

        if ($request->filled('search_siswa')) {
            $term = $this->likeTerm($request->string('search_siswa')->toString());
            $query->whereHas('siswa', function (Builder $siswa) use ($term): void {
                $siswa->whereRaw('LOWER(nama) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(COALESCE(nis, '')) LIKE ?", [$term])
                    ->orWhereRaw("LOWER(COALESCE(nisn, '')) LIKE ?", [$term]);
            });
        }

        if ($request->filled('search_ujian')) {
            $term = $this->likeTerm($request->string('search_ujian')->toString());
            $query->whereHas('exam', function (Builder $exam) use ($term): void {
                $exam->whereRaw('LOWER(nama) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(kode_ujian) LIKE ?', [$term]);
            });
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_attempts.exam_id', $request->integer('exam_id'));
        }

        if ($request->filled('kelas_id')) {
            $kelasId = $request->integer('kelas_id');
            $query->where(function (Builder $q) use ($kelasId): void {
                $q->whereHas('exam', fn (Builder $exam) => $exam->where('kelas_id', $kelasId))
                    ->orWhereHas('siswa', fn (Builder $siswa) => $siswa->where('kelas_id', $kelasId));
            });
        }

        if ($request->filled('mata_pelajaran_id')) {
            $mapelId = $request->integer('mata_pelajaran_id');
            $query->whereHas('exam', fn (Builder $exam) => $exam->where('mata_pelajaran_id', $mapelId));
        }

        if (! $isGuru && $request->filled('guru_id')) {
            $selectedGuruId = $request->integer('guru_id');
            $query->whereHas('exam', function (Builder $exam) use ($selectedGuruId): void {
                $exam->where(fn (Builder $q) => $q->where('guru_id', $selectedGuruId)->orWhere('guru_pengawas_id', $selectedGuruId));
            });
        }

        if ($request->filled('tahun_ajaran')) {
            $tahun = $request->string('tahun_ajaran')->trim()->toString();
            $query->whereHas('exam', fn (Builder $exam) => $exam->where('tahun_ajaran', $tahun));
        }

        if ($request->filled('semester')) {
            $semester = $request->string('semester')->trim()->toString();
            $query->whereHas('exam', fn (Builder $exam) => $exam->where('semester', $semester));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->trim()->toString();
            if ($status === 'finished') {
                $query->whereIn('exam_attempts.status', ['submitted', 'completed', 'needs_grading', 'expired']);
            } else {
                $query->where('exam_attempts.status', $status);
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('exam_attempts.started_at', '>=', $request->date('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('exam_attempts.started_at', '<=', $request->date('end_date'));
        }

        $sort = $request->string('sort')->trim()->toString() ?: 'latest';
        match ($sort) {
            'nilai_desc' => $query->orderByRaw('CASE WHEN exam_attempts.nilai_akhir IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('exam_attempts.nilai_akhir')
                ->latest('exam_attempts.id'),
            'nilai_asc' => $query->orderByRaw('CASE WHEN exam_attempts.nilai_akhir IS NULL THEN 1 ELSE 0 END')
                ->orderBy('exam_attempts.nilai_akhir')
                ->latest('exam_attempts.id'),
            'nama_asc' => $query->orderBy(
                Siswa::select('nama')->whereColumn('siswas.id', 'exam_attempts.siswa_id')->limit(1)
            )->latest('exam_attempts.id'),
            default => $query->latest('exam_attempts.created_at')->latest('exam_attempts.id'),
        };

        return $query;
    }

    /**
     * Format Exam models into lightweight items for the searchable exam dropdown component.
     *
     * @return array<int, array<string, mixed>>
     */
    public function formatExamPickerItems(Collection $exams): array
    {
        return $exams->map(function (Exam $exam): array {
            $status = $exam->effectiveStatus();

            return [
                'id' => $exam->id,
                'nama' => $exam->nama,
                'kode_ujian' => $exam->kode_ujian ?? '-',
                'mapel' => $exam->mataPelajaran?->nama ?? '-',
                'kelas' => $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-',
                'guru' => $exam->guru?->nama ?? '-',
                'tahun_ajaran' => $exam->tahun_ajaran ?? '',
                'semester' => $exam->semester ? ucfirst($exam->semester) : '',
                'status' => $status,
                'questions_count' => (int) ($exam->questions_count ?? 0),
                'attempts_count' => (int) ($exam->exam_attempts_count ?? 0),
                'finished_count' => (int) ($exam->finished_attempts_count ?? 0),
            ];
        })->values()->all();
    }

    /**
     * Build a safe case-insensitive LIKE pattern.
     */
    public function likeTerm(string $keyword): string
    {
        return '%' . mb_strtolower(trim($keyword)) . '%';
    }
}
