<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StaffAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@smart.sch.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'adminsekolah@smart.sch.id'],
            [
                'name' => 'Admin Sekolah',
                'password' => Hash::make('password'),
                'role' => 'admin_sekolah',
                'is_active' => true,
            ]
        );

        $kepsek = User::firstOrCreate(
            ['email' => 'kepalasekolah@smart.sch.id'],
            [
                'name' => 'Kepala Sekolah',
                'password' => Hash::make('password'),
                'role' => 'kepala_sekolah',
                'is_active' => true,
            ]
        );
        // Buat profil Staff Akademik untuk Kepala Sekolah jika belum ada
        StaffAkademik::firstOrCreate(
            ['pengguna_id' => $kepsek->id],
            [
                'nip' => '1234567890',
                'nama_lengkap' => $kepsek->name,
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1980-01-01',
                'nomor_telepon' => '081234567890',
                'alamat' => 'Alamat Kepala Sekolah',
                'status_aktif' => true,
            ]
        );
    }
}
