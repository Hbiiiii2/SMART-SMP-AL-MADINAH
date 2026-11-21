<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengumpulanTugas extends Model
{
    use HasFactory;

    protected $fillable = [
        'tugas_id',
        'siswa_id',
        'file_path',
        'waktu_pengumpulan',
        'nilai',
        'catatan_guru',
        'status',
        'link_url',
    ];

    protected $casts = [
        'waktu_pengumpulan' => 'datetime',
    ];

    public function tugas()
    {
        return $this->belongsTo(Tugas::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
