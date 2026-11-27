@php
    $componentLabels = ['pg' => 'PG', 'essay_1' => 'Essay 1', 'essay_2' => 'Essay 2'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold leading-tight text-slate-900">Data Ujian</h2>
                <p class="mt-0.5 text-sm text-slate-500">Kelola paket ujian, kesiapan soal, dan pantau status pelaksanaan ujian</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Top Bar Actions & Summary Cards --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('ujian.create') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        + Tambah Ujian
                    </a>
                @endif

                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span>Total: <strong class="text-slate-900">{{ $summary['total'] ?? $exams->total() }}</strong> paket</span>
                    <span>&bull;</span>
                    <span>Ada Soal: <strong class="text-blue-700">{{ $summary['sudah_ada_soal'] ?? 0 }}</strong></span>
                    <span>&bull;</span>
                    <span>Belum Ada Soal: <strong class="text-amber-700">{{ $summary['belum_ada_soal'] ?? 0 }}</strong></span>
                    <span>&bull;</span>
                    <span>Pernah Ujian: <strong class="text-indigo-700">{{ $summary['sudah_pernah_ujian'] ?? 0 }}</strong></span>
                </div>
            </div>

            @foreach (['success' => 'emerald', 'error' => 'rose'] as $key => $color)
                @if (session($key))
                    <div class="rounded-xl border border-{{ $color }}-200 bg-{{ $color }}-50 px-4 py-3 text-sm text-{{ $color }}-800">
                        {{ session($key) }}
                    </div>
                @endif
            @endforeach

            {{-- Quick Filter Pills --}}
            <x-exam-quick-filter-pills routeName="ujian.index" :summary="$summary" />

            {{-- Full Search & Filter Bar --}}
            <x-exam-filter-bar
                :action="route('ujian.index')"
                :resetUrl="route('ujian.index')"
                :filterOptions="$filterOptions"
                :showGuruFilter="true"
                :showPeriodAndSort="true"
            />

            {{-- Table --}}
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
                                <th class="px-5 py-3.5 w-12">No</th>
                                <th class="px-5 py-3.5">Paket Ujian</th>
                                <th class="px-5 py-3.5">Mapel &amp; Kelas</th>
                                <th class="px-5 py-3.5">Guru (PIC &amp; Pengawas)</th>
                                <th class="px-5 py-3.5">Kesiapan Soal</th>
                                <th class="px-5 py-3.5">Pelaksanaan Ujian</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($exams as $exam)
                                @php
                                    $effectiveStatus = $exam->effectiveStatus();
                                    $statusBadge = match ($effectiveStatus) {
                                        'aktif' => ['Aktif', 'bg-emerald-100 text-emerald-700'],
                                        'terjadwal' => ['Terjadwal', 'bg-sky-100 text-sky-700'],
                                        'selesai' => ['Selesai', 'bg-blue-100 text-blue-700'],
                                        default => ['Draft', 'bg-slate-100 text-slate-700'],
                                    };
                                    $components = $exam->komponen_soal ?: match ($exam->jenis) {
                                        'pg' => ['pg'],
                                        'essay' => ['essay_1', 'essay_2'],
                                        default => ['pg', 'essay_1', 'essay_2'],
                                    };
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50/70">
                                    <td class="px-5 py-4 font-medium text-slate-500">
                                        {{ ($exams->firstItem() ?? 1) + $loop->index }}
                                    </td>

                                    <td class="min-w-60 px-5 py-4">
                                        <a href="{{ route('ujian.show', $exam) }}" class="font-bold text-slate-900 hover:text-blue-600">
                                            {{ $exam->nama }}
                                        </a>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700">
                                                {{ $exam->kode_ujian ?? '-' }}
                                            </span>
                                            <span>&bull;</span>
                                            <span>{{ $exam->durasi_menit }} menit</span>
                                            @if ($exam->ruangan)
                                                <span>&bull;</span>
                                                <span>{{ $exam->ruangan }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-[11px] text-slate-400">
                                            @if ($exam->tanggal_mulai)
                                                Jadwal: {{ $exam->tanggal_mulai->format('d/m/Y H:i') }}
                                                @if ($exam->tanggal_selesai)
                                                    – {{ $exam->tanggal_selesai->format('d/m H:i') }}
                                                @endif
                                            @else
                                                Jadwal belum diatur
                                            @endif
                                            @if ($exam->tahun_ajaran || $exam->semester)
                                                &bull; {{ $exam->tahun_ajaran ?? '-' }} {{ $exam->semester ? '(' . ucfirst($exam->semester) . ')' : '' }}
                                            @endif
                                        </p>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="font-semibold text-slate-800">{{ $exam->mataPelajaran?->nama ?? '-' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Kelas {{ $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="min-w-44 px-5 py-4">
                                        <p class="text-xs font-semibold text-slate-800">
                                            PIC: {{ $exam->guru?->nama ?? '-' }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Pengawas: {{ $exam->guruPengawas?->nama ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="min-w-44 px-5 py-4">
                                        @if (($exam->questions_count ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">
                                                {{ $exam->questions_count }} Soal &bull; Sudah Dibuat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                                0 Soal &bull; Belum Dibuat
                                            </span>
                                        @endif
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            @foreach ($components as $component)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                                    {{ $componentLabels[$component] ?? $component }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="min-w-44 px-5 py-4">
                                        @if (($exam->exam_attempts_count ?? 0) > 0)
                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                                Sudah Pernah Ujian
                                            </span>
                                            <p class="mt-1 text-xs text-slate-600">
                                                <strong>{{ $exam->exam_attempts_count }}</strong> peserta
                                                ({{ $exam->finished_attempts_count ?? 0 }} selesai)
                                            </p>
                                            @if (($exam->needs_grading_count ?? 0) > 0)
                                                <p class="mt-0.5 text-[11px] font-semibold text-rose-600">
                                                    {{ $exam->needs_grading_count }} perlu nilai essay
                                                </p>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                                Belum Pernah Ujian
                                            </span>
                                            <p class="mt-1 text-[11px] text-slate-400">0 peserta</p>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge[1] }}">
                                            {{ $statusBadge[0] }}
                                        </span>
                                        @if ($exam->token_aktif && $effectiveStatus !== 'selesai')
                                            <p class="mt-1 font-mono text-[11px] font-bold text-emerald-700">
                                                Token: {{ $exam->token }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <a class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50"
                                               href="{{ route('ujian.show', $exam) }}">Detail</a>

                                            @if (($exam->exam_attempts_count ?? 0) > 0)
                                                <a class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100"
                                                   href="{{ route('hasil.index', ['exam_id' => $exam->id]) }}">Hasil</a>
                                            @endif

                                            <a class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                               href="{{ route('ujian.edit', $exam) }}">Edit</a>

                                            @if (Auth::user()->isAdmin())
                                                @php
                                                    $deletePayload = [
                                                        'action' => route('ujian.destroy', $exam, false),
                                                        'nama' => $exam->nama,
                                                        'kode_ujian' => $exam->kode_ujian ?? '-',
                                                        'mapel' => $exam->mataPelajaran?->nama ?? '-',
                                                        'kelas' => $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-',
                                                        'guru' => $exam->guru?->nama ?? '-',
                                                        'questions_count' => (int) ($exam->questions_count ?? 0),
                                                        'attempts_count' => (int) ($exam->exam_attempts_count ?? 0),
                                                        'in_progress_count' => (int) ($exam->in_progress_attempts_count ?? 0),
                                                        'synced_banks_count' => (int) ($exam->synced_question_banks_count ?? 0),
                                                        'return_query' => request()->getQueryString() ?? '',
                                                        'return_route' => 'ujian.index',
                                                    ];
                                                @endphp
                                                <button
                                                    type="button"
                                                    data-exam="{{ json_encode($deletePayload, JSON_UNESCAPED_SLASHES) }}"
                                                    data-delete-exam-trigger="open-delete-exam-modal"
                                                    onclick="window.openDeleteExamModal(this)"
                                                    class="rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                                >
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                        <p class="font-semibold text-slate-700">Tidak ada data ujian yang sesuai.</p>
                                        @if (request()->query())
                                            <a href="{{ route('ujian.index') }}" class="mt-2 inline-flex items-center text-xs font-semibold text-blue-600 hover:underline">
                                                Reset filter untuk menampilkan semua ujian
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exams->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    @if (Auth::user()->isAdmin())
        @include('ujian._delete-modal')
    @endif
</x-app-layout>
