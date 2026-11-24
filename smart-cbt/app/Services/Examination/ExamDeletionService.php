<?php

namespace App\Services\Examination;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExamDeletionService
{
    /**
     * Public storage columns on questions and bank_questions.
     *
     * @var list<string>
     */
    private const QUESTION_IMAGE_COLUMNS = [
        'image',
        'opsi_a_image',
        'opsi_b_image',
        'opsi_c_image',
        'opsi_d_image',
        'opsi_e_image',
    ];

    /**
     * Permanently delete an exam schedule and all dependent CBT records and orphaned files.
     *
     * @return array{
     *     exam_id: int,
     *     exam_name: string,
     *     kode_ujian: ?string,
     *     questions_deleted: int,
     *     attempts_deleted: int,
     *     answers_deleted: int,
     *     attachments_deleted: int,
     *     synced_banks_deleted: int
     * }
     */
    public function delete(Exam $exam, bool $deleteSyncedQuestionBanks = false, ?User $actor = null): array
    {
        return DB::transaction(function () use ($exam, $deleteSyncedQuestionBanks, $actor): array {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->first();

            if (! $lockedExam) {
                return [
                    'exam_id' => (int) $exam->id,
                    'exam_name' => (string) $exam->nama,
                    'kode_ujian' => $exam->kode_ujian,
                    'questions_deleted' => 0,
                    'attempts_deleted' => 0,
                    'answers_deleted' => 0,
                    'attachments_deleted' => 0,
                    'synced_banks_deleted' => 0,
                ];
            }

            $examId = (int) $lockedExam->id;
            $examName = (string) $lockedExam->nama;
            $examCode = $lockedExam->kode_ujian;

            $questionIds = DB::table('questions')
                ->where('exam_id', $examId)
                ->pluck('id')
                ->all();

            $attemptIds = DB::table('exam_attempts')
                ->where('exam_id', $examId)
                ->pluck('id')
                ->all();

            $syncedBankIds = $deleteSyncedQuestionBanks
                ? DB::table('question_banks')
                    ->where('source_exam_id', $examId)
                    ->pluck('id')
                    ->all()
                : [];

            $attachmentDisk = (string) config('cbt.essay_photo_disk', 'exam_private');
            $attachmentPaths = [];

            if (! empty($attemptIds) || ! empty($questionIds)) {
                $attachmentPaths = $this->scopeByAttemptOrQuestion(
                    DB::table('answer_attachments'),
                    $attemptIds,
                    $questionIds
                )
                    ->pluck('file_path')
                    ->map(static fn ($path): string => trim((string) $path))
                    ->filter(static fn (string $path): bool => $path !== '')
                    ->unique()
                    ->values()
                    ->all();
            }

            $candidatePublicImages = array_values(array_unique(array_merge(
                $this->collectImagePaths('questions', 'exam_id', [$examId]),
                $deleteSyncedQuestionBanks
                    ? $this->collectImagePaths('bank_questions', 'question_bank_id', $syncedBankIds)
                    : []
            )));

            // Delete child rows before parent rows to satisfy RESTRICT foreign keys
            // (answers.question_id and answer_attachments.question_id) and avoid Question::deleting guards.
            $attachmentsDeleted = 0;
            $answersDeleted = 0;
            if (! empty($attemptIds) || ! empty($questionIds)) {
                $attachmentsDeleted = $this->scopeByAttemptOrQuestion(
                    DB::table('answer_attachments'),
                    $attemptIds,
                    $questionIds
                )->delete();

                $answersDeleted = $this->scopeByAttemptOrQuestion(
                    DB::table('answers'),
                    $attemptIds,
                    $questionIds
                )->delete();
            }

            $attemptsDeleted = DB::table('exam_attempts')
                ->where('exam_id', $examId)
                ->delete();

            $questionsDeleted = DB::table('questions')
                ->where('exam_id', $examId)
                ->delete();

            DB::table('exam_supervisors')
                ->where('exam_id', $examId)
                ->delete();

            $syncedBanksDeleted = 0;
            if ($deleteSyncedQuestionBanks && ! empty($syncedBankIds)) {
                DB::table('bank_questions')
                    ->whereIn('question_bank_id', $syncedBankIds)
                    ->delete();

                $syncedBanksDeleted = DB::table('question_banks')
                    ->whereIn('id', $syncedBankIds)
                    ->delete();
            } else {
                DB::table('question_banks')
                    ->where('source_exam_id', $examId)
                    ->update(['source_exam_id' => null]);
            }

            DB::table('exams')
                ->where('id', $examId)
                ->delete();

            // Check remaining references after deleting exam questions (and optional synced bank questions).
            $orphanedPublicImages = $this->filterOrphanedPublicImages($candidatePublicImages);

            $summary = [
                'exam_id' => $examId,
                'exam_name' => $examName,
                'kode_ujian' => $examCode,
                'questions_deleted' => (int) $questionsDeleted,
                'attempts_deleted' => (int) $attemptsDeleted,
                'answers_deleted' => (int) $answersDeleted,
                'attachments_deleted' => (int) $attachmentsDeleted,
                'synced_banks_deleted' => (int) $syncedBanksDeleted,
            ];

            DB::afterCommit(function () use (
                $attachmentDisk,
                $attachmentPaths,
                $examId,
                $orphanedPublicImages,
                $actor,
                $summary
            ): void {
                $this->cleanupPrivateAttachments($attachmentDisk, $attachmentPaths, $examId);
                $this->cleanupPublicImages($orphanedPublicImages);

                Log::info('Super admin deleted exam schedule', array_merge($summary, [
                    'admin_user_id' => $actor?->id,
                    'admin_email' => $actor?->email,
                ]));
            });

            return $summary;
        }, 5);
    }

    /**
     * Scope a query to rows belonging to any deleted attempt or question ID.
     *
     * @param  list<int|string>  $attemptIds
     * @param  list<int|string>  $questionIds
     */
    private function scopeByAttemptOrQuestion(Builder $query, array $attemptIds, array $questionIds): Builder
    {
        return $query->where(function (Builder $subQuery) use ($attemptIds, $questionIds): void {
            if (! empty($attemptIds)) {
                $subQuery->whereIn('exam_attempt_id', $attemptIds);
                if (! empty($questionIds)) {
                    $subQuery->orWhereIn('question_id', $questionIds);
                }
            } elseif (! empty($questionIds)) {
                $subQuery->whereIn('question_id', $questionIds);
            }
        });
    }

    /**
     * Collect all non-empty image paths across question and option image columns.
     *
     * @param  list<int|string>  $ids
     * @return list<string>
     */
    private function collectImagePaths(string $table, string $foreignKey, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = DB::table($table)
            ->whereIn($foreignKey, $ids)
            ->get(self::QUESTION_IMAGE_COLUMNS);

        $paths = [];
        foreach ($rows as $row) {
            foreach (self::QUESTION_IMAGE_COLUMNS as $column) {
                $value = trim((string) ($row->{$column} ?? ''));
                if ($value !== '') {
                    $paths[] = $value;
                }
            }
        }

        return $paths;
    }

    /**
     * Return only image paths that are no longer referenced by any remaining question or bank question.
     *
     * @param  list<string>  $candidatePaths
     * @return list<string>
     */
    private function filterOrphanedPublicImages(array $candidatePaths): array
    {
        if (empty($candidatePaths)) {
            return [];
        }

        $stillReferenced = [];

        foreach (['questions', 'bank_questions'] as $table) {
            foreach (self::QUESTION_IMAGE_COLUMNS as $column) {
                $referenced = DB::table($table)
                    ->whereIn($column, $candidatePaths)
                    ->pluck($column)
                    ->map(static fn ($path): string => trim((string) $path))
                    ->filter(static fn (string $path): bool => $path !== '')
                    ->all();

                foreach ($referenced as $path) {
                    $stillReferenced[$path] = true;
                }
            }
        }

        return array_values(array_filter(
            $candidatePaths,
            static fn (string $path): bool => ! isset($stillReferenced[$path])
        ));
    }

    /**
     * Remove private essay attachment files and exam directory after DB commit.
     *
     * @param  list<string>  $attachmentPaths
     */
    private function cleanupPrivateAttachments(string $diskName, array $attachmentPaths, int $examId): void
    {
        $disk = Storage::disk($diskName);

        foreach ($attachmentPaths as $path) {
            try {
                $disk->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete private essay attachment after exam deletion', [
                    'disk' => $diskName,
                    'path' => $path,
                    'exam_id' => $examId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $examDir = "exam_{$examId}";
        try {
            if ($disk->directoryExists($examDir)) {
                $disk->deleteDirectory($examDir);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to delete exam private attachment directory', [
                'disk' => $diskName,
                'directory' => $examDir,
                'exam_id' => $examId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove orphaned public question/option images after DB commit.
     *
     * @param  list<string>  $imagePaths
     */
    private function cleanupPublicImages(array $imagePaths): void
    {
        if (empty($imagePaths)) {
            return;
        }

        $publicDisk = Storage::disk('public');

        foreach ($imagePaths as $path) {
            try {
                $publicDisk->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete orphaned question image after exam deletion', [
                    'disk' => 'public',
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
