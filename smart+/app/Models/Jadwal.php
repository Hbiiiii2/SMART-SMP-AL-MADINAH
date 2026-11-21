<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HashableId;

class Jadwal extends Model
{
    use HasFactory, HashableId;

    protected $table = 'jadwal';

    protected $fillable = [
        'guru_id',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'kelas',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'lokasi_id',
    ];

    protected $casts = [
        //
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }
}
