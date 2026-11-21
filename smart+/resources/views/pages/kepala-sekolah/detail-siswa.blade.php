@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <a href="{{ route('ks.dashboard') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-brand-500 hover:underline">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Dashboard
        </a>
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-xl font-bold text-brand-500 dark:bg-brand-500/10">
                {{ strtoupper(substr($siswa->nama_lengkap, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $siswa->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">NISN: {{ $siswa->nisn }} | Kelas: {{ $siswa->kelas }} | TA: {{ $siswa->tahunAjaran->nama ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-success-500">{{ ($rekap->get('Hadir', 0) ?? 0) + ($rekap->get('Terlambat', 0) ?? 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Hadir</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-error-500">{{ $rekap->get('Alfa', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Alfa</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-warning-500">{{ $rekap->get('Terlambat', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Terlambat</div>
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

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dari</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="dark:bg-dark-900 shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Sampai</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="dark:bg-dark-900 shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            <a href="{{ route('ks.detail-siswa.export', ['siswa' => $siswa->id, 'start_date' => request('start_date', $startDate), 'end_date' => request('end_date', $endDate)]) }}" class="bg-success-500 hover:bg-success-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
        </form>
    </div>

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
                            @php $badge = match($abs->status) { 'Hadir' => 'bg-success-50 text-success-700', 'Terlambat' => 'bg-warning-50 text-warning-700', 'Alfa' => 'bg-error-50 text-error-700', 'Sakit' => 'bg-blue-50 text-blue-700', 'Izin' => 'bg-purple-50 text-purple-700', default => 'bg-gray-50 text-gray-700' }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_keluar ? \Carbon\Carbon::parse($abs->jam_keluar)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->keterangan ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $absensis->links() }}</div>
    </div>
</div>
@endsection
