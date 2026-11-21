<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Dashboard Guru</h2>
            <p class="mt-0.5 text-sm text-slate-500">Ringkasan, pencarian, dan status kesiapan ujian yang menjadi tanggung jawab Anda.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 text-slate-900 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Selamat datang kembali</p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">Kelola Ujian Anda</h1>
                        <p class="mt-2 max-w-xl text-sm text-slate-600">
                            Cek paket ujian mana yang sudah diisi soal, mana yang belum ada soalnya, dan mana yang sudah pernah diikuti siswa.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('guru.ujian.index') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800">
                            Daftar Ujian Saya
                        </a>
                        <a href="{{ route('guru.hasil.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                            Rekap Nilai
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Total Ujian Saya', 'total', 'text-blue-600', 'bg-blue-50', ($summary['sudah_ada_soal'] ?? 0) . ' ada soal • ' . ($summary['sudah_pernah_ujian'] ?? 0) . ' pernah ujian'],
                    ['Ujian Mendatang', 'mendatang', 'text-amber-600', 'bg-amber-50', 'Terjadwal akan dimulai'],
                    ['Ujian Aktif', 'aktif', 'text-emerald-600', 'bg-emerald-50', 'Siap / sedang berlangsung'],
                    ['Ujian Selesai', 'selesai', 'text-slate-600', 'bg-slate-100', 'Sudah melewati jadwal'],
                ] as [$label, $key, $color, $iconBackground, $subtitle])
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                                <p class="mt-2 text-3xl font-black {{ $color }}">{{ number_format($stats[$key]) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $iconBackground }} {{ $color }}">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="1.8" d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm3 5h8M8 13h8M8 17h5"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Quick Filter Pills & Search Bar --}}
            <div class="space-y-3">
                <x-exam-quick-filter-pills routeName="guru.dashboard" :summary="$summary" />

                <x-exam-filter-bar
                    :action="route('guru.dashboard')"
                    :resetUrl="route('guru.dashboard')"
                    :filterOptions="$filterOptions"
                    :showGuruFilter="false"
                    :showPeriodAndSort="true"
                    searchPlaceholder="Cari nama ujian, kode ujian, mapel, kelas, ruangan..."
                />
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="font-bold text-slate-900">Ujian yang Menjadi Tanggung Jawab Anda</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Menampilkan {{ $exams->total() }} paket ujian sesuai filter.</p>
                    </div>
                    <a href="{{ route('guru.ujian.index', request()->query()) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                        Buka Halaman Ujian Saya &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Ujian &amp; Kode</th>
                                <th class="px-5 py-3">Mata Pelajaran &amp; Kelas</th>
                                <th class="px-5 py-3">Jadwal &amp; Ruangan</th>
                                <th class="px-5 py-3">Status &amp; Kesiapan</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($exams as $exam)
                                @php
                                    $guruId = request()->user()->guru?->id;
                                    $isPic = $exam->guru_id === $guruId;
                                    $isPengawas = $exam->guru_pengawas_id === $guruId;
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50">
                                    <td class="min-w-56 px-5 py-4">
                                        <a href="{{ route('guru.ujian.show', $exam) }}" class="font-bold text-slate-900 hover:text-blue-600">
                                            {{ $exam->nama }}
                                        </a>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500">
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono font-semibold text-slate-700">{{ $exam->kode_ujian ?? '-' }}</span>
                                            @if ($isPic)
                                                <span class="rounded bg-blue-50 px-1.5 py-0.5 font-semibold text-blue-700">PIC Soal</span>
                                            @endif
                                            @if ($isPengawas)
                                                <span class="rounded bg-violet-50 px-1.5 py-0.5 font-semibold text-violet-700">Pengawas</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="font-semibold text-slate-800">{{ $exam->mataPelajaran?->nama ?? '-' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Kelas {{ $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs">
                                        <p class="font-medium text-slate-700">{{ $exam->tanggal_mulai?->format('d/m/Y H:i') ?? '-' }}</p>
                                        <p class="text-slate-400">s.d. {{ $exam->tanggal_selesai?->format('d/m/Y H:i') ?? '-' }}</p>
                                        <p class="mt-0.5 text-slate-500">Ruang: {{ $exam->ruangan ?? '-' }}</p>
                                    </td>
                                    <td class="min-w-64 px-5 py-4">
                                        <x-exam-readiness-badges :exam="$exam" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($isPic)
                                                <a class="rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700" href="{{ route('guru.soal.index', $exam) }}">
                                                    Kelola Soal
                                                </a>
                                            @endif
                                            @if ($isPengawas)
                                                <a class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100" href="{{ route('guru.token.show', $exam) }}">
                                                    Token &amp; Monitor
                                                </a>
                                            @endif
                                            @if (($exam->exam_attempts_count ?? 0) > 0)
                                                <a class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100" href="{{ route('guru.hasil.index', ['exam_id' => $exam->id]) }}">
                                                    Hasil ({{ $exam->exam_attempts_count }})
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                        <p class="font-semibold text-slate-700">Tidak ada ujian yang sesuai filter.</p>
                                        @if (request()->query())
                                            <a href="{{ route('guru.dashboard') }}" class="mt-2 inline-flex items-center text-xs font-semibold text-blue-600 hover:underline">
                                                Reset filter
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exams->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
