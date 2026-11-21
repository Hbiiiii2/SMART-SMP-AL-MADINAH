<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->restrictOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->string('kelas', 20);
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->foreignId('lokasi_id')->constrained('lokasi')->restrictOnDelete();
            $table->timestamps();

            $table->index('guru_id');
            $table->index('mata_pelajaran_id');
            $table->index('tahun_ajaran_id');
            $table->index('kelas');
            $table->index('hari');
            $table->index('lokasi_id');

            $table->unique(['tahun_ajaran_id', 'kelas', 'hari', 'jam_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};
