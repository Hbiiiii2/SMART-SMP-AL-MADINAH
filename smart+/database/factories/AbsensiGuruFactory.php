<?php

namespace Database\Factories;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiGuruFactory extends Factory
{
    protected $model = AbsensiGuru::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['Hadir', 'Terlambat', 'Alpha', 'Sakit', 'Izin']);
        $jamMasuk = match ($status) {
            'Hadir' => $this->faker->time('H:i', '07:30'),
            'Terlambat' => $this->faker->time('H:i', '08:30'),
            default => null,
        };

        return [
            'guru_id' => Guru::factory(),
            'tanggal' => $this->faker->dateTimeThisMonth(),
            'jam_masuk' => $jamMasuk,
            'jam_keluar' => $status === 'Hadir' || $status === 'Terlambat' ? $this->faker->time('H:i', '15:30') : null,
            'status' => $status,
            'keterangan' => $status === 'Sakit' || $status === 'Izin' ? $this->faker->sentence() : null,
        ];
    }
}
