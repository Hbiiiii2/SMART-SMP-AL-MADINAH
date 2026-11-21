@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white/90">
            Materi: {{ $materi->judul }}
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="javascript:history.back()">Kembali</a></li>
            </ol>
        </nav>
    </div>

    <!-- Header Card -->
    <div class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-teal-400 to-emerald-500 p-6 text-white shadow-lg relative">
        <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white opacity-10"></div>
        <div class="absolute right-12 -bottom-8 h-24 w-24 rounded-full bg-white opacity-10"></div>
        
        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center gap-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm text-3xl shadow-inner">
                    📖
                </div>
                <div>
                    <h3 class="text-2xl font-bold">Detail Materi</h3>
                    <p class="mt-1 text-sm font-medium text-teal-50">{{ $materi->mataPelajaran->nama ?? '-' }} - Oleh: {{ $materi->guru->nama_lengkap ?? '-' }}</p>
                </div>
            </div>
            <span class="inline-flex rounded-2xl bg-white/20 px-4 py-2 text-sm font-bold backdrop-blur-sm shadow-sm">
                {{ strtoupper($materi->tipe_materi) }}
            </span>
        </div>
    </div>

    <!-- Content Card -->
    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-8">
            <h4 class="mb-3 flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white/90">
                <span>📝</span> Deskripsi
            </h4>
            <div class="rounded-2xl bg-gray-50 p-5 text-sm font-medium text-gray-600 dark:bg-white/[0.02] dark:text-gray-400">
                <p class="whitespace-pre-wrap">{{ $materi->deskripsi ?? 'Tidak ada deskripsi' }}</p>
            </div>
        </div>

        <div>
            <h4 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white/90">
                <span>🎯</span> Konten Materi
            </h4>
            
            @if($materi->tipe_materi === 'youtube')
                @php
                    $videoId = '';
                    $url = $materi->file_path_atau_url;
                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
                        $videoId = $match[1];
                    }
                @endphp
                @if($videoId)
                    <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl pb-[56.25%] shadow-md border border-gray-100 dark:border-gray-800">
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
                    <a href="{{ $materi->file_path_atau_url }}" target="_blank" class="inline-flex w-full md:w-auto items-center justify-center gap-2 rounded-2xl bg-rose-500 py-4 px-8 text-center font-bold text-white shadow-md hover:bg-rose-600 hover:-translate-y-1 transition-all active:scale-95">
                        <span class="text-xl">▶️</span> Buka Link YouTube
                    </a>
                @endif
            @elseif($materi->tipe_materi === 'pdf')
                <div class="mb-6">
                    <a href="{{ asset('storage/' . $materi->file_path_atau_url) }}" target="_blank" class="inline-flex w-full md:w-auto items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-500 py-4 px-8 text-center font-bold text-white shadow-md hover:shadow-lg hover:-translate-y-1 transition-all active:scale-95">
                        <span class="text-2xl">📄</span> 
                        <span>Buka / Download PDF (Khusus Pengguna HP)</span>
                    </a>
                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">💡 Tip: Jika PDF di bawah ini tidak muncul (diblokir oleh browser HP), silakan klik tombol di atas ya!</p>
                </div>
                <div class="w-full max-w-4xl h-[700px] border border-gray-200 rounded-2xl overflow-hidden shadow-sm dark:border-gray-800 hidden md:block">
                    <iframe src="{{ asset('storage/' . $materi->file_path_atau_url) }}" class="w-full h-full" frameborder="0"></iframe>
                </div>
            @else
                <a href="{{ $materi->file_path_atau_url }}" target="_blank" class="inline-flex w-full md:w-auto items-center justify-center gap-2 rounded-2xl bg-brand-500 py-4 px-8 text-center font-bold text-white shadow-md hover:bg-brand-600 hover:-translate-y-1 transition-all active:scale-95">
                    <span class="text-xl">🔗</span> Buka Tautan
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
