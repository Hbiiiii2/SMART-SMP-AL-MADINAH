<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin_sekolah','kepala_sekolah','direktur','guru','staff_akademik','siswa') DEFAULT 'siswa'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','admin_sekolah','kepala_sekolah','guru','siswa') DEFAULT 'siswa'");
        }
    }
};
