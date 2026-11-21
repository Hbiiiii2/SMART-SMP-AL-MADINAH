@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Integrasi Hasil Ujian CBT & Fallback Excel</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Tarik data nilai ujian dari server SMART-CBT secara otomatis atau impor file Excel hasil ekspor CBT.
            </p>
        </div>
        <div>
            @if($cbtHealth)
                <span class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    API CBT Terhubung ({{ $cbtInfo['version'] ?? 'v1' }})
                </span>
            @else
                <span class="inline-flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    API CBT Tidak Terjangkau (Gunakan Impor Excel)
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-800 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Opsi 1: Tarik Langsung via REST API CBT -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Tarik Otomatis via REST API CBT</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sinkronisasi langsung antar server tanpa unduh file.</p>
                </div>
            </div>

            <form action="{{ route('erapor.cbt.sync-api') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="tahun_ajaran_id" value="{{ $activeYear->id ?? 1 }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Penilaian E-Rapor</label>
                        <select name="component_code" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="sts">STS 1 / UTS (Bobot 30%)</option>
                            <option value="uas">UAS / Akhir Semester (Bobot 40%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Semester</label>
                        <select name="semester" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>

                @if(!empty($cbtExams))
                    @php
                        $cbtExamItems = collect($cbtExams)->map(function ($exam) {
                            $mapelLabel = $exam['mata_pelajaran']['nama'] ?? '-';
                            $kelasLabel = $exam['kelas']['nama_kelas'] ?? '-';
                            $pesertaCount = (int) ($exam['total_peserta'] ?? 0);
                            $hasPesertaField = array_key_exists('total_peserta', $exam);
                            $pesertaLabel = $hasPesertaField
                                ? ($pesertaCount > 0 ? " • {$pesertaCount} peserta" : ' • Belum ujian')
                                : '';

                            return [
                                'id' => $exam['id'],
                                'label' => ($exam['nama'] ?? '-') . ' [' . ($exam['kode_ujian'] ?? '-') . '] • ' . $mapelLabel . ' • ' . $kelasLabel . $pesertaLabel,
                                'peserta_count' => $pesertaCount,
                                'search' => strtolower(trim(($exam['nama'] ?? '') . ' ' . ($exam['kode_ujian'] ?? '') . ' ' . $mapelLabel . ' ' . $kelasLabel)),
                            ];
                        })->values()->all();
                    @endphp
                    <div
                        x-data="{
                            examFilter: '',
                            examExecution: 'all',
                            selectedExamId: '',
                            exams: @js($cbtExamItems),
                            get filteredExams() {
                                const q = this.examFilter.trim().toLowerCase();
                                return this.exams.filter((item) => {
                                    if (this.examExecution === 'has_attempts' && item.peserta_count <= 0) return false;
                                    if (this.examExecution === 'no_attempts' && item.peserta_count > 0) return false;
                                    if (!q) return true;
                                    return item.search.includes(q);
                                });
                            }
                        }"
                        class="space-y-2"
                    >
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Ujian Spesifik (Opsional)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input
                                type="search"
                                x-model="examFilter"
                                placeholder="Cari nama ujian, kode, mapel, kelas..."
                                class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            >
                            <select
                                x-model="examExecution"
                                class="w-full rounded-xl border border-gray-300 p-2 text-xs dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            >
                                <option value="all">Semua Status Pelaksanaan</option>
                                <option value="has_attempts">Sudah Pernah Ujian</option>
                                <option value="no_attempts">Belum Ada Peserta</option>
                            </select>
                        </div>
                        <select name="cbt_exam_id" x-model="selectedExamId" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="" x-text="'-- Seluruh Ujian Eligible (' + filteredExams.length + ') --'">-- Seluruh Ujian Eligible --</option>
                            <template x-for="ex in filteredExams" :key="ex.id">
                                <option :value="ex.id" x-text="ex.label"></option>
                            </template>
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mata Pelajaran (Opsional)</label>
                    <select name="mata_pelajaran_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Semua Mata Pelajaran (Tarik Lengkap)</option>
                        @foreach($subjects as $sb)
                            <option value="{{ $sb->id }}">{{ $sb->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Rombel / Kelas (Opsional)</label>
                    <select name="kelas" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mode Sinkronisasi Susulan / Remedial</label>
                    <select name="sync_mode" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="highest" selected>Ambil Nilai Tertinggi (Siswa Ujian Utama + Susulan/Remedial)</option>
                        <option value="replace">Timpa dengan Nilai Terbaru</option>
                        <option value="fill_empty">Hanya Isi Nilai yang Masih Kosong</option>
                    </select>
                </div>

                <button type="submit" class="w-full rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Mulai Tarik Data dari API CBT
                </button>
            </form>
        </div>

        <!-- Opsi 2: Impor File Excel Hasil CBT (Fallback) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Impor File Excel CBT (Mekanisme Fallback)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Mendukung ekspor multi-mapel CBT (otomatis memetakan mapel per baris).</p>
                </div>
            </div>

            <form action="{{ route('erapor.cbt.import-excel') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="tahun_ajaran_id" value="{{ $activeYear->id ?? 1 }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Penilaian E-Rapor</label>
                        <select name="component_code" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="sts">STS 1 / UTS (Bobot 30%)</option>
                            <option value="uas">UAS / Akhir Semester (Bobot 40%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Semester</label>
                        <select name="semester" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mata Pelajaran Tujuan</label>
                    <select name="mata_pelajaran_id" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="">Otomatis Deteksi dari Kolom Excel (Multi-Mapel / Semua Mapel)</option>
                        @foreach($subjects as $sb)
                            <option value="{{ $sb->id }}">{{ $sb->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mode Sinkronisasi Susulan / Remedial</label>
                    <select name="sync_mode" class="w-full rounded-xl border border-gray-300 p-2.5 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                        <option value="highest" selected>Ambil Nilai Tertinggi (Siswa Ujian Utama + Susulan/Remedial)</option>
                        <option value="replace">Timpa dengan Nilai Terbaru</option>
                        <option value="fill_empty">Hanya Isi Nilai yang Masih Kosong</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">File Excel / CSV Hasil Ujian (.xlsx, .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full rounded-xl border border-gray-300 p-2 text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/40 dark:file:text-emerald-300">
                </div>

                <button type="submit" class="w-full rounded-xl bg-emerald-600 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition flex items-center justify-center gap-2 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Unggah & Petakan Nilai Siswa
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Batch -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Riwayat Batch Integrasi CBT</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 border-b border-gray-200 dark:bg-gray-800/80 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="py-3 px-3">Batch ID</th>
                        <th class="py-3 px-3">Sumber</th>
                        <th class="py-3 px-3">Mapel</th>
                        <th class="py-3 px-3">Total Baris</th>
                        <th class="py-3 px-3">Sukses</th>
                        <th class="py-3 px-3">Gagal</th>
                        <th class="py-3 px-3">Waktu</th>
                        <th class="py-3 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($batches as $b)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                            <td class="py-3 px-3 font-mono text-xs font-semibold text-brand-600 dark:text-brand-400">{{ $b->batch_number }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-block px-2.5 py-0.5 text-xs font-medium rounded-full {{ $b->source_type === 'cbt_api' ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' }}">
                                    {{ $b->source_type === 'cbt_api' ? 'REST API' : 'Excel' }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-medium text-gray-800 dark:text-white">{{ $b->mataPelajaran?->nama ?? 'Semua Mapel' }}</td>
                            <td class="py-3 px-3 font-mono">{{ $b->total_rows }}</td>
                            <td class="py-3 px-3 text-emerald-600 dark:text-emerald-400 font-semibold font-mono">{{ $b->success_rows }}</td>
                            <td class="py-3 px-3 text-red-500 font-mono">{{ $b->failed_rows }}</td>
                            <td class="py-3 px-3 text-xs text-gray-400 dark:text-gray-500">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-3 text-center">
                                <a href="{{ route('erapor.cbt.batch-detail', $b->id) }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:bg-brand-50 dark:text-brand-400 dark:hover:text-brand-300 dark:hover:bg-brand-900/20 transition">
                                    Lihat Rincian &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Belum ada riwayat batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-3">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
