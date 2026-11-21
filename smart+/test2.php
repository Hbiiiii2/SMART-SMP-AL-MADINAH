<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$jadwals = \App\Models\Jadwal::whereHas('mataPelajaran', function($q){
    $q->where('nama', 'Bahasa Arab');
})->with(['guru', 'mataPelajaran'])->get();

foreach ($jadwals as $j) {
    echo "Guru: " . $j->guru->nama_lengkap . " | Kelas: " . $j->kelas . "\n";
}
