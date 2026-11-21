<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSekolah;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SelfAbsensiController extends Controller
{
    public function index()
    {
        $staff = auth()->user()->staff_akademik;
        if (!$staff) {
            return redirect()->back()->with('error', 'Profil Staff Akademik belum dibuat untuk akun Anda. Silakan hubungi admin.');
        }

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $settings = PengaturanSekolah::firstOrFail();
        
        $absensiStatus = app(AttendanceService::class)->getStaffAbsensiStatus($staff->id, $today);

        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };
        
        $allowedLocations = auth()->user()->lokasi_diizinkan;
        $isLibur = \App\Models\HariLibur::where('tanggal', $today)->exists();

        // Validasi metode absensi untuk Staff Akademik
        $metode = $settings->metode_absensi_staff ?? 'RFID';

        return view('pages.kepala-sekolah.absensi-saya.index', [
            'title' => 'Absensi Saya',
            'metode' => $metode,
            'absensiStatus' => $absensiStatus,
            'settings' => $settings,
            'today' => $today,
            'dayName' => $dayName,
            'allowedLocations' => $allowedLocations,
            'isLibur' => $isLibur,
        ]);
    }

    public function status(): JsonResponse
    {
        $staff = auth()->user()->staff_akademik;
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Profil tidak ditemukan']);
        }
        
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $absensiStatus = app(AttendanceService::class)->getStaffAbsensiStatus($staff->id, $today);

        return response()->json([
            'success' => true,
            'data' => $absensiStatus,
        ]);
    }

    public function checkin(Request $request, AttendanceService $attendanceService): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $staff = auth()->user()->staff_akademik;
        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Profil tidak ditemukan'], 400);
        }

        $result = $attendanceService->processStaffZonasiCheckin(
            $staff->id,
            $request->latitude,
            $request->longitude
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
