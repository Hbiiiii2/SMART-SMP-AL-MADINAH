@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Kalender Akademik</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jadwal pelajaran & hari libur</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('kalender.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <span class="min-w-[160px] text-center text-lg font-semibold text-gray-800 dark:text-white/90">{{ $monthName }}</span>
            <a href="{{ route('kalender.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="{{ route('kalender.index', ['year' => now('Asia/Jakarta')->year, 'month' => now('Asia/Jakarta')->month]) }}"
                class="ml-2 inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                Hari Ini
            </a>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-4 text-sm">
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-error-100 border border-error-300"></div>
            <span class="text-gray-600 dark:text-gray-400">Hari Libur</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-brand-100 border border-brand-300"></div>
            <span class="text-gray-600 dark:text-gray-400">Ada Jadwal</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-success-100 border border-success-300"></div>
            <span class="text-gray-600 dark:text-gray-400">Ada Absensi</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-purple-100 border border-purple-300"></div>
            <span class="text-gray-600 dark:text-gray-400">Agenda Akademik</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full border-2 border-brand-500"></div>
            <span class="text-gray-600 dark:text-gray-400">Hari Ini</span>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-7 gap-px rounded-lg bg-gray-200 dark:bg-gray-700">
            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $dayName)
                <div class="bg-gray-50 py-2 text-center text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $dayName }}</div>
            @endforeach

            @foreach($calendarDays as $day)
                @php
                    $bgClass = 'bg-white dark:bg-gray-900';
                    if (!$day['isCurrentMonth']) {
                        $bgClass = 'bg-gray-50 dark:bg-gray-900/50';
                    }
                    if ($day['isLibur']) {
                        $bgClass = 'bg-error-50/50 dark:bg-error-500/5';
                    } elseif ($day['jadwalCount'] > 0) {
                        $bgClass = 'bg-brand-50/30 dark:bg-brand-500/5';
                    }
                @endphp
                <div class="{{ $bgClass }} relative min-h-[80px] p-1.5 sm:min-h-[100px] sm:p-2"
                    x-data="{ showTip: false }"
                    @mouseenter="if('{{ $day['liburNama'] ?? '' }}') showTip = true"
                    @mouseleave="showTip = false">

                    <div class="flex items-start justify-between">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium
                            @if(!$day['isCurrentMonth']) text-gray-400 dark:text-gray-600
                            @elseif($day['isToday']) bg-brand-500 text-white font-bold
                            @elseif($day['isLibur']) text-error-600 font-semibold
                            @else text-gray-700 dark:text-gray-300
                            @endif">
                            {{ $day['day'] }}
                        </span>
                        @if($day['jadwalCount'] > 0 && $day['isCurrentMonth'])
                            <span class="hidden sm:inline-flex h-5 items-center rounded-full bg-brand-100 px-1.5 text-[10px] font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                {{ $day['jadwalCount'] }} jadwal
                            </span>
                        @endif
                    </div>

                    @if($day['isLibur'] && $day['isCurrentMonth'])
                        <div class="mt-1 hidden sm:block">
                            <span class="inline-block rounded bg-error-100 px-1 py-0.5 text-[9px] font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400 line-clamp-2">
                                {{ $day['liburNama'] }}
                            </span>
                        </div>
                    @endif

                    @if(isset($day['agenda']) && $day['agenda']->count() > 0 && $day['isCurrentMonth'])
                        @foreach($day['agenda'] as $agenda)
                        <div class="mt-1 hidden sm:block">
                            <span class="inline-block rounded bg-purple-100 px-1 py-0.5 text-[9px] font-medium text-purple-700 dark:bg-purple-500/10 dark:text-purple-400 line-clamp-2" title="{{ $agenda->keterangan }}">
                                {{ $agenda->nama_kegiatan }}
                            </span>
                        </div>
                        @endforeach
                    @endif

                    @if($day['absensi'] && $day['isCurrentMonth'])
                        <div class="mt-auto pt-1 hidden sm:flex items-center justify-between text-[9px] text-gray-500 dark:text-gray-400">
                            <span class="text-success-600">✓{{ $day['absensi']['hadir'] }}</span>
                            <span class="text-error-600">✗{{ $day['absensi']['alfa'] }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if($hariLiburs->isNotEmpty())
    <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Hari Libur Bulan Ini</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800/50">
            @foreach($hariLiburs->sortBy('tanggal') as $libur)
            <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-error-50 dark:bg-error-500/10">
                        <svg class="h-5 w-5 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $libur->nama }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($libur->tanggal)->translatedFormat('d M Y') }}
                            @if($libur->tanggal_selesai && $libur->tanggal_selesai != $libur->tanggal)
                                - {{ \Carbon\Carbon::parse($libur->tanggal_selesai)->translatedFormat('d M Y') }}
                            @endif
                            {{ $libur->is_recurring ? ' (Berulang)' : '' }}
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
