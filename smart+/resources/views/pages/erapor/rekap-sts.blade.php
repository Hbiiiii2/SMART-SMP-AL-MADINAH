@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ ($mode ?? 'sts') === 'uas' ? 'Rekapitulasi Nilai UAS & Peringkat' : 'Rekapitulasi Nilai STS & Peringkat' }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Kelas: <span class="font-semibold text-brand-600">{{ $selectedClass ?? '-' }}</span> | 
                Tahun Ajaran: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $selectedYear->nama ?? $activeYear->nama ?? '2026/2027' }}</span> - Semester {{ $semester }}
                @if(($selectedYear->id ?? null) === ($activeYear->id ?? null))
                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                @else
                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Arsip Masa Lalu</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Finalize Button (Hanya Admin Sekolah, Kepala Sekolah, Super Admin) -->
            @if(($finalization?->status ?? 'draft') !== 'final')
                @if(in_array(auth()->user()?->role, ['super_admin', 'admin_sekolah', 'kepala_sekolah']))
                    <form action="{{ route('erapor.recap.finalize', $selectedClass ?? 'none') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi rapor kelas ini?')" class="inline w-full sm:w-auto">
                        @csrf
                        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                        <input type="hidden" name="semester" value="{{ $semester }}">
                        <button type="submit" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-amber-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Finalisasi Rapor Kelas
                        </button>
                    </form>
                @else
                    <span class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-xl bg-amber-50 px-3.5 py-2 text-xs font-semibold text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                        Status: Draf (Menunggu Finalisasi Operator)
                    </span>
                @endif
            @else
                <span class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 rounded-xl bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                    <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Sudah Difinalisasi ({{ $finalization->finalized_at->format('d/m/Y H:i') }})
                </span>
            @endif

            <!-- Export Excel Button -->
            <a href="{{ route('erapor.recap.export', $selectedClass ?? 'none') }}?tahun_ajaran_id={{ $tahunAjaranId }}&semester={{ $semester }}&mode={{ $mode ?? 'sts' }}" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Ekspor Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Filter Tahun Ajaran, Semester, Kelas & Jenis Rekap -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('erapor.recap.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    @foreach($allYears as $y)
                        <option value="{{ $y->id }}" {{ $tahunAjaranId == $y->id ? 'selected' : '' }}>
                            {{ $y->nama }} {{ $y->is_aktif ? '(Aktif)' : '(Arsip)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Semester</label>
                <select name="semester" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="Ganjil" {{ $semester == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="Genap" {{ $semester == 'Genap' ? 'selected' : '' }}>Genap</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Pilih Kelas / Rombongan Belajar</label>
                <select name="kelas" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    @forelse($classes as $c)
                        <option value="{{ $c }}" {{ $selectedClass == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @empty
                        <option value="">Tidak ada kelas di periode ini</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Acuan Nilai & Peringkat</label>
                <select name="mode" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="sts" {{ ($mode ?? 'sts') === 'sts' ? 'selected' : '' }}>Nilai STS / UTS</option>
                    <option value="uas" {{ ($mode ?? 'sts') === 'uas' ? 'selected' : '' }}>Nilai Akhir UAS (30/30/40)</option>
                </select>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Matrix Table -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-3 px-3 text-center w-10">No</th>
                        <th class="py-3 px-3 min-w-[120px]">NISN</th>
                        <th class="py-3 px-4 min-w-[200px] sticky left-0 z-20 bg-gray-50 dark:bg-gray-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">Nama Lengkap Siswa</th>
                        @foreach($subjects as $mapel)
                            <th class="py-3 px-2 text-center min-w-[70px]" title="{{ $mapel->nama }}">
                                {{ $mapel->nama }}
                            </th>
                        @endforeach
                        <th class="py-3 px-2 text-center bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 min-w-[75px]">{{ ($mode ?? 'sts') === 'uas' ? 'Total UAS' : 'Total STS' }}</th>
                        <th class="py-3 px-2 text-center bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 min-w-[75px]">Rata-rata</th>
                        <th class="py-3 px-2 text-center bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 min-w-[65px]">Rank</th>
                        <th class="py-3 px-2 text-center bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 min-w-[50px]">S</th>
                        <th class="py-3 px-2 text-center bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 min-w-[50px]">I</th>
                        <th class="py-3 px-2 text-center bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 min-w-[50px]">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($students as $idx => $siswa)
                        @php
                            $scoresByMapel = $stsScores[$siswa->id] ?? collect();
                            $isUasMode = ($mode ?? 'sts') === 'uas';
                            $scoreMap = $scoresByMapel->mapWithKeys(fn($s) => [
                                $s->mata_pelajaran_id => $isUasMode ? ($s->final_score ?? $s->sts_score) : $s->sts_score,
                            ]);
                            $summary = $stsSummaries[$siswa->id] ?? null;
                            $displayTotal = $isUasMode ? ($summary?->uas_total_score ?? $summary?->total_score) : $summary?->total_score;
                            $displayAvg = $isUasMode ? ($summary?->uas_average_score ?? $summary?->average_score) : $summary?->average_score;
                            $displayRank = $isUasMode ? ($summary?->uas_rank ?? $summary?->rank) : $summary?->rank;
                            $att = $attendanceMap[$siswa->id] ?? ['sakit' => 0, 'izin' => 0, 'alfa' => 0];
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                            <td class="py-2.5 px-3 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-mono text-gray-600 dark:text-gray-400">{{ $siswa->nisn }}</td>
                            <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white sticky left-0 z-10 bg-white dark:bg-gray-900 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                                {{ $siswa->nama_lengkap }}
                            </td>
                            
                            @foreach($subjects as $mapel)
                                @php $val = $scoreMap[$mapel->id] ?? null; @endphp
                                <td class="py-2.5 px-2 text-center font-mono {{ $val !== null ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-600' }}">
                                    {{ $val !== null ? number_format($val, 1) : '-' }}
                                </td>
                            @endforeach

                            <td class="py-2.5 px-2 text-center font-bold font-mono text-blue-600 dark:text-blue-400 bg-blue-50/30 dark:bg-blue-900/10">
                                {{ $displayTotal !== null ? number_format($displayTotal, 1) : '-' }}
                            </td>
                            <td class="py-2.5 px-2 text-center font-bold font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-900/10">
                                {{ $displayAvg !== null ? number_format($displayAvg, 2) : '-' }}
                            </td>
                            <td class="py-2.5 px-2 text-center font-bold font-mono text-purple-600 dark:text-purple-400 bg-purple-50/30 dark:bg-purple-900/10">
                                {{ $displayRank ? '#' . $displayRank : '-' }}
                            </td>
                            <!-- Ketidakhadiran SMART+ -->
                            <td class="py-2.5 px-2 text-center font-mono font-medium text-amber-700 dark:text-amber-400 bg-amber-50/30 dark:bg-amber-950/20">{{ $att['sakit'] }}</td>
                            <td class="py-2.5 px-2 text-center font-mono font-medium text-blue-700 dark:text-blue-400 bg-blue-50/30 dark:bg-blue-950/20">{{ $att['izin'] }}</td>
                            <td class="py-2.5 px-2 text-center font-mono font-bold text-red-700 dark:text-red-400 bg-red-50/30 dark:bg-red-950/20">{{ $att['alfa'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($subjects) + 9 }}" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada data siswa di kelas {{ $selectedClass }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
