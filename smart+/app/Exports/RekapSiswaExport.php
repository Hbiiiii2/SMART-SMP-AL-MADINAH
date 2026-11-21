<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapSiswaExport implements FromView, ShouldAutoSize, WithStyles
{
    private $data;
    private $startDate;
    private $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        return view('exports.rekap_siswa', [
            'data' => $this->data,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Format first row if needed, although inline styles usually work in FromView
        ];
    }
}
