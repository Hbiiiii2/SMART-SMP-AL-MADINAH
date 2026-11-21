<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiKehadiranBulanan extends Model
{
    use HasFactory;

    protected $table = 'kpi_kehadiran_bulanan';

    protected $fillable = [
        'kpiable_id',
        'kpiable_type',
        'bulan',
        'tahun',
        'target_hari',
    ];

    public function kpiable()
    {
        return $this->morphTo();
    }
}
