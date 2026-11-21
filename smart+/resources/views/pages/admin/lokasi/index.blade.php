@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Data Lokasi</h2>
        <a href="{{ route('admin.lokasi.create') }}"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Lokasi
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

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Deskripsi</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Latitude</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Longitude</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Radius</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Pengguna (Mapped)</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($lokasis as $lokasi)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $lokasi->nama }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ Str::limit($lokasi->deskripsi, 40) }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $lokasi->latitude }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $lokasi->longitude }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $lokasi->radius }} m</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $lokasi->users->count() }} orang</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.lokasi.edit', $lokasi) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.lokasi.destroy', $lokasi) }}" method="POST" x-data="{ show: false }">
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
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data lokasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $lokasis->links() }}
        </div>
    </div>
</div>
@endsection
