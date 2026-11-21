<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TAHUN AJARAN ===\n";
foreach (\App\Models\TahunAjaran::all() as $ta) {
    echo "{$ta->id} | {$ta->nama} | Aktif: {$ta->is_aktif}\n";
}

echo "\n=== SISWA KELAS ===\n";
foreach (\App\Models\Siswa::select('kelas')->distinct()->get() as $s) {
    echo "{$s->kelas}\n";
}
