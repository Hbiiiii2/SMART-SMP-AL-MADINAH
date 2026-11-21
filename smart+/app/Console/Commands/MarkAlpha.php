<?php

namespace App\Console\Commands;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAlpha extends Command
{
    protected $signature = 'attendance:mark-alpha';
    protected $description = 'Auto-mark Alpha untuk guru & siswa yang belum absen setelah batas waktu';

    public function handle(AttendanceService $attendanceService): int
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        if ($now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY) {
            $this->info('Hari ini Sabtu/Minggu. Tidak ada auto-mark Alpha.');
            return Command::SUCCESS;
        }

        if (HariLibur::isLibur($today)) {
            $this->info('Hari ini hari libur nasional. Tidak ada auto-mark Alpha.');
            return Command::SUCCESS;
        }

        $settings = PengaturanSekolah::first();
        if (!$settings) {
            $this->error('Pengaturan sekolah tidak ditemukan.');
            return Command::FAILURE;
        }

        $toleransiAlpha = $settings->toleransi_alpha_menit ?? 30;
        $jamPulang = Carbon::parse($settings->jam_pulang);
        $batasAlpha = $jamPulang->copy()->addMinutes($toleransiAlpha);

        $this->info("Batas Alpha: {$batasAlpha->format('H:i')} (jam pulang {$settings->jam_pulang} + {$toleransiAlpha} menit)");
        $this->info("Waktu sekarang: {$now->format('H:i:s')}");

        if ($now->lt($batasAlpha)) {
            $sisaMenit = $now->diffInMinutes($batasAlpha);
            $this->info("Belum lewat batas alpha. Sisa {$sisaMenit} menit lagi.");
            return Command::SUCCESS;
        }

        $guruMarked = $attendanceService->autoMarkAlphaGuru($today);
        $siswaMarked = $attendanceService->autoMarkAlphaSiswa($today);

        $this->info("Selesai! Guru Alpha: {$guruMarked}, Siswa Alpha: {$siswaMarked}");

        return Command::SUCCESS;
    }
}
