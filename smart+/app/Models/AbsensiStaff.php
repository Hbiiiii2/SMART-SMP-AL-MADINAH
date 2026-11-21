<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiStaff extends Model
{
    use HasFactory;

    protected $table = 'absensi_staff';

    protected $fillable = [
        'staff_akademik_id',
        'lokasi_id',
        'tanggal',
        'waktu_check_in',
        'waktu_check_out',
        'status',
        'keterangan',
        'latitude',
        'longitude',
        'foto_lokasi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_check_in' => 'datetime',
        'waktu_check_out' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(StaffAkademik::class, 'staff_akademik_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }
}
