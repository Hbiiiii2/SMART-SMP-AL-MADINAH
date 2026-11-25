<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'role' => 'super_admin',
        ]);
    }

    public function adminSekolah(): static
    {
        return $this->state(fn () => [
            'role' => 'admin_sekolah',
        ]);
    }

    public function guru(): static
    {
        return $this->state(fn () => [
            'role' => 'guru',
        ]);
    }

    public function siswa(): static
    {
        return $this->state(fn () => [
            'role' => 'siswa',
        ]);
    }

    public function akunDemo(?\Carbon\CarbonInterface $expiredAt = null): static
    {
        return $this->state(fn () => [
            'role' => 'akun_demo',
            'expired_at' => $expiredAt ?? now()->addDays(7),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
