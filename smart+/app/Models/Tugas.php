<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HashableId;

class Tugas extends Model
{
    use HasFactory, HashableId;

    protected $fillable = [
        'guru_id',
        'kelas',
        'mata_pelajaran_id',
        'judul',
        'deskripsi',
        'file_path',
        'tenggat_waktu',
        'tipe_pengumpulan',
    ];

    protected $casts = [
        'tenggat_waktu' => 'datetime',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function pengumpulanTugas()
    {
        return $this->hasMany(PengumpulanTugas::class);
    }
}
