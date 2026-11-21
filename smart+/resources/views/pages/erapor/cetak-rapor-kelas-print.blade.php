<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $paperSize = $raporSettings?->ukuran_kertas ?? 'A4';
        $cssPageSize = match($paperSize) {
            'F4' => '215mm 330mm',
            'Letter' => 'letter',
            default => 'A4',
        };
        $showRanking = $raporSettings?->show_ranking ?? true;
        $isSts = ($jenisRapor === 'sts');

        // Resolve logo safely (base64 if file exists on disk to prevent broken image icon)
        $logoSrc = null;
        if (!empty($schoolSettings?->logo)) {
            $rawLogo = ltrim($schoolSettings->logo, '/');
            $candidates = [
                storage_path('app/public/' . $rawLogo),
                public_path('storage/' . $rawLogo),
                public_path($rawLogo),
            ];
            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $mime = mime_content_type($candidate) ?: 'image/png';
                    $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
                    break;
                }
            }
        }
    @endphp
    <title>Cetak Semua Rapor Kelas {{ $kelas }} - {{ strtoupper($jenisRapor) }} ({{ $semester }} {{ $targetYear->nama }})</title>
    <style>
        @page {
            size: {{ $cssPageSize }} portrait;
            margin: 8mm 14mm 8mm 14mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 9.5pt;
            line-height: 1.25;
        }

        .rapor-page {
            width: 100%;
            max-width: 780px;
            margin: 0 auto;
            padding-top: 4px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Page Break for Bulk Print */
        .page-break {
            page-break-after: always;
            break-after: page;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        .kop-table td {
            vertical-align: middle;
        }

        .kop-logo {
            width: 68px;
            text-align: center;
        }

        .kop-logo img {
            max-width: 58px;
            max-height: 58px;
            object-fit: contain;
        }

        .kop-logo-badge {
            width: 52px;
            height: 52px;
            border: 1.5px solid #000;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14pt;
            letter-spacing: 0.5px;
        }

        .kop-text {
            text-align: center;
            padding-right: 30px;
        }

        .kop-text h4 {
            margin: 0;
            font-size: 9.5pt;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kop-text p {
            margin: 0;
            font-size: 8.5pt;
            color: #222;
            line-height: 1.2;
        }

        .kop-divider {
            border-top: 2px solid #000;
            border-bottom: 0.5px solid #000;
            height: 1px;
            margin: 4px 0 9px 0;
        }

        /* Document Title */
        .doc-title {
            text-align: center;
            margin-bottom: 9px;
        }

        .doc-title h3 {
            margin: 0;
            font-size: 10.5pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .doc-title p {
            margin: 2px 0 0 0;
            font-size: 9pt;
            color: #222;
            font-weight: bold;
        }

        /* Biodata */
        .biodata-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 9px;
        }

        .biodata-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .biodata-label {
            width: 17%;
        }

        .biodata-colon {
            width: 2%;
            text-align: center;
        }

        .biodata-val {
            width: 35%;
            font-weight: bold;
        }

        .biodata-label-right {
            width: 15%;
        }

        .biodata-val-right {
            width: 29%;
            font-weight: bold;
        }

        /* Grade Table */
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 9px;
        }

        .grade-table th, .grade-table td {
            border: 1px solid #000;
            padding: {{ $isSts ? '4px 6px' : '3px 5px' }};
            line-height: 1.2;
        }

        .grade-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            padding: 5px 6px;
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

        /* Attendance & Notes */
        .footer-keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 8.5pt;
        }

        .info-box-cell {
            border: 1px solid #000;
            padding: 7px 10px;
            vertical-align: top;
        }

        .info-spacer-cell {
            width: 12px;
            border: none;
        }

        .sub-box-title {
            font-weight: bold;
            font-size: 8.5pt;
            margin-bottom: 5px;
            border-bottom: 0.5px solid #999;
            padding-bottom: 3px;
        }

        /* Signature block */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9pt;
            line-height: 1.35;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-table td {
            vertical-align: top;
            text-align: center;
            width: 33.33%;
        }

        .sig-space {
            height: 50px;
        }

        /* Toolbar (screen only) */
        .no-print {
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: sans-serif;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .no-print button {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            margin-left: 8px;
        }

        .no-print button:hover {
            background: #059669;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .rapor-page {
                padding-top: 0 !important;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Action Toolbar (hidden on print) -->
<div class="no-print">
    <div>
        <strong>SMP Al Madinah</strong> — Cetak Masal Rapor {{ strtoupper($jenisRapor) }} Kelas {{ $kelas }} ({{ count($raporData) }} Siswa • Kertas {{ $paperSize }})
    </div>
    <div style="display: flex; align-items: center;">
        <a href="{{ route('erapor.cetak.kelas', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id, 'jenis_rapor' => 'sts']) }}" style="text-decoration: none; color: #fff; background: {{ $jenisRapor === 'sts' ? '#d97706' : 'rgba(255,255,255,0.15)' }}; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: bold; margin-left: 6px;">
            Opsi 1: Rapor STS
        </a>
        <a href="{{ route('erapor.cetak.kelas', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id, 'jenis_rapor' => 'uas']) }}" style="text-decoration: none; color: #fff; background: {{ $jenisRapor !== 'sts' ? '#2563eb' : 'rgba(255,255,255,0.15)' }}; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: bold; margin-left: 6px;">
            Opsi 2: Rapor UAS
        </a>
        <button onclick="window.print()">🖨️ Cetak Semua ({{ count($raporData) }} Lembar {{ $paperSize }})</button>
        <button onclick="window.close()" style="background: rgba(255,255,255,0.2);">Tutup</button>
    </div>
</div>

@foreach($raporData as $dataIdx => $item)
    @php
        $siswa = $item['siswa'];
        $gradeItems = $item['gradeItems'];
        $totalNilai = $item['totalNilai'];
        $rataRata = $item['rataRata'];
        $rataRataKelasItem = $item['rataRataKelas'] ?? $rataRataKelas ?? null;
        $displayRank = $item['displayRank'] ?? null;
        $stsSummary = $item['stsSummary'];
        $attendance = $item['attendance'];
        $isLast = $dataIdx === (count($raporData) - 1);
    @endphp

    <div class="rapor-page {{ !$isLast ? 'page-break' : '' }}">
        <!-- Kop Surat Resmi -->
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo">
                    @else
                        <div class="kop-logo-badge">AM</div>
                    @endif
                </td>
                <td class="kop-text">
                    <h4>{{ $raporSettings?->header_yayasan ?? 'YAYASAN KELUARGA BESAR MASYARAKAT BANTEN (KKMB)' }}</h4>
                    <h2>{{ $raporSettings?->header_sekolah ?? 'SMP AL - MADINAH ISLAMIC CENTER' }}</h2>
                    <p>{{ $schoolSettings?->alamat_sekolah ?? 'JL. PUSPITA RAYA BLOK L1 No.IA Sektor III.2 BSD City' }}</p>
                    <p>Telp: {{ $schoolSettings?->no_telepon_sekolah ?? '08970555677' }} | Email: {{ $schoolSettings?->email_sekolah ?? 'smpamickkmb@gmail.com' }}</p>
                </td>
            </tr>
        </table>
        <div class="kop-divider"></div>

        <!-- Judul Dokumen -->
        <div class="doc-title">
            <h3>
                {{ $raporSettings?->getEffectiveJudulRapor($jenisRapor) ?? ($jenisRapor === 'sts' ? 'LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS)' : 'LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK') }}
            </h3>
            <p>
                @if($raporSettings?->show_kurikulum && !empty($raporSettings?->nama_kurikulum))
                    {{ strtoupper($raporSettings->nama_kurikulum) }} — 
                @endif
                TAHUN AJARAN {{ strtoupper($targetYear->nama) }}
            </p>
        </div>

        <!-- Identitas Peserta Didik -->
        <table class="biodata-table">
            <tr>
                <td class="biodata-label">Nama Peserta Didik</td>
                <td class="biodata-colon">:</td>
                <td class="biodata-val">{{ strtoupper($siswa->nama_lengkap) }}</td>

                <td class="biodata-label-right">Kelas{{ $raporSettings?->show_fase ? ' / Fase' : '' }}</td>
                <td class="biodata-colon">:</td>
                <td class="biodata-val-right">{{ $siswa->kelas }}{{ $raporSettings?->show_fase ? ' / ' . ($raporSettings->fase_text ?: 'Fase D') : '' }}</td>
            </tr>
            <tr>
                <td class="biodata-label">NISN / NIS</td>
                <td class="biodata-colon">:</td>
                <td class="biodata-val">{{ $siswa->nisn }}</td>

                <td class="biodata-label-right">Semester</td>
                <td class="biodata-colon">:</td>
                <td class="biodata-val-right">{{ $semester }} ({{ $semester === 'Ganjil' ? 'Satu' : 'Dua' }})</td>
            </tr>
            <tr>
                <td class="biodata-label">Nama Sekolah</td>
                <td class="biodata-colon">:</td>
                <td class="biodata-val">SMP Al Madinah</td>

                <td class="biodata-label-right">Tahun Pelajaran</td>
                <td class="biodata-colon">:</td>
                <td class="biodata-val-right">{{ $targetYear->nama }}</td>
            </tr>
        </table>

        <!-- Tabel Capaian Nilai Akademik -->
        @if($jenisRapor === 'sts')
            @php
                $fmtScore = function ($val) {
                    if ($val === null || $val === '') return '-';
                    $num = (float) $val;
                    return floor($num) == $num ? number_format($num, 0) : number_format($num, 1);
                };
            @endphp
            <table class="grade-table" style="font-size: 8.5pt;">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 24px; vertical-align: middle;">No</th>
                        <th rowspan="2" style="text-align: center; vertical-align: middle;">Mata Pelajaran</th>
                        @if(!empty($showKkm))
                            <th rowspan="2" style="width: 36px; text-align: center; vertical-align: middle;">KKM</th>
                        @endif
                        <th colspan="3" style="text-align: center;">Ulangan Harian</th>
                        <th colspan="3" style="text-align: center;">Tugas</th>
                        <th colspan="2" style="text-align: center;">Praktek</th>
                        <th colspan="2" style="text-align: center;">Projek</th>
                        <th colspan="2" style="text-align: center;">Portofolio</th>
                        <th rowspan="2" style="width: 52px; text-align: center; vertical-align: middle;">Nilai PTS</th>
                    </tr>
                    <tr>
                        @for($i = 1; $i <= 3; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 3; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 2; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 2; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                        @for($i = 1; $i <= 2; $i++)
                            <th style="width: 23px; text-align: center; padding: 2px 1px;">{{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeItems as $idx => $g)
                        @php
                            $skorSts = $g['nilai_sts'] ?? $g['sumatif'] ?? $g['nilai_rapor'];
                        @endphp
                        <tr>
                            <td class="text-center" style="padding: 3px 2px;">{{ $idx + 1 }}</td>
                            <td style="padding: 3px 6px; font-weight: 600;">{{ $g['nama'] }}</td>
                            @if(!empty($showKkm))
                                <td class="text-center font-bold" style="padding: 3px 2px;">
                                    {{ isset($g['kkm']) ? (floor($g['kkm']) == $g['kkm'] ? number_format($g['kkm'], 0) : number_format($g['kkm'], 1)) : '-' }}
                                </td>
                            @endif
                            @for($i = 1; $i <= 3; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['uh'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 3; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['tugas'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 2; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['praktek'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 2; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['projek'][$i] ?? null) }}</td>
                            @endfor
                            @for($i = 1; $i <= 2; $i++)
                                <td class="text-center" style="padding: 3px 1px;">{{ $fmtScore($g['portofolio'][$i] ?? null) }}</td>
                            @endfor
                            <td class="text-center font-bold" style="padding: 3px 2px;">
                                {{ $fmtScore($skorSts) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Tabel Ketidakhadiran & Jumlah + Tanda Tangan (Format STS/PTS) -->
            <div class="footer-keep-together">
                <table class="grade-table" style="margin-top: 10px; font-size: 8.5pt;">
                    <thead>
                        <tr>
                            <th colspan="3" style="text-align: center; width: 48%; padding: 4px;">Ketidakhadiran</th>
                            <th colspan="3" style="text-align: center; width: 52%; padding: 4px;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center" style="width: 24px; padding: 3px 2px;">1</td>
                            <td style="padding: 3px 6px;">Sakit</td>
                            <td class="text-center" style="width: 85px; padding: 3px 4px;">{{ $attendance->s ?? 0 }} Hari</td>
                            <td class="text-center" style="width: 24px; padding: 3px 2px;">4</td>
                            <td style="padding: 3px 6px;">Nilai Total PTS</td>
                            <td class="text-center font-bold" style="width: 85px; padding: 3px 4px;">
                                {{ $totalNilai > 0 ? $fmtScore($totalNilai) : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="padding: 3px 2px;">2</td>
                            <td style="padding: 3px 6px;">Izin</td>
                            <td class="text-center" style="padding: 3px 4px;">{{ $attendance->i ?? 0 }} Hari</td>
                            <td class="text-center" style="padding: 3px 2px;">5</td>
                            <td style="padding: 3px 6px;">Nilai Rata-rata Siswa</td>
                            <td class="text-center font-bold" style="padding: 3px 4px;">
                                {{ $rataRata !== null ? number_format($rataRata, 2) : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center" style="padding: 3px 2px;">3</td>
                            <td style="padding: 3px 6px;">Tanpa Keterangan</td>
                            <td class="text-center" style="padding: 3px 4px;">{{ $attendance->a ?? 0 }} Hari</td>
                            <td class="text-center" style="padding: 3px 2px;">6</td>
                            <td style="padding: 3px 6px;">Nilai Rata-rata Kelas</td>
                            <td class="text-center font-bold" style="padding: 3px 4px;">
                                {{ ($rataRataKelas ?? null) !== null ? number_format($rataRataKelas, 2) : '-' }}
                            </td>
                        </tr>
                        @if($showRanking)
                            <tr>
                                <td colspan="3" style="padding: 3px 6px; font-style: italic; font-size: 8pt; color: #333;">
                                    @if(!empty($stsSummary?->catatan_wali_kelas))
                                        Catatan: "{{ $stsSummary->catatan_wali_kelas }}"
                                    @endif
                                </td>
                                <td class="text-center" style="padding: 3px 2px;">7</td>
                                <td style="padding: 3px 6px; font-weight: bold;">Peringkat Kelas</td>
                                <td class="text-center font-bold" style="padding: 3px 4px;">
                                    @if($stsSummary?->rank)
                                        Ke-{{ $stsSummary->rank }} / {{ $totalSiswaKelas }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Tanda Tangan STS/PTS (2 Kolom: Wali Murid & Wali Kelas) -->
                <table class="sig-table" style="margin-top: 20px;">
                    <tr>
                        <td style="width: 50%; text-align: center;">
                            <br>
                            Wali Murid
                            <div class="sig-space"></div>
                            <strong>( ..................................................... )</strong>
                        </td>
                        <td style="width: 50%; text-align: center;">
                            {{ $raporSettings?->kota_pembagian ?? 'Bogor' }}, {{ $tanggalRaporFormatted }}<br>
                            Wali Kelas
                            <div class="sig-space" style="display: flex; align-items: center; justify-content: center;">
                                @if(!empty($homeroom?->ttd_wali_kelas))
                                    <img src="{{ asset('storage/' . $homeroom->ttd_wali_kelas) }}" alt="TTD Wali Kelas" style="max-height: 45px; max-width: 125px; object-fit: contain;">
                                @endif
                            </div>
                            <strong>( {{ $homeroom?->effective_nama ?? $waliKelas?->nama_lengkap ?? 'Wali Kelas' }} )</strong>
                        </td>
                    </tr>
                </table>
            </div>
        @else
            <table class="grade-table">
                <thead>
                    <tr>
                        <th style="width: 24px;">No</th>
                        <th style="width: 138px; text-align: left;">Mata Pelajaran</th>
                        <th style="width: 48px;">Formatif<br>(UH/Tgs)</th>
                        <th style="width: 48px;">Sumatif<br>(UAS)</th>
                        <th style="width: 44px;">Nilai<br>Akhir</th>
                        <th style="width: 40px;">Predikat</th>
                        <th>Capaian Kompetensi / Catatan Pembelajaran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeItems as $idx => $g)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td style="font-weight: bold;">{{ $g['nama'] }}</td>
                            <td class="text-center">{{ $g['formatif'] !== null ? number_format($g['formatif'], 1) : '-' }}</td>
                            <td class="text-center font-bold">{{ $g['sumatif'] !== null ? number_format($g['sumatif'], 1) : '-' }}</td>
                            <td class="text-center font-bold" style="background-color: #fafafa;">
                                {{ $g['nilai_rapor'] !== null ? number_format($g['nilai_rapor'], 1) : '-' }}
                            </td>
                            <td class="text-center font-bold">{{ $g['predikat'] }}</td>
                            <td style="font-size: 8pt; line-height: 1.18;">{{ $g['deskripsi'] }}</td>
                        </tr>
                    @endforeach

                    <!-- Row Total & Rata-rata (Mode UAS) -->
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="4" class="text-right" style="padding-right: 10px;">JUMLAH NILAI</td>
                        <td class="text-center">{{ $rataRata !== null ? number_format($totalNilai, 1) : '-' }}</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="4" class="text-right" style="padding-right: 10px;">RATA-RATA NILAI</td>
                        <td class="text-center">{{ $rataRata !== null ? number_format($rataRata, 1) : '-' }}</td>
                        <td class="text-center">
                            {{ \App\Http\Controllers\Erapor\CetakRaporController::calculatePredikat($rataRata) }}
                        </td>
                        <td style="font-size: 8pt;">
                            @php $effectiveRank = $displayRank ?? $stsSummary?->uas_rank ?? $stsSummary?->rank; @endphp
                            @if($showRanking)
                                @if($effectiveRank)
                                    <strong>Peringkat ke-{{ $effectiveRank }}</strong> dari {{ $totalSiswaKelas }} siswa di kelas {{ $siswa->kelas }}.
                                @else
                                    <strong>Peringkat Kelas: - (Belum ada nilai)</strong> dari {{ $totalSiswaKelas }} siswa di kelas {{ $siswa->kelas }}.
                                @endif
                            @else
                                Rata-rata kumulatif seluruh mata pelajaran yang telah dinilai.
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Kehadiran, Catatan Wali Kelas & Tanda Tangan (Dijaga 1 Halaman - Mode UAS) -->
            <div class="footer-keep-together">
                <table class="info-grid">
                    <tr>
                        <!-- Ketidakhadiran SMART+ -->
                        <td class="info-box-cell" style="width: 38%;">
                            <div class="sub-box-title">KETIDAKHADIRAN (PRESENSI SMART+)</div>
                            <table style="width: 100%; font-size: 8.5pt; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 62%; padding: 2px 0;">1. Sakit (S)</td>
                                    <td style="width: 5%; padding: 2px 0;">:</td>
                                    <td style="font-weight: bold; padding: 2px 0;">{{ $attendance->s ?? 0 }} hari</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 0;">2. Izin (I)</td>
                                    <td style="padding: 2px 0;">:</td>
                                    <td style="font-weight: bold; padding: 2px 0;">{{ $attendance->i ?? 0 }} hari</td>
                                </tr>
                                <tr>
                                    <td style="padding: 2px 0;">3. Tanpa Keterangan (A)</td>
                                    <td style="padding: 2px 0;">:</td>
                                    <td style="font-weight: bold; padding: 2px 0;">{{ $attendance->a ?? 0 }} hari</td>
                                </tr>
                            </table>
                        </td>

                        <!-- Spacer antar kotak -->
                        <td class="info-spacer-cell"></td>

                        <!-- Catatan Wali Kelas -->
                        <td class="info-box-cell" style="width: 60%;">
                            <div class="sub-box-title">CATATAN WALI KELAS</div>
                            <p style="margin: 3px 0 0 0; font-size: 8.5pt; font-style: italic; line-height: 1.35;">
                                @if(!empty($stsSummary?->catatan_wali_kelas))
                                    "{{ $stsSummary->catatan_wali_kelas }}"
                                @elseif($rataRata && $rataRata >= 85)
                                    "Prestasi belajar sangat memuaskan. Pertahankan semangat belajar, ketekunan, dan akhlak mulia dalam setiap kegiatan di sekolah."
                                @elseif($rataRata && $rataRata >= 75)
                                    "Pencapaian belajar sudah baik. Tingkatkan terus kedisiplinan dan konsistensi dalam menyelesaikan tugas-tugas akademik."
                                @else
                                    "Tingkatkan motivasi dan waktu belajar di rumah. Jangan ragu berdiskusi dengan guru pengampu jika menemui kesulitan materi."
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Tanda Tangan Pengesahan (3 Kolom) -->
                <table class="sig-table" style="margin-top: 20px;">
                    <tr>
                        <td>
                            Mengetahui,<br>
                            Orang Tua / Wali Peserta Didik
                            <div class="sig-space"></div>
                            <strong>( ..................................................... )</strong>
                        </td>
                        <td>
                            {{ $raporSettings?->kota_pembagian ?? 'Bogor' }}, {{ $tanggalRaporFormatted }}<br>
                            Wali Kelas {{ $siswa->kelas }}
                            <div class="sig-space" style="display: flex; align-items: center; justify-content: center;">
                                @if(!empty($homeroom?->ttd_wali_kelas))
                                    <img src="{{ asset('storage/' . $homeroom->ttd_wali_kelas) }}" alt="TTD Wali Kelas" style="max-height: 45px; max-width: 125px; object-fit: contain;">
                                @endif
                            </div>
                            <strong><u>{{ $homeroom?->effective_nama ?? $waliKelas?->nama_lengkap ?? 'Wali Kelas' }}</u></strong><br>
                            <span>NIP: {{ $homeroom?->effective_nip ?? $waliKelas?->nip ?? '-' }}</span>
                        </td>
                        <td>
                            Mengetahui,<br>
                            Kepala {{ $schoolSettings?->nama_sekolah ?? 'SMP Al Madinah' }}
                            <div class="sig-space" style="position: relative; display: flex; align-items: center; justify-content: center;">
                                @if(!empty($raporSettings?->ttd_kepala_sekolah))
                                    <img src="{{ asset('storage/' . $raporSettings->ttd_kepala_sekolah) }}" alt="TTD Kepala Sekolah" style="max-height: 45px; max-width: 125px; object-fit: contain;">
                                @endif
                                @if(!empty($raporSettings?->stempel_sekolah))
                                    <img src="{{ asset('storage/' . $raporSettings->stempel_sekolah) }}" alt="Stempel" style="max-height: 50px; position: absolute; left: 10px; top: -4px; opacity: 0.85; pointer-events: none;">
                                @endif
                            </div>
                            <strong><u>{{ $raporSettings?->nama_kepala_sekolah ?? $kepalaSekolah?->name ?? 'Kepala Sekolah' }}</u></strong><br>
                            <span>NIP: {{ $raporSettings?->nip_kepala_sekolah ?? $kepalaSekolah?->guru?->nip ?? '-' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    </div>
@endforeach

</body>
</html>
