<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function guru()
    {
        return $this->hasOne(\App\Models\Guru::class, 'user_id');
    }

    public function staff_akademik()
    {
        return $this->hasOne(\App\Models\StaffAkademik::class, 'pengguna_id');
    }

    public function siswa()
    {
        return $this->hasOne(\App\Models\Siswa::class);
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\LogAktivitas::class);
    }

    public function pengajuan_cuti()
    {
        return $this->hasMany(\App\Models\PengajuanCuti::class);
    }

    public function lokasi_diizinkan()
    {
        return $this->belongsToMany(\App\Models\Lokasi::class, 'lokasi_pengguna', 'user_id', 'lokasi_id')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin_sekolah']);
    }

    public function isKepalaSekolah(): bool
    {
        return $this->role === 'kepala_sekolah';
    }

    public function isDirektur(): bool
    {
        return $this->role === 'direktur';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isStaffAkademik(): bool
    {
        return $this->role === 'staff_akademik';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
