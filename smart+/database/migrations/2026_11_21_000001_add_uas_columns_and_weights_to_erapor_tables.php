<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subject_grade_summaries')) {
            Schema::table('subject_grade_summaries', function (Blueprint $table) {
                if (!Schema::hasColumn('subject_grade_summaries', 'uas_score')) {
                    $table->decimal('uas_score', 5, 2)->nullable()->after('sts_score');
                }
            });
        }

        if (Schema::hasTable('student_sts_summaries')) {
            Schema::table('student_sts_summaries', function (Blueprint $table) {
                if (!Schema::hasColumn('student_sts_summaries', 'uas_total_score')) {
                    $table->decimal('uas_total_score', 7, 2)->nullable()->after('rank');
                }
                if (!Schema::hasColumn('student_sts_summaries', 'uas_average_score')) {
                    $table->decimal('uas_average_score', 5, 2)->nullable()->after('uas_total_score');
                }
                if (!Schema::hasColumn('student_sts_summaries', 'uas_rank')) {
                    $table->unsignedInteger('uas_rank')->nullable()->after('uas_average_score');
                }
                if (!Schema::hasColumn('student_sts_summaries', 'uas_completed_subjects_count')) {
                    $table->unsignedInteger('uas_completed_subjects_count')->default(0)->after('uas_rank');
                }
            });
        }

        if (Schema::hasTable('assessment_components')) {
            $now = now();

            // 5 formative components share the 30% Formatif (Tugas/UH) bucket
            DB::table('assessment_components')
                ->whereIn('code', ['uh', 'tugas', 'praktek', 'projek', 'portofolio'])
                ->update([
                    'default_weight' => 6.00,
                    'updated_at' => $now,
                ]);

            // STS / UTS carries 30% weight in final UAS grading
            DB::table('assessment_components')
                ->where('code', 'sts')
                ->update([
                    'default_weight' => 30.00,
                    'updated_at' => $now,
                ]);

            // Ensure UAS component exists with 40% weight
            DB::table('assessment_components')->updateOrInsert(
                ['code' => 'uas'],
                [
                    'name' => 'UAS',
                    'default_count' => 1,
                    'default_weight' => 40.00,
                    'is_active' => true,
                    'order' => 7,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        // Recalculate any existing subject grade summaries using the new 30/30/40 normalized formula
        if (Schema::hasTable('subject_grade_summaries') && DB::table('subject_grade_summaries')->exists()) {
            $gradingService = app(\App\Services\GradingCalculationService::class);

            DB::table('subject_grade_summaries')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($gradingService) {
                    foreach ($rows as $row) {
                        $formatifAvg = $gradingService->calculateFormatifAverage([
                            $row->uh_avg,
                            $row->tugas_avg,
                            $row->praktek_avg,
                            $row->projek_avg,
                            $row->portofolio_avg,
                        ]);

                        $finalScore = $gradingService->calculateWeightedUasFinalScore(
                            $formatifAvg,
                            $row->sts_score !== null ? (float) $row->sts_score : null,
                            $row->uas_score !== null ? (float) $row->uas_score : null
                        );

                        DB::table('subject_grade_summaries')
                            ->where('id', $row->id)
                            ->update(['final_score' => $finalScore]);
                    }
                });

            // Recompute class STS & UAS summaries/rankings for any periods already stored
            if (Schema::hasTable('student_sts_summaries') && DB::table('student_sts_summaries')->exists()) {
                $periods = DB::table('student_sts_summaries')
                    ->select('kelas', 'tahun_ajaran_id', 'semester')
                    ->whereNotNull('kelas')
                    ->distinct()
                    ->get();

                foreach ($periods as $period) {
                    $gradingService->recalculateClassSTS(
                        (string) $period->kelas,
                        (int) $period->tahun_ajaran_id,
                        (string) $period->semester
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student_sts_summaries')) {
            Schema::table('student_sts_summaries', function (Blueprint $table) {
                $cols = array_filter([
                    Schema::hasColumn('student_sts_summaries', 'uas_total_score') ? 'uas_total_score' : null,
                    Schema::hasColumn('student_sts_summaries', 'uas_average_score') ? 'uas_average_score' : null,
                    Schema::hasColumn('student_sts_summaries', 'uas_rank') ? 'uas_rank' : null,
                    Schema::hasColumn('student_sts_summaries', 'uas_completed_subjects_count') ? 'uas_completed_subjects_count' : null,
                ]);
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('subject_grade_summaries') && Schema::hasColumn('subject_grade_summaries', 'uas_score')) {
            Schema::table('subject_grade_summaries', function (Blueprint $table) {
                $table->dropColumn('uas_score');
            });
        }
    }
};
