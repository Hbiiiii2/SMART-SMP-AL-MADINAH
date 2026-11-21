<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Riwayat Kelas & Angkatan Siswa (Permanent Cohort & Enrollment History)
        if (!Schema::hasTable('riwayat_kelas_siswa')) {
            Schema::create('riwayat_kelas_siswa', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
                $table->string('semester', 20)->default('Ganjil');
                $table->string('kelas', 50);
                $table->string('status', 50)->default('aktif'); // aktif, naik_kelas, tinggal_kelas, lulus, alumni, mutasi
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester'], 'unique_riwayat_siswa_ta_sem');
                $table->index(['kelas', 'tahun_ajaran_id', 'semester'], 'idx_riwayat_kelas_ta_sem');
            });
        }

        // 2. Add permanent 'kelas' snapshot column to subject_grade_summaries
        if (Schema::hasTable('subject_grade_summaries')) {
            Schema::table('subject_grade_summaries', function (Blueprint $table) {
                if (!Schema::hasColumn('subject_grade_summaries', 'kelas')) {
                    $table->string('kelas', 50)->nullable()->after('semester');
                    $table->index(['kelas', 'tahun_ajaran_id', 'semester'], 'idx_sgs_kelas_ta_sem');
                }
            });
        }

        // 3. Add permanent 'kelas' snapshot column to student_sts_summaries
        if (Schema::hasTable('student_sts_summaries')) {
            Schema::table('student_sts_summaries', function (Blueprint $table) {
                if (!Schema::hasColumn('student_sts_summaries', 'kelas')) {
                    $table->string('kelas', 50)->nullable()->after('semester');
                    $table->index(['kelas', 'tahun_ajaran_id', 'semester'], 'idx_sts_kelas_ta_sem');
                }
            });
        }

        // 4. Populate current active students into riwayat_kelas_siswa as baseline
        $activeYear = \App\Models\TahunAjaran::where('is_aktif', true)->first();
        if ($activeYear && Schema::hasTable('siswa')) {
            $students = \App\Models\Siswa::whereNotNull('kelas')->where('kelas', '!=', '')->get();
            foreach ($students as $siswa) {
                foreach (['Ganjil', 'Genap'] as $sem) {
                    \DB::table('riwayat_kelas_siswa')->updateOrInsert(
                        [
                            'siswa_id' => $siswa->id,
                            'tahun_ajaran_id' => $activeYear->id,
                            'semester' => $sem,
                        ],
                        [
                            'kelas' => $siswa->kelas,
                            'status' => 'aktif',
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswa');

        if (Schema::hasTable('subject_grade_summaries') && Schema::hasColumn('subject_grade_summaries', 'kelas')) {
            Schema::table('subject_grade_summaries', function (Blueprint $table) {
                $table->dropColumn('kelas');
            });
        }

        if (Schema::hasTable('student_sts_summaries') && Schema::hasColumn('student_sts_summaries', 'kelas')) {
            Schema::table('student_sts_summaries', function (Blueprint $table) {
                $table->dropColumn('kelas');
            });
        }
    }
};
