@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Absensi Saya</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }} — {{ ($metode === 'RFID' || $metode === 'Hybrid') ? 'Metode: Kartu RFID' : 'Metode: Zonasi (Lokasi)' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">

            @if($metode === 'RFID' || $metode === 'Hybrid')
                <div class="rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-white/[0.03]" x-data="{ showOverlay: false, scanResult: null }">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 rounded-full bg-brand-500/10 pulse-ring"></div>
                            <div class="relative flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-500/10 dark:to-brand-500/5">
                                <svg class="h-16 w-16 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                                    <line x1="2" y1="10" x2="22" y2="10"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Absensi Melalui Kartu RFID</h3>
                        <p class="mb-6 max-w-md text-sm text-gray-500 dark:text-gray-400">
                            Untuk melakukan absensi, silakan tempelkan kartu RFID Anda pada mesin Kiosk yang tersedia di area sekolah.
                        </p>

                        @if($absensiStatus && $absensiStatus['sudah_check_in'])
                        <div class="w-full max-w-sm rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Status Hari Ini</div>
                            <div class="flex items-center justify-between">
                                <div class="text-left">
                                    <div class="text-sm text-gray-500">Check-in</div>
                                    <div class="text-lg font-bold text-gray-800 dark:text-white/90">{{ $absensiStatus['jam_masuk'] ?? '-' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Check-out</div>
                                    <div class="text-lg font-bold text-gray-800 dark:text-white/90">{{ $absensiStatus['jam_keluar'] ?? '—' }}</div>
                                </div>
                            </div>
                            @php
                                $badgeClass = match($absensiStatus['status']) {
                                    'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                    'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                    'Alpha' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                    default => 'bg-gray-50 text-gray-700',
                                };
                            @endphp
                            <div class="mt-2 flex justify-center">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $absensiStatus['status'] }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

            @else
                {{-- ZONASI --}}
                @include('components.gps-absensi', [
                    'allowedLocations' => $allowedLocations,
                    'isLibur' => $isLibur,
                    'absensiStatus' => $absensiStatus
                ])
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Informasi Hari Ini</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Hari</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $dayName }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Jam Masuk</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ substr($settings->jam_masuk ?? '07:00', 0, 5) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Jam Pulang</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ substr($settings->jam_pulang ?? '15:00', 0, 5) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Toleransi Terlambat</span>
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $settings->toleransi_terlambat_menit ?? 15 }} menit</span>
                    </div>
                    @if($metode === 'Zonasi')
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Metode</span>
                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">Zonasi</span>
                    </div>
                    @else
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Metode</span>
                        <span class="inline-flex items-center rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-semibold text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">Kartu RFID</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($metode === 'Zonasi')
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h4 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Catatan Penting</h4>
                <ul class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Pastikan Anda berada di dalam area sekolah.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Aktifkan GPS/perangkat lokasi pada browser Anda.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Check-in setelah jam masuk ditandai sebagai Terlambat.</span>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @keyframes pulse-ring { 0% { transform: scale(.8); opacity: 1; } 100% { transform: scale(2.2); opacity: 0; } }
    .pulse-ring { animation: pulse-ring 1.5s cubic-bezier(.215,.61,.355,1) infinite; }
</style>


@endsection
