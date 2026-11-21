<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Harden answers -> questions foreign key to restrict on delete
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign('answers_question_id_foreign');
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->restrictOnDelete();
        });

        // 2. Harden exams -> gurus foreign key to restrict on delete
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign('exams_guru_id_foreign');
            $table->foreign('guru_id')
                ->references('id')
                ->on('gurus')
                ->restrictOnDelete();
        });

        // 3. Update exam_attempts unique index to support multiple attempts (remedial/retake)
        // In MySQL, exam_attempts_exam_id_foreign relies on this unique index as its supporting index.
        // We must drop the FK, update the unique index, and restore the FK.
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropForeign('exam_attempts_exam_id_foreign');
            $table->dropUnique('exam_attempts_exam_id_siswa_id_unique');
            $table->unique(['exam_id', 'siswa_id', 'attempt_number'], 'exam_attempts_exam_siswa_attempt_unique');
            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->dropUnique('exam_attempts_exam_siswa_attempt_unique');
            $table->unique(['exam_id', 'siswa_id'], 'exam_attempts_exam_id_siswa_id_unique');
            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->cascadeOnDelete();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->foreign('guru_id')
                ->references('id')
                ->on('gurus')
                ->cascadeOnDelete();
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();
        });
    }
};
