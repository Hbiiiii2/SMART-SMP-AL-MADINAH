@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Materi Belajar</h2>
        </div>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="{{ route('guru.dashboard') }}">Dashboard /</a></li>
                <li class="text-sm font-medium text-brand-500 dark:text-brand-400">Materi</li>
            </ol>
        </nav>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-success-50 p-4 text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Materi</h3>
            <a href="{{ route('guru.materi.create') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-center font-medium text-white hover:bg-brand-600 transition-colors">
                Tambah Materi
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50">
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Judul</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Tipe</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($materi as $m)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800 dark:text-white/90">
                                {{ $m->judul }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                {{ $m->mataPelajaran->nama ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $m->kelas }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 uppercase text-xs font-semibold">
                                {{ $m->tipe_materi }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-3 text-sm">
                                    <a href="{{ route('guru.materi.show', $m) }}" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300 font-medium">View</a>
                                    <a href="{{ route('guru.materi.edit', $m) }}" class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 font-medium">Edit</a>
                                    <form action="{{ route('guru.materi.destroy', $m) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus materi ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error-500 hover:text-error-600 dark:text-error-400 dark:hover:text-error-300 font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada materi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
