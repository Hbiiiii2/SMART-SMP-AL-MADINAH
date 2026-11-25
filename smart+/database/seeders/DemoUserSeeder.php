<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'demo@smart.sch.id'],
            [
                'name' => 'Akun Demo SMART+',
                'password' => Hash::make('password'),
                'role' => 'akun_demo',
                'is_active' => true,
                'expired_at' => now()->addDays(7),
            ]
        );
    }
}
