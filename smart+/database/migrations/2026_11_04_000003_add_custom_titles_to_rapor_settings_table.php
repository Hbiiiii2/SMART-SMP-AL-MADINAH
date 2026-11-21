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
                if (!Schema::hasColumn('rapor_settings', 'tipe_judul_rapor')) {
                    $table->string('tipe_judul_rapor', 20)->default('template')->after('nama_kurikulum');
                }
                if (!Schema::hasColumn('rapor_settings', 'judul_rapor_sts')) {
                    $table->string('judul_rapor_sts', 191)->nullable()->after('tipe_judul_rapor');
                }
                if (!Schema::hasColumn('rapor_settings', 'judul_rapor_akhir')) {
                    $table->string('judul_rapor_akhir', 191)->nullable()->after('judul_rapor_sts');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rapor_settings')) {
            Schema::table('rapor_settings', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('rapor_settings', 'tipe_judul_rapor')) {
                    $cols[] = 'tipe_judul_rapor';
                }
                if (Schema::hasColumn('rapor_settings', 'judul_rapor_sts')) {
                    $cols[] = 'judul_rapor_sts';
                }
                if (Schema::hasColumn('rapor_settings', 'judul_rapor_akhir')) {
                    $cols[] = 'judul_rapor_akhir';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
