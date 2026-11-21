<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\PengaturanSekolah;
use App\Services\AttendanceService;
use Carbon\Carbon;

class KioskController extends Controller
{
    public function index(AttendanceService $attendanceService)
    {
        $stats = $attendanceService->getLiveStats();

        $settings = PengaturanSekolah::first();

        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        $jadwalAktifs = Jadwal::with('mataPelajaran', 'guru.user', 'lokasi')
            ->where('hari', $dayName)
            ->where('jam_mulai', '<=', $now->format('H:i:s'))
            ->where('jam_selesai', '>=', $now->format('H:i:s'))
            ->get();

        $recentActivities = $this->getRecentActivities($today);

        return view('pages.kiosk.index', [
            'title' => 'Kiosk Absensi',
            'stats' => $stats,
            'settings' => $settings,
            'jadwalAktifs' => $jadwalAktifs,
            'recentActivities' => $recentActivities,
            'today' => $today,
        ]);
    }

    public function stats(AttendanceService $attendanceService)
    {
        return response()->json($attendanceService->getLiveStats());
    }

    public function recentActivities()
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        return response()->json($this->getRecentActivities($today));
    }

    private function getRecentActivities(string $today): array
    {
        $firstAbsensiSubquery = AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->groupBy('siswa_id');

        $siswa = AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran')
            ->joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
            })
            ->select('absensi_siswa.*')
            ->latest('absensi_siswa.jam_masuk')
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'nama' => $a->siswa->nama_lengkap ?? '-',
                'nisn' => $a->siswa->nisn ?? '-',
                'kelas' => $a->siswa->kelas ?? '-',
                'foto' => $a->siswa->foto ? asset('storage/' . $a->siswa->foto) : null,
                'initials' => strtoupper(mb_substr($a->siswa->nama_lengkap ?? '??', 0, 2)),
                'mata_pelajaran' => $a->jadwal->mataPelajaran->nama ?? '-',
                'status' => $a->status,
                'jam_masuk' => $a->jam_masuk ? Carbon::parse($a->jam_masuk)->format('H:i:s') : null,
                'jam_keluar' => $a->jam_keluar ? Carbon::parse($a->jam_keluar)->format('H:i:s') : null,
                'type' => $a->jam_keluar ? 'keluar' : 'masuk',
                'role' => 'siswa',
            ]);

        $guru = AbsensiGuru::with('guru.user')
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->latest('jam_masuk')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'nama' => $a->guru->nama_lengkap ?? '-',
                'nisn' => $a->guru->nip ?? '-',
                'kelas' => '',
                'foto' => $a->guru->foto ? asset('storage/' . $a->guru->foto) : null,
                'initials' => strtoupper(mb_substr($a->guru->nama_lengkap ?? '??', 0, 2)),
                'mata_pelajaran' => '',
                'status' => $a->status,
                'jam_masuk' => $a->jam_masuk ? Carbon::parse($a->jam_masuk)->format('H:i:s') : null,
                'jam_keluar' => $a->jam_keluar ? Carbon::parse($a->jam_keluar)->format('H:i:s') : null,
                'type' => $a->jam_keluar ? 'keluar' : 'masuk',
                'role' => 'guru',
            ]);

        return collect(array_merge($siswa->toArray(), $guru->toArray()))->sortByDesc('jam_masuk')->values()->toArray();
    }
}
