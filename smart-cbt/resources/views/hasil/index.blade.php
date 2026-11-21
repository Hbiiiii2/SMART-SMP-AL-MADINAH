@php
    $isGuru = auth()->user()->isGuru();
    $baseRoute = $isGuru ? 'guru.hasil.index' : 'hasil.index';
    $exportRoute = $isGuru ? 'guru.hasil.export.filter' : 'hasil.export.filter';
    $showRoute = $isGuru ? 'guru.hasil.show' : 'hasil.show';

    $activeTab = request('tab') === 'ujian' ? 'ujian' : 'peserta';
    $hasMoreFilter = request()->filled('tahun_ajaran')
        || request()->filled('semester')
        || request()->filled('start_date')
        || request()->filled('end_date')
        || request()->filled('sort');

    $activeFilterCount = collect([
        'exam_id',
        'search_siswa',
        'search_ujian',
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
        'status',
        'tahun_ajaran',
        'semester',
        'start_date',
        'end_date',
        'sort',
    ])->filter(fn ($key) => request()->filled($key))->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Hasil Ujian</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Cari dan saring hasil peserta, atau cek paket ujian mana yang sudah pernah diikuti siswa.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ tab: '{{ $activeTab }}', showMore: {{ $hasMoreFilter ? 'true' : 'false' }} }">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- KPI Stats --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Peserta</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($totalPeserta) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Attempt sesuai filter</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ujian Selesai</p>
                    <p class="mt-2 text-2xl font-black text-emerald-600">{{ number_format($totalSelesai) }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        @if (($totalMenungguNilai ?? 0) > 0)
                            <span class="font-semibold text-amber-600">{{ $totalMenungguNilai }} menunggu nilai essay</span>
                        @else
                            Status selesai / waktu habis
                        @endif
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Nilai</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ $rataRata !== null ? number_format($rataRata, 2) : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Dari nilai tersedia</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Nilai Tertinggi</p>
                    <p class="mt-2 text-2xl font-black text-blue-600">{{ $nilaiTertinggi !== null ? number_format($nilaiTertinggi, 2) : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Dari nilai tersedia</p>
                </div>
            </div>

            {{-- Mode Switcher Tabs --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        @click="tab = 'peserta'"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="tab === 'peserta' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>Hasil Per Siswa</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px]" :class="tab === 'peserta' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
                            {{ number_format($attempts->total()) }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="tab = 'ujian'"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="tab === 'ujian' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>Status Pelaksanaan Per Ujian</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px]" :class="tab === 'ujian' ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-700'">
                            {{ $examExecutionSummary['sudah_pernah_ujian'] ?? 0 }}/{{ $examExecutionSummary['total'] ?? 0 }} pernah ujian
                        </span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2 px-2 text-xs text-slate-500">
                    <span>Sudah ada soal: <strong class="text-blue-700">{{ $examExecutionSummary['sudah_ada_soal'] ?? 0 }}</strong></span>
                    <span>&bull;</span>
                    <span>Sudah pernah ujian: <strong class="text-emerald-700">{{ $examExecutionSummary['sudah_pernah_ujian'] ?? 0 }}</strong></span>
                    <span>&bull;</span>
                    <span>Belum pernah ujian: <strong class="text-amber-700">{{ $examExecutionSummary['belum_pernah_ujian'] ?? 0 }}</strong></span>
                </div>
            </div>

            {{-- =========================================================
                TAB 1: HASIL PER SISWA
            ========================================================== --}}
            <div x-show="tab === 'peserta'" x-cloak class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route($exportRoute, request()->except('tab', 'exam_page')) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export ke Excel
                        </a>
                        <p class="max-w-lg text-xs leading-relaxed text-slate-500">
                            Data yang di-export akan <b>persis sama</b> dengan data yang tampil di tabel bawah sesuai filter aktif.
                        </p>
                    </div>
                </div>

                {{-- Filter Panel Hasil Siswa --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form method="GET" action="{{ route($baseRoute) }}" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            {{-- Searchable Exam Picker --}}
                            <div>
                                <label for="hasil_exam_id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Pilih / Cari Paket Ujian
                                </label>
                                <x-searchable-exam-select
                                    id="hasil_exam_id"
                                    name="exam_id"
                                    :exams="$examPickerItems"
                                    :selected="request('exam_id')"
                                    placeholder="Semua Ujian ({{ count($examPickerItems) }})"
                                />
                            </div>

                            {{-- Cari Siswa --}}
                            <div>
                                <label for="search_siswa" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Cari Siswa (Nama / NIS / NISN)
                                </label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                    </svg>
                                    <input id="search_siswa" name="search_siswa" value="{{ request('search_siswa') }}" type="search"
                                           placeholder="Ketik nama siswa, NIS, atau NISN..."
                                           class="w-full rounded-lg border-slate-300 pl-9 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            {{-- Cari Ujian (kata kunci) --}}
                            <div>
                                <label for="search_ujian" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Kata Kunci Ujian (Nama / Kode)
                                </label>
                                <input id="search_ujian" name="search_ujian" value="{{ request('search_ujian') }}" type="search"
                                       placeholder="Misal: UAS, Matematika, kode ujian..."
                                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $isGuru ? 'lg:grid-cols-3' : 'lg:grid-cols-4' }}">
                            <div>
                                <label for="kelas_id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Kelas</label>
                                <select id="kelas_id" name="kelas_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Kelas</option>
                                    @foreach ($kelasOptions as $kelas)
                                        <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="mata_pelajaran_id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Mata Pelajaran</label>
                                <select id="mata_pelajaran_id" name="mata_pelajaran_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Mata Pelajaran</option>
                                    @foreach ($mataPelajaranOptions as $mataPelajaran)
                                        <option value="{{ $mataPelajaran->id }}" @selected((string) request('mata_pelajaran_id') === (string) $mataPelajaran->id)>{{ $mataPelajaran->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if (! $isGuru)
                                <div>
                                    <label for="guru_id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Guru</label>
                                    <select id="guru_id" name="guru_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Semua Guru</option>
                                        @foreach ($guruOptions as $guru)
                                            <option value="{{ $guru->id }}" @selected((string) request('guru_id') === (string) $guru->id)>{{ $guru->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div>
                                <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Status Pengerjaan</label>
                                <select id="status" name="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Status</option>
                                    @foreach ([
                                        'finished' => 'Semua Selesai (Submit/Dinilai/Habis)',
                                        'submitted' => 'Selesai (PG)',
                                        'completed' => 'Selesai & Dinilai',
                                        'needs_grading' => 'Menunggu Penilaian Essay',
                                        'in_progress' => 'Sedang Berlangsung',
                                        'expired' => 'Waktu Habis',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" @click="showMore = !showMore" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 focus:outline-none">
                                    <span x-text="showMore ? 'Sembunyikan Periode, Tanggal & Urutan' : 'Tampilkan Periode, Tanggal & Urutan'"></span>
                                    <svg :class="showMore ? 'rotate-180' : ''" class="h-4 w-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                @if ($activeFilterCount > 0)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold text-blue-700">
                                        {{ $activeFilterCount }} filter aktif
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route($baseRoute) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    Reset
                                </a>
                                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>

                        <div x-show="showMore" x-cloak x-transition class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 md:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <label for="tahun_ajaran" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Tahun Ajaran</label>
                                <select id="tahun_ajaran" name="tahun_ajaran" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Tahun Ajaran</option>
                                    @foreach ($tahunAjaranOptions as $tahunAjaran)
                                        <option value="{{ $tahunAjaran }}" @selected(request('tahun_ajaran') === $tahunAjaran)>{{ $tahunAjaran }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="semester" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Semester</label>
                                <select id="semester" name="semester" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Semester</option>
                                    <option value="ganjil" @selected(request('semester') === 'ganjil')>Ganjil</option>
                                    <option value="genap" @selected(request('semester') === 'genap')>Genap</option>
                                </select>
                            </div>

                            <div>
                                <label for="start_date" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Mulai Tanggal</label>
                                <input id="start_date" name="start_date" value="{{ request('start_date') }}" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="end_date" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai Tanggal</label>
                                <input id="end_date" name="end_date" value="{{ request('end_date') }}" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="hasil_sort" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Urutkan</label>
                                <select id="hasil_sort" name="sort" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="latest" @selected(request('sort', 'latest') === 'latest')>Terbaru</option>
                                    <option value="nilai_desc" @selected(request('sort') === 'nilai_desc')>Nilai Tertinggi</option>
                                    <option value="nilai_asc" @selected(request('sort') === 'nilai_asc')>Nilai Terendah</option>
                                    <option value="nama_asc" @selected(request('sort') === 'nama_asc')>Nama Siswa (A–Z)</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Tabel Hasil Siswa --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900">Daftar Hasil Peserta</h3>
                            <p class="mt-0.5 text-xs text-slate-500">{{ number_format($attempts->total()) }} hasil ditemukan.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Siswa</th>
                                    <th class="px-5 py-3">Kelas</th>
                                    <th class="px-5 py-3">Ujian</th>
                                    <th class="px-5 py-3">Mata Pelajaran</th>
                                    <th class="px-5 py-3">Periode</th>
                                    <th class="px-5 py-3 text-center">Nilai</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Waktu</th>
                                    <th class="px-5 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($attempts as $attempt)
                                    @php
                                        $statusLabel = [
                                            'in_progress' => 'Sedang Berlangsung',
                                            'submitted' => 'Selesai',
                                            'completed' => 'Selesai Dinilai',
                                            'needs_grading' => 'Perlu Nilai Essay',
                                            'expired' => 'Waktu Habis',
                                        ][$attempt->status] ?? ucfirst(str_replace('_', ' ', (string) $attempt->status));

                                        $statusClass = [
                                            'submitted' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
                                            'completed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
                                            'needs_grading' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200',
                                            'expired' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
                                            'in_progress' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
                                        ][$attempt->status] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <p class="font-semibold text-slate-900">{{ $attempt->siswa?->nama ?? '-' }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $attempt->siswa?->nisn ?: $attempt->siswa?->nis ?: '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                            {{ $attempt->siswa?->kelasData?->nama_kelas ?? $attempt->exam?->kelasData?->nama_kelas ?? $attempt->exam?->kelas ?? '-' }}
                                        </td>
                                        <td class="min-w-48 px-5 py-4">
                                            <p class="font-medium text-slate-800">{{ $attempt->exam?->nama ?? '-' }}</p>
                                            <p class="font-mono text-[11px] text-slate-400">{{ $attempt->exam?->kode_ujian ?? '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $attempt->exam?->mataPelajaran?->nama ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                            <div>{{ $attempt->exam?->tahun_ajaran ?? '-' }}</div>
                                            <div class="text-xs text-slate-400">{{ $attempt->exam?->semester ? ucfirst($attempt->exam->semester) : '-' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center text-lg font-black text-slate-900">
                                            {{ $attempt->nilai_akhir !== null ? number_format($attempt->nilai_akhir, 2) : '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">
                                            <div>Mulai: {{ $attempt->started_at ? $attempt->started_at->format('d M H:i') : '-' }}</div>
                                            <div>Selesai: {{ $attempt->submitted_at ? $attempt->submitted_at->format('d M H:i') : '-' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center">
                                            <a href="{{ route($showRoute, $attempt->id) }}"
                                               class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                                {{ $attempt->status === 'needs_grading' ? 'Nilai Essay' : 'Detail' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-5 py-12 text-center text-slate-500">
                                            <p class="text-sm font-medium text-slate-700">Tidak ada hasil ujian yang sesuai dengan kriteria filter.</p>
                                            @if ($activeFilterCount > 0)
                                                <a href="{{ route($baseRoute) }}" class="mt-2 inline-flex text-xs font-semibold text-blue-600 hover:underline">
                                                    Reset Filter
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($attempts->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4">
                            {{ $attempts->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- =========================================================
                TAB 2: STATUS PELAKSANAAN PER UJIAN
            ========================================================== --}}
            <div x-show="tab === 'ujian'" x-cloak class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form method="GET" action="{{ route($baseRoute) }}" class="space-y-4">
                        <input type="hidden" name="tab" value="ujian">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                            <div class="lg:col-span-2">
                                <label for="summary_search_ujian" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Cari Paket Ujian
                                </label>
                                <input id="summary_search_ujian" name="search_ujian" value="{{ request('search_ujian') }}" type="search"
                                       placeholder="Nama ujian, kode ujian, mapel..."
                                       class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="summary_pelaksanaan" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status Pelaksanaan
                                </label>
                                <select id="summary_pelaksanaan" name="pelaksanaan" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua</option>
                                    <option value="sudah_ujian" @selected(request('pelaksanaan') === 'sudah_ujian')>Sudah Pernah Ujian</option>
                                    <option value="belum_ujian" @selected(request('pelaksanaan') === 'belum_ujian')>Belum Pernah Ujian</option>
                                </select>
                            </div>

                            <div>
                                <label for="summary_soal_status" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Kesiapan Soal
                                </label>
                                <select id="summary_soal_status" name="soal_status" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua</option>
                                    <option value="sudah_ada" @selected(request('soal_status') === 'sudah_ada')>Sudah Ada Soal</option>
                                    <option value="belum_ada" @selected(request('soal_status') === 'belum_ada')>Belum Ada Soal</option>
                                </select>
                            </div>

                            <div>
                                <label for="summary_mapel" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Mata Pelajaran
                                </label>
                                <select id="summary_mapel" name="mata_pelajaran_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Mapel</option>
                                    @foreach ($mataPelajaranOptions as $mataPelajaran)
                                        <option value="{{ $mataPelajaran->id }}" @selected((string) request('mata_pelajaran_id') === (string) $mataPelajaran->id)>{{ $mataPelajaran->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="summary_kelas" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Kelas
                                </label>
                                <select id="summary_kelas" name="kelas_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Kelas</option>
                                    @foreach ($kelasOptions as $kelas)
                                        <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
                            <a href="{{ route($baseRoute, ['tab' => 'ujian']) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Reset
                            </a>
                            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-xs font-bold text-white hover:bg-slate-800">
                                Filter Ujian
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="font-bold text-slate-900">Ringkasan Pelaksanaan &amp; Kesiapan Per Paket Ujian</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Lihat paket ujian mana yang sudah dibuat soalnya dan mana yang sudah pernah diikuti peserta.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Paket Ujian</th>
                                    <th class="px-5 py-3">Mapel &amp; Kelas</th>
                                    <th class="px-5 py-3">Guru PIC</th>
                                    <th class="px-5 py-3">Kesiapan &amp; Status Pelaksanaan</th>
                                    <th class="px-5 py-3 text-center">Rata-rata Nilai</th>
                                    <th class="px-5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($examSummaries as $ex)
                                    <tr class="align-top hover:bg-slate-50">
                                        <td class="min-w-56 px-5 py-4">
                                            <p class="font-bold text-slate-900">{{ $ex->nama }}</p>
                                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">
                                                {{ $ex->kode_ujian ?? '-' }}
                                                @if ($ex->tahun_ajaran)
                                                    &bull; {{ $ex->tahun_ajaran }} {{ $ex->semester ? ucfirst($ex->semester) : '' }}
                                                @endif
                                            </p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <p class="font-medium text-slate-800">{{ $ex->mataPelajaran?->nama ?? '-' }}</p>
                                            <p class="text-xs text-slate-500">Kelas {{ $ex->kelasData?->nama_kelas ?? $ex->kelas ?? '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">
                                            {{ $ex->guru?->nama ?? '-' }}
                                        </td>
                                        <td class="min-w-64 px-5 py-4">
                                            <x-exam-readiness-badges :exam="$ex" />
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-center font-black text-slate-900">
                                            {{ $ex->exam_attempts_avg_nilai_akhir !== null ? number_format((float) $ex->exam_attempts_avg_nilai_akhir, 2) : '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-right">
                                            @if (($ex->exam_attempts_count ?? 0) > 0)
                                                <a href="{{ route($baseRoute, ['exam_id' => $ex->id]) }}"
                                                   class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700">
                                                    Lihat Hasil ({{ $ex->exam_attempts_count }})
                                                </a>
                                            @else
                                                <a href="{{ route($isGuru ? 'guru.ujian.show' : 'ujian.show', $ex) }}"
                                                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    Detail Ujian
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                            Tidak ada paket ujian yang sesuai filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($examSummaries->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4">
                            {{ $examSummaries->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
