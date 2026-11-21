<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. General report card formatting settings
        Schema::create('rapor_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kurikulum')->default('Kurikulum Merdeka');
            $table->boolean('show_kurikulum')->default(true);
            $table->boolean('show_fase')->default(false); // default false: no "Fase / D"
            $table->string('fase_text')->nullable()->default('Fase D');
            $table->string('kota_pembagian')->default('Bogor');
            $table->date('tanggal_rapor')->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nip_kepala_sekolah')->nullable();
            $table->string('ttd_kepala_sekolah')->nullable();
            $table->string('stempel_sekolah')->nullable();
            $table->string('header_yayasan')->nullable()->default('YAYASAN KELUARGA BESAR MASYARAKAT BANTEN (KKMB)');
            $table->string('header_sekolah')->nullable()->default('SMP AL - MADINAH ISLAMIC CENTER');
            $table->timestamps();
        });

        // 2. Class homeroom teacher mapping & signature per class
        Schema::create('class_homerooms', function (Blueprint $table) {
            $table->id();
            $table->string('kelas');
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('guru')->nullOnDelete();
            $table->string('nama_wali_kelas')->nullable();
            $table->string('nip_wali_kelas')->nullable();
            $table->string('ttd_wali_kelas')->nullable();
            $table->timestamps();

            $table->unique(['kelas', 'tahun_ajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_homerooms');
        Schema::dropIfExists('rapor_settings');
    }
};
