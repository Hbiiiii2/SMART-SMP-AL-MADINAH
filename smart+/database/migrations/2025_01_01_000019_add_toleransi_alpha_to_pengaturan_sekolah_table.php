<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_sekolah', function ($table) {
            $table->unsignedSmallInteger('toleransi_alpha_menit')->after('toleransi_terlambat_menit')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_sekolah', function ($table) {
            $table->dropColumn('toleransi_alpha_menit');
        });
    }
};
