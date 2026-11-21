@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Hari Libur</h2>
        <a href="{{ route('admin.hari-libur.create') }}"
            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Hari Libur
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif

    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" class="flex items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau tanggal..."
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-72" />
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">Cari</button>
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Setiap Tahun</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($hariLiburs as $i => $libur)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $hariLiburs->firstItem() + $i }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($libur->tanggal)->translatedFormat('d M Y') }}
                            @if($libur->tanggal_selesai && $libur->tanggal_selesai != $libur->tanggal)
                                - {{ \Carbon\Carbon::parse($libur->tanggal_selesai)->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td class="px-5 py-3 font-medium text-gray-800 dark:text-white/90">{{ $libur->nama }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $libur->keterangan ?? '-' }}</td>
                        <td class="px-5 py-3">
                            @if($libur->is_recurring)
                                <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Ya</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Tidak</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.hari-libur.edit', $libur) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Edit</a>
                                <form action="{{ route('admin.hari-libur.destroy', $libur) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" @click.prevent="if(confirm('Hapus hari libur ini?')) { $el.closest('form').submit() }"
                                        class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-3 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data hari libur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $hariLiburs->links() }}</div>
    </div>
</div>
@endsection
