<?php

use App\Models\Answer;
use App\Models\AnswerAttachment;
use App\Models\BankQuestion;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function setupSuperAdminDeletionFixtures(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Super Admin CBT']);

    $guruUser = User::factory()->create(['role' => 'guru', 'name' => 'Guru Test PIC']);
    $guru = Guru::create([
        'user_id' => $guruUser->id,
        'nama' => 'Guru Test PIC',
        'kode_guru' => 'G-DEL-01',
    ]);

    $pengawasUser = User::factory()->create(['role' => 'guru', 'name' => 'Guru Pengawas']);
    $pengawas = Guru::create([
        'user_id' => $pengawasUser->id,
        'nama' => 'Guru Pengawas',
        'kode_guru' => 'G-DEL-02',
    ]);

    $mapel = MataPelajaran::create(['kode' => 'DEL-01', 'nama' => "Al-Qur'an & Hadits"]);
    $kelas = Kelas::create(['nama_kelas' => 'VIII-DEL', 'tingkat' => '8', 'status' => 'aktif']);

    $siswa1User = User::factory()->create(['role' => 'siswa', 'name' => 'Siswa Pertama']);
    $siswa1 = Siswa::create([
        'user_id' => $siswa1User->id,
        'nis' => 'DEL-001',
        'nisn' => 'DEL-001',
        'nama' => 'Siswa Pertama',
        'kelas_id' => $kelas->id,
        'kelas' => $kelas->nama_kelas,
        'status_aktif' => true,
    ]);

    $siswa2User = User::factory()->create(['role' => 'siswa', 'name' => 'Siswa Kedua']);
    $siswa2 = Siswa::create([
        'user_id' => $siswa2User->id,
        'nis' => 'DEL-002',
        'nisn' => 'DEL-002',
        'nama' => 'Siswa Kedua',
        'kelas_id' => $kelas->id,
        'kelas' => $kelas->nama_kelas,
        'status_aktif' => true,
    ]);

    $otherExam = Exam::create([
        'guru_id' => $guru->id,
        'guru_pengawas_id' => $pengawas->id,
        'mata_pelajaran_id' => $mapel->id,
        'nama' => 'Ujian Resmi Semester',
        'tahun_ajaran' => '2026/2027',
        'semester' => 'ganjil',
        'kelas_id' => $kelas->id,
        'kelas' => $kelas->nama_kelas,
        'ruangan' => 'Ruang Utama',
        'kode_ujian' => 'OFFICIAL-01',
        'durasi_menit' => 90,
        'tanggal_mulai' => now()->addDay(),
        'tanggal_selesai' => now()->addDays(2),
        'status' => 'aktif',
        'token' => 'OFF111',
        'token_aktif' => true,
        'komponen_soal' => ['pg'],
    ]);

    Question::create([
        'exam_id' => $otherExam->id,
        'tipe' => 'pg',
        'urutan' => 1,
        'pertanyaan' => 'Soal Resmi',
        'opsi_a' => 'A',
        'opsi_b' => 'B',
        'opsi_c' => 'C',
        'opsi_d' => 'D',
        'kunci' => 'A',
        'bobot' => 1,
    ]);

    return compact(
        'admin',
        'guruUser',
        'guru',
        'pengawasUser',
        'pengawas',
        'mapel',
        'kelas',
        'siswa1User',
        'siswa1',
        'siswa2User',
        'siswa2',
        'otherExam'
    );
}

function populateFullExamForDeletion(array $fx, array $overrides = []): array
{
    $exam = Exam::create(array_merge([
        'guru_id' => $fx['guru']->id,
        'guru_pengawas_id' => $fx['pengawas']->id,
        'mata_pelajaran_id' => $fx['mapel']->id,
        'nama' => 'Ujian Test Percobaan "Dummy"',
        'tahun_ajaran' => '2026/2027',
        'semester' => 'ganjil',
        'kelas_id' => $fx['kelas']->id,
        'kelas' => $fx['kelas']->nama_kelas,
        'ruangan' => 'Lab Uji',
        'kode_ujian' => 'TST-' . strtoupper(uniqid()),
        'durasi_menit' => 60,
        'tanggal_mulai' => now()->subHour(),
        'tanggal_selesai' => now()->addHour(),
        'status' => 'aktif',
        'token' => strtoupper(substr(md5((string) microtime(true)), 0, 6)),
        'token_aktif' => true,
        'komponen_soal' => ['pg', 'essay_1'],
    ], $overrides));

    $uniqueImage = 'questions/unique_q_' . $exam->id . '.png';
    $uniqueOptionImage = 'questions/options/unique_opt_' . $exam->id . '.png';
    $sharedWithStockImage = 'questions/shared_stock_' . $exam->id . '.png';
    $syncedOnlyImage = 'questions/synced_only_' . $exam->id . '.png';

    Storage::disk('public')->put($uniqueImage, 'unique-q-bytes');
    Storage::disk('public')->put($uniqueOptionImage, 'unique-opt-bytes');
    Storage::disk('public')->put($sharedWithStockImage, 'shared-stock-bytes');
    Storage::disk('public')->put($syncedOnlyImage, 'synced-only-bytes');

    $qPg = Question::create([
        'exam_id' => $exam->id,
        'tipe' => 'pg',
        'urutan' => 1,
        'pertanyaan' => 'Soal PG Test',
        'image' => $uniqueImage,
        'opsi_a' => 'Opsi A',
        'opsi_a_image' => $uniqueOptionImage,
        'opsi_b' => 'Opsi B',
        'opsi_c' => 'Opsi C',
        'opsi_d' => 'Opsi D',
        'kunci' => 'A',
        'bobot' => 1,
    ]);

    $qEssay = Question::create([
        'exam_id' => $exam->id,
        'tipe' => 'essay_1',
        'urutan' => 2,
        'pertanyaan' => 'Soal Essay Test',
        'image' => $sharedWithStockImage,
        'bobot' => 10,
    ]);

    $qSyncedExtra = Question::create([
        'exam_id' => $exam->id,
        'tipe' => 'essay_2',
        'urutan' => 3,
        'pertanyaan' => 'Soal Synced Only Image',
        'image' => $syncedOnlyImage,
        'bobot' => 5,
    ]);

    DB::table('exam_supervisors')->insert([
        'exam_id' => $exam->id,
        'guru_id' => $fx['pengawas']->id,
        'ruangan' => 'Lab Uji',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attemptInProgress = ExamAttempt::create([
        'exam_id' => $exam->id,
        'siswa_id' => $fx['siswa1']->id,
        'attempt_number' => 1,
        'started_at' => now()->subMinutes(10),
        'status' => 'in_progress',
        'token_used' => $exam->token,
    ]);

    $attemptSubmitted = ExamAttempt::create([
        'exam_id' => $exam->id,
        'siswa_id' => $fx['siswa2']->id,
        'attempt_number' => 1,
        'started_at' => now()->subMinutes(40),
        'submitted_at' => now()->subMinutes(5),
        'status' => 'submitted',
        'nilai_akhir' => 80.00,
        'token_used' => $exam->token,
    ]);

    $answerPg = Answer::create([
        'exam_attempt_id' => $attemptSubmitted->id,
        'question_id' => $qPg->id,
        'answer_type' => 'text',
        'jawaban' => 'A',
        'is_correct' => true,
        'skor' => 1,
    ]);

    $answerEssay = Answer::create([
        'exam_attempt_id' => $attemptInProgress->id,
        'question_id' => $qEssay->id,
        'answer_type' => 'photo',
        'jawaban' => null,
    ]);

    $privateFilePath = "exam_{$exam->id}/attempt_{$attemptInProgress->id}/q_{$qEssay->id}/lampiran.jpg";
    Storage::disk('exam_private')->put($privateFilePath, 'private-photo-bytes');

    $attachment = AnswerAttachment::create([
        'answer_id' => $answerEssay->id,
        'exam_attempt_id' => $attemptInProgress->id,
        'question_id' => $qEssay->id,
        'file_path' => $privateFilePath,
        'original_name' => 'lampiran.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 18,
        'width' => 100,
        'height' => 100,
        'urutan' => 1,
        'checksum_sha256' => hash('sha256', 'private-photo-bytes'),
    ]);

    $syncedBank = QuestionBank::create([
        'guru_id' => $fx['guru']->id,
        'mata_pelajaran_id' => $fx['mapel']->id,
        'nama' => $exam->nama . ' • Synced',
        'source_exam_id' => $exam->id,
        'keterangan' => 'Auto-synced',
    ]);

    $syncedBankQuestion = BankQuestion::create([
        'question_bank_id' => $syncedBank->id,
        'tipe' => 'essay_2',
        'pertanyaan' => 'Soal Synced Only Image',
        'image' => $syncedOnlyImage,
        'bobot' => 5,
    ]);

    $stockBank = QuestionBank::create([
        'guru_id' => $fx['guru']->id,
        'mata_pelajaran_id' => $fx['mapel']->id,
        'nama' => 'Bank Stok Guru',
        'source_exam_id' => null,
    ]);

    $stockBankQuestion = BankQuestion::create([
        'question_bank_id' => $stockBank->id,
        'tipe' => 'essay_1',
        'pertanyaan' => 'Soal Stok Shared',
        'image' => $sharedWithStockImage,
        'bobot' => 10,
    ]);

    return compact(
        'exam',
        'qPg',
        'qEssay',
        'qSyncedExtra',
        'attemptInProgress',
        'attemptSubmitted',
        'answerPg',
        'answerEssay',
        'attachment',
        'privateFilePath',
        'uniqueImage',
        'uniqueOptionImage',
        'sharedWithStockImage',
        'syncedOnlyImage',
        'syncedBank',
        'syncedBankQuestion',
        'stockBank',
        'stockBankQuestion'
    );
}

test('super admin can delete an empty exam without force_delete and it disappears from guru views', function () {
    $fx = setupSuperAdminDeletionFixtures();

    $emptyExam = Exam::create([
        'guru_id' => $fx['guru']->id,
        'guru_pengawas_id' => $fx['pengawas']->id,
        'mata_pelajaran_id' => $fx['mapel']->id,
        'nama' => 'Jadwal Kosong Uji Coba',
        'kelas_id' => $fx['kelas']->id,
        'kelas' => $fx['kelas']->nama_kelas,
        'ruangan' => 'R-01',
        'kode_ujian' => 'EMPTY-01',
        'durasi_menit' => 45,
        'status' => 'draft',
        'komponen_soal' => ['pg'],
    ]);

    $this->actingAs($fx['admin'])
        ->delete(route('ujian.destroy', $emptyExam))
        ->assertRedirect(route('ujian.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('exams', ['id' => $emptyExam->id]);
    $this->assertDatabaseHas('exams', ['id' => $fx['otherExam']->id]);

    $this->actingAs($fx['admin'])
        ->get(route('ujian.index'))
        ->assertOk()
        ->assertViewHas('exams', fn ($exams) => ! $exams->contains('id', $emptyExam->id))
        ->assertDontSee($emptyExam->kode_ujian)
        ->assertSee($fx['otherExam']->nama);

    $this->actingAs($fx['guruUser'])
        ->get(route('guru.ujian.index'))
        ->assertOk()
        ->assertDontSee('Jadwal Kosong Uji Coba')
        ->assertDontSee($emptyExam->kode_ujian)
        ->assertSee($fx['otherExam']->nama);

    $this->actingAs($fx['guruUser'])
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertDontSee('Jadwal Kosong Uji Coba')
        ->assertDontSee($emptyExam->kode_ujian)
        ->assertSee($fx['otherExam']->nama);
});

test('super admin deleting an exam with questions or attempts without force_delete is rejected safely', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $fx = setupSuperAdminDeletionFixtures();
    $data = populateFullExamForDeletion($fx);

    $this->actingAs($fx['admin'])
        ->delete(route('ujian.destroy', $data['exam']))
        ->assertRedirect(route('ujian.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('exams', ['id' => $data['exam']->id]);
    $this->assertDatabaseHas('questions', ['id' => $data['qPg']->id]);
    $this->assertDatabaseHas('exam_attempts', ['id' => $data['attemptSubmitted']->id]);
    $this->assertDatabaseHas('answers', ['id' => $data['answerPg']->id]);
    $this->assertDatabaseHas('answer_attachments', ['id' => $data['attachment']->id]);
    Storage::disk('exam_private')->assertExists($data['privateFilePath']);
    Storage::disk('public')->assertExists($data['uniqueImage']);
});

test('super admin can force delete an exam with questions, attempts, answers, attachments, and supervisors while preserving shared assets and stock banks', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $fx = setupSuperAdminDeletionFixtures();
    $data = populateFullExamForDeletion($fx);
    $exam = $data['exam'];

    $this->actingAs($fx['admin'])
        ->delete(route('ujian.destroy', $exam), [
            'force_delete' => '1',
        ])
        ->assertRedirect(route('ujian.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    $this->assertDatabaseMissing('questions', ['exam_id' => $exam->id]);
    $this->assertDatabaseMissing('exam_attempts', ['exam_id' => $exam->id]);
    $this->assertDatabaseMissing('answers', ['id' => $data['answerPg']->id]);
    $this->assertDatabaseMissing('answers', ['id' => $data['answerEssay']->id]);
    $this->assertDatabaseMissing('answer_attachments', ['id' => $data['attachment']->id]);
    $this->assertSame(0, DB::table('exam_supervisors')->where('exam_id', $exam->id)->count());

    // Private attachments and directory removed
    Storage::disk('exam_private')->assertMissing($data['privateFilePath']);
    expect(Storage::disk('exam_private')->directoryExists("exam_{$exam->id}"))->toBeFalse();

    // Unreferenced question/option images removed
    Storage::disk('public')->assertMissing($data['uniqueImage']);
    Storage::disk('public')->assertMissing($data['uniqueOptionImage']);

    // Images still referenced by syncedBank (not deleted) and stockBank must remain intact
    Storage::disk('public')->assertExists($data['syncedOnlyImage']);
    Storage::disk('public')->assertExists($data['sharedWithStockImage']);

    // Synced bank is preserved with source_exam_id detached; unrelated exam intact
    $this->assertDatabaseHas('question_banks', [
        'id' => $data['syncedBank']->id,
        'source_exam_id' => null,
    ]);
    $this->assertDatabaseHas('question_banks', ['id' => $data['stockBank']->id]);
    $this->assertDatabaseHas('exams', ['id' => $fx['otherExam']->id]);

    // Exam disappears from all Guru & Siswa UI views
    $this->actingAs($fx['guruUser'])
        ->get(route('guru.ujian.index'))
        ->assertOk()
        ->assertDontSee($exam->kode_ujian)
        ->assertSee($fx['otherExam']->nama);

    $this->actingAs($fx['guruUser'])
        ->get(route('guru.dashboard'))
        ->assertOk()
        ->assertDontSee($exam->kode_ujian)
        ->assertSee($fx['otherExam']->nama);

    $this->actingAs($fx['guruUser'])
        ->get(route('guru.token.index'))
        ->assertOk()
        ->assertDontSee($exam->kode_ujian);

    $this->actingAs($fx['guruUser'])
        ->get(route('guru.hasil.index', ['tab' => 'ujian']))
        ->assertOk()
        ->assertDontSee($exam->kode_ujian);

    $this->actingAs($fx['siswa1User'])
        ->get(route('siswa.ujian.index'))
        ->assertOk()
        ->assertDontSee($exam->kode_ujian)
        ->assertSee($fx['otherExam']->nama);
});

test('super admin can optionally delete synced question banks while preserving unrelated stock banks', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $fx = setupSuperAdminDeletionFixtures();
    $data = populateFullExamForDeletion($fx);
    $exam = $data['exam'];

    $this->actingAs($fx['admin'])
        ->delete(route('ujian.destroy', $exam), [
            'force_delete' => '1',
            'delete_synced_banks' => '1',
        ])
        ->assertRedirect(route('ujian.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    $this->assertDatabaseMissing('question_banks', ['id' => $data['syncedBank']->id]);
    $this->assertDatabaseMissing('bank_questions', ['id' => $data['syncedBankQuestion']->id]);

    // Synced-only image is now orphaned and must be deleted
    Storage::disk('public')->assertMissing($data['syncedOnlyImage']);

    // Unrelated stock bank & shared image must stay intact
    $this->assertDatabaseHas('question_banks', ['id' => $data['stockBank']->id]);
    $this->assertDatabaseHas('bank_questions', ['id' => $data['stockBankQuestion']->id]);
    Storage::disk('public')->assertExists($data['sharedWithStockImage']);
});

test('super admin deletion preserves allowed index filters and strips page and unknown query parameters', function () {
    $fx = setupSuperAdminDeletionFixtures();

    $exam = Exam::create([
        'guru_id' => $fx['guru']->id,
        'guru_pengawas_id' => $fx['pengawas']->id,
        'mata_pelajaran_id' => $fx['mapel']->id,
        'nama' => 'Test Filter Redirect',
        'kelas_id' => $fx['kelas']->id,
        'kelas' => $fx['kelas']->nama_kelas,
        'ruangan' => 'R-02',
        'kode_ujian' => 'FLT-01',
        'durasi_menit' => 30,
        'status' => 'draft',
        'komponen_soal' => ['pg'],
    ]);

    $this->actingAs($fx['admin'])
        ->delete(route('ujian.destroy', $exam), [
            'return_query' => 'q=test&status=draft&page=3&evil=1',
        ])
        ->assertRedirect(route('ujian.index', ['q' => 'test', 'status' => 'draft']))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
});

test('super admin deletion from dashboard redirects back to dashboard preserving allowed filters', function () {
    $fx = setupSuperAdminDeletionFixtures();

    $exam = Exam::create([
        'guru_id' => $fx['guru']->id,
        'guru_pengawas_id' => $fx['pengawas']->id,
        'mata_pelajaran_id' => $fx['mapel']->id,
        'nama' => 'Test Dashboard Delete',
        'kelas_id' => $fx['kelas']->id,
        'kelas' => $fx['kelas']->nama_kelas,
        'ruangan' => 'R-03',
        'kode_ujian' => 'DSH-01',
        'durasi_menit' => 30,
        'status' => 'draft',
        'komponen_soal' => ['pg'],
    ]);

    $this->actingAs($fx['admin'])
        ->delete(route('ujian.destroy', $exam), [
            'return_route' => 'dashboard',
            'return_query' => 'q=test&page=2&evil=1',
        ])
        ->assertRedirect(route('dashboard', ['q' => 'test']))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
});

test('non admin users cannot delete exam schedules and delete modal triggers only render for admin', function () {
    Storage::fake('public');
    Storage::fake('exam_private');

    $fx = setupSuperAdminDeletionFixtures();
    $data = populateFullExamForDeletion($fx);
    $exam = $data['exam'];

    // Guru (even exam PIC) gets 403
    $this->actingAs($fx['guruUser'])
        ->delete(route('ujian.destroy', $exam), ['force_delete' => '1'])
        ->assertForbidden();

    // Siswa gets 403
    $this->actingAs($fx['siswa1User'])
        ->delete(route('ujian.destroy', $exam), ['force_delete' => '1'])
        ->assertForbidden();

    $this->assertDatabaseHas('exams', ['id' => $exam->id]);

    $relativeDestroyPath = route('ujian.destroy', $exam, false);

    // Admin sees vanilla-JS delete modal trigger on dashboard, ujian.index, and ujian.show even when exam has questions & attempts
    $this->actingAs($fx['admin'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('onclick="window.openDeleteExamModal(this)"', false)
        ->assertSee($relativeDestroyPath, false)
        ->assertSee('delete-exam-modal-title')
        ->assertDontSee('@click="$dispatch(\'open-delete-exam-modal\'', false);

    $this->actingAs($fx['admin'])
        ->get(route('ujian.index'))
        ->assertOk()
        ->assertSee('onclick="window.openDeleteExamModal(this)"', false)
        ->assertSee($relativeDestroyPath, false)
        ->assertSee('delete-exam-modal-title')
        ->assertDontSee('@click="$dispatch(\'open-delete-exam-modal\'', false);

    $this->actingAs($fx['admin'])
        ->get(route('ujian.show', $exam))
        ->assertOk()
        ->assertSee('onclick="window.openDeleteExamModal(this)"', false)
        ->assertSee($relativeDestroyPath, false)
        ->assertSee('Hapus Ujian')
        ->assertSee('delete-exam-modal-title')
        ->assertDontSee('@click="$dispatch(\'open-delete-exam-modal\'', false);

    // Guru show view MUST NOT render delete trigger or modal
    $this->actingAs($fx['guruUser'])
        ->get(route('guru.ujian.show', $exam))
        ->assertOk()
        ->assertDontSee('openDeleteExamModal')
        ->assertDontSee('Hapus Ujian')
        ->assertDontSee('delete-exam-modal-title');
});
