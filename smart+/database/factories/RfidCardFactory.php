<?php

namespace Database\Factories;

use App\Models\RfidCard;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class RfidCardFactory extends Factory
{
    protected $model = RfidCard::class;

    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'uid' => strtoupper($this->faker->bothify('??-####-????')),
            'is_active' => true,
        ];
    }
}
