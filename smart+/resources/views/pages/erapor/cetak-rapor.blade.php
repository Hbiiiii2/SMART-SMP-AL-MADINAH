@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Pencetakan Dokumen Resmi
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">SMP Al Madinah</span>
            </div>
            <h2 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">Cetak Rapor Siswa & Rombel</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pilih Opsi 1 (Cetak Rapor STS) atau Opsi 2 (Cetak Rapor UAS / Akhir Semester) sesuai format kertas {{ $raporSettings->ukuran_kertas ?? 'A4' }}.
            </p>
        </div>

        <!-- Class Actions & Bulk Print -->
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('erapor.cetak.catatan-wali', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="h-4 w-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Input Catatan Wali Kelas
            </a>
            <a href="{{ route('erapor.capaian.index', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Capaian Kompetensi
            </a>
            @if(in_array(auth()->user()?->role, ['super_admin', 'admin_sekolah', 'kepala_sekolah']))
                <a href="{{ route('erapor.cetak.pengaturan', ['tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                    <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Format & TTD Rapor
                </a>
            @endif
            <a href="{{ route('erapor.cetak.kelas', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id, 'jenis_rapor' => 'sts']) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Semua Rapor STS ({{ $kelas }})
            </a>
            <a href="{{ route('erapor.cetak.kelas', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id, 'jenis_rapor' => 'uas']) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Semua Rapor UAS ({{ $kelas }})
            </a>
        </div>
    </div>

    <!-- Status Alert Bar -->
    @if($finalization)
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold">✓</span>
                <div>
                    <h4 class="text-sm font-bold">Nilai Kelas {{ $kelas }} Sudah Difinalisasi</h4>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Difinalisasi oleh {{ $finalization->finalizedBy?->name ?? 'Wali Kelas' }} pada {{ $finalization->finalized_at?->format('d F Y H:i') }}. Rapor resmi siap didistribusikan ke siswa & orang tua.</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-100 text-xs font-bold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                Status: Resmi / Siap Rilis
            </span>
        </div>
    @else
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-300">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white font-bold">!</span>
                <div>
                    <h4 class="text-sm font-bold">Kelas {{ $kelas }} Masih Dalam Status Draf (Belum Difinalisasi)</h4>
                    <p class="text-xs text-amber-700 dark:text-amber-400">Rapor tetap dapat dicetak untuk keperluan evaluasi atau verifikasi draf nilai sebelum pembagian resmi.</p>
                </div>
            </div>
            @if(in_array(auth()->user()?->role, ['super_admin', 'admin_sekolah', 'kepala_sekolah']))
                <a href="{{ route('erapor.recap.index', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800 hover:underline dark:text-amber-300">
                    Buka Rekap STS untuk Finalisasi &rarr;
                </a>
            @endif
        </div>
    @endif

    <!-- Filter Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('erapor.cetak.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                            {{ $y->nama }} {{ $y->is_aktif ? '(Aktif)' : '' }}
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

            <!-- Jenis Rapor -->
            <div>
                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">Mode Cetak Rapor</label>
                <select name="jenis_rapor" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <option value="sts" {{ $jenisRapor === 'sts' ? 'selected' : '' }}>Opsi 1: Rapor STS (Nilai STS & Capaian)</option>
                    <option value="uas" {{ $jenisRapor === 'uas' ? 'selected' : '' }}>Opsi 2: Rapor UAS (Skema Lengkap)</option>
                </select>
            </div>

            <!-- Action Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-800 py-2.5 px-4 text-xs font-semibold text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 transition shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Tampilkan Siswa
                </button>
            </div>
        </form>
    </div>

    <!-- Student List Table -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">
                    Daftar Siswa Kelas {{ $kelas }} — {{ $semester }} {{ $targetYear->nama }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total {{ $students->count() }} peserta didik terdaftar pada rombongan belajar ini.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    Kertas: <strong>{{ $raporSettings->ukuran_kertas ?? 'A4' }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                    Peringkat Cetak: <strong>{{ ($raporSettings->show_ranking ?? true) ? 'Aktif' : 'Nonaktif' }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    {{ $jenisRapor === 'sts' ? 'Opsi 1: Rapor STS' : 'Opsi 2: Rapor UAS' }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4 min-w-[240px] sticky left-0 bg-gray-50 dark:bg-gray-800/95 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">Nama Peserta Didik</th>
                        <th class="py-3.5 px-4 min-w-[140px]">NISN / Gender</th>
                        <th class="py-3.5 px-4 text-center min-w-[130px]">Nilai Rata-rata{{ $jenisRapor === 'uas' ? ' (UAS)' : '' }}</th>
                        <th class="py-3.5 px-4 text-center min-w-[110px]">Peringkat{{ $jenisRapor === 'uas' ? ' (UAS)' : '' }}</th>
                        <th class="py-3.5 px-4 text-center min-w-[140px]">Kelengkapan Nilai</th>
                        <th class="py-3.5 px-4 text-center min-w-[180px]">Aksi Cetak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($students as $idx => $s)
                        @php
                            $sts = $stsSummaries->get($s->id);
                            $useUas = ($jenisRapor === 'uas');
                            $completed = $useUas
                                ? ($sts?->uas_completed_subjects_count ?? $sts?->completed_subjects_count ?? 0)
                                : ($sts?->completed_subjects_count ?? 0);
                            $avg = $useUas
                                ? ($sts?->uas_average_score ?? $sts?->average_score)
                                : $sts?->average_score;
                            $rank = $useUas
                                ? ($sts?->uas_rank ?? $sts?->rank)
                                : $sts?->rank;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                            <td class="py-3 px-4 text-center text-xs text-gray-400 dark:text-gray-500 font-mono">
                                {{ $idx + 1 }}
                            </td>
                            <td class="py-3 px-4 sticky left-0 bg-white dark:bg-gray-900 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] dark:shadow-[2px_0_5px_-2px_rgba(0,0,0,0.4)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300 font-bold text-xs">
                                        {{ strtoupper(substr($s->nama_lengkap, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ $s->nama_lengkap }}
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            Kelas: <span class="font-medium text-gray-600 dark:text-gray-300">{{ $s->kelas }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-mono text-xs font-medium text-gray-700 dark:text-gray-300">{{ $s->nisn }}</div>
                                <div class="text-[11px] text-gray-400 dark:text-gray-500">{{ $s->jenis_kelamin === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' }}</div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($avg !== null)
                                    <span class="inline-flex items-center justify-center font-bold font-mono text-sm {{ $avg >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($avg >= 75 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400') }}">
                                        {{ number_format($avg, 1) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($rank)
                                    <span class="inline-flex items-center rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                        Ke-{{ $rank }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center gap-1 text-xs font-mono {{ $completed >= $totalSubjectsCount && $totalSubjectsCount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $completed }} / {{ $totalSubjectsCount }} Mapel
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Opsi 1: Print Single Student STS -->
                                    <a href="{{ route('erapor.cetak.siswa', ['siswa' => $s->id, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id, 'jenis_rapor' => 'sts']) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition shadow-sm" title="Opsi 1: Cetak Rapor STS (Hanya Nilai STS & Capaian Kompetensi)">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Cetak STS
                                    </a>
                                    <!-- Opsi 2: Print Single Student UAS -->
                                    <a href="{{ route('erapor.cetak.siswa', ['siswa' => $s->id, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id, 'jenis_rapor' => 'uas']) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition shadow-sm" title="Opsi 2: Cetak Rapor UAS (Skema Penilaian Lengkap)">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Cetak UAS
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-400 dark:text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Tidak ada siswa terdaftar pada kelas {{ $kelas }}.</p>
                                <p class="text-xs mt-1 text-gray-400 dark:text-gray-500">Pilih rombel lain dari dropdown di atas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
