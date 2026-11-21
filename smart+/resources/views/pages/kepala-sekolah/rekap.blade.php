@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Rekap Absensi</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Rekapitulasi kehadiran siswa dan guru</p>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('ks.rekap') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="mb-4 flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                <a href="{{ route('ks.rekap', array_merge(request()->query(), ['tab' => 'siswa'])) }}"
                    class="flex-1 rounded-md px-4 py-2 text-center text-sm font-medium transition {{ $tab === 'siswa' ? 'bg-white text-gray-800 shadow dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    Siswa
                </a>
                <a href="{{ route('ks.rekap', array_merge(request()->query(), ['tab' => 'guru'])) }}"
                    class="flex-1 rounded-md px-4 py-2 text-center text-sm font-medium transition {{ $tab === 'guru' ? 'bg-white text-gray-800 shadow dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    Guru
                </a>
            </div>

            <div class="mb-4 flex flex-wrap gap-4">
                <div class="w-full sm:w-auto">
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Tahun Ajaran</label>
                    <select name="tahun_ajaran_id" onchange="this.form.submit()"
                        class="h-10 w-full sm:w-auto rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}" {{ $tahunAjaranFilter == $ta->id ? 'selected' : '' }}>{{ $ta->nama }} {{ $ta->is_aktif ? '(Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Periode</label>
                    <select name="periode" onchange="this.form.submit()"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="harian" {{ $periode === 'harian' ? 'selected' : '' }}>Harian</option>
                        <option value="mingguan" {{ $periode === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        <option value="bulanan" {{ $periode === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                        <option value="semester" {{ $periode === 'semester' ? 'selected' : '' }}>Semester</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Dari</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Sampai</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>

                @if($tab === 'siswa')
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Group By</label>
                    <select name="group_by"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="kelas" {{ $groupBy === 'kelas' ? 'selected' : '' }}>Per Kelas</option>
                        <option value="pelajaran" {{ $groupBy === 'pelajaran' ? 'selected' : '' }}>Per Pelajaran</option>
                        <option value="siswa" {{ $groupBy === 'siswa' ? 'selected' : '' }}>Per Siswa</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Kelas</label>
                    <select name="kelas_filter"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Semua</option>
                        @foreach($kelasList as $kls)
                            <option value="{{ $kls }}" {{ request('kelas_filter') === $kls ? 'selected' : '' }}>{{ $kls }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Pelajaran</label>
                    <select name="mapel_filter"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Semua</option>
                        @foreach($mataPelajarans as $mp)
                            <option value="{{ $mp->id }}" {{ request('mapel_filter') == $mp->id ? 'selected' : '' }}>{{ $mp->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Guru</label>
                    <select name="guru_filter"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Semua</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id }}" {{ request('guru_filter') == $g->id ? 'selected' : '' }}>{{ $g->nama_lengkap }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <button type="submit" class="h-10 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600 transition">Filter</button>
                <a href="{{ route('ks.rekap', ['tab' => $tab]) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
            </div>

            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</p>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @php $h = $summary['total_hadir'] ?? 0; $t = $summary['total_terlambat'] ?? 0; @endphp
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xl font-bold text-success-500">{{ $h + $t }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Hadir</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xl font-bold text-warning-500">{{ $summary['total_terlambat'] ?? $summary['total_alpha'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tab === 'guru' ? 'Alpha' : 'Terlambat' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xl font-bold text-error-500">{{ $tab === 'guru' ? ($summary['total_alpha'] ?? 0) : ($summary['total_alfa'] ?? 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Alfa</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xl font-bold text-blue-500">{{ $summary['total_sakit'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Sakit</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xl font-bold text-purple-500">{{ $summary['total_izin'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Izin</div>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-3" x-data="{ exportOpen: false }">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Export Excel</p>
            <div class="flex flex-wrap gap-2">
                @if($tab === 'siswa' && $groupBy === 'kelas')
                    <div x-data="{
                        showPerKelas: false,
                        selectedKelas: '',
                        loading: false,
                        detail: null,
                        async openDetail(kelas) {
                            this.selectedKelas = kelas;
                            this.showPerKelas = true;
                            this.loading = true;
                            this.detail = null;
                            try {
                                const res = await fetch('{{ route("ks.rekap.detail-kelas") }}?kelas=' + encodeURIComponent(kelas) + '&start_date={{ $startDate }}&end_date={{ $endDate }}');
                                this.detail = await res.json();
                            } catch(e) { this.detail = { siswas: [] }; }
                            this.loading = false;
                        }
                    }">
                        <button @click="exportOpen = !exportOpen"
                            class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-success-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export Excel
                        </button>

                        <div x-show="exportOpen" x-transition @click.outside="exportOpen = false"
                            class="absolute z-50 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Jenis Export</p>
                            <div class="space-y-2">
                                <a href="{{ route('ks.rekap.export', ['tab' => $tab, 'periode' => $periode, 'start_date' => $startDate, 'end_date' => $endDate, 'group_by' => 'siswa', 'kelas_filter' => request('kelas_filter'), 'mapel_filter' => request('mapel_filter')]) }}"
                                    class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:hover:border-brand-500/30 dark:hover:bg-brand-500/5">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">Rekap Per Siswa</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Detail kehadiran tiap siswa</div>
                                    </div>
                                </a>
                                <a href="{{ route('ks.rekap.export', ['tab' => $tab, 'periode' => $periode, 'start_date' => $startDate, 'end_date' => $endDate, 'group_by' => 'pelajaran', 'mapel_filter' => request('mapel_filter')]) }}"
                                    class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-gray-700 dark:hover:border-brand-500/30 dark:hover:bg-brand-500/5">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 text-success-500 dark:bg-success-500/10">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">Rekap Per Pelajaran</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Ringkasan per mata pelajaran</div>
                                    </div>
                                </a>
                            </div>
                            <p class="mt-3 border-t border-gray-200 pt-3 text-xs text-gray-400 dark:border-gray-700">Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>
                @elseif($tab === 'guru')
                    <a href="{{ route('ks.rekap.export', ['tab' => 'guru', 'periode' => $periode, 'start_date' => $startDate, 'end_date' => $endDate, 'guru_filter' => request('guru_filter')]) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-success-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Rekap Guru
                    </a>
                @else
                    <a href="{{ route('ks.rekap.export', ['tab' => $tab, 'periode' => $periode, 'start_date' => $startDate, 'end_date' => $endDate, 'group_by' => $groupBy, 'kelas_filter' => request('kelas_filter'), 'mapel_filter' => request('mapel_filter')]) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-success-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div x-data="{
        showDetail: false,
        loadingDetail: false,
        detailData: null,
        detailKelas: '',
        detailPeriode: '',
        async openDetail(kelas) {
            this.showDetail = true;
            this.loadingDetail = true;
            this.detailKelas = kelas;
            try {
                const res = await fetch('{{ route("ks.rekap.detail-kelas") }}?kelas=' + encodeURIComponent(kelas) + '&start_date={{ $startDate }}&end_date={{ $endDate }}');
                this.detailData = await res.json();
                this.detailPeriode = this.detailData.periode;
            } catch(e) { this.detailData = { siswas: [] }; }
            this.loadingDetail = false;
        }
    }">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">No</th>
                        @if($tab === 'siswa')
                            @if($groupBy === 'kelas')
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                            @elseif($groupBy === 'pelajaran')
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Mata Pelajaran</th>
                            @else
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">NISN</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Kelas</th>
                            @endif
                        @else
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">NIP</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Nama Guru</th>
                        @endif
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Hadir</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Terlambat</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">{{ $tab === 'guru' ? 'Alpha' : 'Alfa' }}</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Sakit</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Izin</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">%</th>
                        @if($tab === 'siswa' && $groupBy === 'kelas')
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($data as $i => $row)
                        @php $persen = $row->total > 0 ? round($row->hadir / $row->total * 100, 1) : 0; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-gray-500">
                                @if($data instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    {{ $data->firstItem() + $i }}
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </td>
                            @if($tab === 'siswa')
                                @if($groupBy === 'kelas')
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{{ $row->kelas }}</td>
                                @elseif($groupBy === 'pelajaran')
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{{ $row->nama_mapel }}</td>
                                @else
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row->nisn }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">
                                        <a href="{{ route('ks.detail-siswa', $row->siswa_id) }}" class="hover:text-brand-500 hover:underline">{{ $row->nama_lengkap }}</a>
                                    </td>
                                    <td class="px-4 py-3"><span class="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $row->kelas }}</span></td>
                                @endif
                            @else
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row->nip }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">
                                    <a href="{{ route('ks.detail-guru', $row->guru_id) }}" class="hover:text-brand-500 hover:underline">{{ $row->nama_lengkap }}</a>
                                </td>
                            @endif
                            <td class="px-4 py-3 text-center text-success-600 font-medium">{{ $row->hadir }}</td>
                            <td class="px-4 py-3 text-center text-warning-600">{{ $row->terlambat }}</td>
                            <td class="px-4 py-3 text-center text-error-600">{{ $tab === 'guru' ? $row->alpha : $row->alfa }}</td>
                            <td class="px-4 py-3 text-center text-blue-600">{{ $row->sakit }}</td>
                            <td class="px-4 py-3 text-center text-purple-600">{{ $row->izin }}</td>
                            <td class="px-4 py-3 text-center font-medium text-gray-800 dark:text-white/90">{{ $row->total }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="h-2 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-full rounded-full {{ $persen >= 80 ? 'bg-success-500' : ($persen >= 60 ? 'bg-warning-500' : 'bg-error-500') }}" style="width: {{ $persen }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $persen }}%</span>
                                </div>
                            </td>
                            @if($tab === 'siswa' && $groupBy === 'kelas')
                                <td class="px-4 py-3">
                                    <button @click="openDetail('{{ $row->kelas }}')"
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400 dark:hover:bg-white/[0.05]">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5.25 12 5.25c4.478 0 8.268 2.69 9.542 6.75-.528 3.104-2.654 5.752-5.542 6.75-2.888 1-4.99 1-5.542 0z"/></svg>
                                        Detail
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data rekap.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($data instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="p-4">{{ $data->links() }}</div>
        @endif
    </div>

    <!-- Modal Detail Per Kelas -->
    <div x-show="showDetail" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" style="display:none">
        <div @click.away="showDetail = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-gray-900" x-transition>
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Detail Kelas <span x-text="detailKelas"></span></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="detailPeriode"></p>
                </div>
                <button @click="showDetail = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="max-h-96 overflow-y-auto p-4">
                <template x-if="loadingDetail">
                    <div class="py-8 text-center text-sm text-gray-400">Memuat data...</div>
                </template>
                <template x-if="!loadingDetail && detailData">
                    <div>
                        <div class="mb-3 grid grid-cols-5 gap-2 text-center">
                            <div class="rounded-lg bg-success-50 p-2 dark:bg-success-500/10">
                                <div class="text-lg font-bold text-success-600" x-text="detailData.siswas.reduce((a,b) => a + b.hadir, 0)"></div>
                                <div class="text-[10px] text-success-700 dark:text-success-400">Hadir</div>
                            </div>
                            <div class="rounded-lg bg-warning-50 p-2 dark:bg-warning-500/10">
                                <div class="text-lg font-bold text-warning-600" x-text="detailData.siswas.reduce((a,b) => a + b.terlambat, 0)"></div>
                                <div class="text-[10px] text-warning-700 dark:text-warning-400">Terlambat</div>
                            </div>
                            <div class="rounded-lg bg-error-50 p-2 dark:bg-error-500/10">
                                <div class="text-lg font-bold text-error-600" x-text="detailData.siswas.reduce((a,b) => a + b.alfa, 0)"></div>
                                <div class="text-[10px] text-error-700 dark:text-error-400">Alfa</div>
                            </div>
                            <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-500/10">
                                <div class="text-lg font-bold text-blue-600" x-text="detailData.siswas.reduce((a,b) => a + b.sakit, 0)"></div>
                                <div class="text-[10px] text-blue-700 dark:text-blue-400">Sakit</div>
                            </div>
                            <div class="rounded-lg bg-purple-50 p-2 dark:bg-purple-500/10">
                                <div class="text-lg font-bold text-purple-600" x-text="detailData.siswas.reduce((a,b) => a + b.izin, 0)"></div>
                                <div class="text-[10px] text-purple-700 dark:text-purple-400">Izin</div>
                            </div>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500">No</th>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500">NISN</th>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500">Nama</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Hadir</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Tlb</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Alfa</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Skt</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Izn</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                                <template x-for="(s, idx) in detailData.siswas" :key="idx">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-3 py-2 text-gray-500" x-text="idx + 1"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400" x-text="s.nisn"></td>
                                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-white/90">
                                            <a :href="'{{ url('kepala-sekolah/siswa') }}/' + s.id" class="hover:text-brand-500 hover:underline" x-text="s.nama"></a>
                                        </td>
                                        <td class="px-3 py-2 text-center text-success-600" x-text="s.hadir"></td>
                                        <td class="px-3 py-2 text-center text-warning-600" x-text="s.terlambat"></td>
                                        <td class="px-3 py-2 text-center text-error-600" x-text="s.alfa"></td>
                                        <td class="px-3 py-2 text-center text-blue-600" x-text="s.sakit"></td>
                                        <td class="px-3 py-2 text-center text-purple-600" x-text="s.izin"></td>
                                        <td class="px-3 py-2 text-center text-xs font-medium" :class="s.persen >= 80 ? 'text-success-600' : (s.persen >= 60 ? 'text-warning-600' : 'text-error-600')">
                                            <span x-text="s.persen + '%'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
