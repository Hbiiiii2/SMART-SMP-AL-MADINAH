<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_sts_summaries', function (Blueprint $table) {
            if (!Schema::hasColumn('student_sts_summaries', 'catatan_wali_kelas')) {
                $table->text('catatan_wali_kelas')->nullable()->after('status');
            }
        });

        Schema::table('subject_grade_summaries', function (Blueprint $table) {
            if (!Schema::hasColumn('subject_grade_summaries', 'capaian_kompetensi')) {
                $table->text('capaian_kompetensi')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_sts_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('student_sts_summaries', 'catatan_wali_kelas')) {
                $table->dropColumn('catatan_wali_kelas');
            }
        });

        Schema::table('subject_grade_summaries', function (Blueprint $table) {
            if (Schema::hasColumn('subject_grade_summaries', 'capaian_kompetensi')) {
                $table->dropColumn('capaian_kompetensi');
            }
        });
    }
};
