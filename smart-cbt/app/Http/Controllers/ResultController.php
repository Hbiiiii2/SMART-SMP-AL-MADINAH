<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Examination\ExamFilterService;
use App\Services\Examination\ScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request, ExamFilterService $examFilterService): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isGuru(), 403);

        $examFilterService->syncExpiredExams();

        $isGuru = $request->user()->isGuru();
        $guruId = $isGuru ? $request->user()->guru?->id : null;
        if ($isGuru) {
            abort_unless($guruId, 403);
        }

        // 1. Query Attempt Hasil Siswa
        $query = ExamAttempt::with(['exam.mataPelajaran', 'exam.kelasData', 'exam.guru', 'siswa.kelasData']);
        $examFilterService->applyAttemptFilters($query, $request, $isGuru, $guruId);

        $totalPeserta = (clone $query)->count();
        $totalSelesai = (clone $query)->whereIn('status', ['submitted', 'completed', 'needs_grading', 'expired'])->count();
        $totalMenungguNilai = (clone $query)->where('status', 'needs_grading')->count();
        $rataRata = (clone $query)->whereNotNull('nilai_akhir')->avg('nilai_akhir');
        $nilaiTertinggi = (clone $query)->whereNotNull('nilai_akhir')->max('nilai_akhir');

        // 2. Opsi Searchable Exam Picker & Filter Dropdowns
        $filterOptions = $examFilterService->examFilterOptions($guruId);

        $examOptionsQuery = Exam::with(['mataPelajaran:id,nama', 'kelasData:id,nama_kelas', 'guru:id,nama'])
            ->when($isGuru, fn (Builder $exam) => $exam->where(
                fn (Builder $q) => $q->where('guru_id', $guruId)->orWhere('guru_pengawas_id', $guruId)
            ))
            ->latest('created_at')
            ->orderBy('nama');
        $examFilterService->withExamCounts($examOptionsQuery);
        $examOptions = $examOptionsQuery->get();
        $examPickerItems = $examFilterService->formatExamPickerItems($examOptions);

        // 3. Rekap Status Pelaksanaan Per Paket Ujian (Sudah Pernah Ujian vs Belum)
        $examBaseQuery = Exam::query()
            ->when($isGuru, fn (Builder $exam) => $exam->where(
                fn (Builder $q) => $q->where('guru_id', $guruId)->orWhere('guru_pengawas_id', $guruId)
            ));
        $examExecutionSummary = $examFilterService->summarizeExams(clone $examBaseQuery);

        $examSummaryQuery = (clone $examBaseQuery)
            ->with(['mataPelajaran', 'kelasData', 'guru', 'guruPengawas'])
            ->withAvg('examAttempts', 'nilai_akhir');
        $examFilterService->withExamCounts($examSummaryQuery);

        // Filter summary ujian memakai keyword search_ujian + filter mapel/kelas/guru/periode/soal/pelaksanaan
        $summaryRequest = $request->duplicate();
        if ($request->filled('search_ujian')) {
            $summaryRequest->merge(['q' => $request->input('search_ujian')]);
        }
        $examFilterService->applyExamFilters($examSummaryQuery, $summaryRequest, [
            'allowGuruFilter' => ! $isGuru,
            'allowGuruSearch' => ! $isGuru,
            'defaultSort' => 'peserta_desc',
        ]);

        $examSummaries = $examSummaryQuery
            ->paginate(12, ['*'], 'exam_page')
            ->withQueryString();

        return view('hasil.index', [
            'attempts' => $query->paginate(20)->withQueryString(),
            'examOptions' => $examOptions,
            'examPickerItems' => $examPickerItems,
            'kelasOptions' => $filterOptions['kelasData'],
            'mataPelajaranOptions' => $filterOptions['mataPelajarans'],
            'guruOptions' => $filterOptions['gurus'],
            'tahunAjaranOptions' => $filterOptions['tahunAjarans'],
            'totalPeserta' => $totalPeserta,
            'totalSelesai' => $totalSelesai,
            'totalMenungguNilai' => $totalMenungguNilai,
            'rataRata' => $rataRata,
            'nilaiTertinggi' => $nilaiTertinggi,
            'examSummaries' => $examSummaries,
            'examExecutionSummary' => $examExecutionSummary,
        ]);
    }

    public function show(Request $request, $id)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isGuru(), 403);

        $attempt = ExamAttempt::with([
            'exam.mataPelajaran',
            'exam.kelasData',
            'siswa.kelasData',
            'siswa.user',
            'answers.question',
        ])->findOrFail($id);

        if ($request->user()->isGuru()) {
            abort_unless($attempt->exam->guru_id === $request->user()->guru?->id || $attempt->exam->guru_pengawas_id === $request->user()->guru?->id, 403, 'Anda tidak memiliki akses ke hasil ujian ini.');
        }

        $riwayatUjian = ExamAttempt::with(['exam.mataPelajaran', 'exam.kelasData'])
            ->where('siswa_id', $attempt->siswa_id)
            ->when($request->user()->isGuru(), function ($query) use ($request) {
                // Jika guru yang lihat, hanya tampilkan riwayat ujian yang diawasi/dibuat guru tsb
                $query->whereHas('exam', fn ($q) => $q->where(fn ($sq) => $sq->where('guru_id', $request->user()->guru?->id)->orWhere('guru_pengawas_id', $request->user()->guru?->id)));
            })
            ->latest('started_at')
            ->get();

        // Urutkan jawaban berdasarkan urutan soal
        $answers = $attempt->answers->sortBy(function ($answer) {
            return $answer->question->urutan ?? 9999;
        });

        return view('hasil.show', [
            'attempt' => $attempt,
            'siswa' => $attempt->siswa,
            'riwayatUjian' => $riwayatUjian,
            'answers' => $answers,
        ]);
    }

    /**
     * Grade an individual essay answer and recalculate attempt score.
     */
    public function gradeEssay(Request $request, $id, ScoringService $scoringService): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isGuru(), 403);

        $attempt = ExamAttempt::with(['exam', 'answers.question'])->findOrFail($id);

        if ($request->user()->isGuru()) {
            abort_unless(
                $attempt->exam->guru_id === $request->user()->guru?->id || $attempt->exam->guru_pengawas_id === $request->user()->guru?->id,
                403,
                'Anda tidak memiliki akses untuk menilai ujian ini.'
            );
        }

        $validated = $request->validate([
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
            'skor' => ['required', 'numeric', 'min:0'],
            'catatan_guru' => ['nullable', 'string', 'max:500'],
        ]);

        $answer = $attempt->answers()->whereKey($validated['answer_id'])->firstOrFail();

        abort_unless($answer->question->tipe !== 'pg', 422, 'Pertanyaan ini bukan bertipe essay.');

        $maxBobot = (float) ($answer->question->bobot ?: 1);
        if ((float) $validated['skor'] > $maxBobot) {
            return back()->with('error', "Skor tidak boleh melebihi bobot maksimal ({$maxBobot}).");
        }

        $scoringService->gradeEssayAnswer($answer, (float) $validated['skor'], $validated['catatan_guru']);
        $scoringService->recalculateAttemptScore($attempt);

        return back()->with('success', 'Nilai essay berhasil disimpan dan nilai akhir telah diperbarui.');
    }
}
