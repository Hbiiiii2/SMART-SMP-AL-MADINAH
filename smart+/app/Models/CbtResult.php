<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CbtResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'source_type',
        'source_record_id',
        'exam_external_id',
        'exam_name',
        'student_nisn',
        'student_name',
        'class_name',
        'subject_name',
        'score',
        'status',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'score' => 'float',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
