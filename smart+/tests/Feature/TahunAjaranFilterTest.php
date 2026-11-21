<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TahunAjaranFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat 2 Tahun Ajaran
        $this->taLama = TahunAjaran::create([
            'nama' => '2023/2024',
            'is_aktif' => false,
            'tanggal_mulai' => '2023-07-01',
            'tanggal_selesai' => '2024-06-30'
        ]);
        
        $this->taAktif = TahunAjaran::create([
            'nama' => '2024/2025',
            'is_aktif' => true,
            'tanggal_mulai' => '2024-07-01',
            'tanggal_selesai' => '2025-06-30'
        ]);

        // Buat Admin
        $this->adminUser = User::factory()->create(['role' => 'super_admin']);

        // Buat Guru & User Guru
        $this->guruUser = User::factory()->create(['role' => 'guru']);
        $this->guru = Guru::factory()->create(['user_id' => $this->guruUser->id]);

        // Buat Mata Pelajaran
        $this->mapel = MataPelajaran::factory()->create();

        // Buat Siswa
        $this->siswaUser = User::factory()->create(['role' => 'siswa']);
        $this->siswa = Siswa::factory()->create([
            'user_id' => $this->siswaUser->id,
            'kelas' => 'X',
            'tahun_ajaran_id' => $this->taAktif->id
        ]);

        // Buat Jadwal Tahun Lama
        $this->jadwalLama = Jadwal::factory()->create([
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->taLama->id,
            'kelas' => 'X'
        ]);

        // Buat Jadwal Tahun Aktif
        $this->jadwalAktif = Jadwal::factory()->create([
            'guru_id' => $this->guru->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'tahun_ajaran_id' => $this->taAktif->id,
            'kelas' => 'X'
        ]);

        // Buat Absensi Tahun Lama
        AbsensiSiswa::create([
            'siswa_id' => $this->siswa->id,
            'jadwal_id' => $this->jadwalLama->id,
            'tanggal' => '2023-08-01',
            'status' => 'Hadir'
        ]);

        // Buat Absensi Tahun Aktif
        AbsensiSiswa::create([
            'siswa_id' => $this->siswa->id,
            'jadwal_id' => $this->jadwalAktif->id,
            'tanggal' => Carbon::now('Asia/Jakarta')->toDateString(),
            'status' => 'Hadir'
        ]);
    }

    public function test_admin_jadwal_default_shows_active_year()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/jadwal');
        $response->assertStatus(200);
        
        // By default, it should only show active year's jadwal in the view data
        $jadwals = $response->original->getData()['jadwals'];
        $this->assertEquals(1, $jadwals->count());
        $this->assertEquals($this->jadwalAktif->id, $jadwals->first()->id);
    }

    public function test_admin_jadwal_can_filter_by_old_year()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/jadwal?tahun_ajaran_id=' . $this->taLama->id);
        $response->assertStatus(200);
        
        $jadwals = $response->original->getData()['jadwals'];
        $this->assertEquals(1, $jadwals->count());
        $this->assertEquals($this->jadwalLama->id, $jadwals->first()->id);
    }

    public function test_guru_laporan_default_shows_active_year_jadwal()
    {
        $response = $this->actingAs($this->guruUser)->get('/guru/laporan');
        $response->assertStatus(200);

        // Hanya boleh mengumpulkan data dari jadwal tahun aktif
        $rekapSiswa = $response->original->getData()['rekapSiswa'];
        $this->assertNotEmpty($rekapSiswa);
        
        // Karena ada absensi di jadwal aktif
        $this->assertTrue($rekapSiswa->contains('jadwal_id', $this->jadwalAktif->id));
        $this->assertFalse($rekapSiswa->contains('jadwal_id', $this->jadwalLama->id));
    }
}
