<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalFactory extends Factory
{
    protected $model = Jadwal::class;

    public function definition(): array
    {
        $jamMulai = $this->faker->randomElement(['07:00', '08:00', '09:00', '10:00', '11:00', '13:00', '14:00']);
        $jamSelesai = [
            '07:00' => '08:30', '08:00' => '09:30', '09:00' => '10:30',
            '10:00' => '11:30', '11:00' => '12:30', '13:00' => '14:30', '14:00' => '15:30',
        ];

        return [
            'guru_id' => Guru::factory(),
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'kelas' => $this->faker->randomElement(['X-1', 'X-2', 'X-3', 'XI-1', 'XI-2', 'XI-3', 'XII-1', 'XII-2', 'XII-3']),
            'hari' => $this->faker->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai[$jamMulai],
            'lokasi_id' => Lokasi::factory(),
        ];
    }
}
