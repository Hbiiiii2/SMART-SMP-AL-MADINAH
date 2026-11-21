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
                if (!Schema::hasColumn('rapor_settings', 'show_kkm')) {
                    $table->boolean('show_kkm')->default(false)->after('show_ranking');
                }
                if (!Schema::hasColumn('rapor_settings', 'default_kkm')) {
                    $table->decimal('default_kkm', 5, 2)->default(75.00)->after('show_kkm');
                }
            });
        }

        if (Schema::hasTable('mata_pelajaran')) {
            Schema::table('mata_pelajaran', function (Blueprint $table) {
                if (!Schema::hasColumn('mata_pelajaran', 'kkm')) {
                    $table->decimal('kkm', 5, 2)->nullable()->default(75.00)->after('nama');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_settings')) {
            Schema::table('rapor_settings', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('rapor_settings', 'show_kkm')) {
                    $cols[] = 'show_kkm';
                }
                if (Schema::hasColumn('rapor_settings', 'default_kkm')) {
                    $cols[] = 'default_kkm';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('mata_pelajaran')) {
            Schema::table('mata_pelajaran', function (Blueprint $table) {
                if (Schema::hasColumn('mata_pelajaran', 'kkm')) {
                    $table->dropColumn('kkm');
                }
            });
        }
    }
};
