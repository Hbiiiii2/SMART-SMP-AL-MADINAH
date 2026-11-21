<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Materi;
use App\Models\Tugas;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $siswa = auth()->user()->siswa;
        // Ambil jadwal berdasarkan kelas siswa
        $jadwals = Jadwal::where('kelas', $siswa->kelas)
            ->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)
            ->with(['guru', 'mataPelajaran'])
            ->get();
            
        // Rekap Absensi (Dikelompokkan per Mata Pelajaran unik)
        $rekapAbsensi = [];
        
        // 1. Inisialisasi struktur rekap berdasarkan mata pelajaran unik dari jadwal aktif
        foreach ($jadwals as $jadwal) {
            $mapelId = $jadwal->mata_pelajaran_id;
            $mapelName = $jadwal->mataPelajaran->nama ?? 'Lainnya';
            if (!isset($rekapAbsensi[$mapelId])) {
                $rekapAbsensi[$mapelId] = [
                    'title' => $mapelName,
                    'Hadir' => 0,
                    'Terlambat' => 0,
                    'Izin' => 0,
                    'Sakit' => 0,
                    'Alfa' => 0,
                    'Total' => 0,
                    'Details' => [],
                ];
            }
        }

        // 2. Ambil data absensi HANYA untuk jadwal yang terkait
        $jadwalIds = $jadwals->pluck('id')->toArray();
        $absensis = \App\Models\AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereIn('jadwal_id', $jadwalIds)
            ->with('jadwal.mataPelajaran')
            ->get();
            
        // 3. Masukkan data absensi ke dalam rekap (dikelompokkan per mata pelajaran)
        foreach ($absensis as $ab) {
            $mapelId = $ab->jadwal->mata_pelajaran_id ?? 0;
            
            if (!isset($rekapAbsensi[$mapelId])) {
                $mapelName = $ab->jadwal->mataPelajaran->nama ?? 'Lainnya';
                $rekapAbsensi[$mapelId] = [
                    'title' => $mapelName,
                    'Hadir' => 0,
                    'Terlambat' => 0,
                    'Izin' => 0,
                    'Sakit' => 0,
                    'Alfa' => 0,
                    'Total' => 0,
                    'Details' => [],
                ];
            }
            
            $status = $ab->status;
            if ($status === 'Alpha') $status = 'Alfa';
            
            if (isset($rekapAbsensi[$mapelId][$status])) {
                $rekapAbsensi[$mapelId][$status]++;
            }
            $rekapAbsensi[$mapelId]['Total']++;
            $rekapAbsensi[$mapelId]['Details'][] = $ab;
        }
        
        // Ambil Tugas Terbaru untuk kelas siswa (misal 10 tugas terakhir)
        $tugasTerbaru = Tugas::where('kelas', $siswa->kelas)
            ->with(['guru', 'mataPelajaran', 'pengumpulanTugas' => function($q) use ($siswa) {
                $q->where('siswa_id', $siswa->id);
            }])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        $unreadTugasCount = Tugas::where('kelas', $siswa->kelas)
            ->whereNotIn('id', function($query) use ($siswa) {
                $query->select('tugas_id')
                      ->from('pengumpulan_tugas')
                      ->where('siswa_id', $siswa->id);
            })->count();
            
        return view('pages.siswa.dashboard', compact('jadwals', 'siswa', 'rekapAbsensi', 'tugasTerbaru', 'unreadTugasCount'));
    }

    public function showJadwal(Jadwal $jadwal)
    {
        $siswa = auth()->user()->siswa;
        
        // Pastikan jadwal ini untuk kelas siswa
        if (strtolower(trim($jadwal->kelas)) !== strtolower(trim($siswa->kelas))) {
            abort(403, "Akses ditolak. Jadwal ini bukan untuk kelas Anda.");
        }

        // Ambil materi untuk kelas dan mata pelajaran ini
        $materi = Materi::where('kelas', $siswa->kelas)
            ->where('mata_pelajaran_id', $jadwal->mata_pelajaran_id)
            ->latest()
            ->get();

        // Ambil tugas untuk kelas dan mata pelajaran ini
        $tugas = Tugas::where('kelas', $siswa->kelas)
            ->where('mata_pelajaran_id', $jadwal->mata_pelajaran_id)
            ->with(['pengumpulanTugas' => function($q) use ($siswa) {
                $q->where('siswa_id', $siswa->id);
            }])
            ->latest()
            ->get();

        return view('pages.siswa.jadwal.show', compact('jadwal', 'materi', 'tugas'));
    }
}
