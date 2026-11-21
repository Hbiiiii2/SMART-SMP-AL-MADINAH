<?php

namespace App\Services;

use App\Models\AssessmentComponent;
use App\Models\AssessmentItem;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\StudentAssessmentScore;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;

class GradingCalculationService
{
    /**
     * Calculate component average according to PRD 7.2:
     * - Average = sum of valid filled scores / count of valid filled scores.
     * - Null / empty scores are ignored and NOT treated as 0.
     * - Score of 0 is valid.
     * - Returns null if no valid scores are present (status "Belum ada nilai").
     */
    public function calculateComponentAverage(array $scores, int $decimals = 2): ?float
    {
        $validScores = [];

        foreach ($scores as $score) {
            if ($score !== null && is_numeric($score)) {
                $val = (float) $score;
                if ($val >= 0 && $val <= 100) {
                    $validScores[] = $val;
                }
            }
        }

        if (empty($validScores)) {
            return null; // "Belum ada nilai"
        }

        $avg = array_sum($validScores) / count($validScores);
        return round($avg, $decimals);
    }

    /**
     * Calculate combined Formatif (Tugas/UH) average from the 5 process components.
     * Null components are ignored so subjects without Praktek/Projek/Portofolio
     * only average whichever components have scores.
     */
    public function calculateFormatifAverage(array $componentAverages, int $decimals = 2): ?float
    {
        $valid = [];

        foreach ($componentAverages as $avg) {
            if ($avg !== null && is_numeric($avg)) {
                $valid[] = (float) $avg;
            }
        }

        if (empty($valid)) {
            return null;
        }

        return round(array_sum($valid) / count($valid), $decimals);
    }

    /**
     * Calculate UAS Final Score with 30% Formatif (Tugas/UH) + 30% UTS (STS) + 40% UAS.
     * Missing components are normalized across the weights of components that exist.
     */
    public function calculateWeightedUasFinalScore(
        ?float $formatifAvg,
        ?float $utsScore,
        ?float $uasScore,
        int $decimals = 2
    ): ?float {
        $buckets = [
            ['score' => $formatifAvg, 'weight' => 30.0],
            ['score' => $utsScore, 'weight' => 30.0],
            ['score' => $uasScore, 'weight' => 40.0],
        ];

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($buckets as $bucket) {
            if ($bucket['score'] !== null) {
                $weightedSum += ((float) $bucket['score'] * $bucket['weight']);
                $totalWeight += $bucket['weight'];
            }
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, $decimals) : null;
    }

    /**
     * Resolve students enrolled in a class for a period (preferring historical cohort records).
     */
    public function resolveClassStudents(?string $kelas, int $tahunAjaranId, string $semester = 'Ganjil', bool $ordered = false)
    {
        if (empty($kelas)) {
            return collect();
        }

        $historicalStudentIds = \App\Models\RiwayatKelasSiswa::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->pluck('siswa_id');

        $query = $historicalStudentIds->isNotEmpty()
            ? Siswa::whereIn('id', $historicalStudentIds)
            : Siswa::where('kelas', $kelas);

        if ($ordered) {
            $query->orderBy('nama_lengkap');
        }

        return $query->get();
    }

    /**
     * Check whether any UAS score has been recorded for a given set of students and period.
     */
    public function hasUasScoresForStudents($studentIds, int $tahunAjaranId, string $semester = 'Ganjil'): bool
    {
        $ids = collect($studentIds)->filter()->values();
        if ($ids->isEmpty()) {
            return false;
        }

        return SubjectGradeSummary::whereIn('siswa_id', $ids)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->whereNotNull('uas_score')
            ->exists();
    }

    /**
     * Recalculate a student's subject grade summary for a given period
     */
    public function recalculateSubjectGrade(
        int $siswaId,
        int $mataPelajaranId,
        int $tahunAjaranId,
        string $semester = 'Ganjil'
    ): SubjectGradeSummary {
        // Fetch all assessment items for this subject and period
        $items = AssessmentItem::with('component')
            ->where('mata_pelajaran_id', $mataPelajaranId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get();

        $itemIds = $items->pluck('id');
        $scoresByItemId = StudentAssessmentScore::where('siswa_id', $siswaId)
            ->whereIn('assessment_item_id', $itemIds)
            ->pluck('score', 'assessment_item_id');

        $componentScores = [
            'uh' => [],
            'tugas' => [],
            'praktek' => [],
            'projek' => [],
            'portofolio' => [],
            'sts' => [],
            'uas' => [],
        ];

        foreach ($items as $item) {
            $code = strtolower($item->component?->code ?? 'tugas');
            if (isset($componentScores[$code])) {
                $componentScores[$code][] = $scoresByItemId[$item->id] ?? null;
            }
        }

        $uhAvg = $this->calculateComponentAverage($componentScores['uh']);
        $tugasAvg = $this->calculateComponentAverage($componentScores['tugas']);
        $praktekAvg = $this->calculateComponentAverage($componentScores['praktek']);
        $projekAvg = $this->calculateComponentAverage($componentScores['projek']);
        $portofolioAvg = $this->calculateComponentAverage($componentScores['portofolio']);
        $stsScore = $this->calculateComponentAverage($componentScores['sts']);
        $uasScore = $this->calculateComponentAverage($componentScores['uas']);

        // Determine completion status
        $filledComponents = 0;
        foreach ([$uhAvg, $tugasAvg, $praktekAvg, $projekAvg, $portofolioAvg, $stsScore, $uasScore] as $val) {
            if ($val !== null) {
                $filledComponents++;
            }
        }

        $status = 'belum_ada_nilai';
        if ($filledComponents > 0 && $filledComponents < 3) {
            $status = 'belum_lengkap';
        } elseif ($filledComponents >= 3) {
            $status = 'siap_ditinjau';
        }

        // UAS Final Score: 30% Formatif (5 process components) + 30% UTS (STS CBT) + 40% UAS (normalized)
        $formatifAvg = $this->calculateFormatifAverage([
            $uhAvg,
            $tugasAvg,
            $praktekAvg,
            $projekAvg,
            $portofolioAvg,
        ]);

        $finalScore = $this->calculateWeightedUasFinalScore($formatifAvg, $stsScore, $uasScore);

        $siswa = Siswa::find($siswaId);

        return SubjectGradeSummary::updateOrCreate(
            [
                'siswa_id' => $siswaId,
                'mata_pelajaran_id' => $mataPelajaranId,
                'tahun_ajaran_id' => $tahunAjaranId,
                'semester' => $semester,
            ],
            [
                'kelas' => $siswa?->kelas,
                'uh_avg' => $uhAvg,
                'tugas_avg' => $tugasAvg,
                'praktek_avg' => $praktekAvg,
                'projek_avg' => $projekAvg,
                'portofolio_avg' => $portofolioAvg,
                'sts_score' => $stsScore,
                'uas_score' => $uasScore,
                'final_score' => $finalScore,
                'status' => $status,
            ]
        );
    }

    /**
     * Recalculate Class STS & UAS Summaries and Competition Ranking (Excel RANK.EQ style)
     */
    public function recalculateClassSTS(string $kelas, int $tahunAjaranId, string $semester = 'Ganjil'): void
    {
        $students = $this->resolveClassStudents($kelas, $tahunAjaranId, $semester);

        if ($students->isEmpty()) {
            return;
        }

        $totalSubjectsCount = MataPelajaran::count();
        $summariesByStudent = SubjectGradeSummary::whereIn('siswa_id', $students->pluck('id'))
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->get()
            ->groupBy('siswa_id');

        $studentStsAverages = [];
        $studentUasAverages = [];

        foreach ($students as $siswa) {
            $summaries = $summariesByStudent->get($siswa->id, collect());

            // 1. STS / UTS summary
            $stsEffectiveScores = $summaries
                ->map(fn ($s) => $s->sts_score ?? $s->final_score)
                ->filter(fn ($v) => $v !== null);

            $completedCount = $stsEffectiveScores->count();
            $totalSts = $completedCount > 0 ? (float) $stsEffectiveScores->sum() : null;
            $avgSts = $completedCount > 0 ? round($totalSts / $completedCount, 2) : null;

            // 2. UAS / Final summary (30% Formatif + 30% UTS + 40% UAS, normalized)
            $uasEffectiveScores = $summaries
                ->map(function ($s) {
                    if ($s->final_score !== null) {
                        return (float) $s->final_score;
                    }
                    $formatif = $this->calculateFormatifAverage([
                        $s->uh_avg,
                        $s->tugas_avg,
                        $s->praktek_avg,
                        $s->projek_avg,
                        $s->portofolio_avg,
                    ]);
                    return $this->calculateWeightedUasFinalScore($formatif, $s->sts_score, $s->uas_score);
                })
                ->filter(fn ($v) => $v !== null);

            $uasCompletedCount = $uasEffectiveScores->count();
            $totalUas = $uasCompletedCount > 0 ? (float) $uasEffectiveScores->sum() : null;
            $avgUas = $uasCompletedCount > 0 ? round($totalUas / $uasCompletedCount, 2) : null;

            StudentStsSummary::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'semester' => $semester,
                ],
                [
                    'kelas' => $kelas,
                    'total_score' => $totalSts,
                    'average_score' => $avgSts,
                    'rank' => null,
                    'uas_total_score' => $totalUas,
                    'uas_average_score' => $avgUas,
                    'uas_rank' => null,
                    'uas_completed_subjects_count' => $uasCompletedCount,
                    'completed_subjects_count' => $completedCount,
                    'total_subjects_count' => $totalSubjectsCount,
                    'status' => ($completedCount >= $totalSubjectsCount) ? 'complete' : 'in_progress',
                ]
            );

            $studentStsAverages[$siswa->id] = $avgSts;
            $studentUasAverages[$siswa->id] = $avgUas;
        }

        $this->assignCompetitionRanks($studentStsAverages, 'rank', $tahunAjaranId, $semester);
        $this->assignCompetitionRanks($studentUasAverages, 'uas_rank', $tahunAjaranId, $semester);
    }

    /**
     * Apply competition ranking (Excel RANK.EQ style) to StudentStsSummary.
     * Ties receive the same rank and the next rank is skipped accordingly.
     */
    protected function assignCompetitionRanks(
        array $studentAverages,
        string $rankColumn,
        int $tahunAjaranId,
        string $semester
    ): void {
        $validAverages = array_filter($studentAverages, fn ($val) => $val !== null);
        arsort($validAverages);

        $currentRank = 1;
        $prevScore = null;
        $itemsAtPrevScore = 0;

        foreach ($validAverages as $studentId => $score) {
            if ($prevScore !== null && abs($score - $prevScore) < 0.001) {
                $itemsAtPrevScore++;
            } else {
                $currentRank += $itemsAtPrevScore;
                $itemsAtPrevScore = 1;
                $prevScore = $score;
            }

            StudentStsSummary::where('siswa_id', $studentId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->update([$rankColumn => $currentRank]);
        }
    }
}
