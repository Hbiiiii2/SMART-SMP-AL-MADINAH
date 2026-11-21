@props([
    'action',
    'resetUrl',
    'filterOptions' => [],
    'showGuruFilter' => false,
    'showPeriodAndSort' => true,
    'searchPlaceholder' => 'Cari nama ujian, kode ujian, mapel, kelas, atau ruangan...',
])

@php
    $mataPelajarans = $filterOptions['mataPelajarans'] ?? collect();
    $kelasData = $filterOptions['kelasData'] ?? collect();
    $gurus = $filterOptions['gurus'] ?? collect();
    $tahunAjarans = $filterOptions['tahunAjarans'] ?? collect();

    $activeFilterCount = collect([
        'q',
        'mata_pelajaran_id',
        'kelas_id',
        'guru_id',
        'status',
        'soal_status',
        'pelaksanaan',
        'tahun_ajaran',
        'semester',
        'sort',
    ])->filter(fn ($key) => request()->filled($key))->count();

    $hasPeriodFilter = request()->filled('tahun_ajaran') || request()->filled('semester') || request()->filled('sort');
@endphp

<div x-data="{ showMore: {{ $hasPeriodFilter ? 'true' : 'false' }} }" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
    <form method="GET" action="{{ $action }}" class="space-y-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-12">
            {{-- Search Keyword --}}
            <div class="lg:col-span-4">
                <label for="filter_q" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Cari Ujian
                </label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input
                        id="filter_q"
                        name="q"
                        type="search"
                        value="{{ request('q') }}"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full rounded-lg border-slate-300 pl-9 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>

            {{-- Mata Pelajaran --}}
            <div class="lg:col-span-2">
                <label for="filter_mapel" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Mata Pelajaran
                </label>
                <select id="filter_mapel" name="mata_pelajaran_id" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Mapel</option>
                    @foreach ($mataPelajarans as $mapel)
                        <option value="{{ $mapel->id }}" @selected((string) request('mata_pelajaran_id') === (string) $mapel->id)>
                            {{ $mapel->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Kelas --}}
            <div class="lg:col-span-2">
                <label for="filter_kelas" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Kelas
                </label>
                <select id="filter_kelas" name="kelas_id" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasData as $kelas)
                        <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>
                            {{ $kelas->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Kesiapan Soal --}}
            <div class="lg:col-span-2">
                <label for="filter_soal_status" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Kesiapan Soal
                </label>
                <select id="filter_soal_status" name="soal_status" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Soal</option>
                    <option value="sudah_ada" @selected(request('soal_status') === 'sudah_ada')>Sudah Ada Soal</option>
                    <option value="belum_ada" @selected(request('soal_status') === 'belum_ada')>Belum Ada Soal</option>
                </select>
            </div>

            {{-- Riwayat Pelaksanaan --}}
            <div class="lg:col-span-2">
                <label for="filter_pelaksanaan" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Riwayat Ujian
                </label>
                <select id="filter_pelaksanaan" name="pelaksanaan" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Riwayat</option>
                    <option value="sudah_ujian" @selected(request('pelaksanaan') === 'sudah_ujian')>Sudah Pernah Ujian</option>
                    <option value="belum_ujian" @selected(request('pelaksanaan') === 'belum_ujian')>Belum Pernah Ujian</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-12 items-end">
            {{-- Status Paket --}}
            <div class="{{ $showGuruFilter ? 'lg:col-span-3' : 'lg:col-span-4' }}">
                <label for="filter_status" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Status Ujian
                </label>
                <select id="filter_status" name="status" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft (Belum Terbit)</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif (Semua)</option>
                    <option value="berlangsung" @selected(request('status') === 'berlangsung')>Aktif (Sedang Berjalan)</option>
                    <option value="terjadwal" @selected(request('status') === 'terjadwal')>Terjadwal (Belum Mulai)</option>
                    <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                </select>
            </div>

            {{-- Guru Filter (Admin only) --}}
            @if ($showGuruFilter)
                <div class="lg:col-span-3">
                    <label for="filter_guru" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Guru (PIC / Pengawas)
                    </label>
                    <select id="filter_guru" name="guru_id" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Guru</option>
                        @foreach ($gurus as $guru)
                            <option value="{{ $guru->id }}" @selected((string) request('guru_id') === (string) $guru->id)>
                                {{ $guru->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Actions & Toggle More --}}
            <div class="{{ $showGuruFilter ? 'lg:col-span-6' : 'lg:col-span-8' }} flex flex-wrap items-center justify-between gap-2 pt-1">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($showPeriodAndSort)
                        <button
                            type="button"
                            @click="showMore = !showMore"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800"
                        >
                            <span x-text="showMore ? 'Sembunyikan Periode & Urutan' : 'Periode & Urutan'"></span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': showMore }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    @endif

                    @if ($activeFilterCount > 0)
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold text-blue-700">
                            {{ $activeFilterCount }} filter aktif
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @if ($activeFilterCount > 0)
                        <a href="{{ $resetUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>

        @if ($showPeriodAndSort)
            <div x-show="showMore" x-cloak x-transition class="grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 md:grid-cols-3">
                <div>
                    <label for="filter_tahun_ajaran" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Tahun Ajaran
                    </label>
                    <select id="filter_tahun_ajaran" name="tahun_ajaran" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach ($tahunAjarans as $ta)
                            <option value="{{ $ta }}" @selected(request('tahun_ajaran') === $ta)>{{ $ta }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_semester" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Semester
                    </label>
                    <select id="filter_semester" name="semester" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Semester</option>
                        <option value="ganjil" @selected(request('semester') === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected(request('semester') === 'genap')>Genap</option>
                    </select>
                </div>

                <div>
                    <label for="filter_sort" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Urutkan
                    </label>
                    <select id="filter_sort" name="sort" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500">
                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>Terbaru Dibuat</option>
                        <option value="jadwal_asc" @selected(request('sort') === 'jadwal_asc')>Jadwal Mulai Terdekat</option>
                        <option value="jadwal_desc" @selected(request('sort') === 'jadwal_desc')>Jadwal Mulai Terbaru</option>
                        <option value="peserta_desc" @selected(request('sort') === 'peserta_desc')>Peserta Terbanyak</option>
                        <option value="soal_desc" @selected(request('sort') === 'soal_desc')>Jumlah Soal Terbanyak</option>
                        <option value="nama_asc" @selected(request('sort') === 'nama_asc')>Nama Ujian (A–Z)</option>
                    </select>
                </div>
            </div>
        @endif
    </form>
</div>
