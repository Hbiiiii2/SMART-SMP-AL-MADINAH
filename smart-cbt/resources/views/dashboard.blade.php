<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Dashboard
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Ringkasan, pencarian, dan monitoring kesiapan ujian SMART CBT
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- =========================
                WELCOME
            ========================== --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-slate-900 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600">
                            Selamat datang kembali
                        </p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">
                            Administrator SMART CBT
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-600">
                            Pantau mana paket ujian yang sudah dibuat soalnya, mana yang belum dilengkapi, dan mana yang sudah pernah diikuti siswa dari satu tempat.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('ujian.create') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                            + Tambah Ujian
                        </a>
                        <a href="{{ route('hasil.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Buka Hasil Ujian
                        </a>
                    </div>
                </div>
            </div>

            {{-- =========================
                STATISTIK MASTER & UJIAN
            ========================== --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- SISWA --}}
                <a href="{{ route('siswa.index') }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Total Siswa
                            </p>
                            <p class="mt-2 text-3xl font-black text-slate-900">
                                {{ number_format($totalSiswa) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Peserta terdaftar
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M17 20h5v-2a4 4 0 00-4-4h-1m-3 6H6a2 2 0 01-2-2v-1a4 4 0 014-4h6a4 4 0 014 4v1a2 2 0 01-2 2zm-3-10a4 4 0 100-8 4 4 0 000 8zm8 2a3 3 0 100-6 3 3 0 000 6z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-blue-600">
                        Kelola Siswa
                        <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                {{-- GURU --}}
                <a href="{{ route('guru.index') }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Total Guru
                            </p>
                            <p class="mt-2 text-3xl font-black text-slate-900">
                                {{ number_format($totalGuru) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Pengajar terdaftar
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M12 14a5 5 0 100-10 5 5 0 000 10zm-7 7a7 7 0 0114 0M19 8h2m-1-1v2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-emerald-600">
                        Kelola Guru
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                {{-- KELAS --}}
                <a href="{{ route('kelas.index') }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Total Kelas
                            </p>
                            <p class="mt-2 text-3xl font-black text-slate-900">
                                {{ number_format($totalKelas) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Kelas terdaftar
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 17A2.5 2.5 0 004 19.5V5a2 2 0 012-2h14v14H6.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-violet-600">
                        Kelola Kelas
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                {{-- UJIAN --}}
                <a href="{{ route('ujian.index') }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Paket Ujian
                            </p>
                            <p class="mt-2 text-3xl font-black text-slate-900">
                                {{ number_format($totalUjian) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $ujianSummary['sudah_ada_soal'] ?? 0 }} ada soal &bull; {{ $ujianSummary['sudah_pernah_ujian'] ?? 0 }} pernah ujian
                            </p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M9 5h6m-8 4h10M7 13h10m-8 4h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-amber-600">
                        Kelola Ujian
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            </div>

            {{-- =========================
                QUICK FILTER PILLS & SEARCH
            ========================== --}}
            <div class="space-y-3">
                <x-exam-quick-filter-pills routeName="dashboard" :summary="$ujianSummary" />

                <x-exam-filter-bar
                    :action="route('dashboard')"
                    :resetUrl="route('dashboard')"
                    :filterOptions="$filterOptions"
                    :showGuruFilter="true"
                    :showPeriodAndSort="true"
                />
            </div>

            {{-- =========================
                DAFTAR UJIAN & RINGKASAN
            ========================== --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- DAFTAR PAKET UJIAN --}}
                <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="font-bold text-slate-900">
                                Daftar Paket Ujian
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Menampilkan {{ $ujianTerbaru->total() }} paket ujian sesuai filter.
                            </p>
                        </div>
                        <a href="{{ route('ujian.index', request()->query()) }}"
                           class="text-xs font-bold text-blue-600 hover:text-blue-800">
                            Buka Tampilan Tabel Lengkap &rarr;
                        </a>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($ujianTerbaru as $ujian)
                            <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                  d="M9 5h6m-8 4h10M7 13h10m-8 4h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                                        </svg>
                                    </div>

                                    <div class="min-w-0 space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('ujian.show', $ujian) }}" class="truncate text-sm font-bold text-slate-900 hover:text-blue-600">
                                                {{ $ujian->nama }}
                                            </a>
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-600">
                                                {{ $ujian->kode_ujian ?? '-' }}
                                            </span>
                                        </div>

                                        <p class="text-xs text-slate-500">
                                            {{ $ujian->mataPelajaran?->nama ?? 'Mapel belum diatur' }}
                                            <span class="mx-1 text-slate-300">&bull;</span>
                                            Kelas {{ $ujian->kelasData?->nama_kelas ?? $ujian->kelas ?? '-' }}
                                            <span class="mx-1 text-slate-300">&bull;</span>
                                            PIC: {{ $ujian->guru?->nama ?? '-' }}
                                            @if ($ujian->tanggal_mulai)
                                                <span class="mx-1 text-slate-300">&bull;</span>
                                                {{ $ujian->tanggal_mulai->format('d/m/Y H:i') }}
                                            @endif
                                        </p>

                                        <x-exam-readiness-badges :exam="$ujian" />
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('ujian.show', $ujian) }}"
                                           class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                            Detail
                                        </a>
                                        @if (($ujian->exam_attempts_count ?? 0) > 0)
                                            <a href="{{ route('hasil.index', ['exam_id' => $ujian->id]) }}"
                                               class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700">
                                                Hasil ({{ $ujian->exam_attempts_count }})
                                            </a>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        Dibuat {{ $ujian->created_at?->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <p class="text-sm font-bold text-slate-700">
                                    Tidak ada paket ujian yang sesuai filter
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Coba ubah kata kunci pencarian atau reset filter untuk melihat semua paket ujian.
                                </p>
                                @if (request()->query())
                                    <a href="{{ route('dashboard') }}" class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Reset Filter
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if ($ujianTerbaru->hasPages())
                        <div class="border-t border-slate-100 px-5 py-4">
                            {{ $ujianTerbaru->links() }}
                        </div>
                    @endif
                </div>

                {{-- PANEL KANAN: RINGKASAN KESIAPAN & SHORTCUT --}}
                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-900">Ringkasan Kesiapan Ujian</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Klik kategori untuk langsung menyaring daftar ujian.</p>

                        <div class="mt-4 space-y-2.5 text-sm">
                            <a href="{{ route('ujian.index', ['soal_status' => 'sudah_ada']) }}"
                               class="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50/60 px-3.5 py-2.5 transition hover:bg-blue-50">
                                <span class="text-xs font-semibold text-blue-900">Soal Sudah Dibuat</span>
                                <span class="rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-bold text-white">
                                    {{ number_format($ujianSummary['sudah_ada_soal'] ?? 0) }}
                                </span>
                            </a>

                            <a href="{{ route('ujian.index', ['soal_status' => 'belum_ada']) }}"
                               class="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/60 px-3.5 py-2.5 transition hover:bg-amber-50">
                                <span class="text-xs font-semibold text-amber-900">Belum Ada Soal</span>
                                <span class="rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-bold text-white">
                                    {{ number_format($ujianSummary['belum_ada_soal'] ?? 0) }}
                                </span>
                            </a>

                            <a href="{{ route('ujian.index', ['pelaksanaan' => 'sudah_ujian']) }}"
                               class="flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50/60 px-3.5 py-2.5 transition hover:bg-indigo-50">
                                <span class="text-xs font-semibold text-indigo-900">Sudah Pernah Ujian</span>
                                <span class="rounded-full bg-indigo-600 px-2.5 py-0.5 text-xs font-bold text-white">
                                    {{ number_format($ujianSummary['sudah_pernah_ujian'] ?? 0) }}
                                </span>
                            </a>

                            <a href="{{ route('ujian.index', ['pelaksanaan' => 'belum_ujian']) }}"
                               class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 transition hover:bg-slate-100">
                                <span class="text-xs font-semibold text-slate-700">Belum Pernah Ujian</span>
                                <span class="rounded-full bg-slate-600 px-2.5 py-0.5 text-xs font-bold text-white">
                                    {{ number_format($ujianSummary['belum_pernah_ujian'] ?? 0) }}
                                </span>
                            </a>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-900">Ringkasan Hasil Ujian</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Status pengerjaan & penilaian peserta.</p>

                        <div class="mt-4 space-y-3">
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3.5 py-2.5">
                                <span class="text-xs font-medium text-slate-600">Total Pengerjaan Selesai</span>
                                <span class="text-sm font-black text-slate-900">{{ number_format($totalAttemptSelesai ?? 0) }}</span>
                            </div>

                            <a href="{{ route('hasil.index', ['status' => 'needs_grading']) }}"
                               class="flex items-center justify-between rounded-xl border {{ ($totalPerluNilaiEssay ?? 0) > 0 ? 'border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100/70' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' }} px-3.5 py-2.5 transition">
                                <span class="text-xs font-semibold">Menunggu Penilaian Essay</span>
                                <span class="rounded-full {{ ($totalPerluNilaiEssay ?? 0) > 0 ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-700' }} px-2.5 py-0.5 text-xs font-bold">
                                    {{ number_format($totalPerluNilaiEssay ?? 0) }}
                                </span>
                            </a>

                            <div class="grid grid-cols-1 gap-2 pt-1">
                                <a href="{{ route('hasil.index') }}"
                                   class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                                    Filter & Cari Hasil Ujian
                                </a>
                                <a href="{{ route('hasil.index', ['tab' => 'ujian']) }}"
                                   class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    Lihat Status Pelaksanaan Per Ujian
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</x-app-layout>
