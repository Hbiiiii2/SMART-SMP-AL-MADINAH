<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_card', function (Blueprint $table) {
            $table->foreignId('guru_id')->nullable()->after('siswa_id')->constrained('guru')->nullOnDelete();
            $table->index('guru_id');
        });
    }

    public function down(): void
    {
        Schema::table('rfid_card', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->dropIndex(['guru_id']);
            $table->dropColumn('guru_id');
        });
    }
};
