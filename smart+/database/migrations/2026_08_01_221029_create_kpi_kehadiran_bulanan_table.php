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
        Schema::create('kpi_kehadiran_bulanan', function (Blueprint $table) {
            $table->id();
            $table->morphs('kpiable'); // kpiable_id, kpiable_type
            $table->integer('bulan');
            $table->integer('tahun');
            $table->integer('target_hari')->default(0);
            $table->timestamps();
            
            $table->unique(['kpiable_id', 'kpiable_type', 'bulan', 'tahun'], 'kpi_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_kehadiran_bulanan');
    }
};
