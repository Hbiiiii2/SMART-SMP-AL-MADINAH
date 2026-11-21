<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Question;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LoadTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Teacher
        $teacherUser = User::firstOrCreate(
            ['email' => 'guru_loadtest@test.local'],
            [
                'name' => 'Guru Simulasi Beban',
                'password' => Hash::make('password123'),
                'role' => 'guru',
            ]
        );

        $guru = Guru::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'nama' => 'Guru Simulasi Beban',
                'kode_guru' => 'G-LOAD-01',
            ]
        );

        // 2. Create Subject & Class
        $mapel = MataPelajaran::firstOrCreate(
            ['kode' => 'LOAD-01'],
            ['nama' => 'Simulasi Beban CBT']
        );

        $kelas = Kelas::firstOrCreate(
            ['nama_kelas' => 'IX - LOADTEST'],
            ['tingkat' => 'IX', 'status' => 'aktif']
        );

        // 3. Create 300 Test Students for Advanced Capacity Testing
        $hashedPassword = Hash::make('password123');
        for ($i = 1; $i <= 300; $i++) {
            $pad = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $email = "loadtest_siswa_{$pad}@test.local";
            $nis = "LT{$pad}";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Siswa Load Test {$pad}",
                    'password' => $hashedPassword,
                    'role' => 'siswa',
                ]
            );

            Siswa::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nis' => $nis,
                    'nisn' => "009988{$pad}",
                    'nama' => "Siswa Load Test {$pad}",
                    'kelas_id' => $kelas->id,
                    'kelas' => $kelas->nama_kelas,
                    'status_aktif' => true,
                ]
            );
        }

        // 4. Create or Update Exam
        $exam = Exam::updateOrCreate(
            ['token' => 'LOAD99'],
            [
                'guru_id' => $guru->id,
                'mata_pelajaran_id' => $mapel->id,
                'kode_ujian' => 'LOADTEST-300',
                'nama' => 'Ujian Simulasi Beban 300 Siswa',
                'kelas_id' => $kelas->id,
                'kelas' => $kelas->nama_kelas,
                'durasi_menit' => 120,
                'status' => 'aktif',
                'tanggal_mulai' => now()->subHour(),
                'tanggal_selesai' => now()->addHours(6),
                'token_aktif' => true,
                'acak_soal' => false,
            ]
        );

        // 5. Create 10 Questions
        if ($exam->questions()->count() === 0) {
            for ($q = 1; $q <= 8; $q++) {
                Question::create([
                    'exam_id' => $exam->id,
                    'tipe' => 'pg',
                    'pertanyaan' => "<p>Soal Pilihan Ganda {$q}: Manakah pernyataan yang paling tepat mengenai optimasi sistem CBT?</p>",
                    'opsi_a' => 'A. Menggunakan arsitektur stateless dengan Redis caching',
                    'opsi_b' => 'B. Menggunakan polling tanpa interval backoff',
                    'opsi_c' => 'C. Menyimpan seluruh sesi pada database tanpa indeks',
                    'opsi_d' => 'D. Menghapus foreign key constraints',
                    'opsi_e' => 'E. Menonaktifkan rate limiting',
                    'kunci' => 'A',
                    'bobot' => 10,
                    'urutan' => $q,
                ]);
            }

            for ($e = 1; $e <= 2; $e++) {
                Question::create([
                    'exam_id' => $exam->id,
                    'tipe' => 'essay_1',
                    'pertanyaan' => "<p>Soal Essay {$e}: Jelaskan prinsip monotonic sequence number pada protokol autosave CBT!</p>",
                    'kunci' => null,
                    'bobot' => 10,
                    'urutan' => 8 + $e,
                ]);
            }
        }
    }
}
