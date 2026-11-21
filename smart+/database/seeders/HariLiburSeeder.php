<?php

namespace Database\Seeders;

use App\Models\HariLibur;
use Illuminate\Database\Seeder;

class HariLiburSeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['tanggal' => '2026-01-01', 'nama' => 'Tahun Baru Masehi', 'keterangan' => 'Libur nasional', 'is_recurring' => true],
            ['tanggal' => '2026-01-29', 'nama' => 'Isra Mi\'raj', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-03-20', 'nama' => 'Tahun Baru Islam 1448 H', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-03-29', 'nama' => 'Wafat Isa Almasih', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-03-30', 'nama' => 'Isra Mi\'raj (Cuti Bersama)', 'keterangan' => 'Cuti bersama', 'is_recurring' => false],
            ['tanggal' => '2026-04-02', 'nama' => 'Hari Raya Nyepi', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-04-03', 'nama' => 'Hari Raya Waisak', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-04-18', 'nama' => 'Wafat Isa Almasih (Cuti Bersama)', 'keterangan' => 'Cuti bersama', 'is_recurring' => false],
            ['tanggal' => '2026-04-20', 'nama' => 'Hari Raya Idul Fitri 1447 H', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-04-21', 'nama' => 'Cuti Bersama Idul Fitri', 'keterangan' => 'Cuti bersama', 'is_recurring' => false],
            ['tanggal' => '2026-05-01', 'nama' => 'Hari Buruh Internasional', 'keterangan' => 'Hari libur nasional', 'is_recurring' => true],
            ['tanggal' => '2026-05-14', 'nama' => 'Kenaikan Isa Almasih', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-05-26', 'nama' => 'Hari Raya Waisak', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-06-01', 'nama' => 'Hari Lahir Pancasila', 'keterangan' => 'Hari libur nasional', 'is_recurring' => true],
            ['tanggal' => '2026-06-07', 'nama' => 'Hari Raya Idul Adha 1447 H', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-06-27', 'nama' => 'Tahun Baru Islam 1448 H', 'keterangan' => 'Hari libur nasional', 'is_recurring' => false],
            ['tanggal' => '2026-08-17', 'nama' => 'Hari Kemerdekaan RI', 'keterangan' => 'Hari libur nasional', 'is_recurring' => true],
        ];

        foreach ($holidays as $holiday) {
            HariLibur::firstOrCreate(
                ['tanggal' => $holiday['tanggal']],
                $holiday
            );
        }
    }
}
