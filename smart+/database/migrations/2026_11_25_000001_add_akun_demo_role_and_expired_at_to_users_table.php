<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('is_active');
            $table->index('expired_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin_sekolah','kepala_sekolah','direktur','guru','staff_akademik','siswa','akun_demo') DEFAULT 'siswa'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin_sekolah','kepala_sekolah','direktur','guru','staff_akademik','siswa') DEFAULT 'siswa'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['expired_at']);
            $table->dropColumn('expired_at');
        });
    }
};
