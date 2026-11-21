<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_sekolah', function ($table) {
            $table->enum('metode_absensi_guru', ['Kartu', 'Zonasi'])->default('Kartu')->after('sistem');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_sekolah', function ($table) {
            $table->dropColumn('metode_absensi_guru');
        });
    }
};
