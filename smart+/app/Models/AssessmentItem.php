<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_component_id',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'semester',
        'kelas',
        'sequence',
        'title',
        'max_score',
        'tugas_id',
        'cbt_exam_external_id',
    ];

    protected $casts = [
        'max_score' => 'float',
        'sequence' => 'integer',
    ];

    public function component()
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function lmsTugas()
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function scores()
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }
}
