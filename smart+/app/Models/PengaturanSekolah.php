<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanSekolah extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_sekolah';

    protected $fillable = [
        'nama_sekolah',
        'alamat_sekolah',
        'no_telepon_sekolah',
        'email_sekolah',
        'logo',
        'jam_masuk',
        'jam_pulang',
        'toleransi_terlambat_menit',
        'toleransi_alpha_menit',
        'radius_absensi_meter',
        'metode_absensi_siswa',
        'metode_absensi_guru',
        'metode_absensi_staff',
    ];

    protected $casts = [
        'toleransi_terlambat_menit' => 'integer',
        'toleransi_alpha_menit' => 'integer',
        'radius_absensi_meter' => 'float',
    ];
}
