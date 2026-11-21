<?php

namespace Database\Seeders;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $daysToSeed = [
            Carbon::now('Asia/Jakarta')->subDays(4),
            Carbon::now('Asia/Jakarta')->subDays(3),
            Carbon::now('Asia/Jakarta')->subDays(2),
            Carbon::now('Asia/Jakarta')->subDays(1),
            Carbon::now('Asia/Jakarta'),
        ];

        $gurus = Guru::all();
        $siswas = Siswa::all();

        foreach ($daysToSeed as $day) {
            if ($day->dayOfWeek === Carbon::SATURDAY || $day->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            $today = $day->toDateString();
            $dayName = match ($day->dayOfWeek) {
                0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
                4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
            };

            foreach ($gurus as $i => $guru) {
                if (AbsensiGuru::where('guru_id', $guru->id)->where('tanggal', $today)->exists()) {
                    continue;
                }

                $statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Terlambat', 'Alpha', 'Sakit', 'Izin'];
                $status = $statuses[($i + $day->dayOfWeek) % count($statuses)];

                $jamMasuk = match ($status) {
                    'Hadir' => $day->copy()->setTime(rand(6, 7), rand(0, 59))->format('H:i:s'),
                    'Terlambat' => $day->copy()->setTime(7, rand(16, 45))->format('H:i:s'),
                    default => null,
                };

                $jamKeluar = in_array($status, ['Hadir', 'Terlambat']) && $jamMasuk && rand(1, 3) > 1
                    ? $day->copy()->setTime(rand(14, 15), rand(0, 59))->format('H:i:s')
                    : null;

                AbsensiGuru::create([
                    'guru_id' => $guru->id,
                    'tanggal' => $today,
                    'jam_masuk' => $jamMasuk,
                    'jam_keluar' => $jamKeluar,
                    'status' => $status,
                    'keterangan' => null,
                ]);
            }

            $jadwals = Jadwal::where('hari', $dayName)->with('mataPelajaran')->get();

            foreach ($jadwals as $jadwal) {
                $siswaInClass = $siswas->where('kelas', $jadwal->kelas);

                foreach ($siswaInClass as $siswa) {
                    if (AbsensiSiswa::where('siswa_id', $siswa->id)->where('jadwal_id', $jadwal->id)->where('tanggal', $today)->exists()) {
                        continue;
                    }

                    $roll = rand(1, 100);
                    if ($roll <= 70) {
                        $status = 'Hadir';
                        $jamMasuk = Carbon::parse($jadwal->jam_mulai)->subMinutes(rand(0, 5))->format('H:i:s');
                    } elseif ($roll <= 80) {
                        $status = 'Terlambat';
                        $jamMasuk = Carbon::parse($jadwal->jam_mulai)->addMinutes(rand(16, 30))->format('H:i:s');
                    } elseif ($roll <= 88) {
                        $status = 'Alfa';
                        $jamMasuk = null;
                    } elseif ($roll <= 94) {
                        $status = 'Sakit';
                        $jamMasuk = null;
                    } else {
                        $status = 'Izin';
                        $jamMasuk = null;
                    }

                    AbsensiSiswa::create([
                        'siswa_id' => $siswa->id,
                        'jadwal_id' => $jadwal->id,
                        'tanggal' => $today,
                        'status' => $status,
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => in_array($status, ['Hadir', 'Terlambat']) && $jamMasuk && rand(1, 3) > 1
                            ? Carbon::parse($jadwal->jam_selesai)->format('H:i:s')
                            : null,
                        'keterangan' => null,
                    ]);
                }
            }
        }

        $this->command->info('Absensi 5 hari terakhir berhasil di-seed.');
    }
}
