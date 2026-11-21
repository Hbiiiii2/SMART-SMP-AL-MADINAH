<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->siswa(),
            'nisn' => $this->faker->numerify('##############'),
            'nama_lengkap' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-18 years', '-13 years'),
            'alamat' => $this->faker->address(),
            'no_telepon' => $this->faker->numerify('08##########'),
            'foto' => null,
            'tahun_ajaran_id' => TahunAjaran::factory(),
        ];
    }
}
