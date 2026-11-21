<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\AbsensiSiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ERapporController extends Controller
{
    /**
     * Get list of students (Siswa)
     */
    public function getSiswa(Request $request)
    {
        // Cache API response to make it blazing fast
        $cacheKey = 'api_siswa_' . md5(json_encode($request->all()));
        
        $siswa = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $query = Siswa::with('tahunAjaran');

            if ($request->has('kelas')) {
                $query->where('kelas', $request->kelas);
            }

            if ($request->has('tahun_ajaran_id')) {
                $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
            }

            return $query->paginate($request->get('per_page', 50));
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Siswa Berhasil Diambil',
            'data'    => $siswa
        ]);
    }

    /**
     * Get detail of a specific student by NISN or Name
     */
    public function getSiswaDetail($identifier)
    {
        // Cache the detail to achieve ~10-50ms response times
        $cacheKey = 'api_siswa_detail_' . md5($identifier);

        $siswa = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($identifier) {
            $nameSearch = str_replace('-', ' ', $identifier);

            // Optimasi: Utamakan exact match index. Hindari LIKE %..% full table scan jika tidak perlu.
            $exactMatch = Siswa::with('tahunAjaran')
                ->where('nisn', $identifier)
                ->orWhere('id', $identifier)
                ->orWhere('nama_lengkap', $nameSearch)
                ->first();

            if ($exactMatch) {
                return $exactMatch;
            }

            // Fallback ke pencarian LIKE (Prefix-based index friendly)
            $prefixMatch = Siswa::with('tahunAjaran')
                ->where('nama_lengkap', 'LIKE', "{$nameSearch}%")
                ->first();
                
            if ($prefixMatch) {
                return $prefixMatch;
            }

            // Fallback terakhir full-text scan
            return Siswa::with('tahunAjaran')
                ->where('nama_lengkap', 'LIKE', "%{$nameSearch}%")
                ->first();
        });

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Data Siswa Tidak Ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Detail Siswa Berhasil Diambil',
            'data'    => $siswa
        ]);
    }

    /**
     * Get rekap absensi per siswa untuk e-rappor
     */
    public function getRekapAbsensi(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'kelas'      => 'nullable|string'
        ]);

        $cacheKey = 'api_rekap_absensi_' . md5(json_encode($request->all()));

        $rekap = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $query = Siswa::query();

            if ($request->has('kelas')) {
                $query->where('kelas', $request->kelas);
            }

            $siswaList = $query->get(['id', 'nisn', 'nama_lengkap', 'kelas']);
            $siswaIds = $siswaList->pluck('id')->toArray();

            // Optimasi Query (Menghindari N+1 Query Problem)
            // Lakukan 1 query GROUP BY untuk mengambil semua rekap, bukan looping query DB per siswa
            $absensiList = AbsensiSiswa::whereIn('siswa_id', $siswaIds)
                ->whereBetween('tanggal', [$request->start_date, $request->end_date])
                ->select('siswa_id', 'status', DB::raw('count(*) as total'))
                ->groupBy('siswa_id', 'status')
                ->get();

            // Inisialisasi map array (O(1) lookup map)
            $rekapData = [];
            foreach ($siswaList as $s) {
                $rekapData[$s->id] = [
                    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'total' => 0
                ];
            }

            // Populasi data hasil GROUP BY
            foreach ($absensiList as $a) {
                $status = strtolower($a->status);
                if (isset($rekapData[$a->siswa_id][$status])) {
                    $rekapData[$a->siswa_id][$status] = (int) $a->total;
                }
                $rekapData[$a->siswa_id]['total'] += (int) $a->total;
            }

            return $siswaList->map(function ($siswa) use ($rekapData) {
                return [
                    'siswa_id'      => $siswa->id,
                    'nisn'          => $siswa->nisn,
                    'nama_lengkap'  => $siswa->nama_lengkap,
                    'kelas'         => $siswa->kelas,
                    'rekap_absensi' => $rekapData[$siswa->id]
                ];
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Rekap Absensi Berhasil Diambil',
            'periode' => [
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date
            ],
            'data'    => $rekap
        ]);
    }
}
