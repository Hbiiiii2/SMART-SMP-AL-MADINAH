<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\StudentStsSummary;
use App\Models\SubjectGradeSummary;
use App\Models\TahunAjaran;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EraporExportService
{
    /**
     * Export Class STS or UAS and Attendance Recap Spreadsheet
     */
    public function generateClassSTSRecap(string $kelas, int $tahunAjaranId, string $semester = 'Ganjil', string $mode = 'sts'): Spreadsheet
    {
        $isUasMode = ($mode === 'uas');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($isUasMode ? 'Rekap Nilai UAS' : 'Rekap Nilai STS');

        $tahunAjaran = TahunAjaran::find($tahunAjaranId);
        $tahunNama = $tahunAjaran?->nama ?? '2026/2027';

        // Title Block
        $sheet->setCellValue('A1', 'SMP AL MADINAH');
        $sheet->setCellValue('A2', $isUasMode ? 'REKAPITULASI NILAI AKHIR SEMESTER (UAS)' : 'REKAPITULASI HASIL SUMATIF TENGAH SEMESTER (STS 1)');
        $sheet->setCellValue('A3', "Kelas: {$kelas} | Tahun Ajaran: {$tahunNama} - Semester {$semester}");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);

        $subjects = MataPelajaran::orderBy('nama')->get();

        $historicalStudentIds = \App\Models\RiwayatKelasSiswa::where('kelas', $kelas)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->pluck('siswa_id');

        if ($historicalStudentIds->isNotEmpty()) {
            $students = Siswa::whereIn('id', $historicalStudentIds)->orderBy('nama_lengkap')->get();
        } else {
            $students = Siswa::where('kelas', $kelas)->orderBy('nama_lengkap')->get();
        }

        // Build Table Headers
        $row = 5;
        $sheet->setCellValue('A' . $row, 'No');
        $sheet->setCellValue('B' . $row, 'NISN');
        $sheet->setCellValue('C' . $row, 'Nama Lengkap Siswa');

        $colIndex = 4; // Column D
        $subjectColMap = [];

        foreach ($subjects as $mapel) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . $row, $mapel->nama);
            $subjectColMap[$mapel->id] = $colLetter;
            $colIndex++;
        }

        // Summary columns
        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $avgCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $rankCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);

        // Attendance columns from SMART+
        $sakitCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $izinCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
        $alfaCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);

        $sheet->setCellValue($totalCol . $row, $isUasMode ? 'Total UAS' : 'Total STS');
        $sheet->setCellValue($avgCol . $row, 'Rata-rata');
        $sheet->setCellValue($rankCol . $row, 'Peringkat');
        $sheet->setCellValue($sakitCol . $row, 'Sakit');
        $sheet->setCellValue($izinCol . $row, 'Izin');
        $sheet->setCellValue($alfaCol . $row, 'Alfa');

        // Style header row
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex - 1);
        $headerRange = "A{$row}:{$lastColLetter}{$row}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0'); // Light grey/slate
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Populate student data
        $no = 1;
        $dataStartRow = $row + 1;
        $currentRow = $dataStartRow;

        foreach ($students as $siswa) {
            $sheet->setCellValue('A' . $currentRow, $no++);
            // Keep NISN as string
            $sheet->setCellValueExplicit('B' . $currentRow, (string) ($siswa->nisn ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $currentRow, $siswa->nama_lengkap);

            // Fetch student STS/UAS scores per mapel
            $gradeSummaries = SubjectGradeSummary::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->get()
                ->keyBy('mata_pelajaran_id');

            foreach ($subjects as $mapel) {
                $colLetter = $subjectColMap[$mapel->id];
                $rec = $gradeSummaries->get($mapel->id);
                $val = $isUasMode ? ($rec?->final_score ?? $rec?->sts_score) : $rec?->sts_score;
                if ($val !== null) {
                    $sheet->setCellValue($colLetter . $currentRow, (float) $val);
                } else {
                    $sheet->setCellValue($colLetter . $currentRow, '-');
                }
            }

            // Fetch STS/UAS Summary and Ranking
            $stsSummary = StudentStsSummary::where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester)
                ->first();

            $totalVal = $isUasMode ? ($stsSummary?->uas_total_score ?? $stsSummary?->total_score) : $stsSummary?->total_score;
            $avgVal = $isUasMode ? ($stsSummary?->uas_average_score ?? $stsSummary?->average_score) : $stsSummary?->average_score;
            $rankVal = $isUasMode ? ($stsSummary?->uas_rank ?? $stsSummary?->rank) : $stsSummary?->rank;

            $sheet->setCellValue($totalCol . $currentRow, $totalVal !== null ? (float) $totalVal : '-');
            $sheet->setCellValue($avgCol . $currentRow, $avgVal !== null ? (float) $avgVal : '-');
            $sheet->setCellValue($rankCol . $currentRow, $rankVal ?? '-');

            // Fetch Attendance Counts directly from SMART+
            $attCounts = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->selectRaw("
                    COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                    COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                    COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
                ")
                ->first();

            $sheet->setCellValue($sakitCol . $currentRow, (int) ($attCounts->s ?? 0));
            $sheet->setCellValue($izinCol . $currentRow, (int) ($attCounts->i ?? 0));
            $sheet->setCellValue($alfaCol . $currentRow, (int) ($attCounts->a ?? 0));

            $currentRow++;
        }

        $dataEndRow = $currentRow - 1;

        // Apply borders and auto-fit column width
        $allTableRange = "A{$row}:{$lastColLetter}{$dataEndRow}";
        $sheet->getStyle($allTableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, $colIndex - 1) as $cIdx) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getColumnDimension($colString)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Export Comprehensive Student Academic Transcript / Buku Induk
     */
    public function generateStudentTranscript(Siswa $siswa): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transkrip Buku Induk');

        // Header Title
        $sheet->setCellValue('A1', 'SMP AL MADINAH ISLAMIC CENTER');
        $sheet->setCellValue('A2', 'BUKU INDUK PESERTA DIDIK - TRANSKRIP NILAI AKADEMIK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Student Info
        $statusStr = $siswa->is_alumni ? 'ALUMNI / LULUS' : 'AKTIF';
        $sheet->setCellValue('A4', 'NISN');
        $sheet->setCellValueExplicit('B4', (string) $siswa->nisn, DataType::TYPE_STRING);
        $sheet->setCellValue('A5', 'Nama Lengkap');
        $sheet->setCellValue('B5', $siswa->nama_lengkap);
        $sheet->setCellValue('A6', 'Jenis Kelamin');
        $sheet->setCellValue('B6', $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan');

        $sheet->setCellValue('E4', 'Kelas Terakhir');
        $sheet->setCellValue('F4', $siswa->kelas);
        $sheet->setCellValue('E5', 'Status Siswa');
        $sheet->setCellValue('F5', $statusStr);
        $sheet->setCellValue('E6', 'Tanggal Cetak');
        $sheet->setCellValue('F6', date('d/m/Y'));

        $sheet->getStyle('A4:A6')->getFont()->setBold(true);
        $sheet->getStyle('E4:E6')->getFont()->setBold(true);

        // Fetch distinct academic periods
        $periods = \App\Models\RiwayatKelasSiswa::where('siswa_id', $siswa->id)
            ->with('tahunAjaran')
            ->orderBy('tahun_ajaran_id')
            ->orderBy('semester')
            ->get();

        if ($periods->isEmpty()) {
            $activeYear = TahunAjaran::where('is_aktif', true)->first();
            $periods = collect([(object) [
                'tahun_ajaran_id' => $activeYear?->id ?? 1,
                'semester' => 'Ganjil',
                'kelas' => $siswa->kelas,
                'tahunAjaran' => $activeYear,
            ]]);
        }

        // Table Header
        $row = 8;
        $sheet->setCellValue('A' . $row, 'No');
        $sheet->setCellValue('B' . $row, 'Mata Pelajaran');

        $colIdx = 3; // Column C
        $periodColMap = [];

        foreach ($periods as $p) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $yearName = $p->tahunAjaran?->nama ?? 'Tahun';
            $sheet->setCellValue($colLetter . $row, "{$p->kelas}\n({$yearName} {$p->semester})");
            $sheet->getStyle($colLetter . $row)->getAlignment()->setWrapText(true);
            $periodColMap[] = [
                'col' => $colLetter,
                'tahun_ajaran_id' => $p->tahun_ajaran_id,
                'semester' => $p->semester,
            ];
            $colIdx++;
        }

        $avgCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($avgCol . $row, "Rata-rata\nKumulatif");
        $sheet->getStyle($avgCol . $row)->getAlignment()->setWrapText(true);

        $lastCol = $avgCol;
        $headerRange = "A{$row}:{$lastCol}{$row}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Fetch Grades
        $subjects = MataPelajaran::orderBy('nama')->get();
        $allSummaries = SubjectGradeSummary::where('siswa_id', $siswa->id)->get();
        $uasPeriodMap = [];
        foreach ($periodColMap as $pInfo) {
            $pKey = $pInfo['tahun_ajaran_id'] . '_' . $pInfo['semester'];
            $uasPeriodMap[$pKey] = $allSummaries
                ->where('tahun_ajaran_id', $pInfo['tahun_ajaran_id'])
                ->where('semester', $pInfo['semester'])
                ->whereNotNull('uas_score')
                ->isNotEmpty();
        }

        $currentRow = $row + 1;
        $no = 1;

        foreach ($subjects as $mapel) {
            $sheet->setCellValue('A' . $currentRow, $no++);
            $sheet->setCellValue('B' . $currentRow, $mapel->nama);

            $subjectScores = [];
            foreach ($periodColMap as $pInfo) {
                $pKey = $pInfo['tahun_ajaran_id'] . '_' . $pInfo['semester'];
                $rec = $allSummaries->where('mata_pelajaran_id', $mapel->id)
                    ->where('tahun_ajaran_id', $pInfo['tahun_ajaran_id'])
                    ->where('semester', $pInfo['semester'])
                    ->first();

                $val = !empty($uasPeriodMap[$pKey])
                    ? ($rec?->final_score ?? $rec?->sts_score)
                    : ($rec?->sts_score ?? $rec?->final_score);
                if ($val !== null) {
                    $sheet->setCellValue($pInfo['col'] . $currentRow, (float) $val);
                    $subjectScores[] = (float) $val;
                } else {
                    $sheet->setCellValue($pInfo['col'] . $currentRow, '-');
                }
                $sheet->getStyle($pInfo['col'] . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $subjectAvg = !empty($subjectScores) ? round(array_sum($subjectScores) / count($subjectScores), 2) : '-';
            $sheet->setCellValue($avgCol . $currentRow, $subjectAvg);
            $sheet->getStyle($avgCol . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($avgCol . $currentRow)->getFont()->setBold(true);

            $currentRow++;
        }

        // Summary Rows (Semester Avg, Rank, Attendance)
        $avgRow = $currentRow;
        $sheet->setCellValue('A' . $avgRow, '');
        $sheet->setCellValue('B' . $avgRow, 'Rata-rata Semester');
        $sheet->getStyle('B' . $avgRow)->getFont()->setBold(true);

        $stsSummaries = StudentStsSummary::where('siswa_id', $siswa->id)->get();
        $grandScores = [];

        foreach ($periodColMap as $pInfo) {
            $pKey = $pInfo['tahun_ajaran_id'] . '_' . $pInfo['semester'];
            $sumRec = $stsSummaries->where('tahun_ajaran_id', $pInfo['tahun_ajaran_id'])
                ->where('semester', $pInfo['semester'])
                ->first();

            $val = !empty($uasPeriodMap[$pKey])
                ? ($sumRec?->uas_average_score ?? $sumRec?->average_score)
                : ($sumRec?->average_score ?? $sumRec?->uas_average_score);
            if ($val !== null) {
                $sheet->setCellValue($pInfo['col'] . $avgRow, (float) $val);
                $grandScores[] = (float) $val;
            } else {
                $sheet->setCellValue($pInfo['col'] . $avgRow, '-');
            }
            $sheet->getStyle($pInfo['col'] . $avgRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($pInfo['col'] . $avgRow)->getFont()->setBold(true);
        }

        $grandAvg = !empty($grandScores) ? round(array_sum($grandScores) / count($grandScores), 2) : '-';
        $sheet->setCellValue($avgCol . $avgRow, $grandAvg);
        $sheet->getStyle($avgCol . $avgRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($avgCol . $avgRow)->getFont()->setBold(true);

        $rankRow = $avgRow + 1;
        $sheet->setCellValue('A' . $rankRow, '');
        $sheet->setCellValue('B' . $rankRow, 'Peringkat di Kelas');
        $sheet->getStyle('B' . $rankRow)->getFont()->setBold(true);

        foreach ($periodColMap as $pInfo) {
            $pKey = $pInfo['tahun_ajaran_id'] . '_' . $pInfo['semester'];
            $sumRec = $stsSummaries->where('tahun_ajaran_id', $pInfo['tahun_ajaran_id'])
                ->where('semester', $pInfo['semester'])
                ->first();

            $rankVal = !empty($uasPeriodMap[$pKey])
                ? ($sumRec?->uas_rank ?? $sumRec?->rank ?? '-')
                : ($sumRec?->rank ?? $sumRec?->uas_rank ?? '-');
            $sheet->setCellValue($pInfo['col'] . $rankRow, $rankVal);
            $sheet->getStyle($pInfo['col'] . $rankRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->setCellValue($avgCol . $rankRow, '-');

        // Attendance
        $attRow = $rankRow + 1;
        $sheet->setCellValue('A' . $attRow, '');
        $sheet->setCellValue('B' . $attRow, 'Kehadiran (Sakit / Izin / Alfa)');
        $sheet->getStyle('B' . $attRow)->getFont()->setBold(true);

        foreach ($periodColMap as $pInfo) {
            $att = AbsensiSiswa::where('siswa_id', $siswa->id)
                ->selectRaw("
                    COUNT(DISTINCT CASE WHEN LOWER(status) = 'sakit' THEN tanggal END) as s,
                    COUNT(DISTINCT CASE WHEN LOWER(status) = 'izin' THEN tanggal END) as i,
                    COUNT(DISTINCT CASE WHEN LOWER(status) IN ('alfa', 'alpha') THEN tanggal END) as a
                ")
                ->first();

            $sheet->setCellValue($pInfo['col'] . $attRow, "{$att->s} / {$att->i} / {$att->a}");
            $sheet->getStyle($pInfo['col'] . $attRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->setCellValue($avgCol . $attRow, '-');

        // Styling
        $tableRange = "A{$row}:{$lastCol}{$attRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, $colIdx) as $cIdx) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
