<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$gurus = \App\Models\Guru::get();
foreach ($gurus as $guru) {
    if (stripos($guru->nama_lengkap, 'Lailyla') !== false || stripos($guru->nama_lengkap, 'Hanum') !== false || stripos($guru->nama_lengkap, 'Laiyla') !== false) {
        echo "Guru: " . $guru->nama_lengkap . "\n";
        $jadwals = \App\Models\Jadwal::where('guru_id', $guru->id)->with('mataPelajaran')->get();
        foreach ($jadwals as $j) {
            echo "- Kelas: " . $j->kelas . ", Mapel: " . $j->mataPelajaran->nama . "\n";
        }
    }
}
