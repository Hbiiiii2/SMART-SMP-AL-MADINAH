<?php

namespace App\Exports;

use App\Models\Guru;
use App\Models\AbsensiGuru;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class KpiGuruIndividuExport implements FromView, ShouldAutoSize
{
    protected $bulan;
    protected $tahun;
    protected $guruId;

    public function __construct($bulan, $tahun, $guruId)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->guruId = $guruId;
    }

    public function view(): View
    {
        $guru = Guru::findOrFail($this->guruId);
        $absensi = AbsensiGuru::where('guru_id', $this->guruId)
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $periode = strtoupper(Carbon::create()->month((int) $this->bulan)->translatedFormat('F')) . ' ' . $this->tahun;

        return view('exports.kpi_guru_individu', [
            'guru' => $guru,
            'absensi' => $absensi,
            'periode' => $periode
        ]);
    }
}
