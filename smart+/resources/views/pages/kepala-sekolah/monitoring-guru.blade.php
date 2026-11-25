@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Monitoring Kehadiran Guru</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }} — Kelola kehadiran guru hari ini</p>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if($errors->has('error'))
        <div class="mb-5 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ $errors->first('error') }}</div>
    @endif

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama atau NIP..."
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-72" />
            <select name="status_filter" onchange="this.form.submit()"
                class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua Status</option>
                <option value="hadir" {{ request('status_filter') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                <option value="terlambat" {{ request('status_filter') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                <option value="alpha" {{ request('status_filter') === 'alpha' ? 'selected' : '' }}>Alpha</option>
                <option value="belum_absen" {{ request('status_filter') === 'belum_absen' ? 'selected' : '' }}>Belum Absen</option>
            </select>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            @if(request('search') || request('status_filter'))
                <a href="{{ route('ks.monitoring-guru') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
            @endif
        </form>
    </div>

    @php
        $guruWithLocation = $gurus->getCollection()->filter(function ($g) {
            return $g->latitude && $g->longitude && $g->latitude != 0 && $g->longitude != 0;
        })->values();
        $guruJson = $guruWithLocation->map(function ($g) {
            return [
                'nama' => $g->nama_lengkap,
                'nip' => $g->nip,
                'lat' => $g->latitude,
                'lng' => $g->longitude,
                'status' => $g->status_tampil,
                'jam_masuk' => $g->jam_masuk ? \Carbon\Carbon::parse($g->jam_masuk)->format('H:i') : '-',
            ];
        })->toJson();
    @endphp

    @if(isset($pengaturan) && $pengaturan->metode_absensi_guru === 'zonasi' && $guruWithLocation->isNotEmpty())
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Peta Lokasi Absensi Guru</h3>
            <p class="text-xs text-gray-400">{{ $guruWithLocation->count() }} guru dengan data GPS</p>
        </div>
        <div id="map" style="height: 400px; width: 100%;"></div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var guruData = {!! $guruJson !!};

            if (guruData.length === 0) return;

            var map = L.map('map').setView([guruData[0].lat, guruData[0].lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            var bounds = L.latLngBounds();

            guruData.forEach(function(g) {
                var color = g.status === 'Hadir' ? '#22c55e' : g.status === 'Terlambat' ? '#f59e0b' : '#6b7280';
                var icon = L.divIcon({
                    html: '<div style="background:' + color + ';width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.3)"></div>',
                    iconSize: [12, 12],
                    className: ''
                });

                var marker = L.marker([g.lat, g.lng], {icon: icon}).addTo(map);
                marker.bindPopup(
                    '<div style="font-size:13px">' +
                    '<strong>' + g.nama + '</strong><br>' +
                    '<span style="color:#6b7280">NIP: ' + g.nip + '</span><br>' +
                    '<span style="color:' + color + ';font-weight:600">' + g.status + '</span> · ' + g.jam_masuk +
                    '</div>'
                );
                bounds.extend([g.lat, g.lng]);
            });

            if (guruData.length > 1) {
                map.fitBounds(bounds, {padding: [30, 30]});
            }
        });
    </script>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">NIP</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Guru</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Status Hari Ini</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Keluar</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Lokasi Absen</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($gurus as $i => $guru)
                    @php
                        $badge = match($guru->status_tampil) {
                            'Hadir' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                            'Terlambat' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                            'Alpha' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                            'Sakit' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                            'Izin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                            default => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                        };
                        $canChange = $guru->status_tampil === 'Alpha' && !empty($guru->absensi_id);
                        $canManual = $guru->status_tampil === 'Belum Absen';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-gray-500">{{ $gurus->firstItem() + $i }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $guru->nip }}</td>
                        <td class="px-5 py-3 font-medium text-gray-800 dark:text-white/90">{{ $guru->nama_lengkap }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $guru->status_tampil }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $guru->jam_masuk ? \Carbon\Carbon::parse($guru->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $guru->jam_keluar ? \Carbon\Carbon::parse($guru->jam_keluar)->format('H:i') : '-' }}</td>
                        <td class="px-5 py-3">
                            @if($guru->latitude && $guru->longitude && $guru->latitude != 0 && $guru->longitude != 0)
                                <a href="https://www.google.com/maps?q={{ $guru->latitude }},{{ $guru->longitude }}" target="_blank"
                                    class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 transition hover:bg-success-100 dark:bg-success-500/10 dark:text-success-400">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    {{ number_format($guru->latitude, 4) }}, {{ number_format($guru->longitude, 4) }}
                                </a>
                            @elseif(!is_null($guru->latitude) && $guru->jam_masuk)
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">Manual</span>
                            @else
                                <span class="text-xs text-gray-400 italic">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($canChange && !auth()->user()?->isAkunDemo())
                                <div class="flex items-center gap-2" x-data="{ showModal: false }">
                                    <button @click="showModal = true"
                                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-600">
                                        Ubah Status
                                    </button>
                                    <a href="{{ route('ks.detail-guru', $guru->id) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Detail</a>

                                    <div x-show="showModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4" style="display:none">
                                        <div @click.away="showModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                                            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Ubah Status Kehadiran</h3>
                                            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $guru->nama_lengkap }} — {{ $guru->nip }}</p>
                                            <form method="POST" action="{{ route('ks.absensi-guru.update-status', $guru->absensi_id) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status Baru</label>
                                                        <select name="status" required
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                            <option value="Sakit">Sakit</option>
                                                            <option value="Izin">Izin</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Alasan <span class="text-error-500">*</span></label>
                                                        <textarea name="alasan" rows="3" required placeholder="Masukkan alasan perubahan status..."
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
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
                            @elseif($canManual && !auth()->user()?->isAkunDemo())
                                <div class="flex items-center gap-2" x-data="{ showManualModal: false }">
                                    <button @click="showManualModal = true"
                                        class="inline-flex items-center justify-center rounded-lg bg-success-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-success-600">
                                        Absen Manual
                                    </button>
                                    <a href="{{ route('ks.detail-guru', $guru->id) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Detail</a>

                                    <div x-show="showManualModal" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4" style="display:none">
                                        <div @click.away="showManualModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                                            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Absen Manual</h3>
                                            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $guru->nama_lengkap }} — {{ $guru->nip }}</p>
                                            <form method="POST" action="{{ route('ks.absensi-guru.manual-checkin', $guru->id) }}">
                                                @csrf
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status Kehadiran</label>
                                                        <select name="status" required
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                            <option value="Hadir">Hadir</option>
                                                            <option value="Sakit">Sakit</option>
                                                            <option value="Izin">Izin / Cuti</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Keterangan (Opsional)</label>
                                                        <textarea name="alasan" rows="3" placeholder="Contoh: Lupa membawa kartu, atau alasan lainnya..."
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <button type="button" @click="showManualModal = false"
                                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                                            Batal
                                                        </button>
                                                        <button type="submit"
                                                            class="bg-success-500 shadow-theme-xs hover:bg-success-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                                                            Simpan Absen
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('ks.detail-guru', $guru->id) }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                                    Detail
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $gurus->links() }}</div>
    </div>
</div>
@endsection
