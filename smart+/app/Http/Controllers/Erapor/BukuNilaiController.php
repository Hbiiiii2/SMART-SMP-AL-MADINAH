<?php

namespace App\Http\Controllers\Erapor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\EraporAuditLog;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Services\CbtIntegrationService;
use App\Services\GradingCalculationService;
use Illuminate\Http\Request;

class BukuNilaiController extends Controller
{
    public function __construct(
        protected GradingCalculationService $gradingService,
        protected CbtIntegrationService $cbtService
    ) {}

    public function index(Request $request)
    {
        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::latest()->first();
        $tahunAjaranId = $request->get('tahun_ajaran_id', $activeYear?->id);
        $semester = $request->get('semester', 'Ganjil');

        $user = auth()->user();
        $isTeacher = $user && $user->role === 'guru';
        $guru = $isTeacher ? $user->guru : null;

        $taughtClasses = collect();
        $taughtSubjectIds = collect();

        if ($isTeacher && $guru) {
            $jadwals = Jadwal::where('guru_id', $guru->id)->get();
            $taughtClasses = $jadwals->pluck('kelas')->filter()->unique()->values();
            $taughtSubjectIds = $jadwals->pluck('mata_pelajaran_id')->filter()->unique()->values();
        }

        $classes = Siswa::select('kelas')->distinct()->pluck('kelas');
        $defaultClass = ($taughtClasses->isNotEmpty()) ? $taughtClasses->first() : $classes->first();
        $selectedClass = $request->get('kelas', $defaultClass);

        $subjects = MataPelajaran::orderBy('nama')->get();
        $defaultSubjectId = ($taughtSubjectIds->isNotEmpty()) ? $taughtSubjectIds->first() : $subjects->first()?->id;
        $selectedSubjectId = (int) $request->get('mata_pelajaran_id', $defaultSubjectId);
        $selectedSubject = MataPelajaran::find($selectedSubjectId);

        // Auto-provision standard components (Tugas/UH 30%, UTS/STS 30%, UAS 40%)
        $standardComponents = [
            ['code' => 'uh', 'name' => 'Ulangan Harian', 'default_count' => 3, 'default_weight' => 6.0, 'order' => 1],
            ['code' => 'tugas', 'name' => 'Tugas', 'default_count' => 6, 'default_weight' => 6.0, 'order' => 2],
            ['code' => 'praktek', 'name' => 'Praktek', 'default_count' => 2, 'default_weight' => 6.0, 'order' => 3],
            ['code' => 'projek', 'name' => 'Projek', 'default_count' => 2, 'default_weight' => 6.0, 'order' => 4],
            ['code' => 'portofolio', 'name' => 'Portofolio', 'default_count' => 2, 'default_weight' => 6.0, 'order' => 5],
            ['code' => 'sts', 'name' => 'STS 1', 'default_count' => 1, 'default_weight' => 30.0, 'order' => 6],
            ['code' => 'uas', 'name' => 'UAS', 'default_count' => 1, 'default_weight' => 40.0, 'order' => 7],
        ];

        if (!auth()->user()?->isAkunDemo()) {
            foreach ($standardComponents as $sc) {
                AssessmentComponent::firstOrCreate(
                    ['code' => $sc['code']],
                    array_merge($sc, ['is_active' => true])
                );
            }
        }

        $components = AssessmentComponent::where('is_active', true)->orderBy('order')->get();

        // Ensure assessment items exist for this subject/period
        if (!auth()->user()?->isAkunDemo()) {
            foreach ($components as $comp) {
                for ($seq = 1; $seq <= $comp->default_count; $seq++) {
                    $itemTitle = match($comp->code) {
                        'uh' => 'UH ' . $seq,
                        'tugas' => 'Tugas ' . $seq,
                        'praktek' => 'Praktek ' . $seq,
                        'projek' => 'Projek ' . $seq,
                        'portofolio' => 'Portofolio ' . $seq,
                        'sts' => 'STS 1',
                        'uas' => 'UAS',
                        default => $comp->name . ' ' . $seq
                    };

                    AssessmentItem::firstOrCreate(
                        [
                            'assessment_component_id' => $comp->id,
                            'mata_pelajaran_id' => $selectedSubjectId,
                            'tahun_ajaran_id' => $tahunAjaranId,
                            'semester' => $semester,
                            'sequence' => $seq,
                        ],
                        [
                            'title' => $itemTitle,
                            'max_score' => 100.0,
                        ]
                    );
                }
            }
        }

        $items = AssessmentItem::with('component')
            ->where('mata_pelajaran_id', $selectedSubjectId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->sortBy(fn ($item) => [
                $item->component?->order ?? 999,
                $item->sequence,
                $item->id,
            ])
            ->values();

        // Fetch students
        $students = Siswa::where('kelas', $selectedClass)->orderBy('nama_lengkap')->get();

        // Fetch student scores
        $studentIds = $students->pluck('id');
        $itemIds = $items->pluck('id');

        $scores = StudentAssessmentScore::whereIn('siswa_id', $studentIds)
            ->whereIn('assessment_item_id', $itemIds)
            ->get()
            ->groupBy('siswa_id');

        // Fetch grade summaries
        $summaries = SubjectGradeSummary::whereIn('siswa_id', $studentIds)
            ->where('mata_pelajaran_id', $selectedSubjectId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        // Available LMS assignments for this subject
        $lmsTugasList = Tugas::where('mata_pelajaran_id', $selectedSubjectId)->get();

        $cbtExams = $this->cbtService->fetchAvailableExams($selectedSubjectId, $selectedClass);

        return view('pages.erapor.buku-nilai', [
            'title' => 'Buku Nilai Mata Pelajaran',
            'activeYear' => $activeYear,
            'tahunAjaranId' => $tahunAjaranId,
            'semester' => $semester,
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'subjects' => $subjects,
            'selectedSubject' => $selectedSubject,
            'selectedSubjectId' => $selectedSubjectId,
            'components' => $components,
            'items' => $items,
            'students' => $students,
            'scores' => $scores,
            'summaries' => $summaries,
            'lmsTugasList' => $lmsTugasList,
            'taughtClasses' => $taughtClasses,
            'taughtSubjectIds' => $taughtSubjectIds,
            'cbtExams' => $cbtExams,
        ]);
    }

    /**
     * Store dynamic new assessment item (e.g. UH 3, Remedial, SAS, Projek 2)
     */
    public function storeItem(Request $request)
    {
        $request->validate([
            'assessment_component_id' => 'required|exists:assessment_components,id',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'title' => 'required|string|max:150',
            'max_score' => 'nullable|numeric|min:10|max:100',
        ]);

        $maxSeq = AssessmentItem::where('assessment_component_id', $request->assessment_component_id)
            ->where('mata_pelajaran_id', $request->mata_pelajaran_id)
            ->where('tahun_ajaran_id', $request->tahun_ajaran_id)
            ->where('semester', $request->semester)
            ->max('sequence') ?? 0;

        $item = AssessmentItem::create([
            'assessment_component_id' => $request->assessment_component_id,
            'mata_pelajaran_id' => $request->mata_pelajaran_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'semester' => $request->semester,
            'sequence' => $maxSeq + 1,
            'title' => $request->title,
            'max_score' => $request->max_score ?? 100.0,
        ]);

        return back()->with('success', "Kolom penilaian '{$item->title}' berhasil ditambahkan ke Buku Nilai.");
    }

    /**
     * Delete assessment item
     */
    public function destroyItem(int $id)
    {
        $item = AssessmentItem::findOrFail($id);
        $title = $item->title;

        // Delete associated scores
        StudentAssessmentScore::where('assessment_item_id', $id)->delete();
        $item->delete();

        return back()->with('success', "Kolom penilaian '{$title}' berhasil dihapus.");
    }

    /**
     * Save score update
     */
    public function updateScore(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'assessment_item_id' => 'required|exists:assessment_items,id',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        $score = $request->score !== null ? (float) $request->score : null;

        $existing = StudentAssessmentScore::where('siswa_id', $request->siswa_id)
            ->where('assessment_item_id', $request->assessment_item_id)
            ->first();

        $beforeVal = $existing?->score;

        $record = StudentAssessmentScore::updateOrCreate(
            [
                'siswa_id' => $request->siswa_id,
                'assessment_item_id' => $request->assessment_item_id,
            ],
            [
                'score' => $score,
                'source_type' => 'manual',
                'updated_by' => auth()->id(),
            ]
        );

        $item = AssessmentItem::find($request->assessment_item_id);

        // Recalculate summary
        $summary = $this->gradingService->recalculateSubjectGrade(
            $request->siswa_id,
            $item->mata_pelajaran_id,
            $item->tahun_ajaran_id,
            $item->semester
        );

        // Recalculate class ranking & STS summary
        $siswa = Siswa::find($request->siswa_id);
        if ($siswa && $siswa->kelas) {
            $this->gradingService->recalculateClassSTS(
                $siswa->kelas,
                (int) $item->tahun_ajaran_id,
                $item->semester
            );
        }

        EraporAuditLog::record(
            action: 'update_score',
            entityType: 'StudentAssessmentScore',
            entityId: (string) $record->id,
            before: ['score' => $beforeVal],
            after: ['score' => $score],
            reason: 'Pembaruan nilai manual oleh guru/admin'
        );

        return response()->json([
            'success' => true,
            'message' => 'Nilai berhasil disimpan.',
            'summary' => $summary,
        ]);
    }

    /**
     * Pull grades directly from LMS task
     */
    public function syncFromLms(Request $request)
    {
        $request->validate([
            'tugas_id' => 'required|exists:tugas,id',
            'sequence' => 'required|integer|min:1|max:6',
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
        ]);

        $result = $this->cbtService->syncLmsTaskScores(
            $request->tugas_id,
            $request->mata_pelajaran_id,
            $request->tahun_ajaran_id,
            $request->semester,
            $request->sequence
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Pull grades directly from CBT API with flexible mapping and remedial strategy
     */
    public function syncFromCbt(Request $request)
    {
        $request->validate([
            'mata_pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'semester' => 'required|string',
            'cbt_exam_id' => 'nullable|integer',
            'target_assessment_item_id' => 'nullable|integer',
            'sync_mode' => 'nullable|string|in:replace,highest,fill_empty',
            'component_code' => 'nullable|string',
        ]);

        $result = $this->cbtService->syncCbtViaApi(
            cbtExamId: $request->cbt_exam_id ? (int) $request->cbt_exam_id : null,
            mataPelajaranId: $request->mata_pelajaran_id,
            tahunAjaranId: $request->tahun_ajaran_id,
            semester: $request->semester,
            kelas: $request->get('kelas'),
            targetAssessmentItemId: $request->target_assessment_item_id ? (int) $request->target_assessment_item_id : null,
            componentCode: $request->get('component_code', 'sts'),
            syncMode: $request->get('sync_mode', 'highest')
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
