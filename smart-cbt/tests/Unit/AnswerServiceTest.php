<?php

namespace Tests\Unit;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Answer\AnswerService;
use App\Services\Examination\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnswerService $answerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->answerService = new AnswerService(new ScoringService());
    }

    private function createExamWithAttempt(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Test', 'kode_guru' => 'G-TEST']);
        $class = Kelas::create(['nama_kelas' => 'VII-A', 'tingkat' => 'VII', 'status' => 'aktif']);
        $subject = MataPelajaran::create(['kode' => 'MAT', 'nama' => 'Matematika']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '1002',
            'nisn' => '10021002',
            'nama' => 'Siswa Test 2',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'nama' => 'Ujian Matematika',
            'kode_ujian' => 'EXAM-' . uniqid(),
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'token' => 'TOK456',
            'token_aktif' => true,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return [$exam, $attempt];
    }

    public function test_save_batch_answers_upserts_in_bulk(): void
    {
        [$exam, $attempt] = $this->createExamWithAttempt();

        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => '1+1=?', 'kunci' => 'B', 'bobot' => 1, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => '2+2=?', 'kunci' => 'C', 'bobot' => 1, 'urutan' => 2]);

        $result = $this->answerService->saveBatchAnswers($attempt, [
            $q1->id => 'B',
            $q2->id => 'A',
        ], $exam);

        $this->assertTrue($result['saved']);
        $this->assertEquals(2, $result['count']);

        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'B',
        ]);
        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'jawaban' => 'A',
        ]);

        // Second autosave update changes answer for Q2 without duplicating row
        $this->answerService->saveBatchAnswers($attempt, [
            $q2->id => 'C',
        ], $exam);

        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'jawaban' => 'C',
        ]);
        $this->assertEquals(2, Answer::where('exam_attempt_id', $attempt->id)->count());
    }

    public function test_save_batch_answers_ignores_unrelated_questions(): void
    {
        [$exam, $attempt] = $this->createExamWithAttempt();

        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Valid Q', 'kunci' => 'A', 'bobot' => 1, 'urutan' => 1]);

        $result = $this->answerService->saveBatchAnswers($attempt, [
            $q1->id => 'A',
            999999 => 'X', // Fake question ID
        ], $exam);

        $this->assertEquals(1, $result['count']);
        $this->assertEquals(1, Answer::where('exam_attempt_id', $attempt->id)->count());
    }
}
