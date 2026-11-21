<?php

namespace App\Exports;

use App\Models\Siswa;
use App\Models\AbsensiSiswa;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class DetailSiswaRfidExport implements FromView, ShouldAutoSize, WithStyles
{
    private $siswaId;
    private $startDate;
    private $endDate;

    public function __construct(int $siswaId, string $startDate, string $endDate)
    {
        $this->siswaId = $siswaId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $siswa = Siswa::find($this->siswaId);
        
        $firstAbsensiSubquery = AbsensiSiswa::select('tanggal', DB::raw('MIN(id) as first_id'))
            ->where('siswa_id', $this->siswaId)
            ->whereBetween('tanggal', [$this->startDate, $this->endDate])
            ->groupBy('tanggal');

        $data = AbsensiSiswa::joinSub($firstAbsensiSubquery, 'first_absensi', function ($join) {
                $join->on('absensi_siswa.id', '=', 'first_absensi.first_id');
            })
            ->where('absensi_siswa.siswa_id', $this->siswaId)
            ->orderBy('absensi_siswa.tanggal', 'asc')
            ->get();

        return view('exports.detail_siswa_rfid', [
            'siswa' => $siswa,
            'data' => $data,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // styling can be done inline in blade
        ];
    }
}
