<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Services\Examination\ExamFilterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruExamController extends Controller
{
    public function show(Exam $ujian, Request $request): View
    {
        $guruId = $request->user()->guru?->id;
        // Pastikan ujian milik guru yang sedang login atau guru pengawas
        abort_unless(
            $ujian->guru_id === $guruId || $ujian->guru_pengawas_id === $guruId,
            403
        );

    // Load relasi yang dibutuhkan
    $ujian->load([
        'mataPelajaran',
        'kelasData',
        'questions',
    ]);

    // Ambil semua soal untuk ujian ini
    $questions = $ujian->questions()
        ->orderBy('urutan')
        ->paginate(15);

    // Hitung jumlah soal PG
    $pgCount = $ujian->questions()
        ->where('tipe', 'pg')
        ->count();

    // Hitung jumlah soal Essay
    $essayCount = $ujian->questions()
        ->whereIn('tipe', ['essay_1', 'essay_2'])
        ->count();

    // Total seluruh soal
    $totalQuestions = $ujian->questions()->count();

    // Total bobot semua soal
    $totalBobot = $ujian->questions()->sum('bobot');

    // Pertanyaan yang sudah masuk ke ujian
    $importedQuestions = $ujian->questions()
        ->pluck('pertanyaan')
        ->toArray();

    // Ambil bank soal milik guru
    $banks = $request->user()
        ->guru
        ->questionBanks()
        ->with('questions')
        ->get();

    return view('ujian.show', [
        'exam' => $ujian,
        'questions' => $questions,
        'banks' => $banks,
        'importedQuestions' => $importedQuestions,

        'pgCount' => $pgCount,
        'essayCount' => $essayCount,
        'totalQuestions' => $totalQuestions,
        'totalBobot' => $totalBobot,

        'totalSchedules' => 0,
        'totalParticipants' => $ujian->examAttempts()->count(),
        'isGuruView' => true,
    ]);
}

    public function index(Request $request, ExamFilterService $examFilterService): View
    {
        $guru = $request->user()->guru;
        abort_unless($guru, 403);

        $examFilterService->syncExpiredExams();

        $baseQuery = Exam::where(function ($q) use ($guru) {
            $q->where('guru_id', $guru->id)
                ->orWhere('guru_pengawas_id', $guru->id);
        });

        $summary = $examFilterService->summarizeExams(clone $baseQuery);
        $totalExams = $summary['total'];
        $activeExams = (clone $baseQuery)->where('status', 'aktif')->where('token_aktif', true)->count();
        $draftExams = (clone $baseQuery)->where(function ($q) {
            $q->where('status', '!=', 'aktif')->orWhere('token_aktif', false);
        })->count();

        $filterOptions = $examFilterService->examFilterOptions($guru->id);

        $query = (clone $baseQuery)->with(['mataPelajaran', 'kelasData', 'guru', 'guruPengawas']);
        $examFilterService->withExamCounts($query);
        $examFilterService->applyExamFilters($query, $request, [
            'allowGuruFilter' => false,
            'allowGuruSearch' => false,
        ]);

        $exams = $query->paginate(12)->withQueryString();

        return view('guru.ujian.index', compact(
            'exams',
            'totalExams',
            'activeExams',
            'draftExams',
            'summary',
            'filterOptions'
        ));
    }
}