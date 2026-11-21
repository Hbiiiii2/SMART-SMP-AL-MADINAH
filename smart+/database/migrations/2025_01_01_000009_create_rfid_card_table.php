<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_card', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->string('uid', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('uid');
            $table->index('siswa_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_card');
    }
};
