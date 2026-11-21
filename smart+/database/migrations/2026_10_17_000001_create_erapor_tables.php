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
        // 1. Assessment Components (UH, Tugas, Praktek, Projek, Portofolio, STS 1)
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('default_count')->default(1);
            $table->decimal('default_weight', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
        });

        // 2. Assessment Items (Tugas 1, UH 1, STS 1 per Mapel per Periode)
        Schema::create('assessment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_component_id')->constrained('assessment_components')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->string('semester', 20)->default('Ganjil');
            $table->string('kelas', 50)->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('title', 150);
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->foreignId('tugas_id')->nullable()->constrained('tugas')->nullOnDelete(); // direct link to LMS!
            $table->string('cbt_exam_external_id', 100)->nullable(); // direct link to CBT!
            $table->timestamps();

            $table->index(['mata_pelajaran_id', 'tahun_ajaran_id', 'semester'], 'idx_ai_mapel_ta_sem');
        });

        // 3. Import Batches (Excel & CBT API tracking)
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 100)->unique();
            $table->enum('source_type', ['excel', 'cbt_api'])->default('excel');
            $table->string('file_name', 255)->nullable();
            $table->foreignId('mata_pelajaran_id')->nullable()->constrained('mata_pelajaran')->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajaran')->nullOnDelete();
            $table->string('semester', 20)->nullable();
            $table->string('kelas', 50)->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status', 50)->default('preview'); // preview, committed, failed
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Raw CBT Results (Raw snapshot)
        Schema::create('cbt_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->cascadeOnDelete();
            $table->string('source_type', 50)->default('excel'); // excel, cbt_api
            $table->string('source_record_id', 100)->nullable();
            $table->string('exam_external_id', 100)->nullable();
            $table->string('exam_name', 255)->nullable();
            $table->string('student_nisn', 50)->nullable();
            $table->string('student_name', 255)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->string('subject_name', 150)->nullable();
            $table->decimal('score', 5, 2)->default(0.00);
            $table->string('status', 50)->default('finalized');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['student_nisn', 'exam_external_id']);
        });

        // 5. Student Assessment Scores (Buku Nilai Detail Siswa)
        Schema::create('student_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('assessment_item_id')->constrained('assessment_items')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->enum('source_type', ['manual', 'lms', 'cbt_api', 'cbt_excel'])->default('manual');
            $table->string('source_ref_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['siswa_id', 'assessment_item_id'], 'unique_student_item_score');
        });

        // 6. Subject Grade Summaries (Agregasi Rata-rata Komponen & Nilai Rapor Mapel)
        Schema::create('subject_grade_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->string('semester', 20)->default('Ganjil');
            $table->decimal('uh_avg', 5, 2)->nullable();
            $table->decimal('tugas_avg', 5, 2)->nullable();
            $table->decimal('praktek_avg', 5, 2)->nullable();
            $table->decimal('projek_avg', 5, 2)->nullable();
            $table->decimal('portofolio_avg', 5, 2)->nullable();
            $table->decimal('sts_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('status', 50)->default('belum_ada_nilai'); // belum_ada_nilai, belum_lengkap, siap_ditinjau, final, published
            $table->timestamps();

            $table->unique(['siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id', 'semester'], 'unique_subject_summary');
        });

        // 7. Student STS Summaries (Total & Rata-rata STS per Siswa untuk Peringkat)
        Schema::create('student_sts_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->string('semester', 20)->default('Ganjil');
            $table->decimal('total_score', 7, 2)->nullable();
            $table->decimal('average_score', 5, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->unsignedInteger('completed_subjects_count')->default(0);
            $table->unsignedInteger('total_subjects_count')->default(0);
            $table->string('status', 50)->default('draft');
            $table->timestamps();

            $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester'], 'unique_student_sts_summary');
        });

        // 8. Report Finalizations (Approval dan Finalisasi Wali Kelas/Kepsek per Rombel)
        Schema::create('report_finalizations', function (Blueprint $table) {
            $table->id();
            $table->string('kelas', 50);
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->string('semester', 20)->default('Ganjil');
            $table->string('status', 50)->default('draft'); // draft, submitted, approved, published
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kelas', 'tahun_ajaran_id', 'semester'], 'unique_class_report_finalization');
        });

        // 9. E-Rapor Audit Logs
        Schema::create('erapor_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // create, update, delete, import, sync_cbt, finalize, publish
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->text('reason')->nullable();
            $table->string('request_id', 100)->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erapor_audit_logs');
        Schema::dropIfExists('report_finalizations');
        Schema::dropIfExists('student_sts_summaries');
        Schema::dropIfExists('subject_grade_summaries');
        Schema::dropIfExists('student_assessment_scores');
        Schema::dropIfExists('cbt_results');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('assessment_items');
        Schema::dropIfExists('assessment_components');
    }
};
