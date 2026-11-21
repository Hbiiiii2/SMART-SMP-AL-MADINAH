<?php

namespace App\Exports;

use App\Models\Tugas;
use App\Models\PengumpulanTugas;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class NilaiTugasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithEvents, WithCustomStartCell
{
    protected $tugas_id;
    protected $tugas;
    protected $rowNumber = 0;

    public function __construct($tugas_id)
    {
        $this->tugas_id = $tugas_id;
        $this->tugas = Tugas::with('mataPelajaran')->find($tugas_id);
    }

    public function collection()
    {
        return PengumpulanTugas::with('siswa')
            ->where('tugas_id', $this->tugas_id)
            ->get();
    }

    public function map($pengumpulan): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $pengumpulan->siswa->nisn ?? '-',
            $pengumpulan->siswa->nama_lengkap ?? '-',
            $pengumpulan->siswa->kelas ?? '-',
            $pengumpulan->waktu_pengumpulan ? $pengumpulan->waktu_pengumpulan->format('d/m/Y H:i') : '-',
            ucfirst($pengumpulan->status),
            $pengumpulan->nilai ?? 0,
            $pengumpulan->catatan_guru ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Waktu Pengumpulan',
            'Status',
            'Nilai',
            'Catatan Guru'
        ];
    }

    public function title(): string
    {
        return substr('Nilai ' . $this->tugas->judul, 0, 31);
    }
    
    public function startCell(): string
    {
        return 'A4';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->setCellValue('A1', 'REKAPITULASI NILAI TUGAS');
                $event->sheet->setCellValue('A2', 'Tugas: ' . $this->tugas->judul . ' - ' . ($this->tugas->mataPelajaran->nama_pelajaran ?? ''));
                $event->sheet->mergeCells('A1:H1');
                $event->sheet->mergeCells('A2:H2');
                
                $event->sheet->getStyle('A1:H2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $event->sheet->getStyle('A4:H4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFEFEFEF',
                        ],
                    ],
                ]);

                $highestRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('A5:H' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
        ];
    }
}
