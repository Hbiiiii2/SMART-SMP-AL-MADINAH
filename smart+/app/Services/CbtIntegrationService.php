<?php

namespace App\Services;

use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\CbtResult;
use App\Models\EraporAuditLog;
use App\Models\ImportBatch;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CbtIntegrationService
{
    public function __construct(
        protected GradingCalculationService $gradingService
    ) {}

    /**
     * Resolve or create target AssessmentComponent for CBT sync/import (sts or uas).
     */
    protected function resolveCbtTargetComponent(string $componentCode): AssessmentComponent
    {
        $code = strtolower(trim($componentCode)) ?: 'sts';
        $isUas = ($code === 'uas');

        return AssessmentComponent::where('code', $code)->first() ?? AssessmentComponent::firstOrCreate(
            ['code' => $code],
            [
                'name' => $isUas ? 'UAS' : ($code === 'sts' ? 'STS 1' : strtoupper($code)),
                'default_count' => 1,
                'default_weight' => $isUas ? 40.0 : 30.0,
                'order' => $isUas ? 7 : 6,
            ]
        );
    }

    /**
     * Fetch list of available exams from CBT API
     */
    public function fetchAvailableExams(?int $subjectId = null, ?string $kelas = null): array
    {
        $baseUrl = rtrim(config('services.cbt.base_url', env('CBT_API_BASE_URL', 'http://smart-cbt-web/api/v1')), '/');
        $token = config('services.cbt.token', env('CBT_API_TOKEN', 'cbt_secret_token_almadinah_2026'));

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$baseUrl}/exams", ['per_page' => 100]);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }
        } catch (\Throwable $e) {
            // Fail gracefully if CBT is not online
        }

        return [];
    }

    /**
     * Resolve Mata Pelajaran ID by subject name or keyword alias
     */
    public function resolveMataPelajaranId(?string $subjectName, ?int $fallbackId = null): ?int
    {
        if (empty($subjectName)) {
            return $fallbackId;
        }

        $clean = trim($subjectName);
        $lower = strtolower($clean);

        // 1. Direct match on exact full name
        $mapel = MataPelajaran::where('nama', $clean)->first();
        if ($mapel) {
            return $mapel->id;
        }

        // 2. Like query
        $mapel = MataPelajaran::where('nama', 'like', '%' . $clean . '%')->first();
        if ($mapel) {
            return $mapel->id;
        }

        // 3. Keyword / alias mapping
        $aliasMap = [
            'matematika' => ['mtk', 'math', 'matematika'],
            'bahasa indonesia' => ['indonesia', 'bhs indonesia', 'b. indonesia', 'indo'],
            'bahasa inggris' => ['inggris', 'bhs inggris', 'b. inggris', 'english'],
            'bahasa arab' => ['arab', 'bhs arab', 'b. arab', 'arabic'],
            'pendidikan agama islam' => ['pai', 'agama islam', 'pendidikan agama'],
            'pendidikan kewarganegaraan' => ['pkn', 'kewarganegaraan', 'ppkn'],
            'pendidikan jasmani olahraga dan kesehatan' => ['pjok', 'penjas', 'olahraga', 'penjaskes', 'jasmani'],
            'ilmu pengetahuan sosial' => ['ips', 'sosial'],
            'biologi' => ['biologi', 'bio', 'ipa biologi', 'ipa'],
            'fisika' => ['fisika', 'fis', 'ipa fisika'],
            'teknologi informasi dan komunikasi' => ['tik', 'informatika', 'komputer', 'it'],
            'prakarya' => ['prakarya', 'prky', 'seni budaya dan prakarya'],
            'baca tulis qur\'an' => ['btq', 'baca tulis al-qur\'an', 'al-qur\'an'],
            'tilawati' => ['tilawati', 'tlwt'],
            'pramuka' => ['pramuka', 'prmk'],
        ];

        foreach ($aliasMap as $officialName => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    $found = MataPelajaran::where('nama', 'like', '%' . $officialName . '%')->first();
                    if ($found) {
                        return $found->id;
                    }
                }
            }
        }

        return $fallbackId;
    }

    /**
     * Sync CBT results via REST API v1 with flexible mapping & remedial modes
     */
    public function syncCbtViaApi(
        ?int $cbtExamId = null,
        ?int $mataPelajaranId = null,
        ?int $tahunAjaranId = null,
        string $semester = 'Ganjil',
        ?string $kelas = null,
        ?int $targetAssessmentItemId = null,
        string $componentCode = 'sts',
        string $syncMode = 'highest' // 'highest' (default: susulan/remedial), 'replace', 'fill_empty'
    ): array {
        $baseUrl = rtrim(config('services.cbt.base_url', env('CBT_API_BASE_URL', 'http://smart-cbt-web/api/v1')), '/');
        $token = config('services.cbt.token', env('CBT_API_TOKEN', 'cbt_secret_token_almadinah_2026'));

        $activeYear = $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : TahunAjaran::where('is_aktif', true)->first();
        if (!$activeYear) {
            return ['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'];
        }

        // Fetch all pages from CBT API (to ensure all subjects & all students are fetched)
        $allResults = [];
        $page = 1;
        $maxPages = 25; // Safety ceiling (up to 5,000 records)

        do {
            $params = [
                'per_page' => 200,
                'page' => $page,
            ];
            if ($cbtExamId) {
                $params['exam_id'] = $cbtExamId;
            }

            try {
                $response = Http::withToken($token)
                    ->timeout(30)
                    ->get("{$baseUrl}/exam-results", $params);
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => "Gagal terhubung ke API SMART-CBT ({$baseUrl}) pada halaman {$page}: " . $e->getMessage() . ". Pastikan container smart-cbt-web aktif.",
                ];
            }

            // Auto-fallback if HTTP 404 is encountered (handles URL variations /api/v1, /v1, root)
            if ($response->status() === 404) {
                $altBases = [];
                if (str_ends_with($baseUrl, '/api/v1')) {
                    $altBases[] = substr($baseUrl, 0, -7) . '/v1';
                    $altBases[] = substr($baseUrl, 0, -7);
                } elseif (str_ends_with($baseUrl, '/v1')) {
                    $altBases[] = substr($baseUrl, 0, -3) . '/api/v1';
                    $altBases[] = substr($baseUrl, 0, -3);
                } else {
                    $altBases[] = $baseUrl . '/api/v1';
                    $altBases[] = $baseUrl . '/v1';
                }

                foreach ($altBases as $altBase) {
                    try {
                        $altResponse = Http::withToken($token)
                            ->timeout(15)
                            ->get("{$altBase}/exam-results", $params);
                        if ($altResponse->successful()) {
                            $response = $altResponse;
                            $baseUrl = $altBase;
                            break;
                        }
                    } catch (\Throwable) {}
                }
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => "Gagal mengambil hasil ujian dari API CBT ({$baseUrl}/exam-results): " . ($response->json('error.message') ?? ('HTTP ' . $response->status())) . ". Silakan jalankan 'docker compose exec smart-cbt-app php artisan route:cache' jika endpoint belum terdaftar.",
                ];
            }

            $pageData = $response->json('data') ?? [];
            if (empty($pageData)) {
                break;
            }

            $allResults = array_merge($allResults, $pageData);
            $hasMore = (bool) ($response->json('meta.has_more') ?? false);
            $page++;
        } while ($hasMore && $page <= $maxPages);

        if (empty($allResults)) {
            return [
                'success' => true,
                'message' => 'Tidak ada data hasil ujian baru yang eligible dari CBT.',
                'synced_count' => 0,
            ];
        }

        // Sort ascending by ID/start time so that regular exams are processed first, and susulan/remedials processed after
        usort($allResults, function ($a, $b) {
            $idA = (int) str_replace('result_', '', (string) ($a['id'] ?? '0'));
            $idB = (int) str_replace('result_', '', (string) ($b['id'] ?? '0'));
            return $idA <=> $idB;
        });

        // Filter by user selection if specific mapel or class requested
        $results = [];
        foreach ($allResults as $item) {
            $resolvedMapel = $this->resolveMataPelajaranId($item['subject_name'] ?? null);
            if ($mataPelajaranId && $resolvedMapel && $resolvedMapel !== $mataPelajaranId) {
                continue;
            }
            if ($kelas && !empty($item['class_name']) && !str_contains(strtolower($item['class_name']), strtolower($kelas))) {
                continue;
            }
            $results[] = $item;
        }

        if (empty($results)) {
            return [
                'success' => true,
                'message' => 'Tidak ada hasil ujian yang cocok dengan filter mata pelajaran / kelas yang dipilih.',
                'synced_count' => 0,
            ];
        }

        // Create import batch
        $batch = ImportBatch::create([
            'batch_number' => 'SYNC-CBT-' . now()->format('YmdHis'),
            'source_type' => 'cbt_api',
            'mata_pelajaran_id' => $mataPelajaranId,
            'tahun_ajaran_id' => $activeYear->id,
            'semester' => $semester,
            'kelas' => $kelas,
            'total_rows' => count($results),
            'status' => 'committed',
            'file_name' => "CBT API Sync (Total: " . count($results) . " nilai, Mode: {$syncMode})",
            'imported_by' => auth()->id(),
        ]);

        $targetComponent = $this->resolveCbtTargetComponent($componentCode);

        $syncedCount = 0;
        $failedCount = 0;
        $affectedStudents = [];
        $assessmentItemCache = [];

        foreach ($results as $item) {
            // Find student by NISN or Name
            $nisn = trim((string) ($item['student_nisn'] ?? ''));
            $name = trim((string) ($item['student_name'] ?? ''));

            // Skip dummy test accounts
            if (strtolower($name) === 'test' || $nisn === '-' || empty($name)) {
                $failedCount++;
                continue;
            }

            $siswa = null;
            if (!empty($nisn) && $nisn !== '-') {
                $siswa = Siswa::where('nisn', $nisn)->first();
            }
            if (!$siswa && !empty($name)) {
                $siswa = Siswa::where('nama_lengkap', $name)->first();
            }

            if (!$siswa) {
                $failedCount++;
                continue;
            }

            // Determine Subject for this row
            $mapelId = $mataPelajaranId ?: $this->resolveMataPelajaranId($item['subject_name'] ?? null);
            if (!$mapelId) {
                $mapelId = MataPelajaran::first()?->id;
            }

            $score = (float) ($item['score'] ?? 0);

            // Record raw snapshot in cbt_results
            CbtResult::create([
                'import_batch_id' => $batch->id,
                'source_type' => 'cbt_api',
                'source_record_id' => $item['id'] ?? null,
                'exam_external_id' => $item['exam_external_id'] ?? null,
                'exam_name' => $item['exam_name'] ?? null,
                'student_nisn' => $nisn,
                'student_name' => $name,
                'class_name' => $item['class_name'] ?? $siswa->kelas,
                'subject_name' => $item['subject_name'] ?? null,
                'score' => $score,
                'status' => 'finalized',
                'started_at' => $item['started_at'] ?? null,
                'submitted_at' => $item['submitted_at'] ?? null,
            ]);

            // Determine AssessmentItem: either specified or auto-created per subject
            if ($targetAssessmentItemId) {
                $assessmentItem = AssessmentItem::find($targetAssessmentItemId);
            } else {
                $cacheKey = "{$targetComponent->id}_{$mapelId}_{$activeYear->id}_{$semester}";
                $assessmentItem = $assessmentItemCache[$cacheKey] ??= AssessmentItem::firstOrCreate(
                    [
                        'assessment_component_id' => $targetComponent->id,
                        'mata_pelajaran_id' => $mapelId,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => $semester,
                        'sequence' => 1,
                    ],
                    [
                        'title' => $targetComponent->name,
                        'max_score' => 100.0,
                        'cbt_exam_external_id' => $item['exam_external_id'] ?? null,
                    ]
                );
            }

            if (!$assessmentItem) {
                $failedCount++;
                continue;
            }

            // Existing score lookup for remedial / susulan modes
            $existingScore = StudentAssessmentScore::where('siswa_id', $siswa->id)
                ->where('assessment_item_id', $assessmentItem->id)
                ->first();

            $finalScoreToSave = $score;
            if ($syncMode === 'highest' && $existingScore?->score !== null) {
                $finalScoreToSave = max((float) $existingScore->score, $score);
            } elseif ($syncMode === 'fill_empty' && $existingScore?->score !== null) {
                // Skip if already filled
                continue;
            }

            // Upsert into student assessment scores
            StudentAssessmentScore::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'assessment_item_id' => $assessmentItem->id,
                ],
                [
                    'score' => $finalScoreToSave,
                    'source_type' => 'cbt_api',
                    'source_ref_id' => $item['id'] ?? null,
                    'notes' => 'Tersinkronisasi otomatis dari API CBT (' . ($item['exam_name'] ?? '') . " [Mode: {$syncMode}])",
                    'updated_by' => auth()->id(),
                ]
            );

            // Recalculate student subject summary
            $this->gradingService->recalculateSubjectGrade(
                $siswa->id,
                $mapelId,
                $activeYear->id,
                $semester
            );

            $affectedStudents[$siswa->kelas][$mapelId] = true;
            $syncedCount++;
        }

        // Recalculate Class STS summaries
        foreach (array_keys($affectedStudents) as $className) {
            $this->gradingService->recalculateClassSTS($className, $activeYear->id, $semester);
        }

        $batch->update([
            'success_rows' => $syncedCount,
            'failed_rows' => $failedCount,
        ]);

        EraporAuditLog::record(
            action: 'sync_cbt',
            entityType: 'ImportBatch',
            entityId: (string) $batch->id,
            before: null,
            after: ['synced' => $syncedCount, 'failed' => $failedCount, 'total_fetched' => count($allResults)],
            reason: "Sinkronisasi hasil ujian dari REST API CBT (Mode: {$syncMode}, Sukses: {$syncedCount})"
        );

        return [
            'success' => true,
            'message' => "Sinkronisasi selesai: {$syncedCount} nilai siswa dari semua mata pelajaran berhasil diperbarui.",
            'batch_id' => $batch->id,
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
        ];
    }

    /**
     * Import CBT results via Excel file (Fallback mechanism)
     * Supports both single-subject Excel and multi-subject consolidated CBT exports.
     */
    public function importCbtViaExcel(
        UploadedFile $file,
        ?int $mataPelajaranId = null,
        ?int $tahunAjaranId = null,
        string $semester = 'Ganjil',
        ?string $kelas = null,
        string $syncMode = 'highest',
        string $componentCode = 'sts'
    ): array {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return ['success' => false, 'message' => 'File Excel kosong atau tidak terbaca.'];
        }

        $activeYear = $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : TahunAjaran::where('is_aktif', true)->first();
        if (!$activeYear) {
            return ['success' => false, 'message' => 'Tahun ajaran aktif tidak ditemukan.'];
        }

        // Header mapping
        $headerRow = array_shift($rows);
        $headers = [
            'nisn' => null,
            'nama' => null,
            'kelas' => null,
            'subject' => null,
            'exam' => null,
            'score' => null,
            'status' => null,
        ];

        foreach ($headerRow as $index => $header) {
            $col = strtolower(trim((string) $header));
            if (str_contains($col, 'nisn') || str_contains($col, 'nis')) {
                $headers['nisn'] = $index;
            } elseif (str_contains($col, 'nama')) {
                $headers['nama'] = $index;
            } elseif (str_contains($col, 'kelas') || str_contains($col, 'rombel')) {
                $headers['kelas'] = $index;
            } elseif (str_contains($col, 'mata pelajaran') || str_contains($col, 'mapel') || str_contains($col, 'pelajaran')) {
                $headers['subject'] = $index;
            } elseif (str_contains($col, 'ujian')) {
                $headers['exam'] = $index;
            } elseif (str_contains($col, 'nilai') || str_contains($col, 'skor') || str_contains($col, 'score')) {
                $headers['score'] = $index;
            } elseif (str_contains($col, 'status')) {
                $headers['status'] = $index;
            }
        }

        $batch = ImportBatch::create([
            'batch_number' => 'IMP-EXCEL-' . now()->format('YmdHis'),
            'source_type' => 'excel',
            'file_name' => $file->getClientOriginalName(),
            'mata_pelajaran_id' => $mataPelajaranId,
            'tahun_ajaran_id' => $activeYear->id,
            'semester' => $semester,
            'kelas' => $kelas,
            'total_rows' => count($rows),
            'status' => 'committed',
            'imported_by' => auth()->id(),
        ]);

        $targetComponent = $this->resolveCbtTargetComponent($componentCode);

        $syncedCount = 0;
        $failedCount = 0;
        $affectedClasses = [];
        $assessmentItemCache = [];

        foreach ($rows as $row) {
            $nisn = $headers['nisn'] !== null ? trim((string) ($row[$headers['nisn']] ?? '')) : null;
            $nama = $headers['nama'] !== null ? trim((string) ($row[$headers['nama']] ?? '')) : null;
            $rawScore = $headers['score'] !== null ? ($row[$headers['score']] ?? null) : null;
            $score = is_numeric($rawScore) ? (float) $rawScore : null;
            $examName = $headers['exam'] !== null ? trim((string) ($row[$headers['exam']] ?? '')) : null;
            $rowSubject = $headers['subject'] !== null ? trim((string) ($row[$headers['subject']] ?? '')) : null;
            $rowKelas = $headers['kelas'] !== null ? trim((string) ($row[$headers['kelas']] ?? '')) : null;

            // Skip invalid or dummy test accounts
            if ($score === null || strtolower($nama ?? '') === 'test' || $nisn === '-' || empty($nama)) {
                $failedCount++;
                continue;
            }

            // Filter by requested class if specified
            if ($kelas && $rowKelas && !str_contains(strtolower($rowKelas), strtolower($kelas))) {
                continue;
            }

            // Determine student
            $siswa = null;
            if (!empty($nisn) && $nisn !== '-') {
                $siswa = Siswa::where('nisn', $nisn)->first();
            }
            if (!$siswa && !empty($nama)) {
                $siswa = Siswa::where('nama_lengkap', $nama)->first();
            }

            if (!$siswa) {
                $failedCount++;
                continue;
            }

            // Determine Target Mata Pelajaran
            $targetMapelId = null;
            if (!empty($rowSubject)) {
                $targetMapelId = $this->resolveMataPelajaranId($rowSubject);
            }
            if (!$targetMapelId && !empty($examName)) {
                $targetMapelId = $this->resolveMataPelajaranId($examName);
            }
            if (!$targetMapelId) {
                $targetMapelId = $mataPelajaranId ?: MataPelajaran::first()?->id;
            }

            if ($mataPelajaranId && $targetMapelId !== $mataPelajaranId) {
                // If user specifically requested only one subject, skip other subjects
                continue;
            }

            // Get or create AssessmentItem for this subject
            $cacheKey = "{$targetComponent->id}_{$targetMapelId}_{$activeYear->id}_{$semester}";
            $assessmentItem = $assessmentItemCache[$cacheKey] ??= AssessmentItem::firstOrCreate(
                [
                    'assessment_component_id' => $targetComponent->id,
                    'mata_pelajaran_id' => $targetMapelId,
                    'tahun_ajaran_id' => $activeYear->id,
                    'semester' => $semester,
                    'sequence' => 1,
                ],
                [
                    'title' => $targetComponent->name,
                    'max_score' => 100.0,
                ]
            );

            // Record snapshot in cbt_results
            CbtResult::create([
                'import_batch_id' => $batch->id,
                'source_type' => 'cbt_excel',
                'source_record_id' => null,
                'exam_external_id' => null,
                'exam_name' => $examName ?: "Impor Excel ({$file->getClientOriginalName()})",
                'student_nisn' => $nisn,
                'student_name' => $nama,
                'class_name' => $rowKelas ?: $siswa->kelas,
                'subject_name' => $rowSubject,
                'score' => $score,
                'status' => 'finalized',
                'started_at' => null,
                'submitted_at' => null,
            ]);

            // Existing score lookup for remedial / susulan
            $existingScore = StudentAssessmentScore::where('siswa_id', $siswa->id)
                ->where('assessment_item_id', $assessmentItem->id)
                ->first();

            $finalScoreToSave = $score;
            if ($syncMode === 'highest' && $existingScore?->score !== null) {
                $finalScoreToSave = max((float) $existingScore->score, $score);
            } elseif ($syncMode === 'fill_empty' && $existingScore?->score !== null) {
                continue;
            }

            // Upsert score
            StudentAssessmentScore::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'assessment_item_id' => $assessmentItem->id,
                ],
                [
                    'score' => $finalScoreToSave,
                    'source_type' => 'cbt_excel',
                    'source_ref_id' => $batch->batch_number,
                    'notes' => 'Diimpor dari file Excel CBT: ' . $file->getClientOriginalName() . " [Mode: {$syncMode}]",
                    'updated_by' => auth()->id(),
                ]
            );

            $this->gradingService->recalculateSubjectGrade(
                $siswa->id,
                $targetMapelId,
                $activeYear->id,
                $semester
            );

            $affectedClasses[$siswa->kelas] = true;
            $syncedCount++;
        }

        foreach (array_keys($affectedClasses) as $cName) {
            $this->gradingService->recalculateClassSTS($cName, $activeYear->id, $semester);
        }

        $batch->update([
            'success_rows' => $syncedCount,
            'failed_rows' => $failedCount,
        ]);

        EraporAuditLog::record(
            action: 'import',
            entityType: 'ImportBatch',
            entityId: (string) $batch->id,
            before: null,
            after: ['imported' => $syncedCount, 'failed' => $failedCount],
            reason: "Impor hasil ujian dari file Excel: " . $file->getClientOriginalName() . " (Sukses: {$syncedCount})"
        );

        return [
            'success' => true,
            'message' => "Impor Excel selesai: {$syncedCount} nilai siswa berhasil diperbarui ke buku nilai E-Rapor.",
            'batch_id' => $batch->id,
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
        ];
    }

    /**
     * Map LMS assignment scores directly into E-Rapor gradebook
     */
    public function syncLmsTaskScores(
        int $tugasId,
        int $mataPelajaranId,
        int $tahunAjaranId,
        string $semester = 'Ganjil',
        int $sequence = 1
    ): array {
        $tugas = Tugas::findOrFail($tugasId);
        $submissions = PengumpulanTugas::where('tugas_id', $tugasId)
            ->whereNotNull('nilai')
            ->get();

        if ($submissions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Belum ada siswa yang dinilai pada tugas ini.',
            ];
        }

        $tugasComponent = AssessmentComponent::firstOrCreate(
            ['code' => 'tugas'],
            ['name' => 'Tugas', 'default_count' => 6, 'default_weight' => 6.0, 'order' => 2]
        );

        $assessmentItem = AssessmentItem::firstOrCreate(
            [
                'assessment_component_id' => $tugasComponent->id,
                'mata_pelajaran_id' => $mataPelajaranId,
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
                'tugas_id' => $tugasId,
            ],
            [
                'title' => 'Tugas ' . $sequence . ' (' . $tugas->judul . ')',
                'sequence' => $sequence,
                'max_score' => 100.0,
            ]
        );

        $synced = 0;
        foreach ($submissions as $sub) {
            StudentAssessmentScore::updateOrCreate(
                [
                    'siswa_id' => $sub->siswa_id,
                    'assessment_item_id' => $assessmentItem->id,
                ],
                [
                    'score' => (float) $sub->nilai,
                    'source_type' => 'lms',
                    'source_ref_id' => (string) $sub->id,
                    'notes' => 'Diambil dari LMS Tugas: ' . $tugas->judul,
                    'updated_by' => auth()->id(),
                ]
            );

            $this->gradingService->recalculateSubjectGrade(
                $sub->siswa_id,
                $mataPelajaranId,
                $tahunAjaranId,
                $semester
            );

            $synced++;
        }

        EraporAuditLog::record(
            action: 'sync_lms',
            entityType: 'Tugas',
            entityId: (string) $tugasId,
            before: null,
            after: ['synced' => $synced],
            reason: "Pemetaan nilai tugas LMS '{$tugas->judul}' ke buku nilai E-Rapor"
        );

        return [
            'success' => true,
            'message' => "Nilai {$synced} siswa dari LMS berhasil dipetakan ke buku nilai E-Rapor.",
            'synced_count' => $synced,
        ];
    }
}
