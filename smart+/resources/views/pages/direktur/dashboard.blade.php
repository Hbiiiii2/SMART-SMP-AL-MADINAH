@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Dashboard Direktur</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitoring Staff Akademik</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('direktur.cuti.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                Persetujuan Cuti
                @if($pengajuanCutiPending > 0)
                <span class="ml-2 flex h-5 w-5 items-center justify-center rounded-full bg-error-500 text-[10px] text-white">{{ $pengajuanCutiPending }}</span>
                @endif
            </a>
        </div>
    </div>

    @php
        $staffKehadiran = $staffHadir + $staffTerlambat;
        $staffPersentase = $totalStaff > 0 ? round(($staffKehadiran / $totalStaff) * 100, 1) : 0;
        
        $guruKehadiran = $guruHadir + $guruTerlambat;
        $guruPersentase = $totalGuru > 0 ? round(($guruKehadiran / $totalGuru) * 100, 1) : 0;
    @endphp

    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Keseluruhan Siswa</p>
                    <h3 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $totalSiswa }}</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10">
                    <svg class="text-blue-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
            </div>
        </div>

        <!-- Pemasukan SPP (Dummy) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pemasukan SPP (Bulan Ini)</p>
                    <h3 class="mt-1 text-2xl font-semibold text-success-600 dark:text-success-500">Rp 45.500.000</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
                    <svg class="text-success-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
            </div>
        </div>

        <!-- Pemasukan Dana BOS (Dummy) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dana BOS (Semester Ini)</p>
                    <h3 class="mt-1 text-2xl font-semibold text-brand-600 dark:text-brand-500">Rp 120.000.000</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10">
                    <svg class="text-brand-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
            </div>
        </div>

        <!-- Pengeluaran Operasional (Dummy) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Operasional (Bulan Ini)</p>
                    <h3 class="mt-1 text-2xl font-semibold text-error-600 dark:text-error-500">Rp 28.300.000</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-error-50 dark:bg-error-500/10">
                    <svg class="text-error-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        
        <!-- Widget Staff Akademik -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Kehadiran Staff Akademik (Hari Ini)</h3>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Staff</p>
                    <h4 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $totalStaff }}</h4>
                </div>
                <div class="rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                    <p class="text-xs font-medium text-brand-600 dark:text-brand-400">% Hadir</p>
                    <div class="mt-1 flex items-baseline gap-1">
                        <h4 class="text-xl font-semibold text-brand-600 dark:text-brand-400">{{ $staffPersentase }}%</h4>
                    </div>
                </div>
                <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-500/10">
                    <p class="text-xs font-medium text-warning-600 dark:text-warning-400">Terlambat</p>
                    <h4 class="mt-1 text-xl font-semibold text-warning-600 dark:text-warning-400">{{ $staffTerlambat }}</h4>
                </div>
                <div class="rounded-xl bg-purple-50 p-4 dark:bg-purple-500/10">
                    <p class="text-xs font-medium text-purple-600 dark:text-purple-400">Cuti</p>
                    <h4 class="mt-1 text-xl font-semibold text-purple-600 dark:text-purple-400">{{ $staffCuti }}</h4>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="mt-4">
                <div class="mb-1 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Progress Kehadiran</span>
                    <span>{{ $staffKehadiran }} / {{ $totalStaff }} Hadir</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $staffPersentase }}%"></div>
                </div>
            </div>
        </div>

        <!-- Widget Guru -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Kehadiran Guru (Hari Ini)</h3>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Guru</p>
                    <h4 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $totalGuru }}</h4>
                </div>
                <div class="rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
                    <p class="text-xs font-medium text-brand-600 dark:text-brand-400">% Hadir</p>
                    <div class="mt-1 flex items-baseline gap-1">
                        <h4 class="text-xl font-semibold text-brand-600 dark:text-brand-400">{{ $guruPersentase }}%</h4>
                    </div>
                </div>
                <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-500/10">
                    <p class="text-xs font-medium text-warning-600 dark:text-warning-400">Terlambat</p>
                    <h4 class="mt-1 text-xl font-semibold text-warning-600 dark:text-warning-400">{{ $guruTerlambat }}</h4>
                </div>
                <div class="rounded-xl bg-purple-50 p-4 dark:bg-purple-500/10">
                    <p class="text-xs font-medium text-purple-600 dark:text-purple-400">Cuti</p>
                    <h4 class="mt-1 text-xl font-semibold text-purple-600 dark:text-purple-400">{{ $guruCuti }}</h4>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="mt-4">
                <div class="mb-1 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Progress Kehadiran</span>
                    <span>{{ $guruKehadiran }} / {{ $totalGuru }} Hadir</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $guruPersentase }}%"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- KPI Target Kehadiran -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">KPI Target Kehadiran Staff (Bulan Ini)</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Target hari masuk efektif bulan {{ \Carbon\Carbon::create()->month($currentMonth)->translatedFormat('F') }} {{ $currentYear }}</p>
            </div>
            <div>
                <a href="{{ route('direktur.kpi.export', ['bulan' => $currentMonth, 'tahun' => $currentYear]) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Semua Excel
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($kpiStaff as $staff)
            <div class="relative flex flex-col rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800/50 dark:bg-gray-800/20">
                <div class="absolute right-3 top-3 flex items-center gap-1">
                    <a href="{{ route('direktur.kpi.export', ['bulan' => $currentMonth, 'tahun' => $currentYear, 'staff_id' => $staff->id]) }}" title="Export Excel" class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-success-500 dark:hover:bg-gray-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </a>
                    @unless(auth()->user()?->isAkunDemo())
                        <button type="button" 
                                x-data 
                                @click="$dispatch('open-kpi-modal', { id: {{ $staff->id }}, nama: '{{ addslashes($staff->nama_lengkap) }}', target: {{ $staff->kpi_target }} })"
                                title="Edit Target"
                                class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-brand-500 dark:hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    @endunless
                </div>
                
                <div class="flex items-center gap-3">
                    @if($staff->foto)
                        <img src="{{ asset('storage/' . $staff->foto) }}" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                    @else
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400 font-bold uppercase">
                            {{ substr($staff->nama_lengkap, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 line-clamp-1">{{ $staff->nama_lengkap }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">NIP: {{ $staff->nip ?? '-' }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Progress Kehadiran</span>
                        <span class="font-bold {{ $staff->kpi_persentase >= 100 ? 'text-success-500' : 'text-brand-500' }}">{{ $staff->kpi_persentase }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full {{ $staff->kpi_persentase >= 100 ? 'bg-success-500' : 'bg-brand-500' }}" style="width: {{ $staff->kpi_persentase }}%"></div>
                    </div>
                    <div class="mt-2 text-right text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $staff->kpi_hadir }}</span> dari 
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $staff->kpi_target }}</span> Hari
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    
    <!-- KPI Target Kehadiran Guru -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">KPI Target Kehadiran Guru (Bulan Ini)</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Target hari masuk efektif bulan {{ \Carbon\Carbon::create()->month($currentMonth)->translatedFormat('F') }} {{ $currentYear }}</p>
            </div>
            <div>
                <!-- Untuk Guru, Direktur mungkin belum punya exportKpiGuru, bisa diarahkan ke fungsi export atau disembunyikan. Asumsi kita belum buat fungsi export Guru untuk direktur, jadi tidak ada tombol export di sini atau arahkan ke KepalaSekolah jika tidak dilarang -->
                <!-- Jika mau, kita hide saja tombol export untuk Direktur (hanya view) atau biarkan -->
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($kpiGuru as $guru)
            <div class="relative flex flex-col rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800/50 dark:bg-gray-800/20">
                <div class="absolute right-3 top-3 flex items-center gap-1">
                    <!-- Hanya view, tidak bisa edit KPI guru untuk role direktur -->
                </div>
                
                <div class="flex items-center gap-3">
                    @if($guru->foto)
                        <img src="{{ asset('storage/' . $guru->foto) }}" alt="Foto" class="h-10 w-10 rounded-full object-cover">
                    @else
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-400 font-bold uppercase">
                            {{ substr($guru->nama_lengkap, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 line-clamp-1">{{ $guru->nama_lengkap }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">NIP: {{ $guru->nip ?? '-' }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Progress Kehadiran</span>
                        <span class="font-bold {{ $guru->kpi_persentase >= 100 ? 'text-success-500' : 'text-brand-500' }}">{{ $guru->kpi_persentase }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full {{ $guru->kpi_persentase >= 100 ? 'bg-success-500' : 'bg-brand-500' }}" style="width: {{ $guru->kpi_persentase }}%"></div>
                    </div>
                    <div class="mt-2 text-right text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $guru->kpi_hadir }}</span> dari 
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $guru->kpi_target }}</span> Hari
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @if(isset($pengaturan) && strtolower($pengaturan->metode_absensi_staff) === 'zonasi')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Peta Sebaran Absensi (Guru, Staff & Kepala Sekolah)</h3>
        
        <div id="direktur-map" class="h-[500px] w-full rounded-xl border border-gray-200 dark:border-gray-700 z-10"></div>
    </div>
    @endif
</div>

@if(isset($pengaturan) && strtolower($pengaturan->metode_absensi_staff) === 'zonasi')
<!-- Leaflet JS for monitoring -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const absensiStaff = @json($absensiHariIni);
        const absensiGuru = @json($absensiGuruHariIni);
        
        if (!document.getElementById('direktur-map')) return;

        let map = L.map('direktur-map').setView([-6.2088, 106.8456], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let markers = [];
        let allAbsensi = [];

        absensiStaff.forEach(abs => {
            let roleStr = (abs.staff && abs.staff.user) ? abs.staff.user.role : 'staff_akademik';
            let pinColor = roleStr === 'kepala_sekolah' ? 'red' : 'blue';
            let roleName = roleStr === 'kepala_sekolah' ? 'Kepala Sekolah' : 'Staff Akademik';
            
            allAbsensi.push({
                ...abs,
                pinColor: pinColor,
                roleName: roleName,
                nama_lengkap: abs.staff ? abs.staff.nama_lengkap : 'Unknown'
            });
        });
        
        absensiGuru.forEach(abs => {
            allAbsensi.push({
                ...abs,
                pinColor: 'green',
                roleName: 'Guru',
                nama_lengkap: abs.guru ? abs.guru.nama_lengkap : 'Unknown'
            });
        });

        allAbsensi.forEach(abs => {
            if (abs.latitude && abs.longitude) {
                let lat = parseFloat(abs.latitude);
                let lng = parseFloat(abs.longitude);
                
                // Mencegah marker menumpuk jika lokasinya persis sama
                let isOverlap = markers.some(m => Math.abs(m[0] - lat) < 0.00005 && Math.abs(m[1] - lng) < 0.00005);
                if (isOverlap) {
                    lat += (Math.random() - 0.5) * 0.0002;
                    lng += (Math.random() - 0.5) * 0.0002;
                }
                
                let marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: abs.pinColor,
                    color: '#fff',
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map);

                let photoHtml = abs.foto_lokasi ? `<br><img src="/storage/${abs.foto_lokasi}" class="mt-2 h-24 w-auto rounded">` : '';
                
                // Guru uses jam_masuk, Staff uses waktu_check_in
                let timeStr = abs.waktu_check_in || abs.jam_masuk;
                let formattedTime = '-';
                if (timeStr) {
                    // Coba parsing sebagai Date (untuk waktu_check_in datetime)
                    let parsedDate = new Date(timeStr);
                    if (!isNaN(parsedDate)) {
                        formattedTime = parsedDate.toLocaleTimeString('id-ID');
                    } else {
                        // Jika berupa string jam saja seperti '07:30:00' (dari jam_masuk cast datetime:H:i)
                        formattedTime = timeStr;
                    }
                }

                marker.bindPopup(`
                    <strong>${abs.nama_lengkap}</strong> (${abs.roleName})<br>
                    Status: ${abs.status}<br>
                    Masuk: ${formattedTime}<br>
                    Lokasi: ${abs.lokasi ? abs.lokasi.nama : '-'}
                    ${photoHtml}
                `);

                markers.push([lat, lng]);
            }
        });

        // Fit bounds to show all markers
        if (markers.length > 0) {
            map.fitBounds(markers, { padding: [50, 50] });
        }
    });
</script>
@endif

<!-- Modal Update KPI -->
<div x-data="{ open: false, id: '', nama: '', target: 0 }"
     @open-kpi-modal.window="open = true; id = $event.detail.id; nama = $event.detail.nama; target = $event.detail.target"
     class="relative z-50"
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true"
     x-show="open"
     x-cloak>
    
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500/75 transition-opacity dark:bg-gray-900/80 backdrop-blur-sm"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                
                <form action="{{ route('direktur.kpi.update-target') }}" method="POST">
                    @csrf
                    <input type="hidden" name="staff_id" x-model="id">
                    <input type="hidden" name="bulan" value="{{ $currentMonth }}">
                    <input type="hidden" name="tahun" value="{{ $currentYear }}">
                    
                    <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-brand-100 sm:mx-0 sm:h-10 sm:w-10 dark:bg-brand-500/20">
                                <svg class="h-6 w-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Update Target KPI Kehadiran</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Atur target hari masuk untuk staff akademik <strong x-text="nama" class="text-gray-700 dark:text-gray-300"></strong> pada bulan ini.</p>
                                    
                                    <div class="mt-4">
                                        <label for="target_hari" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target (Hari)</label>
                                        <input type="number" x-model="target" name="target_hari" id="target_hari" min="0" max="31" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-800/50">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 sm:ml-3 sm:w-auto">Simpan Target</button>
                        <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-700">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
