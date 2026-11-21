<?php

namespace App\Exports;

use App\Models\StaffAkademik;
use App\Models\AbsensiStaff;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class KpiStaffExport implements FromCollection, WithHeadings, WithMapping
{
    protected $bulan;
    protected $tahun;
    protected $staffId;

    public function __construct($bulan, $tahun, $staffId = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->staffId = $staffId;
    }

    public function collection()
    {
        $query = StaffAkademik::with(['kpiBulanan' => function($q) {
            $q->where('bulan', $this->bulan)->where('tahun', $this->tahun);
        }]);

        if ($this->staffId) {
            $query->where('id', $this->staffId);
        }

        $staffs = $query->get()->map(function($s) {
            $hadir = AbsensiStaff::where('staff_akademik_id', $s->id)
                ->whereMonth('tanggal', $this->bulan)
                ->whereYear('tanggal', $this->tahun)
                ->whereIn('status', ['Hadir', 'Terlambat'])
                ->count();
            
            $target = $s->kpiBulanan->first()?->target_hari ?? 0;
            $persentase = $target > 0 ? round(($hadir / $target) * 100) : 0;
            if ($persentase > 100) $persentase = 100;

            $s->kpi_hadir = $hadir;
            $s->kpi_target = $target;
            $s->kpi_persentase = $persentase;
            return $s;
        });

        return $staffs;
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama Staff Akademik',
            'Bulan',
            'Tahun',
            'Target Kehadiran (Hari)',
            'Total Hadir (Hari)',
            'Persentase Pencapaian (%)'
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->nip ?? '-',
            $staff->nama_lengkap,
            Carbon::create()->month((int) $this->bulan)->translatedFormat('F'),
            $this->tahun,
            $staff->kpi_target,
            $staff->kpi_hadir,
            $staff->kpi_persentase . '%'
        ];
    }
}
