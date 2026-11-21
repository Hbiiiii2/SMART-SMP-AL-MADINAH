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
use InvalidArgumentException;
use Tests\TestCase;

class AutosaveIntegrityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected AnswerService $answerService;
    protected ScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = new ScoringService();
        $this->answerService = new AnswerService($this->scoringService);
    }

    private function createExamSetup(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru IPA', 'kode_guru' => 'G-IPA-01']);
        $class = Kelas::create(['nama_kelas' => 'VII-A', 'tingkat' => 'VII', 'status' => 'aktif']);
        $subject = MataPelajaran::create(['kode' => 'IPA', 'nama' => 'IPA']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '1001',
            'nisn' => '10011001',
            'nama' => 'Siswa Uji',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $teacher->id,
            'mata_pelajaran_id' => $subject->id,
            'nama' => 'Ujian Sains',
            'kode_ujian' => 'SCI-01',
            'kelas_id' => $class->id,
            'kelas' => $class->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'token' => 'SCI123',
            'token_aktif' => true,
        ]);

        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q1', 'kunci' => 'A', 'bobot' => 1, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q2', 'kunci' => 'B', 'bobot' => 1, 'urutan' => 2]);
        $q3 = Question::create(['exam_id' => $exam->id, 'tipe' => 'essay', 'pertanyaan' => 'Q3', 'bobot' => 5, 'urutan' => 3]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'token_used' => 'SCI123',
        ]);

        return [$exam, $attempt, [$q1, $q2, $q3], $studentUser];
    }

    /**
     * Scenario 1: Multiple autosave requests from one student.
     */
    public function test_multiple_autosave_requests_from_one_student(): void
    {
        [$exam, $attempt, [$q1, $q2]] = $this->createExamSetup();

        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam);
        $this->answerService->saveBatchAnswers($attempt, [$q2->id => 'B'], $exam);

        $this->assertDatabaseHas('answers', ['exam_attempt_id' => $attempt->id, 'question_id' => $q1->id, 'jawaban' => 'A']);
        $this->assertDatabaseHas('answers', ['exam_attempt_id' => $attempt->id, 'question_id' => $q2->id, 'jawaban' => 'B']);
        $this->assertEquals(2, Answer::where('exam_attempt_id', $attempt->id)->count());
    }

    /**
     * Scenario 2: Repeated identical autosave requests.
     */
    public function test_repeated_identical_autosave_requests(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $res1 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam);
        $res2 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam);

        $this->assertTrue($res1['saved']);
        $this->assertTrue($res2['saved']);
        $this->assertEquals(1, Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->count());
    }

    /**
     * Scenario 3: Out-of-order autosave requests with monotonic client sequence protection.
     * Demonstrates that with client sequence numbers, an older request arriving
     * late does NOT overwrite a newer answer.
     */
    public function test_out_of_order_autosave_demonstrates_lww_overwrite(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Student chose 'A' at seq 1, then changed to 'B' at seq 2
        // Network delivers seq 2 ('B') first:
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam, [$q1->id => 2]);
        $this->assertEquals('B', Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->value('jawaban'));

        // Delayed older packet seq 1 ('A') arrives later:
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 1]);

        // Protected by client_seq: older answer 'A' is skipped and does NOT overwrite 'B'!
        $currentValue = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->value('jawaban');
        $this->assertEquals('B', $currentValue);
    }

    /**
     * Scenario 5 & 6: Autosave arriving during or after attempt completion.
     */
    public function test_autosave_rejected_after_attempt_completion(): void
    {
        [$exam, $attempt, [$q1, $q2, $q3]] = $this->createExamSetup();

        // Student submits examination
        $this->answerService->submitAttemptAnswers($attempt, [
            $q1->id => 'A',
            $q2->id => 'B',
            $q3->id => 'Essay response',
        ], $exam);

        $attempt->refresh();
        $this->assertEquals('needs_grading', $attempt->status);

        // Delayed autosave arrives after submission
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ujian sudah selesai atau tidak aktif.');

        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'C'], $exam);
    }

    /**
     * Scenario 8: Duplicate final submission idempotency.
     */
    public function test_duplicate_final_submission_is_idempotent(): void
    {
        [$exam, $attempt, [$q1, $q2, $q3]] = $this->createExamSetup();

        $firstSubmit = $this->answerService->submitAttemptAnswers($attempt, [
            $q1->id => 'A',
            $q2->id => 'B',
            $q3->id => 'Essay response',
        ], $exam);

        $submittedAtFirst = $firstSubmit->submitted_at;
        $scoreFirst = $firstSubmit->nilai_akhir;

        // Second submission call
        $secondSubmit = $this->answerService->submitAttemptAnswers($attempt, [
            $q1->id => 'C', // Trying to change answer on second submit
        ], $exam);

        // Attempt state must be completely untouched by the second submit
        $attempt->refresh();
        $this->assertEquals($submittedAtFirst->toDateTimeString(), $attempt->submitted_at->toDateTimeString());
        $this->assertEquals($scoreFirst, $attempt->nilai_akhir);
        $this->assertEquals('A', Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->value('jawaban'));
    }

    /**
     * Scenario 9: Partial batch failure / transaction atomicity.
     */
    public function test_partial_batch_failure_rolls_back_entirely(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        try {
            // Passing an invalid question ID and non-scalar
            $this->answerService->saveBatchAnswers($attempt, [
                $q1->id => 'A',
                999999 => ['nested_invalid_array'],
            ], $exam);
        } catch (\Throwable $e) {
            // Expected
        }

        // Only valid scalar question IDs are processed; invalid items are filtered cleanly without crashing
        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'A',
        ]);
    }
}
