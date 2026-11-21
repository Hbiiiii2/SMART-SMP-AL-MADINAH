<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaKenaikanKelasTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $guru;
    private $tahunAjaranLama;
    private $tahunAjaranBaru;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Users
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        // Create Tahun Ajaran
        $this->tahunAjaranLama = TahunAjaran::create([
            'nama' => '2023/2024',
            'tanggal_mulai' => '2023-07-01',
            'tanggal_selesai' => '2024-06-30',
            'is_aktif' => false,
        ]);

        $this->tahunAjaranBaru = TahunAjaran::create([
            'nama' => '2024/2025',
            'tanggal_mulai' => '2024-07-01',
            'tanggal_selesai' => '2025-06-30',
            'is_aktif' => true,
        ]);
    }

    public function test_admin_can_access_kenaikan_kelas_page()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.siswa.kenaikan-kelas'));
        $response->assertStatus(200);
    }

    public function test_guru_cannot_access_kenaikan_kelas_page()
    {
        $response = $this->actingAs($this->guru)->get(route('admin.siswa.kenaikan-kelas'));
        $response->assertStatus(403);
    }

    public function test_admin_can_process_kenaikan_kelas_massal()
    {
        // Setup students in "Kelas X"
        $siswaX_Naik = Siswa::factory()->create([
            'kelas' => 'X',
            'tahun_ajaran_id' => $this->tahunAjaranLama->id
        ]);
        $siswaX_Tinggal = Siswa::factory()->create([
            'kelas' => 'X',
            'tahun_ajaran_id' => $this->tahunAjaranLama->id
        ]);

        // Setup students in "Kelas XI"
        $siswaXI_Lulus = Siswa::factory()->create([
            'kelas' => 'XI',
            'tahun_ajaran_id' => $this->tahunAjaranLama->id
        ]);

        $payload = [
            'tahun_ajaran_tujuan' => $this->tahunAjaranBaru->id,
            'kelas_tujuan' => [
                'X' => 'XI',
                'XI' => 'Lulus',
            ],
            'siswa_naik' => [
                'X' => [
                    $siswaX_Naik->id, // Only this student goes to XI
                ],
                'XI' => [
                    $siswaXI_Lulus->id,
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.siswa.proses-kenaikan-kelas'), $payload);

        $response->assertRedirect(route('admin.siswa.index'));
        $response->assertSessionHas('success');

        // Assert: Siswa Naik
        $this->assertDatabaseHas('siswa', [
            'id' => $siswaX_Naik->id,
            'kelas' => 'XI',
            'tahun_ajaran_id' => $this->tahunAjaranBaru->id,
        ]);

        // Assert: Siswa Tinggal Kelas (stays in X, but new year)
        $this->assertDatabaseHas('siswa', [
            'id' => $siswaX_Tinggal->id,
            'kelas' => 'X',
            'tahun_ajaran_id' => $this->tahunAjaranBaru->id,
        ]);

        // Assert: Siswa Lulus (goes to Lulus, new year)
        $this->assertDatabaseHas('siswa', [
            'id' => $siswaXI_Lulus->id,
            'kelas' => 'Lulus',
            'tahun_ajaran_id' => $this->tahunAjaranBaru->id,
        ]);
        
        // Assert: Akun Siswa Lulus dinonaktifkan
        $this->assertDatabaseHas('users', [
            'id' => $siswaXI_Lulus->user_id,
            'is_active' => 0, // false in DB
        ]);
    }
}
