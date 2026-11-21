<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_homerooms')) {
            Schema::table('class_homerooms', function (Blueprint $table) {
                if (!Schema::hasColumn('class_homerooms', 'kkm_default')) {
                    $table->decimal('kkm_default', 5, 2)->nullable()->default(75.00)->after('ttd_wali_kelas');
                }
                if (!Schema::hasColumn('class_homerooms', 'kkm_mapel')) {
                    $table->json('kkm_mapel')->nullable()->after('kkm_default');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_homerooms')) {
            Schema::table('class_homerooms', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('class_homerooms', 'kkm_default')) {
                    $cols[] = 'kkm_default';
                }
                if (Schema::hasColumn('class_homerooms', 'kkm_mapel')) {
                    $cols[] = 'kkm_mapel';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
