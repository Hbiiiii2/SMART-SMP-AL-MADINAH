<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffAkademik extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_akademik';

    protected $fillable = [
        'pengguna_id',
        'nip',
        'nama_lengkap',
        'jenis_kelamin',
        'tanggal_lahir',
        'nomor_telepon',
        'alamat',
        'foto',
        'status_aktif',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'status_aktif' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function absensi()
    {
        return $this->hasMany(AbsensiStaff::class, 'staff_akademik_id');
    }

    public function kpiBulanan()
    {
        return $this->morphMany(KpiKehadiranBulanan::class, 'kpiable');
    }
}
