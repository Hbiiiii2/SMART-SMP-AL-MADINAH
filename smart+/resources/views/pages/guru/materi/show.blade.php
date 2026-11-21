@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-bold text-black dark:text-white">
            Detail Materi
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="font-medium" href="{{ route('guru.materi.index') }}">Materi /</a></li>
                <li class="font-medium text-primary">Detail</li>
            </ol>
        </nav>
    </div>

    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
            <h3 class="font-medium text-black dark:text-white">{{ $materi->judul }}</h3>
            <a href="{{ route('guru.materi.edit', $materi) }}" class="text-primary hover:underline">Edit Materi</a>
        </div>
        <div class="p-6.5">
            <div class="mb-6">
                <h4 class="text-sm font-medium text-black dark:text-white mb-2">Mata Pelajaran</h4>
                <p class="text-gray-500">{{ $materi->mataPelajaran->nama ?? '-' }} - Kelas {{ $materi->kelas }}</p>
            </div>

            <div class="mb-6">
                <h4 class="text-sm font-medium text-black dark:text-white mb-2">Deskripsi</h4>
                <p class="text-gray-500 whitespace-pre-wrap">{{ $materi->deskripsi ?? 'Tidak ada deskripsi' }}</p>
            </div>

            <div class="mb-6">
                <h4 class="text-sm font-medium text-black dark:text-white mb-4">File Materi ({{ strtoupper($materi->tipe_materi) }})</h4>
                
                @if($materi->tipe_materi === 'youtube')
                    @php
                        // Extract video ID from youtube URL
                        $videoId = '';
                        $url = $materi->file_path_atau_url;
                        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
                            $videoId = $match[1];
                        }
                    @endphp
                    @if($videoId)
                        <div class="relative w-full max-w-3xl overflow-hidden rounded-lg pb-[56.25%]">
                            <iframe 
                                class="absolute top-0 left-0 h-full w-full"
                                src="https://www.youtube.com/embed/{{ $videoId }}" 
                                title="YouTube video player" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                    @else
                        <a href="{{ $materi->file_path_atau_url }}" target="_blank" class="inline-flex items-center justify-center rounded-md bg-primary py-2 px-6 text-center font-medium text-white hover:bg-opacity-90">Buka Link YouTube</a>
                    @endif
                @elseif($materi->tipe_materi === 'pdf')
                    <div class="mb-4">
                        <a href="{{ asset('storage/' . $materi->file_path_atau_url) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 py-2.5 px-6 font-medium text-white hover:bg-brand-600 transition-colors">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Buka / Download PDF (Khusus Pengguna HP)
                        </a>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Jika PDF di bawah ini tidak muncul atau diblokir (terutama di Chrome/HP), silakan klik tombol di atas.</p>
                    </div>
                    <div class="w-full max-w-4xl h-[600px] border border-gray-200 rounded-xl overflow-hidden shadow-sm dark:border-gray-800 hidden md:block">
                        <iframe src="{{ asset('storage/' . $materi->file_path_atau_url) }}" class="w-full h-full" frameborder="0"></iframe>
                    </div>
                @else
                    <a href="{{ $materi->file_path_atau_url }}" target="_blank" class="inline-flex items-center justify-center rounded-md bg-primary py-2 px-6 text-center font-medium text-white hover:bg-opacity-90">Buka Tautan</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
