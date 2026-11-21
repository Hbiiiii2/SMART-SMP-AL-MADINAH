<x-app-layout>
    @php
        $labels = ['online' => 'Peserta Online', 'in_progress' => 'Sedang Mengerjakan', 'completed' => 'Selesai', 'not_started' => 'Belum Mulai'];
    @endphp
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Monitoring Ujian</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau progres peserta pada ujian yang menjadi tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Filter Ujian & Pencarian Peserta --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('guru.monitoring.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-12 items-end">
                    @if ($status !== 'online')
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif

                    <div class="md:col-span-5">
                        <label for="monitoring_exam_id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Pilih / Cari Ujian
                        </label>
                        <x-searchable-exam-select
                            id="monitoring_exam_id"
                            name="exam_id"
                            :exams="$examPickerItems"
                            :selected="$queryExamId"
                            placeholder="Semua Ujian ({{ count($examPickerItems) }})"
                            :autoSubmit="true"
                        />
                    </div>

                    <div class="md:col-span-4">
                        <label for="monitoring_search_siswa" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Cari Peserta (Nama / NIS / NISN)
                        </label>
                        <input
                            id="monitoring_search_siswa"
                            name="search_siswa"
                            type="search"
                            value="{{ request('search_siswa') }}"
                            placeholder="Ketik nama siswa atau NIS..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <div class="md:col-span-3 flex items-center gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-slate-800">
                            Terapkan
                        </button>
                        @if (request()->filled('exam_id') || request()->filled('search_siswa'))
                            <a href="{{ route('guru.monitoring.index', $status !== 'online' ? ['status' => $status] : []) }}"
                               class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($labels as $key => $label)
                    <a href="{{ route('guru.monitoring.index', array_filter(['status' => $key, 'exam_id' => $queryExamId, 'search_siswa' => request('search_siswa')])) }}"
                       class="rounded-2xl border bg-white p-5 shadow-sm transition hover:border-blue-300 {{ $status === $key ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200' }}">
                        <p class="text-sm text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $counts[$key] }}</p>
                    </a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="font-semibold text-slate-900">{{ $labels[$status] }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Peserta</th>
                                <th class="px-6 py-3">Ujian</th>
                                <th class="px-6 py-3">Kelas</th>
                                <th class="px-6 py-3">Status</th>
                                @if ($status === 'not_started')
                                    <th class="px-6 py-3">Jadwal Mulai</th>
                                    <th class="px-6 py-3">Jadwal Selesai</th>
                                @else
                                    <th class="px-6 py-3">Mulai</th>
                                    <th class="px-6 py-3">{{ $status === 'completed' ? 'Selesai' : 'Deadline' }}</th>
                                    @if ($status === 'in_progress')
                                        <th class="px-6 py-3">Progress</th>
                                    @endif
                                    @if ($status === 'completed')
                                        <th class="px-6 py-3">Nilai</th>
                                        <th class="px-6 py-3 text-right">Aksi</th>
                                    @elseif ($status === 'in_progress')
                                        <th class="px-6 py-3 text-right">Aksi</th>
                                    @endif
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($attempts as $attempt)
                                @if ($status === 'not_started')
                                    <tr>
                                        <td class="px-6 py-4 font-semibold text-slate-900">
                                            {{ $attempt->nama }}
                                            <span class="block text-xs font-normal text-slate-400">{{ $attempt->nisn ?: $attempt->nis ?: '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4">{{ $attempt->exam_nama }}</td>
                                        <td class="px-6 py-4">{{ $attempt->exam_kelas ?: '-' }}</td>
                                        <td class="px-6 py-4">Belum Mulai</td>
                                        <td class="px-6 py-4">{{ $attempt->tanggal_mulai ? \Illuminate\Support\Carbon::parse($attempt->tanggal_mulai)->format('d/m/Y H:i') : '-' }}</td>
                                        <td class="px-6 py-4">{{ $attempt->tanggal_selesai ? \Illuminate\Support\Carbon::parse($attempt->tanggal_selesai)->format('d/m/Y H:i') : '-' }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td class="px-6 py-4 font-semibold text-slate-900">
                                            {{ $attempt->siswa?->nama ?? '-' }}
                                            <span class="block text-xs font-normal text-slate-400">{{ $attempt->siswa?->nisn ?: $attempt->siswa?->nis ?: '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4">{{ $attempt->exam?->nama ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $attempt->exam?->kelasData?->nama_kelas ?? $attempt->exam?->kelas ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            {{ $status === 'in_progress' ? 'Sedang Mengerjakan' : ($status === 'online' ? 'Online' : ($attempt->status === 'needs_grading' ? 'Perlu Nilai Essay' : 'Selesai')) }}
                                        </td>
                                        <td class="px-6 py-4">{{ $attempt->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $status === 'completed' ? ($attempt->submitted_at?->format('d/m/Y H:i') ?? '-') : ($attempt->exam?->tanggal_selesai?->format('d/m/Y H:i') ?? '-') }}</td>
                                        @if ($status === 'in_progress')
                                            <td class="px-6 py-4">{{ $attempt->answers_count ?? 0 }} / {{ $attempt->exam?->questions_count ?? 0 }}</td>
                                        @endif
                                        @if ($status === 'completed')
                                            <td class="px-6 py-4 font-bold">{{ $attempt->nilai_akhir ?? 'Belum Dinilai' }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('guru.hasil.show', $attempt->id) }}" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                                    Rekap Jawaban
                                                </a>
                                            </td>
                                        @elseif ($status === 'in_progress')
                                            <td class="px-6 py-4 text-right">
                                                <a href="{{ route('guru.hasil.show', $attempt->id) }}" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                                    Intip Jawaban
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500">Belum ada data peserta pada kategori ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-6">{{ $attempts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
