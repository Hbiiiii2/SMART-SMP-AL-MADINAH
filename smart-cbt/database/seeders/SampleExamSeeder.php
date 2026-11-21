<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;

class SampleExamSeeder extends Seeder
{
    public function run(): void
    {
        $guru = Guru::first() ?? Guru::create([
            'nama' => 'Guru Pengampu CBT',
            'email' => 'guru@smp-almadinah.sch.id',
            'nip' => '198501012010011001',
        ]);

        $kelas7 = Kelas::where('nama_kelas', 'like', '%VII%')->first() ?? Kelas::first();
        if (!$kelas7) {
            return;
        }

        $subjects = [
            ['code' => 'STS1-MTK-7', 'name' => 'STS 1 Matematika Kelas VII', 'search' => 'Matematika'],
            ['code' => 'STS1-BIN-7', 'name' => 'STS 1 Bahasa Indonesia Kelas VII', 'search' => 'Bahasa Indonesia'],
            ['code' => 'STS1-BIG-7', 'name' => 'STS 1 Bahasa Inggris Kelas VII', 'search' => 'Bahasa Inggris'],
            ['code' => 'STS1-IPS-7', 'name' => 'STS 1 Ilmu Pengetahuan Sosial Kelas VII', 'search' => 'Ilmu Pengetahuan Sosial'],
            ['code' => 'STS1-PAI-7', 'name' => 'STS 1 Pendidikan Agama Islam Kelas VII', 'search' => 'Pendidikan Agama Islam'],
            ['code' => 'STS1-PKN-7', 'name' => 'STS 1 Pendidikan Kewarganegaraan Kelas VII', 'search' => 'Pendidikan Kewarganegaraan'],
        ];

        $siswas = Siswa::where('kelas_id', $kelas7->id)->get();
        if ($siswas->isEmpty()) {
            $siswas = Siswa::take(20)->get();
        }

        foreach ($subjects as $subConfig) {
            $mapel = MataPelajaran::where('nama', 'like', '%' . $subConfig['search'] . '%')->first();
            if (!$mapel) {
                continue;
            }

            $exam = Exam::firstOrCreate(
                ['kode_ujian' => $subConfig['code']],
                [
                    'guru_id' => $guru->id,
                    'mata_pelajaran_id' => $mapel->id,
                    'nama' => $subConfig['name'],
                    'tahun_ajaran' => '2026/2027',
                    'semester' => 'Ganjil',
                    'jenis' => 'STS 1',
                    'kelas' => $kelas7->nama_kelas,
                    'kelas_id' => $kelas7->id,
                    'durasi_menit' => 90,
                    'tanggal_mulai' => now()->subDays(5),
                    'tanggal_selesai' => now()->addDays(5),
                    'status' => 'aktif',
                ]
            );

            // Seed attempts for all students
            foreach ($siswas as $idx => $siswa) {
                // Generate consistent, realistic score per student based on student id
                $base = 75 + (($siswa->id * 7 + $mapel->id * 5) % 24); // 75 - 98
                $score = min(100, max(65, $base));

                ExamAttempt::updateOrCreate(
                    ['exam_id' => $exam->id, 'siswa_id' => $siswa->id],
                    [
                        'started_at' => now()->subDays(3)->setTime(8, 0),
                        'submitted_at' => now()->subDays(3)->setTime(9, 15),
                        'status' => 'submitted',
                        'nilai_akhir' => $score,
                        'attempt_number' => 1,
                    ]
                );
            }
        }
    }
}
