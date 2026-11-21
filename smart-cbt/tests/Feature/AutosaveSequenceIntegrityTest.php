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
use App\Services\Examination\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AutosaveSequenceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected AnswerService $answerService;
    protected ExamAttemptService $attemptService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->answerService = app(AnswerService::class);
        $this->attemptService = app(ExamAttemptService::class);
    }

    private function createExamSetup(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru IPA', 'kode_guru' => 'G-IPA-99']);
        $kelas = Kelas::create(['nama_kelas' => 'VIII-SEQ', 'tingkat' => 'VIII', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'IPA-SEQ', 'nama' => 'IPA Sequence']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'SEQ001',
            'nisn' => 'SEQ001',
            'nama' => 'Siswa Sequence',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Sequence Test Exam',
            'kode_ujian' => 'SEQ-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinute(),
            'tanggal_selesai' => now()->addHour(),
            'token' => 'SEQ123',
            'token_aktif' => true,
        ]);

        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q1', 'kunci' => 'A', 'bobot' => 1, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q2', 'kunci' => 'B', 'bobot' => 1, 'urutan' => 2]);
        $q3 = Question::create(['exam_id' => $exam->id, 'tipe' => 'essay', 'pertanyaan' => 'Q3', 'bobot' => 5, 'urutan' => 3]);

        $attempt = $this->attemptService->startOrResumeAttempt($exam, $student, 'SEQ123');

        return [$exam, $attempt, [$q1, $q2, $q3], $studentUser];
    }

    /**
     * Requirement: Newer request arrives before older request; older request must NOT overwrite newer.
     */
    public function test_delayed_older_request_does_not_overwrite_newer_answer(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Step 1: Student changed answer to 'C' with sequence 5.
        // It arrives first:
        $resNewer = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'C'],
            $exam,
            [$q1->id => 5]
        );
        $this->assertEquals(1, $resNewer['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('C', $dbAnswer->jawaban);
        $this->assertEquals(5, $dbAnswer->client_seq);

        // Step 2: An older request (answer 'B' with sequence 2) delayed in transit arrives later:
        $resOlder = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'B'],
            $exam,
            [$q1->id => 2]
        );

        // The stale request should be skipped (saved count 0)
        $this->assertEquals(0, $resOlder['count']);

        // The database must retain the newer answer 'C' with sequence 5!
        $dbAnswer->refresh();
        $this->assertEquals('C', $dbAnswer->jawaban);
        $this->assertEquals(5, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Newer request arriving after an older request updates the record cleanly.
     */
    public function test_newer_request_arriving_after_older_request_updates_answer(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Step 1: Initial answer 'A' with sequence 1
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 1]);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('A', $dbAnswer->jawaban);
        $this->assertEquals(1, $dbAnswer->client_seq);

        // Step 2: Newer answer 'B' with sequence 2 arrives
        $res = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam, [$q1->id => 2]);
        $this->assertEquals(1, $res['count']);

        $dbAnswer->refresh();
        $this->assertEquals('B', $dbAnswer->jawaban);
        $this->assertEquals(2, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Duplicate sequence arrives twice (idempotent, safe retry).
     */
    public function test_duplicate_sequence_arrives_twice_idempotently(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Request sent twice with exact same sequence 3
        $res1 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'D'], $exam, [$q1->id => 3]);
        $res2 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'D'], $exam, [$q1->id => 3]);

        $this->assertEquals(1, $res1['count']);
        $this->assertEquals(1, $res2['count']);

        $count = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->count();
        $this->assertEquals(1, $count);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('D', $dbAnswer->jawaban);
        $this->assertEquals(3, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Multiple questions have independent sequences in the same attempt.
     */
    public function test_multiple_questions_have_independent_sequences(): void
    {
        [$exam, $attempt, [$q1, $q2, $q3]] = $this->createExamSetup();

        // Q1 at sequence 10, Q2 at sequence 1
        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A', $q2->id => 'B'],
            $exam,
            [$q1->id => 10, $q2->id => 1]
        );

        $ans1 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $ans2 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q2->id)->first();

        $this->assertEquals('A', $ans1->jawaban);
        $this->assertEquals(10, $ans1->client_seq);
        $this->assertEquals('B', $ans2->jawaban);
        $this->assertEquals(1, $ans2->client_seq);

        // Later: Q1 sends stale seq 4 (ignored), Q2 sends newer seq 2 (accepted)
        $res = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'C', $q2->id => 'D'],
            $exam,
            [$q1->id => 4, $q2->id => 2]
        );

        $this->assertEquals(1, $res['count']); // Only Q2 was saved

        $ans1->refresh();
        $ans2->refresh();

        $this->assertEquals('A', $ans1->jawaban); // Unchanged
        $this->assertEquals(10, $ans1->client_seq);
        $this->assertEquals('D', $ans2->jawaban); // Updated
        $this->assertEquals(2, $ans2->client_seq);
    }

    /**
     * Requirement: Backward compatibility when client_seq is not provided (legacy clients).
     */
    public function test_legacy_payload_without_client_seq_updates_normally(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam);
        $ans = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('A', $ans->jawaban);
        $this->assertNull($ans->client_seq);

        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam);
        $ans->refresh();
        $this->assertEquals('B', $ans->jawaban);
    }

    /**
     * Requirement: Autosave racing with final submission cannot overwrite final submission.
     */
    public function test_autosave_cannot_overwrite_final_submission(): void
    {
        [$exam, $attempt, [$q1, $q2, $q3]] = $this->createExamSetup();

        // Student submits final answers
        $this->answerService->submitAttemptAnswers($attempt, [$q1->id => 'A', $q2->id => 'B'], $exam);

        $attempt->refresh();
        $this->assertNotEquals('in_progress', $attempt->status);

        // Delayed autosave arrives after submit
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ujian sudah selesai atau tidak aktif.');

        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'D'],
            $exam,
            [$q1->id => 99]
        );
    }
}
