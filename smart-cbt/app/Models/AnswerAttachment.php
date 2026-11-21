<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'answer_id',
        'exam_attempt_id',
        'question_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'urutan',
        'checksum_sha256',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'urutan' => 'integer',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
