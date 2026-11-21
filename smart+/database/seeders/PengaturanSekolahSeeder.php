<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use Illuminate\Database\Seeder;

class PengaturanSekolahSeeder extends Seeder
{
    public function run(): void
    {
        PengaturanSekolah::firstOrCreate([], [
            'nama_sekolah' => 'SMKN 1 Jakarta',
            'alamat_sekolah' => 'Jl. Pendidikan No. 1, Jakarta Selatan',
            'no_telepon_sekolah' => '021-5551234',
            'email_sekolah' => 'info@smkn1jakarta.sch.id',
            'logo' => null,
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'toleransi_terlambat_menit' => 15,
            'toleransi_alpha_menit' => 30,
            'radius_absensi_meter' => 100.00,
            'metode_absensi_siswa' => 'RFID',
            'metode_absensi_guru' => 'RFID',
            'metode_absensi_staff' => 'RFID',
        ]);

        TahunAjaran::create([
            'nama' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'is_aktif' => true,
        ]);

        TahunAjaran::create([
            'nama' => '2024/2025',
            'tanggal_mulai' => '2024-07-01',
            'tanggal_selesai' => '2025-06-30',
            'is_aktif' => false,
        ]);

        $lokasis = [
            ['nama' => 'Gedung A', 'deskripsi' => 'Gedung utama lantai 1-3', 'latitude' => -6.2297, 'longitude' => 106.8213, 'radius' => 100],
            ['nama' => 'Gedung B', 'deskripsi' => 'Gedung laboratorium', 'latitude' => -6.2298, 'longitude' => 106.8215, 'radius' => 100],
            ['nama' => 'Lab Komputer', 'deskripsi' => 'Laboratorium komputer lantai 2', 'latitude' => -6.2299, 'longitude' => 106.8214, 'radius' => 50],
            ['nama' => 'Perpustakaan', 'deskripsi' => 'Perpustakaan sekolah', 'latitude' => -6.2296, 'longitude' => 106.8216, 'radius' => 75],
            ['nama' => 'Aula', 'deskripsi' => 'Aula serbaguna', 'latitude' => -6.2295, 'longitude' => 106.8212, 'radius' => 150],
        ];

        foreach ($lokasis as $lokasi) {
            Lokasi::create($lokasi);
        }

        $mataPelajarans = [
            ['kode' => 'MTK', 'nama' => 'Matematika', 'deskripsi' => 'Mata pelajaran Matematika'],
            ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam', 'deskripsi' => 'Mata pelajaran IPA'],
            ['kode' => 'IPS', 'nama' => 'Ilmu Pengetahuan Sosial', 'deskripsi' => 'Mata pelajaran IPS'],
            ['kode' => 'BHS', 'nama' => 'Bahasa Indonesia', 'deskripsi' => 'Mata pelajaran Bahasa Indonesia'],
            ['kode' => 'ING', 'nama' => 'Bahasa Inggris', 'deskripsi' => 'Mata pelajaran Bahasa Inggris'],
            ['kode' => 'PKN', 'nama' => 'Pendidikan Kewarganegaraan', 'deskripsi' => 'Mata pelajaran PKN'],
            ['kode' => 'PJOK', 'nama' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'deskripsi' => 'Mata pelajaran PJOK'],
            ['kode' => 'SBY', 'nama' => 'Seni Budaya', 'deskripsi' => 'Mata pelajaran Seni Budaya'],
            ['kode' => 'TIK', 'nama' => 'Teknologi Informasi dan Komunikasi', 'deskripsi' => 'Mata pelajaran TIK'],
            ['kode' => 'AKL', 'nama' => 'Akuntansi', 'deskripsi' => 'Mata pelajaran Akuntansi'],
        ];

        foreach ($mataPelajarans as $mp) {
            MataPelajaran::create($mp);
        }
    }
}
