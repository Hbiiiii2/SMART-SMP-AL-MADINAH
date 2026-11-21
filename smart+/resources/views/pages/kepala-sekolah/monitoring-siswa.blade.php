@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Monitoring Siswa</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if ($errors->has('error'))
        <div class="mb-5 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ $errors->first('error') }}</div>
    @endif

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama atau NISN..."
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-72" />
            <select name="kelas_filter" onchange="this.form.submit()"
                class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kls)
                    <option value="{{ $kls }}" {{ request('kelas_filter') === $kls ? 'selected' : '' }}>{{ $kls }}</option>
                @endforeach
            </select>
            <select name="status_filter" onchange="this.form.submit()"
                class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua Status</option>
                <option value="hadir" {{ request('status_filter') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                <option value="terlambat" {{ request('status_filter') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                <option value="alfa" {{ request('status_filter') === 'alfa' ? 'selected' : '' }}>Alfa</option>
                <option value="belum_absen" {{ request('status_filter') === 'belum_absen' ? 'selected' : '' }}>Belum Absen</option>
            </select>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            @if(request('search') || request('kelas_filter') || request('status_filter'))
                <a href="{{ route('ks.monitoring-siswa') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
            @endif
        </form>
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
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status Hari Ini</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($siswas as $i => $siswa)
                    @php
                        $badge = match($siswa->status_tampil) {
                            'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                            'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                            'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                            'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                            'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                            default => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-500">{{ $siswas->firstItem() + $i }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $siswa->nisn }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $siswa->nama_lengkap }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $siswa->kelas }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $siswa->status_tampil }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $siswa->jam_masuk ? \Carbon\Carbon::parse($siswa->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('ks.detail-siswa', $siswa) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Detail</a>
                                
                                <div x-data="{ showModal: false }">
                                    <button @click="showModal = true"
                                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-600">
                                        Ubah Status
                                    </button>
                                    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" style="display:none">
                                        <div @click.away="showModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                                            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Ubah Status Kehadiran</h3>
                                            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $siswa->nama_lengkap }} — {{ $siswa->nisn }}</p>
                                            <form method="POST" action="{{ route('ks.monitoring-siswa.update-status', $siswa->id) }}">
                                                @csrf
                                                <div class="space-y-4 text-left">
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status Baru</label>
                                                        <select name="status" required
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                            <option value="Hadir" {{ $siswa->status_tampil == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                                                            <option value="Alfa" {{ $siswa->status_tampil == 'Alfa' ? 'selected' : '' }}>Alfa</option>
                                                            <option value="Sakit" {{ $siswa->status_tampil == 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                                            <option value="Izin" {{ $siswa->status_tampil == 'Izin' ? 'selected' : '' }}>Izin</option>
                                                            <option value="Terlambat" {{ $siswa->status_tampil == 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Alasan / Keterangan (opsional)</label>
                                                        <textarea name="alasan" rows="3" placeholder="Masukkan alasan..."
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <button type="button" @click="showModal = false"
                                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                                            Batal
                                                        </button>
                                                        <button type="submit"
                                                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                                                            Simpan
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $siswas->links() }}</div>
    </div>
</div>
@endsection
