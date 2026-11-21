<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassHomeroom extends Model
{
    use HasFactory;

    protected $table = 'class_homerooms';

    protected $fillable = [
        'kelas',
        'tahun_ajaran_id',
        'guru_id',
        'nama_wali_kelas',
        'nip_wali_kelas',
        'ttd_wali_kelas',
        'kkm_default',
        'kkm_mapel',
    ];

    protected $casts = [
        'kkm_default' => 'float',
        'kkm_mapel' => 'array',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Helper to get effective homeroom teacher name.
     */
    public function getEffectiveNamaAttribute(): string
    {
        return $this->nama_wali_kelas ?: ($this->guru?->nama_lengkap ?? 'Wali Kelas');
    }

    /**
     * Helper to get effective homeroom teacher NIP.
     */
    public function getEffectiveNipAttribute(): string
    {
        return $this->nip_wali_kelas ?: ($this->guru?->nip ?? '-');
    }

    /**
     * Resolve effective KKM for a given subject in this class.
     */
    public function getKkmForSubject($subjectId, $fallback = 75.0): float
    {
        $map = $this->kkm_mapel;
        if (is_array($map) && isset($map[$subjectId]) && $map[$subjectId] !== null && $map[$subjectId] !== '') {
            return (float) $map[$subjectId];
        }
        if ($this->kkm_default !== null) {
            return (float) $this->kkm_default;
        }
        return (float) $fallback;
    }
}
