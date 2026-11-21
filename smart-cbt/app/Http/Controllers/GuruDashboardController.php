<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Services\Examination\ExamFilterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruDashboardController extends Controller
{
    public function __invoke(Request $request, ExamFilterService $examFilterService): View
    {
        $guru = $request->user()->guru;
        abort_unless($guru, 403);

        $examFilterService->syncExpiredExams();

        $baseQuery = Exam::where(function ($q) use ($guru) {
            $q->where('guru_id', $guru->id)
                ->orWhere('guru_pengawas_id', $guru->id);
        });
        $now = now();

        $summary = $examFilterService->summarizeExams(clone $baseQuery);
        $filterOptions = $examFilterService->examFilterOptions($guru->id);

        $examsQuery = (clone $baseQuery)->with(['mataPelajaran', 'kelasData', 'guru', 'guruPengawas']);
        $examFilterService->withExamCounts($examsQuery);
        $examFilterService->applyExamFilters($examsQuery, $request, [
            'allowGuruFilter' => false,
            'allowGuruSearch' => false,
            'defaultSort' => 'upcoming',
        ]);

        $exams = $examsQuery->paginate(10)->withQueryString();

        return view('guru.dashboard', [
            'exams' => $exams,
            'summary' => $summary,
            'filterOptions' => $filterOptions,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'mendatang' => (clone $baseQuery)->where('tanggal_mulai', '>', $now)->count(),
                'aktif' => (clone $baseQuery)->where(function ($query) use ($now): void {
                    $query->where('status', 'aktif')
                        ->orWhere(function ($query) use ($now): void {
                            $query->where('tanggal_mulai', '<=', $now)
                                ->where('tanggal_selesai', '>=', $now);
                        });
                })->count(),
                'selesai' => (clone $baseQuery)->where(function ($query) use ($now): void {
                    $query->where('status', 'selesai')
                        ->orWhere('tanggal_selesai', '<', $now);
                })->count(),
            ],
        ]);
    }
}
