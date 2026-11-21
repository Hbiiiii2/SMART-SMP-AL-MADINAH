<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['Hadir', 'Terlambat', 'Alfa', 'Sakit', 'Izin']);
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('diedit_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alasan_perubahan')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'jadwal_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('status');
            $table->index('jadwal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswa');
    }
};
