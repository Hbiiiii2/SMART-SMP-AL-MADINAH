@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Arsip Abadi Sekolah
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">SMP Al Madinah</span>
            </div>
            <h2 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">Buku Induk & Transkrip Siswa / Alumni</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pusat arsip permanen nilai akademik siswa & alumni lintas generasi, angkatan, dan tahun ajaran.
            </p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa Terdaftar</span>
                    <h3 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($totalStudents) }}</h3>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">Siswa aktif & alumni di database</p>
        </div>

        <!-- Siswa Aktif -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Siswa Aktif Saat Ini</span>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($activeStudents) }}</h3>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">Tercatat di rombel aktif 2026/2027</p>
        </div>

        <!-- Siswa Alumni -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Alumni / Lulus</span>
                    <h3 class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($alumniStudents) }}</h3>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">Arsip nilai tetap abadi & dapat dicetak</p>
        </div>

        <!-- Nilai Terekam -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Nilai Mapel Terekam</span>
                    <h3 class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalGradesRecorded) }}</h3>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">Rekapitulasi STS & nilai akhir tersimpan</p>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('erapor.buku-induk.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Cari Nama Siswa atau NISN</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama atau NISN..." class="w-full rounded-xl border border-gray-300 py-2.5 pl-10 pr-4 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:placeholder-gray-500">
                    <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Status Siswa -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Status Siswa</label>
                <select name="status" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="semua" {{ $status == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="aktif" {{ $status == 'aktif' ? 'selected' : '' }}>Siswa Aktif</option>
                    <option value="alumni" {{ $status == 'alumni' ? 'selected' : '' }}>Alumni / Lulus</option>
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Kelas Saat Ini / Terakhir</label>
                <select name="kelas" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="semua" {{ $kelasFilter == 'semua' ? 'selected' : '' }}>Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c }}" {{ $kelasFilter == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tahun Ajaran -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="semua" {{ $tahunFilter == 'semua' ? 'selected' : '' }}>Semua Tahun</option>
                    @foreach($allYears as $y)
                        <option value="{{ $y->id }}" {{ $tahunFilter == $y->id ? 'selected' : '' }}>
                            {{ $y->nama }} {{ $y->is_aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="sm:col-span-2 lg:col-span-5 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('erapor.buku-induk.index') }}" class="w-full sm:w-auto text-center inline-flex justify-center items-center rounded-xl border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                    Reset Filter
                </a>
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-600 px-5 py-2 text-xs font-semibold text-white hover:bg-brand-700 transition shadow-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Table Siswa -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4 min-w-[240px] sticky left-0 bg-gray-50 dark:bg-gray-800/95 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">Peserta Didik</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Kelas & Status</th>
                        <th class="py-3.5 px-4 min-w-[220px]">Riwayat Rombel / Angkatan</th>
                        <th class="py-3.5 px-4 text-center min-w-[120px]">Rata-rata Kumulatif</th>
                        <th class="py-3.5 px-4 text-center min-w-[180px]">Aksi & Arsip</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($students as $idx => $s)
                        @php
                            $stat = $studentStats[$s->id] ?? ['cumulative_avg' => null, 'periods_count' => 0, 'grades_count' => 0];
                            $isAlumni = $s->is_alumni;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                            <td class="py-3 px-4 text-center text-xs text-gray-400 dark:text-gray-500 font-mono">
                                {{ $students->firstItem() + $idx }}
                            </td>
                            <td class="py-3 px-4 sticky left-0 bg-white dark:bg-gray-900 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $isAlumni ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300' }} font-bold text-sm">
                                        {{ strtoupper(substr($s->nama_lengkap, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            {{ $s->nama_lengkap }}
                                            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">({{ $s->jenis_kelamin }})</span>
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 font-mono flex items-center gap-2 mt-0.5">
                                            <span>NISN: <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $s->nisn }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if($isAlumni)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300">
                                        🎓 Alumni (Lulus)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300">
                                        ✓ {{ $s->kelas }}
                                    </span>
                                @endif
                                <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                    Tahun Masuk: {{ $s->tahunAjaran?->nama ?? '-' }}
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                @if($s->riwayatKelas->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach($s->riwayatKelas as $rw)
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700" title="{{ $rw->catatan }}">
                                                {{ $rw->kelas }} ({{ $rw->tahunAjaran?->nama ?? '?' }} {{ $rw->semester }})
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">Belum ada riwayat tercatat</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($stat['cumulative_avg'] !== null)
                                    <span class="inline-flex items-center justify-center font-bold font-mono text-sm {{ $stat['cumulative_avg'] >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($stat['cumulative_avg'] >= 75 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400') }}">
                                        {{ number_format($stat['cumulative_avg'], 1) }}
                                    </span>
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ $stat['grades_count'] }} mapel</div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- View Transcript -->
                                    <a href="{{ route('erapor.buku-induk.show', $s->id) }}" class="inline-flex items-center gap-1 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100 dark:bg-brand-900/30 dark:text-brand-300 dark:hover:bg-brand-900/50 transition" title="Lihat Transkrip Nilai Buku Induk">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Transkrip
                                    </a>

                                    <!-- Print -->
                                    <a href="{{ route('erapor.buku-induk.print', $s->id) }}" target="_blank" class="inline-flex items-center rounded-xl border border-gray-200 p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition" title="Cetak Transkrip A4">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>

                                    <!-- Export Excel -->
                                    <a href="{{ route('erapor.buku-induk.export', $s->id) }}" class="inline-flex items-center rounded-xl border border-gray-200 p-1.5 text-emerald-600 hover:bg-emerald-50 dark:border-gray-700 dark:text-emerald-400 dark:hover:bg-emerald-950/30 transition" title="Ekspor Excel (.xlsx)">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Tidak ada data siswa atau alumni yang cocok dengan pencarian.</p>
                                <p class="text-xs mt-1 text-gray-400 dark:text-gray-500">Coba ubah kata kunci atau reset filter pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
