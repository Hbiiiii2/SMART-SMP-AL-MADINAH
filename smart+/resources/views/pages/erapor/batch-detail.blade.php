@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('erapor.cbt.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 mb-2 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Integrasi CBT
            </a>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Rincian Batch: {{ $batch->batch_number }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sumber: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ strtoupper($batch->source_type) }}</span> | {{ $batch->created_at->format('d F Y H:i:s') }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Daftar Nilai Siswa Tersinkronisasi</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-2.5 px-3">No</th>
                        <th class="py-2.5 px-3">NISN</th>
                        <th class="py-2.5 px-3">Nama Siswa</th>
                        <th class="py-2.5 px-3">Kelas</th>
                        <th class="py-2.5 px-3">Nama Ujian</th>
                        <th class="py-2.5 px-3 text-right">Skor Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($batch->cbtResults as $idx => $res)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                            <td class="py-2.5 px-3 text-gray-400 dark:text-gray-500 font-mono text-xs">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $res->student_nisn ?? '-' }}</td>
                            <td class="py-2.5 px-3 font-medium text-gray-900 dark:text-white">{{ $res->student_name ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-xs text-gray-500 dark:text-gray-400">{{ $res->class_name ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-xs text-gray-600 dark:text-gray-300">{{ $res->exam_name ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-right font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ number_format($res->score, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Tidak ada rincian data tersimpan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
