<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->index(['exam_id', 'status'], 'idx_attempts_exam_status');
            $table->index(['status', 'updated_at'], 'idx_attempts_status_updated');
            $table->index(['siswa_id', 'status'], 'idx_attempts_siswa_status');
            $table->index('started_at', 'idx_attempts_started_at');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->index(['kelas_id', 'status', 'tanggal_mulai'], 'idx_exams_kelas_status_tgl');
            $table->index(['token_aktif', 'token_kedaluwarsa_at'], 'idx_exams_token_rotate');
            $table->index('status', 'idx_exams_status');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('idx_exams_kelas_status_tgl');
            $table->dropIndex('idx_exams_token_rotate');
            $table->dropIndex('idx_exams_status');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_attempts_exam_status');
            $table->dropIndex('idx_attempts_status_updated');
            $table->dropIndex('idx_attempts_siswa_status');
            $table->dropIndex('idx_attempts_started_at');
        });
    }
};
