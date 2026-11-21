<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_guru', function ($table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('keterangan');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('absensi_guru', function ($table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
