@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white/90">
            Tugas: {{ $tugas->judul }}
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="javascript:history.back()">Kembali</a></li>
            </ol>
        </nav>
    </div>

    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-emerald-700 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-400">
            <span class="text-2xl">🎉</span>
            <p class="font-bold">{{ session('success') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-rose-50 p-5 text-rose-700 shadow-sm dark:bg-rose-500/10 dark:text-rose-400">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-2xl">⚠️</span>
                <p class="font-bold">Oops! Ada yang salah nih:</p>
            </div>
            <ul class="list-disc pl-11 font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
        <!-- Detail Tugas -->
        <div class="flex flex-col gap-8">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-white shadow-lg relative">
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white opacity-10"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm text-3xl shadow-inner">
                            📝
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Informasi Tugas</h3>
                            <p class="mt-1 text-sm font-medium text-indigo-100">{{ $tugas->mataPelajaran->nama ?? '-' }} - Oleh: {{ $tugas->guru->nama_lengkap ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                        <p class="text-sm font-medium text-indigo-100 mb-1">Tenggat Waktu</p>
                        <p class="text-lg font-bold flex items-center gap-2">
                            <span>⏰</span> {{ \Carbon\Carbon::parse($tugas->tenggat_waktu)->translatedFormat('l, d F Y H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <h4 class="mb-3 flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white/90">
                    <span>🎯</span> Instruksi Pengerjaan
                </h4>
                <div class="rounded-2xl bg-gray-50 p-5 text-sm font-medium text-gray-600 dark:bg-white/[0.02] dark:text-gray-400 mb-6">
                    <div class="whitespace-pre-wrap">
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
                <div class="border-t-2 border-gray-100 pt-6 dark:border-gray-800">
                    <h4 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white/90">
                        <span>📎</span> Lampiran Guru
                    </h4>
                    <a href="{{ asset('storage/' . $tugas->file_path) }}" target="_blank" class="inline-flex w-full md:w-auto items-center justify-center gap-3 rounded-2xl bg-indigo-50 py-3.5 px-6 font-bold text-indigo-600 hover:bg-indigo-100 hover:-translate-y-1 transition-all active:scale-95 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20">
                        <span class="text-xl">📥</span> Download Lampiran
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Form Pengumpulan -->
        <div class="flex flex-col gap-8">
            <div class="rounded-3xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b-2 border-gray-100 p-6 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                        <span>📤</span> Area Kumpul Tugas
                    </h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($pengumpulan && $pengumpulan->status == 'dinilai')
                            <span class="inline-flex rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">✨ Sudah Dinilai</span>
                        @elseif($pengumpulan)
                            <span class="inline-flex rounded-full bg-amber-100 px-4 py-1.5 text-sm font-bold text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">⏳ Menunggu Nilai</span>
                        @else
                            <span class="inline-flex rounded-full bg-rose-100 px-4 py-1.5 text-sm font-bold text-rose-700 dark:bg-rose-500/20 dark:text-rose-400">❌ Belum Dikerjakan</span>
                        @endif

                        @if($pengumpulan)
                            @php
                                $isLate = \Carbon\Carbon::parse($pengumpulan->waktu_pengumpulan)->gt(\Carbon\Carbon::parse($tugas->tenggat_waktu));
                            @endphp
                            @if($isLate)
                                <span class="inline-flex rounded-full bg-rose-100 px-4 py-1.5 text-sm font-bold text-rose-700 dark:bg-rose-500/20 dark:text-rose-400">🚨 Terlambat</span>
                            @else
                                <span class="inline-flex rounded-full bg-brand-100 px-4 py-1.5 text-sm font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-400">👍 Tepat Waktu</span>
                            @endif
                        @endif
                    </div>
                </div>
                
                <div class="p-6">
                    @if($pengumpulan)
                        <div class="mb-8 overflow-hidden rounded-2xl border-2 border-indigo-100 bg-indigo-50/50 p-6 dark:border-indigo-500/20 dark:bg-indigo-500/5">
                            <h4 class="mb-1 text-sm font-bold text-indigo-900 dark:text-indigo-200">Tugas Anda telah dikumpulkan pada:</h4>
                            <p class="mb-5 text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ \Carbon\Carbon::parse($pengumpulan->waktu_pengumpulan)->format('d M Y, H:i') }}</p>
                            
                            @if($pengumpulan->file_path)
                                <a href="{{ asset('storage/' . $pengumpulan->file_path) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-600 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all active:scale-95 dark:bg-white/[0.05] dark:text-indigo-300 mb-2">
                                    <span>👀</span> Lihat File Jawaban Anda
                                </a>
                            @endif
                            
                            @if($pengumpulan->link_url)
                                <a href="{{ $pengumpulan->link_url }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-600 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all active:scale-95 dark:bg-white/[0.05] dark:text-indigo-300">
                                    <span>🔗</span> Buka Tautan Jawaban Anda
                                </a>
                            @endif

                            @if($pengumpulan->status == 'dinilai')
                                <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm dark:bg-white/[0.05]">
                                    <h4 class="mb-2 text-sm font-bold text-gray-800 dark:text-white/90 text-center">Nilai Anda:</h4>
                                    <div class="text-center mb-4">
                                        <span class="text-5xl font-extrabold text-emerald-500">{{ $pengumpulan->nilai }}</span>
                                        <span class="text-xl font-bold text-gray-300 dark:text-gray-600">/100</span>
                                    </div>
                                    @if($pengumpulan->catatan_guru)
                                        <div class="rounded-xl bg-amber-50 p-4 border border-amber-100 dark:bg-amber-500/10 dark:border-amber-500/20">
                                            <h5 class="mb-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-500 flex items-center gap-1"><span>💬</span> Catatan Guru:</h5>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $pengumpulan->catatan_guru }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(!$pengumpulan || $pengumpulan->status != 'dinilai')
                        <form action="{{ route('siswa.tugas.submit', $tugas) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @php
                                $tipePengumpulan = explode(',', $tugas->tipe_pengumpulan);
                            @endphp

                            @if(in_array('file', $tipePengumpulan))
                            <div>
                                <label class="mb-3 block text-base font-bold text-gray-800 dark:text-white/90">Upload File Jawaban @if(!in_array('link_url', $tipePengumpulan))<span class="text-rose-500">*</span>@endif</label>
                                
                                <div class="relative group">
                                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-500 opacity-20 blur group-hover:opacity-30 transition-opacity"></div>
                                    <input type="file" name="file_jawaban" {{ !in_array('link_url', $tipePengumpulan) ? 'required' : '' }} accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" class="relative w-full cursor-pointer rounded-2xl border-2 border-dashed border-brand-200 bg-white px-4 py-8 text-center text-sm font-medium text-gray-600 outline-none transition-all hover:border-brand-500 focus:border-brand-500 file:hidden hover:bg-brand-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-brand-400 dark:hover:bg-gray-800/80" />
                                </div>
                                <div class="mt-3 flex items-start gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <span>💡</span>
                                    <p>Format yang didukung: PDF, Word, Gambar, atau ZIP. (Max 10MB)</p>
                                </div>
                            </div>
                            @endif

                            @if(in_array('link_url', $tipePengumpulan))
                            <div>
                                <label class="mb-3 block text-base font-bold text-gray-800 dark:text-white/90">Link URL Jawaban (Video dll) @if(!in_array('file', $tipePengumpulan))<span class="text-rose-500">*</span>@endif</label>
                                <input type="url" name="link_url" {{ !in_array('file', $tipePengumpulan) ? 'required' : '' }} placeholder="https://..." class="w-full rounded-2xl border-2 border-brand-200 bg-white px-4 py-4 text-sm font-medium text-gray-600 outline-none transition-all focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:focus:border-brand-400">
                                <div class="mt-3 flex items-start gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <span>💡</span>
                                    <p>Pastikan akses link video (misal di Google Drive) diatur menjadi "Everyone with the link" atau "Siapa saja yang memiliki tautan".</p>
                                </div>
                            </div>
                            @endif

                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-600 p-4 text-lg font-bold text-white shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all active:scale-95">
                                <span>🚀</span> {{ $pengumpulan ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas Sekarang!' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
