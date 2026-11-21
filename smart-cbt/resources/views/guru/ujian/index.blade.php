<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold leading-tight text-slate-900">
                    Daftar Ujian Saya
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Kelola soal ujian, penerbitan token, dan pantau status kesiapan maupun pelaksanaan ujian Anda.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Flash Message --}}
            @if (session('success'))
                <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                    <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            {{-- Stat Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Ujian Saya</p>
                        <p class="text-2xl font-black text-slate-900">{{ $totalExams ?? $exams->total() }} <span class="text-xs font-normal text-slate-500">paket</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ujian Aktif / Token</p>
                        <p class="text-2xl font-black text-emerald-600">{{ $activeExams ?? 0 }} <span class="text-xs font-normal text-slate-500">diterbitkan</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Belum Ada Soal</p>
                        <p class="text-2xl font-black text-amber-600">{{ $summary['belum_ada_soal'] ?? 0 }} <span class="text-xs font-normal text-slate-500">perlu dibuat</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1m-3 6H6a2 2 0 01-2-2v-1a4 4 0 014-4h6a4 4 0 014 4v1a2 2 0 01-2 2zm-3-10a4 4 0 100-8 4 4 0 000 8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sudah Pernah Ujian</p>
                        <p class="text-2xl font-black text-indigo-600">{{ $summary['sudah_pernah_ujian'] ?? 0 }} <span class="text-xs font-normal text-slate-500">paket</span></p>
                    </div>
                </div>
            </div>

            {{-- Quick Filter Pills --}}
            <x-exam-quick-filter-pills routeName="guru.ujian.index" :summary="$summary" />

            {{-- Search & Filter Bar --}}
            <x-exam-filter-bar
                :action="route('guru.ujian.index')"
                :resetUrl="route('guru.ujian.index')"
                :filterOptions="$filterOptions"
                :showGuruFilter="false"
                :showPeriodAndSort="true"
                searchPlaceholder="Cari nama ujian, kode ujian, mapel, kelas, ruangan..."
            />

            {{-- Table List Ujian --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-slate-900">Daftar Paket Ujian</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Menampilkan {{ $exams->total() }} paket ujian{{ request()->query() ? ' yang sesuai kriteria filter' : '' }}.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Informasi Ujian</th>
                                <th class="px-6 py-4">Mata Pelajaran &amp; Kelas</th>
                                <th class="px-6 py-4">Kesiapan Soal &amp; Pelaksanaan</th>
                                <th class="px-6 py-4 text-center">Token</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($exams as $exam)
                                @php
                                    $guruId = request()->user()->guru?->id;
                                    $isPic = $exam->guru_id === $guruId;
                                    $isPengawas = $exam->guru_pengawas_id === $guruId;
                                    $examStatus = $exam->effectiveStatus();
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50/70">
                                    <td class="px-6 py-5">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a href="{{ $isPic ? route('guru.soal.index', $exam) : route('guru.ujian.show', $exam) }}" class="text-base font-black text-slate-900 hover:text-blue-600">
                                                        {{ $exam->nama }}
                                                    </a>
                                                    <span class="rounded bg-white px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                                        {{ $exam->kode_ujian ?? '-' }}
                                                    </span>
                                                </div>
                                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                                                    @if ($isPic)
                                                        <span class="rounded bg-blue-50 px-2 py-0.5 font-semibold text-blue-700">Pembuat Soal</span>
                                                    @endif
                                                    @if ($isPengawas)
                                                        <span class="rounded bg-violet-50 px-2 py-0.5 font-semibold text-violet-700">Pengawas</span>
                                                    @endif
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500">
                                                    {{ $exam->durasi_menit }} Menit &bull; Jadwal {{ $exam->tanggal_mulai?->format('d/m/Y H:i') ?? '-' }}
                                                    @if ($exam->ruangan)
                                                        &bull; {{ $exam->ruangan }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex flex-col gap-1">
                                            <p class="font-semibold text-slate-800">{{ $exam->mataPelajaran?->nama ?? '-' }}</p>
                                            <p class="text-xs text-slate-500">Kelas {{ $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-' }}</p>
                                            @if ($exam->tahun_ajaran || $exam->semester)
                                                <p class="text-[11px] text-slate-400">
                                                    {{ $exam->tahun_ajaran ?? '-' }} {{ $exam->semester ? '(' . ucfirst($exam->semester) . ')' : '' }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="min-w-56 px-6 py-5">
                                        <x-exam-readiness-badges :exam="$exam" :showStatus="false" />
                                    </td>

                                    <td class="px-6 py-5 text-center">
                                        @if ($examStatus === 'selesai')
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                                                Ujian Selesai
                                            </span>
                                        @elseif (($examStatus === 'aktif' || $examStatus === 'terjadwal') && $exam->token_aktif)
                                            <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 font-mono text-xs font-black tracking-[0.15em] text-emerald-700">
                                                {{ $exam->token ?? '—' }}
                                            </span>
                                        @else
                                            <span class="text-xs font-medium text-slate-400">Belum Terbit</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-5 text-center">
                                        @if ($examStatus === 'selesai')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                                Selesai
                                            </span>
                                        @elseif ($examStatus === 'aktif')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                                Aktif
                                            </span>
                                        @elseif ($examStatus === 'terjadwal')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-sky-600"></span>
                                                Terjadwal
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                                Draft
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-5 text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($isPic)
                                                <a href="{{ route('guru.soal.index', $exam) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Kelola Soal
                                                </a>
                                            @endif

                                            @if ($isPengawas)
                                                <a href="{{ route('guru.token.show', $exam) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" title="Atur Token Ujian">
                                                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                    </svg>
                                                    {{ $examStatus === 'selesai' ? 'Token' : 'Token' }}
                                                </a>
                                            @endif

                                            @if (($exam->exam_attempts_count ?? 0) > 0)
                                                <a href="{{ route('guru.hasil.index', ['exam_id' => $exam->id]) }}" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                                    Hasil ({{ $exam->exam_attempts_count }})
                                                </a>
                                            @endif

                                            <a href="{{ route('guru.ujian.show', $exam) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" title="Lihat Detail & Penugasan">
                                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <p class="font-medium text-slate-700">
                                            {{ request()->query() ? 'Tidak ada paket ujian yang sesuai dengan filter.' : 'Belum ada paket ujian yang ditugaskan kepada Anda.' }}
                                        </p>
                                        @if (request()->query())
                                            <a href="{{ route('guru.ujian.index') }}" class="mt-2 inline-flex items-center text-xs font-semibold text-blue-600 hover:underline">
                                                Reset filter
                                            </a>
                                        @else
                                            <p class="mt-1 text-xs text-slate-400">Hubungi Administrator untuk penetapan jadwal &amp; paket ujian.</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exams->hasPages())
                    <div class="border-t border-slate-200 p-6">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
