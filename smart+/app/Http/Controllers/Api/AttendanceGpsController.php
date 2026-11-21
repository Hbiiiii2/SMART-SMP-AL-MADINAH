<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use App\Models\AbsensiGuru;
use App\Models\AbsensiStaff;
use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AttendanceGpsController extends Controller
{
    public function checkIn(Request $request)
    {
        return $this->processAttendance($request, 'in');
    }

    public function checkOut(Request $request)
    {
        return $this->processAttendance($request, 'out');
    }

    private function processAttendance(Request $request, $type)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|string', // Base64 image
        ]);

        $user = auth()->user();
        if (!$user || (!$user->isGuru() && !$user->isStaffAkademik() && !$user->isKepalaSekolah())) {
            return response()->json(['success' => false, 'message' => 'Hanya Guru, Kepala Sekolah dan Staff Akademik yang dapat melakukan absensi GPS.'], 403);
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 1. Cek Hari Libur
        $isLibur = HariLibur::where('tanggal', $today)->exists();
        if ($isLibur) {
            return response()->json(['success' => false, 'message' => 'Hari ini adalah hari libur, absensi tidak dapat dilakukan.'], 400);
        }

        // 2. Cek Pengaturan Sekolah (Jam dan Hari Efektif)
        $pengaturan = PengaturanSekolah::first();
        if (!$pengaturan) {
            return response()->json(['success' => false, 'message' => 'Pengaturan sekolah belum dikonfigurasi.'], 500);
        }

        // 3. Verifikasi Lokasi & Zonasi
        $allowedLocations = $user->lokasi_diizinkan;
        if ($allowedLocations->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Anda belum diizinkan melakukan absensi di lokasi manapun. Hubungi Super Admin.'], 403);
        }

        $validLocation = null;
        $userLat = $request->latitude;
        $userLng = $request->longitude;

        foreach ($allowedLocations as $lokasi) {
            $distance = $this->calculateDistance($userLat, $userLng, $lokasi->latitude, $lokasi->longitude);
            if ($distance <= $lokasi->radius) {
                $validLocation = $lokasi;
                break;
            }
        }

        if (!$validLocation) {
            return response()->json(['success' => false, 'message' => 'Anda berada di luar jangkauan (radius) lokasi absensi yang diizinkan.'], 400);
        }

        // 4. Proses Foto
        $imageData = $request->foto;
        $imageParts = explode(";base64,", $imageData);
        if (count($imageParts) != 2) {
            return response()->json(['success' => false, 'message' => 'Format foto tidak valid.'], 400);
        }
        $imageTypeAux = explode("image/", $imageParts[0]);
        $imageType = $imageTypeAux[1] ?? 'jpeg';
        $imageBase64 = base64_decode($imageParts[1]);
        $fileName = 'absensi/' . $user->id . '_' . $now->timestamp . '.' . $imageType;
        
        Storage::disk('public')->put($fileName, $imageBase64);

        // 5. Simpan Data Absensi
        if ($user->isGuru()) {
            return $this->saveGuruAttendance($user, $today, $now, $validLocation, $fileName, $pengaturan, $type, $userLat, $userLng);
        } else {
            return $this->saveStaffAttendance($user, $today, $now, $validLocation, $fileName, $pengaturan, $type, $userLat, $userLng);
        }
    }

    private function saveGuruAttendance($user, $today, $now, $lokasi, $fotoPath, $pengaturan, $type, $lat, $lng)
    {
        $guru = $user->guru;
        if (!$guru) return response()->json(['success' => false, 'message' => 'Data profil guru tidak ditemukan.'], 404);

        $absensi = AbsensiGuru::firstOrNew([
            'guru_id' => $guru->id,
            'tanggal' => $today,
        ]);

        if ($type == 'in') {
            if ($absensi->exists && $absensi->jam_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-In hari ini.'], 400);
            }
            $absensi->jam_masuk = $now;
            $absensi->status = $this->determineStatus($now, $pengaturan);
        } else {
            if (!$absensi->exists || !$absensi->jam_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda belum melakukan Check-In. Silakan Check-In terlebih dahulu.'], 400);
            }
            if ($absensi->jam_keluar) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-Out hari ini.'], 400);
            }
            $absensi->jam_keluar = $now;
        }

        // Save last location and photo
        $absensi->latitude = $lat;
        $absensi->longitude = $lng;
        $absensi->foto_lokasi = $fotoPath;
        $absensi->save();

        return response()->json(['success' => true, 'message' => 'Absensi berhasil dicatat.', 'status' => $absensi->status]);
    }

    private function saveStaffAttendance($user, $today, $now, $lokasi, $fotoPath, $pengaturan, $type, $lat, $lng)
    {
        $staff = $user->staff_akademik;
        if (!$staff) {
            // Auto create missing profile to prevent error
            $staff = \App\Models\StaffAkademik::create([
                'pengguna_id' => $user->id,
                'nama_lengkap' => $user->name,
                'jenis_kelamin' => 'L',
                'status_aktif' => true,
            ]);
        }

        $absensi = AbsensiStaff::firstOrNew([
            'staff_akademik_id' => $staff->id,
            'tanggal' => $today,
        ]);

        if ($type == 'in') {
            if ($absensi->exists && $absensi->waktu_check_in) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-In hari ini.'], 400);
            }
            $absensi->waktu_check_in = $now;
            $absensi->status = $this->determineStatus($now, $pengaturan);
            $absensi->lokasi_id = $lokasi->id; // For staff we record location ID in table
        } else {
            if (!$absensi->exists || !$absensi->waktu_check_in) {
                return response()->json(['success' => false, 'message' => 'Anda belum melakukan Check-In. Silakan Check-In terlebih dahulu.'], 400);
            }
            if ($absensi->waktu_check_out) {
                return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-Out hari ini.'], 400);
            }
            $absensi->waktu_check_out = $now;
        }

        $absensi->latitude = $lat;
        $absensi->longitude = $lng;
        $absensi->foto_lokasi = $fotoPath;
        $absensi->save();

        return response()->json(['success' => true, 'message' => 'Absensi berhasil dicatat.', 'status' => $absensi->status]);
    }

    private function determineStatus($now, $pengaturan)
    {
        $jamMasuk = Carbon::parse($pengaturan->jam_masuk);
        $batasToleransi = $jamMasuk->copy()->addMinutes($pengaturan->toleransi_terlambat_menit);

        if ($now->lessThanOrEqualTo($batasToleransi)) {
            return 'Hadir';
        }
        return 'Terlambat';
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Haversine formula
        $earthRadius = 6371000; // in meters
        
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }
}
