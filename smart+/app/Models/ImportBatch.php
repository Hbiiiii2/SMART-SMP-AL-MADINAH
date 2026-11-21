<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'source_type',
        'file_name',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
        'semester',
        'kelas',
        'total_rows',
        'success_rows',
        'failed_rows',
        'status',
        'imported_by',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'success_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function cbtResults()
    {
        return $this->hasMany(CbtResult::class);
    }
}
