<?php

namespace Tests\Feature;

use App\Models\AssessmentComponent;
use App\Models\ClassHomeroom;
use App\Models\MataPelajaran;
use App\Models\RaporSetting;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AssessmentComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EraporFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private User $adminUser;
    private \App\Models\Guru $guru;
    private Siswa $siswa;
    private MataPelajaran $mapel;
    private TahunAjaran $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AssessmentComponentSeeder::class);

        $this->teacherUser = User::factory()->create([
            'role' => 'guru',
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin_sekolah',
        ]);

        $this->guru = \App\Models\Guru::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198501012010011001',
            'nama_lengkap' => 'Guru Pengampu',
            'jenis_kelamin' => 'L',
        ]);

        $studentUser = User::factory()->create([
            'role' => 'siswa',
        ]);

        $this->tahun = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => now()->startOfYear(),
            'tanggal_selesai' => now()->endOfYear(),
            'is_aktif' => true,
        ]);

        $this->mapel = MataPelajaran::create([
            'nama' => 'Matematika',
            'kode' => 'MTK',
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $studentUser->id,
            'nisn' => '0098765432',
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Assign $this->guru as Wali Kelas of VII - Ibnu Taimiyah
        ClassHomeroom::create([
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'guru_id' => $this->guru->id,
            'nama_wali_kelas' => $this->guru->nama_lengkap,
            'nip_wali_kelas' => $this->guru->nip,
        ]);
    }

    public function test_guest_cannot_access_erapor(): void
    {
        $response = $this->get('/erapor');
        $response->assertRedirect('/login');
    }

    public function test_teacher_can_access_erapor_dashboard(): void
    {
        // Admin sekolah, kepala sekolah, super admin can access E-Rapor dashboard
        $response = $this->actingAs($this->adminUser)->get('/erapor');
        $response->assertStatus(200)
            ->assertSee('Platform E-Rapor Terintegrasi');

        // Guru cannot access restricted E-Rapor management pages
        $this->actingAs($this->teacherUser)->get('/erapor')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cbt')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/buku-induk')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(403);
        $this->actingAs($this->teacherUser)->post('/erapor/recap/finalize/VII - Ibnu Taimiyah')->assertStatus(403);
    }

    public function test_teacher_can_access_buku_nilai(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $response->assertStatus(200)
            ->assertSee('Buku Nilai Mata Pelajaran')
            ->assertSee('Budi Santoso');
    }

    public function test_update_score_recalculates_subject_summary(): void
    {
        // First get the buku nilai to ensure items are created
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $item = \App\Models\AssessmentItem::first();

        $response = $this->actingAs($this->teacherUser)
            ->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $this->siswa->id,
                'assessment_item_id' => $item->id,
                'score' => 88.5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $item->id,
            'score' => 88.5,
        ]);
    }

    public function test_super_admin_and_admin_can_also_input_daily_scores(): void
    {
        $adminUser = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $item = \App\Models\AssessmentItem::first();

        $response = $this->actingAs($adminUser)
            ->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $this->siswa->id,
                'assessment_item_id' => $item->id,
                'score' => 96.0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $item->id,
            'score' => 96.0,
            'updated_by' => $adminUser->id,
        ]);
    }

    public function test_rekap_sts_view_and_calculation(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get('/erapor/recap?kelas=VII - Ibnu Taimiyah');

        $response->assertStatus(200)
            ->assertSee('Rekapitulasi Nilai STS')
            ->assertSee('Budi Santoso');
    }

    public function test_export_class_sts_returns_excel_stream(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get('/erapor/recap/export/VII - Ibnu Taimiyah');

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('content-type'));
    }

    public function test_sync_cbt_via_api_successfully_creates_scores_and_audit(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/exam-results*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 101,
                        'exam_external_id' => 'EXAM-CBT-001',
                        'exam_name' => 'STS 1 Matematika VII',
                        'student_nisn' => '0098765432',
                        'student_name' => 'Budi Santoso',
                        'class_name' => 'VII - Ibnu Taimiyah',
                        'subject_name' => 'Matematika',
                        'score' => 95.0,
                        'status' => 'submitted',
                        'started_at' => now()->subHour()->toIso8601String(),
                        'submitted_at' => now()->toIso8601String(),
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/sync-cbt', [
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'kelas' => 'VII - Ibnu Taimiyah',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cbt_results', [
            'student_nisn' => '0098765432',
            'score' => 95.0,
        ]);

        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'score' => 95.0,
            'source_type' => 'cbt_api',
        ]);

        $this->assertDatabaseHas('erapor_audit_logs', [
            'action' => 'sync_cbt',
        ]);
    }

    public function test_sync_lms_task_scores_maps_pengumpulan_tugas(): void
    {
        $tugas = \App\Models\Tugas::create([
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'judul' => 'Tugas Aljabar 1',
            'deskripsi' => 'Kerjakan soal 1-10',
            'tenggat_waktu' => now()->addDays(2),
        ]);

        \App\Models\PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $this->siswa->id,
            'file_path' => 'tugas/sample.pdf',
            'waktu_pengumpulan' => now(),
            'status' => 'dinilai',
            'nilai' => 92.0,
            'catatan_guru' => 'Bagus sekali',
        ]);

        // Get buku-nilai first to generate assessment items
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $tugasItem = \App\Models\AssessmentItem::whereHas('component', function ($q) {
            $q->where('code', 'tugas');
        })->first();

        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/sync-lms', [
                'tugas_id' => $tugas->id,
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'sequence' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'score' => 92.0,
            'source_type' => 'lms',
        ]);
    }

    public function test_competition_ranking_matches_excel_rank_eq_rules(): void
    {
        $gradingService = app(\App\Services\GradingCalculationService::class);

        // Create 3 additional students in class 'VII - Ibnu Taimiyah'
        $s2 = Siswa::create([
            'user_id' => User::factory()->create()->id,
            'nisn' => '0098765433',
            'nama_lengkap' => 'Student 2',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);
        $s3 = Siswa::create([
            'user_id' => User::factory()->create()->id,
            'nisn' => '0098765434',
            'nama_lengkap' => 'Student 3',
            'jenis_kelamin' => 'P',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);
        $s4 = Siswa::create([
            'user_id' => User::factory()->create()->id,
            'nisn' => '0098765435',
            'nama_lengkap' => 'Student 4',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Manually create SubjectGradeSummary to simulate STS scores
        // Student 1: 95
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 95.0,
        ]);
        // Student 2: 90
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $s2->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 90.0,
        ]);
        // Student 3: 90 (TIE)
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $s3->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 90.0,
        ]);
        // Student 4: 85
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $s4->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'sts_score' => 85.0,
        ]);

        // Recalculate Class STS
        $gradingService->recalculateClassSTS('VII - Ibnu Taimiyah', $this->tahun->id, 'Ganjil');

        $sts1 = \App\Models\StudentStsSummary::where('siswa_id', $this->siswa->id)->first();
        $sts2 = \App\Models\StudentStsSummary::where('siswa_id', $s2->id)->first();
        $sts3 = \App\Models\StudentStsSummary::where('siswa_id', $s3->id)->first();
        $sts4 = \App\Models\StudentStsSummary::where('siswa_id', $s4->id)->first();

        // Check RANK.EQ: [95 -> rank 1, 90 -> rank 2, 90 -> rank 2, 85 -> rank 4]
        $this->assertEquals(1, $sts1->rank);
        $this->assertEquals(2, $sts2->rank);
        $this->assertEquals(2, $sts3->rank);
        $this->assertEquals(4, $sts4->rank);
    }

    public function test_teacher_can_create_and_delete_dynamic_assessment_item(): void
    {
        $comp = \App\Models\AssessmentComponent::where('code', 'uh')->first();

        // 1. Create dynamic item via web form
        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/item', [
                'assessment_component_id' => $comp->id,
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'title' => 'UH 3: Sistem Koordinat',
                'max_score' => 100,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('assessment_items', [
            'title' => 'UH 3: Sistem Koordinat',
            'mata_pelajaran_id' => $this->mapel->id,
        ]);

        $item = \App\Models\AssessmentItem::where('title', 'UH 3: Sistem Koordinat')->first();

        // 2. Delete item
        $delResponse = $this->actingAs($this->teacherUser)
            ->delete('/erapor/buku-nilai/item/' . $item->id);

        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('assessment_items', [
            'id' => $item->id,
        ]);
    }

    public function test_remedial_sync_mode_picks_highest_score(): void
    {
        // Get buku-nilai to ensure items exist
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $stsItem = \App\Models\AssessmentItem::whereHas('component', fn($q) => $q->where('code', 'sts'))->first();

        // Student initial low score = 65
        \App\Models\StudentAssessmentScore::create([
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $stsItem->id,
            'score' => 65.0,
            'source_type' => 'cbt_api',
        ]);

        // Mock Remedial Exam result = 85
        \Illuminate\Support\Facades\Http::fake([
            '*/exam-results*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 999,
                        'exam_external_id' => 'REM-STS-001',
                        'exam_name' => 'Remedial STS Matematika',
                        'student_nisn' => '0098765432',
                        'student_name' => 'Budi Santoso',
                        'class_name' => 'VII - Ibnu Taimiyah',
                        'subject_name' => 'Matematika',
                        'score' => 85.0,
                        'status' => 'submitted',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->post('/erapor/buku-nilai/sync-cbt', [
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'kelas' => 'VII - Ibnu Taimiyah',
                'target_assessment_item_id' => $stsItem->id,
                'sync_mode' => 'highest',
            ]);

        $response->assertRedirect();

        // Score should now be 85.0 (the higher score)
        $this->assertDatabaseHas('student_assessment_scores', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $stsItem->id,
            'score' => 85.0,
        ]);
    }

    public function test_user_can_access_buku_induk_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/erapor/buku-induk');

        $response->assertStatus(200)
            ->assertSee('Buku Induk')
            ->assertSee('Budi Santoso')
            ->assertSee('0098765432');
    }

    public function test_user_can_access_buku_induk_detail(): void
    {
        // Add a grade summary to test transcript display
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'sts_score' => 88.5,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/erapor/buku-induk/' . $this->siswa->id);

        $response->assertStatus(200)
            ->assertSee('Buku Induk')
            ->assertSee('Transkrip')
            ->assertSee('Budi Santoso')
            ->assertSee('Matematika')
            ->assertSee('88.5');
    }

    public function test_user_can_access_buku_induk_print_and_export(): void
    {
        // Test Print
        $printRes = $this->actingAs($this->adminUser)->get('/erapor/buku-induk/' . $this->siswa->id . '/print');
        $printRes->assertStatus(200)
            ->assertSee('BUKU INDUK PESERTA DIDIK')
            ->assertSee('Budi Santoso');

        // Test Excel Export
        $exportRes = $this->actingAs($this->adminUser)->get('/erapor/buku-induk/' . $this->siswa->id . '/export');
        $exportRes->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_rekap_sts_supports_historical_academic_year(): void
    {
        // Create past academic year
        $pastYear = TahunAjaran::create([
            'nama' => '2024/2025',
            'tanggal_mulai' => now()->subYears(2)->startOfYear(),
            'tanggal_selesai' => now()->subYears(2)->endOfYear(),
            'is_aktif' => false,
        ]);

        // Enroll student in past year
        \App\Models\RiwayatKelasSiswa::create([
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran_id' => $pastYear->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'status' => 'aktif',
        ]);

        // Historical grade
        \App\Models\SubjectGradeSummary::create([
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $pastYear->id,
            'semester' => 'Ganjil',
            'kelas' => 'VII - Ibnu Taimiyah',
            'sts_score' => 92.0,
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/erapor/recap?tahun_ajaran_id=' . $pastYear->id . '&semester=Ganjil&kelas=VII - Ibnu Taimiyah');

        $response->assertStatus(200)
            ->assertSee('2024/2025')
            ->assertSee('Arsip Masa Lalu')
            ->assertSee('Budi Santoso');

        // Test export for past year
        $exportRes = $this->actingAs($this->teacherUser)->get('/erapor/recap/export/VII - Ibnu Taimiyah?tahun_ajaran_id=' . $pastYear->id . '&semester=Ganjil');
        $exportRes->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_user_can_access_cetak_rapor_index_and_print_siswa(): void
    {
        // 1. Index page
        $indexRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah');
        $indexRes->assertStatus(200)
            ->assertSee('Cetak Rapor Siswa', false)
            ->assertSee('Budi Santoso');

        // 2. Individual student print
        $printRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts');
        $printRes->assertStatus(200)
            ->assertSee('LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER')
            ->assertSee('SMP AL - MADINAH')
            ->assertSee('BUDI SANTOSO');
    }

    public function test_user_can_bulk_print_class_rapor(): void
    {
        $bulkRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=sts');
        $bulkRes->assertStatus(200)
            ->assertSee('Cetak Semua Rapor Kelas')
            ->assertSee('BUDI SANTOSO');
    }

    public function test_catatan_wali_kelas_can_be_viewed_and_saved(): void
    {
        // 1. View form
        $res = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/catatan-wali-kelas?kelas=VII - Ibnu Taimiyah');
        $res->assertStatus(200)
            ->assertSee('Form Input Catatan Wali Kelas')
            ->assertSee('Budi Santoso');

        // 2. Save note
        $postRes = $this->actingAs($this->teacherUser)->post('/erapor/cetak-rapor/catatan-wali-kelas', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'catatan' => [
                $this->siswa->id => 'Ananda Budi sangat tekun, berprestasi, dan berakhlak mulia di kelas.',
            ],
        ]);

        $postRes->assertRedirect();
        $this->assertDatabaseHas('student_sts_summaries', [
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'catatan_wali_kelas' => 'Ananda Budi sangat tekun, berprestasi, dan berakhlak mulia di kelas.',
        ]);

        // 3. Verify it is printed on student report card
        $printRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts');
        $printRes->assertStatus(200)
            ->assertSee('Ananda Budi sangat tekun, berprestasi, dan berakhlak mulia di kelas.');
    }

    public function test_capaian_kompetensi_can_be_viewed_saved_and_auto_generated(): void
    {
        // 1. View form
        $res = $this->actingAs($this->teacherUser)->get('/erapor/capaian-kompetensi?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);
        $res->assertStatus(200)
            ->assertSee('Input Capaian Kompetensi Mapel')
            ->assertSee('Budi Santoso');

        // 2. Save custom description
        $customText = 'Menunjukkan penguasaan sangat baik dalam materi Aljabar dan Persamaan Linier Satu Variabel.';
        $postRes = $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => $customText,
            ],
        ]);

        $postRes->assertRedirect();
        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'capaian_kompetensi' => $customText,
        ]);

        // 3. Verify print report (UAS) displays this custom description
        $printRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas');
        $printRes->assertStatus(200)
            ->assertSee($customText);

        // 4. Test auto-generate
        $summary = \App\Models\SubjectGradeSummary::where('siswa_id', $this->siswa->id)
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->first();
        $summary->update(['sts_score' => 88.0]);

        $autoRes = $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/auto-generate', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ]);

        $autoRes->assertRedirect();
        $summary->refresh();

        $this->assertNotNull($summary->capaian_kompetensi);
        $this->assertStringContainsString('Matematika', $summary->capaian_kompetensi);
        $this->assertStringContainsString('telah mencapai tujuan pembelajaran', $summary->capaian_kompetensi);
    }

    public function test_ai_catatan_wali_kelas_endpoint_returns_generated_note(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'openrouter.ai/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Ananda Budi Santoso menunjukkan semangat belajar yang sangat baik, teruslah istiqomah dan berakhlak mulia.'
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->teacherUser)->postJson('/erapor/cetak-rapor/catatan-wali-kelas/generate-ai', [
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'catatan' => 'Ananda Budi Santoso menunjukkan semangat belajar yang sangat baik, teruslah istiqomah dan berakhlak mulia.',
                'siswa_id' => $this->siswa->id,
            ]);
    }

    public function test_user_can_access_rapor_pengaturan(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/erapor/cetak-rapor/pengaturan');

        $response->assertStatus(200)
            ->assertSee('Pengaturan Format Rapor')
            ->assertSee('Format Lembar Rapor');
    }

    public function test_user_can_update_rapor_document_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/erapor/cetak-rapor/pengaturan', [
            'nama_kurikulum' => 'Kurikulum Khas Al-Madinah',
            'show_kurikulum' => '1',
            // Note: show_fase is omitted to disable Fase D
            'kota_pembagian' => 'Tangerang Selatan',
            'tanggal_rapor' => '2026-10-25',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Dahlan, M.Pd.',
            'nip_kepala_sekolah' => '197001011995031002',
            'header_yayasan' => 'YAYASAN AL-MADINAH ISLAMIC EDUCATION',
            'header_sekolah' => 'SMP ISLAM PLUS AL-MADINAH',
        ]);

        $response->assertRedirect('/erapor/cetak-rapor/pengaturan');

        $settings = RaporSetting::instance();
        $this->assertEquals('Kurikulum Khas Al-Madinah', $settings->nama_kurikulum);
        $this->assertTrue($settings->show_kurikulum);
        $this->assertFalse($settings->show_fase);
        $this->assertEquals('Tangerang Selatan', $settings->kota_pembagian);
        $this->assertEquals('Dr. H. Ahmad Dahlan, M.Pd.', $settings->nama_kepala_sekolah);
    }

    public function test_user_can_update_homeroom_teacher_and_print_without_fase(): void
    {
        // 1. Setup custom document setting with show_fase = false
        $settings = RaporSetting::instance();
        $settings->update([
            'show_fase' => false,
            'show_kurikulum' => true,
            'nama_kurikulum' => 'Kurikulum Khas Al-Madinah',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Dahlan, M.Pd.',
            'nip_kepala_sekolah' => '197001011995031002',
            'kota_pembagian' => 'Tangerang Selatan',
        ]);

        // 2. Setup custom homeroom for student's class (by Admin/Operator)
        $response = $this->actingAs($this->adminUser)->post('/erapor/cetak-rapor/pengaturan/homerooms', [
            'tahun_ajaran_id' => $this->tahun->id,
            'homerooms' => [
                'VII - Ibnu Taimiyah' => [
                    'guru_id' => $this->guru->id,
                    'nama_wali_kelas' => 'Ustadzah Siti Aminah, S.Pd.I',
                    'nip_wali_kelas' => '198802022015042001',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('class_homerooms', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'nama_wali_kelas' => 'Ustadzah Siti Aminah, S.Pd.I',
            'nip_wali_kelas' => '198802022015042001',
        ]);

        // 3. Print STS & UAS report cards and verify:
        // - "Fase / D" or "Kelas / Fase" does NOT appear
        // - Custom curriculum name appears
        // - Custom homeroom teacher appears on STS (2-col signature) and headmaster appears on UAS (3-col signature)
        $printStsRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts');
        $printStsRes->assertStatus(200);
        $stsContent = $printStsRes->getContent();

        $this->assertStringNotContainsString('Fase D', $stsContent);
        $this->assertStringNotContainsString('Kelas / Fase', $stsContent);
        $this->assertStringContainsString('KURIKULUM KHAS AL-MADINAH', $stsContent);
        $this->assertStringContainsString('Ustadzah Siti Aminah, S.Pd.I', $stsContent);
        $this->assertStringContainsString('Tangerang Selatan', $stsContent);

        $printUasRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas');
        $printUasRes->assertStatus(200);
        $uasContent = $printUasRes->getContent();
        $this->assertStringContainsString('Dr. H. Ahmad Dahlan, M.Pd.', $uasContent);
    }

    public function test_end_to_end_guru_input_nilai_until_operator_walikelas_cetak_rapor_sts_and_uas(): void
    {
        // Create a second student in the same class to test class average & competition ranking
        $studentUser2 = User::factory()->create(['role' => 'siswa']);
        $siswa2 = Siswa::create([
            'user_id' => $studentUser2->id,
            'nisn' => '0098765499',
            'nama_lengkap' => 'Siti Rahmawati',
            'jenis_kelamin' => 'P',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Assign Jadwal for teacher so we also verify teacher name is NOT shown on UTS/STS print
        $lokasi = \App\Models\Lokasi::create([
            'nama' => 'Gedung Utama',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        $jadwal1 = \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        $jadwal2 = \App\Models\Jadwal::create([
            'tahun_ajaran_id' => $this->tahun->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'lokasi_id' => $lokasi->id,
            'hari' => 'Senin',
            'jam_mulai' => '09:00',
            'jam_selesai' => '10:30',
        ]);

        // Record SMART+ Attendance (AbsensiSiswa):
        // - 2 schedules on same date (2026-10-12) with status 'Sakit' -> should count as 1 Hari Sakit
        // - 1 date (2026-10-13) with status 'Izin' -> 1 Hari Izin
        // - 2 dates (2026-10-14 & 2026-10-15) with status 'Alfa' -> 2 Hari Tanpa Keterangan
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-12', 'status' => 'Sakit']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal2->id, 'tanggal' => '2026-10-12', 'status' => 'Sakit']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-13', 'status' => 'Izin']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-14', 'status' => 'Alfa']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal2->id, 'tanggal' => '2026-10-14', 'status' => 'Alfa']);
        \App\Models\AbsensiSiswa::create(['siswa_id' => $this->siswa->id, 'jadwal_id' => $jadwal1->id, 'tanggal' => '2026-10-15', 'status' => 'Alfa']);

        // STEP 1: Guru opens Buku Nilai to auto-provision assessment items (UH 1-3, Tugas 1-6, Praktek 1-2, Projek 1-2, Portofolio 1-2, STS 1, UAS)
        $bukuNilaiRes = $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);
        $bukuNilaiRes->assertStatus(200);

        $items = \App\Models\AssessmentItem::with('component')
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->where('tahun_ajaran_id', $this->tahun->id)
            ->where('semester', 'Ganjil')
            ->get();

        $this->assertCount(17, $items);

        // STEP 2: Guru inputs scores for Siswa 1 (Budi Santoso) & Siswa 2 (Siti Rahmawati)
        $budiScores = [
            'uh' => [1 => 85, 2 => 90, 3 => 88],
            'tugas' => [1 => 92, 2 => 89, 3 => 94, 4 => 90, 5 => 87, 6 => 91],
            'praktek' => [1 => 86, 2 => 88],
            'projek' => [1 => 93, 2 => 95],
            'portofolio' => [1 => 84, 2 => 86],
            'sts' => [1 => 90],
        ];

        foreach ($items as $item) {
            $code = $item->component->code;
            $seq = $item->sequence;
            if (isset($budiScores[$code][$seq])) {
                $res = $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
                    'siswa_id' => $this->siswa->id,
                    'assessment_item_id' => $item->id,
                    'score' => $budiScores[$code][$seq],
                ]);
                $res->assertStatus(200)->assertJson(['success' => true]);
            }
        }

        // Input STS score 80 for Siswa 2 so Budi is Rank 1 (90) and Siti is Rank 2 (80), class average = 85.00
        $stsItem = $items->first(fn ($i) => $i->component->code === 'sts' && $i->sequence === 1);
        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $siswa2->id,
            'assessment_item_id' => $stsItem->id,
            'score' => 80,
        ])->assertStatus(200);

        // STEP 3: Guru inputs Capaian Kompetensi
        $this->actingAs($this->teacherUser)->post('/erapor/capaian-kompetensi/save', [
            'mata_pelajaran_id' => $this->mapel->id,
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'capaian' => [
                $this->siswa->id => 'Sangat mahir dalam operasi aljabar dan aritmatika sosial.',
            ],
        ])->assertRedirect();

        // STEP 4: Wali Kelas inputs Catatan Wali Kelas
        $this->actingAs($this->teacherUser)->post('/erapor/cetak-rapor/catatan-wali-kelas', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'catatan' => [
                $this->siswa->id => 'Pertahankan prestasi dan teruslah menjadi teladan di kelas.',
            ],
        ])->assertRedirect();

        // STEP 5: Operator / Wali Kelas configures Rapor Settings (Paper Size F4 & Show Ranking = true)
        $operatorUser = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($operatorUser)->post('/erapor/cetak-rapor/pengaturan', [
            'nama_kurikulum' => 'Kurikulum Merdeka',
            'show_kurikulum' => '1',
            'show_fase' => '1',
            'fase_text' => 'Fase D',
            'ukuran_kertas' => 'F4',
            'show_ranking' => '1',
            'kota_pembagian' => 'Tangerang Selatan',
            'tanggal_rapor' => '2026-10-25',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Dahlan, M.Pd.',
            'nip_kepala_sekolah' => '197001011995031002',
            'header_yayasan' => 'YAYASAN KELUARGA BESAR MASYARAKAT BANTEN (KKMB)',
            'header_sekolah' => 'SMP AL - MADINAH ISLAMIC CENTER',
        ])->assertRedirect();

        // STEP 6: Operator / Wali Kelas prints Opsi 1: Rapor STS/UTS (Per Siswa)
        $stsPrintRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $stsPrintRes->assertStatus(200);
        $stsHtml = $stsPrintRes->getContent();

        // Verify F4 paper size CSS, 18-column headers, granular scores, no teacher name in mapel, summary & ranking
        $this->assertStringContainsString('215mm 330mm', $stsHtml);
        $this->assertStringContainsString('Ulangan Harian', $stsHtml);
        $this->assertStringContainsString('Tugas', $stsHtml);
        $this->assertStringContainsString('Praktek', $stsHtml);
        $this->assertStringContainsString('Projek', $stsHtml);
        $this->assertStringContainsString('Portofolio', $stsHtml);
        $this->assertStringContainsString('Nilai PTS', $stsHtml);
        $this->assertStringContainsString('Matematika', $stsHtml);
        $this->assertStringNotContainsString('(Guru Pengampu)', $stsHtml);
        $this->assertStringContainsString('Nilai Total PTS', $stsHtml);
        $this->assertStringContainsString('Nilai Rata-rata Siswa', $stsHtml);
        $this->assertStringContainsString('Nilai Rata-rata Kelas', $stsHtml);
        $this->assertStringContainsString('85.00', $stsHtml); // Class average of 90 and 80
        $this->assertStringContainsString('Ke-1 / 2', $stsHtml); // Rank 1 of 2 students
        $this->assertStringContainsString('1 Hari', $stsHtml); // 1 Hari Sakit & 1 Hari Izin (distinct dates)
        $this->assertStringContainsString('2 Hari', $stsHtml); // 2 Hari Tanpa Keterangan (Alfa)
        $this->assertStringContainsString('Wali Murid', $stsHtml);

        // STEP 7: Operator / Wali Kelas prints Opsi 2: Rapor UAS (Per Siswa)
        $uasPrintRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $uasPrintRes->assertStatus(200);
        $uasHtml = $uasPrintRes->getContent();

        $this->assertStringContainsString('LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK', $uasHtml);
        $this->assertStringContainsString('Sangat mahir dalam operasi aljabar dan aritmatika sosial.', $uasHtml);
        $this->assertStringContainsString('Pertahankan prestasi dan teruslah menjadi teladan di kelas.', $uasHtml);
        $this->assertStringContainsString('Peringkat ke-1', $uasHtml);
        $this->assertStringContainsString('Dr. H. Ahmad Dahlan, M.Pd.', $uasHtml);

        // STEP 8: Bulk Class Print for both STS and UAS
        $bulkStsRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=sts&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $bulkStsRes->assertStatus(200)
            ->assertSee('BUDI SANTOSO')
            ->assertSee('SITI RAHMAWATI')
            ->assertSee('Nilai Total PTS')
            ->assertSee('Ke-1 / 2')
            ->assertSee('Ke-2 / 2');

        $bulkUasRes = $this->actingAs($operatorUser)
            ->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=uas&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $bulkUasRes->assertStatus(200)
            ->assertSee('BUDI SANTOSO')
            ->assertSee('SITI RAHMAWATI')
            ->assertSee('Sangat mahir dalam operasi aljabar dan aritmatika sosial.');
    }

    public function test_role_access_and_sidebar_menus_for_guru_admin_sekolah_kepala_sekolah_and_super_admin(): void
    {
        $kepsekUser = User::factory()->create(['role' => 'kepala_sekolah']);
        $superAdminUser = User::factory()->create(['role' => 'super_admin']);

        // 1A. Verify Non-Wali Kelas Guru cannot see or access Cetak Rapor
        $nonWaliUser = User::factory()->create(['role' => 'guru']);
        \App\Models\Guru::create([
            'user_id' => $nonWaliUser->id,
            'nip' => '199001012020011009',
            'nama_lengkap' => 'Guru Mapel Biasa',
            'jenis_kelamin' => 'L',
        ]);

        $this->actingAs($nonWaliUser);
        $nonWaliMenus = collect(\App\Helpers\MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => collect($g['items'])->pluck('name'))
            ->all();

        $this->assertContains('Buku Nilai Mapel', $nonWaliMenus);
        $this->assertNotContains('Cetak Rapor Siswa', $nonWaliMenus);
        $this->assertNotContains('Catatan Wali Kelas', $nonWaliMenus);

        $this->actingAs($nonWaliUser)->get('/erapor/buku-nilai')->assertStatus(200);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor')->assertStatus(403);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor/catatan-wali-kelas')->assertStatus(403);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id)->assertStatus(403);
        $this->actingAs($nonWaliUser)->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah')->assertStatus(403);

        // 1B. Verify Wali Kelas Guru (selected in Format & TTD Rapor) CAN see & access Cetak Rapor for their class
        $this->actingAs($this->teacherUser);
        $guruMenus = collect(\App\Helpers\MenuHelper::getMenuGroups())
            ->flatMap(fn ($g) => collect($g['items'])->pluck('name'))
            ->all();

        $this->assertContains('Buku Nilai Mapel', $guruMenus);
        $this->assertContains('Cetak Rapor Siswa', $guruMenus);
        $this->assertNotContains('Dashboard E-Rapor', $guruMenus);
        $this->assertNotContains('Integrasi CBT', $guruMenus);
        $this->assertNotContains('Format & TTD Rapor', $guruMenus);
        $this->assertNotContains('Buku Induk & Transkrip', $guruMenus);

        // Wali Kelas cannot print a different class they are not assigned to
        $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/kelas/IX - Al-Khawarizmi')->assertStatus(403);

        // Guru can access Buku Nilai & Cetak Rapor (STS & UAS), and UI hides Finalize & Format buttons
        $this->actingAs($this->teacherUser)->get('/erapor/buku-nilai')->assertStatus(200);
        $cetakGuruRes = $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah');
        $cetakGuruRes->assertStatus(200)
            ->assertDontSee('Format & TTD Rapor')
            ->assertDontSee('Buka Rekap STS untuk Finalisasi');

        $rekapGuruRes = $this->actingAs($this->teacherUser)->get('/erapor/recap?kelas=VII - Ibnu Taimiyah');
        $rekapGuruRes->assertStatus(200)
            ->assertDontSee('Finalisasi Rapor Kelas')
            ->assertSee('Status: Draf (Menunggu Finalisasi Operator)');

        // Guru gets 403 on restricted routes
        $this->actingAs($this->teacherUser)->get('/erapor')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cbt')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(403);
        $this->actingAs($this->teacherUser)->get('/erapor/buku-induk')->assertStatus(403);
        $this->actingAs($this->teacherUser)->post('/erapor/recap/finalize/VII - Ibnu Taimiyah', [
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ])->assertStatus(403);

        // 2. Verify Admin Sekolah, Kepala Sekolah, and Super Admin have full access to all 5 restricted features
        foreach ([$this->adminUser, $kepsekUser, $superAdminUser] as $privilegedUser) {
            $this->actingAs($privilegedUser);
            $menus = collect(\App\Helpers\MenuHelper::getMenuGroups())
                ->flatMap(fn ($g) => collect($g['items'])->pluck('name'))
                ->all();

            $this->assertContains('Dashboard E-Rapor', $menus);
            $this->assertContains('Integrasi CBT', $menus);
            $this->assertContains('Format & TTD Rapor', $menus);
            $this->assertContains('Buku Induk & Transkrip', $menus);

            $this->actingAs($privilegedUser)->get('/erapor')->assertStatus(200);
            $this->actingAs($privilegedUser)->get('/erapor/cbt')->assertStatus(200);
            $this->actingAs($privilegedUser)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(200);
            $this->actingAs($privilegedUser)->get('/erapor/buku-induk')->assertStatus(200);
        }

        // Verify Finalisasi Rapor works for Admin Sekolah / Kepala Sekolah / Super Admin
        $finalizeRes = $this->actingAs($this->adminUser)->post('/erapor/recap/finalize/VII - Ibnu Taimiyah', [
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
        ]);
        $finalizeRes->assertRedirect();
        $this->assertDatabaseHas('report_finalizations', [
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
            'semester' => 'Ganjil',
            'status' => 'final',
        ]);
    }

    public function test_uas_grading_scheme_30_30_40_and_normalization_with_partial_components(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/exams*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
        ]);

        // Provision all 17 assessment items for Matematika
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id)
            ->assertStatus(200);

        $items = \App\Models\AssessmentItem::with('component')
            ->where('mata_pelajaran_id', $this->mapel->id)
            ->where('tahun_ajaran_id', $this->tahun->id)
            ->where('semester', 'Ganjil')
            ->get();

        $uh1 = $items->first(fn ($i) => $i->component->code === 'uh' && $i->sequence === 1);
        $tugas1 = $items->first(fn ($i) => $i->component->code === 'tugas' && $i->sequence === 1);
        $sts1 = $items->first(fn ($i) => $i->component->code === 'sts' && $i->sequence === 1);
        $uas1 = $items->first(fn ($i) => $i->component->code === 'uas' && $i->sequence === 1);

        $this->assertNotNull($uas1);

        // Case 1: Only UH (80) & Tugas (90) filled out of the 5 formative components; Praktek/Projek/Portofolio null -> Formatif = 85
        // Plus STS (80) filled, UAS still empty -> Normalized over 30 + 30 = 60: (85*30 + 80*30)/60 = 82.50
        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $uh1->id,
            'score' => 80,
        ])->assertStatus(200);

        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $tugas1->id,
            'score' => 90,
        ])->assertStatus(200);

        $resBeforeUas = $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $sts1->id,
            'score' => 80,
        ]);
        $resBeforeUas->assertStatus(200);
        $this->assertEquals(82.5, (float) $resBeforeUas->json('summary.final_score'));

        // Case 2: Fill UAS (95) -> 85*0.30 + 80*0.30 + 95*0.40 = 25.5 + 24.0 + 38.0 = 87.50
        $resWithUas = $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $uas1->id,
            'score' => 95,
        ]);
        $resWithUas->assertStatus(200);
        $this->assertEquals(95.0, (float) $resWithUas->json('summary.uas_score'));
        $this->assertEquals(87.5, (float) $resWithUas->json('summary.final_score'));

        // Case 3: Student with 0 score on UAS (0 is valid, not null!) and missing UTS -> (80*30 + 0*40)/70 = 34.29
        $s2 = Siswa::create([
            'user_id' => User::factory()->create(['role' => 'siswa'])->id,
            'nisn' => '0098765488',
            'nama_lengkap' => 'Ahmad Zero',
            'jenis_kelamin' => 'L',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $s2->id,
            'assessment_item_id' => $uh1->id,
            'score' => 80,
        ])->assertStatus(200);

        $resZero = $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $s2->id,
            'assessment_item_id' => $uas1->id,
            'score' => 0,
        ]);
        $resZero->assertStatus(200);
        $this->assertEquals(0.0, (float) $resZero->json('summary.uas_score'));
        $this->assertEquals(34.29, (float) $resZero->json('summary.final_score'));
    }

    public function test_cbt_sync_to_uas_and_uas_rapor_ranking_overrides_sts_displays(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/exams*' => \Illuminate\Support\Facades\Http::response(['data' => []], 200),
            '*/exam-results*' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 501,
                        'exam_external_id' => 'UAS-CBT-001',
                        'exam_name' => 'UAS Matematika VII',
                        'student_nisn' => '0098765432',
                        'student_name' => 'Budi Santoso',
                        'class_name' => 'VII - Ibnu Taimiyah',
                        'subject_name' => 'Matematika',
                        'score' => 60.0,
                        'status' => 'submitted',
                    ],
                    [
                        'id' => 502,
                        'exam_external_id' => 'UAS-CBT-001',
                        'exam_name' => 'UAS Matematika VII',
                        'student_nisn' => '0098765477',
                        'student_name' => 'Rina Juara',
                        'class_name' => 'VII - Ibnu Taimiyah',
                        'subject_name' => 'Matematika',
                        'score' => 98.0,
                        'status' => 'submitted',
                    ],
                ],
            ], 200),
        ]);

        $rina = Siswa::create([
            'user_id' => User::factory()->create(['role' => 'siswa'])->id,
            'nisn' => '0098765477',
            'nama_lengkap' => 'Rina Juara',
            'jenis_kelamin' => 'P',
            'kelas' => 'VII - Ibnu Taimiyah',
            'tahun_ajaran_id' => $this->tahun->id,
        ]);

        // Provision items
        $this->actingAs($this->teacherUser)
            ->get('/erapor/buku-nilai?kelas=VII - Ibnu Taimiyah&mata_pelajaran_id=' . $this->mapel->id);

        $uh1 = \App\Models\AssessmentItem::whereHas('component', fn ($q) => $q->where('code', 'uh'))->first();
        $sts1 = \App\Models\AssessmentItem::whereHas('component', fn ($q) => $q->where('code', 'sts'))->first();

        // Give Budi higher STS (90) and Formatif (90), Rina lower STS (70) and Formatif (90)
        // So STS rank: Budi #1 (90), Rina #2 (70)
        foreach ([
            [$this->siswa->id, $uh1->id, 90],
            [$this->siswa->id, $sts1->id, 90],
            [$rina->id, $uh1->id, 90],
            [$rina->id, $sts1->id, 70],
        ] as [$sid, $iid, $val]) {
            $this->actingAs($this->teacherUser)->postJson('/erapor/buku-nilai/update-score', [
                'siswa_id' => $sid,
                'assessment_item_id' => $iid,
                'score' => $val,
            ])->assertStatus(200);
        }

        // Sync UAS scores from CBT API with component_code=uas
        // Budi UAS = 60 -> Final = 90*0.3 + 90*0.3 + 60*0.4 = 27 + 27 + 24 = 78.00
        // Rina UAS = 98 -> Final = 90*0.3 + 70*0.3 + 98*0.4 = 27 + 21 + 39.2 = 87.20
        // So UAS rank reverses: Rina #1 (87.2), Budi #2 (78.0)!
        $this->actingAs($this->adminUser)
            ->post('/erapor/cbt/sync-api', [
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'kelas' => 'VII - Ibnu Taimiyah',
                'component_code' => 'uas',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'sts_score' => 90.0,
            'uas_score' => 60.0,
            'final_score' => 78.0,
        ]);

        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $rina->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'sts_score' => 70.0,
            'uas_score' => 98.0,
            'final_score' => 87.2,
        ]);

        // Verify separated STS vs UAS rankings in StudentStsSummary
        $this->assertDatabaseHas('student_sts_summaries', [
            'siswa_id' => $this->siswa->id,
            'rank' => 1,
            'uas_rank' => 2,
            'uas_average_score' => 78.0,
        ]);
        $this->assertDatabaseHas('student_sts_summaries', [
            'siswa_id' => $rina->id,
            'rank' => 2,
            'uas_rank' => 1,
            'uas_average_score' => 87.2,
        ]);

        // 1. Cetak Rapor UAS for Budi must show Formatif 90.0, Sumatif (UAS) 60.0 (NOT STS 90), Nilai Akhir 78.0, and Rank 2
        $uasPrintBudi = $this->actingAs($this->teacherUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=uas&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $uasPrintBudi->assertStatus(200)
            ->assertSee('60.0')
            ->assertSee('78.0')
            ->assertSee('Peringkat ke-2');

        // 2. Cetak Rapor STS for Budi must STILL show STS Rank 1 and PTS 90
        $stsPrintBudi = $this->actingAs($this->teacherUser)
            ->get('/erapor/cetak-rapor/siswa/' . $this->siswa->id . '?jenis_rapor=sts&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $stsPrintBudi->assertStatus(200)
            ->assertSee('Ke-1 / 2');

        // 3. Bulk Print UAS uses UAS rank for each student
        $bulkUas = $this->actingAs($this->teacherUser)
            ->get('/erapor/cetak-rapor/kelas/VII - Ibnu Taimiyah?jenis_rapor=uas&semester=Ganjil&tahun_ajaran_id=' . $this->tahun->id);
        $bulkUas->assertStatus(200)
            ->assertSee('87.2')
            ->assertSee('Peringkat ke-1')
            ->assertSee('Peringkat ke-2');

        // 4. Overview pages automatically switch to UAS averages & ranks once UAS scores exist
        $this->actingAs($this->teacherUser)
            ->get('/erapor/cetak-rapor?kelas=VII - Ibnu Taimiyah')
            ->assertStatus(200)
            ->assertSee('Opsi 2: Rapor UAS')
            ->assertSee('87.2')
            ->assertSee('78.0');

        $this->actingAs($this->teacherUser)
            ->get('/erapor/recap?kelas=VII - Ibnu Taimiyah')
            ->assertStatus(200)
            ->assertSee('Rekapitulasi Nilai UAS')
            ->assertSee('87.20')
            ->assertSee('78.00');

        $this->actingAs($this->adminUser)
            ->get('/erapor')
            ->assertStatus(200)
            ->assertSee('Peringkat Teratas UAS')
            ->assertSee('87.20');

        $this->actingAs($this->adminUser)
            ->get('/erapor/buku-induk/' . $rina->id)
            ->assertStatus(200)
            ->assertSee('87.2')
            ->assertSee('Ke-1');

        $this->actingAs($this->teacherUser)
            ->get('/erapor/cetak-rapor/catatan-wali-kelas?kelas=VII - Ibnu Taimiyah')
            ->assertStatus(200)
            ->assertSee('87.2')
            ->assertSee('78.0');
    }

    public function test_cbt_excel_import_can_target_uas_component(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['NISN', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Ujian', 'Nilai'],
            ['0098765432', 'Budi Santoso', 'VII - Ibnu Taimiyah', 'Matematika', 'UAS Matematika', 85],
        ]);

        $tmpPath = sys_get_temp_dir() . '/test_cbt_uas_' . uniqid() . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmpPath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tmpPath,
            'uas_matematika.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->actingAs($this->adminUser)
            ->post('/erapor/cbt/import-excel', [
                'file' => $uploadedFile,
                'mata_pelajaran_id' => $this->mapel->id,
                'tahun_ajaran_id' => $this->tahun->id,
                'semester' => 'Ganjil',
                'kelas' => 'VII - Ibnu Taimiyah',
                'component_code' => 'uas',
            ])
            ->assertRedirect();

        @unlink($tmpPath);

        $this->assertDatabaseHas('subject_grade_summaries', [
            'siswa_id' => $this->siswa->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'uas_score' => 85.0,
            'final_score' => 85.0,
        ]);
    }
}


