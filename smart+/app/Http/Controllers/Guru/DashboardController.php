<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\PengaturanSekolah;
use App\Services\AttendanceService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $guru = auth()->user()->guru;
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $jadwals = Jadwal::where('guru_id', $guru->id)
            ->where('hari', $dayName)
            ->with('mataPelajaran', 'lokasi')
            ->get();

        $rekapJadwal = [];
        foreach ($jadwals as $j) {
            $absensi = AbsensiSiswa::where('jadwal_id', $j->id)
                ->where('tanggal', $today)
                ->get();
            $rekapJadwal[] = [
                'jadwal' => $j,
                'totalSiswa' => $absensi->count(),
                'hadirCount' => $absensi->whereIn('status', ['Hadir', 'Terlambat'])->count(),
                'alfaCount' => $absensi->where('status', 'Alfa')->count(),
                'izinCount' => $absensi->where('status', 'Izin')->count(),
                'sakitCount' => $absensi->where('status', 'Sakit')->count(),
            ];
        }

        $absensiStatus = app(AttendanceService::class)->getGuruAbsensiStatus($guru->id, $today);
        $settings = PengaturanSekolah::firstOrFail();
        
        $user = auth()->user();
        $historyCuti = \App\Models\PengajuanCuti::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();
        $allowedLocations = $user->lokasi_diizinkan;
        $now = Carbon::now('Asia/Jakarta');
        $isWeekend = $now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY;
        $isLibur = $isWeekend || \App\Models\HariLibur::isLibur($today);

        return view('pages.guru.dashboard', [
            'title' => 'Dashboard Guru',
            'jadwals' => $jadwals,
            'rekapJadwal' => $rekapJadwal,
            'absensiStatus' => $absensiStatus,
            'metode' => $settings->metode_absensi_guru ?? 'RFID',
            'today' => $today,
            'historyCuti' => $historyCuti,
            'allowedLocations' => $allowedLocations,
            'isLibur' => $isLibur,
        ]);
    }
}
