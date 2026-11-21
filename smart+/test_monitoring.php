<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::where('role', 'admin_sekolah')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
    } else {
        echo "No admin_sekolah user found.\n";
    }

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

    $siswas->getCollection()->transform(function ($s) {
        $s->status_tampil = $s->status_hadir ?? 'Belum Absen';
        return $s;
    });

    $kelasList = \App\Models\Siswa::distinct()->pluck('kelas')->sort()->values();
    
    $request = \Illuminate\Http\Request::create('/kepala-sekolah/monitoring-siswa', 'GET');
    $app->instance('request', $request);

    $errors = new \Illuminate\Support\ViewErrorBag;
    \Illuminate\Support\Facades\View::share('errors', $errors);

    $view = view('pages.kepala-sekolah.monitoring-siswa', [
        'title' => 'Monitoring Siswa',
        'siswas' => $siswas,
        'kelasList' => $kelasList,
        'today' => $today,
    ]);
    
    echo "Rendering view for admin_sekolah...\n";
    $rendered = $view->render();
    echo "Success! Length: " . strlen($rendered) . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
