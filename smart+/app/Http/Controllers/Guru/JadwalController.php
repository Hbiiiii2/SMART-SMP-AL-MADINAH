<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use Carbon\Carbon;

class JadwalController extends Controller
{
    public function index()
    {
        $guru = auth()->user()->guru;
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };

        $jadwalHariIni = Jadwal::where('guru_id', $guru->id)
            ->where('hari', $dayName)
            ->with('mataPelajaran', 'lokasi')
            ->orderBy('jam_mulai')
            ->get();

        $semuaJadwal = Jadwal::where('guru_id', $guru->id)
            ->with('mataPelajaran', 'lokasi')
            ->orderByRaw("FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')")
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy('hari');

        return view('pages.guru.jadwal.index', [
            'title' => 'Jadwal Saya',
            'jadwalHariIni' => $jadwalHariIni,
            'semuaJadwal' => $semuaJadwal,
            'hariAktif' => $dayName,
        ]);
    }

    public function getMataPelajaranByKelas(\Illuminate\Http\Request $request)
    {
        $kelas = $request->get('kelas');
        $guruId = auth()->user()->guru->id;

        if (!$kelas) {
            return response()->json([]);
        }

        $jadwals = Jadwal::where('guru_id', $guruId)
            ->where('kelas', $kelas)
            ->with('mataPelajaran')
            ->get();

        // extract mata_pelajaran
        $mapelOptions = $jadwals->pluck('mataPelajaran')->filter()->unique('id')->values();

        return response()->json($mapelOptions);
    }
}
