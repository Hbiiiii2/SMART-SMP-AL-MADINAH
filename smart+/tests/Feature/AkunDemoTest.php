<?php

namespace Tests\Feature;

use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\Guru;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RaporSetting;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AkunDemoTest extends TestCase
{
    use RefreshDatabase;

    private TahunAjaran $tahunAjaran;
    private MataPelajaran $mapel;
    private Guru $guru;
    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response(['status' => 'ok', 'version' => 'v1-test'], 200),
        ]);

        $this->tahunAjaran = TahunAjaran::factory()->aktif()->create([
            'nama' => '2026/2027',
        ]);

        $this->mapel = MataPelajaran::create([
            'kode' => 'MTK',
            'nama' => 'Matematika',
        ]);

        $this->guru = Guru::factory()->create();

        $this->siswa = Siswa::factory()->create([
            'kelas' => '7-A',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
    }

    public function test_active_akun_demo_can_login_and_redirects_to_admin_dashboard(): void
    {
        $demo = User::factory()->akunDemo(now()->addDays(3))->create();

        $response = $this->post('/login', [
            'email' => $demo->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($demo);

        $this->actingAs($demo)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_expired_akun_demo_cannot_login(): void
    {
        $expiredDemo = User::factory()->akunDemo(now()->subMinute())->create();

        $response = $this->post('/login', [
            'email' => $expiredDemo->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_active_session_of_expired_akun_demo_is_logged_out_on_web_and_json_requests(): void
    {
        $expiredDemo = User::factory()->akunDemo(now()->subHour())->create();

        $webResponse = $this->actingAs($expiredDemo)->get('/admin/dashboard');
        $webResponse->assertRedirect(route('login'));
        $webResponse->assertSessionHasErrors('email');
        $this->assertGuest();

        $jsonResponse = $this->actingAs($expiredDemo)->getJson('/admin/dashboard');
        $jsonResponse->assertStatus(401)
            ->assertJsonStructure(['message']);
        $this->assertGuest();
    }

    public function test_akun_demo_can_access_all_preview_routes_and_sees_preview_banner(): void
    {
        $demo = User::factory()->akunDemo(now()->addDays(5))->create();

        $previewRoutes = [
            '/admin/dashboard',
            '/admin/guru',
            '/admin/siswa',
            '/admin/mata-pelajaran',
            '/admin/jadwal',
            '/admin/tahun-ajaran',
            '/admin/lokasi',
            '/admin/rfid',
            '/admin/hari-libur',
            '/admin/tanggal-akademik',
            '/admin/pengaturan-sekolah',
            '/admin/laporan',
            '/admin/update-history',
            '/admin/profil',
            '/erapor',
            '/erapor/buku-nilai',
            '/erapor/cbt',
            '/erapor/capaian-kompetensi',
            '/erapor/recap',
            '/erapor/cetak-rapor',
            '/erapor/cetak-rapor/pengaturan',
            '/erapor/cetak-rapor/catatan-wali-kelas',
            '/erapor/buku-induk',
            '/kepala-sekolah/dashboard',
            '/kepala-sekolah/monitoring-guru',
            '/kepala-sekolah/monitoring-siswa',
            '/kepala-sekolah/rekap',
            '/kepala-sekolah/cuti',
            '/direktur/dashboard',
            '/direktur/cuti',
            '/kalender',
        ];

        $this->actingAs($demo)
            ->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee('Mode Preview (Akun Demo — Read-Only)');

        foreach ($previewRoutes as $uri) {
            $response = $this->actingAs($demo)->get($uri);
            $this->assertSame(
                200,
                $response->status(),
                "Expected 200 on preview route [{$uri}], got {$response->status()}"
            );
        }
    }

    public function test_akun_demo_is_blocked_from_create_edit_and_sensitive_get_routes(): void
    {
        $demo = User::factory()->akunDemo(now()->addDays(5))->create();

        $blockedRoutes = [
            '/admin/guru/create',
            '/admin/siswa/create',
            '/admin/siswa/kenaikan-kelas',
            '/admin/guru/' . $this->guru->id . '/edit',
            '/admin/ubah-password',
            '/admin/users',
            '/admin/log-aktivitas',
            '/admin/backup',
            '/admin/backup/export',
            '/kepala-sekolah/absensi-saya',
        ];

        foreach ($blockedRoutes as $uri) {
            $response = $this->actingAs($demo)->get($uri);
            $this->assertSame(
                403,
                $response->status(),
                "Expected 403 on blocked route [{$uri}], got {$response->status()}"
            );
        }
    }

    public function test_akun_demo_cannot_mutate_data_via_post_put_delete_or_json(): void
    {
        $demo = User::factory()->akunDemo(now()->addDays(5))->create();
        $pengaturan = PengaturanSekolah::create([
            'nama_sekolah' => 'SMP Al Madinah Test',
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'RFID',
        ]);

        $initialGuruCount = Guru::count();
        $originalNamaGuru = $this->guru->nama_lengkap;
        $originalDemoEmail = $demo->email;
        $originalDemoPassword = $demo->password;

        // Create guru -> 403
        $this->actingAs($demo)->post('/admin/guru', [
            'name' => 'Guru Baru',
            'email' => 'gurubaru@example.com',
            'password' => 'password123',
            'nip' => '9999999999',
            'nama_lengkap' => 'Guru Baru',
            'jenis_kelamin' => 'L',
        ])->assertStatus(403);
        $this->assertSame($initialGuruCount, Guru::count());

        // Update guru -> 403
        $this->actingAs($demo)->put('/admin/guru/' . $this->guru->id, [
            'nama_lengkap' => 'Diubah Demo',
            'nip' => $this->guru->nip,
            'jenis_kelamin' => 'L',
        ])->assertStatus(403);
        $this->assertSame($originalNamaGuru, $this->guru->fresh()->nama_lengkap);

        // Delete guru -> 403
        $this->actingAs($demo)->delete('/admin/guru/' . $this->guru->id)->assertStatus(403);
        $this->assertDatabaseHas('guru', ['id' => $this->guru->id]);

        // Update pengaturan sekolah -> 403
        $this->actingAs($demo)->put('/admin/pengaturan-sekolah', [
            'nama_sekolah' => 'Nama Berubah',
        ])->assertStatus(403);
        $this->assertSame('SMP Al Madinah Test', $pengaturan->fresh()->nama_sekolah);

        // Update profil -> 403
        $this->actingAs($demo)->put('/admin/profil', [
            'email' => 'changed@example.com',
        ])->assertStatus(403);
        $this->assertSame($originalDemoEmail, $demo->fresh()->email);

        // Update password -> 403
        $this->actingAs($demo)->put('/admin/ubah-password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(403);
        $this->assertSame($originalDemoPassword, $demo->fresh()->password);

        // Create E-Rapor item -> 403
        $component = AssessmentComponent::create([
            'code' => 'uh',
            'name' => 'Ulangan Harian',
            'default_count' => 1,
            'default_weight' => 10,
            'order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($demo)->post('/erapor/buku-nilai/item', [
            'assessment_component_id' => $component->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'title' => 'UH Demo',
        ])->assertStatus(403);
        $this->assertSame(0, AssessmentItem::count());

        // Update score via JSON AJAX -> 403
        $item = AssessmentItem::create([
            'assessment_component_id' => $component->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'semester' => 'Ganjil',
            'sequence' => 1,
            'title' => 'UH 1',
            'max_score' => 100,
        ]);

        $this->actingAs($demo)->postJson('/erapor/buku-nilai/update-score', [
            'siswa_id' => $this->siswa->id,
            'assessment_item_id' => $item->id,
            'score' => 95,
        ])->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('student_assessment_scores', 0);
    }

    public function test_akun_demo_get_requests_cause_zero_database_inserts(): void
    {
        $demo = User::factory()->akunDemo(now()->addDays(5))->create();

        $initialComponents = AssessmentComponent::count();
        $initialItems = AssessmentItem::count();
        $initialPengaturan = PengaturanSekolah::count();
        $initialRaporSetting = RaporSetting::count();
        $initialStsSummary = \App\Models\StudentStsSummary::count();
        $initialGradeSummary = \App\Models\SubjectGradeSummary::count();

        $this->actingAs($demo)->get('/erapor/buku-nilai')->assertStatus(200);
        $this->actingAs($demo)->get('/admin/pengaturan-sekolah')->assertStatus(200);
        $this->actingAs($demo)->get('/erapor/cetak-rapor/pengaturan')->assertStatus(200);
        $this->actingAs($demo)->get('/erapor/recap')->assertStatus(200);
        $this->actingAs($demo)->get('/erapor/cetak-rapor')->assertStatus(200);
        $this->actingAs($demo)->get('/erapor/cetak-rapor/catatan-wali-kelas')->assertStatus(200);

        $this->assertSame($initialComponents, AssessmentComponent::count());
        $this->assertSame($initialItems, AssessmentItem::count());
        $this->assertSame($initialPengaturan, PengaturanSekolah::count());
        $this->assertSame($initialRaporSetting, RaporSetting::count());
        $this->assertSame($initialStsSummary, \App\Models\StudentStsSummary::count());
        $this->assertSame($initialGradeSummary, \App\Models\SubjectGradeSummary::count());
    }

    public function test_akun_demo_can_logout_cleanly_even_when_expired(): void
    {
        $activeDemo = User::factory()->akunDemo(now()->addDays(2))->create();
        $this->actingAs($activeDemo)
            ->post('/logout')
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $expiredDemo = User::factory()->akunDemo(now()->subDay())->create();
        $this->actingAs($expiredDemo)
            ->post('/logout')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_super_admin_can_create_and_update_akun_demo_with_expiration_and_validation_enforces_expiry(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // Missing expired_at on creation fails validation
        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Calon Demo',
            'email' => 'calon-demo@example.com',
            'role' => 'akun_demo',
            'password' => 'password123',
            'is_active' => '1',
        ])->assertSessionHasErrors('expired_at');

        // Past expired_at on creation fails validation
        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Calon Demo Past',
            'email' => 'calon-demo-past@example.com',
            'role' => 'akun_demo',
            'expired_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'password' => 'password123',
            'is_active' => '1',
        ])->assertSessionHasErrors('expired_at');

        // Valid creation succeeds
        $futureExpiry = now()->addDays(10)->format('Y-m-d\TH:i');
        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Demo Client A',
            'email' => 'demo-client-a@example.com',
            'role' => 'akun_demo',
            'expired_at' => $futureExpiry,
            'password' => 'password123',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'demo-client-a@example.com')->firstOrFail();
        $this->assertSame('akun_demo', $created->role);
        $this->assertNotNull($created->expired_at);

        // Super admin can extend expiration via update
        $newExpiry = now()->addDays(20)->format('Y-m-d\TH:i');
        $this->actingAs($superAdmin)->put(route('admin.users.update', $created), [
            'name' => 'Demo Client A Extended',
            'email' => 'demo-client-a@example.com',
            'role' => 'akun_demo',
            'expired_at' => $newExpiry,
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $created->refresh();
        $this->assertSame('Demo Client A Extended', $created->name);
        $this->assertTrue($created->expired_at->greaterThan(now()->addDays(15)));

        // Switching role away from akun_demo clears expired_at
        $this->actingAs($superAdmin)->put(route('admin.users.update', $created), [
            'name' => 'Jadi Guru',
            'email' => 'demo-client-a@example.com',
            'role' => 'guru',
            'expired_at' => $newExpiry,
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $created->refresh();
        $this->assertSame('guru', $created->role);
        $this->assertNull($created->expired_at);
    }

    public function test_demo_user_seeder_creates_demo_account(): void
    {
        $this->seed(DemoUserSeeder::class);

        $demo = User::where('email', 'demo@smart.sch.id')->first();
        $this->assertNotNull($demo);
        $this->assertSame('akun_demo', $demo->role);
        $this->assertTrue($demo->is_active);
        $this->assertNotNull($demo->expired_at);
        $this->assertTrue($demo->expired_at->isFuture());
    }
}
