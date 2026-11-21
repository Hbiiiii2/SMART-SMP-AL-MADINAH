@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Platform E-Rapor Terintegrasi</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Tahun Ajaran {{ $activeYear->nama ?? '2026/2027' }} - Semester {{ $semester }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($cbtStatus === 'healthy')
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    CBT API Online ({{ $cbtVersion }})
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    CBT API Offline (Fallback Excel Aktif)
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa Terdaftar</p>
            <h3 class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $totalSiswa }}</h3>
            <p class="mt-2 text-xs text-gray-400">Tersebar di {{ $classes->count() }} Rombongan Belajar</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran Aktif</p>
            <h3 class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $totalMapel }}</h3>
            <p class="mt-2 text-xs text-gray-400">Komponen Penilaian Terstandarisasi</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kelas Selesai Finalisasi</p>
            <h3 class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $finalizedClassesCount }} / {{ $classes->count() }}</h3>
            <p class="mt-2 text-xs text-gray-400">Rapor STS Siap Rilis</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinkronisasi CBT Terakhir</p>
            <h3 class="mt-2 text-lg font-bold text-gray-800 dark:text-white">
                {{ $recentSyncs->first()?->created_at?->diffForHumans() ?? 'Belum ada' }}
            </h3>
            <p class="mt-2 text-xs text-gray-400">{{ $recentSyncs->first()?->batch_number ?? '-' }}</p>
        </div>
    </div>

    <!-- Quick Navigation / Workflow -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <a href="{{ route('erapor.buku-nilai.index') }}" class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-brand-500 hover:shadow-md transition dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-brand-600 dark:text-white">1. Buku Nilai</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Input Harian Guru</p>
                </div>
            </div>
        </a>

        <a href="{{ route('erapor.cbt.index') }}" class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-brand-500 hover:shadow-md transition dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-blue-600 dark:text-white">2. Sinkron CBT</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Tarik Nilai Ujian</p>
                </div>
            </div>
        </a>

        <a href="{{ route('erapor.capaian.index') }}" class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-emerald-500 hover:shadow-md transition dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-emerald-600 dark:text-white">3. Capaian CP</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Deskripsi Merdeka</p>
                </div>
            </div>
        </a>

        <a href="{{ route('erapor.cetak.catatan-wali') }}" class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-indigo-500 hover:shadow-md transition dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-indigo-600 dark:text-white">4. Catatan Wali</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Motivasi & Presensi</p>
                </div>
            </div>
        </a>

        <a href="{{ route('erapor.cetak.index') }}" class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-amber-500 hover:shadow-md transition dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-amber-600 dark:text-white">5. Cetak Rapor</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Format Resmi A4</p>
                </div>
            </div>
        </a>

        <a href="{{ route('erapor.buku-induk.index') }}" class="group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-purple-500 hover:shadow-md transition dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800 group-hover:text-purple-600 dark:text-white">6. Buku Induk</h4>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Arsip Alumni</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Students STS / UAS -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">{{ !empty($hasUasScores) ? 'Peringkat Teratas UAS' : 'Peringkat Teratas STS 1' }}</h3>
            @if($topStudents->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data rekap nilai yang dihitung.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="border-b border-gray-100 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 dark:border-gray-800">
                            <tr>
                                <th class="py-2.5 px-2">Peringkat</th>
                                <th class="py-2.5 px-2">Nama Siswa</th>
                                <th class="py-2.5 px-2">Kelas</th>
                                <th class="py-2.5 px-2 text-right">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($topStudents as $st)
                                @php
                                    $stRank = !empty($hasUasScores) ? ($st->uas_rank ?? $st->rank) : $st->rank;
                                    $stAvg = !empty($hasUasScores) ? ($st->uas_average_score ?? $st->average_score) : $st->average_score;
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                                    <td class="py-2.5 px-2 font-bold text-brand-600 dark:text-brand-400">#{{ $stRank }}</td>
                                    <td class="py-2.5 px-2 font-medium text-gray-900 dark:text-white">{{ $st->siswa?->nama_lengkap }}</td>
                                    <td class="py-2.5 px-2 text-xs text-gray-500 dark:text-gray-400">{{ $st->siswa?->kelas }}</td>
                                    <td class="py-2.5 px-2 text-right font-bold font-mono text-gray-900 dark:text-white">{{ number_format($stAvg, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Recent Syncs -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Riwayat Sinkronisasi Terkini</h3>
            @if($recentSyncs->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat integrasi atau impor.</p>
            @else
                <div class="space-y-3">
                    @foreach($recentSyncs as $sync)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100 dark:bg-gray-800/50 dark:border-gray-800">
                            <div>
                                <span class="text-xs font-mono font-medium text-brand-600 dark:text-brand-400">{{ $sync->batch_number }}</span>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">
                                    {{ $sync->source_type === 'cbt_api' ? 'REST API CBT' : 'File Excel (' . $sync->file_name . ')' }}
                                </p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $sync->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                                    {{ $sync->success_rows }} Siswa Sukses
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
