<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')
                ->constrained('answers')
                ->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')
                ->constrained('exam_attempts')
                ->cascadeOnDelete();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->restrictOnDelete();
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->char('checksum_sha256', 64)->nullable();
            $table->timestamps();

            $table->index(['answer_id', 'urutan'], 'idx_att_answer_urutan');
            $table->index(['exam_attempt_id', 'question_id', 'urutan'], 'idx_att_attempt_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_attachments');
    }
};
