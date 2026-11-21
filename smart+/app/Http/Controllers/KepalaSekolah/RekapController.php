<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Exports\RekapSiswaExport;
use App\Exports\RekapGuruExport;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $periode = $request->periode ?? 'harian';
        $startDate = $request->start_date ?? Carbon::now('Asia/Jakarta')->toDateString();
        $endDate = $request->end_date ?? Carbon::now('Asia/Jakarta')->toDateString();
        $tab = $request->tab ?? 'siswa';

        $tahunAjaranAktif = \App\Models\TahunAjaran::where('is_aktif', true)->first();
        $tahunAjaranFilter = $request->tahun_ajaran_id ?? ($tahunAjaranAktif ? $tahunAjaranAktif->id : null);

        $dateRange = $this->getDateRange($periode, $startDate, $endDate);

        if ($tab === 'siswa') {
            $data = $this->getRekapSiswa($dateRange, $request, $tahunAjaranFilter);
        } else {
            $data = $this->getRekapGuru($dateRange, $request, $tahunAjaranFilter);
        }

        $tahunAjarans = \App\Models\TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();

        return view('pages.kepala-sekolah.rekap', [
            'title' => 'Rekap Absensi',
            'data' => $data['data'],
            'summary' => $data['summary'],
            'periode' => $periode,
            'startDate' => $dateRange['start'],
            'endDate' => $dateRange['end'],
            'tab' => $tab,
            'groupBy' => $request->group_by ?? 'kelas',
            'kelasList' => Siswa::when($tahunAjaranFilter, fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranFilter))->distinct()->pluck('kelas')->sort()->values(),
            'mataPelajarans' => MataPelajaran::all(),
            'gurus' => Guru::with('user')->get(),
            'tahunAjarans' => $tahunAjarans,
            'tahunAjaranFilter' => $tahunAjaranFilter,
        ]);
    }

    public function detailKelas(Request $request)
    {
        $kelas = $request->kelas;
        $startDate = $request->start_date ?? Carbon::now('Asia/Jakarta')->toDateString();
        $endDate = $request->end_date ?? Carbon::now('Asia/Jakarta')->toDateString();

        $siswas = Siswa::where('kelas', $kelas)->orderBy('nama_lengkap')->get();

        $firstAbsensiSubquery = \App\Models\AbsensiSiswa::select('siswa_id', 'tanggal', \Illuminate\Support\Facades\DB::raw('MIN(id) as first_id'))
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy('siswa_id', 'tanggal');

        $detail = $siswas->map(function ($siswa) use ($startDate, $endDate, $firstAbsensiSubquery) {
            $absensi = \App\Models\AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                    $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
                })
                ->where('absensi_siswa.siswa_id', $siswa->id)
                ->whereBetween('absensi_siswa.tanggal', [$startDate, $endDate])
                ->selectRaw('absensi_siswa.status, count(*) as total')
                ->groupBy('absensi_siswa.status')
                ->pluck('total', 'status');

            $total = $absensi->sum();
            $hadir = ($absensi->get('Hadir', 0) ?? 0) + ($absensi->get('Terlambat', 0) ?? 0);

            return [
                'id' => $siswa->id,
                'nisn' => $siswa->nisn,
                'nama' => $siswa->nama_lengkap,
                'hadir' => $absensi->get('Hadir', 0) ?? 0,
                'terlambat' => $absensi->get('Terlambat', 0) ?? 0,
                'alfa' => $absensi->get('Alfa', 0) ?? 0,
                'sakit' => $absensi->get('Sakit', 0) ?? 0,
                'izin' => $absensi->get('Izin', 0) ?? 0,
                'total' => $total,
                'persen' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
            ];
        });

        return response()->json([
            'kelas' => $kelas,
            'periode' => $startDate . ' — ' . $endDate,
            'siswas' => $detail,
        ]);
    }

    private function getDateRange(string $periode, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        switch ($periode) {
            case 'harian':
                break;
            case 'mingguan':
                $start = Carbon::parse($startDate)->startOfWeek();
                $end = Carbon::parse($startDate)->endOfWeek();
                break;
            case 'bulanan':
                $start = Carbon::parse($startDate)->startOfMonth();
                $end = Carbon::parse($startDate)->endOfMonth();
                break;
            case 'semester':
                $parsedDate = Carbon::parse($startDate);
                $month = (int) $parsedDate->month;
                $year = $parsedDate->year;
                if ($month >= 7) {
                    $start = Carbon::create($year, 7, 1)->startOfDay();
                    $end = Carbon::create($year, 12, 31)->endOfDay();
                } else {
                    $start = Carbon::create($year, 1, 1)->startOfDay();
                    $end = Carbon::create($year, 6, 30)->endOfDay();
                }
                break;
        }

        return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
    }

    private function getRekapSiswa(array $dateRange, Request $request, $tahunAjaranFilter = null): array
    {
        $query = AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran')
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']]);

        if ($tahunAjaranFilter) {
            $query->whereHas('jadwal', fn($q) => $q->where('tahun_ajaran_id', $tahunAjaranFilter));
        }

        if ($request->kelas_filter) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas', $request->kelas_filter));
        }
        if ($request->mapel_filter) {
            $query->whereHas('jadwal', fn ($q) => $q->where('mata_pelajaran_id', $request->mapel_filter));
        }
        if ($request->siswa_filter) {
            $query->where('siswa_id', $request->siswa_filter);
        }

        $summaryQuery = clone $query;
        $groupBy = $request->group_by ?? 'kelas';

        $rekap = match ($groupBy) {
            'kelas' => $query->selectRaw("
                    siswa.kelas,
                    COUNT(CASE WHEN absensi_siswa.status IN ('Hadir','Terlambat') THEN 1 END) as hadir,
                    COUNT(CASE WHEN absensi_siswa.status = 'Terlambat' THEN 1 END) as terlambat,
                    COUNT(CASE WHEN absensi_siswa.status = 'Alfa' THEN 1 END) as alfa,
                    COUNT(CASE WHEN absensi_siswa.status = 'Sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN absensi_siswa.status = 'Izin' THEN 1 END) as izin,
                    COUNT(*) as total
                ")->join('siswa', 'absensi_siswa.siswa_id', '=', 'siswa.id')
                ->groupBy('siswa.kelas')
                ->orderBy('siswa.kelas')
                ->get(),
            'pelajaran' => $query->selectRaw("
                    mata_pelajaran.nama as nama_mapel,
                    COUNT(CASE WHEN absensi_siswa.status IN ('Hadir','Terlambat') THEN 1 END) as hadir,
                    COUNT(CASE WHEN absensi_siswa.status = 'Terlambat' THEN 1 END) as terlambat,
                    COUNT(CASE WHEN absensi_siswa.status = 'Alfa' THEN 1 END) as alfa,
                    COUNT(CASE WHEN absensi_siswa.status = 'Sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN absensi_siswa.status = 'Izin' THEN 1 END) as izin,
                    COUNT(*) as total
                ")->join('jadwal', 'absensi_siswa.jadwal_id', '=', 'jadwal.id')
                ->join('mata_pelajaran', 'jadwal.mata_pelajaran_id', '=', 'mata_pelajaran.id')
                ->groupBy('mata_pelajaran.nama')
                ->orderBy('mata_pelajaran.nama')
                ->get(),
            'siswa' => $query->selectRaw("
                    siswa.id as siswa_id,
                    siswa.nama_lengkap,
                    siswa.nisn,
                    siswa.kelas,
                    COUNT(CASE WHEN absensi_siswa.status IN ('Hadir','Terlambat') THEN 1 END) as hadir,
                    COUNT(CASE WHEN absensi_siswa.status = 'Terlambat' THEN 1 END) as terlambat,
                    COUNT(CASE WHEN absensi_siswa.status = 'Alfa' THEN 1 END) as alfa,
                    COUNT(CASE WHEN absensi_siswa.status = 'Sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN absensi_siswa.status = 'Izin' THEN 1 END) as izin,
                    COUNT(*) as total
                ")->join('siswa', 'absensi_siswa.siswa_id', '=', 'siswa.id')
                ->groupBy('siswa.id', 'siswa.nama_lengkap', 'siswa.nisn', 'siswa.kelas')
                ->orderBy('siswa.kelas')
                ->orderBy('siswa.nama_lengkap')
                ->paginate(20)
                ->withQueryString(),
        };

        $summaryData = $summaryQuery->selectRaw("
            COUNT(CASE WHEN absensi_siswa.status IN ('Hadir','Terlambat') THEN 1 END) as total_hadir,
            COUNT(CASE WHEN absensi_siswa.status = 'Terlambat' THEN 1 END) as total_terlambat,
            COUNT(CASE WHEN absensi_siswa.status = 'Alfa' THEN 1 END) as total_alfa,
            COUNT(CASE WHEN absensi_siswa.status = 'Sakit' THEN 1 END) as total_sakit,
            COUNT(CASE WHEN absensi_siswa.status = 'Izin' THEN 1 END) as total_izin,
            COUNT(*) as grand_total
        ")->first();

        $summary = [
            'total_hadir' => $summaryData->total_hadir ?? 0,
            'total_terlambat' => $summaryData->total_terlambat ?? 0,
            'total_alfa' => $summaryData->total_alfa ?? 0,
            'total_sakit' => $summaryData->total_sakit ?? 0,
            'total_izin' => $summaryData->total_izin ?? 0,
            'grand_total' => $summaryData->grand_total ?? 0,
        ];

        return ['data' => $rekap, 'summary' => $summary];
    }

    private function getRekapGuru(array $dateRange, Request $request, $tahunAjaranFilter = null): array
    {
        $query = AbsensiGuru::with('guru.user')
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']]);

        if ($request->guru_filter) {
            $query->where('guru_id', $request->guru_filter);
        }

        $summaryQuery = clone $query;

        $rekap = $query->selectRaw("
                guru.id as guru_id,
                guru.nama_lengkap,
                guru.nip,
                COUNT(CASE WHEN absensi_guru.status IN ('Hadir','Terlambat') THEN 1 END) as hadir,
                COUNT(CASE WHEN absensi_guru.status = 'Terlambat' THEN 1 END) as terlambat,
                COUNT(CASE WHEN absensi_guru.status = 'Alpha' THEN 1 END) as alpha,
                COUNT(CASE WHEN absensi_guru.status = 'Sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN absensi_guru.status = 'Izin' THEN 1 END) as izin,
                COUNT(*) as total
            ")->join('guru', 'absensi_guru.guru_id', '=', 'guru.id')
            ->groupBy('guru.id', 'guru.nama_lengkap', 'guru.nip')
            ->orderBy('guru.nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        $summaryData = $summaryQuery->selectRaw("
            COUNT(CASE WHEN absensi_guru.status IN ('Hadir','Terlambat') THEN 1 END) as total_hadir,
            COUNT(CASE WHEN absensi_guru.status = 'Terlambat' THEN 1 END) as total_terlambat,
            COUNT(CASE WHEN absensi_guru.status = 'Alpha' THEN 1 END) as total_alpha,
            COUNT(CASE WHEN absensi_guru.status = 'Sakit' THEN 1 END) as total_sakit,
            COUNT(CASE WHEN absensi_guru.status = 'Izin' THEN 1 END) as total_izin,
            COUNT(*) as grand_total
        ")->first();

        return [
            'data' => $rekap,
            'summary' => [
                'total_hadir' => $summaryData->total_hadir ?? 0,
                'total_terlambat' => $summaryData->total_terlambat ?? 0,
                'total_alpha' => $summaryData->total_alpha ?? 0,
                'total_sakit' => $summaryData->total_sakit ?? 0,
                'total_izin' => $summaryData->total_izin ?? 0,
                'grand_total' => $summaryData->grand_total ?? 0,
            ],
        ];
    }

    public function export(Request $request)
    {
        $periode = $request->periode ?? 'harian';
        $startDate = $request->start_date ?? Carbon::now('Asia/Jakarta')->toDateString();
        $endDate = $request->end_date ?? Carbon::now('Asia/Jakarta')->toDateString();
        $tab = $request->tab ?? 'siswa';
        $groupBy = $request->group_by ?? 'kelas';

        $dateRange = $this->getDateRange($periode, $startDate, $endDate);

        if ($tab === 'siswa') {
            $query = AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran')
                ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']]);

            if ($request->kelas_filter) {
                $query->whereHas('siswa', fn ($q) => $q->where('kelas', $request->kelas_filter));
            }
            if ($request->mapel_filter) {
                $query->whereHas('jadwal', fn ($q) => $q->where('mata_pelajaran_id', $request->mapel_filter));
            }

            $siswaExportData = $query->select('absensi_siswa.*')
                ->join('siswa', 'absensi_siswa.siswa_id', '=', 'siswa.id')
                ->orderBy('absensi_siswa.tanggal', 'asc')
                ->orderBy('siswa.kelas', 'asc')
                ->orderBy('siswa.nama_lengkap', 'asc')
                ->get();

            $filename = "rekap-siswa-{$dateRange['start']}-{$dateRange['end']}.xlsx";

            return Excel::download(
                new RekapSiswaExport($siswaExportData, $dateRange['start'], $dateRange['end']),
                $filename
            );
        } else {
            $query = AbsensiGuru::with('guru.user')
                ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']]);

            if ($request->guru_filter) {
                $query->where('guru_id', $request->guru_filter);
            }

            $guruExportData = $query->select('absensi_guru.*')
                ->join('guru', 'absensi_guru.guru_id', '=', 'guru.id')
                ->orderBy('absensi_guru.tanggal', 'asc')
                ->orderBy('guru.nama_lengkap', 'asc')
                ->get();

            $filename = "rekap-guru-{$dateRange['start']}-{$dateRange['end']}.xlsx";

            return Excel::download(new RekapGuruExport($guruExportData, $dateRange['start'], $dateRange['end']), $filename);
        }
    }
}
