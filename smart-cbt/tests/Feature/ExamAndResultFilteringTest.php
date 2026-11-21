<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedFilteringFixtures(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Sekolah']);

    $guruAUser = User::factory()->create(['role' => 'guru', 'name' => 'Budi Santoso']);
    $guruA = Guru::create([
        'user_id' => $guruAUser->id,
        'nama' => 'Budi Santoso',
        'kode_guru' => 'G-BUDI',
    ]);

    $guruBUser = User::factory()->create(['role' => 'guru', 'name' => 'Siti Aminah']);
    $guruB = Guru::create([
        'user_id' => $guruBUser->id,
        'nama' => 'Siti Aminah',
        'kode_guru' => 'G-SITI',
    ]);

    $mapelMtk = MataPelajaran::create(['kode' => 'MTK', 'nama' => 'Matematika']);
    $mapelIpa = MataPelajaran::create(['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam']);

    $kelas8A = Kelas::create(['nama_kelas' => 'VIII-A', 'tingkat' => '8', 'status' => 'aktif']);
    $kelas8B = Kelas::create(['nama_kelas' => 'VIII-B', 'tingkat' => '8', 'status' => 'aktif']);

    // 1. Exam with questions AND student attempts (Sudah Ada Soal + Sudah Pernah Ujian)
    $examWithAttempts = Exam::create([
        'guru_id' => $guruA->id,
        'guru_pengawas_id' => $guruA->id,
        'mata_pelajaran_id' => $mapelMtk->id,
        'nama' => 'UTS Matematika Aljabar',
        'tahun_ajaran' => '2026/2027',
        'semester' => 'ganjil',
        'kelas_id' => $kelas8A->id,
        'kelas' => $kelas8A->nama_kelas,
        'ruangan' => 'Lab 1',
        'kode_ujian' => 'MTK-UTS-01',
        'durasi_menit' => 90,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'status' => 'aktif',
        'token' => 'TOK111',
        'token_aktif' => true,
        'komponen_soal' => ['pg'],
    ]);

    Question::create([
        'exam_id' => $examWithAttempts->id,
        'tipe' => 'pg',
        'pertanyaan' => '2 + 2 = ?',
        'opsi_a' => '3',
        'opsi_b' => '4',
        'opsi_c' => '5',
        'opsi_d' => '6',
        'kunci' => 'B',
        'bobot' => 1,
        'urutan' => 1,
    ]);

    // 2. Exam with questions BUT NO student attempts (Sudah Ada Soal + Belum Pernah Ujian)
    $examReadyNoAttempts = Exam::create([
        'guru_id' => $guruA->id,
        'guru_pengawas_id' => $guruB->id,
        'mata_pelajaran_id' => $mapelIpa->id,
        'nama' => 'Latihan IPA Fisika',
        'tahun_ajaran' => '2026/2027',
        'semester' => 'genap',
        'kelas_id' => $kelas8B->id,
        'kelas' => $kelas8B->nama_kelas,
        'ruangan' => 'Lab 2',
        'kode_ujian' => 'IPA-LAT-02',
        'durasi_menit' => 60,
        'tanggal_mulai' => now()->addDay(),
        'tanggal_selesai' => now()->addDays(2),
        'status' => 'aktif',
        'token' => 'TOK222',
        'token_aktif' => true,
        'komponen_soal' => ['pg'],
    ]);

    Question::create([
        'exam_id' => $examReadyNoAttempts->id,
        'tipe' => 'pg',
        'pertanyaan' => 'Satuan gaya?',
        'opsi_a' => 'Newton',
        'opsi_b' => 'Joule',
        'opsi_c' => 'Watt',
        'opsi_d' => 'Pascal',
        'kunci' => 'A',
        'bobot' => 1,
        'urutan' => 1,
    ]);

    // 3. Draft exam WITHOUT questions and WITHOUT attempts (Belum Ada Soal + Belum Pernah Ujian)
    $examEmptyDraft = Exam::create([
        'guru_id' => $guruB->id,
        'guru_pengawas_id' => $guruB->id,
        'mata_pelajaran_id' => $mapelIpa->id,
        'nama' => 'UAS IPA Biologi',
        'tahun_ajaran' => '2025/2026',
        'semester' => 'genap',
        'kelas_id' => $kelas8B->id,
        'kelas' => $kelas8B->nama_kelas,
        'ruangan' => 'R-03',
        'kode_ujian' => 'IPA-UAS-03',
        'durasi_menit' => 90,
        'status' => 'draft',
        'komponen_soal' => ['pg', 'essay_1'],
    ]);

    $siswa1User = User::factory()->create(['role' => 'siswa', 'name' => 'Ahmad Fauzi']);
    $siswa1 = Siswa::create([
        'user_id' => $siswa1User->id,
        'nis' => '1001',
        'nisn' => '00991122',
        'nama' => 'Ahmad Fauzi',
        'kelas_id' => $kelas8A->id,
        'kelas' => $kelas8A->nama_kelas,
        'status_aktif' => true,
    ]);

    $siswa2User = User::factory()->create(['role' => 'siswa', 'name' => 'Rina Kartika']);
    $siswa2 = Siswa::create([
        'user_id' => $siswa2User->id,
        'nis' => '1002',
        'nisn' => '00993344',
        'nama' => 'Rina Kartika',
        'kelas_id' => $kelas8A->id,
        'kelas' => $kelas8A->nama_kelas,
        'status_aktif' => true,
    ]);

    $attempt1 = ExamAttempt::create([
        'exam_id' => $examWithAttempts->id,
        'siswa_id' => $siswa1->id,
        'attempt_number' => 1,
        'started_at' => now()->subHours(2),
        'submitted_at' => now()->subHour(),
        'status' => 'submitted',
        'nilai_akhir' => 92.5,
    ]);

    $attempt2 = ExamAttempt::create([
        'exam_id' => $examWithAttempts->id,
        'siswa_id' => $siswa2->id,
        'attempt_number' => 1,
        'started_at' => now()->subHours(1),
        'submitted_at' => now()->subMinutes(20),
        'status' => 'needs_grading',
        'nilai_akhir' => 65.0,
    ]);

    return compact(
        'admin',
        'guruAUser',
        'guruA',
        'guruBUser',
        'guruB',
        'mapelMtk',
        'mapelIpa',
        'kelas8A',
        'kelas8B',
        'examWithAttempts',
        'examReadyNoAttempts',
        'examEmptyDraft',
        'siswa1',
        'siswa2',
        'attempt1',
        'attempt2'
    );
}

test('admin dashboard and ujian index support search, question readiness, and execution filters', function () {
    $fx = seedFilteringFixtures();

    // Dashboard search by kode_ujian
    $this->actingAs($fx['admin'])
        ->get(route('dashboard', ['q' => 'mtk-uts-01']))
        ->assertOk()
        ->assertSee($fx['examWithAttempts']->nama)
        ->assertDontSee($fx['examReadyNoAttempts']->nama)
        ->assertDontSee($fx['examEmptyDraft']->nama);

    // Filter sudah ada soal vs belum ada soal
    $this->actingAs($fx['admin'])
        ->get(route('ujian.index', ['soal_status' => 'belum_ada']))
        ->assertOk()
        ->assertSee($fx['examEmptyDraft']->nama)
        ->assertDontSee($fx['examWithAttempts']->nama)
        ->assertDontSee($fx['examReadyNoAttempts']->nama);

    // Filter sudah pernah ujian vs belum pernah ujian
    $this->actingAs($fx['admin'])
        ->get(route('ujian.index', ['pelaksanaan' => 'sudah_ujian']))
        ->assertOk()
        ->assertSee($fx['examWithAttempts']->nama)
        ->assertDontSee($fx['examReadyNoAttempts']->nama)
        ->assertDontSee($fx['examEmptyDraft']->nama);

    $this->actingAs($fx['admin'])
        ->get(route('ujian.index', ['pelaksanaan' => 'belum_ujian']))
        ->assertOk()
        ->assertSee($fx['examReadyNoAttempts']->nama)
        ->assertSee($fx['examEmptyDraft']->nama)
        ->assertDontSee($fx['examWithAttempts']->nama);

    // Filter by guru & tahun_ajaran
    $this->actingAs($fx['admin'])
        ->get(route('ujian.index', ['guru_id' => $fx['guruB']->id, 'tahun_ajaran' => '2025/2026']))
        ->assertOk()
        ->assertSee($fx['examEmptyDraft']->nama)
        ->assertDontSee($fx['examWithAttempts']->nama);
});

test('guru ujian index, dashboard, token index, and monitoring support search and filtering', function () {
    $fx = seedFilteringFixtures();

    // Guru A sees only their assigned exams (examWithAttempts as PIC, examReadyNoAttempts as PIC)
    $this->actingAs($fx['guruAUser'])
        ->get(route('guru.ujian.index', ['pelaksanaan' => 'belum_ujian']))
        ->assertOk()
        ->assertSee($fx['examReadyNoAttempts']->nama)
        ->assertDontSee($fx['examWithAttempts']->nama)
        ->assertDontSee($fx['examEmptyDraft']->nama);

    $this->actingAs($fx['guruAUser'])
        ->get(route('guru.dashboard', ['q' => 'aljabar']))
        ->assertOk()
        ->assertSee($fx['examWithAttempts']->nama)
        ->assertDontSee($fx['examReadyNoAttempts']->nama);

    $this->actingAs($fx['guruAUser'])
        ->get(route('guru.token.index', ['pelaksanaan' => 'sudah_ujian']))
        ->assertOk()
        ->assertSee($fx['examWithAttempts']->nama)
        ->assertDontSee($fx['examReadyNoAttempts']->nama);

    // Monitoring keeps full ownedExams list even when one exam_id is selected
    $this->actingAs($fx['guruAUser'])
        ->get(route('guru.monitoring.index', [
            'exam_id' => $fx['examWithAttempts']->id,
            'status' => 'completed',
            'search_siswa' => '00991122',
        ]))
        ->assertOk()
        ->assertViewHas('ownedExams', fn ($list) => $list->count() === 2)
        ->assertSee('Ahmad Fauzi')
        ->assertDontSee('Rina Kartika');
});

test('admin and guru hasil index and export support search, status, and exam execution summary tab', function () {
    $fx = seedFilteringFixtures();

    // Search by student NISN case-insensitively + filter by needs_grading
    $this->actingAs($fx['admin'])
        ->get(route('hasil.index', ['search_siswa' => '00993344', 'status' => 'needs_grading']))
        ->assertOk()
        ->assertSee('Rina Kartika')
        ->assertDontSee('Ahmad Fauzi');

    // Search by exam keyword
    $this->actingAs($fx['admin'])
        ->get(route('hasil.index', ['search_ujian' => 'mtk-uts-01']))
        ->assertOk()
        ->assertSee('Ahmad Fauzi')
        ->assertSee('Rina Kartika');

    // Tab ujian summary shows execution status
    $this->actingAs($fx['admin'])
        ->get(route('hasil.index', ['tab' => 'ujian', 'pelaksanaan' => 'belum_ujian']))
        ->assertOk()
        ->assertViewHas('examSummaries', fn ($summaries) => $summaries->total() === 2);

    // Export respects search_siswa filter and guru_pengawas_id authorization
    $this->actingAs($fx['admin'])
        ->get(route('hasil.export.filter', ['search_siswa' => 'Ahmad']))
        ->assertOk();

    $this->actingAs($fx['admin'])
        ->get(route('hasil.export.filter', ['search_siswa' => 'TidakAdaSiswaIni']))
        ->assertRedirect();
});

test('cbt api exams endpoint includes question and participant counts', function () {
    $fx = seedFilteringFixtures();
    $token = config('services.cbt.api_token') ?: env('CBT_API_TOKEN', 'cbt_test_token_2026');

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/exams')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $fx['examWithAttempts']->id,
            'total_soal' => 1,
            'total_peserta' => 2,
        ]);
});
