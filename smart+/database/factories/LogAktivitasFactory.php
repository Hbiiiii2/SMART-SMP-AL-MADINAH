<?php

namespace Database\Factories;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LogAktivitasFactory extends Factory
{
    protected $model = LogAktivitas::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'aksi' => $this->faker->randomElement([
                'login', 'logout', 'create', 'update', 'delete',
                'view_attendance', 'export_report', 'change_password',
            ]),
            'deskripsi' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
