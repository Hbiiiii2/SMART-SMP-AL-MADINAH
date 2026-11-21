<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidCard extends Model
{
    use HasFactory;

    protected $table = 'rfid_card';

    protected $fillable = [
        'siswa_id',
        'guru_id',
        'staff_akademik_id',
        'uid',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function staff()
    {
        return $this->belongsTo(StaffAkademik::class, 'staff_akademik_id');
    }
}
