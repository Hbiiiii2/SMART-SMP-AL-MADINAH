@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
            Penilaian Tugas: {{ $tugas->judul }}
        </h2>
        <nav>
            <ol class="flex items-center gap-2 text-sm">
                <li><a class="font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90" href="{{ route('guru.tugas.index') }}">Tugas /</a></li>
                <li class="font-medium text-brand-500">Penilaian</li>
            </ol>
        </nav>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Detail Tugas</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</p>
                <p class="text-gray-800 dark:text-white/90">{{ $tugas->mataPelajaran->nama ?? '-' }} - Kelas {{ $tugas->kelas }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenggat Waktu</p>
                <p class="text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($tugas->tenggat_waktu)->translatedFormat('l, d F Y H:i') }}</p>
            </div>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Deskripsi</p>
            <div class="text-gray-800 dark:text-white/90 whitespace-pre-wrap mt-1">
                @php
                    $deskripsi = htmlspecialchars($tugas->deskripsi);
                    $deskripsi = preg_replace(
                        '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
                        '<div class="relative w-full max-w-2xl mt-4 mb-4 overflow-hidden rounded-xl pb-[56.25%] border border-gray-200 dark:border-gray-700 shadow-sm"><iframe class="absolute top-0 left-0 h-full w-full" src="https://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe></div>',
                        $deskripsi
                    );
                @endphp
                {!! nl2br($deskripsi) !!}
            </div>
        </div>
        @if($tugas->file_path)
        <div class="mt-4">
            <a href="{{ asset('storage/' . $tugas->file_path) }}" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Lampiran
            </a>
        </div>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Pengumpulan Siswa</h3>
            <a href="{{ route('guru.tugas.export', $tugas) }}" class="inline-flex items-center justify-center rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white hover:bg-success-600 transition-colors gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Siswa</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Waktu Kumpul</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">File Jawaban</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nilai</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Beri Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($pengumpulans as $p)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-4 text-gray-800 dark:text-white/90 align-top">
                                <span class="font-medium">{{ $p->siswa->nama_lengkap ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4 text-gray-800 dark:text-white/90 align-top">
                                {{ \Carbon\Carbon::parse($p->waktu_pengumpulan)->format('d M Y H:i') }}
                            </td>
                            <td class="px-5 py-4 align-top">
                                @php
                                    $isLate = \Carbon\Carbon::parse($p->waktu_pengumpulan)->gt(\Carbon\Carbon::parse($tugas->tenggat_waktu));
                                @endphp
                                <div class="flex flex-col gap-1 items-start">
                                    @if($p->status == 'dinilai')
                                        <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-700/10 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-400/20">Dinilai</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-700/10 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-400/20">Menunggu Nilai</span>
                                    @endif

                                    @if($isLate)
                                        <span class="inline-flex items-center rounded-md bg-error-50 px-2 py-1 text-xs font-medium text-error-700 ring-1 ring-inset ring-error-700/10 dark:bg-error-500/10 dark:text-error-400 dark:ring-error-400/20">Terlambat</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-700/10 dark:bg-brand-500/10 dark:text-brand-400 dark:ring-brand-400/20">Tepat Waktu</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                @if($p->file_path)
                                    <div class="mb-2">
                                        <a href="{{ asset('storage/' . $p->file_path) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Lihat File
                                        </a>
                                    </div>
                                @endif
                                @if($p->link_url)
                                    <div>
                                        <a href="{{ $p->link_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                            Buka Link
                                        </a>
                                    </div>
                                @endif
                                @if(!$p->file_path && !$p->link_url)
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top text-gray-800 dark:text-white/90">
                                <span class="font-bold">{{ $p->nilai ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <form action="{{ route('guru.tugas.nilai', [$tugas, $p->id]) }}" method="POST" class="flex flex-col gap-2">
                                    @csrf
                                    <div class="flex items-center gap-2">
                                        <input type="number" step="any" name="nilai" value="{{ $p->nilai }}" min="0" max="100" required 
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-20 rounded-lg border border-gray-300 bg-transparent px-2.5 py-1.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" 
                                            placeholder="0-100" />
                                        <button type="submit" 
                                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium text-white transition">
                                            Simpan
                                        </button>
                                    </div>
                                    <textarea name="catatan_guru" rows="1" placeholder="Catatan opsional..." 
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-2.5 py-1.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ $p->catatan_guru }}</textarea>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada siswa yang mengumpulkan tugas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
