<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Siswa;
use App\Models\Jadwal;
use App\Models\AbsensiSiswa;

$siswa = Siswa::where('kelas', 'IX - Ibnu Hisyam')->first();
echo "Siswa: {$siswa->nama_lengkap} | Kelas: {$siswa->kelas}\n\n";

$jadwals = Jadwal::where('kelas', $siswa->kelas)
    ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
    ->with(['guru', 'mataPelajaran'])
    ->get();

// === TAB JADWAL: Mata pelajaran unik yang tampil ===
echo "=== TAB JADWAL PELAJARAN: Mata Pelajaran Unik ===\n";
$mapelUnikJadwal = $jadwals->pluck('mataPelajaran.nama')->unique()->sort()->values();
foreach ($mapelUnikJadwal as $i => $nama) {
    echo "  " . ($i+1) . ". {$nama}\n";
}
echo "  Total: {$mapelUnikJadwal->count()} mata pelajaran unik\n\n";

// === TAB ABSENSI: Bangun rekapAbsensi per mata_pelajaran_id ===
$rekapAbsensi = [];
foreach ($jadwals as $jadwal) {
    $mapelId = $jadwal->mata_pelajaran_id;
    $mapelName = $jadwal->mataPelajaran->nama ?? 'Lainnya';
    if (!isset($rekapAbsensi[$mapelId])) {
        $rekapAbsensi[$mapelId] = ['title' => $mapelName, 'Total' => 0];
    }
}

$jadwalIds = $jadwals->pluck('id')->toArray();
$absensis = AbsensiSiswa::where('siswa_id', $siswa->id)
    ->whereIn('jadwal_id', $jadwalIds)
    ->with('jadwal.mataPelajaran')
    ->get();
foreach ($absensis as $ab) {
    $mapelId = $ab->jadwal->mata_pelajaran_id ?? 0;
    if (isset($rekapAbsensi[$mapelId])) {
        $rekapAbsensi[$mapelId]['Total']++;
    }
}

echo "=== TAB ABSENSI SAYA: Mata Pelajaran ===\n";
$mapelUnikAbsensi = collect();
$i = 1;
foreach ($rekapAbsensi as $id => $r) {
    echo "  {$i}. {$r['title']} (total record: {$r['Total']})\n";
    $mapelUnikAbsensi->push($r['title']);
    $i++;
}
echo "  Total: " . count($rekapAbsensi) . " mata pelajaran\n\n";

// === PERBANDINGAN ===
echo "=== PERBANDINGAN ===\n";
$jadwalSorted = $mapelUnikJadwal->sort()->values();
$absensiSorted = $mapelUnikAbsensi->sort()->values();

if ($jadwalSorted->count() === $absensiSorted->count()) {
    echo "  ✅ Jumlah SAMA: {$jadwalSorted->count()} mata pelajaran\n";
} else {
    echo "  ❌ Jumlah BEDA: Jadwal={$jadwalSorted->count()}, Absensi={$absensiSorted->count()}\n";
}

$match = true;
foreach ($jadwalSorted as $idx => $nama) {
    $absensiNama = $absensiSorted[$idx] ?? '(tidak ada)';
    if ($nama === $absensiNama) {
        echo "  ✅ {$nama}\n";
    } else {
        echo "  ❌ Jadwal: {$nama} vs Absensi: {$absensiNama}\n";
        $match = false;
    }
}

echo "\n" . ($match ? "✅ HASIL: SESUAI! Tab Jadwal dan tab Absensi menampilkan mata pelajaran yang sama." : "❌ HASIL: MASIH ADA PERBEDAAN!") . "\n";
