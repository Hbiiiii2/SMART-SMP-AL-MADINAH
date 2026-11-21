<?php

namespace Database\Factories;

use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

class TahunAjaranFactory extends Factory
{
    protected $model = TahunAjaran::class;

    public function definition(): array
    {
        $year = $this->faker->unique()->numberBetween(1970, 2099);
        return [
            'nama' => $year . '/' . ($year + 1),
            'tanggal_mulai' => "$year-07-01",
            'tanggal_selesai' => ($year + 1) . "-06-30",
            'is_aktif' => false,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn () => [
            'is_aktif' => true,
        ]);
    }
}
