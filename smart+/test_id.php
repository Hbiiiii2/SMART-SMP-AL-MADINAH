<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

$firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
    ->where('tanggal', $today)
    ->groupBy('siswa_id');

$siswas = \App\Models\Siswa::with('user')
    ->leftJoinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
        $join->on('siswa.id', '=', 'first_absensi.siswa_id');
    })
    ->leftJoin('absensi_siswa', function ($join) {
        $join->on('first_absensi.first_id', '=', 'absensi_siswa.id');
    })
    ->select(
        'siswa.*',
        'absensi_siswa.status as status_hadir',
        'absensi_siswa.jam_masuk',
        'absensi_siswa.jadwal_id'
    )
    ->orderBy('siswa.kelas')
    ->orderBy('siswa.nama_lengkap')
    ->paginate(20)
    ->withQueryString();

$firstSiswa = $siswas->first();
if ($firstSiswa) {
    echo "ID: " . var_export($firstSiswa->id, true) . "\n";
    echo "Nama: " . var_export($firstSiswa->nama_lengkap, true) . "\n";
    echo "Keys: " . implode(', ', array_keys($firstSiswa->getAttributes())) . "\n";
} else {
    echo "No siswa found.\n";
}
