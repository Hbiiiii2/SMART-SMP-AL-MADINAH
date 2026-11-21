<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class CapaianKompetensiController extends Controller
{
    /**
     * Display interface for subject teachers to input and customize competence descriptions.
     */
    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        $allYears = TahunAjaran::orderBy('id', 'desc')->get();
        $subjects = MataPelajaran::orderBy('nama', 'asc')->get();
        $classes = Siswa::select('kelas')->distinct()->whereNotNull('kelas')->orderBy('kelas')->pluck('kelas');

        $mapelId = (int) $request->query('mata_pelajaran_id', $subjects->first()?->id ?? 1);
        $kelas = $request->query('kelas', $classes->first() ?? '7-A');
        $tahunId = (int) $request->query('tahun_ajaran_id', $activeYear?->id ?? 1);
        $semester = $request->query('semester', 'Ganjil');

        $targetYear = TahunAjaran::find($tahunId) ?? $activeYear;
        $targetMapel = MataPelajaran::find($mapelId) ?? $subjects->first();

        $students = Siswa::where('kelas', $kelas)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $summaries = SubjectGradeSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('mata_pelajaran_id', $mapelId)
            ->where('tahun_ajaran_id', $tahunId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        return view('pages.erapor.capaian-kompetensi', compact(
            'subjects',
            'targetMapel',
            'classes',
            'kelas',
            'allYears',
            'targetYear',
            'semester',
            'students',
            'summaries'
        ));
    }

    /**
     * Save competence descriptions per student for a specific subject.
     */
    public function save(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'kelas' => 'required|string',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'capaian' => 'array',
        ]);

        $mapelId = (int) $request->input('mata_pelajaran_id');
        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = $request->input('semester');
        $capaianList = $request->input('capaian', []);

        foreach ($capaianList as $siswaId => $text) {
            SubjectGradeSummary::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'mata_pelajaran_id' => $mapelId,
                    'tahun_ajaran_id' => $tahunId,
                    'semester' => $semester,
                ],
                [
                    'capaian_kompetensi' => trim($text),
                ]
            );
        }

        return redirect()->route('erapor.capaian.index', [
            'mata_pelajaran_id' => $mapelId,
            'kelas' => $request->input('kelas'),
            'tahun_ajaran_id' => $tahunId,
            'semester' => $semester,
        ])->with('success', 'Deskripsi Capaian Kompetensi berhasil disimpan dan siap dicetak pada Rapor.');
    }

    /**
     * Auto-generate Kurikulum Merdeka competence descriptors for all students in the class based on their scores.
     */
    public function autoGenerate(Request $request)
    {
        $mapelId = (int) $request->input('mata_pelajaran_id');
        $kelas = $request->input('kelas');
        $tahunId = (int) $request->input('tahun_ajaran_id');
        $semester = $request->input('semester');

        $mapel = MataPelajaran::findOrFail($mapelId);
        $students = Siswa::where('kelas', $kelas)->get();
        $gradingService = app(\App\Services\GradingCalculationService::class);

        foreach ($students as $siswa) {
            $sum = SubjectGradeSummary::firstOrNew([
                'siswa_id' => $siswa->id,
                'mata_pelajaran_id' => $mapelId,
                'tahun_ajaran_id' => $tahunId,
                'semester' => $semester,
            ]);

            $formatif = $gradingService->calculateFormatifAverage([
                $sum->uh_avg,
                $sum->tugas_avg,
                $sum->praktek_avg,
                $sum->projek_avg,
                $sum->portofolio_avg,
            ], 1);

            $score = $sum->final_score ?? $sum->sts_score ?? $formatif;
            $predikat = CetakRaporController::calculatePredikat($score);
            $sum->capaian_kompetensi = CetakRaporController::generateDeskripsi($mapel->nama, $score, $predikat);
            $sum->save();
        }

        return redirect()->route('erapor.capaian.index', [
            'mata_pelajaran_id' => $mapelId,
            'kelas' => $kelas,
            'tahun_ajaran_id' => $tahunId,
            'semester' => $semester,
        ])->with('success', 'Seluruh Capaian Kompetensi Kurikulum Merdeka berhasil digenerate otomatis.');
    }
}
