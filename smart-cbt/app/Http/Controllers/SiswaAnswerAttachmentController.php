<?php

namespace App\Http\Controllers;

use App\Models\AnswerAttachment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Siswa;
use App\Services\Answer\AnswerAttachmentService;
use App\Services\Examination\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SiswaAnswerAttachmentController extends Controller
{
    public function __construct(
        protected ExamAttemptService $examAttemptService,
        protected AnswerAttachmentService $attachmentService
    ) {}

    public function store(Request $request, Exam $ujian, Question $question): JsonResponse
    {
        [$siswa, $attempt] = $this->authorizeAttempt($request, $ujian);

        $maxKb = (int) config('cbt.essay_photo_max_kb', 5120);
        $maxDim = (int) config('cbt.essay_photo_max_dimension', 4096);

        $validated = $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                "max:{$maxKb}",
                "dimensions:min_width=1,min_height=1,max_width={$maxDim},max_height={$maxDim}",
            ],
            'replace_attachment_id' => ['nullable', 'integer'],
        ], [
            'photo.required' => 'Pilih atau ambil foto jawaban terlebih dahulu.',
            'photo.image' => 'File harus berupa gambar yang valid.',
            'photo.mimes' => 'Format gambar yang didukung hanya JPG, JPEG, PNG, atau WEBP.',
            'photo.mimetypes' => 'Tipe file gambar tidak diizinkan.',
            'photo.max' => 'Ukuran foto maksimal ' . round($maxKb / 1024, 1) . ' MB.',
            'photo.dimensions' => "Dimensi foto maksimal {$maxDim}x{$maxDim} piksel.",
        ]);

        $replaceId = isset($validated['replace_attachment_id'])
            ? (int) $validated['replace_attachment_id']
            : null;

        try {
            $attachment = $this->attachmentService->uploadAttachment(
                $attempt,
                $ujian,
                $question->id,
                $request->file('photo'),
                $replaceId
            );
        } catch (InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'Waktu ujian') || str_contains($e->getMessage(), 'Ujian sudah selesai')
                ? 403
                : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }

        $totalAttachments = AnswerAttachment::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->count();

        return response()->json([
            'persisted' => true,
            'answer_type' => 'photo',
            'attachment' => $this->attachmentService->formatAttachmentDto($attachment),
            'total_attachments' => $totalAttachments,
        ], $replaceId !== null ? 200 : 201);
    }

    public function destroy(Request $request, Exam $ujian, AnswerAttachment $attachment): JsonResponse
    {
        [, $attempt] = $this->authorizeAttempt($request, $ujian);

        abort_unless((int) $attachment->exam_attempt_id === (int) $attempt->id, 403, 'Akses ditolak.');

        try {
            $remainingCount = $this->attachmentService->deleteAttachment($attempt, $ujian, $attachment);
        } catch (InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'Waktu ujian') || str_contains($e->getMessage(), 'Ujian sudah selesai')
                ? 403
                : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }

        return response()->json([
            'deleted' => true,
            'attachment_id' => $attachment->id,
            'question_id' => $attachment->question_id,
            'remaining_count' => $remainingCount,
        ]);
    }

    public function updateMode(Request $request, Exam $ujian, Question $question): JsonResponse
    {
        [, $attempt] = $this->authorizeAttempt($request, $ujian);

        $validated = $request->validate([
            'answer_type' => ['required', 'in:text,photo'],
        ]);

        try {
            $answer = $this->attachmentService->setAnswerMode(
                $attempt,
                $ujian,
                $question->id,
                $validated['answer_type']
            );
        } catch (InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'Waktu ujian') || str_contains($e->getMessage(), 'Ujian sudah selesai')
                ? 403
                : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }

        return response()->json([
            'saved' => true,
            'question_id' => $question->id,
            'answer_type' => $answer->answer_type,
        ]);
    }

    /**
     * @return array{0: Siswa, 1: ExamAttempt}
     */
    private function authorizeAttempt(Request $request, Exam $ujian): array
    {
        $siswa = $request->user()->siswa;
        abort_unless($siswa && $siswa->status_aktif, 403);

        $this->examAttemptService->ensureAssigned($ujian, $siswa);

        $attempt = ExamAttempt::where('exam_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->latest('attempt_number')
            ->first();

        abort_unless($attempt?->status === 'in_progress', 403, 'Ujian sudah selesai atau tidak aktif.');
        abort_unless(
            $request->session()->get("siswa.exam_token_verified.{$ujian->id}") === $attempt->id,
            403,
            'Token ujian tidak valid.'
        );

        return [$siswa, $attempt];
    }
}
