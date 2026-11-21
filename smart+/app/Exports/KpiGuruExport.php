<?php

namespace App\Exports;

use App\Models\Guru;
use App\Models\AbsensiGuru;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class KpiGuruExport implements FromCollection, WithHeadings, WithMapping
{
    protected $bulan;
    protected $tahun;
    protected $guruId;

    public function __construct($bulan, $tahun, $guruId = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->guruId = $guruId;
    }

    public function collection()
    {
        $query = Guru::with(['kpiBulanan' => function($q) {
            $q->where('bulan', $this->bulan)->where('tahun', $this->tahun);
        }]);

        if ($this->guruId) {
            $query->where('id', $this->guruId);
        }

        $gurus = $query->get()->map(function($g) {
            $hadir = AbsensiGuru::where('guru_id', $g->id)
                ->whereMonth('tanggal', $this->bulan)
                ->whereYear('tanggal', $this->tahun)
                ->whereIn('status', ['Hadir', 'Terlambat'])
                ->count();
            
            $target = $g->kpiBulanan->first()?->target_hari ?? 0;
            $persentase = $target > 0 ? round(($hadir / $target) * 100) : 0;
            if ($persentase > 100) $persentase = 100;

            $g->kpi_hadir = $hadir;
            $g->kpi_target = $target;
            $g->kpi_persentase = $persentase;
            return $g;
        });

        return $gurus;
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama Guru',
            'Bulan',
            'Tahun',
            'Target Kehadiran (Hari)',
            'Total Hadir (Hari)',
            'Persentase Pencapaian (%)'
        ];
    }

    public function map($guru): array
    {
        return [
            $guru->nip ?? '-',
            $guru->nama_lengkap,
            Carbon::create()->month((int) $this->bulan)->translatedFormat('F'),
            $this->tahun,
            $guru->kpi_target,
            $guru->kpi_hadir,
            $guru->kpi_persentase . '%'
        ];
    }
}
