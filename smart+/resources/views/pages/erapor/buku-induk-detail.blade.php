@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Breadcrumb & Actions -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('erapor.buku-induk.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition">Buku Induk & Arsip</a>
                <span>/</span>
                <span class="text-gray-800 dark:text-white font-medium">Transkrip Siswa</span>
            </nav>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                {{ $siswa->nama_lengkap }}
                @if($siswa->is_alumni)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        🎓 Alumni
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        ✓ Siswa Aktif
                    </span>
                @endif
            </h2>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('erapor.buku-induk.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            <a href="{{ route('erapor.buku-induk.print', $siswa->id) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-800 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Lembar Transkrip A4
            </a>
            <a href="{{ route('erapor.buku-induk.export', $siswa->id) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Ekspor Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Student Biodata & Overview -->
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Identity Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-2">
            <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="h-5 w-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Biodata Buku Induk Peserta Didik
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">NISN (Nomor Induk Siswa Nasional):</span>
                    <p class="font-mono font-semibold text-gray-800 dark:text-gray-200 text-sm mt-0.5">{{ $siswa->nisn }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Nama Lengkap:</span>
                    <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm mt-0.5">{{ $siswa->nama_lengkap }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Jenis Kelamin:</span>
                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Tanggal Lahir:</span>
                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ $siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('d F Y') : '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Kelas Saat Ini / Terakhir:</span>
                    <p class="font-semibold text-brand-600 dark:text-brand-400 mt-0.5">{{ $siswa->kelas }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Tahun Pertama Masuk:</span>
                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ $siswa->tahunAjaran?->nama ?? '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Alamat Tempat Tinggal:</span>
                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-0.5">{{ $siswa->alamat ?? '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Academic Performance Summary -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Ringkasan Kumulatif
                </h3>
                <div class="text-center py-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 mb-4 border border-gray-100 dark:border-gray-800">
                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rata-rata Nilai Kumulatif</span>
                    <div class="mt-1 font-mono text-3xl font-extrabold {{ $cumulativeAvg >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($cumulativeAvg >= 75 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400') }}">
                        {{ $cumulativeAvg !== null ? number_format($cumulativeAvg, 2) : '-' }}
                    </div>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">Dari seluruh semester yang ditempuh</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs border-t border-gray-100 dark:border-gray-800 pt-3">
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-2.5 dark:border-amber-900/40 dark:bg-amber-950/30">
                    <span class="text-amber-700 dark:text-amber-300 font-semibold block">Sakit</span>
                    <span class="font-mono font-bold text-gray-800 dark:text-white mt-1 block">{{ $attendance->s ?? 0 }} hari</span>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-2.5 dark:border-blue-900/40 dark:bg-blue-950/30">
                    <span class="text-blue-700 dark:text-blue-300 font-semibold block">Izin</span>
                    <span class="font-mono font-bold text-gray-800 dark:text-white mt-1 block">{{ $attendance->i ?? 0 }} hari</span>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50/70 p-2.5 dark:border-red-900/40 dark:bg-red-950/30">
                    <span class="text-red-700 dark:text-red-300 font-semibold block">Alfa</span>
                    <span class="font-mono font-bold text-gray-800 dark:text-white mt-1 block">{{ $attendance->a ?? 0 }} hari</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Journey Timeline -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Rekam Jejak Rombongan Belajar (Riwayat Kenaikan Kelas)
        </h3>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @foreach($periods as $idx => $p)
                @php
                    $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                    $stsInfo = $stsSummaries[$pKey] ?? null;
                    $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                    $pAvg = $hasUasInPeriod
                        ? ($stsInfo?->uas_average_score ?? $stsInfo?->average_score)
                        : ($stsInfo?->average_score ?? $stsInfo?->uas_average_score);
                    $pRank = $hasUasInPeriod
                        ? ($stsInfo?->uas_rank ?? $stsInfo?->rank)
                        : ($stsInfo?->rank ?? $stsInfo?->uas_rank);
                @endphp
                <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40 relative">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Periode {{ $idx + 1 }}</span>
                        @if($p->status === 'alumni' || $p->status === 'lulus')
                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Lulus</span>
                        @elseif($p->status === 'naik_kelas')
                            <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Naik Kelas</span>
                        @elseif($p->status === 'tinggal_kelas')
                            <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-200 dark:border-red-800">Tinggal</span>
                        @else
                            <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Aktif</span>
                        @endif
                    </div>
                    <div class="font-bold text-gray-900 dark:text-white text-sm">
                        {{ $p->kelas }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $p->tahunAjaran?->nama ?? 'Tahun Ajaran' }} (Sem. {{ $p->semester }})
                    </div>
                    @if($pAvg !== null)
                        <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs">
                            <span class="text-gray-400 dark:text-gray-500">{{ $hasUasInPeriod ? 'Rata-rata UAS:' : 'Rata-rata STS:' }}</span>
                            <span class="font-mono font-bold text-brand-600 dark:text-brand-400">{{ number_format($pAvg, 1) }}</span>
                        </div>
                        @if($pRank)
                            <div class="flex items-center justify-between text-xs mt-0.5">
                                <span class="text-gray-400 dark:text-gray-500">Peringkat:</span>
                                <span class="font-semibold text-purple-600 dark:text-purple-400">Ke-{{ $pRank }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Multi-Semester Grade Matrix Table -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Matriks Nilai Transkrip Akademik</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Nilai hasil sumatif tengah semester (STS) & capaian akhir per mata pelajaran sepanjang jenjang studi.</p>
            </div>
            <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Semua nilai tersimpan permanen
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-3 px-3 text-center w-10">No</th>
                        <th class="py-3 px-4 min-w-[200px] sticky left-0 bg-gray-50 dark:bg-gray-800/95 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">Mata Pelajaran</th>
                        @foreach($periods as $p)
                            <th class="py-3 px-3 text-center min-w-[110px]">
                                <div>{{ $p->kelas }}</div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 font-normal">({{ $p->tahunAjaran?->nama ?? 'Tahun' }} {{ $p->semester }})</div>
                            </th>
                        @endforeach
                        <th class="py-3 px-3 text-center bg-brand-50/80 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 min-w-[110px]">
                            Rata-rata Kumulatif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($subjects as $idx => $mapel)
                        @php
                            $subjectScores = [];
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                            <td class="py-3 px-3 text-center text-gray-400 dark:text-gray-500">{{ $idx + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-900 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">{{ $mapel->nama }}</td>
                            @foreach($periods as $p)
                                @php
                                    $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                                    $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                                    $rec = $allSummaries->where('mata_pelajaran_id', $mapel->id)
                                        ->where('tahun_ajaran_id', $p->tahun_ajaran_id)
                                        ->where('semester', $p->semester)
                                        ->first();
                                    $score = $hasUasInPeriod
                                        ? ($rec?->final_score ?? $rec?->sts_score)
                                        : ($rec?->sts_score ?? $rec?->final_score);
                                    if ($score !== null) {
                                        $subjectScores[] = (float) $score;
                                    }
                                @endphp
                                <td class="py-3 px-3 text-center font-mono {{ $score !== null ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-gray-300 dark:text-gray-600' }}">
                                    {{ $score !== null ? number_format($score, 1) : '-' }}
                                </td>
                            @endforeach
                            <td class="py-3 px-3 text-center font-mono font-bold bg-brand-50/30 dark:bg-brand-900/10">
                                @if(!empty($subjectScores))
                                    @php
                                        $mAvg = round(array_sum($subjectScores) / count($subjectScores), 1);
                                    @endphp
                                    <span class="{{ $mAvg >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($mAvg >= 75 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400') }}">
                                        {{ number_format($mAvg, 1) }}
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($periods) + 3 }}" class="py-8 text-center text-gray-400 dark:text-gray-500">
                                Belum ada mata pelajaran terdaftar.
                            </td>
                        </tr>
                    @endforelse

                    <!-- Summary Row: Semester Average -->
                    <tr class="bg-gray-50 font-bold border-t-2 border-gray-200 dark:bg-gray-800/80 dark:border-gray-700">
                        <td class="py-3 px-3 text-center"></td>
                        <td class="py-3 px-4 text-gray-800 dark:text-white uppercase tracking-wider text-xs sticky left-0 bg-gray-50 dark:bg-gray-800/95 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                            Rata-rata Semester
                        </td>
                        @foreach($periods as $p)
                            @php
                                $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                                $stsInfo = $stsSummaries[$pKey] ?? null;
                                $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                                $pAvg = $hasUasInPeriod
                                    ? ($stsInfo?->uas_average_score ?? $stsInfo?->average_score)
                                    : ($stsInfo?->average_score ?? $stsInfo?->uas_average_score);
                            @endphp
                            <td class="py-3 px-3 text-center font-mono text-sm text-brand-600 dark:text-brand-400">
                                {{ $pAvg !== null ? number_format($pAvg, 1) : '-' }}
                            </td>
                        @endforeach
                        <td class="py-3 px-3 text-center font-mono text-sm font-extrabold text-brand-700 bg-brand-100/50 dark:bg-brand-900/30 dark:text-brand-300">
                            {{ $cumulativeAvg !== null ? number_format($cumulativeAvg, 1) : '-' }}
                        </td>
                    </tr>

                    <!-- Summary Row: Rank -->
                    <tr class="bg-gray-50/60 font-semibold dark:bg-gray-800/50">
                        <td class="py-2.5 px-3 text-center"></td>
                        <td class="py-2.5 px-4 text-gray-700 dark:text-gray-300 text-xs sticky left-0 bg-gray-50/60 dark:bg-gray-800/95 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                            Peringkat di Kelas
                        </td>
                        @foreach($periods as $p)
                            @php
                                $pKey = $p->tahun_ajaran_id . '_' . $p->semester;
                                $stsInfo = $stsSummaries[$pKey] ?? null;
                                $hasUasInPeriod = $uasPeriodMap[$pKey] ?? false;
                                $pRank = $hasUasInPeriod
                                    ? ($stsInfo?->uas_rank ?? $stsInfo?->rank)
                                    : ($stsInfo?->rank ?? $stsInfo?->uas_rank);
                            @endphp
                            <td class="py-2.5 px-3 text-center text-purple-700 dark:text-purple-400">
                                {{ $pRank ? 'Ke-' . $pRank : '-' }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-3 text-center text-gray-400 bg-brand-50/30 dark:bg-brand-900/10">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
