<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\MataPelajaranController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\LokasiController;
use App\Http\Controllers\Admin\RfidController;
use App\Http\Controllers\Admin\PengaturanSekolahController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\TanggalAkademikController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\AbsensiController as GuruAbsensiController;
use App\Http\Controllers\Guru\JadwalController as GuruJadwalController;
use App\Http\Controllers\Guru\LaporanController as GuruLaporanController;
use App\Http\Controllers\Guru\SelfAbsensiController as GuruSelfAbsensiController;
use App\Http\Controllers\KepalaSekolah\DashboardController as KepalaDashboardController;
use App\Http\Controllers\KepalaSekolah\RekapController as KepalaRekapController;
use App\Http\Controllers\KepalaSekolah\SelfAbsensiController as KepalaSelfAbsensiController;
use App\Http\Controllers\Direktur\DashboardController as DirekturDashboardController;
use App\Http\Controllers\StaffAkademik\DashboardController as StaffDashboardController;
use App\Http\Controllers\Api\RfidApiController;
use App\Http\Controllers\Api\AttendanceGpsController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\CutiController;

Route::get('/', function (Request $request) {
    // Jika diakses melalui subdomain elearning, langsung arahkan ke halaman login
    if (str_contains($request->getHost(), 'elearning')) {
        return redirect()->route('login');
    }

    return view('landing');
})->name('landing');

Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/kiosk/stats', [KioskController::class, 'stats'])->name('kiosk.stats');
Route::get('/kiosk/recent', [KioskController::class, 'recentActivities'])->name('kiosk.recent');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender.index');
});

// --- E-Rapor Akademik Terintegrasi ---
Route::prefix('erapor')->name('erapor.')->middleware(['auth', 'role:super_admin,admin_sekolah,guru,kepala_sekolah'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Erapor\EraporDashboardController::class, 'index'])
        ->middleware('role:super_admin,admin_sekolah,kepala_sekolah')
        ->name('dashboard');

    // Buku Nilai Mapel
    Route::prefix('buku-nilai')->name('buku-nilai.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Erapor\BukuNilaiController::class, 'index'])->name('index');
        Route::post('/item', [\App\Http\Controllers\Erapor\BukuNilaiController::class, 'storeItem'])->name('item.store');
        Route::delete('/item/{id}', [\App\Http\Controllers\Erapor\BukuNilaiController::class, 'destroyItem'])->name('item.destroy');
        Route::post('/update-score', [\App\Http\Controllers\Erapor\BukuNilaiController::class, 'updateScore'])->name('update-score');
        Route::post('/sync-lms', [\App\Http\Controllers\Erapor\BukuNilaiController::class, 'syncFromLms'])->name('sync-lms');
        Route::post('/sync-cbt', [\App\Http\Controllers\Erapor\BukuNilaiController::class, 'syncFromCbt'])->name('sync-cbt');
    });

    // Integrasi Nilai CBT & Fallback Excel (Hanya Admin Sekolah, Kepala Sekolah, Super Admin)
    Route::prefix('cbt')->name('cbt.')->middleware('role:super_admin,admin_sekolah,kepala_sekolah')->group(function () {
        Route::get('/', [\App\Http\Controllers\Erapor\CbtSyncController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/sync-api', [\App\Http\Controllers\Erapor\CbtSyncController::class, 'syncApi'])->name('sync-api');
        Route::post('/import-excel', [\App\Http\Controllers\Erapor\CbtSyncController::class, 'importExcel'])->name('import-excel');
        Route::get('/batch/{id}', [\App\Http\Controllers\Erapor\CbtSyncController::class, 'batchDetail'])->name('batch-detail');
    });

    // Rekapitulasi STS, Peringkat & Ekspor
    Route::prefix('recap')->name('recap.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Erapor\RecapStsController::class, 'index'])->name('index');
        Route::post('/finalize/{kelas}', [\App\Http\Controllers\Erapor\RecapStsController::class, 'finalizeClass'])
            ->middleware('role:super_admin,admin_sekolah,kepala_sekolah')
            ->name('finalize');
        Route::get('/export/{kelas}', [\App\Http\Controllers\Erapor\RecapStsController::class, 'exportExcel'])->name('export');
    });

    // Buku Induk & Arsip Siswa / Alumni (Hanya Admin Sekolah, Kepala Sekolah, Super Admin)
    Route::prefix('buku-induk')->name('buku-induk.')->middleware('role:super_admin,admin_sekolah,kepala_sekolah')->group(function () {
        Route::get('/', [\App\Http\Controllers\Erapor\BukuIndukController::class, 'index'])->name('index');
        Route::get('/{siswa}', [\App\Http\Controllers\Erapor\BukuIndukController::class, 'show'])->name('show');
        Route::get('/{siswa}/print', [\App\Http\Controllers\Erapor\BukuIndukController::class, 'print'])->name('print');
        Route::get('/{siswa}/export', [\App\Http\Controllers\Erapor\BukuIndukController::class, 'exportExcel'])->name('export');
    });

    // Cetak Rapor Siswa & Kelas (STS & Semester)
    Route::prefix('cetak-rapor')->name('cetak.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'index'])->name('index');
        Route::get('/catatan-wali-kelas', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'catatanWaliKelas'])->name('catatan-wali');
        Route::post('/catatan-wali-kelas', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'saveCatatanWaliKelas'])->name('save-catatan-wali');
        Route::post('/catatan-wali-kelas/generate-ai', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'generateAiCatatan'])->name('ai-catatan-wali');
        Route::middleware('role:super_admin,admin_sekolah,kepala_sekolah')->group(function () {
            Route::get('/pengaturan', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'pengaturan'])->name('pengaturan');
            Route::post('/pengaturan', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'updatePengaturan'])->name('update-pengaturan');
            Route::post('/pengaturan/homerooms', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'updateHomerooms'])->name('update-homerooms');
        });
        Route::get('/siswa/{siswa}', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'printSiswa'])->name('siswa');
        Route::get('/kelas/{kelas}', [\App\Http\Controllers\Erapor\CetakRaporController::class, 'printKelas'])->name('kelas');
    });

    // Capaian Pembelajaran & Deskripsi Kompetensi Kurikulum Merdeka
    Route::prefix('capaian-kompetensi')->name('capaian.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Erapor\CapaianKompetensiController::class, 'index'])->name('index');
        Route::post('/save', [\App\Http\Controllers\Erapor\CapaianKompetensiController::class, 'save'])->name('save');
        Route::post('/auto-generate', [\App\Http\Controllers\Erapor\CapaianKompetensiController::class, 'autoGenerate'])->name('auto-generate');
    });
});


Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin,admin_sekolah'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ubah-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/ubah-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::resource('guru', GuruController::class);
    Route::get('siswa/kenaikan-kelas', [SiswaController::class, 'kenaikanKelas'])->name('siswa.kenaikan-kelas');
    Route::post('siswa/kenaikan-kelas', [SiswaController::class, 'prosesKenaikanKelas'])->name('siswa.proses-kenaikan-kelas');
    Route::resource('siswa', SiswaController::class);
    Route::resource('mata-pelajaran', MataPelajaranController::class);
    Route::resource('jadwal', JadwalController::class);
    Route::resource('tahun-ajaran', TahunAjaranController::class);
    Route::post('tahun-ajaran/{tahun_ajaran}/set-aktif', [TahunAjaranController::class, 'setAktif'])->name('tahun-ajaran.set-aktif');
    Route::resource('lokasi', LokasiController::class);
    Route::resource('rfid', RfidController::class);
    Route::get('/pengaturan-sekolah', [PengaturanSekolahController::class, 'edit'])->name('pengaturan-sekolah.edit');
    Route::put('/pengaturan-sekolah', [PengaturanSekolahController::class, 'update'])->name('pengaturan-sekolah.update');
    Route::resource('update-history', \App\Http\Controllers\UpdateHistoryController::class);

    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BackupController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\BackupController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\BackupController::class, 'import'])->name('import');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/export-siswa', [LaporanController::class, 'exportSiswa'])->name('export-siswa');
        Route::get('/export-guru', [LaporanController::class, 'exportGuru'])->name('export-guru');
    });

    Route::resource('hari-libur', HariLiburController::class);
    Route::resource('tanggal-akademik', TanggalAkademikController::class);

    Route::prefix('log-aktivitas')->name('log-aktivitas.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [LogAktivitasController::class, 'index'])->name('index');
        Route::get('/laravel-logs', [LogAktivitasController::class, 'laravelLogs'])->name('laravel-logs');
        Route::get('/{log_aktivitas}', [LogAktivitasController::class, 'show'])->name('show');
    });
});

Route::prefix('guru')->name('guru.')->middleware(['auth', 'role:guru'])->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::get('/jadwal', [GuruJadwalController::class, 'index'])->name('jadwal.index');
    Route::get('/absensi', [GuruAbsensiController::class, 'index'])->name('absensi.index');
    Route::put('/absensi/{absensiId}/status', [GuruAbsensiController::class, 'updateStatus'])->name('absensi.update-status');
    Route::get('/absensi-saya', [GuruSelfAbsensiController::class, 'index'])->name('absensi-saya.index');
    Route::get('/absensi-saya/status', [GuruSelfAbsensiController::class, 'status'])->name('absensi-saya.status');
    Route::post('/absensi-saya/checkin', [GuruSelfAbsensiController::class, 'checkin'])->name('absensi-saya.checkin');
    Route::get('/laporan', [GuruLaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/siswa/{siswa}/jadwal/{jadwal}', [GuruLaporanController::class, 'detail'])->name('laporan.detail');
    Route::get('/laporan/siswa/{siswa}/jadwal/{jadwal}/export', [GuruLaporanController::class, 'exportDetail'])->name('laporan.detail.export');
    Route::get('/laporan/export', [GuruLaporanController::class, 'export'])->name('laporan.export');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ubah-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/ubah-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/cuti', [CutiController::class, 'index'])->name('cuti.index');
    Route::get('/cuti/create', [CutiController::class, 'create'])->name('cuti.create');
    Route::post('/cuti', [CutiController::class, 'store'])->name('cuti.store');

    Route::get('/get-mata-pelajaran', [\App\Http\Controllers\Guru\JadwalController::class, 'getMataPelajaranByKelas'])->name('get-mata-pelajaran');

    Route::resource('materi', \App\Http\Controllers\Guru\MateriController::class);
    Route::get('/tugas/{tugas}/export', [\App\Http\Controllers\Guru\TugasController::class, 'export'])->name('tugas.export');
    Route::resource('tugas', \App\Http\Controllers\Guru\TugasController::class)->parameters(['tugas' => 'tugas']);
    Route::post('/tugas/{tugas}/nilai/{pengumpulan}', [\App\Http\Controllers\Guru\TugasController::class, 'nilai'])->name('tugas.nilai')->withoutScopedBindings();
});

Route::prefix('kepala-sekolah')->name('ks.')->middleware(['auth', 'role:kepala_sekolah,super_admin,admin_sekolah'])->group(function () {
    Route::get('/dashboard', [KepalaDashboardController::class, 'index'])->name('dashboard');
    Route::post('/kpi/target', [KepalaDashboardController::class, 'updateKpiTarget'])->name('kpi.update-target');
    Route::get('/kpi/export', [KepalaDashboardController::class, 'exportKpiGuru'])->name('kpi.export');
    Route::get('/monitoring-guru', [KepalaDashboardController::class, 'monitoringGuru'])->name('monitoring-guru');
    Route::put('/absensi-guru/{absensiId}/status', [KepalaDashboardController::class, 'updateGuruStatus'])->name('absensi-guru.update-status');
    Route::post('/absensi-guru/{guru}/manual', [KepalaDashboardController::class, 'manualGuruCheckin'])->name('absensi-guru.manual-checkin');
    Route::get('/monitoring-siswa', [KepalaDashboardController::class, 'monitoringSiswa'])->name('monitoring-siswa');
    Route::post('/monitoring-siswa/{siswa}/status', [KepalaDashboardController::class, 'updateSiswaStatus'])->name('monitoring-siswa.update-status');
    Route::get('/rekap', [KepalaRekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/detail-kelas', [KepalaRekapController::class, 'detailKelas'])->name('rekap.detail-kelas');
    Route::get('/rekap/export', [KepalaRekapController::class, 'export'])->name('rekap.export');
    Route::get('/guru/{guru}', [KepalaDashboardController::class, 'detailGuru'])->name('detail-guru');
    Route::get('/guru/{guru}/export', [KepalaDashboardController::class, 'exportDetailGuru'])->name('detail-guru.export');
    Route::get('/siswa/{siswa}', [KepalaDashboardController::class, 'detailSiswa'])->name('detail-siswa');
    Route::get('/siswa/{siswa}/export', [KepalaDashboardController::class, 'exportDetailSiswa'])->name('detail-siswa.export');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ubah-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/ubah-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/cuti', [CutiController::class, 'index'])->name('cuti.index');
    Route::post('/cuti/{cuti}/process', [CutiController::class, 'process'])->name('cuti.process');

    Route::get('/absensi-saya', [KepalaSelfAbsensiController::class, 'index'])->name('absensi-saya.index');
    Route::get('/absensi-saya/status', [KepalaSelfAbsensiController::class, 'status'])->name('absensi-saya.status');
    Route::post('/absensi-saya/checkin', [KepalaSelfAbsensiController::class, 'checkin'])->name('absensi-saya.checkin');
});


Route::prefix('direktur')->name('direktur.')->middleware(['auth', 'role:direktur,super_admin,admin_sekolah'])->group(function () {
    Route::get('/dashboard', [DirekturDashboardController::class, 'index'])->name('dashboard');
    Route::post('/kpi/target', [DirekturDashboardController::class, 'updateKpiTarget'])->name('kpi.update-target');
    Route::get('/kpi/export', [DirekturDashboardController::class, 'exportKpiStaff'])->name('kpi.export');
    Route::get('/cuti', [CutiController::class, 'index'])->name('cuti.index');
    Route::post('/cuti/{cuti}/process', [CutiController::class, 'process'])->name('cuti.process');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ubah-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/ubah-password', [ProfileController::class, 'updatePassword'])->name('password.update');
});

Route::prefix('staff-akademik')->name('staff.')->middleware(['auth', 'role:staff_akademik'])->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::get('/cuti', [CutiController::class, 'index'])->name('cuti.index');
    Route::get('/cuti/create', [CutiController::class, 'create'])->name('cuti.create');
    Route::post('/cuti', [CutiController::class, 'store'])->name('cuti.store');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ubah-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/ubah-password', [ProfileController::class, 'updatePassword'])->name('password.update');
});

Route::prefix('siswa')->name('siswa.')->middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Siswa\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/jadwal/{jadwal}', [\App\Http\Controllers\Siswa\DashboardController::class, 'showJadwal'])->name('jadwal.show');
    Route::get('/materi/{materi}', [\App\Http\Controllers\Siswa\LMSController::class, 'showMateri'])->name('materi.show');
    Route::get('/tugas/{tugas}', [\App\Http\Controllers\Siswa\LMSController::class, 'showTugas'])->name('tugas.show');
    Route::post('/tugas/{tugas}/submit', [\App\Http\Controllers\Siswa\LMSController::class, 'submitTugas'])->name('tugas.submit');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/ubah-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/ubah-password', [ProfileController::class, 'updatePassword'])->name('password.update');
});
