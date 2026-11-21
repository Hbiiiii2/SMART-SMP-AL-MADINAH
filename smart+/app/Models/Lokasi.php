<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasi';

    protected $fillable = [
        'nama',
        'deskripsi',
        'latitude',
        'longitude',
        'radius',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'float',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'lokasi_pengguna', 'lokasi_id', 'user_id')->withTimestamps();
    }
}
