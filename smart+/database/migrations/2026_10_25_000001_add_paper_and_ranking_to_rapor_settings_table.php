<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rapor_settings')) {
            Schema::table('rapor_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('rapor_settings', 'ukuran_kertas')) {
                    $table->string('ukuran_kertas', 20)->default('A4')->after('show_fase');
                }
                if (!Schema::hasColumn('rapor_settings', 'show_ranking')) {
                    $table->boolean('show_ranking')->default(true)->after('ukuran_kertas');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_settings')) {
            Schema::table('rapor_settings', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('rapor_settings', 'ukuran_kertas')) {
                    $cols[] = 'ukuran_kertas';
                }
                if (Schema::hasColumn('rapor_settings', 'show_ranking')) {
                    $cols[] = 'show_ranking';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
