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
use App\Services\Examination\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = new ScoringService();
    }

    private function createExamWithAttempt(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Test', 'kode_guru' => 'G-TEST']);
        $class = Kelas::create(['nama_kelas' => 'VII-A', 'tingkat' => 'VII', 'status' => 'aktif']);
        $subject = MataPelajaran::create(['kode' => 'BIO', 'nama' => 'Biologi']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '1001',
            'nisn' => '10011001',
            'nama' => 'Siswa Test',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'nama' => 'Ujian Test',
            'kode_ujian' => 'EXAM-' . uniqid(),
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'token' => 'TOK123',
            'token_aktif' => true,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'submitted',
        ]);

        return [$exam, $attempt];
    }

    public function test_evaluate_pg_question_correct_answer(): void
    {
        $question = new Question([
            'tipe' => 'pg',
            'kunci' => 'A',
            'bobot' => 2.0,
        ]);

        $result = $this->scoringService->evaluatePgQuestion($question, 'a');

        $this->assertTrue($result['is_correct']);
        $this->assertEquals(2.0, $result['skor']);
    }

    public function test_evaluate_pg_question_wrong_answer(): void
    {
        $question = new Question([
            'tipe' => 'pg',
            'kunci' => 'B',
            'bobot' => 2.0,
        ]);

        $result = $this->scoringService->evaluatePgQuestion($question, 'C');

        $this->assertFalse($result['is_correct']);
        $this->assertEquals(0.0, $result['skor']);
    }

    public function test_grade_essay_answer_clamps_to_max_weight(): void
    {
        [$exam, $attempt] = $this->createExamWithAttempt();

        $question = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'essay',
            'pertanyaan' => 'Explain photosynthesis',
            'bobot' => 10.0,
            'urutan' => 1,
        ]);

        $answer = Answer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'jawaban' => 'Photosynthesis is...',
            'is_correct' => false,
            'skor' => 0.0,
            'sudah_dinilai' => false,
        ]);

        $graded = $this->scoringService->gradeEssayAnswer($answer, 15.0, 'Very good answer');

        $this->assertEquals(10.0, $graded->skor);
        $this->assertTrue($graded->is_correct);
        $this->assertTrue($graded->sudah_dinilai);
        $this->assertEquals('Very good answer', $graded->catatan_guru);
    }

    public function test_recalculate_attempt_score_with_mixed_questions(): void
    {
        [$exam, $attempt] = $this->createExamWithAttempt();

        // 2 PG questions (weight 2 each) + 1 Essay (weight 6) = total weight 10
        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q1', 'kunci' => 'A', 'bobot' => 2.0, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q2', 'kunci' => 'B', 'bobot' => 2.0, 'urutan' => 2]);
        $q3 = Question::create(['exam_id' => $exam->id, 'tipe' => 'essay', 'pertanyaan' => 'Q3', 'bobot' => 6.0, 'urutan' => 3]);

        Answer::create(['exam_attempt_id' => $attempt->id, 'question_id' => $q1->id, 'jawaban' => 'A', 'is_correct' => true, 'skor' => 2.0, 'sudah_dinilai' => true]);
        Answer::create(['exam_attempt_id' => $attempt->id, 'question_id' => $q2->id, 'jawaban' => 'C', 'is_correct' => false, 'skor' => 0.0, 'sudah_dinilai' => true]);
        $essayAnswer = Answer::create(['exam_attempt_id' => $attempt->id, 'question_id' => $q3->id, 'jawaban' => 'Essay text', 'is_correct' => false, 'skor' => 0.0, 'sudah_dinilai' => false]);

        // Recalculate while essay is ungraded
        $recalculated = $this->scoringService->recalculateAttemptScore($attempt);

        $this->assertEquals(2.0, $recalculated->nilai_pg);
        $this->assertEquals(0.0, $recalculated->nilai_essay);
        $this->assertEquals(20.0, $recalculated->nilai_akhir); // (2 / 10) * 100 = 20
        $this->assertEquals('needs_grading', $recalculated->status);

        // Now grade the essay
        $this->scoringService->gradeEssayAnswer($essayAnswer, 5.0, 'Good');
        $final = $this->scoringService->recalculateAttemptScore($attempt);

        $this->assertEquals(2.0, $final->nilai_pg);
        $this->assertEquals(5.0, $final->nilai_essay);
        $this->assertEquals(70.0, $final->nilai_akhir); // (7 / 10) * 100 = 70
        $this->assertEquals('completed', $final->status);
    }
}
