<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'answer_type',
        'jawaban',
        'client_seq',
        'is_correct',
        'skor',
        'sudah_dinilai',
        'catatan_guru',
    ];

    protected $casts = [
        'client_seq' => 'integer',
        'is_correct' => 'boolean',
        'sudah_dinilai' => 'boolean',
        'skor' => 'decimal:2',
    ];

    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function attachments()
    {
        return $this->hasMany(AnswerAttachment::class)->orderBy('urutan')->orderBy('id');
    }
}