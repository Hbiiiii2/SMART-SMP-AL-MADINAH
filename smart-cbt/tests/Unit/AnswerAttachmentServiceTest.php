<?php

namespace Tests\Unit;

use App\Models\Answer;
use App\Models\AnswerAttachment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Answer\AnswerAttachmentService;
use App\Services\Answer\AnswerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class AnswerAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnswerAttachmentService $attachmentService;
    protected AnswerService $answerService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('exam_private');
        $this->answerService = app(AnswerService::class);
        $this->attachmentService = app(AnswerAttachmentService::class);
    }

    private function createExamContext(): array
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama' => 'Guru Fisika',
            'kode_guru' => 'GF-' . uniqid(),
        ]);
        $kelas = Kelas::create(['nama_kelas' => 'VIII-B', 'tingkat' => '8', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'FIS-' . uniqid(), 'nama' => 'Fisika']);

        $siswaUser = User::factory()->create(['role' => 'siswa']);
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'nis' => '9001',
            'nisn' => '90019001',
            'nama' => 'Siti Pelajar',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Ujian Fisika Terapan',
            'kode_ujian' => 'FIS-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinutes(5),
            'tanggal_selesai' => now()->addHour(),
            'komponen_soal' => ['pg', 'essay_1', 'essay_2'],
        ]);

        $pg = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Satuan gaya?',
            'opsi_a' => 'Newton',
            'opsi_b' => 'Joule',
            'opsi_c' => 'Watt',
            'opsi_d' => 'Pascal',
            'kunci' => 'A',
            'bobot' => 2,
            'urutan' => 1,
        ]);

        $essay1 = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'essay_1',
            'pertanyaan' => 'Tuliskan penurunan rumus GLBB!',
            'bobot' => 5,
            'urutan' => 2,
        ]);

        $essay2 = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'essay_2',
            'pertanyaan' => 'Gambarkan diagram gaya bebas!',
            'bobot' => 5,
            'urutan' => 3,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $siswa->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'token_used' => 'FIS123',
        ]);

        return [$exam, $attempt, $pg, $essay1, $essay2];
    }

    public function test_max_three_photos_enforced_per_essay_question(): void
    {
        [$exam, $attempt, , $essay1] = $this->createExamContext();

        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("jawab_{$i}.jpg", 400, 300);
            $att = $this->attachmentService->uploadAttachment($attempt, $exam, $essay1->id, $file);
            $this->assertEquals($i, $att->urutan);
            Storage::disk('exam_private')->assertExists($att->file_path);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maksimal 3 foto');

        $extra = UploadedFile::fake()->image('jawab_4.jpg', 400, 300);
        $this->attachmentService->uploadAttachment($attempt, $exam, $essay1->id, $extra);
    }

    public function test_pg_question_rejects_photo_attachment(): void
    {
        [$exam, $attempt, $pg] = $this->createExamContext();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hanya diperbolehkan untuk soal essay');

        $file = UploadedFile::fake()->image('pg.png', 200, 200);
        $this->attachmentService->uploadAttachment($attempt, $exam, $pg->id, $file);
    }

    public function test_replace_deletes_old_file_and_keeps_slot_order(): void
    {
        [$exam, $attempt, , , $essay2] = $this->createExamContext();

        $oldFile = UploadedFile::fake()->image('old.png', 300, 200);
        $attachment = $this->attachmentService->uploadAttachment($attempt, $exam, $essay2->id, $oldFile);
        $oldPath = $attachment->file_path;

        Storage::disk('exam_private')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->image('new.webp', 500, 400);
        $replaced = $this->attachmentService->uploadAttachment($attempt, $exam, $essay2->id, $newFile, $attachment->id);

        $this->assertEquals($attachment->id, $replaced->id);
        $this->assertEquals(1, $replaced->urutan);
        $this->assertNotEquals($oldPath, $replaced->file_path);
        Storage::disk('exam_private')->assertMissing($oldPath);
        Storage::disk('exam_private')->assertExists($replaced->file_path);
    }

    public function test_failed_replace_keeps_original_file_and_cleans_new_file(): void
    {
        [$exam, $attempt, , $essay1] = $this->createExamContext();

        $oldFile = UploadedFile::fake()->image('valid.jpg', 300, 200);
        $attachment = $this->attachmentService->uploadAttachment($attempt, $exam, $essay1->id, $oldFile);
        $oldPath = $attachment->file_path;

        try {
            $newFile = UploadedFile::fake()->image('replacement.jpg', 300, 200);
            $this->attachmentService->uploadAttachment($attempt, $exam, $essay1->id, $newFile, 999999);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('tidak ditemukan', $e->getMessage());
        }

        Storage::disk('exam_private')->assertExists($oldPath);
        $this->assertCount(1, Storage::disk('exam_private')->allFiles());
    }

    public function test_upload_rejected_when_attempt_not_in_progress(): void
    {
        [$exam, $attempt, , $essay1] = $this->createExamContext();
        $attempt->update(['status' => 'needs_grading']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah selesai');

        $file = UploadedFile::fake()->image('late.jpg', 200, 200);
        $this->attachmentService->uploadAttachment($attempt, $exam, $essay1->id, $file);
    }

    public function test_submit_attempt_preserves_photo_answer_type_and_attachments(): void
    {
        [$exam, $attempt, $pg, $essay1, $essay2] = $this->createExamContext();

        $att1 = $this->attachmentService->uploadAttachment($attempt, $exam, $essay1->id, UploadedFile::fake()->image('e1.jpg', 300, 300));
        $att2 = $this->attachmentService->uploadAttachment($attempt, $exam, $essay2->id, UploadedFile::fake()->image('e2.png', 300, 300));

        $completed = $this->answerService->submitAttemptAnswers($attempt, [
            $pg->id => 'A',
            $essay1->id => '',
            $essay2->id => '',
        ], $exam);

        $this->assertEquals('needs_grading', $completed->status);

        $ans1 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $essay1->id)->first();
        $ans2 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $essay2->id)->first();

        $this->assertEquals('photo', $ans1->answer_type);
        $this->assertEquals('photo', $ans2->answer_type);
        $this->assertEquals(1, $ans1->attachments()->count());
        $this->assertEquals(1, $ans2->attachments()->count());
        $this->assertEquals($att1->id, $ans1->attachments()->first()->id);
        $this->assertEquals($att2->id, $ans2->attachments()->first()->id);
    }
}
