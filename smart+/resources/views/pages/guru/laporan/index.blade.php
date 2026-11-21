@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Laporan Kehadiran Siswa</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Berdasarkan jadwal yang Anda ajar</p>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('guru.laporan.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tahun Ajaran</label>
                <select name="tahun_ajaran_id" onchange="this.form.submit()"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($tahunAjarans as $ta)
                        <option value="{{ $ta->id }}" {{ $tahunAjaranFilter == $ta->id ? 'selected' : '' }}>{{ $ta->nama }} {{ $ta->is_aktif ? '(Aktif)' : '' }}</option>
                    @endforeach
                </select>
            </div>
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
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Kelas</label>
                <select name="kelas_filter" onchange="this.form.submit()"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $kls)
                        <option value="{{ $kls }}" {{ request('kelas_filter') === $kls ? 'selected' : '' }}>{{ $kls }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Mata Pelajaran</label>
                <select name="mapel_filter" onchange="this.form.submit()"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Pelajaran</option>
                    @foreach($mapelList as $mp)
                        <option value="{{ $mp->id }}" {{ request('mapel_filter') == $mp->id ? 'selected' : '' }}>{{ $mp->nama }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            @if(request('start_date') || request('end_date') || request('kelas_filter') || request('mapel_filter'))
                <a href="{{ route('guru.laporan.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
            @endif
        </form>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
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

    <div class="mb-6">
        <a href="{{ route('guru.laporan.export', ['start_date' => $startDate, 'end_date' => $endDate, 'kelas_filter' => request('kelas_filter'), 'mapel_filter' => request('mapel_filter')]) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-success-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export Excel
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">NISN</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kehadiran</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($rekapSiswa as $i => $rekapRow)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-500">{{ $rekapSiswa->firstItem() + $i }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $rekapRow->siswa->nisn ?? '-' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $rekapRow->siswa->nama_lengkap ?? '-' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $rekapRow->siswa->kelas ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $rekapRow->jadwal->mataPelajaran->nama ?? '-' }}</td>
                        <td class="px-6 py-3">
                            @php
                                $persen = $rekapRow->total_pertemuan > 0 ? round(($rekapRow->total_hadir / $rekapRow->total_pertemuan) * 100, 1) : 0;
                                $colorClass = $persen >= 80 ? 'text-success-600 dark:text-success-500' : ($persen >= 60 ? 'text-warning-600 dark:text-warning-500' : 'text-error-600 dark:text-error-500');
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="font-medium {{ $colorClass }}">{{ $persen }}%</span>
                                <span class="text-xs text-gray-400">({{ $rekapRow->total_hadir }}/{{ $rekapRow->total_pertemuan }})</span>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <a href="{{ route('guru.laporan.detail', ['siswa' => $rekapRow->siswa_id, 'jadwal' => $rekapRow->jadwal_id, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $rekapSiswa->links() }}</div>
    </div>
</div>
@endsection
