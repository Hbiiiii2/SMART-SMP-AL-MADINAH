<?php

namespace Tests\Feature;

use App\Models\PengaturanSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PengaturanSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create initial settings
        PengaturanSekolah::create([
            'nama_sekolah' => 'Sekolah Test',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'RFID',
        ]);
    }

    public function test_super_admin_can_update_attendance_methods()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin'
        ]);

        $response = $this->actingAs($superAdmin)->put(route('admin.pengaturan-sekolah.update'), [
            'nama_sekolah' => 'Sekolah Update',
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'toleransi_terlambat_menit' => 10,
            'toleransi_alpha_menit' => 45,
            'radius_absensi_meter' => 50,
            'metode_absensi_siswa' => 'Zonasi',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'Zonasi',
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $this->assertDatabaseHas('pengaturan_sekolah', [
            'nama_sekolah' => 'Sekolah Update',
            'metode_absensi_siswa' => 'Zonasi',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'Zonasi',
        ]);
    }

    public function test_admin_cannot_update_attendance_methods()
    {
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($admin)->put(route('admin.pengaturan-sekolah.update'), [
            'nama_sekolah' => 'Sekolah Admin Update',
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'toleransi_terlambat_menit' => 10,
            'toleransi_alpha_menit' => 45,
            'radius_absensi_meter' => 50,
            'metode_absensi_siswa' => 'Zonasi',
            'metode_absensi_guru' => 'Hybrid',
            'metode_absensi_staff' => 'Zonasi',
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        // The methods should remain the defaults because unset happens in controller
        $this->assertDatabaseHas('pengaturan_sekolah', [
            'nama_sekolah' => 'Sekolah Admin Update',
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'RFID',
        ]);
    }

    public function test_validation_fails_for_invalid_methods()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin'
        ]);

        $response = $this->actingAs($superAdmin)->put(route('admin.pengaturan-sekolah.update'), [
            'nama_sekolah' => 'Sekolah Update',
            'jam_masuk' => '08:00',
            'jam_pulang' => '16:00',
            'toleransi_terlambat_menit' => 10,
            'toleransi_alpha_menit' => 45,
            'radius_absensi_meter' => 50,
            'metode_absensi_siswa' => 'Invalid',
            'metode_absensi_guru' => 'Invalid',
            'metode_absensi_staff' => 'Invalid',
        ]);

        $response->assertSessionHasErrors(['metode_absensi_siswa', 'metode_absensi_guru', 'metode_absensi_staff']);
    }
}
