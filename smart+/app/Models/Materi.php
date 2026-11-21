<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HashableId;

class Materi extends Model
{
    use HasFactory, HashableId;

    protected $table = 'materi';

    protected $fillable = [
        'guru_id',
        'kelas',
        'mata_pelajaran_id',
        'judul',
        'deskripsi',
        'tipe_materi',
        'file_path_atau_url',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
