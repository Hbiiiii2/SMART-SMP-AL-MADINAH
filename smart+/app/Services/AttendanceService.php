<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\Lokasi;
use App\Models\PengaturanSekolah;
use App\Models\RfidCard;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceService
{
    private ?PengaturanSekolah $settings = null;

    private function getSettings(): PengaturanSekolah
    {
        if (!$this->settings) {
            $this->settings = PengaturanSekolah::firstOrCreate([], [
                'nama_sekolah' => 'SMP Al - Madinah',
                'jam_masuk' => '07:15:00',
                'jam_pulang' => '15:00:00',
                'toleransi_terlambat_menit' => 15,
                'toleransi_alpha_menit' => 30,
                'radius_absensi_meter' => 100,
                'metode_absensi_siswa' => 'RFID',
                'metode_absensi_guru' => 'RFID',
                'metode_absensi_staff' => 'RFID',
            ]);
        }

        return $this->settings;
    }

    public function processRfidScan(string $uid): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        if ($now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY) {
            return ['success' => false, 'message' => 'Hari ini Sabtu/Minggu. Absensi tidak diperlukan.'];
        }

        if (HariLibur::isLibur($today)) {
            return ['success' => false, 'message' => 'Hari ini hari libur nasional. Absensi tidak diperlukan.'];
        }

        $rfid = RfidCard::with(['siswa.user', 'guru.user', 'staff.user'])
            ->where('uid', $uid)
            ->where('is_active', true)
            ->first();

        if (!$rfid) {
            return ['success' => false, 'message' => 'Kartu RFID tidak valid atau tidak aktif.'];
        }

        if ($rfid->guru_id && $rfid->guru) {
            return $this->processGuruScan($rfid);
        }

        if ($rfid->siswa_id && $rfid->siswa) {
            return $this->processSiswaScan($rfid);
        }

        if ($rfid->staff_akademik_id && $rfid->staff) {
            return $this->processStaffScan($rfid);
        }

        return ['success' => false, 'message' => 'Kartu RFID tidak terhubung ke akun yang valid.'];
    }

    private function isPastAlphaTime(): bool
    {
        $settings = $this->getSettings();
        $now = Carbon::now('Asia/Jakarta');
        $batasAlpha = Carbon::parse($now->toDateString() . ' ' . $settings->jam_pulang, 'Asia/Jakarta')
            ->addMinutes($settings->toleransi_alpha_menit);

        return $now->greaterThan($batasAlpha);
    }

    private function processGuruScan(RfidCard $rfid): array
    {
        $guru = $rfid->guru;

        if (!$guru || !$guru->user || !$guru->user->is_active) {
            return ['success' => false, 'message' => 'Akun guru tidak aktif.'];
        }

        $fotoUrl = $guru->foto ? asset('storage/' . $guru->foto) : null;
        $initials = strtoupper(mb_substr($guru->nama_lengkap, 0, 2));
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');

        if ($this->isPastAlphaTime()) {
            $this->autoMarkAlphaGuru($today);
            $settings = $this->getSettings();
            $batasAlpha = Carbon::parse($settings->jam_pulang)->addMinutes($settings->toleransi_alpha_menit);
            return [
                'success' => false,
                'message' => 'Waktu absensi sudah berakhir (lewat ' . $batasAlpha->format('H:i') . ').',
                'role' => 'guru',
                'nama' => $guru->nama_lengkap,
                'nip' => $guru->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        $existing = AbsensiGuru::where('guru_id', $guru->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing && $existing->jam_keluar) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan check-out hari ini.',
                'role' => 'guru',
                'nama' => $guru->nama_lengkap,
                'nip' => $guru->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if ($existing && $existing->jam_masuk && !$existing->jam_keluar) {
            $existing->update(['jam_keluar' => $now->format('H:i:s')]);

            return [
                'success' => true,
                'message' => 'Check-out guru berhasil.',
                'type' => 'keluar',
                'role' => 'guru',
                'nama' => $guru->nama_lengkap,
                'nip' => $guru->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
                'jam' => $now->format('H:i:s'),
            ];
        }

        $settings = $this->getSettings();
        $batasTerlambat = Carbon::parse($settings->jam_masuk)->addMinutes($settings->toleransi_terlambat_menit);
        $status = $now->greaterThan($batasTerlambat) ? 'Terlambat' : 'Hadir';

        AbsensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => $today,
            'jam_masuk' => $now->format('H:i:s'),
            'jam_keluar' => null,
            'status' => $status,
        ]);

        return [
            'success' => true,
            'message' => "Check-in guru {$status}.",
            'type' => 'masuk',
            'role' => 'guru',
            'nama' => $guru->nama_lengkap,
            'nip' => $guru->nip,
            'foto' => $fotoUrl,
            'initials' => $initials,
            'status' => $status,
            'jam' => $now->format('H:i:s'),
        ];
    }

    private function processSiswaScan(RfidCard $rfid): array
    {
        $siswa = $rfid->siswa;

        if (!$siswa || !$siswa->user || !$siswa->user->is_active) {
            return ['success' => false, 'message' => 'Akun siswa tidak aktif.'];
        }

        $fotoUrl = $siswa->foto ? asset('storage/' . $siswa->foto) : null;
        $initials = strtoupper(mb_substr($siswa->nama_lengkap, 0, 2));
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');
        $dayName = $this->getDayName($now->dayOfWeek);
        $currentTime = $now->format('H:i:s');

        $jadwal = Jadwal::where('kelas', $siswa->kelas)
            ->where('hari', $dayName)
            ->where('jam_mulai', '<=', $currentTime)
            ->where('jam_selesai', '>=', $currentTime)
            ->with('mataPelajaran')
            ->first();

        if (!$jadwal) {
            $jadwal = Jadwal::where('kelas', $siswa->kelas)
                ->where('hari', $dayName)
                ->where('jam_mulai', '>=', $now->copy()->subMinutes(60)->format('H:i:s'))
                ->where('jam_mulai', '<=', $currentTime)
                ->with('mataPelajaran')
                ->first();
        }

        if (!$jadwal) {
            $jadwal = Jadwal::where('kelas', $siswa->kelas)
                ->where('hari', $dayName)
                ->where('jam_mulai', '>', $currentTime)
                ->where('jam_mulai', '<=', $now->copy()->addMinutes(120)->format('H:i:s'))
                ->orderBy('jam_mulai')
                ->with('mataPelajaran')
                ->first();
        }

        if (!$jadwal) {
            $jadwal = Jadwal::where('kelas', $siswa->kelas)
                ->where('hari', $dayName)
                ->orderBy('jam_mulai')
                ->with('mataPelajaran')
                ->first();
        }

        if (!$jadwal) {
            // Cek apakah siswa punya absensi masuk hari ini yang belum check-out
            $existingOpen = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->whereDate('tanggal', $today)
                ->whereNotNull('jam_masuk')
                ->whereNull('jam_keluar')
                ->orderBy('jam_masuk', 'desc')
                ->first();

            if ($existingOpen) {
                $jadwalOpen = Jadwal::with('mataPelajaran')->find($existingOpen->jadwal_id);

                return $this->processSiswaTapOut($siswa, $today, $now, $fotoUrl, $initials, $jadwalOpen);
            }

            return [
                'success' => false,
                'message' => 'Tidak ada jadwal aktif untuk kelas ' . $siswa->kelas . ' saat ini.',
                'role' => 'siswa',
                'nama' => $siswa->nama_lengkap,
                'nisn' => $siswa->nisn,
                'kelas' => $siswa->kelas,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if ($this->isPastAlphaTime()) {
            $this->autoMarkAlphaSiswa($today);
            $settings = $this->getSettings();
            $batasAlpha = Carbon::parse($today . ' ' . $settings->jam_pulang, 'Asia/Jakarta')
                ->addMinutes($settings->toleransi_alpha_menit);

            return [
                'success' => false,
                'message' => 'Waktu absensi sudah berakhir (lewat ' . $batasAlpha->format('H:i') . ').',
                'role' => 'siswa',
                'nama' => $siswa->nama_lengkap,
                'nisn' => $siswa->nisn,
                'kelas' => $siswa->kelas,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        $existing = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->where('jadwal_id', $jadwal->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing && $existing->jam_keluar) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan absensi keluar untuk jadwal ini hari ini.',
                'role' => 'siswa',
                'nama' => $siswa->nama_lengkap,
                'nisn' => $siswa->nisn,
                'kelas' => $siswa->kelas,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if ($existing && $existing->jam_masuk && !$existing->jam_keluar) {
            return $this->processSiswaTapOut($siswa, $today, $now, $fotoUrl, $initials, $jadwal);
        }

        $existingOpen = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->orderBy('jam_masuk', 'desc')
            ->first();

        if ($existingOpen) {
            $jadwalOpen = Jadwal::with('mataPelajaran')->find($existingOpen->jadwal_id);

            return $this->processSiswaTapOut($siswa, $today, $now, $fotoUrl, $initials, $jadwalOpen ?? $jadwal);
        }

        $settings = $this->getSettings();
        $acuanJamMasuk = $settings->jam_masuk ?: $jadwal->jam_mulai;
        $batasTerlambat = Carbon::parse($today . ' ' . $acuanJamMasuk, 'Asia/Jakarta')
            ->addMinutes($settings->toleransi_terlambat_menit);
        $status = $now->greaterThan($batasTerlambat) ? 'Terlambat' : 'Hadir';
        $jamMasukAktual = $now->format('H:i:s');

        if ($existing) {
            $existing->update([
                'status' => $status,
                'jam_masuk' => $jamMasukAktual,
            ]);
        } else {
            AbsensiSiswa::create([
                'siswa_id' => $siswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $today,
                'status' => $status,
                'jam_masuk' => $jamMasukAktual,
            ]);
        }

        // Otomatis buatkan atau perbarui absensi untuk SEMUA jadwal lain di hari ini
        // agar guru di mata pelajaran lain langsung bisa melihat kehadiran siswa
        $allJadwalsToday = Jadwal::where('kelas', $siswa->kelas)
            ->where('hari', $dayName)
            ->where('id', '!=', $jadwal->id)
            ->get();

        foreach ($allJadwalsToday as $otherJadwal) {
            $otherAbsensi = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $otherJadwal->id)
                ->whereDate('tanggal', $today)
                ->first();

            if (!$otherAbsensi) {
                AbsensiSiswa::create([
                    'siswa_id' => $siswa->id,
                    'jadwal_id' => $otherJadwal->id,
                    'tanggal' => $today,
                    'status' => $status,
                    'jam_masuk' => $jamMasukAktual,
                    'keterangan' => 'Otomatis mengikuti absen pertama',
                ]);
            } elseif ($otherAbsensi->status === 'Alfa' && !$otherAbsensi->jam_masuk) {
                $otherAbsensi->update([
                    'status' => $status,
                    'jam_masuk' => $jamMasukAktual,
                    'keterangan' => 'Otomatis mengikuti absen pertama',
                ]);
            }
        }

        Cache::forget('live_stats_' . $today);

        return [
            'success' => true,
            'message' => "Absensi {$status} berhasil.",
            'type' => 'masuk',
            'role' => 'siswa',
            'nama' => $siswa->nama_lengkap,
            'nisn' => $siswa->nisn,
            'kelas' => $siswa->kelas,
            'foto' => $fotoUrl,
            'initials' => $initials,
            'mata_pelajaran' => $jadwal->mataPelajaran->nama ?? '-',
            'status' => $status,
            'jam' => $jamMasukAktual,
        ];
    }

    private function processSiswaTapOut(
        Siswa $siswa,
        string $today,
        Carbon $now,
        ?string $fotoUrl,
        string $initials,
        ?Jadwal $jadwal = null
    ): array {
        $jamKeluarAktual = $now->format('H:i:s');

        $allOpenAbsensi = AbsensiSiswa::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->get();

        foreach ($allOpenAbsensi as $openAbs) {
            $openAbs->update(['jam_keluar' => $jamKeluarAktual]);
        }

        Cache::forget('live_stats_' . $today);

        return [
            'success' => true,
            'message' => 'Absensi keluar berhasil.',
            'type' => 'keluar',
            'role' => 'siswa',
            'nama' => $siswa->nama_lengkap,
            'nisn' => $siswa->nisn,
            'kelas' => $siswa->kelas,
            'foto' => $fotoUrl,
            'initials' => $initials,
            'mata_pelajaran' => $jadwal?->mataPelajaran?->nama ?? '-',
            'jam' => $jamKeluarAktual,
        ];
    }

    public function autoMarkAlphaGuru(string $today): int
    {
        $marked = 0;
        $gurus = Guru::all();

        foreach ($gurus as $guru) {
            $exists = AbsensiGuru::where('guru_id', $guru->id)->whereDate('tanggal', $today)->exists();
            if (!$exists) {
                AbsensiGuru::create([
                    'guru_id' => $guru->id,
                    'tanggal' => $today,
                    'jam_masuk' => null,
                    'jam_keluar' => null,
                    'status' => 'Alpha',
                    'keterangan' => 'Tidak melakukan absensi hari ini',
                ]);
                $marked++;
            }
        }

        return $marked;
    }

    public function autoMarkAlphaSiswaForJadwal(Jadwal $jadwal, string $today): int
    {
        $marked = 0;
        $siswaInClass = Siswa::where('kelas', $jadwal->kelas)->get();

        foreach ($siswaInClass as $siswa) {
            $exists = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $today)
                ->exists();

            if (!$exists) {
                $firstTap = AbsensiSiswa::where('siswa_id', $siswa->id)
                    ->whereDate('tanggal', $today)
                    ->whereNotNull('jam_masuk')
                    ->orderBy('jam_masuk', 'asc')
                    ->first();

                if (!$firstTap) {
                    $firstTap = AbsensiSiswa::where('siswa_id', $siswa->id)
                        ->whereDate('tanggal', $today)
                        ->orderBy('created_at', 'asc')
                        ->first();
                }

                if ($firstTap) {
                    $jamMasukRecord = $firstTap->jam_masuk instanceof Carbon
                        ? $firstTap->jam_masuk->format('H:i:s')
                        : $firstTap->jam_masuk;
                    $jamKeluarRecord = $firstTap->jam_keluar instanceof Carbon
                        ? $firstTap->jam_keluar->format('H:i:s')
                        : $firstTap->jam_keluar;

                    AbsensiSiswa::create([
                        'siswa_id' => $siswa->id,
                        'jadwal_id' => $jadwal->id,
                        'tanggal' => $today,
                        'status' => $firstTap->status,
                        'jam_masuk' => $jamMasukRecord,
                        'jam_keluar' => $jamKeluarRecord,
                        'keterangan' => 'Otomatis mengikuti absen pertama',
                    ]);
                } else {
                    AbsensiSiswa::create([
                        'siswa_id' => $siswa->id,
                        'jadwal_id' => $jadwal->id,
                        'tanggal' => $today,
                        'status' => 'Alfa',
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'keterangan' => 'Tidak melakukan absensi',
                    ]);
                    $marked++;
                }
            }
        }

        return $marked;
    }

    public function autoMarkAlphaSiswa(string $today): int
    {
        $marked = 0;
        $dayName = match (Carbon::parse($today, 'Asia/Jakarta')->dayOfWeek) {
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', default => 'Senin',
        };

        $jadwals = Jadwal::where('hari', $dayName)->get();

        foreach ($jadwals as $jadwal) {
            $marked += $this->autoMarkAlphaSiswaForJadwal($jadwal, $today);
        }

        return $marked;
    }

    public function getLiveStats(string $date = null): array
    {
        $date = $date ?? Carbon::now('Asia/Jakarta')->toDateString();

        return Cache::remember('live_stats_' . $date, 5, function () use ($date) {
            $counts = AbsensiSiswa::whereDate('tanggal', $date)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $guruHadir = AbsensiGuru::where('tanggal', $date)->where('status', 'Hadir')->count();
            $guruTerlambat = AbsensiGuru::where('tanggal', $date)->where('status', 'Terlambat')->count();

            return [
                'hadir' => $counts->get('Hadir', 0),
                'terlambat' => $counts->get('Terlambat', 0),
                'alfa' => $counts->get('Alfa', 0),
                'sakit' => $counts->get('Sakit', 0),
                'izin' => $counts->get('Izin', 0),
                'total' => $counts->sum(),
                'guru_hadir' => $guruHadir + $guruTerlambat,
            ];
        });
    }

    public function updateAbsensiStatus(int $absensiId, string $newStatus, string $alasan, int $userId): array
    {
        $absensi = AbsensiSiswa::findOrFail($absensiId);

        if ($absensi->status !== 'Alfa') {
            return ['success' => false, 'message' => 'Hanya status Alfa yang bisa diubah.'];
        }

        if (!in_array($newStatus, ['Sakit', 'Izin', 'Hadir'])) {
            return ['success' => false, 'message' => 'Status hanya bisa diubah ke Sakit, Izin, atau Hadir.'];
        }

        $updateData = [
            'status' => $newStatus,
            'keterangan' => $alasan,
            'diedit_oleh' => $userId,
            'alasan_perubahan' => "Alfa → {$newStatus}: {$alasan}",
        ];

        if ($newStatus === 'Hadir') {
            $absensi->loadMissing('jadwal');
            $updateData['jam_masuk'] = $absensi->jadwal ? $absensi->jadwal->jam_mulai : \Carbon\Carbon::now('Asia/Jakarta')->format('H:i:s');
        }

        $absensi->update($updateData);

        Cache::forget('live_stats_' . $absensi->tanggal);

        return ['success' => true, 'message' => "Status berhasil diubah menjadi {$newStatus}."];
    }

    public function updateGuruAbsensiStatus(int $absensiId, string $newStatus, string $alasan, int $userId): array
    {
        $absensi = AbsensiGuru::findOrFail($absensiId);

        if ($absensi->status !== 'Alpha') {
            return ['success' => false, 'message' => 'Hanya status Alpha yang bisa diubah.'];
        }

        if (!in_array($newStatus, ['Sakit', 'Izin'])) {
            return ['success' => false, 'message' => 'Status hanya bisa diubah ke Sakit atau Izin.'];
        }

        $absensi->update([
            'status' => $newStatus,
            'keterangan' => $alasan,
        ]);

        Cache::forget('live_stats_' . $absensi->tanggal);

        return ['success' => true, 'message' => "Status guru berhasil diubah menjadi {$newStatus}."];
    }

    public function getAbsensiHariIni(int $jadwalId, string $date = null): \Illuminate\Support\Collection
    {
        $date = $date ?? Carbon::now('Asia/Jakarta')->toDateString();

        return AbsensiSiswa::with('siswa.user')
            ->where('jadwal_id', $jadwalId)
            ->where('tanggal', $date)
            ->get();
    }

    private function getDayName(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin',
        };
    }

    public function processGuruZonasiCheckin(int $guruId, float $latitude, float $longitude): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        if ($now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY) {
            return ['success' => false, 'message' => 'Hari ini Sabtu/Minggu. Absensi tidak diperlukan.'];
        }

        if (HariLibur::isLibur($today)) {
            return ['success' => false, 'message' => 'Hari ini hari libur nasional. Absensi tidak diperlukan.'];
        }

        $guru = Guru::find($guruId);
        if (!$guru || !$guru->user || !$guru->user->is_active) {
            return ['success' => false, 'message' => 'Akun guru tidak aktif.'];
        }

        $fotoUrl = $guru->foto ? asset('storage/' . $guru->foto) : null;
        $initials = strtoupper(mb_substr($guru->nama_lengkap, 0, 2));

        if ($this->isPastAlphaTime()) {
            $this->autoMarkAlphaGuru($today);
            $settings = $this->getSettings();
            $batasAlpha = Carbon::parse($settings->jam_pulang)->addMinutes($settings->toleransi_alpha_menit);
            return [
                'success' => false,
                'message' => 'Waktu absensi sudah berakhir (lewat ' . $batasAlpha->format('H:i') . ').',
                'role' => 'guru',
                'nama' => $guru->nama_lengkap,
                'nip' => $guru->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        // Skip radius check for manual confirmation (lat/lng = 0)
        if (!($latitude === 0.0 && $longitude === 0.0)) {
            $inSchool = $this->isInSchoolRadius($latitude, $longitude);
            if (!$inSchool) {
                return [
                    'success' => false,
                    'message' => 'Anda berada di luar area sekolah. Pastikan Anda berada dalam radius lokasi sekolah.',
                    'role' => 'guru',
                    'nama' => $guru->nama_lengkap,
                    'nip' => $guru->nip,
                    'foto' => $fotoUrl,
                    'initials' => $initials,
                ];
            }
        }

        $existing = AbsensiGuru::where('guru_id', $guru->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing && $existing->jam_keluar) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan check-out hari ini.',
                'role' => 'guru',
                'nama' => $guru->nama_lengkap,
                'nip' => $guru->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if ($existing && $existing->jam_masuk && !$existing->jam_keluar) {
            $existing->update([
                'jam_keluar' => $now->format('H:i:s'),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return [
                'success' => true,
                'message' => 'Check-out guru berhasil.',
                'type' => 'keluar',
                'role' => 'guru',
                'nama' => $guru->nama_lengkap,
                'nip' => $guru->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
                'jam' => $now->format('H:i:s'),
            ];
        }

        $settings = $this->getSettings();
        $batasTerlambat = Carbon::parse($settings->jam_masuk)->addMinutes($settings->toleransi_terlambat_menit);
        $status = $now->greaterThan($batasTerlambat) ? 'Terlambat' : 'Hadir';

        AbsensiGuru::create([
            'guru_id' => $guru->id,
            'tanggal' => $today,
            'jam_masuk' => $now->format('H:i:s'),
            'jam_keluar' => null,
            'status' => $status,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        Cache::forget('live_stats_' . $today);

        return [
            'success' => true,
            'message' => "Check-in guru {$status}.",
            'type' => 'masuk',
            'role' => 'guru',
            'nama' => $guru->nama_lengkap,
            'nip' => $guru->nip,
            'foto' => $fotoUrl,
            'initials' => $initials,
            'status' => $status,
            'jam' => $now->format('H:i:s'),
        ];
    }

    private function isInSchoolRadius(float $latitude, float $longitude): bool
    {
        $lokasis = Lokasi::all();

        if ($lokasis->isEmpty()) {
            $settings = $this->getSettings();
            return true;
        }

        foreach ($lokasis as $lokasi) {
            if ($lokasi->latitude && $lokasi->longitude) {
                $distance = $this->haversineDistance($latitude, $longitude, $lokasi->latitude, $lokasi->longitude);
                if ($distance <= $lokasi->radius) {
                    return true;
                }
            }
        }

        return false;
    }

    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getGuruAbsensiStatus(int $guruId, string $date = null): ?array
    {
        $date = $date ?? Carbon::now('Asia/Jakarta')->toDateString();

        $absensi = AbsensiGuru::where('guru_id', $guruId)
            ->where('tanggal', $date)
            ->first();

        if (!$absensi) {
            return null;
        }

        return [
            'id' => $absensi->id,
            'jam_masuk' => $absensi->jam_masuk ? Carbon::parse($absensi->jam_masuk)->format('H:i:s') : null,
            'jam_keluar' => $absensi->jam_keluar ? Carbon::parse($absensi->jam_keluar)->format('H:i:s') : null,
            'status' => $absensi->status,
            'keterangan' => $absensi->keterangan,
            'sudah_check_in' => $absensi->jam_masuk !== null,
            'sudah_check_out' => $absensi->jam_keluar !== null,
        ];
    }

    public function getStaffAbsensiStatus(int $staffId, string $date = null): ?array
    {
        $date = $date ?? Carbon::now('Asia/Jakarta')->toDateString();

        $absensi = \App\Models\AbsensiStaff::where('staff_akademik_id', $staffId)
            ->where('tanggal', $date)
            ->first();

        if (!$absensi) {
            return null;
        }

        return [
            'id' => $absensi->id,
            'jam_masuk' => $absensi->waktu_check_in ? Carbon::parse($absensi->waktu_check_in)->format('H:i:s') : null,
            'jam_keluar' => $absensi->waktu_check_out ? Carbon::parse($absensi->waktu_check_out)->format('H:i:s') : null,
            'status' => $absensi->status,
            'keterangan' => $absensi->keterangan,
            'sudah_check_in' => $absensi->waktu_check_in !== null,
            'sudah_check_out' => $absensi->waktu_check_out !== null,
        ];
    }

    public function processStaffZonasiCheckin(int $staffId, float $latitude, float $longitude): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        if ($now->dayOfWeek === Carbon::SATURDAY || $now->dayOfWeek === Carbon::SUNDAY) {
            return ['success' => false, 'message' => 'Hari ini Sabtu/Minggu. Absensi tidak diperlukan.'];
        }

        if (HariLibur::isLibur($today)) {
            return ['success' => false, 'message' => 'Hari ini hari libur nasional. Absensi tidak diperlukan.'];
        }

        $staff = \App\Models\StaffAkademik::find($staffId);
        if (!$staff || !$staff->user || !$staff->user->is_active) {
            return ['success' => false, 'message' => 'Akun staff tidak aktif.'];
        }

        $fotoUrl = $staff->foto ? asset('storage/' . $staff->foto) : null;
        $initials = strtoupper(mb_substr($staff->nama_lengkap, 0, 2));

        if ($this->isPastAlphaTime()) {
            $settings = $this->getSettings();
            $batasAlpha = Carbon::parse($settings->jam_pulang)->addMinutes($settings->toleransi_alpha_menit);
            return [
                'success' => false,
                'message' => 'Waktu absensi sudah berakhir (lewat ' . $batasAlpha->format('H:i') . ').',
                'role' => 'staff_akademik',
                'nama' => $staff->nama_lengkap,
                'nip' => $staff->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if (!($latitude === 0.0 && $longitude === 0.0)) {
            $inSchool = $this->isInSchoolRadius($latitude, $longitude);
            if (!$inSchool) {
                return [
                    'success' => false,
                    'message' => 'Anda berada di luar area sekolah. Pastikan Anda berada dalam radius lokasi sekolah.',
                    'role' => 'staff_akademik',
                    'nama' => $staff->nama_lengkap,
                    'nip' => $staff->nip,
                    'foto' => $fotoUrl,
                    'initials' => $initials,
                ];
            }
        }

        $existing = \App\Models\AbsensiStaff::where('staff_akademik_id', $staff->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing && $existing->waktu_check_out) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan check-out hari ini.',
                'role' => 'staff_akademik',
                'nama' => $staff->nama_lengkap,
                'nip' => $staff->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if ($existing && $existing->waktu_check_in && !$existing->waktu_check_out) {
            $existing->update([
                'waktu_check_out' => $now,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return [
                'success' => true,
                'message' => 'Check-out berhasil.',
                'type' => 'keluar',
                'role' => 'staff_akademik',
                'nama' => $staff->nama_lengkap,
                'nip' => $staff->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
                'jam' => $now->format('H:i:s'),
            ];
        }

        $settings = $this->getSettings();
        $batasTerlambat = Carbon::parse($settings->jam_masuk)->addMinutes($settings->toleransi_terlambat_menit);
        $status = $now->greaterThan($batasTerlambat) ? 'Terlambat' : 'Hadir';

        \App\Models\AbsensiStaff::create([
            'staff_akademik_id' => $staff->id,
            'tanggal' => $today,
            'waktu_check_in' => $now,
            'waktu_check_out' => null,
            'status' => $status,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        \Illuminate\Support\Facades\Cache::forget('live_stats_' . $today);

        return [
            'success' => true,
            'message' => "Check-in {$status}.",
            'type' => 'masuk',
            'role' => 'staff_akademik',
            'nama' => $staff->nama_lengkap,
            'nip' => $staff->nip,
            'foto' => $fotoUrl,
            'initials' => $initials,
            'status' => $status,
            'jam' => $now->format('H:i:s'),
        ];
    }

    private function processStaffScan(\App\Models\RfidCard $rfid): array
    {
        $staff = $rfid->staff;

        if (!$staff || !$staff->user || !$staff->user->is_active) {
            return ['success' => false, 'message' => 'Akun staff tidak aktif.'];
        }

        $fotoUrl = $staff->foto ? asset('storage/' . $staff->foto) : null;
        $initials = strtoupper(mb_substr($staff->nama_lengkap, 0, 2));
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');

        if ($this->isPastAlphaTime()) {
            $settings = $this->getSettings();
            $batasAlpha = Carbon::parse($settings->jam_pulang)->addMinutes($settings->toleransi_alpha_menit);
            return [
                'success' => false,
                'message' => 'Waktu absensi sudah berakhir (lewat ' . $batasAlpha->format('H:i') . ').',
                'role' => 'staff_akademik',
                'nama' => $staff->nama_lengkap,
                'nip' => $staff->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        $existing = \App\Models\AbsensiStaff::where('staff_akademik_id', $staff->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing && $existing->waktu_check_out) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan check-out hari ini.',
                'role' => 'staff_akademik',
                'nama' => $staff->nama_lengkap,
                'nip' => $staff->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
            ];
        }

        if ($existing && $existing->waktu_check_in && !$existing->waktu_check_out) {
            $existing->update(['waktu_check_out' => $now]);

            return [
                'success' => true,
                'message' => 'Check-out berhasil.',
                'type' => 'keluar',
                'role' => 'staff_akademik',
                'nama' => $staff->nama_lengkap,
                'nip' => $staff->nip,
                'foto' => $fotoUrl,
                'initials' => $initials,
                'jam' => $now->format('H:i:s'),
            ];
        }

        $settings = $this->getSettings();
        $batasTerlambat = Carbon::parse($settings->jam_masuk)->addMinutes($settings->toleransi_terlambat_menit);
        $status = $now->greaterThan($batasTerlambat) ? 'Terlambat' : 'Hadir';

        \App\Models\AbsensiStaff::create([
            'staff_akademik_id' => $staff->id,
            'tanggal' => $today,
            'waktu_check_in' => $now,
            'waktu_check_out' => null,
            'status' => $status,
        ]);

        \Illuminate\Support\Facades\Cache::forget('live_stats_' . $today);

        return [
            'success' => true,
            'message' => 'Check-in ' . $status . '.',
            'type' => 'masuk',
            'role' => 'staff_akademik',
            'nama' => $staff->nama_lengkap,
            'nip' => $staff->nip,
            'foto' => $fotoUrl,
            'initials' => $initials,
            'status' => $status,
            'jam' => $now->format('H:i:s'),
        ];
    }
}
