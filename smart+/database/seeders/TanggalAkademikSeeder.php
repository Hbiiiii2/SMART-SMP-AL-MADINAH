<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TanggalAkademikSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\TanggalAkademik::truncate();
        
        $now = \Carbon\Carbon::now('Asia/Jakarta');

        $agendas = [
            [
                'tanggal' => $now->copy()->addDays(2)->format('Y-m-d'),
                'tanggal_selesai' => $now->copy()->addDays(5)->format('Y-m-d'),
                'nama_kegiatan' => 'Ujian Tengah Semester',
                'keterangan' => 'Pelaksanaan UTS Semester Ganjil',
                'warna' => '#3b82f6',
            ],
            [
                'tanggal' => $now->copy()->addDays(10)->format('Y-m-d'),
                'tanggal_selesai' => null,
                'nama_kegiatan' => 'Class Meeting',
                'keterangan' => 'Lomba antar kelas setelah UTS',
                'warna' => '#a855f7',
            ],
            [
                'tanggal' => $now->copy()->subDays(5)->format('Y-m-d'),
                'tanggal_selesai' => $now->copy()->subDays(3)->format('Y-m-d'),
                'nama_kegiatan' => 'Rapat Wali Murid',
                'keterangan' => 'Sosialisasi program sekolah',
                'warna' => '#10b981',
            ],
        ];

        foreach ($agendas as $agenda) {
            \App\Models\TanggalAkademik::create($agenda);
        }
    }
}
