<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\BankQuestion;
use App\Models\Siswa;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionMutationGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function createExamWithTeacher(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $guru = Guru::create(['user_id' => $teacherUser->id, 'nama' => 'Guru Guard', 'kode_guru' => 'G-GRD-01']);
        $kelas = Kelas::create(['nama_kelas' => 'VIII-GRD', 'tingkat' => 'VIII', 'status' => 'aktif']);
        $mapel = MataPelajaran::create(['kode' => 'GRD-01', 'nama' => 'Guard Mapel']);

        $exam = Exam::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mapel->id,
            'nama' => 'Guard Test Exam',
            'kode_ujian' => 'GRD-' . uniqid(),
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'durasi_menit' => 60,
            'status' => 'aktif',
            'tanggal_mulai' => now()->subMinute(),
            'tanggal_selesai' => now()->addHour(),
            'token' => 'GRD123',
            'token_aktif' => true,
        ]);

        $q1 = Question::create([
            'exam_id' => $exam->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Existing Question 1',
            'opsi_a' => 'A1',
            'opsi_b' => 'B1',
            'opsi_c' => 'C1',
            'opsi_d' => 'D1',
            'kunci' => 'A',
            'bobot' => 1,
            'urutan' => 1,
        ]);

        return [$exam, $q1, $teacherUser, $guru, $kelas];
    }

    private function addAttemptToExam(Exam $exam, Kelas $kelas): ExamAttempt
    {
        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'GRD-STU',
            'nisn' => 'GRD-STU',
            'nama' => 'Student Guard',
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama_kelas,
            'status_aktif' => true,
        ]);

        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'siswa_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'token_used' => 'GRD123',
            'question_order' => [$exam->questions()->first()->id],
        ]);
    }

    /**
     * Test BLK-02: store() is blocked with HTTP 422 after exam has attempts.
     */
    public function test_store_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->post(route('guru.soal.store', $exam), [
            'tipe' => 'pg',
            'pertanyaan' => 'New Question While Active',
            'opsi_a' => 'A',
            'opsi_b' => 'B',
            'opsi_c' => 'C',
            'opsi_d' => 'D',
            'kunci' => 'B',
            'bobot' => 2,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('questions', ['pertanyaan' => 'New Question While Active']);
    }

    /**
     * Test BLK-02: update() is blocked with HTTP 422 after exam has attempts.
     */
    public function test_update_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->put(route('guru.soal.update', [$exam, $q1]), [
            'tipe' => 'pg',
            'pertanyaan' => 'Tampered Question',
            'opsi_a' => 'A',
            'opsi_b' => 'B',
            'opsi_c' => 'C',
            'opsi_d' => 'D',
            'kunci' => 'C',
            'bobot' => 5,
        ]);

        $response->assertStatus(422);
        $q1->refresh();
        $this->assertEquals('Existing Question 1', $q1->pertanyaan);
    }

    /**
     * Test BLK-02: destroy() is blocked with HTTP 422 after exam has attempts.
     */
    public function test_destroy_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->delete(route('guru.soal.destroy', [$exam, $q1]));

        $response->assertStatus(422);
        $this->assertDatabaseHas('questions', ['id' => $q1->id]);
    }

    /**
     * Test BLK-02: duplicate() is blocked with HTTP 422 after exam has attempts.
     */
    public function test_duplicate_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->post(route('guru.soal.duplicate', [$exam, $q1]));

        $response->assertStatus(422);
        $this->assertEquals(1, $exam->questions()->count());
    }

    /**
     * Test BLK-02: reorder() is blocked with HTTP 422 after exam has attempts.
     */
    public function test_reorder_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->patch(route('guru.soal.reorder', $exam), [
            'order' => [$q1->id => 10],
        ]);

        $response->assertStatus(422);
        $q1->refresh();
        $this->assertEquals(1, $q1->urutan);
    }

    /**
     * Test BLK-02: import() from bank question is blocked with HTTP 422 after exam has attempts.
     */
    public function test_import_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        
        $bank = QuestionBank::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $exam->mata_pelajaran_id,
            'nama' => 'Test Bank',
        ]);
        $bankQuestion = BankQuestion::create([
            'question_bank_id' => $bank->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Bank Question 1',
            'opsi_a' => 'A',
            'opsi_b' => 'B',
            'opsi_c' => 'C',
            'opsi_d' => 'D',
            'kunci' => 'A',
            'bobot' => 1,
        ]);

        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->post(route('guru.soal.import', [$exam, $bankQuestion]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('questions', ['pertanyaan' => 'Bank Question 1']);
    }

    /**
     * Test BLK-02: importBank() is blocked with HTTP 422 after exam has attempts.
     */
    public function test_import_bank_is_blocked_after_exam_has_attempts(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        
        $bank = QuestionBank::create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $exam->mata_pelajaran_id,
            'nama' => 'Test Full Bank',
        ]);
        BankQuestion::create([
            'question_bank_id' => $bank->id,
            'tipe' => 'pg',
            'pertanyaan' => 'Bank Bulk Question 1',
            'opsi_a' => 'A',
            'opsi_b' => 'B',
            'opsi_c' => 'C',
            'opsi_d' => 'D',
            'kunci' => 'A',
            'bobot' => 1,
        ]);

        $this->addAttemptToExam($exam, $kelas);

        $response = $this->actingAs($teacherUser)->post(route('guru.soal.import-bank', [$exam, $bank]));

        $response->assertStatus(422);
        $this->assertDatabaseMissing('questions', ['pertanyaan' => 'Bank Bulk Question 1']);
    }

    /**
     * Test BLK-02: Direct Eloquent model mutations bypass controller and throw DomainException.
     */
    public function test_direct_model_mutation_throws_domain_exception_when_attempts_exist(): void
    {
        [$exam, $q1, $teacherUser, $guru, $kelas] = $this->createExamWithTeacher();
        $this->addAttemptToExam($exam, $kelas);

        // Attempting direct model update
        $this->expectException(DomainException::class);
        $q1->update(['pertanyaan' => 'Direct DB Modification Attempt']);
    }

    /**
     * Test Authorization: Other teacher cannot modify exam questions.
     */
    public function test_other_teacher_cannot_modify_exam_questions(): void
    {
        [$exam, $q1] = $this->createExamWithTeacher();

        $otherTeacherUser = User::factory()->create(['role' => 'guru']);
        Guru::create(['user_id' => $otherTeacherUser->id, 'nama' => 'Other Teacher', 'kode_guru' => 'G-OTH-99']);

        $response = $this->actingAs($otherTeacherUser)->delete(route('guru.soal.destroy', [$exam, $q1]));
        $response->assertStatus(403);
    }
}
