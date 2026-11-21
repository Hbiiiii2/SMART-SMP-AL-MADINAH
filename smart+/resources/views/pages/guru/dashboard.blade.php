@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard Guru</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
        </div>
    </div>

    {{-- Status Absensi Guru --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                @if($absensiStatus && $absensiStatus['sudah_check_in'])
                    @php
                        $guruBadgeClass = match($absensiStatus['status']) {
                            'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                            'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                            default => 'bg-gray-50 text-gray-700',
                        };
                    @endphp
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $absensiStatus['status'] === 'Hadir' ? 'bg-success-50 dark:bg-success-500/10' : 'bg-warning-50 dark:bg-warning-500/10' }}">
                        @if($absensiStatus['status'] === 'Hadir')
                            <svg class="h-6 w-6 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="h-6 w-6 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Absensi Hari Ini</div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $guruBadgeClass }}">{{ $absensiStatus['status'] }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Masuk: {{ $absensiStatus['jam_masuk'] }}</span>
                            @if($absensiStatus['jam_keluar'])
                                <span class="text-sm text-gray-500 dark:text-gray-400">| Keluar: {{ $absensiStatus['jam_keluar'] }}</span>
                            @endif
                        </div>
                    </div>
                @elseif($isLibur)
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-800">
                        <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Absensi Hari Ini</div>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-400">Libur</span>
                    </div>
                @else
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-error-50 dark:bg-error-500/10">
                        <svg class="h-6 w-6 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Absensi Hari Ini</div>
                        <span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-semibold text-error-700 dark:bg-error-500/10 dark:text-error-400">Belum Absen</span>
                    </div>
                @endif
            </div>
            <div>
                @if($metode === 'Zonasi' && (!$absensiStatus || !$absensiStatus['sudah_check_in']))
                    <a href="{{ route('guru.absensi-saya.index') }}"
                        class="bg-success-500 shadow-theme-xs hover:bg-success-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Check-in Sekarang
                    </a>
                @elseif($metode === 'Zonasi' && $absensiStatus['sudah_check_in'] && !$absensiStatus['sudah_check_out'])
                    <a href="{{ route('guru.absensi-saya.index') }}"
                        class="bg-warning-500 shadow-theme-xs hover:bg-warning-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Check-out Sekarang
                    </a>
                @elseif($metode === 'RFID' || $metode === 'Hybrid')
                    <a href="{{ route('guru.absensi-saya.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                        Lihat Status Absensi
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($metode === 'Zonasi')
        @include('components.gps-absensi')
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] col-span-full">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Rekap Kelas Hari Ini</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Jadwal Mengajar: {{ count($rekapJadwal) }} Kelas</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10">
                    <svg class="text-brand-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($rekapJadwal as $rekap)
                <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-5 dark:border-gray-800/80 dark:bg-gray-800/20 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="inline-flex items-center rounded-lg bg-brand-100 px-2.5 py-1 text-xs font-bold text-brand-700 dark:bg-brand-500/20 dark:text-brand-400 mb-2">
                                Kelas {{ $rekap['jadwal']->kelas }}
                            </span>
                            <h4 class="font-bold text-gray-800 dark:text-white/90">{{ $rekap['jadwal']->mataPelajaran->nama ?? '-' }}</h4>
                            <p class="text-xs text-gray-500 font-medium">{{ substr($rekap['jadwal']->jam_mulai, 0, 5) }} - {{ substr($rekap['jadwal']->jam_selesai, 0, 5) }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- Progress Bar -->
                        @php
                            $persentase = $rekap['totalSiswa'] > 0 ? round(($rekap['hadirCount'] / $rekap['totalSiswa']) * 100) : 0;
                            $progressColor = $persentase >= 85 ? 'bg-success-500' : ($persentase >= 70 ? 'bg-warning-500' : 'bg-error-500');
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-600 dark:text-gray-400">Kehadiran</span>
                                <span class="font-bold {{ $persentase >= 85 ? 'text-success-500' : ($persentase >= 70 ? 'text-warning-500' : 'text-error-500') }}">{{ $persentase }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                <div class="{{ $progressColor }} h-2.5 rounded-full" style="width: {{ $persentase }}%"></div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-2 flex items-center justify-between border border-gray-100 dark:border-gray-700">
                                <span class="text-xs font-medium text-gray-500 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-success-500"></span> Hadir</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-white">{{ $rekap['hadirCount'] }}</span>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-2 flex items-center justify-between border border-gray-100 dark:border-gray-700">
                                <span class="text-xs font-medium text-gray-500 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-error-500"></span> Alfa</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-white">{{ $rekap['alfaCount'] }}</span>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-2 flex items-center justify-between border border-gray-100 dark:border-gray-700">
                                <span class="text-xs font-medium text-gray-500 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Izin</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-white">{{ $rekap['izinCount'] }}</span>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-2 flex items-center justify-between border border-gray-100 dark:border-gray-700">
                                <span class="text-xs font-medium text-gray-500 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Sakit</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-white">{{ $rekap['sakitCount'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-12 text-center">
                    <div class="text-4xl mb-4">☕</div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Tidak Ada Jadwal</h3>
                    <p class="text-gray-500 dark:text-gray-400">Bapak/Ibu sedang tidak ada jadwal mengajar hari ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
