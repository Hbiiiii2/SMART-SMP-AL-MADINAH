<?php

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\LogAktivitas;
use App\Models\PengaturanSekolah;
use App\Models\RfidCard;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('inherits first tap status when auto-marking alfa', function () {
    // Setup data
    $siswa = Siswa::factory()->create(['kelas' => 'X-A']);
    $today = Carbon::now('Asia/Jakarta')->toDateString();

    $jadwal1 = Jadwal::factory()->create([
        'kelas' => $siswa->kelas,
        'hari' => 'Senin',
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'jam_mulai' => '07:00',
        'jam_selesai' => '08:30',
    ]);

    $jadwal2 = Jadwal::factory()->create([
        'kelas' => $siswa->kelas,
        'hari' => 'Senin',
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'jam_mulai' => '08:30',
        'jam_selesai' => '10:00',
    ]);

    // Student taps in for Jadwal 1 (status: Hadir)
    \Illuminate\Support\Facades\DB::table('absensi_siswa')->insert([
        'siswa_id' => $siswa->id,
        'jadwal_id' => $jadwal1->id,
        'tanggal' => $today,
        'status' => 'Hadir',
        'jam_masuk' => '07:00:00',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    // Auto mark alpha runs for Jadwal 2
    $service = new AttendanceService();
    $service->autoMarkAlphaSiswaForJadwal($jadwal2, $today);

    $absensiJadwal2 = AbsensiSiswa::where('siswa_id', $siswa->id)
        ->where('jadwal_id', $jadwal2->id)
        ->first();

    expect($absensiJadwal2)->not->toBeNull();
    expect($absensiJadwal2->status)->toBe('Hadir');
    expect($absensiJadwal2->jam_masuk->format('H:i:s'))->toBe('07:00:00');
});

it('defaults to alfa if no first tap exists', function () {
    // Setup data
    $siswa = Siswa::factory()->create(['kelas' => 'X-A']);
    $today = Carbon::now('Asia/Jakarta')->toDateString();

    $jadwal1 = Jadwal::factory()->create([
        'kelas' => $siswa->kelas,
        'hari' => 'Senin',
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
    ]);

    // Auto mark alpha runs for Jadwal 1
    $service = new AttendanceService();
    $service->autoMarkAlphaSiswaForJadwal($jadwal1, $today);

    // Verify Jadwal 1 is 'Alfa'
    $absensiJadwal1 = AbsensiSiswa::where('siswa_id', $siswa->id)
        ->where('jadwal_id', $jadwal1->id)
        ->first();

    expect($absensiJadwal1)->not->toBeNull();
    expect($absensiJadwal1->status)->toBe('Alfa');
    expect($absensiJadwal1->jam_masuk)->toBeNull();
});

it('prioritizes physical tap over auto alfa when carrying over status', function () {
    $siswa = Siswa::factory()->create(['kelas' => 'X-A']);
    $today = Carbon::now('Asia/Jakarta')->toDateString();

    $jadwal1 = Jadwal::factory()->create([
        'kelas' => $siswa->kelas,
        'hari' => 'Senin',
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'jam_mulai' => '07:00',
        'jam_selesai' => '08:30',
    ]);
    $jadwal2 = Jadwal::factory()->create([
        'kelas' => $siswa->kelas,
        'hari' => 'Senin',
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'jam_mulai' => '08:30',
        'jam_selesai' => '10:00',
    ]);
    $jadwal3 = Jadwal::factory()->create([
        'kelas' => $siswa->kelas,
        'hari' => 'Senin',
        'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
        'jam_mulai' => '10:00',
        'jam_selesai' => '11:30',
    ]);

    // Teacher opens Jadwal 1 at 07:00, student gets Alfa
    \Illuminate\Support\Facades\DB::table('absensi_siswa')->insert([
        'siswa_id' => $siswa->id,
        'jadwal_id' => $jadwal1->id,
        'tanggal' => $today,
        'status' => 'Alfa',
        'created_at' => Carbon::now()->subHours(2),
        'updated_at' => Carbon::now()->subHours(2),
    ]);

    // Student arrives at 09:00 and taps for Jadwal 2 (status: Terlambat)
    \Illuminate\Support\Facades\DB::table('absensi_siswa')->insert([
        'siswa_id' => $siswa->id,
        'jadwal_id' => $jadwal2->id,
        'tanggal' => $today,
        'status' => 'Terlambat',
        'jam_masuk' => '09:00:00',
        'created_at' => Carbon::now()->subHour(),
        'updated_at' => Carbon::now()->subHour(),
    ]);

    // Auto mark alpha runs for Jadwal 3
    $service = new AttendanceService();
    $service->autoMarkAlphaSiswaForJadwal($jadwal3, $today);

    // Verify Jadwal 3 inherited 'Terlambat' and jam_masuk from the physical tap, NOT 'Alfa' from Jadwal 1
    $absensiJadwal3 = AbsensiSiswa::where('siswa_id', $siswa->id)
        ->where('jadwal_id', $jadwal3->id)
        ->first();

    expect($absensiJadwal3)->not->toBeNull();
    expect($absensiJadwal3->status)->toBe('Terlambat');
    expect($absensiJadwal3->jam_masuk->format('H:i:s'))->toBe('09:00:00');
});

it('records actual tap-in time and detects lateness based on school jam_masuk instead of jadwal jam_mulai', function () {
    PengaturanSekolah::create([
        'nama_sekolah' => 'SMP Test',
        'jam_masuk' => '07:15:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30,
        'radius_absensi_meter' => 100,
        'metode_absensi_siswa' => 'RFID',
        'metode_absensi_guru' => 'RFID',
        'metode_absensi_staff' => 'RFID',
    ]);

    $ta = TahunAjaran::factory()->aktif()->create();

    $jadwal1 = Jadwal::factory()->create([
        'kelas' => 'VIII-A',
        'hari' => 'Senin',
        'jam_mulai' => '07:50:00',
        'jam_selesai' => '09:10:00',
        'tahun_ajaran_id' => $ta->id,
    ]);
    $jadwal2 = Jadwal::factory()->create([
        'kelas' => 'VIII-A',
        'hari' => 'Senin',
        'jam_mulai' => '09:40:00',
        'jam_selesai' => '11:00:00',
        'tahun_ajaran_id' => $ta->id,
    ]);

    $siswaOnTime = Siswa::factory()->create([
        'kelas' => 'VIII-A',
        'tahun_ajaran_id' => $ta->id,
    ]);
    RfidCard::create(['siswa_id' => $siswaOnTime->id, 'uid' => 'UID-ONTIME', 'is_active' => true]);

    $siswaLate = Siswa::factory()->create([
        'kelas' => 'VIII-A',
        'tahun_ajaran_id' => $ta->id,
    ]);
    RfidCard::create(['siswa_id' => $siswaLate->id, 'uid' => 'UID-LATE', 'is_active' => true]);

    $service = new AttendanceService();

    // Tap in at 07:20:00 (within 07:15 + 15m = 07:30) -> Hadir, actual jam_masuk 07:20:00
    Carbon::setTestNow(Carbon::parse('2026-11-02 07:20:00', 'Asia/Jakarta'));
    $resOnTime = $service->processRfidScan('UID-ONTIME');

    expect($resOnTime['success'])->toBeTrue();
    expect($resOnTime['status'])->toBe('Hadir');
    expect($resOnTime['jam'])->toBe('07:20:00');

    $recOnTime1 = AbsensiSiswa::where('siswa_id', $siswaOnTime->id)->where('jadwal_id', $jadwal1->id)->first();
    $recOnTime2 = AbsensiSiswa::where('siswa_id', $siswaOnTime->id)->where('jadwal_id', $jadwal2->id)->first();
    expect($recOnTime1->status)->toBe('Hadir');
    expect($recOnTime1->jam_masuk->format('H:i:s'))->toBe('07:20:00');
    expect($recOnTime2->status)->toBe('Hadir');
    expect($recOnTime2->jam_masuk->format('H:i:s'))->toBe('07:20:00');

    // Tap in at 07:45:00 (past 07:30 even though jadwal1 starts at 07:50) -> Terlambat, actual jam_masuk 07:45:00
    Carbon::setTestNow(Carbon::parse('2026-11-02 07:45:00', 'Asia/Jakarta'));
    $resLate = $service->processRfidScan('UID-LATE');

    expect($resLate['success'])->toBeTrue();
    expect($resLate['status'])->toBe('Terlambat');
    expect($resLate['jam'])->toBe('07:45:00');

    $recLate1 = AbsensiSiswa::where('siswa_id', $siswaLate->id)->where('jadwal_id', $jadwal1->id)->first();
    $recLate2 = AbsensiSiswa::where('siswa_id', $siswaLate->id)->where('jadwal_id', $jadwal2->id)->first();
    expect($recLate1->status)->toBe('Terlambat');
    expect($recLate1->jam_masuk->format('H:i:s'))->toBe('07:45:00');
    expect($recLate2->status)->toBe('Terlambat');
    expect($recLate2->jam_masuk->format('H:i:s'))->toBe('07:45:00');

    // Tap out for on-time student at 15:00:00 -> updates jam_keluar across all open rows today
    Carbon::setTestNow(Carbon::parse('2026-11-02 15:00:00', 'Asia/Jakarta'));
    $resOut = $service->processRfidScan('UID-ONTIME');

    Carbon::setTestNow();

    expect($resOut['success'])->toBeTrue();
    expect($resOut['type'])->toBe('keluar');
    expect($resOut['jam'])->toBe('15:00:00');
    expect($recOnTime1->fresh()->jam_keluar->format('H:i:s'))->toBe('15:00:00');
    expect($recOnTime2->fresh()->jam_keluar->format('H:i:s'))->toBe('15:00:00');
});

it('updates existing Alfa records when a late student taps in before school pulang cutoff', function () {
    PengaturanSekolah::create([
        'nama_sekolah' => 'SMP Test',
        'jam_masuk' => '07:15:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30,
        'radius_absensi_meter' => 100,
        'metode_absensi_siswa' => 'RFID',
        'metode_absensi_guru' => 'RFID',
        'metode_absensi_staff' => 'RFID',
    ]);

    $ta = TahunAjaran::factory()->aktif()->create();

    $jadwal1 = Jadwal::factory()->create([
        'kelas' => 'VIII-B',
        'hari' => 'Senin',
        'jam_mulai' => '07:50:00',
        'jam_selesai' => '09:10:00',
        'tahun_ajaran_id' => $ta->id,
    ]);
    $jadwal2 = Jadwal::factory()->create([
        'kelas' => 'VIII-B',
        'hari' => 'Senin',
        'jam_mulai' => '09:40:00',
        'jam_selesai' => '11:00:00',
        'tahun_ajaran_id' => $ta->id,
    ]);

    $siswa = Siswa::factory()->create([
        'kelas' => 'VIII-B',
        'tahun_ajaran_id' => $ta->id,
    ]);
    RfidCard::create(['siswa_id' => $siswa->id, 'uid' => 'UID-VERY-LATE', 'is_active' => true]);

    $today = '2026-11-02';
    AbsensiSiswa::create([
        'siswa_id' => $siswa->id,
        'jadwal_id' => $jadwal1->id,
        'tanggal' => $today,
        'status' => 'Alfa',
        'jam_masuk' => null,
    ]);
    AbsensiSiswa::create([
        'siswa_id' => $siswa->id,
        'jadwal_id' => $jadwal2->id,
        'tanggal' => $today,
        'status' => 'Alfa',
        'jam_masuk' => null,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-02 08:40:00', 'Asia/Jakarta'));
    $res = (new AttendanceService())->processRfidScan('UID-VERY-LATE');
    Carbon::setTestNow();

    expect($res['success'])->toBeTrue();
    expect($res['status'])->toBe('Terlambat');
    expect($res['jam'])->toBe('08:40:00');

    foreach ([$jadwal1->id, $jadwal2->id] as $jid) {
        $rec = AbsensiSiswa::where('siswa_id', $siswa->id)->where('jadwal_id', $jid)->first();
        expect($rec->status)->toBe('Terlambat');
        expect($rec->jam_masuk->format('H:i:s'))->toBe('08:40:00');
    }
});

it('handles /api/rfid/scan endpoint and deduplicates /kiosk/recent with actual tap time', function () {
    PengaturanSekolah::create([
        'nama_sekolah' => 'SMP Test',
        'jam_masuk' => '07:15:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30,
        'radius_absensi_meter' => 100,
        'metode_absensi_siswa' => 'RFID',
        'metode_absensi_guru' => 'RFID',
        'metode_absensi_staff' => 'RFID',
    ]);

    $ta = TahunAjaran::factory()->aktif()->create();

    Jadwal::factory()->create([
        'kelas' => 'VIII-C',
        'hari' => 'Senin',
        'jam_mulai' => '07:50:00',
        'jam_selesai' => '09:10:00',
        'tahun_ajaran_id' => $ta->id,
    ]);
    Jadwal::factory()->create([
        'kelas' => 'VIII-C',
        'hari' => 'Senin',
        'jam_mulai' => '09:40:00',
        'jam_selesai' => '11:00:00',
        'tahun_ajaran_id' => $ta->id,
    ]);

    $siswa = Siswa::factory()->create([
        'kelas' => 'VIII-C',
        'tahun_ajaran_id' => $ta->id,
    ]);
    RfidCard::create([
        'siswa_id' => $siswa->id,
        'uid' => 'UID-HTTP-LATE',
        'is_active' => true,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-02 07:48:54', 'Asia/Jakarta'));

    $scanResponse = $this->postJson('/api/rfid/scan', ['uid' => 'UID-HTTP-LATE']);
    $scanResponse->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 'Terlambat',
            'jam' => '07:48:54',
            'role' => 'siswa',
        ]);

    expect(LogAktivitas::where('aksi', 'rfid_scan')->count())->toBe(1);

    $recentResponse = $this->getJson('/kiosk/recent');
    $recentResponse->assertOk();

    $matchingEntries = collect($recentResponse->json())
        ->where('nisn', $siswa->nisn)
        ->values();

    expect($matchingEntries)->toHaveCount(1);
    expect($matchingEntries[0]['status'])->toBe('Terlambat');
    expect($matchingEntries[0]['jam_masuk'])->toBe('07:48:54');
});

it('preserves actual jam_masuk and renders H:i in guru absensi, admin dashboard, and admin laporan views', function () {
    PengaturanSekolah::create([
        'nama_sekolah' => 'SMP Test',
        'jam_masuk' => '07:15:00',
        'jam_pulang' => '15:00:00',
        'toleransi_terlambat_menit' => 15,
        'toleransi_alpha_menit' => 30,
        'radius_absensi_meter' => 100,
        'metode_absensi_siswa' => 'RFID',
        'metode_absensi_guru' => 'RFID',
        'metode_absensi_staff' => 'RFID',
    ]);

    $ta = TahunAjaran::factory()->aktif()->create();

    $guruUser = User::factory()->guru()->create(['is_active' => true]);
    $guru = Guru::factory()->create(['user_id' => $guruUser->id]);

    $siswa = Siswa::factory()->create([
        'kelas' => 'VIII-D',
        'tahun_ajaran_id' => $ta->id,
    ]);

    $jadwal1 = Jadwal::factory()->create([
        'guru_id' => $guru->id,
        'kelas' => 'VIII-D',
        'hari' => 'Senin',
        'jam_mulai' => '07:50:00',
        'jam_selesai' => '09:10:00',
        'tahun_ajaran_id' => $ta->id,
    ]);
    $jadwal2 = Jadwal::factory()->create([
        'guru_id' => $guru->id,
        'kelas' => 'VIII-D',
        'hari' => 'Senin',
        'jam_mulai' => '09:40:00',
        'jam_selesai' => '11:00:00',
        'tahun_ajaran_id' => $ta->id,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-02 09:45:00', 'Asia/Jakarta'));

    AbsensiSiswa::create([
        'siswa_id' => $siswa->id,
        'jadwal_id' => $jadwal1->id,
        'tanggal' => '2026-11-02',
        'status' => 'Terlambat',
        'jam_masuk' => '07:48:54',
        'jam_keluar' => '15:05:12',
    ]);

    $guruResponse = $this->actingAs($guruUser)->get('/guru/absensi?jadwal_id=' . $jadwal2->id);
    $guruResponse->assertOk()
        ->assertSee($siswa->nama_lengkap)
        ->assertSee('Terlambat')
        ->assertSee('07:48')
        ->assertSee('15:05');

    $autoCreated = AbsensiSiswa::where('siswa_id', $siswa->id)
        ->where('jadwal_id', $jadwal2->id)
        ->first();
    expect($autoCreated)->not->toBeNull();
    expect($autoCreated->status)->toBe('Terlambat');
    expect($autoCreated->jam_masuk->format('H:i:s'))->toBe('07:48:54');

    $adminUser = User::factory()->create([
        'role' => 'admin_sekolah',
        'is_active' => true,
    ]);

    $dashboardResponse = $this->actingAs($adminUser)->get('/admin/dashboard');
    $dashboardResponse->assertOk()
        ->assertSee($siswa->nama_lengkap)
        ->assertSee('Terlambat')
        ->assertSee('07:48');

    $laporanResponse = $this->actingAs($adminUser)->get('/admin/laporan');
    $laporanResponse->assertOk()
        ->assertSee($siswa->nama_lengkap)
        ->assertSee('Terlambat')
        ->assertSee('07:48');
});
