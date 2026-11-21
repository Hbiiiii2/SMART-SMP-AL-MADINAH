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
use App\Services\Examination\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AutosaveProtocolEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected AnswerService $answerService;
    protected ExamAttemptService $attemptService;
    protected ScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoringService = app(ScoringService::class);
        $this->answerService = app(AnswerService::class);
        $this->attemptService = app(ExamAttemptService::class);
    }

    private function createExamSetup(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Edge', 'kode_guru' => 'G-EDG-01']);
        $kelas = Kelas::create(['nama_kelas' => 'IX-EDGE', 'tingkat' => 'IX', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'EDG-01', 'nama' => 'Edge Protocols']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'EDGE001',
            'nisn' => 'EDGE001',
            'nama' => 'Siswa Edge Protocol',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Protocol Edge Exam',
            'kode_ujian' => 'EDGE-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinute(),
            'tanggal_selesai' => now()->addHour(),
            'token' => 'EDGE99',
            'token_aktif' => true,
        ]);

        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q1 Edge', 'kunci' => 'A', 'bobot' => 2, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q2 Edge', 'kunci' => 'B', 'bobot' => 3, 'urutan' => 2]);
        $q3 = Question::create(['exam_id' => $exam->id, 'tipe' => 'essay', 'pertanyaan' => 'Q3 Edge', 'bobot' => 5, 'urutan' => 3]);

        $attempt = $this->attemptService->startOrResumeAttempt($exam, $student, 'EDGE99');

        return [$exam, $attempt, [$q1, $q2, $q3], $studentUser, $student];
    }

    // =========================================================================
    // SECTION 1: LEGACY PAYLOAD DOWNGRADE PROTECTION & SEQUENCE VALIDATION
    // =========================================================================

    /**
     * Requirement: Sequenced answer followed by legacy unsequenced payload must be rejected.
     */
    public function test_sequenced_answer_followed_by_legacy_payload_is_rejected_and_not_overwritten(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Step 1: Save answer with sequence 4
        $res = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 4]);
        $this->assertEquals(1, $res['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('A', $dbAnswer->jawaban);
        $this->assertEquals(4, $dbAnswer->client_seq);

        // Step 2: Attempt to downgrade by sending a legacy payload without client_seq
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Downgrade rejected: Question {$q1->id} already has sequence protection");

        try {
            $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam);
        } finally {
            // Assert answer 'A' and sequence 4 were preserved and never overwritten
            $dbAnswer->refresh();
            $this->assertEquals('A', $dbAnswer->jawaban);
            $this->assertEquals(4, $dbAnswer->client_seq);
        }
    }

    /**
     * Requirement: Legacy unsequenced answer followed by sequenced payload upgrades safely.
     */
    public function test_legacy_answer_followed_by_sequenced_payload_upgrades_safely(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Step 1: Legacy answer saved without sequence
        $res1 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'C'], $exam);
        $this->assertEquals(1, $res1['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('C', $dbAnswer->jawaban);
        $this->assertNull($dbAnswer->client_seq);

        // Step 2: Modern sequenced answer arrives for this question
        $res2 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'D'], $exam, [$q1->id => 1]);
        $this->assertEquals(1, $res2['count']);

        $dbAnswer->refresh();
        $this->assertEquals('D', $dbAnswer->jawaban);
        $this->assertEquals(1, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Mixed legacy and sequenced answers in one batch where safe.
     */
    public function test_mixed_legacy_and_sequenced_answers_in_single_batch(): void
    {
        [$exam, $attempt, [$q1, $q2]] = $this->createExamSetup();

        // Q1 is legacy (no sequence), Q2 is sequenced (client_seq = 2)
        $res = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A', $q2->id => 'B'],
            $exam,
            [$q2->id => 2]
        );

        $this->assertEquals(2, $res['count']);

        $ans1 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $ans2 = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q2->id)->first();

        $this->assertEquals('A', $ans1->jawaban);
        $this->assertNull($ans1->client_seq);

        $this->assertEquals('B', $ans2->jawaban);
        $this->assertEquals(2, $ans2->client_seq);
    }

    /**
     * Requirement: Negative sequence numbers are rejected.
     */
    public function test_negative_sequence_number_throws_invalid_argument_exception(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sequence number for question {$q1->id}");

        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A'],
            $exam,
            [$q1->id => -5]
        );
    }

    /**
     * Requirement: Malformed sequence numbers (strings/floats) are rejected.
     */
    public function test_malformed_sequence_number_throws_invalid_argument_exception(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sequence number for question {$q1->id}");

        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A'],
            $exam,
            [$q1->id => 'invalid_seq_abc']
        );
    }

    /**
     * Requirement: Out-of-range sequence numbers (> 4294967295) are rejected.
     */
    public function test_out_of_range_sequence_number_throws_invalid_argument_exception(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sequence number for question {$q1->id}");

        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A'],
            $exam,
            [$q1->id => 5000000000]
        );
    }

    // =========================================================================
    // SECTION 2: DUPLICATE SEQUENCE CONFLICT DETECTION & SEMANTICS
    // =========================================================================

    /**
     * Requirement: Same sequence + same payload = idempotent retry (safe).
     */
    public function test_duplicate_sequence_same_payload_is_idempotent_retry(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $res1 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 3]);
        $res2 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 3]);

        $this->assertEquals(1, $res1['count']);
        $this->assertEquals(1, $res2['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('A', $dbAnswer->jawaban);
        $this->assertEquals(3, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Same sequence + different payload = conflict; do not overwrite.
     */
    public function test_duplicate_sequence_different_payload_is_conflict_and_does_not_overwrite(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Step 1: Sequence 3 saved with answer 'A'
        $res1 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 3]);
        $this->assertEquals(1, $res1['count']);

        // Step 2: Conflicting request arrives with sequence 3 but DIFFERENT answer 'B'
        $res2 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam, [$q1->id => 3]);

        // Must NOT overwrite; saved count must be 0
        $this->assertEquals(0, $res2['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('A', $dbAnswer->jawaban);
        $this->assertEquals(3, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Lower sequence = stale; do not overwrite.
     */
    public function test_lower_sequence_is_stale_and_does_not_overwrite(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Step 1: Sequence 5 saved with answer 'C'
        $res1 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'C'], $exam, [$q1->id => 5]);
        $this->assertEquals(1, $res1['count']);

        // Step 2: Delayed stale packet arrives with sequence 4 and answer 'D'
        $res2 = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'D'], $exam, [$q1->id => 4]);

        $this->assertEquals(0, $res2['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('C', $dbAnswer->jawaban);
        $this->assertEquals(5, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Higher sequence = eligible for persistence.
     */
    public function test_higher_sequence_is_persisted_cleanly(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 5]);
        $res = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam, [$q1->id => 6]);

        $this->assertEquals(1, $res['count']);

        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('B', $dbAnswer->jawaban);
        $this->assertEquals(6, $dbAnswer->client_seq);
    }

    // =========================================================================
    // SECTION 3: BROWSER STORAGE LOSS AND SEQUENCE RECOVERY
    // =========================================================================

    /**
     * Requirement: Resuming an exam passes authoritative server sequences to the view.
     */
    public function test_attempt_resume_passes_authoritative_server_sequences_to_view(): void
    {
        [$exam, $attempt, [$q1, $q2], $studentUser] = $this->createExamSetup();

        // Student previously answered Q1 with seq 7 and Q2 with seq 4
        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A', $q2->id => 'B'],
            $exam,
            [$q1->id => 7, $q2->id => 4]
        );

        $response = $this->actingAs($studentUser)
            ->withSession(["siswa.exam_token_verified.{$exam->id}" => $attempt->id])
            ->get(route('siswa.ujian.work', $exam));

        $response->assertOk();
        $response->assertViewHas('answerSeqs');

        $answerSeqs = $response->viewData('answerSeqs');
        $this->assertEquals(7, $answerSeqs[$q1->id]);
        $this->assertEquals(4, $answerSeqs[$q2->id]);
    }

    /**
     * Requirement: After storage loss, new answer edit starts at server_seq + 1 (not 1).
     */
    public function test_recovered_sequence_increments_monotonically_above_server_state(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Server authoritative sequence is 8
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 8]);

        // Student switches device or clears localStorage.
        // Recovered state initializes state.questionSequences[q1] = 8.
        // Next student change produces sequence 9:
        $res = $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'B'], $exam, [$q1->id => 9]);

        $this->assertEquals(1, $res['count']);
        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('B', $dbAnswer->jawaban);
        $this->assertEquals(9, $dbAnswer->client_seq);
    }

    // =========================================================================
    // SECTION 4: SNAPSHOT SECURITY AND IMMUTABILITY
    // =========================================================================

    /**
     * Requirement: questions_snapshot is hidden from ExamAttempt JSON/array serialization.
     */
    public function test_questions_snapshot_is_hidden_from_json_and_array_serialization(): void
    {
        [$exam, $attempt] = $this->createExamSetup();

        // Direct property access in internal backend services works:
        $this->assertIsArray($attempt->questions_snapshot);
        $this->assertNotEmpty($attempt->questions_snapshot);

        // But JSON / array serialization strictly hides questions_snapshot:
        $array = $attempt->toArray();
        $this->assertArrayNotHasKey('questions_snapshot', $array);

        $json = json_encode($attempt);
        $this->assertStringNotContainsString('questions_snapshot', $json);
        $this->assertStringNotContainsString('kunci', $json);
    }

    /**
     * Requirement: Student presentation models never leak correct answer keys.
     */
    public function test_student_ordered_questions_never_contain_kunci(): void
    {
        [$exam, $attempt] = $this->createExamSetup();

        $orderedQuestions = $this->attemptService->getOrderedQuestions($exam, $attempt);

        foreach ($orderedQuestions as $q) {
            $this->assertNull($q->kunci, "Question ID {$q->id} leaked kunci attribute.");
            $this->assertArrayNotHasKey('kunci', $q->toArray());
            $this->assertStringNotContainsString('"kunci"', json_encode($q));
        }
    }

    /**
     * Requirement: Historical attempts with null snapshot fall back cleanly.
     */
    public function test_historical_attempt_with_null_snapshot_falls_back_safely(): void
    {
        [$exam, $attempt, [$q1, $q2]] = $this->createExamSetup();

        // Simulate historical attempt created before snapshot migration
        $attempt->update(['questions_snapshot' => null]);
        $attempt->refresh();
        $this->assertNull($attempt->questions_snapshot);

        // Fallback retrieves questions safely and still strips kunci:
        $questions = $this->attemptService->getOrderedQuestions($exam, $attempt);
        $this->assertCount(3, $questions);
        foreach ($questions as $q) {
            $this->assertNull($q->kunci);
        }

        // Scoring fallback evaluates cleanly:
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A', $q2->id => 'B'], $exam);
        $completed = $this->answerService->submitAttemptAnswers($attempt, [], $exam);
        $this->assertEquals(5.0, $completed->nilai_pg);
    }
}
