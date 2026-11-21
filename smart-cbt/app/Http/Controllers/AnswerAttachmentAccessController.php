<?php

namespace App\Http\Controllers;

use App\Models\AnswerAttachment;
use App\Services\Answer\AnswerAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnswerAttachmentAccessController extends Controller
{
    public function __construct(
        protected AnswerAttachmentService $attachmentService
    ) {}

    public function show(Request $request, AnswerAttachment $attachment): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $attachment->loadMissing('examAttempt.exam.supervisors');
        $attempt = $attachment->examAttempt;
        $exam = $attempt?->exam;

        abort_unless($attempt && $exam, 404);

        if ($user->isAdmin()) {
            // Admin may view any attachment.
        } elseif ($user->isSiswa()) {
            $siswaId = $user->siswa?->id;
            abort_unless($siswaId && (int) $attempt->siswa_id === (int) $siswaId, 403, 'Anda tidak memiliki akses ke lampiran ini.');
        } elseif ($user->isGuru()) {
            $guruId = $user->guru?->id;
            $hasAccess = $guruId && (
                (int) $exam->guru_id === (int) $guruId
                || (int) $exam->guru_pengawas_id === (int) $guruId
                || $exam->supervisors->contains('id', $guruId)
            );
            abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lampiran ujian ini.');
        } else {
            abort(403);
        }

        $disk = Storage::disk($this->attachmentService->diskName());
        abort_unless($disk->exists($attachment->file_path), 404);

        $filename = $attachment->original_name ?: ('lampiran_' . $attachment->id . '.jpg');

        return $disk->response($attachment->file_path, $filename, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
        ]);
    }
}
