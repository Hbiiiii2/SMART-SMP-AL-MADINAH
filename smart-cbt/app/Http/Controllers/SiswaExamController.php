<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Siswa;
use App\Services\Answer\AnswerAttachmentService;
use App\Services\Answer\AnswerService;
use App\Services\Examination\ExamAttemptService;
use App\Services\Examination\ScoringService;
use App\Services\Security\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class SiswaExamController extends Controller
{
    public function __construct(
        protected ExamAttemptService $examAttemptService,
        protected AnswerService $answerService,
        protected AnswerAttachmentService $attachmentService,
        protected ScoringService $scoringService,
        protected TokenService $tokenService
    ) {}

    public function dashboard(Request $request): View
    {
        $siswa = $this->siswa($request);
        $exams = $this->availableExams($siswa)->get();

        return view('siswa.dashboard', $this->grouped($exams, $siswa));
    }

    public function index(Request $request): View
    {
        $siswa = $this->siswa($request);
        $exams = $this->availableExams($siswa)->get();

        return view('siswa.exams.index', compact('exams', 'siswa'));
    }

    public function history(Request $request): View
    {
        $siswa = $this->siswa($request);
        $exams = $this->availableExams($siswa)
            ->whereHas('examAttempts', fn ($query) => $query->where('siswa_id', $siswa->id))
            ->get();

        return view('siswa.exams.history', compact('exams', 'siswa'));
    }

    public function results(Request $request): View
    {
        $siswa = $this->siswa($request);
        $exams = $this->availableExams($siswa)
            ->whereHas('examAttempts', function ($query) use ($siswa): void {
                $query->where('siswa_id', $siswa->id)
                    ->where('status', '!=', 'in_progress');
            })
            ->get();
        $scoredExams = $exams->filter(fn (Exam $exam) => $exam->examAttempts->first()?->nilai_akhir !== null);

        return view('siswa.exams.results', [
            'exams' => $exams,
            'siswa' => $siswa,
            'totalResults' => $exams->count(),
            'averageScore' => $scoredExams->avg(fn (Exam $exam) => $exam->examAttempts->first()->nilai_akhir),
            'highestScore' => $scoredExams->max(fn (Exam $exam) => $exam->examAttempts->first()->nilai_akhir),
        ]);
    }

    public function show(Request $request, Exam $ujian): View
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $attempt = $this->attempt($ujian, $siswa);

        if ($attempt?->status === 'in_progress' && $this->deadline($attempt) <= now()) {
            $this->answerService->submitAttemptAnswers($attempt, [], $ujian);
            $attempt->refresh();
        }

        return view('siswa.exams.show', [
            'exam' => $ujian->load(['mataPelajaran', 'guru', 'kelasData'])->loadCount('questions'),
            'attempt' => $attempt,
            'status' => $this->status($ujian, $attempt),
        ]);
    }

    public function token(Request $request, Exam $ujian): View|RedirectResponse
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $this->tokenService->rotateExpiredToken($ujian);
        $attempt = $this->attempt($ujian, $siswa);

        abort_unless($ujian->token_aktif && $ujian->token && $ujian->questions()->exists(), 404);

        if ($attempt?->status !== null && $attempt->status !== 'in_progress') {
            return to_route('siswa.ujian.result', $ujian);
        }

        return view('siswa.exams.token', ['exam' => $ujian]);
    }

    public function start(Request $request, Exam $ujian): RedirectResponse
    {
        $siswa = $this->siswa($request);
        $this->tokenService->rotateExpiredToken($ujian);

        $data = $request->validate(['token' => ['required', 'string', 'max:20']]);

        try {
            $attempt = $this->examAttemptService->startOrResumeAttempt($ujian, $siswa, $data['token']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $request->session()->put($this->tokenSessionKey($ujian), $attempt->id);

        return to_route('siswa.ujian.work', $ujian);
    }

    public function work(Request $request, Exam $ujian): View|RedirectResponse
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $this->tokenService->rotateExpiredToken($ujian);
        abort_unless($ujian->token_aktif && $ujian->token && $ujian->questions()->exists(), 404);

        $attempt = $this->attempt($ujian, $siswa);
        abort_unless($attempt?->status === 'in_progress', 403);
        abort_unless($request->session()->get($this->tokenSessionKey($ujian)) === $attempt->id, 403, 'Token ujian tidak valid.');

        if ($this->deadline($attempt)->addMinutes(5) <= now()) {
            $this->answerService->submitAttemptAnswers($attempt, [], $ujian);
            return to_route('siswa.ujian.result', $ujian)->with('success', 'Waktu habis. Jawaban telah dikumpulkan otomatis.');
        }

        $ujian->load(['mataPelajaran']);
        $orderedQuestions = $this->examAttemptService->getOrderedQuestions($ujian, $attempt);
        $ujian->setRelation('questions', $orderedQuestions);

        $attemptAnswers = $attempt->answers()->with('attachments')->get();
        $answers = $attemptAnswers->pluck('jawaban', 'question_id');
        $answerSeqs = $attemptAnswers->whereNotNull('client_seq')->pluck('client_seq', 'question_id');
        $answerTypes = $attemptAnswers->mapWithKeys(function ($ans): array {
            $mode = $ans->answer_type ?: 'text';
            if ($mode === 'text' && $ans->attachments->isNotEmpty() && trim((string) $ans->jawaban) === '') {
                $mode = 'photo';
            }
            return [$ans->question_id => $mode];
        });
        $answerAttachments = $attemptAnswers->mapWithKeys(fn ($ans): array => [
            $ans->question_id => $ans->attachments
                ->map(fn ($attachment) => $this->attachmentService->formatAttachmentDto($attachment))
                ->values()
                ->all(),
        ]);
        $maxEssayPhotos = (int) config('cbt.essay_max_photos', 3);
        $exam = $ujian;
        $deadline = $this->deadline($attempt);
        $now = now();
        $remainingSeconds = max(0, $deadline->getTimestamp() - $now->getTimestamp());
        $serverNowMs = $now->getTimestamp() * 1000;
        $deadlineMs = $deadline->getTimestamp() * 1000;

        return view('siswa.exams.work', compact(
            'exam',
            'attempt',
            'answers',
            'answerSeqs',
            'answerTypes',
            'answerAttachments',
            'maxEssayPhotos',
            'deadline',
            'remainingSeconds',
            'serverNowMs',
            'deadlineMs'
        ));
    }

    public function submit(Request $request, Exam $ujian): RedirectResponse
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $attempt = $this->attempt($ujian, $siswa);
        abort_unless($attempt?->status === 'in_progress', 403);
        abort_unless($request->session()->get($this->tokenSessionKey($ujian)) === $attempt->id, 403, 'Token ujian tidak valid.');

        $answers = $request->input('answers', []);
        $this->answerService->submitAttemptAnswers($attempt, is_array($answers) ? $answers : [], $ujian);

        return to_route('siswa.ujian.result', $ujian)->with('success', 'Ujian berhasil dikumpulkan.');
    }

    public function saveAnswers(Request $request, Exam $ujian): JsonResponse
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $attempt = $this->attempt($ujian, $siswa);
        abort_unless($attempt?->status === 'in_progress', 403);
        abort_unless($request->session()->get($this->tokenSessionKey($ujian)) === $attempt->id, 403, 'Token ujian tidak valid.');

        $answers = $request->input('answers', []);
        $clientSeqs = $request->input('client_seqs', []);
        $requestId = $request->input('request_id');
        abort_unless(is_array($answers), 422);

        try {
            $res = $this->answerService->saveBatchAnswers(
                $attempt,
                $answers,
                $ujian,
                is_array($clientSeqs) ? $clientSeqs : [],
                $requestId ? (string) $requestId : null
            );
            return response()->json($res);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function leave(Request $request, Exam $ujian): JsonResponse
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $attempt = $this->attempt($ujian, $siswa);

        if ($attempt?->status === 'in_progress' && $request->session()->get($this->tokenSessionKey($ujian)) === $attempt->id) {
            $request->session()->forget($this->tokenSessionKey($ujian));
        }

        return response()->json(['left' => true]);
    }

    public function result(Request $request, Exam $ujian): View
    {
        $siswa = $this->siswa($request);
        $this->examAttemptService->ensureAssigned($ujian, $siswa);
        $attempt = $this->attempt($ujian, $siswa);
        abort_unless($attempt && $attempt->status !== 'in_progress', 404);

        return view('siswa.exams.result', [
            'exam' => $ujian->load(['mataPelajaran', 'guru', 'kelasData']),
            'attempt' => $attempt,
            'siswa' => $siswa->load('kelasData'),
        ]);
    }

    private function siswa(Request $request): Siswa
    {
        $siswa = $request->user()->siswa;
        abort_unless($siswa && $siswa->status_aktif, 403);

        return $siswa;
    }

    private function availableExams(Siswa $siswa)
    {
        if (! $siswa->kelas_id) {
            return Exam::query()->whereRaw('1 = 0');
        }

        return Exam::with(['mataPelajaran', 'guru', 'kelasData'])
            ->withCount('questions')
            ->whereNotNull('kelas_id')
            ->where('kelas_id', $siswa->kelas_id)
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($siswa): void {
                $query->where(function ($readyQuery): void {
                    $readyQuery->where('token_aktif', true)
                        ->whereNotNull('token')
                        ->whereHas('questions');
                })->orWhereHas('examAttempts', fn ($attemptQuery) => $attemptQuery->where('siswa_id', $siswa->id));
            })
            ->with(['examAttempts' => fn ($query) => $query->where('siswa_id', $siswa->id)])
            ->orderBy('tanggal_mulai');
    }

    private function grouped($exams, Siswa $siswa): array
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $todayExams = $exams->filter(fn (Exam $exam) => $exam->tanggal_mulai?->betweenIncluded($today, $tomorrow->copy()->subSecond()));

        return [
            'siswa' => $siswa->load('kelasData'),
            'todayExams' => $todayExams,
            'upcomingExams' => $exams->filter(fn (Exam $exam) => $exam->tanggal_mulai?->gte($tomorrow)),
            'completedExams' => $exams
                ->filter(fn (Exam $exam) => $exam->examAttempts->first()?->status !== null && $exam->examAttempts->first()?->status !== 'in_progress')
                ->sortByDesc(fn (Exam $exam) => $exam->examAttempts->first()?->submitted_at)
                ->values(),
        ];
    }

    private function attempt(Exam $exam, Siswa $siswa): ?ExamAttempt
    {
        return $exam->examAttempts()->where('siswa_id', $siswa->id)->first();
    }

    private function tokenSessionKey(Exam $exam): string
    {
        return 'siswa.exam_token_verified.' . $exam->id;
    }

    private function status(Exam $exam, ?ExamAttempt $attempt): string
    {
        if ($exam->status !== 'aktif') return 'Tidak Tersedia';
        if ($attempt && $attempt->status !== 'in_progress') return 'Sudah Dikerjakan';
        if ($exam->tanggal_mulai?->gt(now())) return 'Belum Dimulai';
        if ($exam->tanggal_selesai && $exam->tanggal_selesai->lte(now())) return 'Sudah Selesai';
        return 'Sedang Berlangsung';
    }

    private function deadline(ExamAttempt $attempt): Carbon
    {
        return $this->examAttemptService->calculateDeadline($attempt);
    }
}
