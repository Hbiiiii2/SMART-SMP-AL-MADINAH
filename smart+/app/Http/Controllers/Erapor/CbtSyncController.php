<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\CbtIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CbtSyncController extends Controller
{
    public function __construct(
        protected CbtIntegrationService $cbtService
    ) {}

    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::latest()->first();
        $subjects = MataPelajaran::orderBy('nama')->get();
        $classes = Siswa::select('kelas')->distinct()->pluck('kelas');

        $batches = ImportBatch::with('mataPelajaran', 'importer')
            ->latest()
            ->paginate(15);

        // Check CBT API Health
        $cbtApiBaseUrl = rtrim(config('services.cbt.base_url', env('CBT_API_BASE_URL', 'http://smart-cbt-web/api/v1')), '/');
        $cbtHealth = false;
        $cbtInfo = null;

        try {
            $res = Http::timeout(3)->get("{$cbtApiBaseUrl}/health");
            if ($res->successful()) {
                $cbtHealth = true;
                $cbtInfo = $res->json();
            } else {
                // Probe alternative without /api/v1 or with /v1
                $altHealth = str_ends_with($cbtApiBaseUrl, '/api/v1') ? substr($cbtApiBaseUrl, 0, -7) . '/api/v1/health' : $cbtApiBaseUrl . '/health';
                $altRes = Http::timeout(2)->get($altHealth);
                if ($altRes->successful()) {
                    $cbtHealth = true;
                    $cbtInfo = $altRes->json();
                }
            }
        } catch (\Throwable $e) {}

        $cbtExams = $cbtHealth ? $this->cbtService->fetchAvailableExams() : [];

        return view('pages.erapor.cbt-sync', [
            'title' => 'Integrasi Nilai CBT & Fallback Excel',
            'activeYear' => $activeYear,
            'subjects' => $subjects,
            'classes' => $classes,
            'batches' => $batches,
            'cbtHealth' => $cbtHealth,
            'cbtInfo' => $cbtInfo,
            'cbtExams' => $cbtExams,
        ]);
    }

    public function syncApi(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('erapor.cbt.index');
        }

        $request->validate([
            'mata_pelajaran_id' => 'nullable|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'kelas' => 'nullable|string',
            'cbt_exam_id' => 'nullable|integer',
            'component_code' => 'nullable|string|in:sts,uas',
            'sync_mode' => 'nullable|string|in:replace,highest,fill_empty',
        ]);

        try {
            $result = $this->cbtService->syncCbtViaApi(
                cbtExamId: $request->cbt_exam_id ? (int) $request->cbt_exam_id : null,
                mataPelajaranId: $request->mata_pelajaran_id ? (int) $request->mata_pelajaran_id : null,
                tahunAjaranId: (int) $request->tahun_ajaran_id,
                semester: $request->semester,
                kelas: $request->kelas,
                componentCode: $request->get('component_code', 'sts'),
                syncMode: $request->get('sync_mode', 'highest')
            );

            return back()->with($result['success'] ? 'success' : 'error', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan sistem saat sinkronisasi CBT: ' . $e->getMessage());
        }
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:15360',
            'mata_pelajaran_id' => 'nullable|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'kelas' => 'nullable|string',
            'component_code' => 'nullable|string|in:sts,uas',
            'sync_mode' => 'nullable|string|in:replace,highest,fill_empty',
        ]);

        try {
            $result = $this->cbtService->importCbtViaExcel(
                file: $request->file('file'),
                mataPelajaranId: $request->mata_pelajaran_id ? (int) $request->mata_pelajaran_id : null,
                tahunAjaranId: (int) $request->tahun_ajaran_id,
                semester: $request->semester,
                kelas: $request->kelas,
                syncMode: $request->get('sync_mode', 'highest'),
                componentCode: $request->get('component_code', 'sts')
            );

            return back()->with($result['success'] ? 'success' : 'error', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    public function batchDetail($id)
    {
        $batch = ImportBatch::with(['mataPelajaran', 'importer', 'cbtResults'])->findOrFail($id);

        return view('pages.erapor.batch-detail', [
            'title' => 'Detail Riwayat Impor CBT - ' . $batch->batch_number,
            'batch' => $batch,
        ]);
    }
}
