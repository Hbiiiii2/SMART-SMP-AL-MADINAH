@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Data Jadwal Pelajaran</h2>
        <a href="{{ route('admin.jadwal.create') }}"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Jadwal
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <form method="GET" action="{{ route('admin.jadwal.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Pencarian Teks -->
                    <div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kelas atau Lokasi..."
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <!-- Filter Tahun Ajaran -->
                    <div>
                        <select name="tahun_ajaran_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach($tahunAjarans as $ta)
                                <option value="{{ $ta->id }}" {{ $tahunAjaranFilter == $ta->id ? 'selected' : '' }}>{{ $ta->nama }} {{ $ta->is_aktif ? '(Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Hari -->
                    <div>
                        <select name="hari" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Semua Hari</option>
                            @foreach($hariOptions as $hari)
                                <option value="{{ $hari }}" {{ request('hari') == $hari ? 'selected' : '' }}>{{ $hari }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Mata Pelajaran -->
                    <div>
                        <select name="mata_pelajaran_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Semua Mata Pelajaran</option>
                            @foreach($mataPelajarans as $mapel)
                                <option value="{{ $mapel->id }}" {{ request('mata_pelajaran_id') == $mapel->id ? 'selected' : '' }}>{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Guru & Tombol Aksi -->
                    <div class="flex items-center gap-2">
                        <select name="guru_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Semua Guru</option>
                            @foreach($gurus as $guru)
                                <option value="{{ $guru->id }}" {{ request('guru_id') == $guru->id ? 'selected' : '' }}>{{ $guru->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition h-11">
                            Filter
                        </button>
                        @if(request('search') || request('hari') || request('mata_pelajaran_id') || request('guru_id') || request('tahun_ajaran_id'))
                            <a href="{{ route('admin.jadwal.index') }}" title="Reset Filter" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-500 transition hover:bg-gray-50 hover:text-error-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran & Kelas</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Guru Pengajar</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Waktu & Lokasi</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($jadwals as $jadwal)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4 text-gray-800 dark:text-white/90 align-top">{{ ($jadwals->currentPage() - 1) * $jadwals->perPage() + $loop->iteration }}</td>
                            
                            <td class="px-5 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $jadwal->mataPelajaran->nama ?? '-' }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-700/10 dark:bg-brand-500/10 dark:text-brand-400 dark:ring-brand-400/20">
                                            Kelas {{ $jadwal->kelas }}
                                        </span>
                                    </div>
                                    @if($jadwal->tahunAjaran)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">TA: {{ $jadwal->tahunAjaran->nama }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $jadwal->guru->nama_lengkap ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">NIP: {{ $jadwal->guru->nip ?? '-' }}</span>
                                </div>
                            </td>

                            <td class="px-5 py-4 align-top">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-1.5 text-gray-800 dark:text-white/90">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>{{ $jadwal->hari }}, {{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 text-xs">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span>{{ $jadwal->lokasi->nama ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.jadwal.edit', $jadwal) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.jadwal.destroy', $jadwal) }}" method="POST" x-data="{ show: false }">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            @click.prevent="if(confirm('Yakin ingin menghapus?')) { $el.closest('form').submit() }"
                                            class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-3 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data jadwal pelajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jadwals->hasPages())
        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            {{ $jadwals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
