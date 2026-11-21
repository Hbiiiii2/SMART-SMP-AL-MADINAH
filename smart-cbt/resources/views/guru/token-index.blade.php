<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                {{-- Breadcrumb --}}
                <nav class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                    <a href="{{ route('guru.dashboard') }}" class="hover:text-slate-800">Dashboard</a>
                    <span>/</span>
                    <span class="font-medium text-slate-800">Token Ujian</span>
                </nav>
                <h2 class="text-xl font-bold leading-tight text-slate-900">
                    Manajemen Token Ujian
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Cari paket ujian, cek kesiapan soal, dan kelola kode token untuk ujian yang Anda ampu.
                </p>
            </div>

            <div>
                <a href="{{ route('guru.ujian.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Ujian Saya
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            <x-exam-quick-filter-pills routeName="guru.token.index" :summary="$summary" />

            <x-exam-filter-bar
                :action="route('guru.token.index')"
                :resetUrl="route('guru.token.index')"
                :filterOptions="$filterOptions"
                :showGuruFilter="false"
                :showPeriodAndSort="false"
                searchPlaceholder="Cari nama ujian, kode ujian, mapel, kelas..."
            />

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-slate-900">Daftar Token Ujian</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Menampilkan {{ $exams->total() }} paket ujian.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Nama Paket Ujian</th>
                                <th class="px-6 py-4">Kesiapan &amp; Pelaksanaan</th>
                                <th class="px-6 py-4 text-center">Kode Token</th>
                                <th class="px-6 py-4 text-center">Status Token</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($exams as $exam)
                                <tr class="align-top transition hover:bg-slate-50/70">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900">{{ $exam->nama }}</p>
                                        <p class="mt-0.5 text-xs font-normal text-slate-500">
                                            <span class="font-mono text-slate-600">{{ $exam->kode_ujian ?? '-' }}</span>
                                            &bull;
                                            {{ $exam->mataPelajaran?->nama ?? '-' }}
                                            &bull;
                                            Kelas: {{ $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="min-w-56 px-6 py-4">
                                        <x-exam-readiness-badges :exam="$exam" />
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-base font-black {{ $exam->token_aktif ? 'text-emerald-700' : 'text-slate-400' }}">
                                        {{ $exam->token ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($exam->token_aktif)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <a href="{{ route('guru.token.show', $exam) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                                            Kelola Token &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        {{ request()->query() ? 'Tidak ada ujian yang sesuai dengan filter.' : 'Belum ada ujian yang ditugaskan kepada Anda.' }}
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
