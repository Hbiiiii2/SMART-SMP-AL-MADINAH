<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\EraporAuditLog;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Services\EraporExportService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BukuIndukController extends Controller
{
    public function __construct(
        protected EraporExportService $exportService
    ) {}

    /**
     * Display list of students and alumni with historical archive filters.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'semua');
        $kelasFilter = $request->get('kelas', 'semua');
        $tahunFilter = $request->get('tahun_ajaran_id', 'semua');

        // High-level Statistics for School Archive
        $totalStudents = Siswa::count();
        $activeStudents = Siswa::where('kelas', 'not like', '%alumni%')
            ->where('kelas', 'not like', '%lulus%')
            ->count();
        $alumniStudents = Siswa::where(function ($q) {
            $q->where('kelas', 'like', '%alumni%')
                ->orWhere('kelas', 'like', '%lulus%');
        })->count();
        $totalGradesRecorded = SubjectGradeSummary::count();

        // Query students with relations
        $query = Siswa::query()->with(['tahunAjaran', 'riwayatKelas.tahunAjaran']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($status === 'aktif') {
            $query->where('kelas', 'not like', '%alumni%')
                ->where('kelas', 'not like', '%lulus%');
        } elseif ($status === 'alumni') {
            $query->where(function ($q) {
                $q->where('kelas', 'like', '%alumni%')
                    ->orWhere('kelas', 'like', '%lulus%');
            });
        }

        if ($kelasFilter !== 'semua' && !empty($kelasFilter)) {
            $query->where('kelas', $kelasFilter);
        }

        if ($tahunFilter !== 'semua' && !empty($tahunFilter)) {
            $query->where(function ($q) use ($tahunFilter) {
                $q->where('tahun_ajaran_id', $tahunFilter)
                    ->orWhereHas('riwayatKelas', function ($r) use ($tahunFilter) {
                        $r->where('tahun_ajaran_id', $tahunFilter);
                    });
            });
        }

        $students = $query->orderBy('kelas')->orderBy('nama_lengkap')->paginate(20)->withQueryString();

        // Calculate cumulative averages for students in the current page
        $studentIds = $students->pluck('id');
        $summariesByStudent = SubjectGradeSummary::whereIn('siswa_id', $studentIds)
            ->where(function ($q) {
                $q->whereNotNull('sts_score')
                    ->orWhereNotNull('uas_score')
                    ->orWhereNotNull('final_score');
            })
            ->get()
            ->groupBy('siswa_id');

        $studentStats = [];
        foreach ($students as $s) {
            $scores = $summariesByStudent[$s->id] ?? collect();
            $uasPeriods = $scores
                ->groupBy(fn ($item) => $item->tahun_ajaran_id . '_' . $item->semester)
                ->map(fn ($group) => $group->whereNotNull('uas_score')->isNotEmpty());

            $effectiveScores = $scores->map(function ($item) use ($uasPeriods) {
                $pKey = $item->tahun_ajaran_id . '_' . $item->semester;
                return ($uasPeriods[$pKey] ?? false)
                    ? ($item->final_score ?? $item->sts_score)
                    : ($item->sts_score ?? $item->final_score);
            })->filter(fn ($v) => $v !== null);

            $avg = $effectiveScores->isNotEmpty() ? round($effectiveScores->avg(), 1) : null;
            $periodsCount = $s->riwayatKelas->count();
            $studentStats[$s->id] = [
                'cumulative_avg' => $avg,
                'periods_count' => $periodsCount,
                'grades_count' => $effectiveScores->count(),
            ];
        }

        $classes = Siswa::select('kelas')->distinct()->pluck('kelas')->sort()->values();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();

        return view('pages.erapor.buku-induk', [
            'title' => 'Buku Induk & Arsip Siswa / Alumni',
            'students' => $students,
            'studentStats' => $studentStats,
            'classes' => $classes,
            'allYears' => $allYears,
            'search' => $search,
            'status' => $status,
            'kelasFilter' => $kelasFilter,
            'tahunFilter' => $tahunFilter,
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'alumniStudents' => $alumniStudents,
            'totalGradesRecorded' => $totalGradesRecorded,
        ]);
    }

    /**
     * Prepare shared transcript data for detail view and A4 printable view.
     */
    protected function prepareTranscriptData(Siswa $siswa): array
    {
        $siswa->load(['tahunAjaran', 'riwayatKelas.tahunAjaran']);

        $periods = RiwayatKelasSiswa::where('siswa_id', $siswa->id)
            ->with('tahunAjaran')
            ->orderBy('tahun_ajaran_id')
            ->orderBy('semester')
            ->get();

        if ($periods->isEmpty()) {
            $activeYear = TahunAjaran::where('is_aktif', true)->first();
            $periods = collect([(object) [
                'tahun_ajaran_id' => $activeYear?->id ?? 1,
                'semester' => 'Ganjil',
                'kelas' => $siswa->kelas,
                'status' => $siswa->is_alumni ? 'alumni' : 'aktif',
                'tahunAjaran' => $activeYear,
            ]]);
        }

        $subjects = MataPelajaran::orderBy('nama')->get();
        $allSummaries = SubjectGradeSummary::where('siswa_id', $siswa->id)->get();
        $stsSummaries = StudentStsSummary::where('siswa_id', $siswa->id)
            ->get()
            ->keyBy(fn ($item) => $item->tahun_ajaran_id . '_' . $item->semester);

        $uasPeriodMap = [];
        foreach ($periods as $p) {
            $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
            $uasPeriodMap[$pKey] = $allSummaries
                ->where('tahun_ajaran_id', $p->tahun_ajaran_id)
                ->where('semester', $p->semester)
                ->whereNotNull('uas_score')
                ->isNotEmpty();
        }

        $attResults = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
            ")
            ->first();

        $schoolSettings = PengaturanSekolah::first();

        $allScores = $allSummaries->map(function ($s) use ($uasPeriodMap) {
            $pKey = $s->tahun_ajaran_id . '_' . $s->semester;
            return ($uasPeriodMap[$pKey] ?? false)
                ? ($s->final_score ?? $s->sts_score)
                : ($s->sts_score ?? $s->final_score);
        })->filter(fn ($v) => $v !== null);

        $cumulativeAvg = $allScores->isNotEmpty() ? round($allScores->avg(), 2) : null;

        return [
            'siswa' => $siswa,
            'periods' => $periods,
            'subjects' => $subjects,
            'allSummaries' => $allSummaries,
            'stsSummaries' => $stsSummaries,
            'uasPeriodMap' => $uasPeriodMap,
            'attendance' => $attResults,
            'schoolSettings' => $schoolSettings,
            'cumulativeAvg' => $cumulativeAvg,
        ];
    }

    /**
     * Show comprehensive individual student academic transcript across all years & semesters.
     */
    public function show(Siswa $siswa)
    {
        $data = $this->prepareTranscriptData($siswa);

        return view('pages.erapor.buku-induk-detail', array_merge($data, [
            'title' => "Buku Induk & Transkrip - {$siswa->nama_lengkap}",
        ]));
    }

    /**
     * Printable A4 view of the student transcript / Buku Induk
     */
    public function print(Siswa $siswa)
    {
        $data = $this->prepareTranscriptData($siswa);

        EraporAuditLog::record(
            action: 'export',
            entityType: 'SiswaTranscript',
            entityId: (string) $siswa->id,
            before: null,
            after: ['action' => 'print'],
            reason: "Cetak Buku Induk / Transkrip Siswa {$siswa->nama_lengkap}"
        );

        return view('pages.erapor.buku-induk-print', array_merge($data, [
            'title' => "Cetak Transkrip - {$siswa->nama_lengkap}",
        ]));
    }

    /**
     * Export student multi-semester transcript to Excel
     */
    public function exportExcel(Siswa $siswa): StreamedResponse
    {
        $spreadsheet = $this->exportService->generateStudentTranscript($siswa);

        $safeNisn = preg_replace('/[^0-9]/', '', $siswa->nisn);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $siswa->nama_lengkap);
        $fileName = "Transkrip_Buku_Induk_{$safeNisn}_{$safeName}.xlsx";

        EraporAuditLog::record(
            action: 'export',
            entityType: 'SiswaTranscript',
            entityId: (string) $siswa->id,
            before: null,
            after: ['filename' => $fileName],
            reason: "Ekspor Transkrip Excel Buku Induk Siswa {$siswa->nama_lengkap}"
        );

        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
