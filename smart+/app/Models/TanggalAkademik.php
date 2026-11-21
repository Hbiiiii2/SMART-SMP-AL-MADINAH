<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TanggalAkademik extends Model
{
    protected $fillable = [
        'tanggal',
        'tanggal_selesai',
        'nama_kegiatan',
        'keterangan',
        'warna'
    ];
    
    protected $casts = [
        'tanggal' => 'date',
        'tanggal_selesai' => 'date',
    ];
}
