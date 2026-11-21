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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionSnapshotImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected ExamAttemptService $attemptService;
    protected AnswerService $answerService;
    protected ScoringService $scoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attemptService = app(ExamAttemptService::class);
        $this->answerService = app(AnswerService::class);
        $this->scoringService = app(ScoringService::class);
    }

    private function createExamSetup(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Snapshot', 'kode_guru' => 'G-SNP-01']);
        $kelas = Kelas::create(['nama_kelas' => 'VIII-SNP', 'tingkat' => 'VIII', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'SNP-MAPEL', 'nama' => 'Snapshot Subject']);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Snapshot Test Exam',
            'kode_ujian' => 'SNP-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinute(),
            'tanggal_selesai' => now()->addHour(),
            'token' => 'SNP123',
            'token_aktif' => true,
        ]);

        $q1 = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Original Question 1?',
            'opsi_a' => 'Answer A',
            'opsi_b' => 'Answer B',
            'opsi_c' => 'Answer C',
            'opsi_d' => 'Answer D',
            'kunci' => 'A',
            'bobot' => 2.0,
            'urutan' => 1,
        ]);

        $q2 = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Original Question 2?',
            'opsi_a' => 'Answer A',
            'opsi_b' => 'Answer B',
            'opsi_c' => 'Answer C',
            'opsi_d' => 'Answer D',
            'kunci' => 'B',
            'bobot' => 3.0,
            'urutan' => 2,
        ]);

        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'SNP-STU',
            'nisn' => 'SNP-STU',
            'nama' => 'Student Snapshot',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        return [$exam, [$q1, $q2], $student, $studentUser];
    }

    /**
     * Requirement: Attempt creation captures immutable questions snapshot.
     */
    public function test_attempt_creation_captures_questions_snapshot(): void
    {
        [$exam, [$q1, $q2], $student] = $this->createExamSetup();

        $attempt = $this->attemptService->startOrResumeAttempt($exam, $student, 'SNP123');

        $this->assertNotNull($attempt->questions_snapshot);
        $this->assertIsArray($attempt->questions_snapshot);
        $this->assertCount(2, $attempt->questions_snapshot);

        $snap1 = collect($attempt->questions_snapshot)->firstWhere('id', $q1->id);
        $this->assertEquals('Original Question 1?', $snap1['pertanyaan']);
        $this->assertEquals('A', $snap1['kunci']);
        $this->assertEquals(2.0, $snap1['bobot']);
    }

    /**
     * Requirement: Security check - correct answer key 'kunci' is never exposed in student-facing question models.
     */
    public function test_answer_key_is_stripped_from_student_ordered_questions(): void
    {
        [$exam, [$q1, $q2], $student] = $this->createExamSetup();

        $attempt = $this->attemptService->startOrResumeAttempt($exam, $student, 'SNP123');

        $orderedQuestions = $this->attemptService->getOrderedQuestions($exam, $attempt);

        $this->assertCount(2, $orderedQuestions);
        foreach ($orderedQuestions as $q) {
            $this->assertNull($q->kunci, "Student question model must not expose correct answer key 'kunci'!");
            $this->assertArrayNotHasKey('kunci', $q->toArray(), "Serialized question array must not contain 'kunci'!");
        }
    }

    /**
     * Requirement: If question in database is altered directly, attempt scoring still uses snapshot!
     */
    public function test_historical_attempt_scoring_is_immune_to_subsequent_question_edits(): void
    {
        [$exam, [$q1, $q2], $student] = $this->createExamSetup();

        $attempt = $this->attemptService->startOrResumeAttempt($exam, $student, 'SNP123');

        // Student answers Q1 with 'A' (correct according to snapshot)
        // Student answers Q2 with 'B' (correct according to snapshot)
        $this->answerService->saveBatchAnswers($attempt, [$q1->id => 'A', $q2->id => 'B'], $exam, [$q1->id => 1, $q2->id => 1]);
        $attempt = $this->answerService->submitAttemptAnswers($attempt, [], $exam);

        // Earned: (2 + 3) / (2 + 3) * 100 = 100%
        $this->assertEquals(100.0, (float) $attempt->nilai_akhir);

        // Now, simulate direct SQL modification of question in questions table:
        // Teacher alters key of Q1 from 'A' to 'C', and weight of Q2 from 3 to 10
        DB::table('questions')->where('id', $q1->id)->update(['kunci' => 'C', 'pertanyaan' => 'Tampered Q1']);
        DB::table('questions')->where('id', $q2->id)->update(['bobot' => 10.0]);

        // Recalculate score for student attempt:
        $recalculated = $this->scoringService->recalculateAttemptScore($attempt);

        // Score MUST remain 100% because recalculation uses the immutable attempt snapshot!
        $this->assertEquals(100.0, (float) $recalculated->nilai_akhir);
        $this->assertEquals(5.0, (float) $recalculated->nilai_pg);
    }

    /**
     * Requirement: Historical attempts without snapshot (questions_snapshot = null) fall back gracefully.
     */
    public function test_historical_attempt_without_snapshot_falls_back_to_live_questions(): void
    {
        [$exam, [$q1, $q2], $student] = $this->createExamSetup();

        // Create attempt with null questions_snapshot (simulating legacy attempt)
        $legacyAttempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $student->id,
            'attempt_number' => 2,
            'started_at' => now(),
            'status' => 'in_progress',
            'token_used' => 'SNP123',
            'question_order' => [$q1->id, $q2->id],
            'questions_snapshot' => null, // Legacy null snapshot
        ]);

        $this->assertNull($legacyAttempt->questions_snapshot);

        // getOrderedQuestions should fall back gracefully
        $ordered = $this->attemptService->getOrderedQuestions($exam, $legacyAttempt);
        $this->assertCount(2, $ordered);
        $this->assertEquals('Original Question 1?', $ordered->first()->pertanyaan);
        $this->assertNull($ordered->first()->kunci); // Still stripped for security

        // Submit and score
        $this->answerService->saveBatchAnswers($legacyAttempt, [$q1->id => 'A', $q2->id => 'B'], $exam);
        $scored = $this->answerService->submitAttemptAnswers($legacyAttempt, [], $exam);

        $this->assertEquals(100.0, (float) $scored->nilai_akhir);
    }
}
