@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10" x-data="bukuNilai()">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Buku Nilai Mata Pelajaran</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Kelas: <span class="font-semibold text-brand-600">{{ $selectedClass }}</span> | 
                Mapel: <span class="font-semibold text-brand-600">{{ $selectedSubject?->nama }}</span>
            </p>
        </div>
        @unless(auth()->user()?->isAkunDemo())
            <div class="flex flex-wrap items-center gap-2">
                <!-- Add Item Button -->
                <button @click="openAddItemModal = true" type="button" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Butir Penilaian
                </button>

                <!-- Sync CBT Button (Open Modal) -->
                <button @click="openCbtModal = true" type="button" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Tarik Nilai CBT
                </button>

                <!-- Sync LMS Button (Open Modal) -->
                <button @click="openLmsModal = true" type="button" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-purple-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Tarik Tugas dari LMS
                </button>
            </div>
        @endunless
    </div>

    <!-- Filters -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('erapor.buku-nilai.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Rombongan Belajar (Kelas)</label>
                <select name="kelas" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @foreach($classes as $c)
                        <option value="{{ $c }}" {{ $selectedClass == $c ? 'selected' : '' }}>
                            {{ $c }} {{ (isset($taughtClasses) && $taughtClasses->contains($c)) ? '★ (Kelas Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Mata Pelajaran</label>
                <select name="mata_pelajaran_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @foreach($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ $selectedSubjectId == $sb->id ? 'selected' : '' }}>
                            {{ $sb->nama }} {{ (isset($taughtSubjectIds) && $taughtSubjectIds->contains($sb->id)) ? '★ (Mapel Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Semester</label>
                <select name="semester" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="Ganjil" {{ $semester == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="Genap" {{ $semester == 'Genap' ? 'selected' : '' }}>Genap</option>
                </select>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Gradebook Matrix Table -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-3 px-3 text-center w-10">No</th>
                        <th class="py-3 px-3 min-w-[120px]">NISN</th>
                        <th class="py-3 px-4 min-w-[200px] sticky left-0 z-20 bg-gray-50 dark:bg-gray-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                            Nama Siswa
                        </th>
                        @foreach($items as $item)
                            <th class="py-3 px-2 text-center min-w-[70px]" title="{{ $item->title }}">
                                {{ $item->title }}
                            </th>
                        @endforeach
                        <th class="py-3 px-2 text-center bg-gray-100 dark:bg-gray-700/50 min-w-[70px]">Rata UH</th>
                        <th class="py-3 px-2 text-center bg-gray-100 dark:bg-gray-700/50 min-w-[70px]">Rata Tugas</th>
                        <th class="py-3 px-2 text-center bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 min-w-[70px]">STS 1</th>
                        <th class="py-3 px-2 text-center bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 min-w-[70px]">UAS</th>
                        <th class="py-3 px-2 text-center bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 min-w-[75px]">Nilai Akhir</th>
                        <th class="py-3 px-3 text-center min-w-[100px]">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($students as $idx => $siswa)
                        @php
                            $stScores = $scores[$siswa->id] ?? collect();
                            $scoreMap = $stScores->pluck('score', 'assessment_item_id');
                            $summary = $summaries[$siswa->id] ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                            <td class="py-2.5 px-3 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-mono text-gray-600 dark:text-gray-400">{{ $siswa->nisn }}</td>
                            <td class="py-2.5 px-4 font-semibold text-gray-900 dark:text-white sticky left-0 z-10 bg-white dark:bg-gray-900 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                                {{ $siswa->nama_lengkap }}
                            </td>
                            
                            @foreach($items as $item)
                                @php $val = $scoreMap[$item->id] ?? null; @endphp
                                <td class="py-2 px-1 text-center">
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           max="100" 
                                           value="{{ $val !== null ? $val : '' }}"
                                           placeholder="-"
                                           @if(auth()->user()?->isAkunDemo())
                                               readonly
                                               disabled
                                           @else
                                               @change="saveScore({{ $siswa->id }}, {{ $item->id }}, $event.target.value, $event.target)"
                                           @endif
                                           class="w-16 rounded-lg border border-gray-200 bg-white px-2 py-1 text-center text-xs font-mono focus:border-brand-500 focus:outline-none transition-colors duration-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white {{ auth()->user()?->isAkunDemo() ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : '' }}">
                                </td>
                            @endforeach

                            <td id="uh-avg-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-semibold font-mono text-gray-800 dark:text-gray-200 bg-gray-50/50 dark:bg-gray-800/30">
                                {{ $summary?->uh_avg !== null ? number_format($summary->uh_avg, 1) : '-' }}
                            </td>
                            <td id="tugas-avg-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-semibold font-mono text-gray-800 dark:text-gray-200 bg-gray-50/50 dark:bg-gray-800/30">
                                {{ $summary?->tugas_avg !== null ? number_format($summary->tugas_avg, 1) : '-' }}
                            </td>
                            <td id="sts-score-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-bold font-mono text-blue-600 dark:text-blue-400 bg-blue-50/30 dark:bg-blue-900/10">
                                {{ $summary?->sts_score !== null ? number_format($summary->sts_score, 1) : '-' }}
                            </td>
                            <td id="uas-score-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-bold font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-900/10">
                                {{ $summary?->uas_score !== null ? number_format($summary->uas_score, 1) : '-' }}
                            </td>
                            <td id="final-score-{{ $siswa->id }}" class="py-2.5 px-2 text-center font-bold font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50/30 dark:bg-emerald-900/10">
                                {{ $summary?->final_score !== null ? number_format($summary->final_score, 1) : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @php
                                    $statusBadge = match($summary?->status) {
                                        'siap_ditinjau', 'final', 'published' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                                        'belum_lengkap' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
                                        default => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
                                    };
                                    $statusLabel = match($summary?->status) {
                                        'siap_ditinjau', 'final', 'published' => 'Lengkap',
                                        'belum_lengkap' => 'Sebagian',
                                        default => 'Kosong',
                                    };
                                @endphp
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-full border {{ $statusBadge }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($items) + 9 }}" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada siswa di kelas {{ $selectedClass }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tarik dari LMS -->
    <div x-show="openLmsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div @click.away="openLmsModal = false" class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Tarik Nilai dari LMS E-Learning</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Pilih tugas yang nilainya ingin Anda petakan ke kolom Tugas E-Rapor.</p>
            
            <form action="{{ route('erapor.buku-nilai.sync-lms') }}" method="POST">
                @csrf
                <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject?->id }}">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tugas LMS Terkait</label>
                    <select name="tugas_id" required class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        @forelse($lmsTugasList as $t)
                            <option value="{{ $t->id }}">{{ $t->judul }} (Kelas: {{ $t->kelas }})</option>
                        @empty
                            <option value="" disabled>Belum ada tugas dibuat di mapel ini</option>
                        @endforelse
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Petakan ke Komponen</label>
                    <select name="sequence" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="1">Tugas 1</option>
                        <option value="2">Tugas 2</option>
                        <option value="3">Tugas 3</option>
                        <option value="4">Tugas 4</option>
                        <option value="5">Tugas 5</option>
                        <option value="6">Tugas 6</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="openLmsModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-purple-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-purple-700 shadow-sm transition">Tarik Nilai</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Butir Penilaian -->
    <div x-show="openAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div @click.away="openAddItemModal = false" class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Tambah Kolom / Butir Penilaian Baru</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Buat butir penilaian baru untuk mata pelajaran {{ $selectedSubject?->nama }} kelas {{ $selectedClass }}.</p>
            
            <form action="{{ route('erapor.buku-nilai.item.store') }}" method="POST">
                @csrf
                <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject?->id }}">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kategori Komponen</label>
                    <select name="assessment_component_id" required class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        @foreach($components as $comp)
                            @php
                                $weightLabel = in_array($comp->code, ['uh', 'tugas', 'praktek', 'projek', 'portofolio'])
                                    ? 'Kelompok Formatif: 30%'
                                    : ($comp->code === 'sts' ? 'Bobot UTS: 30%' : 'Bobot: ' . number_format($comp->default_weight, 0) . '%');
                            @endphp
                            <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $weightLabel }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Judul Butir Penilaian</label>
                    <input type="text" name="title" required placeholder="Contoh: UH 3 (Aljabar Lanjutan) / Remedial STS / SAS" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Skor Maksimal</label>
                    <input type="number" name="max_score" value="100" min="10" max="100" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="openAddItemModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 shadow-sm transition">Simpan Kolom</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tarik Nilai dari CBT -->
    <div x-show="openCbtModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div @click.away="openCbtModal = false" class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Tarik Nilai dari SMART-CBT</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Pilih ujian CBT yang ingin disinkronkan, tentukan kolom tujuan, serta strategi penilaian.</p>
            
            <form action="{{ route('erapor.buku-nilai.sync-cbt') }}" method="POST">
                @csrf
                <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedSubject?->id }}">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <input type="hidden" name="kelas" value="{{ $selectedClass }}">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Ujian di SMART-CBT</label>
                    <select name="cbt_exam_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">-- Seluruh Ujian Eligible Mapel Ini --</option>
                        @foreach($cbtExams as $exam)
                            <option value="{{ $exam['id'] }}">{{ $exam['nama'] }} [{{ $exam['kode_ujian'] }}] ({{ $exam['jenis'] }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Petakan ke Kolom Buku Nilai</label>
                    <select name="target_assessment_item_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Otomatis (Sesuai Target Komponen di Bawah)</option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}">{{ $it->title }} ({{ $it->component?->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Komponen (Jika Mode Otomatis)</label>
                    <select name="component_code" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="sts">STS 1 / UTS (Bobot 30%)</option>
                        <option value="uas">UAS / Akhir Semester (Bobot 40%)</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Strategi Sinkronisasi / Remedial</label>
                    <select name="sync_mode" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="replace">Ganti Nilai (Timpa semua nilai siswa dengan hasil ujian ini)</option>
                        <option value="highest">Remedial: Ambil Nilai Tertinggi (Bandingkan nilai lama & baru, ambil yang terbaik)</option>
                        <option value="fill_empty">Ujian Susulan: Hanya Isi Siswa Kosong (Jangan ubah nilai siswa yang sudah ada)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="openCbtModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-blue-700 shadow-sm transition">Mulai Tarik Nilai</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function bukuNilai() {
    return {
        openLmsModal: false,
        openCbtModal: false,
        openAddItemModal: false,
        saveScore(siswaId, itemId, scoreVal, targetEl) {
            fetch("{{ route('erapor.buku-nilai.update-score') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    siswa_id: siswaId,
                    assessment_item_id: itemId,
                    score: scoreVal !== "" ? parseFloat(scoreVal) : null
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (targetEl) {
                        targetEl.classList.add('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950/40');
                        setTimeout(() => {
                            targetEl.classList.remove('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950/40');
                        }, 1200);
                    }
                    if (data.summary) {
                        const uhEl = document.getElementById('uh-avg-' + siswaId);
                        const tugasEl = document.getElementById('tugas-avg-' + siswaId);
                        const stsEl = document.getElementById('sts-score-' + siswaId);
                        const uasEl = document.getElementById('uas-score-' + siswaId);
                        const finalEl = document.getElementById('final-score-' + siswaId);
                        if (uhEl) uhEl.textContent = data.summary.uh_avg !== null ? Number(data.summary.uh_avg).toFixed(1) : '-';
                        if (tugasEl) tugasEl.textContent = data.summary.tugas_avg !== null ? Number(data.summary.tugas_avg).toFixed(1) : '-';
                        if (stsEl) stsEl.textContent = data.summary.sts_score !== null ? Number(data.summary.sts_score).toFixed(1) : '-';
                        if (uasEl) uasEl.textContent = data.summary.uas_score !== null ? Number(data.summary.uas_score).toFixed(1) : '-';
                        if (finalEl) finalEl.textContent = data.summary.final_score !== null ? Number(data.summary.final_score).toFixed(1) : '-';
                    }
                } else {
                    alert('Gagal menyimpan nilai');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi');
            });
        }
    }
}
</script>
@endsection
