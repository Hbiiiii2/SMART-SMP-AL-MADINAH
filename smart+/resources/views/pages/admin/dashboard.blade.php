@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard Admin</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }} — {{ $data['tahun_ajaran_aktif'] }}</p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Guru</p>
                    <h3 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $data['guru_count'] }}</h3>
                    <p class="mt-1 text-xs text-success-500">{{ $data['guru_hadir_hari_ini'] }} hadir hari ini</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10">
                    <svg class="text-brand-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</p>
                    <h3 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $data['siswa_count'] }}</h3>
                    <p class="mt-1 text-xs text-success-500">{{ $data['siswa_hadir_hari_ini'] }} hadir | <span class="text-error-500">{{ $data['siswa_alfa_hari_ini'] }} alfa</span></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
                    <svg class="text-success-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kartu RFID Aktif</p>
                    <h3 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $data['rfid_count'] }}</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-warning-50 dark:bg-warning-500/10">
                    <svg class="text-warning-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tahun Ajaran Aktif</p>
                    <h3 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $data['tahun_ajaran_aktif'] }}</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-error-50 dark:bg-error-500/10">
                    <svg class="text-error-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @if($data['absensi_siswa_terbaru']->isNotEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Absensi Siswa Hari Ini</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50">
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Siswa</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @foreach($data['absensi_siswa_terbaru'] as $i => $abs)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-white/90">{{ $abs->siswa->nama_lengkap ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->siswa->kelas ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jadwal->mataPelajaran->nama ?? '-' }}</td>
                        <td class="px-6 py-3">
                            @php $badge = match($abs->status) {
                                'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                                default => 'bg-gray-50 text-gray-700',
                            }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
