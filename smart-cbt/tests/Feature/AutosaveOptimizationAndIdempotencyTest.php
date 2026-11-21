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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Tests\TestCase;

class AutosaveOptimizationAndIdempotencyTest extends TestCase
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
        $guru = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Opt', 'kode_guru' => 'G-OPT-01']);
        $kelas = Kelas::create(['nama_kelas' => 'IX-OPT', 'tingkat' => 'IX', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'OPT-01', 'nama' => 'Optimization Protocols']);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'OPT001',
            'nisn' => 'OPT001',
            'nama' => 'Siswa Optimization Protocol',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Optimization Autosave Exam',
            'kode_ujian' => 'OPT-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinute(),
            'tanggal_selesai' => now()->addHour(),
            'token' => 'OPT99',
            'token_aktif' => true,
        ]);

        $q1 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q1 Opt', 'kunci' => 'A', 'bobot' => 2, 'urutan' => 1]);
        $q2 = Question::create(['exam_id' => $exam->id, 'tipe' => 'pg', 'pertanyaan' => 'Q2 Opt', 'kunci' => 'B', 'bobot' => 3, 'urutan' => 2]);
        $q3 = Question::create(['exam_id' => $exam->id, 'tipe' => 'essay', 'pertanyaan' => 'Q3 Opt', 'bobot' => 5, 'urutan' => 3]);

        $attempt = $this->attemptService->startOrResumeAttempt($exam, $student, 'OPT99');

        return [$exam, $attempt, [$q1, $q2, $q3], $studentUser, $student];
    }

    /**
     * Requirement: saveBatchAnswers with request_id must return a structured acknowledgment.
     */
    public function test_autosave_with_request_id_returns_structured_acknowledgment(): void
    {
        [$exam, $attempt, [$q1, $q2]] = $this->createExamSetup();

        $requestId = 'req_test_abc_123';
        $res = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A', $q2->id => 'B'],
            $exam,
            [$q1->id => 1, $q2->id => 1],
            $requestId
        );

        $this->assertTrue($res['saved']);
        $this->assertEquals($attempt->id, $res['attempt_id']);
        $this->assertEquals($requestId, $res['request_id']);
        $this->assertEquals(2, $res['count']);
        $this->assertArrayHasKey((string) $q1->id, $res['acknowledged']);
        $this->assertEquals('persisted', $res['acknowledged'][(string) $q1->id]['status']);
        $this->assertEquals(1, $res['acknowledged'][(string) $q1->id]['client_seq']);
        $this->assertNotEmpty($res['server_time']);

        // Assert database state
        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'A',
            'client_seq' => 1,
        ]);
    }

    /**
     * Requirement: Re-submitting the same request_id with identical payload must return the cached response idempotently.
     */
    public function test_duplicate_request_id_returns_cached_idempotent_result(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $requestId = 'req_idempotent_999';
        
        // First execution
        $res1 = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A'],
            $exam,
            [$q1->id => 5],
            $requestId
        );

        $this->assertTrue($res1['saved']);
        $this->assertEquals(1, $res1['count']);

        // Simulate network retry with the exact same request_id and identical payload
        $res2 = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A'],
            $exam,
            [$q1->id => 5],
            $requestId
        );

        // res2 must be identical to res1
        $this->assertEquals($res1, $res2);

        // Database answer must remain 'A' from the first execution
        $dbAnswer = Answer::where('exam_attempt_id', $attempt->id)->where('question_id', $q1->id)->first();
        $this->assertEquals('A', $dbAnswer->jawaban);
        $this->assertEquals(5, $dbAnswer->client_seq);
    }

    /**
     * Requirement: Re-submitting the same request_id with a DIFFERENT payload must be rejected as an idempotency conflict.
     */
    public function test_duplicate_request_id_with_different_payload_is_rejected(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        $requestId = 'req_idempotent_conflict_888';

        // First execution with answer 'A'
        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'A'],
            $exam,
            [$q1->id => 1],
            $requestId
        );

        // Second execution reusing the same request_id but with answer 'B' (tampered or mismatched payload)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Idempotency conflict: Request ID {$requestId} was already processed with a different payload.");

        $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'B'],
            $exam,
            [$q1->id => 2],
            $requestId
        );
    }

    /**
     * Requirement: Delta payload format with answer_version / client_seq nested objects.
     */
    public function test_delta_payload_format_with_nested_answer_version(): void
    {
        [$exam, $attempt, [$q1, $q2]] = $this->createExamSetup();

        $nestedPayload = [
            [
                'question_id' => $q1->id,
                'answer' => 'C',
                'answer_version' => 10,
            ],
            [
                'question_id' => $q2->id,
                'jawaban' => 'D',
                'client_seq' => 12,
            ],
        ];

        $res = $this->answerService->saveBatchAnswers(
            $attempt,
            $nestedPayload,
            $exam,
            [],
            'req_nested_delta_1'
        );

        $this->assertTrue($res['saved']);
        $this->assertEquals(2, $res['count']);

        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'C',
            'client_seq' => 10,
        ]);
        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'jawaban' => 'D',
            'client_seq' => 12,
        ]);
    }

    /**
     * Requirement: Stale sequence on one question must report in rejected while valid question persists.
     */
    public function test_partial_rejection_marks_stale_answer_as_rejected_while_persisting_valid_answers(): void
    {
        [$exam, $attempt, [$q1, $q2]] = $this->createExamSetup();

        // Seed q1 with sequence 10
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 10]);

        // Now send batch: q1 has stale sequence 8, q2 has fresh sequence 1
        $res = $this->answerService->saveBatchAnswers(
            $attempt,
            [$q1->id => 'B', $q2->id => 'D'],
            $exam,
            [$q1->id => 8, $q2->id => 1],
            'req_partial_rejection_test'
        );

        $this->assertTrue($res['saved']);
        $this->assertEquals(1, $res['count']); // Only q2 was saved
        
        // Assert q2 acknowledged
        $this->assertArrayHasKey((string) $q2->id, $res['acknowledged']);
        $this->assertEquals('persisted', $res['acknowledged'][(string) $q2->id]['status']);

        // Assert q1 rejected as stale
        $this->assertArrayHasKey((string) $q1->id, $res['rejected']);
        $this->assertEquals('stale_sequence', $res['rejected'][(string) $q1->id]['reason']);
        $this->assertEquals(10, $res['rejected'][(string) $q1->id]['current_server_seq']);

        // Database check: q1 must remain 'A' with sequence 10
        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'jawaban' => 'A',
            'client_seq' => 10,
        ]);
        // q2 persisted as 'D' with sequence 1
        $this->assertDatabaseHas('answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'jawaban' => 'D',
            'client_seq' => 1,
        ]);
    }

    /**
     * Requirement: Autosave rejected once exam attempt is submitted.
     */
    public function test_autosave_rejected_after_attempt_submission(): void
    {
        [$exam, $attempt, [$q1]] = $this->createExamSetup();

        // Submit the attempt
        $attempt->update(['status' => 'submitted', 'submitted_at' => now()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ujian sudah selesai atau tidak aktif.');

        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A'], $exam, [$q1->id => 1]);
    }

    /**
     * Requirement: HTTP controller endpoint returns structured acknowledgment with 200 OK.
     */
    public function test_http_autosave_endpoint_with_request_id_returns_structured_json(): void
    {
        [$exam, $attempt, [$q1], $studentUser] = $this->createExamSetup();

        $response = $this->actingAs($studentUser)
            ->withSession(['siswa.exam_token_verified.' . $exam->id => $attempt->id])
            ->postJson(route('siswa.ujian.answers', $exam), [
                'request_id' => 'req_http_integration_01',
                'answers' => [$q1->id => 'B'],
                'client_seqs' => [$q1->id => 3],
            ]);

        $response->assertOk()
            ->assertJson([
                'saved' => true,
                'attempt_id' => $attempt->id,
                'request_id' => 'req_http_integration_01',
                'count' => 1,
            ])
            ->assertJsonStructure([
                'saved',
                'attempt_id',
                'request_id',
                'count',
                'acknowledged',
                'rejected',
                'server_time',
            ]);
    }
}
