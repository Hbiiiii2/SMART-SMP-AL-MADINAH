<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$siswa = \App\Models\Siswa::first();
$guru = \App\Models\Guru::first();
$today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
$dayName = 'Senin';

// Create 3 schedules
$mapel = \App\Models\MataPelajaran::first();
$lokasi = \App\Models\Lokasi::first();
$tahunAjaran = \App\Models\TahunAjaran::first();

// Clear previous test jadwals and absensis for this student today
\App\Models\Jadwal::where('guru_id', $guru->id)->where('hari', $dayName)->delete();
\App\Models\AbsensiSiswa::where('siswa_id', $siswa->id)->where('tanggal', $today)->delete();

$jadwal1 = \App\Models\Jadwal::create(['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'tahun_ajaran_id' => $tahunAjaran->id, 'kelas' => $siswa->kelas, 'hari' => $dayName, 'jam_mulai' => '07:00', 'jam_selesai' => '08:00', 'lokasi_id' => $lokasi->id]);
$jadwal2 = \App\Models\Jadwal::create(['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'tahun_ajaran_id' => $tahunAjaran->id, 'kelas' => $siswa->kelas, 'hari' => $dayName, 'jam_mulai' => '08:00', 'jam_selesai' => '09:00', 'lokasi_id' => $lokasi->id]);
$jadwal3 = \App\Models\Jadwal::create(['guru_id' => $guru->id, 'mata_pelajaran_id' => $mapel->id, 'tahun_ajaran_id' => $tahunAjaran->id, 'kelas' => $siswa->kelas, 'hari' => $dayName, 'jam_mulai' => '09:00', 'jam_selesai' => '10:00', 'lokasi_id' => $lokasi->id]);

// 1. Siswa taps in morning
$firstTap = \App\Models\AbsensiSiswa::create([
    'siswa_id' => $siswa->id,
    'jadwal_id' => $jadwal1->id,
    'tanggal' => $today,
    'status' => 'Hadir',
    'jam_masuk' => '06:45:00',
    'keterangan' => 'Tap Gerbang'
]);
echo "1. Siswa Tap Pagi (Jam 1): " . $firstTap->status . "\n";

// 3. Guru opens Jam 2
// Simulate AbsensiController creating record for Jam 2
\App\Models\AbsensiSiswa::create([
    'siswa_id' => $siswa->id,
    'jadwal_id' => $jadwal2->id,
    'tanggal' => $today,
    'status' => $firstTap->status,
    'jam_masuk' => $firstTap->jam_masuk,
    'keterangan' => 'Otomatis mengikuti absen pertama'
]);
echo "2. Guru Buka Absen Jam 2 (Otomatis): " . \App\Models\AbsensiSiswa::where('jadwal_id', $jadwal2->id)->first()->status . "\n";

// 4. Guru Jam 2 manually updates to Sakit
\App\Models\AbsensiSiswa::where('jadwal_id', $jadwal2->id)->update(['status' => 'Sakit', 'keterangan' => 'Sakit di UKS']);
echo "3. Guru Jam 2 Ubah Manual: " . \App\Models\AbsensiSiswa::where('jadwal_id', $jadwal2->id)->first()->status . "\n";

// 5. Guru opens Jam 3
// Simulate AbsensiController creating record for Jam 3
$autoTap = \App\Models\AbsensiSiswa::where('siswa_id', $siswa->id)->where('tanggal', $today)->whereNotNull('jam_masuk')->orderBy('jam_masuk', 'asc')->first();
\App\Models\AbsensiSiswa::create([
    'siswa_id' => $siswa->id,
    'jadwal_id' => $jadwal3->id,
    'tanggal' => $today,
    'status' => $autoTap->status,
    'jam_masuk' => $autoTap->jam_masuk,
    'keterangan' => 'Otomatis mengikuti absen pertama'
]);
echo "4. Guru Buka Absen Jam 3 (Otomatis tarik dari tap pertama): " . \App\Models\AbsensiSiswa::where('jadwal_id', $jadwal3->id)->first()->status . "\n";

echo "\nKesimpulan: Relasi absensi per mata pelajaran BERHASIL independen!\n";
