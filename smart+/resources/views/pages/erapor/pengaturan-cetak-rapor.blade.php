@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('erapor.cetak.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                    &larr; Kembali ke Cetak Rapor
                </a>
                <span class="text-xs text-gray-400">•</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    Konfigurasi Cetak
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">SMP Al Madinah</span>
            </div>
            <h2 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">Pengaturan Format Rapor & Wali Kelas</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kustomisasi kurikulum, sembunyikan/tampilkan fase, atur nama wali kelas, kepala sekolah, serta upload tanda tangan digital (TTD) & stempel.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('erapor.cetak.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-brand-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Buka Cetak Rapor
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 flex items-center justify-between gap-3 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold text-sm">✓</span>
                <p class="text-sm font-semibold">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">&times;</button>
        </div>
    @endif

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-300">
            <p class="text-xs font-bold uppercase mb-1">Terjadi kesalahan validasi:</p>
            <ul class="list-disc pl-5 text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- Section 1: Dokumen & Pengesahan Kepala Sekolah (Left Column) -->
        <div class="xl:col-span-5 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-100 pb-4 mb-5 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Format Lembar Rapor
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Sesuaikan format kurikulum, opsi fase, kop surat, dan pengesahan kepala sekolah.
                    </p>
                </div>

                <form method="POST" action="{{ route('erapor.cetak.update-pengaturan') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $targetYear->id }}">

                    <!-- Nama Kurikulum & Tampilkan -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                            Nama Kurikulum
                        </label>
                        <input
                            type="text"
                            name="nama_kurikulum"
                            value="{{ old('nama_kurikulum', $raporSettings->nama_kurikulum) }}"
                            class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            placeholder="Contoh: Kurikulum Merdeka / Kurikulum 2013"
                        >
                        <div class="mt-2 flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="show_kurikulum"
                                id="show_kurikulum"
                                value="1"
                                {{ old('show_kurikulum', $raporSettings->show_kurikulum) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                            >
                            <label for="show_kurikulum" class="text-xs text-gray-600 dark:text-gray-300">
                                Tampilkan nama kurikulum pada judul dokumen rapor
                            </label>
                        </div>
                    </div>

                    <!-- Pengaturan Judul Dokumen Rapor (Template vs Custom) -->
                    <div class="p-3.5 rounded-xl border border-sky-200 bg-sky-50/50 dark:border-sky-900/40 dark:bg-sky-950/20">
                        <label class="block text-xs font-bold text-gray-800 dark:text-white mb-1 flex items-center justify-between">
                            <span>Format Judul Dokumen Rapor</span>
                            <span class="text-[10px] text-sky-600 dark:text-sky-400 font-semibold uppercase">Header Rapor</span>
                        </label>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-2.5">
                            Pilih apakah ingin menggunakan judul bawaan (template) atau kustomisasi teks judul sendiri.
                        </p>

                        @php
                            $currentTipeJudul = old('tipe_judul_rapor', $raporSettings->tipe_judul_rapor ?? 'template');
                        @endphp

                        <!-- Opsi Radio Template vs Custom -->
                        <div class="grid grid-cols-2 gap-2 mb-2.5">
                            <label class="flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition text-xs font-semibold {{ $currentTipeJudul === 'template' ? 'border-brand-500 bg-brand-50/80 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300' }}" id="label_tipe_template">
                                <input
                                    type="radio"
                                    name="tipe_judul_rapor"
                                    value="template"
                                    id="radio_tipe_template"
                                    {{ $currentTipeJudul === 'template' ? 'checked' : '' }}
                                    onchange="toggleJudulRaporMode('template')"
                                    class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                                >
                                <span>Template Standar</span>
                            </label>

                            <label class="flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition text-xs font-semibold {{ $currentTipeJudul === 'custom' ? 'border-brand-500 bg-brand-50/80 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300' }}" id="label_tipe_custom">
                                <input
                                    type="radio"
                                    name="tipe_judul_rapor"
                                    value="custom"
                                    id="radio_tipe_custom"
                                    {{ $currentTipeJudul === 'custom' ? 'checked' : '' }}
                                    onchange="toggleJudulRaporMode('custom')"
                                    class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                                >
                                <span>Custom Judul</span>
                            </label>
                        </div>

                        <!-- Preview Saat Mode Template -->
                        <div id="judul_template_box" class="rounded-xl border border-dashed border-gray-300 bg-white/70 p-2.5 text-[11px] text-gray-600 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-400 space-y-1" style="{{ $currentTipeJudul === 'template' ? '' : 'display: none;' }}">
                            <div>• <strong>Rapor UTS / STS:</strong> <span class="font-mono text-gray-800 dark:text-gray-200">LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS)</span></div>
                            <div>• <strong>Rapor Akhir / UAS:</strong> <span class="font-mono text-gray-800 dark:text-gray-200">LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK</span></div>
                        </div>

                        <!-- Form Input Saat Mode Custom -->
                        <div id="judul_custom_box" class="space-y-3 pt-1" style="{{ $currentTipeJudul === 'custom' ? '' : 'display: none;' }}">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                    Judul Rapor Tengah Semester (STS / PTS / UTS)
                                </label>
                                <input
                                    type="text"
                                    name="judul_rapor_sts"
                                    value="{{ old('judul_rapor_sts', $raporSettings->judul_rapor_sts ?? 'LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS)') }}"
                                    class="w-full rounded-xl border border-gray-300 p-2 text-xs font-medium dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                    placeholder="LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS)"
                                >
                                <p class="text-[10px] text-gray-400 mt-0.5">Contoh: LAPORAN PENILAIAN SUMATIF TENGAH SEMESTER (STS), ASESMEN SUMATIF TENGAH SEMESTER (ASTS), dll.</p>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                    Judul Rapor Akhir Semester (UAS / Semester)
                                </label>
                                <input
                                    type="text"
                                    name="judul_rapor_akhir"
                                    value="{{ old('judul_rapor_akhir', $raporSettings->judul_rapor_akhir ?? 'LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK') }}"
                                    class="w-full rounded-xl border border-gray-300 p-2 text-xs font-medium dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                    placeholder="LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK"
                                >
                                <p class="text-[10px] text-gray-400 mt-0.5">Contoh: LAPORAN CAPAIAN HASIL BELAJAR PESERTA DIDIK, LAPORAN HASIL BELAJAR (RAPOR AKHIR), dll.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Fase (Disable by default per request) -->
                    <div class="p-3.5 rounded-xl border border-amber-200 bg-amber-50/50 dark:border-amber-900/40 dark:bg-amber-950/20">
                        <div class="flex items-start gap-2.5">
                            <input
                                type="checkbox"
                                name="show_fase"
                                id="show_fase"
                                value="1"
                                {{ old('show_fase', $raporSettings->show_fase) ? 'checked' : '' }}
                                onchange="document.getElementById('fase_input_box').style.display = this.checked ? 'block' : 'none'"
                                class="mt-0.5 h-4 w-4 rounded border-amber-300 text-brand-600 focus:ring-brand-500 dark:border-amber-700 dark:bg-gray-800"
                            >
                            <div>
                                <label for="show_fase" class="text-xs font-bold text-gray-800 dark:text-white cursor-pointer">
                                    Tampilkan Elemen "Fase D" pada Biodata Siswa
                                </label>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    Bila dinonaktifkan (default), biodata hanya akan mencetak <strong>Kelas: 7-A</strong> tanpa ada tulisan <em>"/ Fase D"</em>.
                                </p>
                            </div>
                        </div>

                        <div id="fase_input_box" class="mt-3 pl-6" style="{{ old('show_fase', $raporSettings->show_fase) ? '' : 'display: none;' }}">
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                Teks Label Fase (Opsional)
                            </label>
                            <input
                                type="text"
                                name="fase_text"
                                value="{{ old('fase_text', $raporSettings->fase_text ?? 'Fase D') }}"
                                class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                placeholder="Fase D"
                            >
                        </div>
                    </div>

                    <!-- Pilihan Ukuran Kertas & Opsi Peringkat -->
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                Ukuran Kertas Cetak Rapor
                            </label>
                            @php
                                $selectedPaper = old('ukuran_kertas', $raporSettings->ukuran_kertas ?? 'A4');
                            @endphp
                            <select
                                name="ukuran_kertas"
                                class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white font-medium"
                            >
                                <option value="A4" {{ $selectedPaper === 'A4' ? 'selected' : '' }}>Kertas A4 (210 x 297 mm)</option>
                                <option value="F4" {{ $selectedPaper === 'F4' ? 'selected' : '' }}>Kertas F4 / Folio (215 x 330 mm)</option>
                                <option value="Letter" {{ $selectedPaper === 'Letter' ? 'selected' : '' }}>Kertas Letter / Kuarto (216 x 279 mm)</option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Ukuran kertas ini otomatis diterapkan pada saat mencetak Rapor STS maupun Rapor UAS.</p>
                        </div>

                        <!-- Opsi Tampilkan Peringkat -->
                        <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/50 dark:border-purple-900/40 dark:bg-purple-950/20">
                            <div class="flex items-start gap-2.5">
                                <input
                                    type="checkbox"
                                    name="show_ranking"
                                    id="show_ranking"
                                    value="1"
                                    {{ old('show_ranking', $raporSettings->show_ranking ?? true) ? 'checked' : '' }}
                                    class="mt-0.5 h-4 w-4 rounded border-purple-300 text-brand-600 focus:ring-brand-500 dark:border-purple-700 dark:bg-gray-800"
                                >
                                <div>
                                    <label for="show_ranking" class="text-xs font-bold text-gray-800 dark:text-white cursor-pointer">
                                        Tampilkan Peringkat Siswa pada Hasil Cetak Rapor
                                    </label>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Jika dicentang, baris rata-rata nilai akan menampilkan keterangan <strong>"Peringkat ke-X dari Y siswa"</strong>. Jika tidak dicentang, keterangan peringkat disembunyikan pada lembar rapor.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Opsi Tampilkan KKM (Kriteria Ketuntasan Minimal) -->
                        <div class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                            <div class="flex items-start gap-2.5">
                                <input
                                    type="checkbox"
                                    name="show_kkm"
                                    id="show_kkm"
                                    value="1"
                                    {{ old('show_kkm', $raporSettings->show_kkm ?? false) ? 'checked' : '' }}
                                    onchange="document.getElementById('kkm_config_box').style.display = this.checked ? 'block' : 'none'"
                                    class="mt-0.5 h-4 w-4 rounded border-emerald-300 text-brand-600 focus:ring-brand-500 dark:border-emerald-700 dark:bg-gray-800"
                                >
                                <div class="w-full">
                                    <label for="show_kkm" class="text-xs font-bold text-gray-800 dark:text-white cursor-pointer">
                                        Tampilkan Kolom KKM pada Cetak Rapor UTS
                                    </label>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Jika dicentang, kolom <strong>KKM</strong> akan ditampilkan tepat di sebelah setelah nama mata pelajaran pada hasil Cetak Rapor format UTS. Nilai KKM diatur secara khusus <strong>per kelas</strong>.
                                    </p>
                                </div>
                            </div>

                            <!-- Panel Konfigurasi Nilai KKM -->
                            <div id="kkm_config_box" class="mt-3 pl-6 border-t border-emerald-200/60 pt-3 dark:border-emerald-900/40" style="{{ old('show_kkm', $raporSettings->show_kkm ?? false) ? '' : 'display: none;' }}">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                        Standar KKM Default Global (Fallback)
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="number"
                                            name="default_kkm"
                                            step="0.5"
                                            min="0"
                                            max="100"
                                            value="{{ old('default_kkm', $raporSettings->default_kkm ?? 75) }}"
                                            class="w-24 rounded-xl border border-gray-300 p-2 text-xs font-mono dark:bg-gray-800 dark:border-gray-700 dark:text-white font-semibold text-center"
                                            placeholder="75"
                                        >
                                        <span class="text-[11px] text-gray-500">Nilai cadangan jika suatu kelas belum diatur KKM-nya.</span>
                                    </div>
                                </div>

                                @if($classes->isNotEmpty())
                                    <div class="mt-3 rounded-xl border border-emerald-200/80 bg-white/70 p-3 dark:border-emerald-900/50 dark:bg-gray-900/50">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                                Quick Edit: KKM Standar Per Kelas
                                            </span>
                                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">T.A. {{ $targetYear->tahun }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            @foreach($classes as $c)
                                                @php
                                                    $hr = $homerooms->get($c);
                                                    $valKkm = old('class_kkm.'.$c, $hr?->kkm_default ?? $raporSettings->default_kkm ?? 75);
                                                @endphp
                                                <div class="flex items-center justify-between gap-1.5 p-1.5 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/60">
                                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $c }}</span>
                                                    <input
                                                        type="number"
                                                        name="class_kkm[{{ $c }}]"
                                                        step="0.5"
                                                        min="0"
                                                        max="100"
                                                        value="{{ $valKkm }}"
                                                        class="w-14 rounded border border-gray-300 px-1.5 py-0.5 text-xs text-center font-mono font-bold text-brand-600 dark:bg-gray-700 dark:border-gray-600 dark:text-brand-400"
                                                        title="KKM Kelas {{ $c }}"
                                                    >
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-2">
                                            *Untuk mengatur KKM per mata pelajaran di rombel tertentu, gunakan formulir di panel kanan.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Titimangsa: Tempat & Tanggal Rapor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                Kota Penerbitan Rapor
                            </label>
                            <input
                                type="text"
                                name="kota_pembagian"
                                value="{{ old('kota_pembagian', $raporSettings->kota_pembagian) }}"
                                class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                placeholder="Contoh: Bogor"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1">
                                Tanggal Dokumen Rapor
                            </label>
                            <input
                                type="date"
                                name="tanggal_rapor"
                                value="{{ old('tanggal_rapor', $raporSettings->tanggal_rapor?->format('Y-m-d')) }}"
                                class="w-full rounded-xl border border-gray-300 p-2 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            >
                            <p class="text-[10px] text-gray-400 mt-1">Kosongkan jika ingin otomatis tanggal hari cetak.</p>
                        </div>
                    </div>

                    <!-- Kepala Sekolah & TTD -->
                    <div class="border-t border-gray-100 pt-4 dark:border-gray-800 space-y-3">
                        <h4 class="text-xs font-bold uppercase text-gray-600 dark:text-gray-300">Pengesahan Kepala Sekolah</h4>
                        
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Nama Kepala Sekolah (dengan Gelar)</label>
                            <input
                                type="text"
                                name="nama_kepala_sekolah"
                                value="{{ old('nama_kepala_sekolah', $raporSettings->nama_kepala_sekolah ?? $defaultKepalaSekolah?->name) }}"
                                class="w-full rounded-xl border border-gray-300 p-2 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                placeholder="Nama Kepala Sekolah"
                            >
                        </div>

                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">NIP Kepala Sekolah</label>
                            <input
                                type="text"
                                name="nip_kepala_sekolah"
                                value="{{ old('nip_kepala_sekolah', $raporSettings->nip_kepala_sekolah ?? $defaultKepalaSekolah?->guru?->nip) }}"
                                class="w-full rounded-xl border border-gray-300 p-2 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                placeholder="NIP Kepala Sekolah atau '-'"
                            >
                        </div>

                        <!-- Upload TTD & Stempel -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">TTD Digital Kepala Sekolah</label>
                                <input
                                    type="file"
                                    name="ttd_kepala_sekolah"
                                    accept="image/*"
                                    class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-800 dark:file:text-brand-300"
                                >
                                @if(!empty($raporSettings->ttd_kepala_sekolah))
                                    <div class="mt-2 p-1.5 border border-dashed rounded-lg inline-block bg-gray-50 dark:bg-gray-800">
                                        <img src="{{ asset('storage/' . $raporSettings->ttd_kepala_sekolah) }}" alt="TTD Kepsek" class="h-9 max-w-[100px] object-contain">
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Stempel Sekolah (PNG Transparan)</label>
                                <input
                                    type="file"
                                    name="stempel_sekolah"
                                    accept="image/*"
                                    class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-800 dark:file:text-brand-300"
                                >
                                @if(!empty($raporSettings->stempel_sekolah))
                                    <div class="mt-2 p-1.5 border border-dashed rounded-lg inline-block bg-gray-50 dark:bg-gray-800">
                                        <img src="{{ asset('storage/' . $raporSettings->stempel_sekolah) }}" alt="Stempel" class="h-9 max-w-[100px] object-contain">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Kop Surat Yayasan / Sekolah -->
                    <div class="border-t border-gray-100 pt-4 dark:border-gray-800 space-y-3">
                        <h4 class="text-xs font-bold uppercase text-gray-600 dark:text-gray-300">Teks Kop Surat (Opsional)</h4>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Baris 1: Nama Yayasan</label>
                            <input
                                type="text"
                                name="header_yayasan"
                                value="{{ old('header_yayasan', $raporSettings->header_yayasan) }}"
                                class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Baris 2: Nama Sekolah</label>
                            <input
                                type="text"
                                name="header_sekolah"
                                value="{{ old('header_sekolah', $raporSettings->header_sekolah) }}"
                                class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            >
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-brand-700 transition">
                            Simpan Format Lembar Rapor
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 2: Pemetaan Wali Kelas, KKM & TTD per Rombel (Right Column) -->
        <div class="xl:col-span-7 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-100 pb-4 mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Daftar Wali Kelas, KKM & TTD per Rombel
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Atur wali kelas, nilai KKM khusus per kelas / mapel, serta tanda tangan digital per rombel untuk T.A. <strong>{{ $targetYear->tahun }}</strong>.
                        </p>
                    </div>

                    <!-- Switch Year Form -->
                    <form method="GET" action="{{ route('erapor.cetak.pengaturan') }}" class="shrink-0">
                        <select name="tahun_ajaran_id" onchange="this.form.submit()" class="rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white font-semibold">
                            @foreach($allYears as $y)
                                <option value="{{ $y->id }}" {{ $targetYear->id == $y->id ? 'selected' : '' }}>
                                    T.A. {{ $y->tahun }} {{ $y->is_aktif ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if($classes->isNotEmpty())
                    <!-- Quick Tool KKM Toolbar -->
                    <div class="mb-4 p-3 rounded-xl border border-emerald-200/80 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/20 flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300">Set KKM Cepat:</span>
                            <input
                                type="number"
                                id="quick_kkm_input"
                                step="0.5"
                                min="0"
                                max="100"
                                value="75"
                                class="w-16 rounded-lg border border-emerald-300 px-2 py-1 text-xs text-center font-mono font-bold text-emerald-900 dark:bg-gray-800 dark:border-emerald-700 dark:text-emerald-200"
                            >
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                onclick="applyQuickKkm('all')"
                                class="rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-700 transition"
                            >
                                Ke Semua Kelas
                            </button>
                            <button
                                type="button"
                                onclick="applyQuickKkm('7')"
                                class="rounded-lg border border-emerald-300 bg-white px-2 py-1 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-50 dark:bg-gray-800 dark:border-emerald-700 dark:text-emerald-300"
                            >
                                Kelas 7
                            </button>
                            <button
                                type="button"
                                onclick="applyQuickKkm('8')"
                                class="rounded-lg border border-emerald-300 bg-white px-2 py-1 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-50 dark:bg-gray-800 dark:border-emerald-700 dark:text-emerald-300"
                            >
                                Kelas 8
                            </button>
                            <button
                                type="button"
                                onclick="applyQuickKkm('9')"
                                class="rounded-lg border border-emerald-300 bg-white px-2 py-1 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-50 dark:bg-gray-800 dark:border-emerald-700 dark:text-emerald-300"
                            >
                                Kelas 9
                            </button>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('erapor.cetak.update-homerooms') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $targetYear->id }}">

                    <div class="space-y-4">
                        @forelse($classes as $c)
                            @php
                                $homeroom = $homerooms->get($c);
                                $kkmClassVal = old("homerooms.{$c}.kkm_default", $homeroom?->kkm_default ?? $raporSettings->default_kkm ?? 75);
                            @endphp
                            <div class="p-4 rounded-2xl border border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-white/[0.01]">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200/60 pb-3 mb-3 dark:border-gray-800">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-brand-600 text-white text-xs font-bold">
                                            Kelas {{ $c }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 text-[11px] font-bold dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            KKM Kelas: <span id="badge_kkm_{{ $c }}">{{ $kkmClassVal }}</span>
                                        </span>
                                    </div>
                                    <div>
                                        @if(!empty($homeroom?->ttd_wali_kelas))
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                                ✓ TTD Digital Tersimpan
                                            </span>
                                        @else
                                            <span class="text-[11px] text-gray-400">TTD Belum Diupload (akan mencetak ruang ttd basah)</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                    <!-- Dropdown Guru (4 cols) -->
                                    <div class="md:col-span-4">
                                        <label class="block text-[11px] font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                            Pilih Akun Guru
                                        </label>
                                        <select
                                            name="homerooms[{{ $c }}][guru_id]"
                                            onchange="fillGuruDetails('{{ $c }}', this)"
                                            class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                        >
                                            <option value="">-- Pilih Guru Wali Kelas --</option>
                                            @foreach($teachers as $t)
                                                <option
                                                    value="{{ $t->id }}"
                                                    data-nama="{{ $t->nama_lengkap }}"
                                                    data-nip="{{ $t->nip ?? '-' }}"
                                                    {{ ($homeroom?->guru_id == $t->id) ? 'selected' : '' }}
                                                >
                                                    {{ $t->nama_lengkap }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Nama Lengkap Wali Kelas (3 cols) -->
                                    <div class="md:col-span-3">
                                        <label class="block text-[11px] font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                            Nama Cetak Rapor
                                        </label>
                                        <input
                                            type="text"
                                            id="nama_{{ $c }}"
                                            name="homerooms[{{ $c }}][nama_wali_kelas]"
                                            value="{{ old("homerooms.{$c}.nama_wali_kelas", $homeroom?->nama_wali_kelas ?: $homeroom?->guru?->nama_lengkap) }}"
                                            placeholder="Nama lengkap + gelar"
                                            class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                        >
                                    </div>

                                    <!-- NIP Wali Kelas (2 cols) -->
                                    <div class="md:col-span-2">
                                        <label class="block text-[11px] font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                            NIP / NUPTK
                                        </label>
                                        <input
                                            type="text"
                                            id="nip_{{ $c }}"
                                            name="homerooms[{{ $c }}][nip_wali_kelas]"
                                            value="{{ old("homerooms.{$c}.nip_wali_kelas", $homeroom?->nip_wali_kelas ?: $homeroom?->guru?->nip) }}"
                                            placeholder="NIP atau '-'"
                                            class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                        >
                                    </div>

                                    <!-- KKM Standar Kelas (3 cols) -->
                                    <div class="md:col-span-3">
                                        <label class="block text-[11px] font-bold uppercase text-emerald-700 dark:text-emerald-400 mb-1 flex items-center justify-between">
                                            <span>KKM Kelas {{ $c }}</span>
                                            <span class="text-[10px] text-gray-400 font-normal">UTS</span>
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            max="100"
                                            id="kkm_default_{{ $c }}"
                                            name="homerooms[{{ $c }}][kkm_default]"
                                            value="{{ $kkmClassVal }}"
                                            oninput="document.getElementById('badge_kkm_{{ $c }}').textContent = this.value"
                                            placeholder="75"
                                            class="w-full rounded-xl border-2 border-emerald-300 bg-white p-2 text-xs font-mono font-bold text-emerald-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-emerald-700 dark:bg-gray-800 dark:text-emerald-200"
                                            data-kelas="{{ $c }}"
                                        >
                                    </div>
                                </div>

                                <!-- Accordion: KKM Khusus Per Mapel pada Kelas Ini (Opsional) -->
                                @if(isset($subjects) && $subjects->isNotEmpty())
                                    @php
                                        $classMapelKkm = (array) ($homeroom?->kkm_mapel ?? []);
                                        $hasCustomSubjectKkm = !empty(array_filter($classMapelKkm, fn($v) => $v !== null && $v !== ''));
                                    @endphp
                                    <div class="mt-3 pt-2.5 border-t border-gray-200/50 dark:border-gray-800/60">
                                        <button
                                            type="button"
                                            onclick="toggleSubjectKkm('{{ $c }}')"
                                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 transition"
                                        >
                                            <svg id="icon_toggle_kkm_{{ $c }}" class="h-3.5 w-3.5 transform transition-transform {{ $hasCustomSubjectKkm ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            <span>Atur KKM Mapel Tertentu di Kelas {{ $c }} (Opsional)</span>
                                            @if($hasCustomSubjectKkm)
                                                <span class="rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.5 text-[10px] font-bold">Ada Kustomisasi Mapel</span>
                                            @endif
                                        </button>

                                        <div id="panel_subject_kkm_{{ $c }}" class="mt-2.5 p-3 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900/60" style="{{ $hasCustomSubjectKkm ? '' : 'display: none;' }}">
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-2">
                                                Kosongkan jika mata pelajaran mengikuti KKM Kelas ({{ $kkmClassVal }}). Isi hanya jika ada mata pelajaran tertentu yang memiliki target KKM berbeda di Kelas {{ $c }}.
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1">
                                                @foreach($subjects as $sbj)
                                                    @php
                                                        $sbjVal = old("homerooms.{$c}.kkm_mapel.{$sbj->id}", $classMapelKkm[$sbj->id] ?? null);
                                                    @endphp
                                                    <div class="flex items-center justify-between text-xs p-1.5 rounded-lg border border-gray-100 bg-gray-50/70 hover:bg-gray-100/60 dark:border-gray-800 dark:bg-gray-800/40">
                                                        <span class="truncate pr-1 text-[11px] font-medium text-gray-700 dark:text-gray-300" title="{{ $sbj->nama }}">
                                                            {{ $sbj->nama }}
                                                        </span>
                                                        <input
                                                            type="number"
                                                            step="0.5"
                                                            min="0"
                                                            max="100"
                                                            name="homerooms[{{ $c }}][kkm_mapel][{{ $sbj->id }}]"
                                                            value="{{ $sbjVal }}"
                                                            placeholder="Auto"
                                                            class="w-14 rounded border border-gray-300 px-1 py-0.5 text-center text-[11px] font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                        >
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Upload TTD File -->
                                <div class="mt-3 pt-3 border-t border-gray-200/50 dark:border-gray-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($homeroom?->ttd_wali_kelas))
                                            <div class="p-1 border border-dashed rounded-lg bg-white dark:bg-gray-800 shrink-0">
                                                <img src="{{ asset('storage/' . $homeroom->ttd_wali_kelas) }}" alt="TTD {{ $c }}" class="h-8 max-w-[110px] object-contain">
                                            </div>
                                        @endif
                                        <div>
                                            <label class="block text-[11px] text-gray-500 dark:text-gray-400">
                                                Upload Gambar Tanda Tangan (PNG Transparan direkomendasikan)
                                            </label>
                                            <input
                                                type="file"
                                                name="homerooms[{{ $c }}][ttd_file]"
                                                accept="image/*"
                                                class="mt-1 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-800 dark:file:text-brand-300"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Tidak ada rombel/kelas ditemukan pada data siswa.</p>
                        @endforelse
                    </div>

                    @if($classes->isNotEmpty())
                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Simpan Semua Wali Kelas, KKM & TTD
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function fillGuruDetails(kelas, selectElement) {
        const option = selectElement.options[selectElement.selectedIndex];
        const namaInput = document.getElementById('nama_' + kelas);
        const nipInput = document.getElementById('nip_' + kelas);

        if (option && option.value) {
            const nama = option.getAttribute('data-nama');
            const nip = option.getAttribute('data-nip');
            if (namaInput && (!namaInput.value || confirm('Perbarui Nama Cetak dengan ' + nama + '?'))) {
                namaInput.value = nama;
            }
            if (nipInput && (!nipInput.value || nipInput.value === '-')) {
                nipInput.value = (nip && nip !== '-') ? nip : '-';
            }
        }
    }

    function toggleSubjectKkm(kelas) {
        const panel = document.getElementById('panel_subject_kkm_' + kelas);
        const icon = document.getElementById('icon_toggle_kkm_' + kelas);
        if (!panel) return;
        if (panel.style.display === 'none' || panel.style.display === '') {
            panel.style.display = 'block';
            if (icon) icon.classList.add('rotate-180');
        } else {
            panel.style.display = 'none';
            if (icon) icon.classList.remove('rotate-180');
        }
    }

    function applyQuickKkm(level) {
        const quickInput = document.getElementById('quick_kkm_input');
        const val = quickInput ? quickInput.value : '';
        if (!val) {
            alert('Masukkan angka KKM terlebih dahulu!');
            return;
        }

        const inputs = document.querySelectorAll('input[id^="kkm_default_"]');
        let count = 0;
        inputs.forEach(input => {
            const kelas = input.getAttribute('data-kelas');
            if (!kelas) return;

            let match = false;
            if (level === 'all') {
                match = true;
            } else if (kelas.startsWith(level)) {
                match = true;
            }

            if (match) {
                input.value = val;
                const badge = document.getElementById('badge_kkm_' + kelas);
                if (badge) badge.textContent = val;
                const leftInput = document.querySelector(`input[name="class_kkm[${kelas}]"]`);
                if (leftInput) leftInput.value = val;
                count++;
            }
        });

        alert(`KKM ${val} berhasil diterapkan ke ${count} rombel. Jangan lupa klik 'Simpan Semua Wali Kelas, KKM & TTD'!`);
    }

    function toggleJudulRaporMode(mode) {
        const templateBox = document.getElementById('judul_template_box');
        const customBox = document.getElementById('judul_custom_box');
        const labelTemplate = document.getElementById('label_tipe_template');
        const labelCustom = document.getElementById('label_tipe_custom');

        if (mode === 'custom') {
            if (templateBox) templateBox.style.display = 'none';
            if (customBox) customBox.style.display = 'block';
            if (labelCustom) {
                labelCustom.className = 'flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition text-xs font-semibold border-brand-500 bg-brand-50/80 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300';
            }
            if (labelTemplate) {
                labelTemplate.className = 'flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition text-xs font-semibold border-gray-200 bg-white text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300';
            }
        } else {
            if (templateBox) templateBox.style.display = 'block';
            if (customBox) customBox.style.display = 'none';
            if (labelTemplate) {
                labelTemplate.className = 'flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition text-xs font-semibold border-brand-500 bg-brand-50/80 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300';
            }
            if (labelCustom) {
                labelCustom.className = 'flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition text-xs font-semibold border-gray-200 bg-white text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300';
            }
        }
    }
</script>
@endsection
