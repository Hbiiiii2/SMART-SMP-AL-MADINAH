@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <a href="{{ route('ks.dashboard') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-brand-500 hover:underline">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Dashboard
        </a>
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-xl font-bold text-brand-500 dark:bg-brand-500/10">
                {{ strtoupper(substr($guru->nama_lengkap, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $guru->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">NIP: {{ $guru->nip }}</p>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-success-500">{{ ($rekap->get('Hadir', 0) ?? 0) + ($rekap->get('Terlambat', 0) ?? 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Hadir</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-error-500">{{ $rekap->get('Alpha', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Alpha</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-warning-500">{{ $rekap->get('Terlambat', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Terlambat</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-blue-500">{{ $rekap->get('Sakit', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Sakit</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-bold text-purple-500">{{ $rekap->get('Izin', 0) ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Izin</div>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dari</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="dark:bg-dark-900 shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Sampai</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="dark:bg-dark-900 shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            <a href="{{ route('ks.detail-guru.export', ['guru' => $guru->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </a>
        </form>
    </div>

    @php
        $absenWithLocation = $absensis->getCollection()->filter(function ($a) {
            return $a->latitude && $a->longitude && $a->latitude != 0 && $a->longitude != 0;
        })->values();
        $absenJson = $absenWithLocation->map(function ($a) {
            return [
                'tanggal' => \Carbon\Carbon::parse($a->tanggal)->format('d M Y'),
                'lat' => $a->latitude,
                'lng' => $a->longitude,
                'status' => $a->status,
                'jam' => $a->jam_masuk ? \Carbon\Carbon::parse($a->jam_masuk)->format('H:i') : '-',
            ];
        })->toJson();
    @endphp

    @if($absenWithLocation->isNotEmpty())
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Peta Lokasi Absensi</h3>
            <p class="text-xs text-gray-400">{{ $absenWithLocation->count() }} catatan dengan data GPS</p>
        </div>
        <div id="detail-map" style="height: 350px; width: 100%;"></div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var absenData = {!! $absenJson !!};

            if (absenData.length === 0) return;

            var map = L.map('detail-map').setView([absenData[0].lat, absenData[0].lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            var bounds = L.latLngBounds();

            absenData.forEach(function(a) {
                var color = a.status === 'Hadir' ? '#22c55e' : a.status === 'Terlambat' ? '#f59e0b' : '#6b7280';
                var icon = L.divIcon({
                    html: '<div style="background:' + color + ';width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.3)"></div>',
                    iconSize: [12, 12],
                    className: ''
                });

                var marker = L.marker([a.lat, a.lng], {icon: icon}).addTo(map);
                marker.bindPopup(
                    '<div style="font-size:13px">' +
                    '<strong>' + a.tanggal + '</strong><br>' +
                    '<span style="color:' + color + ';font-weight:600">' + a.status + '</span> · ' + a.jam +
                    '</div>'
                );
                bounds.extend([a.lat, a.lng]);
            });

            if (absenData.length > 1) {
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
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Jam Keluar</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Lokasi</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Keterangan</th>
                        <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($absensis as $abs)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($abs->tanggal)->translatedFormat('l, d M Y') }}</td>
                        <td class="px-6 py-3">
                            @php $badge = match($abs->status) { 'Hadir' => 'bg-success-50 text-success-700', 'Terlambat' => 'bg-warning-50 text-warning-700', 'Alpha' => 'bg-error-50 text-error-700', 'Sakit' => 'bg-blue-50 text-blue-700', 'Izin' => 'bg-purple-50 text-purple-700', default => 'bg-gray-50 text-gray-700' }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $abs->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->jam_keluar ? \Carbon\Carbon::parse($abs->jam_keluar)->format('H:i') : '-' }}</td>
                        <td class="px-6 py-3">
                            @if($abs->latitude && $abs->longitude && $abs->latitude != 0 && $abs->longitude != 0)
                                <a href="https://www.google.com/maps?q={{ $abs->latitude }},{{ $abs->longitude }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline dark:text-brand-400">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    {{ number_format($abs->latitude, 4) }}, {{ number_format($abs->longitude, 4) }}
                                </a>
                            @elseif(!is_null($abs->latitude) && $abs->jam_masuk)
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">Manual</span>
                            @else
                                <span class="text-xs text-gray-400 italic">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $abs->keterangan ?? '-' }}</td>
                        <td class="px-6 py-3">
                            @if($abs->status === 'Alpha')
                                <div class="flex items-center gap-2" x-data="{ showModal: false }">
                                    <button @click="showModal = true"
                                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-600">Ubah Status</button>
                                    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" style="display:none">
                                        <div @click.away="showModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                                            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Ubah Status Kehadiran</h3>
                                            <p class="mb-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($abs->tanggal)->translatedFormat('d M Y') }}</p>
                                            <form method="POST" action="{{ route('ks.absensi-guru.update-status', $abs->id) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status Baru</label>
                                                        <select name="status" required class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                            <option value="Sakit">Sakit</option>
                                                            <option value="Izin">Izin</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Alasan <span class="text-error-500">*</span></label>
                                                        <textarea name="alasan" rows="3" required placeholder="Masukkan alasan perubahan status..."
                                                            class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <button type="button" @click="showModal = false" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Batal</button>
                                                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">Simpan</button>
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
                    @empty
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $absensis->links() }}</div>
    </div>
</div>
@endsection
