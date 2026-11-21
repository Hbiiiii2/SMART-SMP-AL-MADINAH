<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\CbtResult;
use App\Models\EraporAuditLog;
use App\Models\Guru;
use App\Models\ImportBatch;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Services\GradingCalculationService;

class EraporSampleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Assessment Components exist
        $this->call(AssessmentComponentSeeder::class);

        $activeYear = TahunAjaran::where('is_aktif', true)->first() ?? TahunAjaran::first();
        if (!$activeYear) {
            $this->command?->error('Tahun ajaran aktif tidak ditemukan!');
            return;
        }

        $guru = Guru::first();
        $targetClass = 'VII - Ibnu Taimiyah';
        $students = Siswa::where('kelas', $targetClass)->orderBy('nama_lengkap')->get();

        if ($students->isEmpty()) {
            $this->command?->warn("Tidak ada siswa di kelas {$targetClass}. Mengambil 20 siswa pertama.");
            $students = Siswa::take(20)->get();
            $targetClass = $students->first()?->kelas ?? 'VII - Ibnu Taimiyah';
        }

        $semester = 'Ganjil';

        // 2. Target Subjects for Class 7
        $subjectKeywords = [
            'Matematika',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Pendidikan Agama Islam',
            'Ilmu Pengetahuan Sosial',
            'Pendidikan Kewarganegaraan',
        ];

        $targetSubjects = MataPelajaran::where(function ($query) use ($subjectKeywords) {
            foreach ($subjectKeywords as $kw) {
                $query->orWhere('nama', 'like', "%{$kw}%");
            }
        })->get();

        if ($targetSubjects->isEmpty()) {
            $targetSubjects = MataPelajaran::take(6)->get();
        }

        $gradingService = app(GradingCalculationService::class);
        $components = AssessmentComponent::all()->keyBy('code');

        // 3. Create LMS Tugas & Student Submissions (so teacher can test 'Tarik Tugas dari LMS')
        $this->command?->info("1/4: Membuat Data Tugas LMS E-Learning...");
        $lmsTaskMap = [];
        foreach ($targetSubjects as $subject) {
            // Task 1
            $tugas1 = Tugas::firstOrCreate(
                [
                    'guru_id' => $guru?->id ?? 1,
                    'mata_pelajaran_id' => $subject->id,
                    'kelas' => $targetClass,
                    'judul' => 'Tugas 1: ' . $subject->nama . ' - Pemahaman Konsep',
                ],
                [
                    'deskripsi' => 'Kerjakan latihan soal modul 1 secara mandiri dan unggah hasil pekerjaan Anda.',
                    'tenggat_waktu' => now()->addDays(7),
                    'tipe_pengumpulan' => 'file',
                ]
            );

            // Task 2
            $tugas2 = Tugas::firstOrCreate(
                [
                    'guru_id' => $guru?->id ?? 1,
                    'mata_pelajaran_id' => $subject->id,
                    'kelas' => $targetClass,
                    'judul' => 'Tugas 2: ' . $subject->nama . ' - Analisis & Aplikasi Kasus',
                ],
                [
                    'deskripsi' => 'Analisis studi kasus pada lembar kerja dan berikan kesimpulan terstruktur.',
                    'tenggat_waktu' => now()->addDays(14),
                    'tipe_pengumpulan' => 'file',
                ]
            );

            // Seed submissions for all students in target class
            foreach ($students as $student) {
                $score1 = min(98, max(75, 78 + (($student->id * 5 + $subject->id * 3) % 20)));
                $score2 = min(98, max(75, 80 + (($student->id * 3 + $subject->id * 7) % 18)));

                PengumpulanTugas::updateOrCreate(
                    ['tugas_id' => $tugas1->id, 'siswa_id' => $student->id],
                    [
                        'file_path' => 'tugas/sample_tugas1.pdf',
                        'waktu_pengumpulan' => now()->subDays(2),
                        'status' => 'dinilai',
                        'nilai' => $score1,
                        'catatan_guru' => 'Pekerjaan rapi dan sistematis.',
                    ]
                );

                PengumpulanTugas::updateOrCreate(
                    ['tugas_id' => $tugas2->id, 'siswa_id' => $student->id],
                    [
                        'file_path' => 'tugas/sample_tugas2.pdf',
                        'waktu_pengumpulan' => now()->subDay(),
                        'status' => 'dinilai',
                        'nilai' => $score2,
                        'catatan_guru' => 'Analisis cukup mendalam dan tepat sasaran.',
                    ]
                );
            }

            $lmsTaskMap[$subject->id] = [$tugas1, $tugas2];
        }

        // 4. Create Simulated CBT Exam Results & Import Batch (so teacher can test 'Tarik STS dari CBT')
        $this->command?->info("2/4: Membuat Data Ujian CBT & Batch Sinkronisasi...");
        $batch = ImportBatch::create([
            'batch_number' => 'SYNC-CBT-' . now()->format('YmdHis'),
            'source_type' => 'cbt_api',
            'mata_pelajaran_id' => null,
            'tahun_ajaran_id' => $activeYear->id,
            'semester' => $semester,
            'kelas' => $targetClass,
            'total_rows' => $students->count() * $targetSubjects->count(),
            'success_rows' => $students->count() * $targetSubjects->count(),
            'failed_rows' => 0,
            'status' => 'committed',
            'file_name' => 'API Engine SMART-CBT (STS Ganjil)',
            'imported_by' => $guru?->user_id,
        ]);

        // 5. Seed Assessment Items & Scores across all components
        $this->command?->info("3/4: Mengisi Nilai Buku Nilai (UH, Tugas, Praktek, Projek, Portofolio, STS)...");
        foreach ($targetSubjects as $subject) {
            // Generate items for UH (1..2), Tugas (1..2), Praktek (1), Projek (1), Portofolio (1), STS (1)
            $itemsMap = [];

            // UH 1 & 2
            if (isset($components['uh'])) {
                for ($seq = 1; $seq <= 2; $seq++) {
                    $itemsMap[] = AssessmentItem::firstOrCreate(
                        [
                            'assessment_component_id' => $components['uh']->id,
                            'mata_pelajaran_id' => $subject->id,
                            'tahun_ajaran_id' => $activeYear->id,
                            'semester' => $semester,
                            'sequence' => $seq,
                        ],
                        [
                            'title' => 'UH ' . $seq . ' (' . $subject->nama . ')',
                            'max_score' => 100.0,
                        ]
                    );
                }
            }

            // Tugas 1 & 2 (linked to LMS)
            if (isset($components['tugas'])) {
                for ($seq = 1; $seq <= 2; $seq++) {
                    $linkedTugas = $lmsTaskMap[$subject->id][$seq - 1] ?? null;
                    $itemsMap[] = AssessmentItem::firstOrCreate(
                        [
                            'assessment_component_id' => $components['tugas']->id,
                            'mata_pelajaran_id' => $subject->id,
                            'tahun_ajaran_id' => $activeYear->id,
                            'semester' => $semester,
                            'sequence' => $seq,
                        ],
                        [
                            'title' => 'Tugas ' . $seq . ' (' . $subject->nama . ')',
                            'tugas_id' => $linkedTugas?->id,
                            'max_score' => 100.0,
                        ]
                    );
                }
            }

            // Praktek 1
            if (isset($components['praktek'])) {
                $itemsMap[] = AssessmentItem::firstOrCreate(
                    [
                        'assessment_component_id' => $components['praktek']->id,
                        'mata_pelajaran_id' => $subject->id,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => $semester,
                        'sequence' => 1,
                    ],
                    [
                        'title' => 'Praktek 1 (' . $subject->nama . ')',
                        'max_score' => 100.0,
                    ]
                );
            }

            // Projek 1
            if (isset($components['projek'])) {
                $itemsMap[] = AssessmentItem::firstOrCreate(
                    [
                        'assessment_component_id' => $components['projek']->id,
                        'mata_pelajaran_id' => $subject->id,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => $semester,
                        'sequence' => 1,
                    ],
                    [
                        'title' => 'Projek 1 (' . $subject->nama . ')',
                        'max_score' => 100.0,
                    ]
                );
            }

            // Portofolio 1
            if (isset($components['portofolio'])) {
                $itemsMap[] = AssessmentItem::firstOrCreate(
                    [
                        'assessment_component_id' => $components['portofolio']->id,
                        'mata_pelajaran_id' => $subject->id,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => $semester,
                        'sequence' => 1,
                    ],
                    [
                        'title' => 'Portofolio 1 (' . $subject->nama . ')',
                        'max_score' => 100.0,
                    ]
                );
            }

            // STS 1 (CBT)
            $stsItem = null;
            if (isset($components['sts'])) {
                $stsItem = AssessmentItem::firstOrCreate(
                    [
                        'assessment_component_id' => $components['sts']->id,
                        'mata_pelajaran_id' => $subject->id,
                        'tahun_ajaran_id' => $activeYear->id,
                        'semester' => $semester,
                        'sequence' => 1,
                    ],
                    [
                        'title' => 'STS 1 (' . $subject->nama . ')',
                        'max_score' => 100.0,
                        'cbt_exam_external_id' => 'STS1-' . strtoupper(substr($subject->nama, 0, 3)) . '-7',
                    ]
                );
                $itemsMap[] = $stsItem;
            }

            // Populate Student Scores for each item
            foreach ($students as $student) {
                // Calculate realistic score variation per student
                $studentAbility = 72 + (($student->id * 11) % 24); // between 72 and 96

                foreach ($itemsMap as $item) {
                    $variance = (($item->id * 7 + $student->id * 3) % 9) - 4; // -4 to +4
                    $score = min(98.5, max(68.0, round($studentAbility + $variance, 1)));

                    $sourceType = 'manual';
                    $notes = 'Penilaian harian guru';

                    if ($item->assessment_component_id === $components['sts']?->id) {
                        $sourceType = 'cbt_api';
                        $notes = 'Tersinkronisasi otomatis dari engine SMART-CBT';

                        // Save snapshot into cbt_results
                        CbtResult::create([
                            'import_batch_id' => $batch->id,
                            'source_type' => 'cbt_api',
                            'exam_external_id' => $item->cbt_exam_external_id,
                            'exam_name' => $item->title,
                            'student_nisn' => $student->nisn,
                            'student_name' => $student->nama_lengkap,
                            'class_name' => $targetClass,
                            'subject_name' => $subject->nama,
                            'score' => $score,
                            'status' => 'finalized',
                            'started_at' => now()->subDays(3)->setTime(8, 0),
                            'submitted_at' => now()->subDays(3)->setTime(9, 20),
                        ]);
                    } elseif ($item->assessment_component_id === $components['tugas']?->id) {
                        $sourceType = 'lms';
                        $notes = 'Ditarik dari LMS Tugas E-Learning';
                    }

                    StudentAssessmentScore::updateOrCreate(
                        [
                            'siswa_id' => $student->id,
                            'assessment_item_id' => $item->id,
                        ],
                        [
                            'score' => $score,
                            'source_type' => $sourceType,
                            'notes' => $notes,
                            'updated_by' => $guru?->user_id,
                        ]
                    );
                }

                // Recalculate student subject summary
                $gradingService->recalculateSubjectGrade(
                    $student->id,
                    $subject->id,
                    $activeYear->id,
                    $semester
                );
            }
        }

        // 6. Recalculate Class STS Summaries & Competition Ranking (RANK.EQ)
        $this->command?->info("4/4: Menghitung Rekapitulasi Nilai STS & Peringkat Excel RANK.EQ...");
        $gradingService->recalculateClassSTS($targetClass, $activeYear->id, $semester);

        EraporAuditLog::record(
            action: 'seed_sample_data',
            entityType: 'EraporSampleSeeder',
            entityId: (string) $batch->id,
            before: null,
            after: [
                'class' => $targetClass,
                'students_count' => $students->count(),
                'subjects_count' => $targetSubjects->count(),
            ],
            reason: "Seeding data lengkap E-Rapor terintegrasi untuk kelas {$targetClass}"
        );

        $this->command?->info("SELESAI! Data E-Rapor untuk kelas {$targetClass} berhasil di-generate secara lengkap.");
    }
}
