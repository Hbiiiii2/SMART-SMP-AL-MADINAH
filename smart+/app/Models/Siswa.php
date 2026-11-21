<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'user_id',
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'kelas',
        'tanggal_lahir',
        'alamat',
        'no_telepon',
        'foto',
        'tahun_ajaran_id',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rfidCards()
    {
        return $this->hasMany(RfidCard::class);
    }

    public function absensiSiswas()
    {
        return $this->hasMany(AbsensiSiswa::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function pengumpulanTugas()
    {
        return $this->hasMany(PengumpulanTugas::class);
    }

    public function riwayatKelas()
    {
        return $this->hasMany(RiwayatKelasSiswa::class, 'siswa_id')->orderBy('tahun_ajaran_id')->orderBy('semester');
    }

    public function gradeSummaries()
    {
        return $this->hasMany(SubjectGradeSummary::class, 'siswa_id');
    }

    public function stsSummaries()
    {
        return $this->hasMany(StudentStsSummary::class, 'siswa_id');
    }

    public function assessmentScores()
    {
        return $this->hasMany(StudentAssessmentScore::class, 'siswa_id');
    }

    public function getIsAlumniAttribute(): bool
    {
        $k = strtolower(trim($this->kelas ?? ''));
        return str_starts_with($k, 'alumni') || str_starts_with($k, 'lulus');
    }
}
