<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\Siswa;
use App\Services\CacheService;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $guru = $user->guru;
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $jadwals = Jadwal::where('guru_id', $guru->id)
            ->where('hari', $dayName)
            ->with('mataPelajaran', 'lokasi')
            ->get();

        $selectedJadwalId = $request->jadwal_id;
        $absensis = collect();
        $jadwal = null;

        $now = Carbon::now('Asia/Jakarta');
        $isWeekend = $now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY;
        $isLibur = \App\Models\HariLibur::isLibur($today);
        $liburDetail = \App\Models\HariLibur::whereDate('tanggal', '<=', $today)
                        ->where(function($q) use ($today) {
                            $q->whereNull('tanggal_selesai')
                              ->orWhereDate('tanggal_selesai', '>=', $today);
                        })->first();

        $isAttendanceDisabled = $isWeekend || $isLibur;

        if ($selectedJadwalId) {
            $jadwal = Jadwal::where('id', $selectedJadwalId)
                ->where('guru_id', $guru->id)
                ->first();

            if (!$jadwal) {
                return back()->withErrors(['error' => 'Jadwal tidak ditemukan atau bukan milik Anda.']);
            }

            if ($isAttendanceDisabled) {
                if ($isWeekend) {
                    return back()->withErrors(['error' => 'Sistem absensi dinonaktifkan karena hari ini adalah hari libur (Sabtu/Minggu).']);
                } else {
                    $namaLibur = $liburDetail ? $liburDetail->nama : 'Hari Libur';
                    return back()->withErrors(['error' => "Sistem absensi dinonaktifkan karena hari ini adalah hari libur nasional: {$namaLibur}."]);
                }
            }

            $allSiswaIds = Siswa::where('kelas', $jadwal->kelas)->pluck('id');

            $existingAbsensi = AbsensiSiswa::where('jadwal_id', $selectedJadwalId)
                ->whereDate('tanggal', $today)
                ->get()
                ->keyBy('siswa_id');

            foreach ($allSiswaIds as $siswaId) {
                if (!$existingAbsensi->has($siswaId)) {
                    $firstTap = \App\Models\AbsensiSiswa::where('siswa_id', $siswaId)
                        ->whereDate('tanggal', $today)
                        ->whereNotNull('jam_masuk')
                        ->orderBy('jam_masuk', 'asc')
                        ->first();

                    if (!$firstTap) {
                        $firstTap = \App\Models\AbsensiSiswa::where('siswa_id', $siswaId)
                            ->whereDate('tanggal', $today)
                            ->orderBy('created_at', 'asc')
                            ->first();
                    }

                    if ($firstTap) {
                        $jamMasukRecord = $firstTap->jam_masuk instanceof Carbon
                            ? $firstTap->jam_masuk->format('H:i:s')
                            : $firstTap->jam_masuk;
                        $jamKeluarRecord = $firstTap->jam_keluar instanceof Carbon
                            ? $firstTap->jam_keluar->format('H:i:s')
                            : $firstTap->jam_keluar;

                        AbsensiSiswa::create([
                            'siswa_id' => $siswaId,
                            'jadwal_id' => $selectedJadwalId,
                            'tanggal' => $today,
                            'status' => $firstTap->status,
                            'jam_masuk' => $jamMasukRecord,
                            'jam_keluar' => $jamKeluarRecord,
                            'keterangan' => 'Otomatis mengikuti absen pertama',
                        ]);
                    } else {
                        AbsensiSiswa::create([
                            'siswa_id' => $siswaId,
                            'jadwal_id' => $selectedJadwalId,
                            'tanggal' => $today,
                            'status' => 'Alfa',
                            'jam_masuk' => null,
                            'jam_keluar' => null,
                            'keterangan' => null,
                        ]);
                    }
                }
            }

            $absensis = AbsensiSiswa::with('siswa.user')
                ->where('jadwal_id', $selectedJadwalId)
                ->whereDate('tanggal', $today)
                ->get();
        }

        return view('pages.guru.absensi.index', [
            'title' => 'Absensi Siswa',
            'jadwals' => $jadwals,
            'absensis' => $absensis,
            'selectedJadwalId' => $selectedJadwalId,
            'jadwal' => $jadwal,
            'today' => $today,
            'isLibur' => $isAttendanceDisabled,
            'liburDetail' => $liburDetail
        ]);
    }

    public function updateStatus(Request $request, int $absensiId)
    {
        CacheService::flushDropdowns();

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');
        $isWeekend = $now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY;
        
        if ($isWeekend || \App\Models\HariLibur::isLibur($today)) {
            return back()->withErrors(['error' => 'Sistem absensi dinonaktifkan karena hari ini adalah hari libur atau akhir pekan.']);
        }

        $absensi = \App\Models\AbsensiSiswa::with('jadwal')->findOrFail($absensiId);
        $guruId = auth()->user()->guru->id ?? null;
        
        // Mencegah kerentanan IDOR: Pastikan jadwal dari absensi ini adalah milik guru yang sedang login
        if ($guruId && $absensi->jadwal->guru_id !== $guruId) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengubah absensi pada jadwal ini.');
        }

        $request->validate([
            'status' => 'required|in:Sakit,Izin,Hadir',
            'alasan' => 'required|string|max:255',
        ]);

        $result = $this->attendanceService->updateAbsensiStatus(
            $absensiId,
            $request->status,
            $request->alasan,
            auth()->id()
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }
}
