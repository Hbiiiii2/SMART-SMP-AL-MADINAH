@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard Staff Akademik</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('staff.cuti.create') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                + Pengajuan Cuti
            </a>
            <a href="{{ route('staff.cuti.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                History Cuti
            </a>
        </div>
    </div>

    @if(($pengaturan->metode_absensi_staff ?? 'RFID') === 'Zonasi')
        @include('components.gps-absensi')
    @else
        <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-gray-600 dark:text-gray-400">Metode absensi Anda saat ini menggunakan <strong>{{ ($pengaturan->metode_absensi_staff ?? 'RFID') === 'Hybrid' ? 'Hybrid (Manual / RFID)' : 'RFID Kiosk' }}</strong>.</p>
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- History Absensi -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">History Absensi Terakhir</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800/50">
                            <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                            <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Masuk</th>
                            <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Keluar</th>
                            <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                        @forelse($historyAbsensi as $abs)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-6 py-3 text-gray-800 dark:text-gray-300">{{ \Carbon\Carbon::parse($abs->tanggal)->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->waktu_check_in ? \Carbon\Carbon::parse($abs->waktu_check_in)->format('H:i') : '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->waktu_check_out ? \Carbon\Carbon::parse($abs->waktu_check_out)->format('H:i') : '-' }}</td>
                            <td class="px-6 py-3">
                                @php $badge = match($abs->status) {
                                    'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10',
                                    'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10',
                                    'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10',
                                    default => 'bg-gray-50 text-gray-700',
                                }; @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada history absensi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kalender Akademik Statis Placeholder -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Kalender Akademik</h3>
                <a href="{{ route('kalender.index') }}" class="text-sm font-medium text-brand-500 hover:text-brand-600">Lihat Semua</a>
            </div>
            <div class="flex h-64 items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-800/50">
                <!-- A static monthly view or upcoming events could go here -->
                <p class="text-gray-500 dark:text-gray-400 text-center">
                    @if($isLibur)
                        <span class="block text-error-500 font-semibold mb-2">Hari Ini Libur!</span>
                    @endif
                    Silakan klik 'Lihat Semua' untuk melihat Kalender Akademik lengkap.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
