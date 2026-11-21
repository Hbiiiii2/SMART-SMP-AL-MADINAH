<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuruFactory extends Factory
{
    protected $model = Guru::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->guru(),
            'nip' => $this->faker->numerify('##########'),
            'nama_lengkap' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-50 years', '-25 years'),
            'alamat' => $this->faker->address(),
            'no_telepon' => $this->faker->numerify('08##########'),
            'foto' => null,
        ];
    }
}
