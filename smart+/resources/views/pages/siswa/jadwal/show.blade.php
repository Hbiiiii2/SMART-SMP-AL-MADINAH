@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10" x-data="{ activeTab: 'materi' }">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white/90">
            {{ $jadwal->mataPelajaran->nama ?? '-' }} 🎯
        </h2>

        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="{{ route('siswa.dashboard') }}">Jadwal /</a></li>
                <li class="text-sm font-medium text-brand-500 dark:text-brand-400">Detail</li>
            </ol>
        </nav>
    </div>

    <!-- Info Card with Fun Style -->
    <div class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-r from-blue-500 to-brand-500 p-6 text-white shadow-lg relative">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white opacity-10"></div>
        <div class="absolute right-20 -bottom-10 h-24 w-24 rounded-full bg-white opacity-10"></div>
        
        <div class="relative z-10 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm text-2xl">👨‍🏫</div>
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-blue-100">Guru Pengampu</p>
                    <p class="text-lg font-bold">{{ $jadwal->guru->nama_lengkap ?? '-' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm text-2xl">⏰</div>
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-blue-100">Waktu</p>
                    <p class="text-lg font-bold">{{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm text-2xl">🏫</div>
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-blue-100">Lokasi</p>
                    <p class="text-lg font-bold">{{ $jadwal->lokasi->nama ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs with Bubbles -->
    <div class="mb-8 flex flex-wrap gap-4 border-b-2 border-gray-100 pb-4 dark:border-gray-800">
        <button @click="activeTab = 'materi'"
            :class="activeTab === 'materi' ? 'bg-brand-500 text-white shadow-md -translate-y-1' : 'bg-gray-100 text-gray-600 hover:bg-brand-50 hover:text-brand-500 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.1]'"
            class="flex items-center gap-2 rounded-2xl px-6 py-3 font-bold transition-all duration-300">
            <span>📚</span> Materi Belajar ({{ $materi->count() }})
        </button>
        <button @click="activeTab = 'tugas'"
            :class="activeTab === 'tugas' ? 'bg-indigo-500 text-white shadow-md -translate-y-1' : 'bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-500 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.1]'"
            class="flex items-center gap-2 rounded-2xl px-6 py-3 font-bold transition-all duration-300">
            <span>📝</span> Tugas Saya ({{ $tugas->count() }})
        </button>
    </div>

    <!-- Materi Tab Content -->
    <div x-show="activeTab === 'materi'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
        @forelse($materi as $m)
        <div class="group flex flex-col md:flex-row md:items-center justify-between gap-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/50">
            <div class="flex items-start gap-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-500 shrink-0 transition-colors group-hover:bg-brand-500 group-hover:text-white dark:bg-blue-500/10 dark:text-blue-400">
                    @if($m->tipe_materi == 'pdf')
                        <span class="text-3xl">📄</span>
                    @elseif($m->tipe_materi == 'youtube')
                        <span class="text-3xl">▶️</span>
                    @else
                        <span class="text-3xl">🔗</span>
                    @endif
                </div>
                <div>
                    <h4 class="text-xl font-bold text-gray-800 group-hover:text-brand-600 dark:text-white/90 dark:group-hover:text-brand-400">{{ $m->judul }}</h4>
                    <p class="text-sm font-medium text-gray-500 mt-1 line-clamp-2 dark:text-gray-400">{{ $m->deskripsi ?? 'Tidak ada deskripsi' }}</p>
                    <p class="text-xs font-bold text-gray-400 mt-3 dark:text-gray-500">Diupload pada: {{ \Carbon\Carbon::parse($m->created_at)->format('d M Y H:i') }}</p>
                </div>
            </div>
            <div class="mt-2 md:mt-0 shrink-0 text-right">
                <a href="{{ route('siswa.materi.show', $m) }}" class="inline-flex w-full md:w-auto items-center justify-center gap-2 rounded-2xl bg-brand-500 py-3 px-8 text-center font-bold text-white shadow-sm hover:bg-brand-600 hover:shadow-md transition-all active:scale-95">
                    Mulai Belajar 🚀
                </a>
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white p-12 dark:border-gray-800 dark:bg-white/[0.03] text-center shadow-sm">
            <div class="mb-4 text-6xl">
                🤷‍♂️
            </div>
            <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Belum Ada Materi</h4>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Guru kamu belum upload materi apa-apa nih.</p>
        </div>
        @endforelse
    </div>

    <!-- Tugas Tab Content -->
    <div x-show="activeTab === 'tugas'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5" style="display: none;">
        @forelse($tugas as $t)
        @php
            $pengumpulan = $t->pengumpulanTugas->first();
            $status = 'Belum Dikerjakan';
            $statusColor = 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400';
            if ($pengumpulan) {
                if ($pengumpulan->status == 'dinilai') {
                    $status = 'Sudah Dinilai (' . $pengumpulan->nilai . '/100)';
                    $statusColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400';
                } else {
                    $status = 'Sudah Dikumpulkan';
                    $statusColor = 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400';
                }
            }
        @endphp
        <div class="group flex flex-col md:flex-row md:items-center justify-between gap-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-indigo-500/50">
            <div class="flex items-start gap-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-500 shrink-0 transition-colors group-hover:bg-indigo-500 group-hover:text-white dark:bg-indigo-500/10 dark:text-indigo-400">
                    <span class="text-3xl">📝</span>
                </div>
                <div>
                    <h4 class="text-xl font-bold text-gray-800 group-hover:text-indigo-600 dark:text-white/90 dark:group-hover:text-indigo-400">{{ $t->judul }}</h4>
                    <p class="text-sm font-medium text-gray-500 mt-1 line-clamp-2 dark:text-gray-400">{{ $t->deskripsi }}</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-white/[0.05] rounded-full px-3 py-1">
                            Tenggat: <span class="text-rose-500 dark:text-rose-400">{{ \Carbon\Carbon::parse($t->tenggat_waktu)->translatedFormat('d M Y') }}</span>
                        </p>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusColor }}">
                            {{ $status }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-2 md:mt-0 shrink-0 text-right">
                <a href="{{ route('siswa.tugas.show', $t) }}" class="inline-flex w-full md:w-auto items-center justify-center gap-2 rounded-2xl border-2 border-indigo-500 bg-transparent py-2.5 px-8 text-center font-bold text-indigo-600 hover:bg-indigo-500 hover:text-white shadow-sm transition-all active:scale-95 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-500 dark:hover:text-white">
                    Cek Tugas ✨
                </a>
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white p-12 dark:border-gray-800 dark:bg-white/[0.03] text-center shadow-sm">
            <div class="mb-4 text-6xl">
                🎮
            </div>
            <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Belum Ada Tugas</h4>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Asik! Belum ada tugas untuk mata pelajaran ini.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
