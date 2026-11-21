<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $data = Cache::remember("ks_dashboard_{$today}", 30, function () use ($today, $dayName) {
            $totalGuru = Guru::count();
            $guruHadir = AbsensiGuru::where('tanggal', $today)->whereIn('status', ['Hadir', 'Terlambat'])->count();
            $guruTidakHadir = AbsensiGuru::where('tanggal', $today)->where('status', 'Alpha')->count();
            $guruSakit = AbsensiGuru::where('tanggal', $today)->where('status', 'Sakit')->count();
            $guruIzin = AbsensiGuru::where('tanggal', $today)->where('status', 'Izin')->count();

            $guruList = Guru::with('user')
                ->leftJoin('absensi_guru', function ($join) use ($today) {
                    $join->on('guru.id', '=', 'absensi_guru.guru_id')
                        ->where('absensi_guru.tanggal', '=', $today);
                })
                ->select(
                    'guru.*',
                    'absensi_guru.id as absensi_id',
                    'absensi_guru.status as status_hadir',
                    'absensi_guru.jam_masuk',
                    'absensi_guru.jam_keluar',
                    'absensi_guru.latitude',
                    'absensi_guru.longitude',
                    'absensi_guru.foto_lokasi'
                )
                ->orderByRaw("CASE WHEN absensi_guru.status IS NULL THEN 0 ELSE 1 END ASC, guru.nama_lengkap ASC")
                ->get()
                ->map(function ($g) {
                    $g->status_tampil = $g->status_hadir ?? 'Belum Absen';
                    return $g;
                });

            $totalSiswa = Siswa::count();
            
            $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
                ->where('tanggal', $today)
                ->groupBy('siswa_id');

            $dailyAbsensiBase = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function($join) {
                $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
            });

            $siswaHadir = (clone $dailyAbsensiBase)->whereIn('status', ['Hadir', 'Terlambat'])->count();
            $siswaAlfa = (clone $dailyAbsensiBase)->where('status', 'Alfa')->count();
            $siswaSakit = (clone $dailyAbsensiBase)->where('status', 'Sakit')->count();
            $siswaIzin = (clone $dailyAbsensiBase)->where('status', 'Izin')->count();
            $siswaTerlambat = (clone $dailyAbsensiBase)->where('status', 'Terlambat')->count();

            $rekapPerKelas = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function($join) {
                    $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
                })
                ->join('siswa', 'absensi_siswa.siswa_id', '=', 'siswa.id')
                ->selectRaw("siswa.kelas, 
                    COUNT(CASE WHEN absensi_siswa.status IN ('Hadir','Terlambat') THEN 1 END) as hadir,
                    COUNT(CASE WHEN absensi_siswa.status = 'Terlambat' THEN 1 END) as terlambat,
                    COUNT(CASE WHEN absensi_siswa.status = 'Alfa' THEN 1 END) as alfa,
                    COUNT(CASE WHEN absensi_siswa.status = 'Sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN absensi_siswa.status = 'Izin' THEN 1 END) as izin,
                    COUNT(*) as total")
                ->groupBy('siswa.kelas')
                ->orderBy('siswa.kelas')
                ->get();

            return compact(
                'totalGuru', 'guruHadir', 'guruTidakHadir', 'guruSakit', 'guruIzin', 'guruList',
                'totalSiswa', 'siswaHadir', 'siswaAlfa', 'siswaSakit', 'siswaIzin', 'siswaTerlambat',
                'rekapPerKelas'
            );
        });

        $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
            ->where('tanggal', $today)
            ->groupBy('siswa_id');

        $allSiswaHariIni = Siswa::with('user')
            ->leftJoinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('siswa.id', '=', 'first_absensi.siswa_id');
            })
            ->leftJoin('absensi_siswa', function ($join) {
                $join->on('first_absensi.first_id', '=', 'absensi_siswa.id');
            })
            ->select(
                'siswa.id as siswa_id',
                'siswa.nama_lengkap',
                'siswa.nisn',
                'siswa.kelas',
                'absensi_siswa.status',
                'absensi_siswa.jam_masuk'
            )
            ->orderBy('siswa.kelas')
            ->orderBy('siswa.nama_lengkap')
            ->get()
            ->groupBy('kelas');

        $pengaturan = \App\Models\PengaturanSekolah::first();

        $currentMonth = Carbon::now('Asia/Jakarta')->month;
        $currentYear = Carbon::now('Asia/Jakarta')->year;

        $kpiGuru = Guru::with(['kpiBulanan' => function($q) use ($currentMonth, $currentYear) {
            $q->where('bulan', $currentMonth)->where('tahun', $currentYear);
        }])->get()->map(function($g) use ($currentMonth, $currentYear) {
            $hadir = AbsensiGuru::where('guru_id', $g->id)
                ->whereMonth('tanggal', $currentMonth)
                ->whereYear('tanggal', $currentYear)
                ->whereIn('status', ['Hadir', 'Terlambat'])
                ->count();
            
            $target = $g->kpiBulanan->first()?->target_hari ?? 0;
            $persentase = $target > 0 ? round(($hadir / $target) * 100) : 0;
            if ($persentase > 100) $persentase = 100;

            $g->kpi_hadir = $hadir;
            $g->kpi_target = $target;
            $g->kpi_persentase = $persentase;
            return $g;
        });

        return view('pages.kepala-sekolah.dashboard', [
            'title' => 'Dashboard Kepala Sekolah',
            'data' => $data,
            'allSiswaHariIni' => $allSiswaHariIni,
            'today' => $today,
            'hariAktif' => $dayName,
            'pengaturan' => $pengaturan,
            'kpiGuru' => $kpiGuru,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
        ]);
    }

    public function updateGuruStatus(Request $request, int $absensiId, AttendanceService $attendanceService)
    {
        $request->validate([
            'status' => 'required|in:Sakit,Izin',
            'alasan' => 'required|string|max:255',
        ]);

        $result = $attendanceService->updateGuruAbsensiStatus(
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

    public function updateKpiTarget(Request $request)
    {
        $request->validate([
            'guru_id' => 'required|exists:guru,id',
            'target_hari' => 'required|integer|min:0',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $guru = Guru::findOrFail($request->guru_id);
        $guru->kpiBulanan()->updateOrCreate(
            ['bulan' => $request->bulan, 'tahun' => $request->tahun],
            ['target_hari' => $request->target_hari]
        );

        return back()->with('success', 'Target KPI berhasil diperbarui.');
    }

    public function exportKpiGuru(Request $request)
    {
        $bulan = $request->query('bulan', Carbon::now('Asia/Jakarta')->month);
        $tahun = $request->query('tahun', Carbon::now('Asia/Jakarta')->year);
        $guruId = $request->query('guru_id');

        if ($guruId) {
            $namaFile = 'Kpi_Target_Guru_' . $guruId . '_' . $bulan . '_' . $tahun . '.xlsx';
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\KpiGuruIndividuExport($bulan, $tahun, $guruId),
                $namaFile
            );
        }

        $namaFile = 'Kpi_Target_Semua_Guru_' . $bulan . '_' . $tahun . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KpiGuruExport($bulan, $tahun),
            $namaFile
        );
    }

    public function manualGuruCheckin(Request $request, Guru $guru)
    {
        $request->validate([
            'status' => 'required|in:Hadir,Sakit,Izin',
            'alasan' => 'nullable|string|max:255',
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta')->format('H:i:s');
        
        $existing = AbsensiGuru::where('guru_id', $guru->id)->where('tanggal', $today)->first();
        if ($existing) {
            return back()->withErrors(['error' => 'Guru sudah memiliki record absensi hari ini.']);
        }

        AbsensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => $today,
            'jam_masuk' => $request->status === 'Hadir' ? $now : null,
            'jam_keluar' => null,
            'status' => $request->status,
            'keterangan' => $request->alasan ?? 'Check-in manual oleh Admin/Kepala Sekolah',
        ]);
        
        Cache::forget('live_stats_' . $today);

        return back()->with('success', "Absensi manual untuk {$guru->nama_lengkap} berhasil disimpan sebagai {$request->status}.");
    }

    public function detailGuru(Guru $guru)
    {
        $guru->load('user');

        $startDate = request('start_date', Carbon::now('Asia/Jakarta')->copy()->subDays(30)->toDateString());
        $endDate = request('end_date', Carbon::now('Asia/Jakarta')->toDateString());

        $absensis = AbsensiGuru::where('guru_id', $guru->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderByDesc('tanggal')
            ->paginate(20)
            ->withQueryString();

        $rekap = AbsensiGuru::where('guru_id', $guru->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pages.kepala-sekolah.detail-guru', [
            'title' => 'Detail Absensi Guru',
            'guru' => $guru,
            'absensis' => $absensis,
            'rekap' => $rekap,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportDetailGuru(Request $request, Guru $guru)
    {
        $startDate = $request->query('start_date', Carbon::now('Asia/Jakarta')->copy()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', Carbon::now('Asia/Jakarta')->toDateString());

        $namaFile = 'Detail_Kehadiran_' . Str::slug($guru->nama_lengkap) . '_' . $startDate . '_to_' . $endDate . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DetailGuruExport($guru->id, $startDate, $endDate),
            $namaFile
        );
    }

    public function detailSiswa(Siswa $siswa)
    {
        $siswa->load('user', 'tahunAjaran');

        $startDate = request('start_date', Carbon::now('Asia/Jakarta')->copy()->subDays(30)->toDateString());
        $endDate = request('end_date', Carbon::now('Asia/Jakarta')->toDateString());

        $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('tanggal', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
            ->where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy('tanggal');

        $absensis = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
            })
            ->where('absensi_siswa.siswa_id', $siswa->id)
            ->orderByDesc('absensi_siswa.tanggal')
            ->paginate(20)
            ->withQueryString();

        $rekap = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
            })
            ->where('absensi_siswa.siswa_id', $siswa->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pages.kepala-sekolah.detail-siswa', [
            'title' => 'Detail Absensi Siswa',
            'siswa' => $siswa,
            'absensis' => $absensis,
            'rekap' => $rekap,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportDetailSiswa(Request $request, Siswa $siswa)
    {
        $startDate = $request->query('start_date', Carbon::now('Asia/Jakarta')->copy()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', Carbon::now('Asia/Jakarta')->toDateString());

        $namaFile = 'Detail_Kehadiran_' . Str::slug($siswa->nama_lengkap) . '_' . $startDate . '_to_' . $endDate . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DetailSiswaRfidExport($siswa->id, $startDate, $endDate),
            $namaFile
        );
    }

    public function monitoringGuru(Request $request)
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $gurus = Guru::with('user')
            ->leftJoin('absensi_guru', function ($join) use ($today) {
                $join->on('guru.id', '=', 'absensi_guru.guru_id')
                    ->where('absensi_guru.tanggal', '=', $today);
            })
            ->select(
                'guru.*',
                'absensi_guru.id as absensi_id',
                'absensi_guru.status as status_hadir',
                'absensi_guru.jam_masuk',
                'absensi_guru.jam_keluar',
                'absensi_guru.latitude',
                'absensi_guru.longitude'
            )
            ->when($request->search, function ($q, $s) {
                $q->where('nama_lengkap', 'like', "%{$s}%")->orWhere('nip', 'like', "%{$s}%");
            })
            ->when($request->status_filter, function ($q, $f) use ($today) {
                if ($f === 'belum_absen') {
                    $q->whereNull('absensi_guru.id');
                } elseif ($f === 'hadir') {
                    $q->where('absensi_guru.status', 'Hadir');
                } elseif ($f === 'terlambat') {
                    $q->where('absensi_guru.status', 'Terlambat');
                } elseif ($f === 'alpha') {
                    $q->where('absensi_guru.status', 'Alpha');
                }
            })
            ->orderByRaw("CASE WHEN absensi_guru.status IS NULL THEN 0 ELSE 1 END ASC, guru.nama_lengkap ASC")
            ->paginate(15)
            ->withQueryString();

        $gurus->getCollection()->transform(function ($g) {
            $g->status_tampil = $g->status_hadir ?? 'Belum Absen';
            return $g;
        });

        $pengaturan = \App\Models\PengaturanSekolah::first();

        return view('pages.kepala-sekolah.monitoring-guru', [
            'title' => 'Monitoring Guru',
            'gurus' => $gurus,
            'today' => $today,
            'pengaturan' => $pengaturan,
        ]);
    }

    public function monitoringSiswa(Request $request)
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
            ->where('tanggal', $today)
            ->groupBy('siswa_id');

        $siswas = Siswa::with('user')
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
            ->when($request->search, function ($q, $s) {
                $q->where('nama_lengkap', 'like', "%{$s}%")->orWhere('nisn', 'like', "%{$s}%");
            })
            ->when($request->kelas_filter, function ($q, $f) {
                $q->where('siswa.kelas', $f);
            })
            ->when($request->status_filter, function ($q, $f) {
                if ($f === 'belum_absen') {
                    $q->whereNull('absensi_siswa.id');
                } elseif ($f === 'hadir') {
                    $q->where('absensi_siswa.status', 'Hadir');
                } elseif ($f === 'terlambat') {
                    $q->where('absensi_siswa.status', 'Terlambat');
                } elseif ($f === 'alfa') {
                    $q->where('absensi_siswa.status', 'Alfa');
                }
            })
            ->orderBy('siswa.kelas')
            ->orderBy('siswa.nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        $siswas->getCollection()->transform(function ($s) {
            $s->status_tampil = $s->status_hadir ?? 'Belum Absen';
            return $s;
        });

        $kelasList = Siswa::distinct()->pluck('kelas')->sort()->values();

        return view('pages.kepala-sekolah.monitoring-siswa', [
            'title' => 'Monitoring Siswa',
            'siswas' => $siswas,
            'kelasList' => $kelasList,
            'today' => $today,
        ]);
    }

    public function updateSiswaStatus(Request $request, Siswa $siswa)
    {
        $request->validate([
            'status' => 'required|in:Hadir,Alfa,Izin,Sakit,Terlambat',
            'alasan' => 'nullable|string|max:255',
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $jadwals = \App\Models\Jadwal::where('kelas', $siswa->kelas)
            ->where('hari', $dayName)
            ->get();

        if ($jadwals->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada jadwal pelajaran hari ini untuk kelas ' . $siswa->kelas]);
        }

        $now = Carbon::now('Asia/Jakarta')->format('H:i:s');
        $keterangan = $request->alasan ?? '-';
        $alasanPerubahan = 'Diubah oleh admin: ' . $keterangan;
        
        foreach ($jadwals as $jadwal) {
            \App\Models\AbsensiSiswa::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'jadwal_id' => $jadwal->id,
                    'tanggal' => $today,
                ],
                [
                    'status' => $request->status,
                    'keterangan' => $keterangan,
                    'diedit_oleh' => auth()->id(),
                    'alasan_perubahan' => $alasanPerubahan,
                    // If it was "Hadir" or "Terlambat", we need jam_masuk. If it already has jam_masuk, we might not want to overwrite it if it's already set, but updateOrCreate will overwrite unless we specifically check.
                    // For simplicity, we just set it if it doesn't exist. Actually, let's just do an updateOrCreate and let it overwrite. Or check first.
                ]
            );

            // Perbarui jam_masuk hanya jika belum ada (misal dari Alfa/Sakit jadi Hadir)
            $absensi = \App\Models\AbsensiSiswa::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $jadwal->id)
                ->where('tanggal', $today)
                ->first();
            
            if (in_array($request->status, ['Hadir', 'Terlambat']) && !$absensi->jam_masuk) {
                $absensi->update(['jam_masuk' => $now]);
            } elseif (!in_array($request->status, ['Hadir', 'Terlambat'])) {
                $absensi->update(['jam_masuk' => null, 'jam_keluar' => null]);
            }
        }

        \Illuminate\Support\Facades\Cache::forget("ks_dashboard_{$today}");
        \Illuminate\Support\Facades\Cache::forget("live_stats_{$today}");
        \App\Services\CacheService::flushDropdowns();

        return back()->with('success', 'Status absensi siswa berhasil diubah.');
    }
}
