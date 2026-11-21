<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->json('question_order')->nullable()->after('token_used');
            $table->decimal('nilai_pg', 5, 2)->nullable()->after('nilai_akhir');
            $table->decimal('nilai_essay', 5, 2)->nullable()->after('nilai_pg');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['question_order', 'nilai_pg', 'nilai_essay']);
        });
    }
};
