<?php

namespace Database\Factories;

use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class LokasiFactory extends Factory
{
    protected $model = Lokasi::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->randomElement([
                'Gedung A', 'Gedung B', 'Gedung C',
                'Lab Komputer', 'Perpustakaan', 'Aula',
                'Ruang Guru', 'Halaman Depan',
            ]),
            'deskripsi' => $this->faker->sentence(),
            'latitude' => $this->faker->latitude(-6.2, -6.1),
            'longitude' => $this->faker->longitude(106.7, 106.9),
            'radius' => $this->faker->randomFloat(2, 50, 200),
        ];
    }
}
