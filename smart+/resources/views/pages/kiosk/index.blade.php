<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ $settings->nama_sekolah ?? 'SMART' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] { display: none !important; }
        @keyframes pulse-ring { 0% { transform: scale(.8); opacity: 1; } 100% { transform: scale(2.2); opacity: 0; } }
        .pulse-ring { animation: pulse-ring 1.5s cubic-bezier(.215,.61,.355,1) infinite; }
        @keyframes fadeSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-slide-up { animation: fadeSlideUp 0.35s ease-out; }
        @keyframes checkBounce { 0% { transform: scale(0); } 50% { transform: scale(1.15); } 100% { transform: scale(1); } }
        .check-bounce { animation: checkBounce 0.4s ease-out 0.1s both; }
        @keyframes xShake { 0%,100%{transform:rotate(0)} 25%{transform:rotate(-8deg)} 75%{transform:rotate(8deg)} }
        .x-shake { animation: xShake 0.3s ease-out 0.1s both; }
        .gradient-header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%); }
    </style>
</head>
<body class="bg-gray-50 font-outfit antialiased h-full overflow-hidden" x-data="kioskApp()" x-init="init()" @keydown.window="handleWindowKeydown($event)" @click.window="handleClickWindow($event)">

    {{-- HEADER --}}
    <header class="gradient-header relative z-20 flex items-center justify-between px-8 py-4 text-white shadow-lg">
        <div class="flex items-center gap-4">
            @if(!empty($settings->logo))
                <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo" class="h-11 w-11 rounded-xl object-cover">
            @else
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm font-bold text-lg">S</div>
            @endif
            <div>
                <h1 class="text-xl font-bold">{{ $settings->nama_sekolah ?? 'SMART' }}</h1>
                <p class="text-xs text-white/70">School Management Attendance & Reporting Technologies</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold tabular-nums tracking-tight" x-text="currentTime">--:--:--</div>
            <div class="text-xs text-white/70" x-text="currentDate">--</div>
        </div>
    </header>

    {{-- MAIN --}}
    <main class="flex flex-1 overflow-hidden">

        {{-- LEFT: Stats + Jadwal + Aktivitas --}}
        <div class="flex flex-1 flex-col overflow-y-auto p-6">

            {{-- Stats Cards --}}
            <div class="mb-5 grid grid-cols-5 gap-3">
                <div class="group rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:border-success-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-success-50 dark:bg-success-500/10">
                        <svg class="h-5 w-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="text-3xl font-bold text-success-600 tabular-nums" x-text="stats.hadir">0</div>
                    <div class="mt-0.5 text-[11px] font-medium text-gray-400">Hadir</div>
                </div>
                <div class="group rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:border-warning-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-warning-50 dark:bg-warning-500/10">
                        <svg class="h-5 w-5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="text-3xl font-bold text-warning-600 tabular-nums" x-text="stats.terlambat">0</div>
                    <div class="mt-0.5 text-[11px] font-medium text-gray-400">Terlambat</div>
                </div>
                <div class="group rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:border-error-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10">
                        <svg class="h-5 w-5 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div class="text-3xl font-bold text-error-600 tabular-nums" x-text="stats.alfa">0</div>
                    <div class="mt-0.5 text-[11px] font-medium text-gray-400">Alfa</div>
                </div>
                <div class="group rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:border-blue-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-500/10">
                        <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="text-3xl font-bold text-blue-600 tabular-nums" x-text="stats.sakit">0</div>
                    <div class="mt-0.5 text-[11px] font-medium text-gray-400">Sakit</div>
                </div>
                <div class="group rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:border-purple-300 hover:shadow-md dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-purple-50 dark:bg-purple-500/10">
                        <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="text-3xl font-bold text-purple-600 tabular-nums" x-text="stats.izin">0</div>
                    <div class="mt-0.5 text-[11px] font-medium text-gray-400">Izin</div>
                </div>
            </div>

            {{-- Jadwal Aktif --}}
            @if($jadwalAktifs->isNotEmpty())
            <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-3 flex items-center gap-2">
                    <div class="h-1.5 w-1.5 rounded-full bg-success-500 animate-pulse"></div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jadwal Aktif Saat Ini</h3>
                </div>
                <div class="space-y-2">
                    @foreach($jadwalAktifs as $jadwal)
                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-2.5 dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-7 items-center rounded-md bg-brand-50 px-2 text-[11px] font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $jadwal->kelas }}</span>
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $jadwal->mataPelajaran->nama ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            <span>{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</span>
                            <span class="text-gray-300">|</span>
                            <span>{{ $jadwal->guru->nama_lengkap ?? '-' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Aktivitas Terakhir --}}
            <div class="flex-1 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aktivitas Terakhir</h3>
                    <span class="text-[10px] text-gray-400" x-text="recentActivity.length + ' catatan'"></span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800/50 overflow-y-auto" style="max-height: calc(100vh - 340px);">
                    <template x-for="(act, idx) in recentActivity" :key="idx">
                        <div class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/[0.02]"
                            :class="act.status === 'Terlambat' ? 'border-l-2 border-l-warning-400' : act.type === 'keluar' ? 'border-l-2 border-l-gray-300' : 'border-l-2 border-l-success-400'">
                            <template x-if="act.foto">
                                <img :src="act.foto" class="h-9 w-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-900" />
                            </template>
                            <template x-if="!act.foto">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full text-[10px] font-bold ring-2 ring-white dark:ring-gray-900"
                                    :class="act.role === 'guru' ? 'bg-blue-100 text-blue-600' : 'bg-brand-100 text-brand-600'" x-text="act.initials"></div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-white/90 truncate" x-text="act.nama"></span>
                                    <span class="inline-flex rounded px-1 text-[9px] font-bold uppercase leading-tight"
                                        :class="act.role === 'guru' ? 'bg-blue-50 text-blue-600' : 'bg-brand-50 text-brand-600'" x-text="act.role"></span>
                                </div>
                                <div class="text-[11px] text-gray-400 truncate">
                                    <span x-text="act.kelas || ''"></span>
                                    <span x-show="act.mata_pelajaran"> · </span>
                                    <span x-text="act.mata_pelajaran || ''"></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-[11px] font-bold"
                                    :class="act.status === 'Terlambat' ? 'text-warning-500' : act.type === 'keluar' ? 'text-gray-400' : 'text-success-500'"
                                    x-text="act.type === 'keluar' ? 'OUT' : act.status"></div>
                                <div class="text-[10px] text-gray-400 tabular-nums" x-text="act.jam_masuk || act.jam_keluar || '-'"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="recentActivity.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-300">
                        <svg class="mb-2 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs">Belum ada aktivitas hari ini</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: RFID Panel --}}
        <aside class="hidden w-72 flex-col border-l border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:flex">
            {{-- Scan indicator --}}
            <div class="mb-5 text-center">
                <div class="relative mx-auto mb-4 h-28 w-28">
                    <div class="absolute inset-0 rounded-full bg-brand-500/10 pulse-ring"></div>
                    <div class="relative flex h-28 w-28 items-center justify-center rounded-full bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-500/10 dark:to-brand-500/5">
                        <svg class="h-14 w-14 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <rect x="2" y="5" width="20" height="14" rx="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Tap Kartu RFID</h3>
                <p class="text-[11px] text-gray-400">Tempelkan kartu pada reader</p>
            </div>

            {{-- UID Input --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-gray-400">UID Manual</label>
                <div class="flex gap-1.5">
                    <input type="text" x-ref="uidInput" x-model="manualUid" @keydown.enter="scanRfid()" placeholder="Masukkan UID..." autofocus
                        class="h-10 flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 placeholder:text-gray-300 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white/90" />
                    <button @click="scanRfid()" :disabled="scanning"
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-500 text-white transition hover:bg-brand-600 disabled:opacity-40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                    </button>
                </div>
            </div>

            {{-- Scan count today --}}
            <div class="mb-4 rounded-lg bg-gray-50 p-3 text-center dark:bg-gray-800">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Total Scan Hari Ini</div>
                <div class="mt-1 text-2xl font-bold text-gray-800 dark:text-white tabular-nums" x-text="stats.total">0</div>
            </div>

            <div class="mt-auto text-center text-[10px] text-gray-300">SMART v1.2 — Attendance Kiosk</div>
        </aside>
    </main>

    {{-- OVERLAY: Hasil Scan --}}
    <div x-show="showOverlay" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm" style="display:none"
        :class="overlayResult?.success ? 'bg-success-500/20' : 'bg-error-500/20'"
        @click="closeOverlay()">
        <div class="fade-slide-up flex w-full max-w-md flex-col items-center rounded-2xl bg-white p-8 text-center shadow-2xl dark:bg-gray-900" @click.stop>
            <div class="mb-4">
                <template x-if="overlayResult?.success">
                    <div class="check-bounce inline-flex h-16 w-16 items-center justify-center rounded-full"
                        :class="overlayResult?.status === 'Terlambat' ? 'bg-warning-100 dark:bg-warning-500/20' : 'bg-success-100 dark:bg-success-500/20'">
                        <svg class="h-8 w-8" :class="overlayResult?.status === 'Terlambat' ? 'text-warning-500' : 'text-success-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </template>
                <template x-if="!overlayResult?.success">
                    <div class="x-shake inline-flex h-16 w-16 items-center justify-center rounded-full bg-error-100 dark:bg-error-500/20">
                        <svg class="h-8 w-8 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                </template>
            </div>
            <div class="mb-3" x-show="overlayResult?.foto">
                <img :src="overlayResult?.foto" class="mx-auto h-20 w-20 rounded-full border-2 object-cover shadow"
                    :class="overlayResult?.success ? 'border-success-300' : 'border-error-300'" />
            </div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white" x-text="overlayResult?.nama || overlayResult?.message"></h2>
            <div x-show="overlayResult?.nama" class="mt-1 flex items-center justify-center gap-1.5">
                <span class="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold text-brand-600 dark:bg-brand-500/10" x-text="overlayResult?.role === 'guru' ? 'GURU' : 'SISWA'"></span>
                <span class="text-xs text-gray-400" x-text="overlayResult?.nisn || overlayResult?.nip || ''"></span>
                <span x-show="overlayResult?.kelas" class="text-xs text-gray-400" x-text="'· ' + overlayResult?.kelas"></span>
            </div>
            <div x-show="overlayResult?.success" class="mt-3">
                <span class="inline-flex items-center gap-1 rounded-full px-4 py-1.5 text-sm font-bold"
                    :class="overlayResult?.status === 'Terlambat' ? 'bg-warning-100 text-warning-700' : 'bg-success-100 text-success-700'">
                    <span x-text="overlayResult?.type === 'keluar' ? 'KELUAR' : overlayResult?.status"></span>
                </span>
            </div>
            <div x-show="overlayResult?.mata_pelajaran" class="mt-2 text-xs text-gray-400" x-text="overlayResult?.mata_pelajaran + (overlayResult?.jam ? ' · ' + overlayResult?.jam : '')"></div>
            <div x-show="!overlayResult?.success && overlayResult?.nama" class="mt-3 rounded-lg bg-error-50 px-3 py-2 text-xs text-error-600 dark:bg-error-500/10" x-text="overlayResult?.message"></div>
            <p class="mt-4 text-[10px] text-gray-300">Klik di mana saja untuk menutup</p>
        </div>
    </div>

    <script>
    function kioskApp() {
        return {
            stats: @json($stats),
            recentActivity: @json($recentActivities),
            currentTime: '',
            currentDate: '',
            manualUid: '',
            scanning: false,
            showOverlay: false,
            overlayResult: null,

            playSound(type) {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!this.audioCtx) {
                        this.audioCtx = new AudioContext();
                    }
                    if (this.audioCtx.state === 'suspended') {
                        this.audioCtx.resume();
                    }

                    const oscillator = this.audioCtx.createOscillator();
                    const gainNode = this.audioCtx.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(this.audioCtx.destination);

                    if (type === 'success') {
                        // Suara Ding/Bell (Nada tinggi, halus)
                        oscillator.type = 'sine';
                        oscillator.frequency.setValueAtTime(880, this.audioCtx.currentTime); // Nada A5
                        gainNode.gain.setValueAtTime(0, this.audioCtx.currentTime);
                        gainNode.gain.linearRampToValueAtTime(1, this.audioCtx.currentTime + 0.05);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.5);
                        oscillator.start(this.audioCtx.currentTime);
                        oscillator.stop(this.audioCtx.currentTime + 0.5);
                    } else {
                        // Suara Buzzer/Error (Nada rendah, kasar)
                        oscillator.type = 'sawtooth';
                        oscillator.frequency.setValueAtTime(150, this.audioCtx.currentTime);
                        gainNode.gain.setValueAtTime(0, this.audioCtx.currentTime);
                        gainNode.gain.linearRampToValueAtTime(1, this.audioCtx.currentTime + 0.05);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.5);
                        oscillator.start(this.audioCtx.currentTime);
                        oscillator.stop(this.audioCtx.currentTime + 0.5);
                    }
                } catch(e) {
                    console.log('Audio blocked or not supported:', e);
                }
            },

            focusInput() {
                this.$nextTick(() => {
                    if (this.$refs.uidInput) {
                        this.$refs.uidInput.focus();
                    }
                });
            },

            closeOverlay() {
                this.showOverlay = false;
                this.focusInput();
            },

            handleWindowKeydown(event) {
                if (this.showOverlay || this.scanning) return;

                if (['Tab', 'Escape', 'Control', 'Alt', 'Meta', 'Shift', 'CapsLock', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) {
                    return;
                }

                const activeEl = document.activeElement;
                if (activeEl !== this.$refs.uidInput && !['INPUT', 'TEXTAREA'].includes(activeEl?.tagName)) {
                    if (this.$refs.uidInput) {
                        this.$refs.uidInput.focus();
                    }
                }
            },

            handleClickWindow(event) {
                if (this.showOverlay || this.scanning) return;
                const clickedTag = event.target.tagName;
                if (!['BUTTON', 'A', 'INPUT', 'SELECT', 'TEXTAREA'].includes(clickedTag) && !event.target.closest('button, a, input, select, textarea')) {
                    this.focusInput();
                }
            },

            init() {
                this.updateClock();
                this.focusInput();
                setInterval(() => this.updateClock(), 1000);
                setInterval(() => this.refreshStats(), 10000);
                setInterval(() => this.refreshRecent(), 15000);
            },

            updateClock() {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('id-ID', { hour12: false });
                this.currentDate = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            },

            async refreshStats() {
                try {
                    const res = await fetch('{{ route("kiosk.stats") }}', { headers: { 'Accept': 'application/json' } });
                    if (res.ok) this.stats = await res.json();
                } catch (e) {}
            },

            async refreshRecent() {
                try {
                    const res = await fetch('{{ route("kiosk.recent") }}', { headers: { 'Accept': 'application/json' } });
                    if (res.ok) this.recentActivity = await res.json();
                } catch (e) {}
            },

            async scanRfid() {
                const uid = this.manualUid.trim();
                if (!uid || this.scanning) return;
                this.scanning = true;

                // Tampilkan loading SweetAlert
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang memvalidasi kartu RFID',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const scanUrl = window.location.origin + '/api/rfid/scan';
                    const response = await fetch(scanUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ uid: uid }),
                    });

                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        data = { success: false, message: 'Respon server tidak valid (' + response.status + ').' };
                    }

                    if (response.status === 404) {
                        data = { success: false, message: 'Layanan scanner belum siap (404). Silakan coba kembali.' };
                    }

                    this.overlayResult = data;
                    
                    Swal.close(); // Tutup loading alert
                    this.showOverlay = true;

                    if (this.overlayResult.success) {
                        this.playSound('success');
                        this.recentActivity.unshift({
                            nama: this.overlayResult.nama,
                            kelas: this.overlayResult.kelas,
                            mata_pelajaran: this.overlayResult.mata_pelajaran,
                            status: this.overlayResult.status,
                            jam_masuk: this.overlayResult.jam,
                            jam_keluar: null,
                            type: this.overlayResult.type,
                            foto: this.overlayResult.foto,
                            initials: this.overlayResult.initials,
                            role: this.overlayResult.role,
                        });
                        if (this.recentActivity.length > 30) this.recentActivity = this.recentActivity.slice(0, 30);
                        this.refreshStats();
                    } else {
                        this.playSound('error');
                    }

                    this.manualUid = '';
                    setTimeout(() => { this.closeOverlay(); }, 4000);
                } catch (error) {
                    Swal.close(); // Tutup loading alert jika error
                    this.playSound('error');
                    this.overlayResult = { success: false, message: 'Gagal menghubungi server.' };
                    this.showOverlay = true;
                    setTimeout(() => { this.closeOverlay(); }, 3000);
                } finally {
                    this.scanning = false;
                }
            }
        };
    }
    </script>
</body>
</html>
