<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\PengaturanSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat pengaturan default
        PengaturanSekolah::create([
            'nama_sekolah' => 'Sekolah Test',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'RFID',
        ]);
    }

    public function test_guru_dashboard_hybrid_shows_rfid_view_not_zonasi()
    {
        $user = User::factory()->create(['role' => 'guru']);
        $guru = Guru::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertStatus(200);
        // Hybrid should NOT show Check-in Zonasi buttons (Check-in Sekarang / Check-out Sekarang)
        $response->assertDontSee('Check-in Sekarang');
        $response->assertDontSee('Check-out Sekarang');
        
        // It should show Lihat Status Absensi
        $response->assertSee('Lihat Status Absensi');
        
        // It should NOT include gps-absensi
        $response->assertDontSee('navigator.geolocation.getCurrentPosition');
    }

    public function test_guru_dashboard_zonasi_shows_zonasi_view()
    {
        $user = User::factory()->create(['role' => 'guru']);
        $guru = Guru::factory()->create(['user_id' => $user->id]);

        // Change to Zonasi
        $pengaturan = PengaturanSekolah::first();
        $pengaturan->update(['metode_absensi_guru' => 'Zonasi']);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertStatus(200);
        // Should show Check-in/Check-out buttons and GPS script
        $response->assertSee('Check-in Sekarang');
        $response->assertSee('navigator.geolocation.getCurrentPosition');
    }
}
