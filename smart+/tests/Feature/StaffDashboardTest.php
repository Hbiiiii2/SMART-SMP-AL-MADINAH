<?php

namespace Tests\Feature;

use App\Models\StaffAkademik;
use App\Models\PengaturanSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        PengaturanSekolah::create([
            'nama_sekolah' => 'Sekolah Test',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'Hybrid',
        ]);
    }

    public function test_staff_dashboard_hybrid_shows_rfid_message_not_zonasi()
    {
        $user = User::factory()->create(['role' => 'staff_akademik']);
        $staff = StaffAkademik::create([
            'pengguna_id' => $user->id,
            'nip' => '123456789',
            'nama_lengkap' => 'Staff Test',
            'jenis_kelamin' => 'L',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertStatus(200);
        
        $response->assertSee('Hybrid (Manual / RFID)');
        
        // It should NOT include gps-absensi
        $response->assertDontSee('navigator.geolocation.getCurrentPosition');
    }

    public function test_staff_dashboard_zonasi_shows_zonasi_view()
    {
        $user = User::factory()->create(['role' => 'staff_akademik']);
        $staff = StaffAkademik::create([
            'pengguna_id' => $user->id,
            'nip' => '123456790',
            'nama_lengkap' => 'Staff Test 2',
            'jenis_kelamin' => 'L',
            'status_aktif' => true,
        ]);

        // Change to Zonasi
        $pengaturan = PengaturanSekolah::first();
        $pengaturan->update(['metode_absensi_staff' => 'Zonasi']);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertStatus(200);
        // Should show GPS script
        $response->assertSee('navigator.geolocation.getCurrentPosition');
        $response->assertDontSee('Metode absensi Anda saat ini menggunakan');
    }
}
