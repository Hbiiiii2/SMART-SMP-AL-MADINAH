<?php

namespace Database\Factories;

use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiSiswaFactory extends Factory
{
    protected $model = AbsensiSiswa::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['Hadir', 'Terlambat', 'Alfa', 'Sakit', 'Izin']);
        $jamMasuk = match ($status) {
            'Hadir' => $this->faker->time('H:i', '07:15'),
            'Terlambat' => $this->faker->time('H:i', '07:45'),
            default => null,
        };

        return [
            'siswa_id' => Siswa::factory(),
            'jadwal_id' => Jadwal::factory(),
            'tanggal' => $this->faker->dateTimeThisMonth(),
            'status' => $status,
            'jam_masuk' => $jamMasuk,
            'jam_keluar' => in_array($status, ['Hadir', 'Terlambat']) ? $this->faker->time('H:i', '15:30') : null,
            'keterangan' => null,
            'diedit_oleh' => null,
            'alasan_perubahan' => null,
        ];
    }
}
