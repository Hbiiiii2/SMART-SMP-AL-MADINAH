<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaffAkademik;
use App\Models\AbsensiStaff;
use App\Models\PengajuanCuti;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $absensiHariIni = AbsensiStaff::with(['staff.user', 'lokasi'])->where('tanggal', $today)->get();
        
        $totalStaff = StaffAkademik::count();
        $staffHadir = $absensiHariIni->where('status', 'Hadir')->count();
        $staffTerlambat = $absensiHariIni->where('status', 'Terlambat')->count();
        
        $staffCuti = PengajuanCuti::whereHas('user', function($q) {
            $q->where('role', 'staff_akademik');
        })->where('tanggal_mulai', '<=', $today)->where('tanggal_selesai', '>=', $today)->where('status', 'disetujui')->count();

        $pengajuanCutiPending = PengajuanCuti::whereHas('user', function($q) {
            $q->where('role', 'staff_akademik');
        })->where('status', 'pending')->count();

        // Guru Widgets
        $absensiGuruHariIni = \App\Models\AbsensiGuru::with(['guru.user'])->where('tanggal', $today)->get();
        $totalGuru = \App\Models\Guru::count();
        $guruHadir = $absensiGuruHariIni->where('status', 'Hadir')->count();
        $guruTerlambat = $absensiGuruHariIni->where('status', 'Terlambat')->count();
        $guruCuti = PengajuanCuti::whereHas('user', function($q) {
            $q->where('role', 'guru');
        })->where('tanggal_mulai', '<=', $today)->where('tanggal_selesai', '>=', $today)->where('status', 'disetujui')->count();

        // Siswa Widget
        $totalSiswa = \App\Models\Siswa::count();

        $pengaturan = \App\Models\PengaturanSekolah::first();

        $currentMonth = Carbon::now('Asia/Jakarta')->month;
        $currentYear = Carbon::now('Asia/Jakarta')->year;

        $kpiStaff = StaffAkademik::with(['kpiBulanan' => function($q) use ($currentMonth, $currentYear) {
            $q->where('bulan', $currentMonth)->where('tahun', $currentYear);
        }])->get()->map(function($s) use ($currentMonth, $currentYear) {
            $hadir = AbsensiStaff::where('staff_akademik_id', $s->id)
                ->whereMonth('tanggal', $currentMonth)
                ->whereYear('tanggal', $currentYear)
                ->whereIn('status', ['Hadir', 'Terlambat'])
                ->count();
            
            $target = $s->kpiBulanan->first()?->target_hari ?? 0;
            $persentase = $target > 0 ? round(($hadir / $target) * 100) : 0;
            if ($persentase > 100) $persentase = 100;

            $s->kpi_hadir = $hadir;
            $s->kpi_target = $target;
            $s->kpi_persentase = $persentase;
            return $s;
        });

        $kpiGuru = \App\Models\Guru::with(['kpiBulanan' => function($q) use ($currentMonth, $currentYear) {
            $q->where('bulan', $currentMonth)->where('tahun', $currentYear);
        }])->get()->map(function($g) use ($currentMonth, $currentYear) {
            $hadir = \App\Models\AbsensiGuru::where('guru_id', $g->id)
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

        return view('pages.direktur.dashboard', compact(
            'absensiHariIni', 'totalStaff', 'staffHadir', 'staffTerlambat', 'staffCuti', 'pengajuanCutiPending', 'pengaturan',
            'absensiGuruHariIni', 'totalGuru', 'guruHadir', 'guruTerlambat', 'guruCuti', 'totalSiswa',
            'kpiStaff', 'kpiGuru', 'currentMonth', 'currentYear'
        ));
    }

    public function updateKpiTarget(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff_akademik,id',
            'target_hari' => 'required|integer|min:0',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $staff = StaffAkademik::findOrFail($request->staff_id);
        $staff->kpiBulanan()->updateOrCreate(
            ['bulan' => $request->bulan, 'tahun' => $request->tahun],
            ['target_hari' => $request->target_hari]
        );

        return back()->with('success', 'Target KPI Staff berhasil diperbarui.');
    }

    public function exportKpiStaff(Request $request)
    {
        $bulan = $request->query('bulan', Carbon::now('Asia/Jakarta')->month);
        $tahun = $request->query('tahun', Carbon::now('Asia/Jakarta')->year);
        $staffId = $request->query('staff_id');

        if ($staffId) {
            $namaFile = 'Kpi_Target_Staff_' . $staffId . '_' . $bulan . '_' . $tahun . '.xlsx';
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\KpiStaffIndividuExport($bulan, $tahun, $staffId),
                $namaFile
            );
        }

        $namaFile = 'Kpi_Target_Semua_Staff_' . $bulan . '_' . $tahun . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KpiStaffExport($bulan, $tahun),
            $namaFile
        );
    }
}
