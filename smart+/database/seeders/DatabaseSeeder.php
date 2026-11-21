<?php

namespace Database\Seeders;

use Database\Seeders\AdminSeeder;
use Database\Seeders\HariLiburSeeder;
use Database\Seeders\PengaturanSekolahSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PengaturanSekolahSeeder::class,
            // TestDataSeeder::class,
            // AttendanceSeeder::class,
            HariLiburSeeder::class,
        ]);
    }
}
