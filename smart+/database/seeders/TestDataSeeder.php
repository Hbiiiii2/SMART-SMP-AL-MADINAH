<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\RfidCard;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $ta = TahunAjaran::where('is_aktif', true)->first();
        $lokasi = Lokasi::first();

        $guruData = [
            ['nip' => '197801152005011001', 'nama' => 'Dr. Budi Santoso, M.Pd.', 'email' => 'budi@smart.sch.id', 'jk' => 'L', 'tl' => '1978-01-15'],
            ['nip' => '198203202008012002', 'nama' => 'Siti Rahmawati, S.Pd.', 'email' => 'siti@smart.sch.id', 'jk' => 'P', 'tl' => '1982-03-20'],
            ['nip' => '198505102010011003', 'nama' => 'Andi Prasetyo, S.Kom.', 'email' => 'andi@smart.sch.id', 'jk' => 'L', 'tl' => '1985-05-10'],
            ['nip' => '198808252012012004', 'nama' => 'Dewi Lestari, S.Pd.', 'email' => 'dewi@smart.sch.id', 'jk' => 'P', 'tl' => '1988-08-25'],
            ['nip' => '199012012015011005', 'nama' => 'Rizki Firmansyah, S.E.', 'email' => 'rizki@smart.sch.id', 'jk' => 'L', 'tl' => '1990-12-01'],
        ];

        $gurus = [];
        foreach ($guruData as $gd) {
            $user = User::create([
                'name' => $gd['nama'],
                'email' => $gd['email'],
                'password' => Hash::make('password'),
                'role' => 'guru',
                'is_active' => true,
            ]);

            $guru = Guru::create([
                'user_id' => $user->id,
                'nip' => $gd['nip'],
                'nama_lengkap' => $gd['nama'],
                'jenis_kelamin' => $gd['jk'],
                'tanggal_lahir' => $gd['tl'],
                'alamat' => 'Jl. Pendidikan No. ' . fake()->numberBetween(1, 100) . ', Jakarta Selatan',
                'no_telepon' => '0812' . fake()->numerify('########'),
            ]);

            $gurus[] = $guru;
        }

        $siswaData = [
            ['nisn' => '00987654321001', 'nama' => 'Ahmad Rizky Pratama', 'jk' => 'L', 'kelas' => 'X-1', 'tl' => '2010-03-15', 'email' => 'ahmad@student.smart.sch.id'],
            ['nisn' => '00987654321002', 'nama' => 'Putri Ayu Lestari', 'jk' => 'P', 'kelas' => 'X-1', 'tl' => '2010-06-22', 'email' => 'putri@student.smart.sch.id'],
            ['nisn' => '00987654321003', 'nama' => 'Muhammad Fadil', 'jk' => 'L', 'kelas' => 'X-1', 'tl' => '2010-01-10', 'email' => 'fadil@student.smart.sch.id'],
            ['nisn' => '00987654321004', 'nama' => 'Salsabila Nurhaliza', 'jk' => 'P', 'kelas' => 'X-2', 'tl' => '2010-09-05', 'email' => 'salsa@student.smart.sch.id'],
            ['nisn' => '00987654321005', 'nama' => 'Rafli Aditya Nugroho', 'jk' => 'L', 'kelas' => 'X-2', 'tl' => '2010-11-18', 'email' => 'rafli@student.smart.sch.id'],
            ['nisn' => '00987654321006', 'nama' => 'Aisyah Ramadhani', 'jk' => 'P', 'kelas' => 'XI-1', 'tl' => '2009-04-30', 'email' => 'aisyah@student.smart.sch.id'],
            ['nisn' => '00987654321007', 'nama' => 'Dimas Candra Saputra', 'jk' => 'L', 'kelas' => 'XI-1', 'tl' => '2009-07-12', 'email' => 'dimas@student.smart.sch.id'],
        ];

        $siswas = [];
        foreach ($siswaData as $sd) {
            $user = User::create([
                'name' => $sd['nama'],
                'email' => $sd['email'],
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]);

            $siswa = Siswa::create([
                'user_id' => $user->id,
                'nisn' => $sd['nisn'],
                'nama_lengkap' => $sd['nama'],
                'jenis_kelamin' => $sd['jk'],
                'kelas' => $sd['kelas'],
                'tanggal_lahir' => $sd['tl'],
                'alamat' => 'Jl. ' . fake()->randomElement(['Mawar', 'Melati', 'Kenanga', 'Anggrek']) . ' No. ' . fake()->numberBetween(1, 50) . ', Jakarta',
                'no_telepon' => '0857' . fake()->numerify('########'),
                'tahun_ajaran_id' => $ta->id,
            ]);

            $siswas[] = $siswa;
        }

        $mpMatematika = MataPelajaran::where('kode', 'MTK')->first();
        $mpIndo = MataPelajaran::where('kode', 'BHS')->first();
        $mpInggris = MataPelajaran::where('kode', 'ING')->first();
        $mpIPA = MataPelajaran::where('kode', 'IPA')->first();
        $mpTIK = MataPelajaran::where('kode', 'TIK')->first();

        $jadwalData = [
            ['guru' => 0, 'mp' => $mpMatematika, 'kelas' => 'X-1', 'hari' => 'Senin',    'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 1, 'mp' => $mpIndo,        'kelas' => 'X-1', 'hari' => 'Selasa',   'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 2, 'mp' => $mpTIK,         'kelas' => 'X-1', 'hari' => 'Rabu',     'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 3, 'mp' => $mpInggris,     'kelas' => 'X-1', 'hari' => 'Kamis',    'mulai' => '07:00', 'selesai' => '08:30'],
            ['guru' => 4, 'mp' => $mpIPA,         'kelas' => 'X-1', 'hari' => 'Jumat',    'mulai' => '07:00', 'selesai' => '08:30'],

            ['guru' => 0, 'mp' => $mpIPA,         'kelas' => 'X-2', 'hari' => 'Senin',    'mulai' => '08:30', 'selesai' => '10:00'],
            ['guru' => 1, 'mp' => $mpMatematika,  'kelas' => 'X-2', 'hari' => 'Selasa',   'mulai' => '08:30', 'selesai' => '10:00'],
            ['guru' => 2, 'mp' => $mpInggris,     'kelas' => 'X-2', 'hari' => 'Rabu',     'mulai' => '08:30', 'selesai' => '10:00'],
            ['guru' => 3, 'mp' => $mpIndo,        'kelas' => 'X-2', 'hari' => 'Kamis',    'mulai' => '08:30', 'selesai' => '10:00'],
            ['guru' => 4, 'mp' => $mpTIK,         'kelas' => 'X-2', 'hari' => 'Jumat',    'mulai' => '08:30', 'selesai' => '10:00'],

            ['guru' => 0, 'mp' => $mpInggris,     'kelas' => 'XI-1', 'hari' => 'Senin',   'mulai' => '10:00', 'selesai' => '11:30'],
            ['guru' => 1, 'mp' => $mpIPA,         'kelas' => 'XI-1', 'hari' => 'Selasa',  'mulai' => '10:00', 'selesai' => '11:30'],
            ['guru' => 2, 'mp' => $mpMatematika,  'kelas' => 'XI-1', 'hari' => 'Rabu',    'mulai' => '10:00', 'selesai' => '11:30'],
            ['guru' => 3, 'mp' => $mpTIK,         'kelas' => 'XI-1', 'hari' => 'Kamis',   'mulai' => '10:00', 'selesai' => '11:30'],
            ['guru' => 4, 'mp' => $mpIndo,        'kelas' => 'XI-1', 'hari' => 'Jumat',   'mulai' => '10:00', 'selesai' => '11:30'],
        ];

        foreach ($jadwalData as $jd) {
            Jadwal::create([
                'guru_id' => $gurus[$jd['guru']]->id,
                'mata_pelajaran_id' => $jd['mp']->id,
                'tahun_ajaran_id' => $ta->id,
                'kelas' => $jd['kelas'],
                'hari' => $jd['hari'],
                'jam_mulai' => $jd['mulai'],
                'jam_selesai' => $jd['selesai'],
                'lokasi_id' => $lokasi->id,
            ]);
        }

        $rfidUids = [
            'RFID-0001', 'RFID-0002', 'RFID-0003', 'RFID-0004',
            'RFID-0005', 'RFID-0006', 'RFID-0007',
        ];

        foreach ($siswas as $i => $siswa) {
            RfidCard::create([
                'siswa_id' => $siswa->id,
                'uid' => $rfidUids[$i],
                'is_active' => true,
            ]);
        }

        $guruRfids = ['GRF-0001', 'GRF-0002', 'GRF-0003', 'GRF-0004', 'GRF-0005'];
        foreach ($gurus as $i => $guru) {
            RfidCard::create([
                'guru_id' => $guru->id,
                'uid' => $guruRfids[$i],
                'is_active' => true,
            ]);
        }
    }
}
