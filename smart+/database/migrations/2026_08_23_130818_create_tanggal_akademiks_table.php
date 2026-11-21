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
        Schema::create('tanggal_akademiks', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('nama_kegiatan');
            $table->text('keterangan')->nullable();
            $table->string('warna')->default('#3b82f6')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanggal_akademiks');
    }
};
