<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$siswaId = 2;
$rfidUid = '0003803162';
$tanggal = '2026-08-25';

// 1. Reset data hari ini
App\Models\AbsensiSiswa::where('siswa_id', $siswaId)->where('tanggal', clone \Carbon\Carbon::parse($tanggal))->delete();
App\Models\AbsensiSiswa::where('siswa_id', $siswaId)->where('tanggal', $tanggal)->delete(); // just in case string

echo "=== MEMULAI SIMULASI ABSENSI ===\n\n";

$service = app(App\Services\AttendanceService::class);

// 2. Simulasi Tap In Pagi (07:45)
echo "[1] Simulasi siswa datang ke sekolah dan Tap In kartu pukul 07:45...\n";
\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($tanggal . ' 07:45:00', 'Asia/Jakarta'));
$res = $service->processRfidScan($rfidUid);
echo "    Hasil Tap In: " . $res['message'] . "\n\n";

// 3. Simulasi Auto Mark berjalan di siang hari (14:00)
echo "[2] Simulasi cronjob Auto-Mark berjalan pukul 14:00 untuk mapel-mapel selanjutnya...\n";
\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($tanggal . ' 14:00:00', 'Asia/Jakarta'));
$marked = $service->autoMarkAlphaSiswa($tanggal);
echo "    Cronjob selesai memproses absensi mapel lanjutan.\n\n";

// 4. Simulasi Tap Out Sore (15:15)
echo "[3] Simulasi siswa pulang sekolah dan Tap Out kartu pukul 15:15...\n";
\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($tanggal . ' 15:15:00', 'Asia/Jakarta'));
$res = $service->processRfidScan($rfidUid);
echo "    Hasil Tap Out: " . $res['message'] . "\n\n";

// 5. Cek Hasil Akhir di Database
echo "[4] BUKTI RECORD DATABASE (Hasil Akhir Absensi per Mata Pelajaran):\n";
$records = App\Models\AbsensiSiswa::with('jadwal.mataPelajaran')
    ->where('siswa_id', $siswaId)
    ->where('tanggal', clone \Carbon\Carbon::parse($tanggal))
    ->orderBy('jam_masuk')
    ->get();

if ($records->isEmpty()) {
    $records = App\Models\AbsensiSiswa::with('jadwal.mataPelajaran')
        ->where('siswa_id', $siswaId)
        ->where('tanggal', $tanggal)
        ->orderBy('jam_masuk')
        ->get();
}

foreach ($records as $r) {
    $mapel = $r->jadwal->mataPelajaran->nama ?? 'Unknown';
    $jadwalMasuk = $r->jadwal->jam_mulai;
    $jadwalKeluar = $r->jadwal->jam_selesai;
    
    echo "    - Mapel: {$mapel}\n";
    echo "      Jadwal: {$jadwalMasuk} - {$jadwalKeluar}\n";
    echo "      Status: {$r->status}\n";
    echo "      Jam Masuk di DB: {$r->jam_masuk}\n";
    echo "      Jam Keluar di DB: {$r->jam_keluar}\n";
    echo "      Keterangan: {$r->keterangan}\n\n";
}
echo "=== SELESAI ===\n";
