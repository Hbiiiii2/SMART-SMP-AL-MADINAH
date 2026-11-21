<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rfidUid = '0003803162';
$service = app(App\Services\AttendanceService::class);

echo "=== MEMULAI TEST HARI LIBUR & WEEKEND ===\n\n";

// --- TEST 1: HARI SABTU (WEEKEND) ---
$sabtuDate = '2026-08-29'; // Tanggal 29 Agustus 2026 adalah hari Sabtu
echo "[1] TEST HARI SABTU ({$sabtuDate})\n";
\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($sabtuDate . ' 08:00:00', 'Asia/Jakarta'));

// Test Tap RFID
$resSabtu = $service->processRfidScan($rfidUid);
echo "    - Hasil Tap In Siswa : " . $resSabtu['message'] . "\n";

// Test Command Auto Mark Alpha
Illuminate\Support\Facades\Artisan::call('attendance:mark-alpha');
$outputSabtu = Illuminate\Support\Facades\Artisan::output();
echo "    - Hasil Cronjob Auto-Mark: " . trim($outputSabtu) . "\n\n";

// --- TEST 2: HARI LIBUR NASIONAL ---
$liburDate = '2026-08-26'; // Simulasi hari Rabu
echo "[2] TEST HARI LIBUR NASIONAL ({$liburDate})\n";

// Bikin record hari libur buatan di DB buat ngetes
$holiday = App\Models\HariLibur::create([
    'tanggal' => \Carbon\Carbon::parse($liburDate)->startOfDay(),
    'nama' => 'Hari Libur Simulasi Tes',
    'is_recurring' => false,
]);

\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($liburDate . ' 08:00:00', 'Asia/Jakarta'));

// Test Tap RFID
$resLibur = $service->processRfidScan($rfidUid);
echo "    - Hasil Tap In Siswa : " . $resLibur['message'] . "\n";

// Test Command Auto Mark Alpha
Illuminate\Support\Facades\Artisan::call('attendance:mark-alpha');
$outputLibur = Illuminate\Support\Facades\Artisan::output();
echo "    - Hasil Cronjob Auto-Mark: " . trim($outputLibur) . "\n\n";

// Hapus libur buatan biar bersih
$holiday->delete();

echo "=== SELESAI ===\n";
