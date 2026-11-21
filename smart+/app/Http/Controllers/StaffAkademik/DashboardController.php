<?php

namespace App\Http\Controllers\StaffAkademik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AbsensiStaff;
use App\Models\PengajuanCuti;
use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $staff = $user->staff_akademik;
        
        $today = Carbon::today()->toDateString();
        $isLibur = HariLibur::where('tanggal', $today)->exists();

        $absensiHariIni = null;
        $historyAbsensi = collect();
        $historyCuti = collect();

        $absensiStatus = null;
        if ($staff) {
            $absensiHariIni = AbsensiStaff::where('staff_akademik_id', $staff->id)
                ->where('tanggal', $today)
                ->first();
                
            if ($absensiHariIni) {
                $absensiStatus = [
                    'sudah_check_in' => true,
                    'sudah_check_out' => $absensiHariIni->waktu_check_out !== null,
                    'status' => $absensiHariIni->status
                ];
            }
                
            $historyAbsensi = AbsensiStaff::where('staff_akademik_id', $staff->id)
                ->latest('tanggal')
                ->limit(30)
                ->get();
                
            $historyCuti = PengajuanCuti::where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get();
        }

        $pengaturan = PengaturanSekolah::first();
        $allowedLocations = collect();
        if ($user) {
            $allowedLocations = $user->lokasi_diizinkan;
        }

        return view('pages.staff.dashboard', compact(
            'staff', 'absensiHariIni', 'historyAbsensi', 'historyCuti', 
            'isLibur', 'pengaturan', 'allowedLocations', 'absensiStatus'
        ));
    }
}
