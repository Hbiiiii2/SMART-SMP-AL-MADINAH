@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Detail Kehadiran Siswa</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Riwayat absen untuk siswa di mata pelajaran ini</p>
        </div>
        <div>
            <a href="{{ route('guru.laporan.index', ['start_date' => $startDate, 'end_date' => $endDate, 'kelas_filter' => request('kelas_filter'), 'mapel_filter' => request('mapel_filter')]) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <!-- Profil Siswa & Mata Pelajaran -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-brand-50 text-2xl font-bold text-brand-600 dark:bg-brand-500/20 dark:text-brand-400">
                {{ strtoupper(substr($siswa->nama_lengkap, 0, 2)) }}
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $siswa->nama_lengkap }}</h3>
                <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                        NISN: {{ $siswa->nisn }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Kelas: {{ $siswa->kelas }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        Mata Pelajaran: {{ $jadwal->mataPelajaran->nama }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('guru.laporan.detail', ['siswa' => $siswa->id, 'jadwal' => $jadwal->id]) }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            <a href="{{ route('guru.laporan.detail.export', ['siswa' => $siswa->id, 'jadwal' => $jadwal->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </a>
            @if(request('start_date') || request('end_date'))
                <a href="{{ route('guru.laporan.detail', ['siswa' => $siswa->id, 'jadwal' => $jadwal->id]) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
            @endif
        </form>
    </div>

    <!-- Ringkasan Absen -->
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-success-500">{{ ($rekap->get('Hadir', 0) ?? 0) + ($rekap->get('Terlambat', 0) ?? 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Hadir</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-error-500">{{ $rekap->get('Alfa', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Alfa</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-blue-500">{{ $rekap->get('Sakit', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Sakit</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-purple-500">{{ $rekap->get('Izin', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Izin</div>
        </div>
    </div>

    <!-- Tabel Riwayat Kehadiran -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Keluar</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($absensis as $abs)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($abs->tanggal)->translatedFormat('l, d M Y') }}</td>
                        <td class="px-6 py-3">
                            @php $badge = match($abs->status) { 'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400', 'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400', 'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400', 'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400', 'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400', default => 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-400' }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_keluar ? \Carbon\Carbon::parse($abs->jam_keluar)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->keterangan ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada riwayat kehadiran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $absensis->links() }}</div>
    </div>
</div>
@endsection
