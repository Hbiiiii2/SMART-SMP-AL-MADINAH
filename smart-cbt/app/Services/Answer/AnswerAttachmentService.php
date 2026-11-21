<?php

namespace App\Services\Answer;

use App\Models\Answer;
use App\Models\AnswerAttachment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AnswerAttachmentService
{
    public function __construct(
        protected AnswerService $answerService
    ) {}

    /**
     * Validate, store privately, and attach an essay photo to a student's exam attempt.
     */
    public function uploadAttachment(
        ExamAttempt $attempt,
        Exam $exam,
        int $questionId,
        UploadedFile $file,
        ?int $replaceAttachmentId = null
    ): AnswerAttachment {
        $this->assertAttemptModifiable($attempt);
        $this->assertEssayQuestion($attempt, $questionId);

        $prepared = $this->prepareImagePayload($file);
        $disk = $this->diskName();
        $relativePath = sprintf(
            'exam_%d/attempt_%d/q_%d/%s.%s',
            $exam->id,
            $attempt->id,
            $questionId,
            (string) Str::uuid(),
            $prepared['extension']
        );

        // Write binary file outside DB::transaction so row locks are never held during disk I/O.
        if (! Storage::disk($disk)->put($relativePath, $prepared['bytes'])) {
            throw new RuntimeException('Gagal menyimpan foto ke penyimpanan server.');
        }

        try {
            return DB::transaction(function () use (
                $attempt,
                $exam,
                $questionId,
                $file,
                $prepared,
                $relativePath,
                $replaceAttachmentId,
                $disk
            ): AnswerAttachment {
                $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();
                if (! $lockedAttempt || $lockedAttempt->exam_id !== $exam->id) {
                    throw new InvalidArgumentException('Sesi ujian tidak valid.');
                }

                $this->assertAttemptModifiable($lockedAttempt);

                $answer = Answer::where('exam_attempt_id', $lockedAttempt->id)
                    ->where('question_id', $questionId)
                    ->first();

                if (! $answer) {
                    $answer = Answer::create([
                        'exam_attempt_id' => $lockedAttempt->id,
                        'question_id' => $questionId,
                        'answer_type' => 'photo',
                    ]);
                } elseif ($answer->answer_type !== 'photo') {
                    $answer->update(['answer_type' => 'photo']);
                }

                $originalName = Str::limit(
                    preg_replace('/[^\w.\- ]+/', '_', (string) $file->getClientOriginalName()),
                    240,
                    ''
                ) ?: ('foto_' . now()->format('His') . '.' . $prepared['extension']);

                if ($replaceAttachmentId !== null) {
                    $attachment = AnswerAttachment::where('id', $replaceAttachmentId)
                        ->where('answer_id', $answer->id)
                        ->where('exam_attempt_id', $lockedAttempt->id)
                        ->first();

                    if (! $attachment) {
                        throw new InvalidArgumentException('Foto lampiran yang akan diganti tidak ditemukan.');
                    }

                    $oldPath = $attachment->file_path;

                    $attachment->update([
                        'file_path' => $relativePath,
                        'original_name' => $originalName,
                        'mime_type' => $prepared['mime'],
                        'file_size' => $prepared['size'],
                        'width' => $prepared['width'],
                        'height' => $prepared['height'],
                        'checksum_sha256' => $prepared['sha256'],
                    ]);

                    DB::afterCommit(static function () use ($disk, $oldPath): void {
                        if ($oldPath !== '') {
                            Storage::disk($disk)->delete($oldPath);
                        }
                    });

                    return $attachment->fresh();
                }

                $maxPhotos = (int) config('cbt.essay_max_photos', 3);
                $existingCount = $answer->attachments()->count();
                if ($existingCount >= $maxPhotos) {
                    throw new InvalidArgumentException("Maksimal {$maxPhotos} foto untuk setiap soal essay.");
                }

                $nextOrder = ((int) $answer->attachments()->max('urutan')) + 1;

                return AnswerAttachment::create([
                    'answer_id' => $answer->id,
                    'exam_attempt_id' => $lockedAttempt->id,
                    'question_id' => $questionId,
                    'file_path' => $relativePath,
                    'original_name' => $originalName,
                    'mime_type' => $prepared['mime'],
                    'file_size' => $prepared['size'],
                    'width' => $prepared['width'],
                    'height' => $prepared['height'],
                    'urutan' => $nextOrder,
                    'checksum_sha256' => $prepared['sha256'],
                ]);
            }, 5);
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($relativePath);
            throw $e;
        }
    }

    /**
     * Delete an existing photo attachment while attempt is in progress.
     */
    public function deleteAttachment(ExamAttempt $attempt, Exam $exam, AnswerAttachment $attachment): int
    {
        $disk = $this->diskName();

        return DB::transaction(function () use ($attempt, $exam, $attachment, $disk): int {
            $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if (! $lockedAttempt || $lockedAttempt->exam_id !== $exam->id) {
                throw new InvalidArgumentException('Sesi ujian tidak valid.');
            }

            $this->assertAttemptModifiable($lockedAttempt);

            if ((int) $attachment->exam_attempt_id !== (int) $lockedAttempt->id) {
                throw new InvalidArgumentException('Lampiran tidak termasuk dalam sesi ujian ini.');
            }

            $answerId = $attachment->answer_id;
            $oldPath = $attachment->file_path;

            $attachment->delete();

            DB::afterCommit(static function () use ($disk, $oldPath): void {
                if ($oldPath !== '') {
                    Storage::disk($disk)->delete($oldPath);
                }
            });

            return AnswerAttachment::where('answer_id', $answerId)->count();
        }, 5);
    }

    /**
     * Persist essay answer mode ('text' or 'photo') without disrupting monotonic text client_seq.
     */
    public function setAnswerMode(ExamAttempt $attempt, Exam $exam, int $questionId, string $mode): Answer
    {
        if (! in_array($mode, ['text', 'photo'], true)) {
            throw new InvalidArgumentException('Metode jawaban harus text atau photo.');
        }

        $this->assertAttemptModifiable($attempt);
        $this->assertEssayQuestion($attempt, $questionId);

        return DB::transaction(function () use ($attempt, $exam, $questionId, $mode): Answer {
            $lockedAttempt = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if (! $lockedAttempt || $lockedAttempt->exam_id !== $exam->id) {
                throw new InvalidArgumentException('Sesi ujian tidak valid.');
            }

            $this->assertAttemptModifiable($lockedAttempt);

            $answer = Answer::where('exam_attempt_id', $lockedAttempt->id)
                ->where('question_id', $questionId)
                ->first();

            if ($answer) {
                $answer->update(['answer_type' => $mode]);
                return $answer;
            }

            return Answer::create([
                'exam_attempt_id' => $lockedAttempt->id,
                'question_id' => $questionId,
                'answer_type' => $mode,
            ]);
        }, 5);
    }

    /**
     * Format attachment payload for frontend exam view & upload responses.
     *
     * @return array<string, mixed>
     */
    public function formatAttachmentDto(AnswerAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'question_id' => $attachment->question_id,
            'url' => route('lampiran-jawaban.show', $attachment, false),
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'file_size' => (int) $attachment->file_size,
            'width' => $attachment->width,
            'height' => $attachment->height,
            'urutan' => (int) $attachment->urutan,
            'persisted' => true,
        ];
    }

    public function diskName(): string
    {
        return (string) config('cbt.essay_photo_disk', 'exam_private');
    }

    private function assertAttemptModifiable(ExamAttempt $attempt): void
    {
        if ($attempt->status !== 'in_progress') {
            throw new InvalidArgumentException('Ujian sudah selesai atau tidak aktif.');
        }

        $deadline = $this->answerService->calculateDeadline($attempt);
        if (now()->gt($deadline->copy()->addMinutes(10))) {
            throw new InvalidArgumentException('Waktu ujian sudah berakhir.');
        }
    }

    private function assertEssayQuestion(ExamAttempt $attempt, int $questionId): void
    {
        $questions = $this->answerService->getAttemptQuestionsForScoring($attempt);
        $question = $questions->firstWhere('id', $questionId);

        if (! $question) {
            throw new InvalidArgumentException('Soal tidak ditemukan dalam ujian ini.');
        }

        if ($question->tipe === 'pg') {
            throw new InvalidArgumentException('Lampiran foto hanya diperbolehkan untuk soal essay.');
        }
    }

    /**
     * Inspect magic bytes, validate dimensions, and fix JPEG EXIF orientation when needed.
     *
     * @return array{bytes: string, mime: string, extension: string, width: int, height: int, size: int, sha256: string}
     */
    private function prepareImagePayload(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        if (! $realPath || ! is_readable($realPath)) {
            throw new InvalidArgumentException('File foto tidak dapat dibaca.');
        }

        $bytes = file_get_contents($realPath);
        if ($bytes === false || $bytes === '') {
            throw new InvalidArgumentException('File foto kosong atau rusak.');
        }

        $imageInfo = @getimagesizefromstring($bytes);
        if (! is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1]) || empty($imageInfo['mime'])) {
            throw new InvalidArgumentException('File bukan gambar yang valid.');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $mime = strtolower((string) $imageInfo['mime']);

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! array_key_exists($mime, $extensionMap)) {
            throw new InvalidArgumentException('Format gambar yang didukung hanya JPG, PNG, atau WEBP.');
        }

        $maxDim = (int) config('cbt.essay_photo_max_dimension', 4096);
        if ($width < 1 || $height < 1 || $width > $maxDim || $height > $maxDim) {
            throw new InvalidArgumentException("Dimensi gambar tidak valid atau melebihi {$maxDim}x{$maxDim} piksel.");
        }

        // Normalize rotated smartphone JPEGs upright if EXIF orientation tag is present.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data') && function_exists('imagecreatefromstring')) {
            $exif = @exif_read_data($realPath);
            $orientation = (int) ($exif['Orientation'] ?? 1);
            if (in_array($orientation, [3, 6, 8], true)) {
                $oriented = $this->reorientJpeg($bytes, $orientation);
                if ($oriented !== null) {
                    $bytes = $oriented['bytes'];
                    $width = $oriented['width'];
                    $height = $oriented['height'];
                }
            }
        }

        return [
            'bytes' => $bytes,
            'mime' => $mime,
            'extension' => $extensionMap[$mime],
            'width' => $width,
            'height' => $height,
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
        ];
    }

    /**
     * @return array{bytes: string, width: int, height: int}|null
     */
    private function reorientJpeg(string $bytes, int $orientation): ?array
    {
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            return null;
        }

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        $rotated = $angle !== 0 ? @imagerotate($image, $angle, 0) : $image;
        if (! $rotated) {
            imagedestroy($image);
            return null;
        }

        ob_start();
        $ok = imagejpeg($rotated, null, 85);
        $output = ob_get_clean();

        $newWidth = imagesx($rotated);
        $newHeight = imagesy($rotated);

        if ($rotated !== $image) {
            imagedestroy($rotated);
        }
        imagedestroy($image);

        if (! $ok || ! is_string($output) || $output === '') {
            return null;
        }

        return [
            'bytes' => $output,
            'width' => $newWidth,
            'height' => $newHeight,
        ];
    }
}
