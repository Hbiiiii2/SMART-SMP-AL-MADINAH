<?php

namespace App\Exports;

use App\Models\AbsensiSiswa;
use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsensiSiswaExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?int $jadwalId = null,
        private ?array $jadwalIds = null,
    ) {}

    public function collection()
    {
        $query = AbsensiSiswa::with('siswa.user', 'jadwal.mataPelajaran', 'jadwal')
            ->orderBy('tanggal', 'desc')
            ->orderBy('jadwal_id');

        if ($this->startDate) {
            $query->where('tanggal', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('tanggal', '<=', $this->endDate);
        }
        if ($this->jadwalId) {
            $query->where('jadwal_id', $this->jadwalId);
        }
        if ($this->jadwalIds) {
            $query->whereIn('jadwal_id', $this->jadwalIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Mata Pelajaran',
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
            $absensi->siswa->nisn ?? '-',
            $absensi->siswa->nama_lengkap ?? '-',
            $absensi->siswa->kelas ?? '-',
            $absensi->jadwal?->mataPelajaran?->nama ?? '-',
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
