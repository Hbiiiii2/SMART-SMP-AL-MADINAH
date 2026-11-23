<?php

namespace App\Services\Examination;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    /**
     * Start a new attempt or resume an existing in-progress attempt.
     *
     * @throws ValidationException
     */
    public function startOrResumeAttempt(Exam $exam, Siswa $siswa, string $submittedToken): ExamAttempt
    {
        $this->ensureAssigned($exam, $siswa);

        $now = now();

        if ($exam->status !== 'aktif' || ! $exam->tanggal_mulai || $now->lt($exam->tanggal_mulai)) {
            throw ValidationException::withMessages(['token' => 'Ujian belum dapat dimulai.']);
        }

        if ($exam->tanggal_selesai && $now->gte($exam->tanggal_selesai)) {
            throw ValidationException::withMessages(['token' => 'Waktu ujian sudah berakhir.']);
        }

        $baseQuestions = $exam->questions()->orderBy('urutan')->get();
        if ($baseQuestions->isEmpty()) {
            throw ValidationException::withMessages(['token' => 'Soal ujian belum tersedia.']);
        }

        $cleanToken = strtoupper(trim($submittedToken));
        if (! $exam->token_aktif || ! $exam->token || ! hash_equals(strtoupper((string) $exam->token), $cleanToken)) {
            throw ValidationException::withMessages(['token' => 'Token ujian tidak valid.']);
        }

        $maxRetries = 5;
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            try {
                return DB::transaction(function () use ($exam, $siswa, $cleanToken, $now, $baseQuestions): ExamAttempt {
                    $attempt = ExamAttempt::where('exam_id', $exam->id)
                        ->where('siswa_id', $siswa->id)
                        ->where('attempt_number', 1)
                        ->first();

                    if (! $attempt) {
                        // Determine question ordering and capture immutable attempt snapshot
                        $questions = $exam->acak_soal ? $baseQuestions->shuffle() : $baseQuestions;

                        $questionOrder = $questions->pluck('id')->values()->all();

                        $questionsSnapshot = $questions->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'exam_id' => $q->exam_id,
                                'tipe' => $q->tipe,
                                'pertanyaan' => $q->pertanyaan,
                                'image' => $q->image,
                                'petunjuk_jawaban' => $q->petunjuk_jawaban,
                                'opsi_a' => $q->opsi_a,
                                'opsi_a_image' => $q->opsi_a_image,
                                'opsi_b' => $q->opsi_b,
                                'opsi_b_image' => $q->opsi_b_image,
                                'opsi_c' => $q->opsi_c,
                                'opsi_c_image' => $q->opsi_c_image,
                                'opsi_d' => $q->opsi_d,
                                'opsi_d_image' => $q->opsi_d_image,
                                'opsi_e' => $q->opsi_e,
                                'opsi_e_image' => $q->opsi_e_image,
                                'kunci' => $q->kunci,
                                'bobot' => (float) ($q->bobot ?: 1),
                                'urutan' => (int) $q->urutan,
                            ];
                        })->values()->all();

                        $attempt = ExamAttempt::create([
                            'exam_id' => $exam->id,
                            'siswa_id' => $siswa->id,
                            'attempt_number' => 1,
                            'started_at' => $now,
                            'status' => 'in_progress',
                            'token_used' => $cleanToken,
                            'question_order' => $questionOrder,
                            'questions_snapshot' => $questionsSnapshot,
                        ]);
                    } else {
                        $attempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();

                        if ($attempt->status !== 'in_progress') {
                            throw ValidationException::withMessages(['token' => 'Ujian ini sudah selesai dikerjakan.']);
                        }

                        $attempt->update(['token_used' => $cleanToken]);
                    }

                    return $attempt;
                });
            } catch (\Illuminate\Database\QueryException $e) {
                $errorCode = $e->errorInfo[1] ?? 0;
                // Retry with randomized jitter on deadlock (1213), lock wait timeout (1205), or duplicate entry race (1062)
                if (in_array($errorCode, [1213, 1205, 1062]) && $retry < $maxRetries - 1) {
                    usleep(random_int(10000, 50000)); // 10ms - 50ms randomized jitter
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Get exam questions sorted according to the attempt's persisted question order.
     * Note: Correct answer keys are explicitly omitted from student-facing models.
     */
    public function getOrderedQuestions(Exam $exam, ExamAttempt $attempt): Collection
    {
        if (is_array($attempt->questions_snapshot) && ! empty($attempt->questions_snapshot)) {
            $questions = collect($attempt->questions_snapshot)->map(function ($item) {
                // Ensure correct answer key is strictly stripped from student view models
                unset($item['kunci']);
                $q = new Question($item);
                $q->id = $item['id'];
                $q->exists = true;
                return $q;
            });

            $order = $attempt->question_order;
            if (is_array($order) && ! empty($order)) {
                $orderMap = array_flip($order);
                return $questions->sortBy(function ($q) use ($orderMap) {
                    return $orderMap[$q->id] ?? 999999;
                })->values();
            }

            return $questions->values();
        }

        // Fallback for historical attempts without snapshot
        $exam->load('questions');
        $questions = $exam->questions->map(function ($q) {
            $cloned = clone $q;
            $cloned->kunci = null;
            return $cloned;
        });

        $order = $attempt->question_order;

        if (is_array($order) && ! empty($order)) {
            $orderMap = array_flip($order);

            return $questions->sortBy(function ($q) use ($orderMap) {
                return $orderMap[$q->id] ?? 999999;
            })->values();
        }

        return $questions->sortBy('urutan')->values();
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
     * Ensure student belongs to the class assigned to the exam.
     */
    public function ensureAssigned(Exam $exam, Siswa $siswa): void
    {
        abort_unless(
            $siswa->kelas_id && $exam->kelas_id && (int) $exam->kelas_id === (int) $siswa->kelas_id,
            403,
            'Ujian ini tidak ditujukan untuk kelas Anda.'
        );
    }

    /**
     * Calculate absolute deadline for an attempt.
     */
    public function calculateDeadline(ExamAttempt $attempt): Carbon
    {
        $deadline = $attempt->started_at->copy()->addMinutes((int) $attempt->exam->durasi_menit);

        if ($attempt->exam->tanggal_selesai && $attempt->exam->tanggal_selesai->lt($deadline)) {
            $deadline = $attempt->exam->tanggal_selesai;
        }

        return $deadline;
    }
}
