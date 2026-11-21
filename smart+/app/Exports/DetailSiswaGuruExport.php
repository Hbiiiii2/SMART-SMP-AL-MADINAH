<?php

namespace App\Exports;

use App\Models\AbsensiSiswa;
use App\Models\Jadwal;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailSiswaGuruExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $siswaId;
    protected $jadwalId;
    protected $startDate;
    protected $endDate;

    public function __construct(int $siswaId, int $jadwalId, $startDate, $endDate)
    {
        $this->siswaId = $siswaId;
        $this->jadwalId = $jadwalId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $siswa = Siswa::find($this->siswaId);
        $jadwal = Jadwal::with('mataPelajaran')->find($this->jadwalId);
        
        $query = AbsensiSiswa::with('siswa', 'jadwal.mataPelajaran')
            ->where('siswa_id', $this->siswaId)
            ->where('jadwal_id', $this->jadwalId)
            ->orderBy('tanggal');
            
        if ($this->startDate) {
            $query->where('tanggal', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('tanggal', '<=', $this->endDate);
        }

        $absensis = $query->get();

        $start = $this->startDate ? Carbon::parse($this->startDate) : ($absensis->first() ? Carbon::parse($absensis->first()->tanggal) : Carbon::now('Asia/Jakarta'));
        $end = $this->endDate ? Carbon::parse($this->endDate) : ($absensis->last() ? Carbon::parse($absensis->last()->tanggal) : Carbon::now('Asia/Jakarta'));

        if ($start->format('Y-m') === $end->format('Y-m')) {
            $periodeStr = strtoupper($start->translatedFormat('F Y'));
        } else {
            $periodeStr = strtoupper($start->translatedFormat('d M Y')) . ' - ' . strtoupper($end->translatedFormat('d M Y'));
        }

        return view('exports.detail_siswa_guru', [
            'siswa' => $siswa,
            'jadwal' => $jadwal,
            'absensis' => $absensis,
            'periodeStr' => $periodeStr,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => [
                'font' => ['bold' => true],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}
