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
        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            $table->dropColumn('sistem');
            $table->dropColumn('metode_absensi_guru');
        });

        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            $table->enum('metode_absensi_siswa', ['RFID', 'Zonasi'])->default('RFID')->after('radius_absensi_meter');
            $table->enum('metode_absensi_guru', ['RFID', 'Hybrid', 'Zonasi'])->default('RFID')->after('metode_absensi_siswa');
            $table->enum('metode_absensi_staff', ['RFID', 'Hybrid', 'Zonasi'])->default('RFID')->after('metode_absensi_guru');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            $table->dropColumn('metode_absensi_siswa');
            $table->dropColumn('metode_absensi_guru');
            $table->dropColumn('metode_absensi_staff');
        });

        Schema::table('pengaturan_sekolah', function (Blueprint $table) {
            $table->enum('sistem', ['RFID', 'Manual', 'Hybrid'])->default('RFID');
            $table->enum('metode_absensi_guru', ['Kartu', 'Zonasi'])->default('Kartu');
        });
    }
};
