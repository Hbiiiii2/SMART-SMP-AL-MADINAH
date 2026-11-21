<?php

namespace App\Exports;

use App\Models\Guru;
use App\Models\AbsensiGuru;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class DetailGuruExport implements FromView, ShouldAutoSize
{
    protected $guruId;
    protected $startDate;
    protected $endDate;

    public function __construct($guruId, $startDate, $endDate)
    {
        $this->guruId = $guruId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $guru = Guru::findOrFail($this->guruId);
        $absensi = AbsensiGuru::where('guru_id', $this->guruId)
            ->whereBetween('tanggal', [$this->startDate, $this->endDate])
            ->orderBy('tanggal', 'asc')
            ->get();

        $periode = Carbon::parse($this->startDate)->translatedFormat('d F Y') . ' - ' . Carbon::parse($this->endDate)->translatedFormat('d F Y');

        return view('exports.kpi_guru_individu', [
            'guru' => $guru,
            'absensi' => $absensi,
            'periode' => $periode
        ]);
    }
}
