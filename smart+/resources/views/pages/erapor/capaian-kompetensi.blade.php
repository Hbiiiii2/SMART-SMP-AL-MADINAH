@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Kurikulum Merdeka
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">SMP Al Madinah</span>
            </div>
            <h2 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">Input Capaian Kompetensi Mapel</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola narasi Capaian Kompetensi (CP) per siswa pada mata pelajaran <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $targetMapel->nama ?? '-' }}</span> kelas <span class="font-bold text-brand-600 dark:text-brand-400">{{ $kelas }}</span>.
            </p>
        </div>

        <!-- Quick actions -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('erapor.buku-nilai.index', ['mata_pelajaran_id' => $targetMapel->id, 'kelas' => $kelas, 'tahun_ajaran_id' => $targetYear->id, 'semester' => $semester]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="h-4 w-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Lihat Buku Nilai Mapel
            </a>
            <a href="{{ route('erapor.cetak.index', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Rapor
            </a>
        </div>
    </div>

    <!-- Alert / Success Notification -->
    @if(session('success'))
        <div class="mb-6 flex items-center justify-between gap-3 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold text-sm">✓</span>
                <p class="text-sm font-semibold">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">&times;</button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('erapor.capaian.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Mata Pelajaran -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Mata Pelajaran</label>
                <select name="mata_pelajaran_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" {{ $targetMapel?->id == $sub->id ? 'selected' : '' }}>
                            {{ $sub->nama }} ({{ $sub->kode ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Rombel / Kelas</label>
                <select name="kelas" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    @foreach($classes as $c)
                        <option value="{{ $c }}" {{ $kelas === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tahun Ajaran -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    @foreach($allYears as $y)
                        <option value="{{ $y->id }}" {{ $targetYear->id == $y->id ? 'selected' : '' }}>
                            {{ $y->tahun }} {{ $y->is_aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Semester -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Semester</label>
                <select name="semester" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="Ganjil" {{ $semester === 'Ganjil' ? 'selected' : '' }}>Ganjil (1)</option>
                    <option value="Genap" {{ $semester === 'Genap' ? 'selected' : '' }}>Genap (2)</option>
                </select>
            </div>

            <!-- Filter Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-black dark:bg-brand-600 dark:hover:bg-brand-700 transition">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Auto-Generate & Guideline Card -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-emerald-900 dark:bg-emerald-950/20 dark:border-emerald-900/50 dark:text-emerald-300">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold text-lg">✨</span>
            <div>
                <h4 class="text-sm font-bold">Generator Otomatis Capaian Kompetensi Kurikulum Merdeka</h4>
                <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1 max-w-3xl">
                    Sistem dapat membuat narasi capaian pembelajaran terstandar secara otomatis berdasarkan nilai akhir/STS siswa. Anda tetap dapat mengedit teks per siswa sesuai evaluasi belajar individu.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('erapor.capaian.auto-generate') }}" onsubmit="return confirm('Apakah Anda yakin ingin men-generate otomatis seluruh deskripsi capaian untuk siswa kelas {{ $kelas }} pada mapel {{ $targetMapel->nama }}? Teks yang ada akan diperbarui sesuai formula Kurikulum Merdeka.');">
                @csrf
                <input type="hidden" name="mata_pelajaran_id" value="{{ $targetMapel->id }}">
                <input type="hidden" name="kelas" value="{{ $kelas }}">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $targetYear->id }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Generate Otomatis Semua Siswa
                </button>
            </form>
        </div>
    </div>

    <!-- Form Input Table -->
    <form method="POST" action="{{ route('erapor.capaian.save') }}" id="formCapaianKompetensi">
        @csrf
        <input type="hidden" name="mata_pelajaran_id" value="{{ $targetMapel->id }}">
        <input type="hidden" name="kelas" value="{{ $kelas }}">
        <input type="hidden" name="tahun_ajaran_id" value="{{ $targetYear->id }}">
        <input type="hidden" name="semester" value="{{ $semester }}">

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">
                        Daftar Capaian Siswa: {{ $targetMapel->nama }} (Kelas {{ $kelas }})
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total {{ $students->count() }} siswa.</p>
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Semua Capaian
                </button>
            </div>

            <div class="max-w-full overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-4 py-3.5 text-center w-12">No</th>
                            <th class="px-4 py-3.5 min-w-[220px]">Nama & Identitas Siswa</th>
                            <th class="px-4 py-3.5 text-center min-w-[130px]">Nilai & Predikat</th>
                            <th class="px-4 py-3.5 min-w-[460px]">Deskripsi Capaian Pembelajaran (Dicetak di Rapor)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($students as $idx => $s)
                            @php
                                $sum = $summaries->get($s->id);
                                $score = $sum?->final_score ?? $sum?->sts_score;
                                $predikat = \App\Http\Controllers\Erapor\CetakRaporController::calculatePredikat($score);
                                $suggestedDeskripsi = \App\Http\Controllers\Erapor\CetakRaporController::generateDeskripsi($targetMapel->nama, $score, $predikat);
                                $currentText = $sum?->capaian_kompetensi ?? '';
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                <td class="px-4 py-3 text-center text-xs text-gray-400 font-medium align-top pt-4">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-4 py-3 align-top pt-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ strtoupper(substr($s->nama_lengkap, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-800 dark:text-white">
                                                {{ $s->nama_lengkap }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                NIS: {{ $s->nis ?? '-' }} • NISN: {{ $s->nisn ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top pt-4 text-center">
                                    @if($score !== null)
                                        <div class="inline-flex flex-col items-center gap-1">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-800 dark:text-gray-200">
                                                Nilai: {{ number_format($score, 1) }}
                                            </span>
                                            <span class="text-xs font-bold {{ $predikat === 'A' ? 'text-emerald-600' : ($predikat === 'B' ? 'text-blue-600' : ($predikat === 'C' ? 'text-amber-600' : 'text-rose-600')) }}">
                                                Predikat {{ $predikat }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Belum ada nilai</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-1.5">
                                        <textarea
                                            name="capaian[{{ $s->id }}]"
                                            id="capaian_{{ $s->id }}"
                                            data-suggestion="{{ $suggestedDeskripsi }}"
                                            rows="2"
                                            class="w-full rounded-xl border border-gray-300 p-2.5 text-xs text-gray-800 placeholder-gray-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:placeholder-gray-500"
                                            placeholder="Deskripsi capaian kompetensi untuk {{ $s->nama_lengkap }}..."
                                        >{{ old('capaian.' . $s->id, $currentText ?: $suggestedDeskripsi) }}</textarea>

                                        <div class="flex items-center justify-between text-[11px]">
                                            <button
                                                type="button"
                                                onclick="applyCapaianSuggestion('{{ $s->id }}')"
                                                class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium"
                                            >
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Reset ke Formula Standar
                                            </button>
                                            <span class="text-gray-400">Dicetak di kolom Capaian Kompetensi rapor</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada data siswa ditemukan untuk kelas {{ $kelas }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Action -->
            @if($students->isNotEmpty())
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 dark:border-gray-800 dark:bg-white/[0.01]">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Capaian kompetensi yang disimpan akan langsung tampil pada lembar cetak rapor STS dan Akhir Semester.
                    </span>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Semua Capaian Kompetensi
                    </button>
                </div>
            @endif
        </div>
    </form>
</div>

<script>
    function applyCapaianSuggestion(studentId) {
        const textarea = document.getElementById('capaian_' + studentId);
        if (textarea) {
            textarea.value = textarea.getAttribute('data-suggestion');
            textarea.focus();
        }
    }
</script>
@endsection
