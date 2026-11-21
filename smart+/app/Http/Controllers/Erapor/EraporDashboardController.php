<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\MataPelajaran;
use App\Models\ReportFinalization;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EraporDashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::latest()->first();
        $tahunAjaranId = $request->get('tahun_ajaran_id', $activeYear?->id);
        $semester = $request->get('semester', 'Ganjil');

        $totalSiswa = Siswa::count();
        $totalMapel = MataPelajaran::count();
        $classes = Siswa::select('kelas')->distinct()->pluck('kelas');

        // Check CBT API Health status
        $cbtApiBaseUrl = config('services.cbt.base_url', env('CBT_API_BASE_URL', 'http://smart-cbt-web/api/v1'));
        $cbtStatus = 'unconfigured';
        $cbtVersion = null;

        try {
            $cbtHealth = Http::timeout(3)->get("{$cbtApiBaseUrl}/health");
            if ($cbtHealth->successful() && $cbtHealth->json('status') === 'ok') {
                $cbtStatus = 'healthy';
                $cbtVersion = $cbtHealth->json('version');
            } else {
                $cbtStatus = 'error';
            }
        } catch (\Throwable $e) {
            $cbtStatus = 'unreachable';
        }

        // Summary counts
        $finalizedClassesCount = ReportFinalization::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->where('status', 'final')
            ->count();

        $recentSyncs = ImportBatch::with('mataPelajaran', 'importer')
            ->latest()
            ->take(5)
            ->get();

        $hasUasScores = SubjectGradeSummary::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->whereNotNull('uas_score')
            ->exists();

        $rankColumn = $hasUasScores ? 'uas_rank' : 'rank';
        $topStudents = StudentStsSummary::with('siswa')
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->whereNotNull($rankColumn)
            ->orderBy($rankColumn)
            ->take(5)
            ->get();

        return view('pages.erapor.dashboard', [
            'title' => 'Dashboard Platform E-Rapor Terintegrasi',
            'activeYear' => $activeYear,
            'tahunAjaranId' => $tahunAjaranId,
            'semester' => $semester,
            'totalSiswa' => $totalSiswa,
            'totalMapel' => $totalMapel,
            'classes' => $classes,
            'cbtStatus' => $cbtStatus,
            'cbtVersion' => $cbtVersion,
            'finalizedClassesCount' => $finalizedClassesCount,
            'recentSyncs' => $recentSyncs,
            'topStudents' => $topStudents,
            'hasUasScores' => $hasUasScores,
        ]);
    }
}
