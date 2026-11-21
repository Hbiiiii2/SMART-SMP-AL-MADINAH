<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'siswa_id',
        'started_at',
        'submitted_at',
        'status',
        'nilai_akhir',
        'nilai_pg',
        'nilai_essay',
        'attempt_number',
        'token_used',
        'question_order',
        'questions_snapshot',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'nilai_akhir' => 'decimal:2',
        'nilai_pg' => 'decimal:2',
        'nilai_essay' => 'decimal:2',
        'attempt_number' => 'integer',
        'question_order' => 'array',
        'questions_snapshot' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays and JSON serialization.
     */
    protected $hidden = [
        'questions_snapshot',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}