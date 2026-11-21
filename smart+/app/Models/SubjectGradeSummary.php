<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectGradeSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'semester',
        'kelas',
        'uh_avg',
        'tugas_avg',
        'praktek_avg',
        'projek_avg',
        'portofolio_avg',
        'sts_score',
        'uas_score',
        'final_score',
        'status',
        'capaian_kompetensi',
    ];

    protected $casts = [
        'uh_avg' => 'float',
        'tugas_avg' => 'float',
        'praktek_avg' => 'float',
        'projek_avg' => 'float',
        'portofolio_avg' => 'float',
        'sts_score' => 'float',
        'uas_score' => 'float',
        'final_score' => 'float',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }
}
