<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Status Pengerjaan Ujian</h2>
            <p class="mt-0.5 text-sm text-slate-500">Daftar ujian yang telah Anda selesaikan.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ujian Selesai</p>
                    <p class="mt-2 text-3xl font-black text-slate-900">{{ $totalResults }}</p>
                    <p class="mt-1 text-xs text-slate-500">Total ujian yang telah dikumpulkan</p>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Informasi Nilai</p>
                    <p class="mt-2 text-sm font-semibold text-blue-900">Nilai ujian dievaluasi dan diumumkan oleh guru mata pelajaran.</p>
                    <p class="mt-1 text-xs text-blue-700">Anda tetap dapat melihat status pengumpulan ujian di bawah ini.</p>
                </div>
            </div>

            <div class="flex items-end justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $siswa->nama }}</span>
                    <h1 class="mt-2 text-2xl font-black text-slate-900">Rekap Penyelesaian Ujian</h1>
                    <p class="mt-1 text-sm text-slate-500">Daftar ujian yang telah selesai Anda kerjakan.</p>
                </div>
                <a href="{{ route('siswa.riwayat') }}" class="text-sm font-bold text-blue-600 hover:text-blue-800">Lihat Riwayat</a>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($exams as $exam)
                    @php($attempt = $exam->examAttempts->first())
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">{{ $exam->mataPelajaran?->nama ?? 'Mata Pelajaran' }}</p>
                                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $exam->nama }}</h2>
                                <p class="mt-1 text-xs text-slate-500">{{ $exam->tahun_ajaran ?? 'Periode belum diatur' }} <span class="mx-1 text-slate-300">·</span> {{ $exam->semester ? ucfirst($exam->semester) : 'Semester belum diatur' }}</p>
                            </div>
                            <span class="inline-flex rounded-full {{ $attempt?->status === 'expired' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-xs font-semibold">{{ $attempt?->status === 'expired' ? 'Waktu Habis' : 'Terkumpul' }}</span>
                        </div>
                        <div class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4">
                            <div>
                                <p class="text-xs text-slate-400">Status</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $attempt?->status === 'expired' ? 'Waktu Habis (Terkumpul Otomatis)' : 'Sudah Dikumpulkan' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Waktu Selesai</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $attempt?->submitted_at?->format('d M Y, H:i') ?? '-' }}</p>
                                <a href="{{ route('siswa.ujian.result', $exam) }}" class="mt-2 inline-block text-sm font-bold text-blue-600 hover:text-blue-800">Lihat Bukti Selesai</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2">Belum ada ujian yang diselesaikan.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
