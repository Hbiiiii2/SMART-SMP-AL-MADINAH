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
        Schema::table('tugas', function (Blueprint $table) {
            $table->string('tipe_pengumpulan')->default('file')->after('deskripsi');
        });
        
        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->string('link_url')->nullable()->after('siswa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->dropColumn('tipe_pengumpulan');
        });

        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->dropColumn('link_url');
        });
    }
};
