<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportFinalization extends Model
{
    use HasFactory;

    protected $fillable = [
        'kelas',
        'tahun_ajaran_id',
        'semester',
        'status',
        'reviewed_by',
        'approved_by',
        'finalized_at',
        'published_at',
        'notes',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
