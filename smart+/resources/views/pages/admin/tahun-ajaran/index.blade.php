@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Data Tahun Ajaran</h2>
        <a href="{{ route('admin.tahun-ajaran.create') }}"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Tahun Ajaran
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
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal Mulai</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal Selesai</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($tahunAjarans as $ta)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ $ta->nama }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($ta->tanggal_mulai)->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($ta->tanggal_selesai)->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                @if($ta->is_aktif)
                                    <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">Tidak Aktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    @if(!$ta->is_aktif)
                                        <form action="{{ route('admin.tahun-ajaran.set-aktif', $ta) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg border border-success-300 bg-success-50 px-3 py-1.5 text-xs font-medium text-success-700 transition hover:bg-success-100 dark:border-success-700 dark:bg-success-500/10 dark:text-success-400">
                                                Set Aktif
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.tahun-ajaran.edit', $ta) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.tahun-ajaran.destroy', $ta) }}" method="POST" x-data="{ show: false }">
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
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data tahun ajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $tahunAjarans->links() }}
        </div>
    </div>
</div>
@endsection
