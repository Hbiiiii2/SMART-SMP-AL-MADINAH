<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Guru;
use App\Models\StaffAkademik;
use App\Models\AbsensiGuru;
use App\Models\AbsensiStaff;
use App\Models\User;
use Carbon\Carbon;

class MapAbsensiSeeder extends Seeder
{
    public function run()
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // 1. Guru
        $gurus = Guru::take(3)->get();
        $latGuru = -6.2080;
        $lngGuru = 106.8450;
        foreach ($gurus as $guru) {
            AbsensiGuru::updateOrCreate(
                ['guru_id' => $guru->id, 'tanggal' => $today],
                [
                    'jam_masuk' => Carbon::now('Asia/Jakarta')->subHours(rand(1, 3))->format('H:i:s'),
                    'status' => 'Hadir',
                    'latitude' => $latGuru + (rand(-10, 10) / 10000),
                    'longitude' => $lngGuru + (rand(-10, 10) / 10000),
                ]
            );
        }

        // 2. Staff Akademik (bukan Kepala Sekolah)
        $staffs = StaffAkademik::whereHas('user', function($q){
            $q->where('role', 'staff_akademik');
        })->take(3)->get();
        $latStaff = -6.2100;
        $lngStaff = 106.8480;
        foreach ($staffs as $staff) {
            AbsensiStaff::updateOrCreate(
                ['staff_akademik_id' => $staff->id, 'tanggal' => $today],
                [
                    'waktu_check_in' => Carbon::now('Asia/Jakarta')->subHours(rand(1, 3)),
                    'status' => 'Hadir',
                    'latitude' => $latStaff + (rand(-10, 10) / 10000),
                    'longitude' => $lngStaff + (rand(-10, 10) / 10000),
                ]
            );
        }

        // 3. Kepala Sekolah
        $kepsek = StaffAkademik::whereHas('user', function($q){
            $q->where('role', 'kepala_sekolah');
        })->first();

        if ($kepsek) {
            AbsensiStaff::updateOrCreate(
                ['staff_akademik_id' => $kepsek->id, 'tanggal' => $today],
                [
                    'waktu_check_in' => Carbon::now('Asia/Jakarta')->subHours(rand(1, 3)),
                    'status' => 'Hadir',
                    'latitude' => -6.2088,
                    'longitude' => 106.8456,
                ]
            );
        }
    }
}
