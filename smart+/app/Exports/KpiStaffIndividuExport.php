<?php

namespace App\Exports;

use App\Models\StaffAkademik;
use App\Models\AbsensiStaff;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class KpiStaffIndividuExport implements FromView, ShouldAutoSize
{
    protected $bulan;
    protected $tahun;
    protected $staffId;

    public function __construct($bulan, $tahun, $staffId)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->staffId = $staffId;
    }

    public function view(): View
    {
        $staff = StaffAkademik::findOrFail($this->staffId);
        $absensi = AbsensiStaff::where('staff_akademik_id', $this->staffId)
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $periode = strtoupper(Carbon::create()->month((int) $this->bulan)->translatedFormat('F')) . ' ' . $this->tahun;

        return view('exports.kpi_staff_individu', [
            'staff' => $staff,
            'absensi' => $absensi,
            'periode' => $periode
        ]);
    }
}
