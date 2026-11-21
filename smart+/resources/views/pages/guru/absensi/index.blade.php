@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Absensi Siswa</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }} — Kelola kehadiran siswa di kelas Anda</p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if ($errors->has('error'))
        <div class="mb-5 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ $errors->first('error') }}</div>
    @endif

    <div class="mb-6">
        <form method="GET" action="{{ route('guru.absensi.index') }}">
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Pilih Jadwal Hari Ini</label>
            <select name="jadwal_id" onchange="this.form.submit()"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full max-w-md rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">— Pilih Jadwal —</option>
                @foreach($jadwals as $j)
                    <option value="{{ $j->id }}" {{ $selectedJadwalId == $j->id ? 'selected' : '' }}>
                        {{ $j->mataPelajaran->nama }} — {{ substr($j->jam_mulai, 0, 5) }} s/d {{ substr($j->jam_selesai, 0, 5) }} ({{ $j->kelas }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($selectedJadwalId && $jadwal)
        @php
            $hadirCount = $absensis->where('status', 'Hadir')->count();
            $terlambatCount = $absensis->where('status', 'Terlambat')->count();
                                $alfaCount = $absensis->where('status', 'Alfa')->count();
            $sakitCount = $absensis->where('status', 'Sakit')->count();
            $izinCount = $absensis->where('status', 'Izin')->count();
            $total = $absensis->count();
        @endphp

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="text-xl font-bold text-success-500">{{ $hadirCount + $terlambatCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Hadir</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="text-xl font-bold text-warning-500">{{ $terlambatCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Terlambat</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="text-xl font-bold text-error-500">{{ $alfaCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Belum Absen</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="text-xl font-bold text-blue-500">{{ $sakitCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Sakit</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="text-xl font-bold text-purple-500">{{ $izinCount }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Izin</div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Kehadiran — {{ $jadwal->mataPelajaran->nama ?? '-' }} ({{ $jadwal->kelas }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">NISN</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Siswa</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Keluar</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                        @foreach($absensis as $i => $absensi)
                            @php
                                $statusTampil = $absensi->status;
                                $badgeClass = match($statusTampil) {
                                    'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                    'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                                    'Alfa' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                    'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                    'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                                    default => 'bg-gray-50 text-gray-700',
                                };
                                $canEdit = $absensi->status === 'Alfa';
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $absensi->siswa->nisn ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{{ $absensi->siswa->nama_lengkap ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $statusTampil }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $absensi->jam_masuk ? \Carbon\Carbon::parse($absensi->jam_masuk)->format('H:i') : '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $absensi->jam_keluar ? \Carbon\Carbon::parse($absensi->jam_keluar)->format('H:i') : '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($canEdit)
                                        <div x-data="{ showModal: false }">
                                            <button @click="showModal = true"
                                                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-600">
                                                Ubah Status
                                            </button>
                                            <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" style="display:none">
                                                <div @click.away="showModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-theme-lg dark:bg-gray-900">
                                                    <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Ubah Status Kehadiran</h3>
                                                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $absensi->siswa->nama_lengkap ?? '-' }} — {{ $absensi->siswa->nisn ?? '' }}</p>
                                                    <form method="POST" action="{{ route('guru.absensi.update-status', $absensi->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status Baru</label>
                                                                <select name="status" required
                                                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                                    <option value="Hadir">Hadir</option>
                                                                    <option value="Sakit">Sakit</option>
                                                                    <option value="Izin">Izin</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Alasan</label>
                                                                <textarea name="alasan" rows="3" required placeholder="Masukkan alasan perubahan..."
                                                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                                            </div>
                                                            <div class="flex items-center gap-3">
                                                                <button type="button" @click="showModal = false"
                                                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                                                    Batal
                                                                </button>
                                                                <button type="submit"
                                                                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                                                                    Simpan
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
