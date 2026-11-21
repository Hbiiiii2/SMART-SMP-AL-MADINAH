@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Laporan Kehadiran</h2>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('admin.laporan.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tahun Ajaran</label>
                <select name="tahun_ajaran_id"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
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
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            <a href="{{ route('admin.laporan.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-success-500">{{ $rekapSiswa->get('Hadir', 0) + $rekapSiswa->get('Terlambat', 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Siswa Hadir</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-error-500">{{ $rekapSiswa->get('Alfa', 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Siswa Alfa</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-blue-500">{{ $rekapSiswa->get('Sakit', 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Siswa Sakit</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-purple-500">{{ $rekapSiswa->get('Izin', 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Siswa Izin</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-brand-500">{{ $rekapGuru->get('Hadir', 0) + $rekapGuru->get('Terlambat', 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Guru Hadir</div>
        </div>
    </div>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.laporan.export-siswa', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-success-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export Excel Siswa
        </a>
        <a href="{{ route('admin.laporan.export-guru', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export Excel Guru
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Absensi Siswa</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">NISN</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Keluar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($absensiSiswa as $abs)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->tanggal }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->siswa->nisn ?? '-' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $abs->siswa->nama_lengkap ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->siswa->kelas ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jadwal->mataPelajaran->nama ?? '-' }}</td>
                        <td class="px-6 py-3">
                            @php $badge = match($abs->status) { 'Hadir' => 'bg-success-50 text-success-700', 'Terlambat' => 'bg-warning-50 text-warning-700', 'Alfa' => 'bg-error-50 text-error-700', 'Sakit' => 'bg-blue-50 text-blue-700', 'Izin' => 'bg-purple-50 text-purple-700', default => 'bg-gray-50 text-gray-700' }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_keluar ? \Carbon\Carbon::parse($abs->jam_keluar)->format('H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $absensiSiswa->links() }}</div>
    </div>

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Absensi Guru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">NIP</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Keluar</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Lokasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($absensiGuru as $abs)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->tanggal }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->guru->nip ?? '-' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $abs->guru->nama_lengkap ?? '-' }}</td>
                        <td class="px-6 py-3">
                            @php $badge = match($abs->status) { 'Hadir' => 'bg-success-50 text-success-700', 'Terlambat' => 'bg-warning-50 text-warning-700', default => 'bg-gray-50 text-gray-700' }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_keluar ? \Carbon\Carbon::parse($abs->jam_keluar)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3">
                            @if($abs->latitude && $abs->longitude && $abs->latitude != 0 && $abs->longitude != 0)
                                <a href="https://www.google.com/maps?q={{ $abs->latitude }},{{ $abs->longitude }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline dark:text-brand-400">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    {{ number_format($abs->latitude, 4) }}, {{ number_format($abs->longitude, 4) }}
                                </a>
                            @elseif(!is_null($abs->latitude) && $abs->jam_masuk)
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">Manual</span>
                            @else
                                <span class="text-xs text-gray-400 italic">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $absensiGuru->links() }}</div>
    </div>
</div>
@endsection
