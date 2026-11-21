<?php

namespace App\Services\Examination;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    /**
     * Evaluate single Multiple Choice (PG) question.
     *
     * @return array{is_correct: bool, skor: float}
     */
    public function evaluatePgQuestion(Question $question, ?string $answer): array
    {
        if ($question->tipe !== 'pg') {
            return ['is_correct' => false, 'skor' => 0.0];
        }

        $studentAnswer = strtoupper(trim((string) $answer));
        $correctKey = strtoupper(trim((string) $question->kunci));
        $weight = (float) ($question->bobot ?: 1);

        $isCorrect = !empty($studentAnswer) && !empty($correctKey) && hash_equals($correctKey, $studentAnswer);
        $score = $isCorrect ? $weight : 0.0;

        return [
            'is_correct' => $isCorrect,
            'skor' => $score,
        ];
    }

    /**
     * Grade an individual essay answer and update its evaluation records.
     * Clamped against the attempt's snapshot weight if available, or the live question weight.
     */
    public function gradeEssayAnswer(Answer $answer, float $score, ?string $teacherNotes = null): Answer
    {
        $attempt = $answer->examAttempt;
        $maxWeight = 1.0;

        if ($attempt && is_array($attempt->questions_snapshot)) {
            $snapItem = collect($attempt->questions_snapshot)->firstWhere('id', $answer->question_id);
            if ($snapItem) {
                $maxWeight = (float) ($snapItem['bobot'] ?? 1);
            }
        } elseif ($answer->question) {
            $maxWeight = (float) ($answer->question->bobot ?: 1);
        }

        $clampedScore = max(0.0, min($score, $maxWeight));

        $answer->update([
            'skor' => $clampedScore,
            'is_correct' => $clampedScore > 0,
            'sudah_dinilai' => true,
            'catatan_guru' => $teacherNotes,
        ]);

        return $answer;
    }

    /**
     * Recalculate full score breakdown (nilai_pg, nilai_essay, nilai_akhir) for an attempt.
     * Utilizes attempt question snapshot to guarantee score immutability across future question edits.
     */
    public function recalculateAttemptScore(ExamAttempt $attempt): ExamAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if (!$lockedAttempt) {
                return $attempt;
            }

            $lockedAttempt->load('answers');
            $questions = $this->getAttemptQuestions($lockedAttempt);

            $totalWeight = (float) $questions->sum(fn ($q) => (float) ($q->bobot ?: 1));
            $totalPgEarned = 0.0;
            $totalEssayEarned = 0.0;
            $hasUngradedEssay = false;
            $hasEssayQuestions = false;

            $answersByQuestion = $lockedAttempt->answers->keyBy('question_id');

            foreach ($questions as $question) {
                $answer = $answersByQuestion->get($question->id);

                if ($question->tipe === 'pg') {
                    if ($answer && $answer->skor !== null) {
                        $totalPgEarned += (float) $answer->skor;
                    }
                } else {
                    $hasEssayQuestions = true;
                    if ($answer && $answer->sudah_dinilai) {
                        $totalEssayEarned += (float) ($answer->skor ?? 0.0);
                    } else {
                        $hasUngradedEssay = true;
                    }
                }
            }

            $totalEarned = $totalPgEarned + $totalEssayEarned;
            $finalScore = $totalWeight > 0 ? round(($totalEarned / $totalWeight) * 100, 2) : 0.0;

            $newStatus = $lockedAttempt->status;
            if ($hasEssayQuestions && $hasUngradedEssay) {
                // If attempt was submitted or expired, mark needs_grading
                if (in_array($lockedAttempt->status, ['submitted', 'expired'], true)) {
                    $newStatus = 'needs_grading';
                }
            } elseif ($hasEssayQuestions && !$hasUngradedEssay) {
                if ($lockedAttempt->status === 'needs_grading') {
                    $newStatus = 'completed';
                }
            }

            $lockedAttempt->update([
                'nilai_pg' => round($totalPgEarned, 2),
                'nilai_essay' => round($totalEssayEarned, 2),
                'nilai_akhir' => $finalScore,
                'status' => $newStatus,
            ]);

            return $lockedAttempt;
        });
    }

    /**
     * Get attempt questions from snapshot if available, or fall back to exam questions for historical records.
     */
    public function getAttemptQuestions(ExamAttempt $attempt): Collection
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
}
