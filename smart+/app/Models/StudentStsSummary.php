<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentStsSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'semester',
        'kelas',
        'total_score',
        'average_score',
        'rank',
        'uas_total_score',
        'uas_average_score',
        'uas_rank',
        'uas_completed_subjects_count',
        'completed_subjects_count',
        'total_subjects_count',
        'status',
        'catatan_wali_kelas',
    ];

    protected $casts = [
        'total_score' => 'float',
        'average_score' => 'float',
        'rank' => 'integer',
        'uas_total_score' => 'float',
        'uas_average_score' => 'float',
        'uas_rank' => 'integer',
        'uas_completed_subjects_count' => 'integer',
        'completed_subjects_count' => 'integer',
        'total_subjects_count' => 'integer',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }
}
