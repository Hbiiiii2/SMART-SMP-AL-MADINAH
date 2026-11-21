<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_akademik_id')->constrained('staff_akademik')->onDelete('cascade');
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasi')->nullOnDelete();
            $table->date('tanggal');
            $table->dateTime('waktu_check_in')->nullable();
            $table->dateTime('waktu_check_out')->nullable();
            $table->enum('status', ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alfa'])->default('Alfa');
            $table->text('keterangan')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('foto_lokasi')->nullable();
            $table->timestamps();

            $table->unique(['staff_akademik_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_staff');
    }
};
