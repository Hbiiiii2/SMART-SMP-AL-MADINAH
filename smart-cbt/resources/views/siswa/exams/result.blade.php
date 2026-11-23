@php
    $siswaData = $siswa ?? $attempt->siswa ?? auth()->user()?->siswa;
    $namaPeserta = $siswaData?->nama ?: auth()->user()?->name ?: '-';
    $identitasPeserta = $siswaData?->nisn ?: ($siswaData?->nis ?: '-');
    $kelasPeserta = $siswaData?->kelasData?->nama_kelas ?? $siswaData?->kelas ?? $exam->kelasData?->nama_kelas ?? $exam->kelas ?? '-';
@endphp
<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold text-slate-900">Ujian Selesai</h2></x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-3xl text-emerald-600">✓</div>
                <p class="mt-6 text-sm font-bold text-blue-600">{{ $exam->mataPelajaran?->nama }}</p>
                <h1 class="mt-2 text-3xl font-black text-slate-900">{{ $exam->nama }}</h1>
                <p class="mt-3 text-slate-500">
                    {{ $attempt->status === 'expired' ? 'Waktu ujian telah berakhir dan jawaban Anda dikumpulkan secara otomatis.' : 'Jawaban ujian Anda telah berhasil dikumpulkan.' }}
                </p>

                <div class="my-6 rounded-xl border border-slate-200 bg-slate-50 p-5 text-left">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Nama Peserta</dt>
                            <dd class="text-right font-bold text-slate-900">{{ $namaPeserta }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <dt class="text-slate-500">NISN / NIS</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $identitasPeserta }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <dt class="text-slate-500">Kelas</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $kelasPeserta }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <dt class="text-slate-500">Status Pengumpulan</dt>
                            <dd>
                                @if ($attempt->status === 'expired')
                                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Waktu Habis (Terkumpul Otomatis)</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Terkumpul</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <dt class="text-slate-500">Waktu Mulai</dt>
                            <dd class="font-semibold text-slate-900">{{ $attempt->started_at?->format('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                            <dt class="text-slate-500">Waktu Selesai</dt>
                            <dd class="font-semibold text-slate-900">{{ $attempt->submitted_at?->format('d M Y, H:i') ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mb-7 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-left text-sm text-blue-800">
                    Terima kasih telah mengikuti ujian dengan tertib. Jawaban Anda telah tersimpan di sistem dan hasil ujian akan direkapitulasi oleh guru mata pelajaran.
                </div>

                <div class="flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('siswa.ujian.index') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">Kembali ke Ujian Saya</a>
                    <a href="{{ route('siswa.riwayat') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Lihat Riwayat Ujian</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>