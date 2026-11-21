<?php

namespace App\Http\Controllers\Guru;

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
        $guru = auth()->user()->guru;
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $settings = PengaturanSekolah::firstOrFail();
        $absensiStatus = app(AttendanceService::class)->getGuruAbsensiStatus($guru->id, $today);

        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };
        
        $allowedLocations = auth()->user()->lokasi_diizinkan;
        $now = Carbon::now('Asia/Jakarta');
        $isWeekend = $now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY;
        $isLibur = $isWeekend || \App\Models\HariLibur::isLibur($today);

        return view('pages.guru.absensi-saya.index', [
            'title' => 'Absensi Saya',
            'metode' => $settings->metode_absensi_guru ?? 'RFID',
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
        $guru = auth()->user()->guru;
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $absensiStatus = app(AttendanceService::class)->getGuruAbsensiStatus($guru->id, $today);

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

        $guru = auth()->user()->guru;
        $result = $attendanceService->processGuruZonasiCheckin(
            $guru->id,
            $request->latitude,
            $request->longitude
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
