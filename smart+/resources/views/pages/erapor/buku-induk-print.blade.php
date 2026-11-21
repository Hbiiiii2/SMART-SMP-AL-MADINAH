<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transkrip Buku Induk - {{ $siswa->nama_lengkap }} ({{ $siswa->nisn }})</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.3;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .kop-table td {
            vertical-align: middle;
        }

        .kop-logo {
            width: 75px;
            text-align: center;
        }

        .kop-logo img {
            max-width: 70px;
            max-height: 70px;
        }

        .kop-text {
            text-align: center;
        }

        .kop-text h4 {
            margin: 0;
            font-size: 11pt;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .kop-text p {
            margin: 0;
            font-size: 9pt;
            color: #333;
        }

        .kop-divider {
            border-top: 2.5px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin: 4px 0 14px 0;
        }

        /* Document Title */
        .doc-title {
            text-align: center;
            margin-bottom: 14px;
        }

        .doc-title h3 {
            margin: 0;
            font-size: 12pt;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-title p {
            margin: 2px 0 0 0;
            font-size: 9.5pt;
            color: #444;
        }

        /* Biodata Table */
        .biodata-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10pt;
        }

        .biodata-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .biodata-label {
            width: 18%;
        }

        .biodata-colon {
            width: 2%;
            text-align: center;
        }

        .biodata-val {
            width: 30%;
            font-weight: bold;
        }

        /* Grade Matrix Table */
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 12px;
        }

        .grade-table th, .grade-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .grade-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        /* Signature block */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10pt;
        }

        .sig-table td {
            vertical-align: top;
            text-align: center;
            width: 50%;
        }

        .sig-space {
            height: 65px;
        }

        /* Floating Toolbar (screen only) */
        .no-print {
            background: #2563eb;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-family: sans-serif;
            font-size: 13px;
        }

        .no-print button {
            background: #fff;
            color: #2563eb;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 8px;
        }

        .no-print button:hover {
            background: #f0fdf4;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<!-- Action Toolbar (hidden on print) -->
<div class="no-print">
    <div>
        <strong>SMP Al Madinah</strong> — Pratinjau Lembar Cetak Transkrip Buku Induk Siswa
    </div>
    <div>
        <button onclick="window.print()">🖨️ Cetak Dokumen</button>
        <button onclick="window.close()" style="background: rgba(255,255,255,0.2); color: #fff;">Tutup</button>
    </div>
</div>

<div class="container" style="padding-top: 15px;">
    <!-- Kop Surat Resmi -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($schoolSettings?->logo))
                    <img src="{{ asset('storage/' . $schoolSettings->logo) }}" alt="Logo">
                @else
                    <div style="font-weight: bold; font-size: 24pt;">AM</div>
                @endif
            </td>
            <td class="kop-text">
                <h4>YAYASAN KELUARGA BESAR MASYARAKAT BANTEN (KKMB)</h4>
                <h2>SMP AL - MADINAH ISLAMIC CENTER</h2>
                <p>{{ $schoolSettings?->alamat_sekolah ?? 'JL. PUSPITA RAYA BLOK L1 No.IA Sektor III.2 BSD City' }}</p>
                <p>Telp: {{ $schoolSettings?->no_telepon_sekolah ?? '08970555677' }} | Email: {{ $schoolSettings?->email_sekolah ?? 'smpamickkmb@gmail.com' }}</p>
            </td>
        </tr>
    </table>
    <div class="kop-divider"></div>

    <!-- Judul Dokumen -->
    <div class="doc-title">
        <h3>BUKU INDUK PESERTA DIDIK</h3>
        <p>TRANSKRIP NILAI PRESTASI AKADEMIK LINTAS SEMESTER</p>
    </div>

    <!-- Identitas Peserta Didik -->
    <table class="biodata-table">
        <tr>
            <td class="biodata-label">Nama Peserta Didik</td>
            <td class="biodata-colon">:</td>
            <td class="biodata-val">{{ strtoupper($siswa->nama_lengkap) }}</td>
            <td class="biodata-label">Kelas Terakhir</td>
            <td class="biodata-colon">:</td>
            <td class="biodata-val">{{ $siswa->kelas }}</td>
        </tr>
        <tr>
            <td class="biodata-label">NISN</td>
            <td class="biodata-colon">:</td>
            <td class="biodata-val">{{ $siswa->nisn }}</td>
            <td class="biodata-label">Status Siswa</td>
            <td class="biodata-colon">:</td>
            <td class="biodata-val">{{ $siswa->is_alumni ? 'ALUMNI (LULUS)' : 'AKTIF' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Jenis Kelamin</td>
            <td class="biodata-colon">:</td>
            <td class="biodata-val">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <td class="biodata-label">Tahun Masuk</td>
            <td class="biodata-colon">:</td>
            <td class="biodata-val">{{ $siswa->tahunAjaran?->nama ?? '-' }}</td>
        </tr>
    </table>

    <!-- Matriks Nilai Lintas Semester -->
    <table class="grade-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">No</th>
                <th rowspan="2">Mata Pelajaran</th>
                <th colspan="{{ count($periods) }}">Semester / Rombel</th>
                <th rowspan="2" style="width: 70px;">Rata-rata Kumulatif</th>
            </tr>
            <tr>
                @foreach($periods as $p)
                    <th style="font-size: 8pt; width: 65px;">
                        {{ $p->kelas }}<br>
                        ({{ $p->semester }})
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $idx => $mapel)
                @php
                    $scores = [];
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $mapel->nama }}</td>
                    @foreach($periods as $p)
                        @php
                            $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                            $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                            $rec = $allSummaries->where('mata_pelajaran_id', $mapel->id)
                                ->where('tahun_ajaran_id', $p->tahun_ajaran_id)
                                ->where('semester', $p->semester)
                                ->first();
                            $val = $hasUasInPeriod
                                ? ($rec?->final_score ?? $rec?->sts_score)
                                : ($rec?->sts_score ?? $rec?->final_score);
                            if ($val !== null) {
                                $scores[] = (float) $val;
                            }
                        @endphp
                        <td class="text-center">{{ $val !== null ? number_format($val, 1) : '-' }}</td>
                    @endforeach
                    <td class="text-center font-bold">
                        {{ !empty($scores) ? number_format(array_sum($scores) / count($scores), 1) : '-' }}
                    </td>
                </tr>
            @endforeach

            <!-- Rata-rata Semester -->
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="2" class="text-center">RATA-RATA SEMESTER</td>
                @foreach($periods as $p)
                    @php
                        $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                        $stsInfo = $stsSummaries[$pKey] ?? null;
                        $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                        $pAvg = $hasUasInPeriod
                            ? ($stsInfo?->uas_average_score ?? $stsInfo?->average_score)
                            : ($stsInfo?->average_score ?? $stsInfo?->uas_average_score);
                    @endphp
                    <td class="text-center">{{ $pAvg !== null ? number_format($pAvg, 1) : '-' }}</td>
                @endforeach
                <td class="text-center font-bold">{{ $cumulativeAvg !== null ? number_format($cumulativeAvg, 1) : '-' }}</td>
            </tr>

            <!-- Peringkat -->
            <tr style="font-size: 8.5pt;">
                <td colspan="2" class="text-center">Peringkat di Kelas</td>
                @foreach($periods as $p)
                    @php
                        $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                        $stsInfo = $stsSummaries[$pKey] ?? null;
                        $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                        $pRank = $hasUasInPeriod
                            ? ($stsInfo?->uas_rank ?? $stsInfo?->rank)
                            : ($stsInfo?->rank ?? $stsInfo?->uas_rank);
                    @endphp
                    <td class="text-center">{{ $pRank ? 'Ke-' . $pRank : '-' }}</td>
                @endforeach
                <td class="text-center">-</td>
            </tr>

            <!-- Kehadiran -->
            <tr style="font-size: 8.5pt;">
                <td colspan="2" class="text-center">Kehadiran (Sakit / Izin / Alfa)</td>
                <td colspan="{{ count($periods) }}" class="text-center">
                    Sakit: {{ $attendance->s ?? 0 }} hari | Izin: {{ $attendance->i ?? 0 }} hari | Alfa: {{ $attendance->a ?? 0 }} hari
                </td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <table class="sig-table">
        <tr>
            <td>
                Mengetahui,<br>
                Orang Tua / Wali Peserta Didik
                <div class="sig-space"></div>
                ___________________________
            </td>
            <td>
                Tangerang Selatan, {{ date('d F Y') }}<br>
                Kepala Sekolah
                <div class="sig-space"></div>
                <strong><u>Drs. H. M. Kosasih, M.Pd</u></strong><br>
                NIP. 197103151998021002
            </td>
        </tr>
    </table>
</div>

</body>
</html>
