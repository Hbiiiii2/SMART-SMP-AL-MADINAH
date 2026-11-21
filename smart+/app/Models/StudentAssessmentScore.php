<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAssessmentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'assessment_item_id',
        'score',
        'source_type',
        'source_ref_id',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function item()
    {
        return $this->belongsTo(AssessmentItem::class, 'assessment_item_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
