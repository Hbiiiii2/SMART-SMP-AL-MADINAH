@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Jadwal Saya</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
    </div>

    @if($jadwalHariIni->isNotEmpty())
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Jadwal Hari Ini ({{ $hariAktif }})</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800/50">
            @foreach($jadwalHariIni as $j)
            <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 flex-col items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10">
                        <span class="text-xs font-semibold text-brand-500">{{ substr($j->jam_mulai, 0, 5) }}</span>
                        <span class="text-[10px] text-brand-400">s/d</span>
                        <span class="text-xs font-semibold text-brand-500">{{ substr($j->jam_selesai, 0, 5) }}</span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $j->mataPelajaran->nama ?? '-' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Lokasi: {{ $j->lokasi->nama ?? '-' }}</div>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $j->kelas }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-gray-500 dark:text-gray-400">Tidak ada jadwal hari ini.</p>
    </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Semua Jadwal Mingguan</h3>
        </div>
        <div class="p-6">
            @if($semuaJadwal->isEmpty())
                <p class="text-center text-gray-500 dark:text-gray-400">Belum ada jadwal.</p>
            @else
                <div class="space-y-6">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $day)
                        @if($semuaJadwal->has($day))
                        <div>
                            <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full {{ $day === $hariAktif ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                    {{ substr($day, 0, 2) }}
                                </span>
                                {{ $day }} {{ $day === $hariAktif ? '(Hari Ini)' : '' }}
                            </h4>
                            <div class="space-y-2">
                                @foreach($semuaJadwal[$day] as $j)
                                <div class="flex items-center justify-between rounded-xl px-4 py-3 {{ $day === $hariAktif ? 'bg-brand-50/50 dark:bg-brand-500/5 border border-brand-100 dark:border-brand-500/10' : 'bg-gray-50 dark:bg-white/[0.02]' }}">
                                    <div class="flex items-center gap-3">
                                        <span class="w-20 text-sm font-semibold text-gray-800 dark:text-white/90">{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $j->mataPelajaran->nama ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400">{{ $j->lokasi->nama ?? '-' }}</span>
                                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $j->kelas }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
