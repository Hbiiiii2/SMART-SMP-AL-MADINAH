<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfidApiController extends Controller
{
    public function scan(Request $request, AttendanceService $attendanceService): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|max:50',
        ]);

        $result = $attendanceService->processRfidScan($request->uid);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aksi' => 'rfid_scan',
            'deskripsi' => json_encode($result),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function stats(AttendanceService $attendanceService): JsonResponse
    {
        $stats = $attendanceService->getLiveStats();

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function autoMarkAlfa(AttendanceService $attendanceService): JsonResponse
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        if ($now->dayOfWeek === \Carbon\Carbon::SATURDAY || $now->dayOfWeek === \Carbon\Carbon::SUNDAY) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini Sabtu/Minggu. Tidak ada auto-mark Alpha.',
            ]);
        }

        if (\App\Models\HariLibur::isLibur($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini hari libur nasional. Tidak ada auto-mark Alpha.',
            ]);
        }

        $guruCount = $attendanceService->autoMarkAlphaGuru($today);
        $siswaCount = $attendanceService->autoMarkAlphaSiswa($today);
        $count = $guruCount + $siswaCount;

        return response()->json([
            'success' => true,
            'message' => "{$count} orang (Siswa: {$siswaCount}, Guru: {$guruCount}) ditandai Alfa.",
        ]);
    }
}
