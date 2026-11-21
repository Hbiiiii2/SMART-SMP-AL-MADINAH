<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah');
            $table->text('alamat_sekolah')->nullable();
            $table->string('no_telepon_sekolah', 20)->nullable();
            $table->string('email_sekolah')->nullable();
            $table->string('logo')->nullable();
            $table->time('jam_masuk')->default('07:00');
            $table->time('jam_pulang')->default('15:00');
            $table->unsignedSmallInteger('toleransi_terlambat_menit')->default(15);
            $table->decimal('radius_absensi_meter', 8, 2)->default(100.00);
            $table->enum('sistem', ['RFID', 'Manual', 'Hybrid'])->default('RFID');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_sekolah');
    }
};
