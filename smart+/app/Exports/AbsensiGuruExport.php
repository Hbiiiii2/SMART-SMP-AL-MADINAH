<?php

namespace App\Exports;

use App\Models\AbsensiGuru;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiGuruExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
    ) {}

    public function collection()
    {
        $query = AbsensiGuru::with('guru.user')
            ->orderBy('tanggal', 'desc');

        if ($this->startDate) {
            $query->where('tanggal', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('tanggal', '<=', $this->endDate);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'NIP',
            'Nama Guru',
            'Status',
            'Jam Masuk',
            'Jam Keluar',
            'Keterangan',
        ];
    }

    public function map($absensi): array
    {
        static $no = 0;
        return [
            ++$no,
            $absensi->tanggal,
            $absensi->guru->nip ?? '-',
            $absensi->guru->nama_lengkap ?? '-',
            $absensi->status,
            $absensi->jam_masuk ? $absensi->jam_masuk->format('H:i:s') : '-',
            $absensi->jam_keluar ? $absensi->jam_keluar->format('H:i:s') : '-',
            $absensi->keterangan ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
