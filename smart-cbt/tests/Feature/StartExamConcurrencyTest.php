<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Examination\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartExamConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_attempt_idempotency_and_question_order_persistence(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru MTK', 'kode_guru' => 'G-MTK']);
        $class = Kelas::create(['nama_kelas' => 'IX-A', 'tingkat' => 'IX', 'status' => 'aktif']);
        $subject = MataPelajaran::create(['kode' => 'MTK', 'nama' => 'Matematika']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '3001',
            'nisn' => '30013001',
            'nama' => 'Budi Siswa',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'nama' => 'Ujian Akhir MTK',
            'kode_ujian' => 'MTK-UAS-01',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinutes(5),
            'tanggal_selesai' => now()->addHours(2),
            'token' => 'MTK999',
            'token_aktif' => true,
            'acak_soal' => true,
        ]);

        // Create 5 questions
        for ($i = 1; $i <= 5; $i++) {
            Question::create([
                'exam_id' => $exam->id,
                'tipe' => 'pg',
                'pertanyaan' => "Soal nomor {$i}",
                'kunci' => 'A',
                'bobot' => 1.0,
                'urutan' => $i,
            ]);
        }

        $attemptService = app(ExamAttemptService::class);

        // First start call
        $attempt1 = $attemptService->startOrResumeAttempt($exam, $student, 'MTK999');

        $this->assertNotNull($attempt1);
        $this->assertEquals(1, $attempt1->attempt_number);
        $this->assertEquals('in_progress', $attempt1->status);
        $this->assertIsArray($attempt1->question_order);
        $this->assertCount(5, $attempt1->question_order);

        $orderedQuestions1 = $attemptService->getOrderedQuestions($exam, $attempt1);
        $orderedIds1 = $orderedQuestions1->pluck('id')->all();
        $this->assertEquals($attempt1->question_order, $orderedIds1);

        // Concurrent / Second start call (resume with same token)
        $attempt2 = $attemptService->startOrResumeAttempt($exam, $student, 'MTK999');

        $this->assertEquals($attempt1->id, $attempt2->id);
        $this->assertEquals(1, ExamAttempt::where('exam_id', $exam->id)->where('siswa_id', $student->id)->count());

        // Question order must remain 100% stable across resumes
        $orderedQuestions2 = $attemptService->getOrderedQuestions($exam, $attempt2);
        $orderedIds2 = $orderedQuestions2->pluck('id')->all();
        $this->assertEquals($orderedIds1, $orderedIds2);
    }

    public function test_student_from_different_class_is_forbidden(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru MTK', 'kode_guru' => 'G-MTK2']);
        $classA = Kelas::create(['nama_kelas' => 'VII-A', 'tingkat' => 'VII', 'status' => 'aktif']);
        $classB = Kelas::create(['nama_kelas' => 'VII-B', 'tingkat' => 'VII', 'status' => 'aktif']);
        $subject = MataPelajaran::create(['kode' => 'ENG', 'nama' => 'English']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $studentB = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '4001',
            'nisn' => '40014001',
            'nama' => 'Siswa Kelas B',
            'kelas_id' => $classB->id,
            'kelas' => $classB->nama_kelas,
            'status_aktif' => true,
        ]);

        $examForClassA = Exam::create([
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'nama' => 'English Quiz A',
            'kode_ujian' => 'ENG-QUIZ-A',
            'kelas_id' => $classA->id,
            'kelas' => $classA->nama_kelas,
            'durasi_menit' => 30,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinutes(5),
            'token' => 'ENG111',
            'token_aktif' => true,
        ]);

        Question::create([
            'exam_id' => $examForClassA->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Question?',
            'kunci' => 'A',
            'bobot' => 1.0,
            'urutan' => 1,
        ]);

        $attemptService = app(ExamAttemptService::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $attemptService->startOrResumeAttempt($examForClassA, $studentB, 'ENG111');
    }
}
