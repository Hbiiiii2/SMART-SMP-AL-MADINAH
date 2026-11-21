@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10" x-data="{ activeTab: 'jadwal' }">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white/90">
                Halo, {{ explode(' ', $siswa->nama_lengkap)[0] }}! 👋
            </h2>
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">Siap untuk belajar hari ini? Yuk cek jadwalmu!</p>
        </div>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="{{ route('siswa.dashboard') }}">Dashboard /</a></li>
                <li class="text-sm font-medium text-brand-500 dark:text-brand-400">Jadwal</li>
            </ol>
        </nav>
    </div>

    <!-- Student Profile Card with Gradient -->
    <div class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-500 to-indigo-600 p-6 text-white shadow-lg relative">
        <!-- Decorative Shapes -->
        <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white opacity-10"></div>
        <div class="absolute right-12 -bottom-8 h-24 w-24 rounded-full bg-white opacity-10"></div>
        
        <div class="relative z-10 flex items-center gap-5">
            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm shadow-inner">
                <span class="text-3xl">🧑‍🎓</span>
            </div>
            <div>
                <h4 class="text-2xl font-bold">{{ $siswa->nama_lengkap }}</h4>
                <div class="mt-1 flex flex-wrap gap-2 text-sm font-medium text-white/80">
                    <span class="rounded-full bg-white/20 px-3 py-1">Kelas: {{ $siswa->kelas }}</span>
                    <span class="rounded-full bg-white/20 px-3 py-1">NIS: {{ $siswa->nisn ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs with Bubbles -->
    <div class="mb-8 flex flex-wrap gap-4 border-b-2 border-gray-100 pb-4 dark:border-gray-800">
        <button @click="activeTab = 'jadwal'"
            :class="activeTab === 'jadwal' ? 'bg-brand-500 text-white shadow-md -translate-y-1' : 'bg-gray-100 text-gray-600 hover:bg-brand-50 hover:text-brand-500 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.1]'"
            class="flex items-center gap-2 rounded-2xl px-6 py-3 font-bold transition-all duration-300">
            <span>📅</span> Jadwal Pelajaran
        </button>
        <button @click="activeTab = 'tugas'"
            :class="activeTab === 'tugas' ? 'bg-rose-500 text-white shadow-md -translate-y-1' : 'bg-gray-100 text-gray-600 hover:bg-rose-50 hover:text-rose-500 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.1]'"
            class="flex items-center gap-2 rounded-2xl px-6 py-3 font-bold transition-all duration-300 relative">
            <span>📝</span> Tugas Terbaru
            @if(isset($unreadTugasCount) && $unreadTugasCount > 0)
                <span class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-rose-500 text-xs font-bold text-white shadow-md border-2 border-white dark:border-gray-900">
                    {{ $unreadTugasCount }}
                </span>
            @endif
        </button>
        <button @click="activeTab = 'absensi'"
            :class="activeTab === 'absensi' ? 'bg-indigo-500 text-white shadow-md -translate-y-1' : 'bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-500 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.1]'"
            class="flex items-center gap-2 rounded-2xl px-6 py-3 font-bold transition-all duration-300">
            <span>📊</span> Absensi Saya
        </button>
    </div>

    <!-- TAB 1: JADWAL PELAJARAN -->
    <div x-show="activeTab === 'jadwal'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        @php
            // Group and sort the jadwals by day
            $groupedJadwals = $jadwals->groupBy('hari');
            $hariOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            $groupedJadwals = $groupedJadwals->sortBy(function($item, $key) use ($hariOrder) {
                $pos = array_search($key, $hariOrder);
                return $pos === false ? 99 : $pos;
            });
        @endphp

        @forelse($groupedJadwals as $hari => $jadwalsHari)
            <div class="mb-10">
                <div class="mb-5 flex items-center gap-3 border-b-2 border-brand-100 pb-3 dark:border-brand-900/30">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-500 text-white shadow-sm">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">
                        Hari {{ $hari }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($jadwalsHari->sortBy('jam_mulai') as $jadwal)
                    <a href="{{ route('siswa.jadwal.show', $jadwal) }}" class="group relative block overflow-hidden rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-2 hover:shadow-xl active:scale-95 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/50">
                        <!-- Top color bar -->
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-brand-400 to-indigo-500 opacity-0 transition-opacity group-hover:opacity-100"></div>
                        
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-500 transition-colors group-hover:bg-indigo-500 group-hover:text-white dark:bg-indigo-500/10 dark:text-indigo-400">
                                <span class="text-xl">📚</span>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                ⏰ {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                            </span>
                        </div>

                        <div>
                            <h4 class="mb-3 text-lg font-bold text-gray-800 line-clamp-2 group-hover:text-brand-600 dark:text-white/90 dark:group-hover:text-brand-400">
                                {{ $jadwal->mataPelajaran->nama ?? '-' }}
                            </h4>
                            
                            <div class="space-y-2">
                                <div class="flex items-center gap-2.5 text-sm font-medium text-gray-500 dark:text-gray-400">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 dark:bg-white/[0.05]">👨‍🏫</div>
                                    <span class="line-clamp-1">{{ $jadwal->guru->nama_lengkap ?? '-' }}</span>
                                </div>
                                
                                <div class="flex items-center gap-2.5 text-sm font-medium text-gray-500 dark:text-gray-400">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 dark:bg-white/[0.05]">🏫</div>
                                    <span class="line-clamp-1">Ruang: {{ $jadwal->lokasi->nama ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white p-12 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-4 text-6xl">
                    🏝️
                </div>
                <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Belum Ada Jadwal Pelajaran</h4>
                <p class="text-center text-sm font-medium text-gray-500 dark:text-gray-400">Yey! Kamu belum punya jadwal pelajaran untuk tahun ajaran aktif ini. <br>Waktunya bersantai!</p>
            </div>
        @endforelse
    </div>

    <!-- TAB 2: TUGAS TERBARU -->
    <div x-show="activeTab === 'tugas'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
        @forelse($tugasTerbaru as $t)
            @php
                $pengumpulan = $t->pengumpulanTugas->first();
                $status = 'Belum Dikerjakan';
                $statusColor = 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400';
                $btnText = 'Kerjakan ✨';
                $btnClass = 'border-rose-500 text-rose-600 hover:bg-rose-500 hover:text-white dark:border-rose-400 dark:text-rose-400 dark:hover:bg-rose-500 dark:hover:text-white';

                if ($pengumpulan) {
                    if ($pengumpulan->status == 'dinilai') {
                        $status = 'Sudah Dinilai (' . $pengumpulan->nilai . '/100)';
                        $statusColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400';
                        $btnText = 'Lihat Nilai 🏆';
                        $btnClass = 'border-emerald-500 text-emerald-600 hover:bg-emerald-500 hover:text-white dark:border-emerald-400 dark:text-emerald-400 dark:hover:bg-emerald-500 dark:hover:text-white';
                    } else {
                        $status = 'Sudah Dikumpulkan';
                        $statusColor = 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400';
                        $btnText = 'Lihat Tugas 👁️';
                        $btnClass = 'border-amber-500 text-amber-600 hover:bg-amber-500 hover:text-white dark:border-amber-400 dark:text-amber-400 dark:hover:bg-amber-500 dark:hover:text-white';
                    }
                }
            @endphp
            <div class="group flex flex-col md:flex-row md:items-center justify-between gap-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-rose-500/50">
                <div class="flex items-start gap-5">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 text-rose-500 shrink-0 transition-colors group-hover:bg-rose-500 group-hover:text-white dark:bg-rose-500/10 dark:text-rose-400">
                        <span class="text-3xl">📝</span>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-800 group-hover:text-rose-600 dark:text-white/90 dark:group-hover:text-rose-400">{{ $t->judul }}</h4>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-lg bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600 dark:bg-white/[0.05] dark:text-gray-400">{{ $t->mataPelajaran->nama ?? '-' }}</span>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400 line-clamp-1">Oleh: {{ $t->guru->nama_lengkap ?? '-' }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-white/[0.05] rounded-full px-3 py-1">
                                Tenggat: <span class="text-rose-500 dark:text-rose-400">{{ \Carbon\Carbon::parse($t->tenggat_waktu)->translatedFormat('d M Y') }}</span>
                            </p>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusColor }}">
                                {{ $status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-2 md:mt-0 shrink-0 text-right">
                    <a href="{{ route('siswa.tugas.show', $t) }}" class="inline-flex w-full md:w-auto items-center justify-center gap-2 rounded-2xl border-2 bg-transparent py-2.5 px-8 text-center font-bold shadow-sm transition-all active:scale-95 {{ $btnClass }}">
                        {{ $btnText }}
                    </a>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white p-12 shadow-sm dark:border-gray-800 dark:bg-white/[0.03] text-center">
                <div class="mb-4 text-6xl">
                    🎮
                </div>
                <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Belum Ada Tugas Terbaru</h4>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Asik! Belum ada guru yang memberikan tugas untuk kelasmu saat ini.</p>
            </div>
        @endforelse
    </div>

    <!-- TAB 3: ABSENSI SAYA -->
    <div x-show="activeTab === 'absensi'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse($rekapAbsensi as $mapel => $rekap)
                <div x-data="{ open: false }" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm transition-all hover:shadow-lg dark:border-gray-800 dark:bg-white/[0.03]">
                    <div @click="open = !open" class="mb-5 flex items-center justify-between border-b-2 border-gray-50 pb-4 dark:border-gray-800 cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-colors dark:bg-indigo-500/10 dark:text-indigo-400 dark:group-hover:bg-indigo-500 dark:group-hover:text-white">
                                <span class="text-xl">📋</span>
                            </div>
                            <h4 class="font-bold text-gray-800 line-clamp-2 dark:text-white/90 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $rekap['title'] ?? $mapel }}</h4>
                        </div>
                        <button class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-all duration-300 group-hover:bg-indigo-50 group-hover:text-indigo-500 dark:bg-white/[0.05] dark:group-hover:bg-indigo-500/20 dark:group-hover:text-indigo-400" :class="open ? 'rotate-180' : ''">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- Hadir -->
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 dark:bg-emerald-500/10">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">✅ Hadir</span>
                            </div>
                            <span class="font-extrabold text-emerald-700 dark:text-emerald-400">{{ $rekap['Hadir'] }}</span>
                        </div>
                        
                        <!-- Terlambat -->
                        @if($rekap['Terlambat'] > 0)
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-500/10">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-amber-700 dark:text-amber-400">⏰ Terlambat</span>
                            </div>
                            <span class="font-extrabold text-amber-700 dark:text-amber-400">{{ $rekap['Terlambat'] }}</span>
                        </div>
                        @endif

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Izin -->
                            <div class="rounded-xl bg-blue-50 p-3 text-center dark:bg-blue-500/10">
                                <p class="mb-1 text-xs font-bold text-blue-700 dark:text-blue-400">Izin</p>
                                <p class="text-lg font-extrabold text-blue-700 dark:text-blue-400">{{ $rekap['Izin'] }}</p>
                            </div>
                            <!-- Sakit -->
                            <div class="rounded-xl bg-orange-50 p-3 text-center dark:bg-orange-500/10">
                                <p class="mb-1 text-xs font-bold text-orange-700 dark:text-orange-400">Sakit</p>
                                <p class="text-lg font-extrabold text-orange-700 dark:text-orange-400">{{ $rekap['Sakit'] }}</p>
                            </div>
                            <!-- Alfa -->
                            <div class="rounded-xl bg-rose-50 p-3 text-center dark:bg-rose-500/10">
                                <p class="mb-1 text-xs font-bold text-rose-700 dark:text-rose-400">Alfa</p>
                                <p class="text-lg font-extrabold text-rose-700 dark:text-rose-400">{{ $rekap['Alfa'] }}</p>
                            </div>
                        </div>

                        <!-- Progress Bar (Kehadiran) -->
                        @php
                            $totalKehadiran = $rekap['Hadir'] + $rekap['Terlambat'];
                            $persentase = $rekap['Total'] > 0 ? round(($totalKehadiran / $rekap['Total']) * 100) : 0;
                            
                            $progressColor = 'bg-emerald-500';
                            if ($persentase < 70) $progressColor = 'bg-rose-500';
                            elseif ($persentase < 85) $progressColor = 'bg-amber-500';
                        @endphp
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Persentase Kehadiran</span>
                                <span class="text-xs font-extrabold {{ $persentase >= 85 ? 'text-emerald-500' : ($persentase >= 70 ? 'text-amber-500' : 'text-rose-500') }}">{{ $persentase }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-white/[0.05]">
                                <div class="h-2 rounded-full {{ $progressColor }} transition-all duration-1000" style="width: {{ $persentase }}%"></div>
                            </div>
                        </div>

                        <!-- Details Accordion -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800" style="display: none;">
                            <h5 class="mb-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Riwayat Kehadiran</h5>
                            <div class="max-h-52 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                @forelse(collect($rekap['Details'])->sortByDesc('tanggal') as $detail)
                                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 dark:bg-white/[0.02]">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($detail->tanggal)->translatedFormat('d M Y') }}</span>
                                            <span class="text-[10px] font-medium text-gray-500">
                                                Jadwal: {{ substr($detail->jadwal->jam_mulai ?? '00:00', 0, 5) }} - {{ substr($detail->jadwal->jam_selesai ?? '00:00', 0, 5) }}
                                            </span>
                                            @if($detail->jam_masuk)
                                                <span class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                                    Tap Masuk: {{ \Carbon\Carbon::parse($detail->jam_masuk)->format('H:i') }}
                                                </span>
                                            @endif
                                            @if($detail->keterangan && $detail->keterangan !== 'Otomatis mengikuti absen pertama')
                                                <span class="text-[10px] font-medium text-gray-500 italic mt-0.5">
                                                    Alasan: {{ $detail->keterangan }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            @if($detail->status == 'Hadir')
                                                <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">Hadir</span>
                                            @elseif($detail->status == 'Terlambat')
                                                <span class="rounded-lg bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Telat</span>
                                            @elseif($detail->status == 'Izin')
                                                <span class="rounded-lg bg-blue-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:bg-blue-500/20 dark:text-blue-400">Izin</span>
                                            @elseif($detail->status == 'Sakit')
                                                <span class="rounded-lg bg-orange-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-orange-700 dark:bg-orange-500/20 dark:text-orange-400">Sakit</span>
                                            @else
                                                <span class="rounded-lg bg-rose-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:bg-rose-500/20 dark:text-rose-400">Alfa</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500 text-center">Belum ada data.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center rounded-3xl border border-gray-200 bg-white p-12 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mb-4 text-6xl">
                        🤷‍♂️
                    </div>
                    <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Belum Ada Rekap Absensi</h4>
                    <p class="text-center text-sm font-medium text-gray-500 dark:text-gray-400">Data kehadiranmu belum tersedia. Rajin-rajin masuk kelas ya biar absensinya penuh!</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
