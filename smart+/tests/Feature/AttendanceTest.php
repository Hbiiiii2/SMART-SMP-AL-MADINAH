<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\RfidCard;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setupTestData(): array
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:10:00', 'Asia/Jakarta'));

        $ta = TahunAjaran::factory()->aktif()->create();
        $lokasi = Lokasi::factory()->create();
        $mp = MataPelajaran::factory()->create();
        $guruUser = User::factory()->guru()->create(['is_active' => true]);
        $guru = Guru::factory()->create(['user_id' => $guruUser->id]);

        $jadwal = Jadwal::factory()->create([
            'guru_id' => $guru->id,
            'mata_pelajaran_id' => $mp->id,
            'tahun_ajaran_id' => $ta->id,
            'lokasi_id' => $lokasi->id,
            'kelas' => 'X-1',
            'hari' => match (now('Asia/Jakarta')->dayOfWeek) {
                1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
            },
            'jam_mulai' => '06:00',
            'jam_selesai' => '23:59',
        ]);

        $siswaUser = User::factory()->siswa()->create(['is_active' => true]);
        $siswa = Siswa::factory()->create([
            'user_id' => $siswaUser->id,
            'kelas' => 'X-1',
            'tahun_ajaran_id' => $ta->id,
        ]);

        $rfid = RfidCard::create([
            'siswa_id' => $siswa->id,
            'uid' => 'TEST-SCAN-001',
            'is_active' => true,
        ]);

        return compact('ta', 'lokasi', 'mp', 'guru', 'guruUser', 'jadwal', 'siswa', 'siswaUser', 'rfid');
    }

    public function test_rfid_scan_creates_attendance(): void
    {
        $data = $this->setupTestData();

        $response = $this->postJson('/api/rfid/scan', ['uid' => 'TEST-SCAN-001']);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'role' => 'siswa',
            'type' => 'masuk',
        ]);

        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $data['siswa']->id,
            'jadwal_id' => $data['jadwal']->id,
        ]);
    }

    public function test_rfid_scan_invalid_returns_error(): void
    {
        $response = $this->postJson('/api/rfid/scan', ['uid' => 'INVALID-UID']);
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_guru_rfid_scan_creates_check_in(): void
    {
        $data = $this->setupTestData();

        RfidCard::create([
            'guru_id' => $data['guru']->id,
            'uid' => 'GRF-TEST-001',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/rfid/scan', ['uid' => 'GRF-TEST-001']);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'role' => 'guru',
        ]);

        $this->assertDatabaseHas('absensi_guru', [
            'guru_id' => $data['guru']->id,
        ]);
    }

    public function test_guru_absensi_page_requires_auth(): void
    {
        $response = $this->get('/guru/absensi');
        $response->assertRedirect('/login');
    }

    public function test_admin_cannot_access_guru_absensi(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin_sekolah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/guru/absensi');
        $response->assertStatus(403);
    }
}
