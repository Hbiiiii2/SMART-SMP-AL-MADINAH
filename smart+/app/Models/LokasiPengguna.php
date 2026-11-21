<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LokasiPengguna extends Model
{
    use HasFactory;

    protected $table = 'lokasi_pengguna';

    protected $fillable = [
        'lokasi_id',
        'user_id',
    ];

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
