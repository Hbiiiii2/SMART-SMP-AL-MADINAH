<?php

namespace App\Services\Answer;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\Examination\ScoringService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AnswerService
{
    public function __construct(
        protected ScoringService $scoringService
    ) {}

    /**
     * Save answers in bulk atomically using a single upsert query with monotonic client sequence protection,
     * request-level idempotency caching, and structured version acknowledgment.
     *
     * @param  array<int|string, mixed>  $answers  [question_id => answer_value] or [question_id => ['jawaban' => ..., 'client_seq' => ...]]
     * @param  array<int|string, int>    $clientSeqs [question_id => client_seq]
     * @param  string|null               $requestId  Unique idempotency request identifier
     * @return array{saved: bool, attempt_id: int, request_id: ?string, count: int, acknowledged: array, rejected: array, server_time: string}
     */
    public function saveBatchAnswers(
        ExamAttempt $attempt,
        array $answers,
        Exam $exam,
        array $clientSeqs = [],
        ?string $requestId = null
    ): array {
        // Fast-path Idempotency Check:
        // If this exact request_id has already been processed for this attempt,
        // return the cached structured acknowledgment immediately without touching MySQL.
        if ($requestId !== null && $requestId !== '') {
            $cacheKey = "cbt:idempotency:{$attempt->id}:{$requestId}";
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && ! empty($cached['saved'])) {
                $payloadHash = md5(json_encode($answers));
                if (isset($cached['payload_hash']) && $cached['payload_hash'] !== $payloadHash) {
                    throw new InvalidArgumentException("Idempotency conflict: Request ID {$requestId} was already processed with a different payload.");
                }
                return $cached;
            }
        }

        return DB::transaction(function () use ($attempt, $answers, $exam, $clientSeqs, $requestId): array {
            $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();

            if (! $lockedAttempt || $lockedAttempt->status !== 'in_progress') {
                throw new InvalidArgumentException('Ujian sudah selesai atau tidak aktif.');
            }

            $deadline = $this->calculateDeadline($lockedAttempt);
            if (now()->gt($deadline->copy()->addMinutes(10))) {
                throw new InvalidArgumentException('Waktu ujian sudah berakhir.');
            }

            // Determine valid question IDs from snapshot if available, or fall back to exam questions
            if (is_array($lockedAttempt->questions_snapshot) && ! empty($lockedAttempt->questions_snapshot)) {
                $validQuestionIds = collect($lockedAttempt->questions_snapshot)->pluck('id')->all();
            } else {
                $validQuestionIds = $exam->questions()->pluck('id')->all();
            }

            // Normalize incoming answers and sequences
            $normalized = [];
            foreach ($answers as $key => $val) {
                if (is_array($val) && isset($val['question_id'])) {
                    $qId = (int) $val['question_id'];
                    $jawaban = $val['jawaban'] ?? ($val['answer'] ?? null);
                    $seqRaw = array_key_exists('client_seq', $val)
                        ? $val['client_seq']
                        : ($val['answer_version'] ?? ($clientSeqs[$qId] ?? null));
                } elseif (is_array($val)) {
                    $qId = (int) $key;
                    $jawaban = $val['jawaban'] ?? ($val['answer'] ?? null);
                    $seqRaw = array_key_exists('client_seq', $val)
                        ? $val['client_seq']
                        : ($val['answer_version'] ?? ($clientSeqs[$qId] ?? null));
                } else {
                    $qId = (int) $key;
                    $jawaban = $val;
                    $seqRaw = array_key_exists($qId, $clientSeqs) ? $clientSeqs[$qId] : null;
                }

                $validatedSeq = null;
                if ($seqRaw !== null) {
                    // Sequence must be a non-negative integer within 32-bit unsigned range (0 to 4294967295)
                    if (! is_numeric($seqRaw) || (int) $seqRaw < 0 || (float) $seqRaw > 4294967295 || (string) (int) $seqRaw !== (string) $seqRaw) {
                        throw new InvalidArgumentException("Invalid sequence number for question {$qId}: must be a valid non-negative integer within range (0-4294967295).");
                    }
                    $validatedSeq = (int) $seqRaw;
                }

                if ($jawaban !== null && is_scalar($jawaban)) {
                    $normalized[$qId] = [
                        'jawaban' => (string) $jawaban,
                        'client_seq' => $validatedSeq,
                    ];
                }
            }

            if (empty($normalized)) {
                $emptyResult = [
                    'saved' => true,
                    'attempt_id' => $lockedAttempt->id,
                    'request_id' => $requestId,
                    'count' => 0,
                    'acknowledged' => [],
                    'rejected' => [],
                    'server_time' => now()->toIso8601String(),
                    'payload_hash' => md5(json_encode($answers)),
                ];
                if ($requestId !== null && $requestId !== '') {
                    Cache::put("cbt:idempotency:{$attempt->id}:{$requestId}", $emptyResult, now()->addMinutes(5));
                }
                return $emptyResult;
            }

            // Retrieve existing answers for this attempt to evaluate sequence numbers
            $existingAnswers = Answer::where('exam_attempt_id', $lockedAttempt->id)
                ->whereIn('question_id', array_keys($normalized))
                ->get()
                ->keyBy('question_id');

            $now = now();
            $payload = [];
            $savedCount = 0;
            $acknowledged = [];
            $rejected = [];

            foreach ($normalized as $qId => $item) {
                if (! in_array($qId, $validQuestionIds, true)) {
                    $rejected[$qId] = [
                        'client_seq' => $item['client_seq'],
                        'reason' => 'invalid_question_id',
                    ];
                    continue;
                }

                $existing = $existingAnswers->get($qId);

                // 1. Legacy Downgrade Protection:
                // An answer that already has sequence protection MUST NOT be overwritten
                // by an unsequenced legacy payload.
                if ($existing && $existing->client_seq !== null && $item['client_seq'] === null) {
                    throw new InvalidArgumentException("Downgrade rejected: Question {$qId} already has sequence protection and cannot be updated with an unsequenced legacy payload.");
                }

                // 2. Monotonic Sequence Conflict & Stale Detection:
                if ($existing && $existing->client_seq !== null && $item['client_seq'] !== null) {
                    // Lower sequence = stale; do not overwrite (network jitter/delayed packet)
                    if ($item['client_seq'] < $existing->client_seq) {
                        $rejected[$qId] = [
                            'client_seq' => $item['client_seq'],
                            'reason' => 'stale_sequence',
                            'current_server_seq' => $existing->client_seq,
                        ];
                        continue;
                    }

                    // Same sequence:
                    if ($item['client_seq'] === $existing->client_seq) {
                        // Same sequence + same payload = idempotent retry (safe, re-save)
                        // Same sequence + different payload = conflict; do not overwrite!
                        if ((string) $item['jawaban'] !== (string) $existing->jawaban) {
                            $rejected[$qId] = [
                                'client_seq' => $item['client_seq'],
                                'reason' => 'sequence_conflict',
                                'current_server_seq' => $existing->client_seq,
                            ];
                            continue;
                        }
                    }
                }

                $payload[] = [
                    'exam_attempt_id' => $lockedAttempt->id,
                    'question_id' => $qId,
                    'jawaban' => $item['jawaban'],
                    'client_seq' => $item['client_seq'],
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ];
                $acknowledged[$qId] = [
                    'client_seq' => $item['client_seq'],
                    'status' => 'persisted',
                ];
                $savedCount++;
            }

            if (! empty($payload)) {
                // Deterministic row ordering eliminates InnoDB lock acquisition deadlocks
                usort($payload, fn ($a, $b) => $a['question_id'] <=> $b['question_id']);

                Answer::upsert(
                    $payload,
                    ['exam_attempt_id', 'question_id'],
                    ['jawaban', 'client_seq', 'updated_at']
                );
            }

            $result = [
                'saved' => true,
                'attempt_id' => $lockedAttempt->id,
                'request_id' => $requestId,
                'count' => $savedCount,
                'acknowledged' => $acknowledged,
                'rejected' => $rejected,
                'server_time' => $now->toIso8601String(),
                'payload_hash' => md5(json_encode($answers)),
            ];

            if ($requestId !== null && $requestId !== '') {
                Cache::put("cbt:idempotency:{$attempt->id}:{$requestId}", $result, now()->addMinutes(5));
            }

            return $result;
        }, 5);
    }

    /**
     * Submit all answers for an attempt, auto-score PG questions, and set final or needs_grading status.
     *
     * @param  array<int|string, mixed>  $submittedAnswers
     */
    public function submitAttemptAnswers(ExamAttempt $attempt, array $submittedAnswers, Exam $exam): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $submittedAnswers, $exam): ExamAttempt {
            $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();

            if (! $lockedAttempt || $lockedAttempt->status !== 'in_progress') {
                return $attempt;
            }

            $lockedAttempt->load('answers');
            $questions = $this->getAttemptQuestionsForScoring($lockedAttempt);

            // Merge already saved answers with any newly submitted answers
            $existingAnswers = $lockedAttempt->answers->pluck('jawaban', 'question_id')->all();
            $mergedAnswers = array_replace($existingAnswers, $submittedAnswers);

            $now = now();
            $hasEssayQuestions = false;
            $toInsert = [];

            foreach ($questions as $question) {
                $rawAnswer = $mergedAnswers[$question->id] ?? null;
                $answerString = is_scalar($rawAnswer) ? (string) $rawAnswer : null;
                $existing = $lockedAttempt->answers->firstWhere('question_id', $question->id);

                if ($question->tipe === 'pg') {
                    $eval = $this->scoringService->evaluatePgQuestion($question, $answerString);
                    $data = [
                        'jawaban' => $answerString,
                        'is_correct' => $eval['is_correct'],
                        'skor' => $eval['skor'],
                        'sudah_dinilai' => true,
                        'catatan_guru' => null,
                        'updated_at' => $now,
                    ];
                } else {
                    $hasEssayQuestions = true;
                    $data = [
                        'jawaban' => $answerString,
                        'is_correct' => $existing?->is_correct ?? false,
                        'skor' => $existing?->skor ?? 0.0,
                        'sudah_dinilai' => $existing?->sudah_dinilai ?? false,
                        'catatan_guru' => $existing?->catatan_guru,
                        'updated_at' => $now,
                    ];
                }

                if ($existing) {
                    $existing->update($data);
                } else {
                    $toInsert[] = array_merge($data, [
                        'exam_attempt_id' => $lockedAttempt->id,
                        'question_id' => $question->id,
                        'created_at' => $now,
                    ]);
                }
            }

            if (! empty($toInsert)) {
                // Deterministic row ordering eliminates InnoDB lock acquisition deadlocks
                usort($toInsert, fn ($a, $b) => $a['question_id'] <=> $b['question_id']);

                Answer::insert($toInsert);
            }

            $deadline = $this->calculateDeadline($lockedAttempt);
            $isExpired = now()->gte($deadline);

            $finalStatus = $isExpired ? 'expired' : 'submitted';
            if ($hasEssayQuestions) {
                $finalStatus = 'needs_grading';
            }

            $lockedAttempt->update([
                'status' => $finalStatus,
                'submitted_at' => now(),
            ]);

            // Recalculate score breakdown
            return $this->scoringService->recalculateAttemptScore($lockedAttempt);
        }, 5);
    }

    /**
     * Get questions with scoring metadata (including answer keys) from attempt snapshot or fallback to live questions.
     */
    public function getAttemptQuestionsForScoring(ExamAttempt $attempt): Collection
    {
        if (is_array($attempt->questions_snapshot) && ! empty($attempt->questions_snapshot)) {
            return collect($attempt->questions_snapshot)->map(function ($item) {
                $q = new Question($item);
                $q->id = $item['id'];
                $q->exists = true;
                return $q;
            });
        }

        $attempt->loadMissing('exam.questions');
        return $attempt->exam->questions;
    }

    /**
     * Calculate examination deadline from duration and exam end date.
     */
    public function calculateDeadline(ExamAttempt $attempt): Carbon
    {
        $exam = $attempt->exam;
        $deadline = $attempt->started_at->copy()->addMinutes($exam->durasi_menit);

        if ($exam->tanggal_selesai && $exam->tanggal_selesai->lt($deadline)) {
            $deadline = $exam->tanggal_selesai;
        }

        return $deadline;
    }
}
