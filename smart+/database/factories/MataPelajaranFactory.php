<?php

namespace Database\Factories;

use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

class MataPelajaranFactory extends Factory
{
    protected $model = MataPelajaran::class;

    private static $mataPelajaran = [
        ['kode' => 'MTK', 'nama' => 'Matematika'],
        ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam'],
        ['kode' => 'IPS', 'nama' => 'Ilmu Pengetahuan Sosial'],
        ['kode' => 'BHS', 'nama' => 'Bahasa Indonesia'],
        ['kode' => 'ING', 'nama' => 'Bahasa Inggris'],
        ['kode' => 'PKN', 'nama' => 'Pendidikan Kewarganegaraan'],
        ['kode' => 'PJOK', 'nama' => 'Pendidikan Jasmani Olahraga dan Kesehatan'],
        ['kode' => 'SBY', 'nama' => 'Seni Budaya'],
        ['kode' => 'TIK', 'nama' => 'Teknologi Informasi dan Komunikasi'],
        ['kode' => 'AKL', 'nama' => 'Akuntansi'],
    ];

    private static int $index = 0;

    public function definition(): array
    {
        $mp = self::$mataPelajaran[self::$index % count(self::$mataPelajaran)];
        self::$index++;

        return [
            'kode' => $mp['kode'],
            'nama' => $mp['nama'],
            'deskripsi' => $this->faker->sentence(),
        ];
    }
}
