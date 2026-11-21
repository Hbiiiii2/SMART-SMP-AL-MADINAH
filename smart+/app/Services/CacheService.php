<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Lokasi;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const SHORT = 300;
    private const MEDIUM = 900;
    private const LONG = 3600;

    public static function guruList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_guru', self::LONG, function () {
            return Guru::with('user')->get();
        });
    }

    public static function siswaList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_siswa', self::MEDIUM, function () {
            return Siswa::with('user')->get();
        });
    }

    public static function tahunAjaranList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_tahun_ajaran', self::LONG, function () {
            return TahunAjaran::orderByDesc('is_aktif')->orderByDesc('nama')->get();
        });
    }

    public static function tahunAjaranAktif(): ?TahunAjaran
    {
        return Cache::remember('tahun_ajaran_aktif', self::LONG, function () {
            return TahunAjaran::where('is_aktif', true)->first();
        });
    }

    public static function mataPelajaranList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_mapel', self::LONG, function () {
            return MataPelajaran::all();
        });
    }

    public static function lokasiList(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown_lokasi', self::LONG, function () {
            return Lokasi::all();
        });
    }

    public static function guruCount(): int
    {
        return Cache::remember('count_guru', self::SHORT, fn () => Guru::count());
    }

    public static function siswaCount(): int
    {
        return Cache::remember('count_siswa', self::SHORT, fn () => Siswa::count());
    }

    public static function rfidActiveCount(): int
    {
        return Cache::remember('count_rfid_active', self::SHORT, fn () => \App\Models\RfidCard::where('is_active', true)->count());
    }

    public static function flushDropdowns(): void
    {
        Cache::forget('dropdown_guru');
        Cache::forget('dropdown_siswa');
        Cache::forget('dropdown_tahun_ajaran');
        Cache::forget('dropdown_mapel');
        Cache::forget('dropdown_lokasi');
        Cache::forget('tahun_ajaran_aktif');
        Cache::forget('count_guru');
        Cache::forget('count_siswa');
        Cache::forget('count_rfid_active');
        Cache::forget('admin_dashboard');
        Cache::forget('pengaturan_sekolah');
    }

    public static function dashboardAdmin(string $today): array
    {
        return Cache::remember('admin_dashboard_' . $today, self::SHORT, function () use ($today) {
            return [
                'guru_count' => self::guruCount(),
                'siswa_count' => self::siswaCount(),
                'rfid_count' => self::rfidActiveCount(),
                'tahun_ajaran_aktif' => self::tahunAjaranAktif()->nama ?? '-',
                'guru_hadir_hari_ini' => \App\Models\AbsensiGuru::whereDate('tanggal', $today)->whereIn('status', ['Hadir', 'Terlambat'])->count(),
                'siswa_hadir_hari_ini' => \App\Models\AbsensiSiswa::whereDate('tanggal', $today)->whereIn('status', ['Hadir', 'Terlambat'])->count(),
                'siswa_alfa_hari_ini' => \App\Models\AbsensiSiswa::whereDate('tanggal', $today)->where('status', 'Alfa')->count(),
                'absensi_siswa_terbaru' => \App\Models\AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran')
                    ->whereDate('tanggal', $today)
                    ->latest('jam_masuk')
                    ->limit(10)
                    ->get(),
            ];
        });
    }
}
