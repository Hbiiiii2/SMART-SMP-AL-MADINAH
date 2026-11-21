<?php

namespace Tests\Feature;

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

class EssayGradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_essay_grading_cycle_and_score_recalculation(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru IPA', 'kode_guru' => 'G-IPA']);
        $class = Kelas::create(['nama_kelas' => 'VIII-A', 'tingkat' => 'VIII', 'status' => 'aktif']);
        $subject = MataPelajaran::create(['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '2001',
            'nisn' => '20012001',
            'nama' => 'Ahmad Siswa',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'nama' => 'Ujian Akhir IPA',
            'kode_ujian' => 'IPA-UAS-01',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'token' => 'IPA123',
            'token_aktif' => true,
        ]);

        // 1 PG (weight 2) + 1 Essay (weight 8) = total 10
        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Simbol Air?', 'kunci' => 'A', 'bobot' => 2.0, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'essay', 'pertanyaan' => 'Jelaskan siklus air!', 'bobot' => 8.0, 'urutan' => 2]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'token_used' => 'IPA123',
        ]);

        // Student submits answers via AnswerService
        $answerService = new AnswerService(new ScoringService());
        $attempt = $answerService->submitAttemptAnswers($attempt, [
            $q1->id => 'A',
            $q2->id => 'Siklus air terjadi melalui evaporasi, kondensasi, dan presipitasi.',
        ], $exam);

        // Verification of Student Submission State
        $this->assertEquals('needs_grading', $attempt->status);
        $this->assertEquals(2.0, $attempt->nilai_pg);
        $this->assertEquals(0.0, $attempt->nilai_essay);
        $this->assertEquals(20.0, $attempt->nilai_akhir);

        $essayAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q2->id)->first();
        $this->assertNotNull($essayAnswer);
        $this->assertFalse((bool) $essayAnswer->sudah_dinilai);

        // Teacher grades the essay
        $response = $this->actingAs($teacherUser)->post(route('guru.hasil.grade-essay', $attempt->id), [
            'answer_id' => $essayAnswer->id,
            'skor' => 6.0,
            'catatan_guru' => 'Penjelasan sudah lengkap dan runut.',
        ]);

        $response->assertSessionHas('success');

        // Post-grading attempt state
        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertEquals(2.0, $attempt->nilai_pg);
        $this->assertEquals(6.0, $attempt->nilai_essay);
        $this->assertEquals(80.0, $attempt->nilai_akhir); // (8 / 10) * 100 = 80

        $essayAnswer->refresh();
        $this->assertTrue((bool) $essayAnswer->sudah_dinilai);
        $this->assertEquals(6.0, $essayAnswer->skor);
        $this->assertEquals('Penjelasan sudah lengkap dan runut.', $essayAnswer->catatan_guru);
    }
}
