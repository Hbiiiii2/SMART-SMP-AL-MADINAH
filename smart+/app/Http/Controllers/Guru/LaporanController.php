<?php

namespace App\Http\Controllers\Guru;

use App\Exports\AbsensiSiswaExport;
use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $guru = auth()->user()->guru;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $tahunAjaranAktif = \App\Models\TahunAjaran::where('is_aktif', true)->first();
        $tahunAjaranFilter = $request->tahun_ajaran_id ?? ($tahunAjaranAktif ? $tahunAjaranAktif->id : null);

        $jadwalQuery = Jadwal::where('guru_id', $guru->id)->with('mataPelajaran');
        if ($tahunAjaranFilter) {
            $jadwalQuery->where('tahun_ajaran_id', $tahunAjaranFilter);
        }
        $jadwals = $jadwalQuery->get();

        $jadwalIds = $jadwals->pluck('id');
        $kelasList = $jadwals->pluck('kelas')->unique()->sort()->values();
        $mapelList = $jadwals->pluck('mataPelajaran')->unique('id')->values();
        $tahunAjarans = \App\Models\TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();

        // Base query to group by siswa and jadwal
        $query = AbsensiSiswa::select('absensi_siswa.siswa_id', 'absensi_siswa.jadwal_id')
            ->selectRaw('COUNT(CASE WHEN absensi_siswa.status IN ("Hadir", "Terlambat") THEN 1 END) as total_hadir')
            ->selectRaw('COUNT(*) as total_pertemuan')
            ->whereIn('absensi_siswa.jadwal_id', $jadwalIds);

        if ($startDate) {
            $query->where('absensi_siswa.tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('absensi_siswa.tanggal', '<=', $endDate);
        }
        
        // Apply filters
        $filteredJadwalIds = $jadwalIds;
        if ($request->kelas_filter || $request->mapel_filter) {
            $filteredQuery = Jadwal::where('guru_id', $guru->id);
            if ($request->kelas_filter) {
                $filteredQuery->where('kelas', $request->kelas_filter);
            }
            if ($request->mapel_filter) {
                $filteredQuery->where('mata_pelajaran_id', $request->mapel_filter);
            }
            $filteredJadwalIds = $filteredQuery->pluck('id');
            $query->whereIn('absensi_siswa.jadwal_id', $filteredJadwalIds);
        }

        $query->groupBy('absensi_siswa.siswa_id', 'absensi_siswa.jadwal_id');

        // Join siswa to order properly
        $query->join('siswa', 'absensi_siswa.siswa_id', '=', 'siswa.id')
              ->orderBy('siswa.kelas')
              ->orderBy('siswa.nama_lengkap');

        $rekapSiswa = $query->paginate(20)->withQueryString();
        
        // Eager load relations for the view
        $rekapSiswa->getCollection()->transform(function ($item) {
            $item->siswa = Siswa::find($item->siswa_id);
            $item->jadwal = Jadwal::with('mataPelajaran')->find($item->jadwal_id);
            return $item;
        });

        // Global summary for the cards
        $rekap = AbsensiSiswa::whereIn('jadwal_id', $filteredJadwalIds)
            ->when($startDate, fn ($q) => $q->where('tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('tanggal', '<=', $endDate))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pages.guru.laporan.index', [
            'title' => 'Laporan Kehadiran',
            'rekapSiswa' => $rekapSiswa,
            'rekap' => $rekap,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'kelasList' => $kelasList,
            'mapelList' => $mapelList,
            'tahunAjarans' => $tahunAjarans,
            'tahunAjaranFilter' => $tahunAjaranFilter,
        ]);
    }

    public function detail(Request $request, Siswa $siswa, Jadwal $jadwal)
    {
        $guru = auth()->user()->guru;
        
        if ($jadwal->guru_id !== $guru->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        $query = AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran')
            ->where('siswa_id', $siswa->id)
            ->where('jadwal_id', $jadwal->id)
            ->orderByDesc('tanggal');
            
        if ($startDate) {
            $query->where('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('tanggal', '<=', $endDate);
        }
        
        $absensis = $query->paginate(20)->withQueryString();
        
        $rekap = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->where('jadwal_id', $jadwal->id)
            ->when($startDate, fn ($q) => $q->where('tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('tanggal', '<=', $endDate))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pages.guru.laporan.detail', [
            'title' => 'Detail Kehadiran Siswa',
            'siswa' => $siswa,
            'jadwal' => $jadwal,
            'absensis' => $absensis,
            'rekap' => $rekap,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportDetail(Request $request, Siswa $siswa, Jadwal $jadwal)
    {
        $guru = auth()->user()->guru;
        
        if ($jadwal->guru_id !== $guru->id) {
            abort(403, 'Unauthorized access to this schedule.');
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $namaFile = 'Detail_Kehadiran_' . Str::slug($siswa->nama_lengkap) . '_' . Str::slug($jadwal->mataPelajaran->nama);
        if ($startDate && $endDate) {
            $namaFile .= '_' . $startDate . '_to_' . $endDate;
        }
        $namaFile .= '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DetailSiswaGuruExport($siswa->id, $jadwal->id, $startDate, $endDate),
            $namaFile
        );
    }

    public function export(Request $request)
    {
        $guru = auth()->user()->guru;

        $jadwalQuery = Jadwal::where('guru_id', $guru->id);

        if ($request->kelas_filter) {
            $jadwalQuery->where('kelas', $request->kelas_filter);
        }
        if ($request->mapel_filter) {
            $jadwalQuery->where('mata_pelajaran_id', $request->mapel_filter);
        }

        $jadwalIds = $jadwalQuery->pluck('id')->toArray();

        $filename = 'laporan-guru-' . Str::slug(auth()->user()->name);
        if ($request->start_date) {
            $filename .= '-dari-' . $request->start_date;
        }
        if ($request->end_date) {
            $filename .= '-sampai-' . $request->end_date;
        }
        if ($request->kelas_filter) {
            $filename .= '-kelas-' . $request->kelas_filter;
        }
        $filename .= '.xlsx';

        return Excel::download(
            new AbsensiSiswaExport($request->start_date, $request->end_date, null, $jadwalIds),
            $filename
        );
    }
}
