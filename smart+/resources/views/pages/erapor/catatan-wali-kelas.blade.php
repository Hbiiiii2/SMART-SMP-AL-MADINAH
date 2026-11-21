@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('erapor.cetak.index', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                    &larr; Kembali ke Cetak Rapor
                </a>
                <span class="text-xs text-gray-400">•</span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    Wali Kelas
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">SMP Al Madinah</span>
            </div>
            <h2 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">Form Input Catatan Wali Kelas</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Input catatan bimbingan, motivasi, dan apresiasi wali kelas untuk lembar rapor resmi siswa kelas <span class="font-semibold text-brand-600 dark:text-brand-400">{{ $kelas }}</span>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('erapor.cetak.index', ['kelas' => $kelas, 'semester' => $semester, 'tahun_ajaran_id' => $targetYear->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Lihat Lembar Cetak Rapor
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

    <!-- Filter Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('erapor.cetak.catatan-wali') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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

    <!-- Instruction & Batch Action Bar -->
    <div class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-brand-50/80 via-purple-50/40 to-white border border-brand-100 text-brand-900 dark:from-brand-950/30 dark:via-purple-950/20 dark:to-gray-900 dark:border-brand-900/40 dark:text-brand-300">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-lg shadow-sm">💡</span>
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Dua Metode Pengisian Catatan Wali Kelas</h4>
                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 max-w-2xl leading-relaxed">
                    Tersedia 2 opsi otomatis untuk mempermudah wali kelas: 
                    <span class="font-semibold text-brand-700 dark:text-brand-300">1. Template Standar</span> (formula baku Kurikulum Merdeka & presensi), dan 
                    <span class="font-semibold text-purple-700 dark:text-purple-300">2. Generate via AI</span> (narasi personal, islami & menyentuh via OpenRouter). Anda juga bebas mengetik/mengedit manual.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button type="button" onclick="autoFillEmptyNotes()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <svg class="h-3.5 w-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Isi Template Standar (Kosong)
            </button>
            <button type="button" id="btn_batch_ai" onclick="generateAllAiNotes()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-600 text-xs font-bold text-white shadow-sm hover:bg-purple-700 dark:bg-purple-600 dark:hover:bg-purple-500 transition">
                <span id="batch_ai_icon">✨</span>
                <span id="batch_ai_label">Generate Semua via AI</span>
            </button>
        </div>
    </div>

    <!-- Form Input Table -->
    <form method="POST" action="{{ route('erapor.cetak.save-catatan-wali') }}" id="formCatatanWali">
        @csrf
        <input type="hidden" name="kelas" value="{{ $kelas }}">
        <input type="hidden" name="tahun_ajaran_id" value="{{ $targetYear->id }}">
        <input type="hidden" name="semester" value="{{ $semester }}">

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">Daftar Siswa Kelas {{ $kelas }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total {{ $students->count() }} siswa terdaftar.</p>
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Semua Catatan
                </button>
            </div>

            <div class="max-w-full overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-4 py-3.5 text-center w-12">No</th>
                            <th class="px-4 py-3.5 min-w-[220px]">Nama & Identitas Siswa</th>
                            <th class="px-4 py-3.5 text-center min-w-[140px]">Akademik & Presensi</th>
                            <th class="px-4 py-3.5 min-w-[420px]">Catatan Wali Kelas (Rapor Resmi)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($students as $idx => $s)
                            @php
                                $sts = $stsSummaries->get($s->id);
                                $att = $attendances->get($s->id);
                                $rataRata = !empty($hasUasScores)
                                    ? ($sts?->uas_average_score ?? $sts?->average_score)
                                    : ($sts?->average_score ?? $sts?->uas_average_score);
                                $rank = !empty($hasUasScores)
                                    ? ($sts?->uas_rank ?? $sts?->rank)
                                    : ($sts?->rank ?? $sts?->uas_rank);

                                // Suggestion calculation
                                if ($rataRata && $rataRata >= 85) {
                                    $defaultSuggestion = "Prestasi belajar sangat memuaskan dan patut diapresiasi. Pertahankan semangat belajar, ketekunan, dan akhlak mulia dalam setiap kegiatan.";
                                } elseif ($rataRata && $rataRata >= 75) {
                                    $defaultSuggestion = "Pencapaian belajar sudah baik. Tingkatkan terus kedisiplinan dan konsistensi dalam menyelesaikan tugas-tugas akademik serta aktif di kelas.";
                                } else {
                                    $defaultSuggestion = "Tingkatkan motivasi dan waktu belajar di rumah. Jangan ragu berdiskusi dengan guru pengampu mata pelajaran jika menemui kesulitan materi.";
                                }

                                if (($att?->a ?? 0) >= 3) {
                                    $defaultSuggestion .= " Mohon lebih memperhatikan kehadiran di sekolah dan kurangi ketidakhadiran tanpa keterangan.";
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                <td class="px-4 py-3 text-center text-xs text-gray-400 font-medium align-top pt-4">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-4 py-3 align-top pt-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-300 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ strtoupper(substr($s->nama_lengkap, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-800 dark:text-white">
                                                {{ $s->nama_lengkap }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                                <span>NIS: {{ $s->nis ?? '-' }}</span>
                                                <span>•</span>
                                                <span>NISN: {{ $s->nisn ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top pt-4 text-center">
                                    <div class="inline-flex flex-col items-center gap-1">
                                        @if($rataRata !== null)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300 text-xs font-bold">
                                                Nilai: {{ number_format($rataRata, 1) }}
                                                @if($rank)
                                                    <span class="text-brand-500 font-normal">#{{ $rank }}</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Belum ada nilai</span>
                                        @endif

                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                                            <span title="Sakit" class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 font-medium">S: {{ $att->s ?? 0 }}</span>
                                            <span title="Izin" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-medium">I: {{ $att->i ?? 0 }}</span>
                                            <span title="Alpha" class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 font-medium">A: {{ $att->a ?? 0 }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-1.5">
                                        <textarea
                                            name="catatan[{{ $s->id }}]"
                                            id="note_{{ $s->id }}"
                                            data-suggestion="{{ $defaultSuggestion }}"
                                            rows="2"
                                            class="w-full rounded-xl border border-gray-300 p-2.5 text-xs text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:placeholder-gray-500"
                                            placeholder="Tuliskan catatan wali kelas untuk {{ $s->nama_lengkap }}..."
                                        >{{ old('catatan.' . $s->id, $sts?->catatan_wali_kelas) }}</textarea>

                                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <!-- Tombol 1: Template Rule Based -->
                                                <button
                                                    type="button"
                                                    onclick="applySuggestion('{{ $s->id }}')"
                                                    title="Gunakan formula standar Kurikulum Merdeka & data presensi"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 font-semibold text-[11px] transition shadow-2xs"
                                                >
                                                    <svg class="h-3.5 w-3.5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    Template Standar
                                                </button>

                                                <!-- Tombol 2: AI OpenRouter Catatan Wali Kelas -->
                                                <button
                                                    type="button"
                                                    id="btn_ai_{{ $s->id }}"
                                                    onclick="generateAiNote('{{ $s->id }}')"
                                                    title="Generate narasi personal dan islami via AI OpenRouter"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 dark:border-purple-800/60 dark:bg-purple-950/40 dark:text-purple-300 dark:hover:bg-purple-900/50 font-bold text-[11px] transition shadow-2xs"
                                                >
                                                    <span id="ai_icon_{{ $s->id }}">✨</span>
                                                    <span id="ai_label_{{ $s->id }}">Generate via AI</span>
                                                </button>
                                            </div>
                                            <span class="text-[11px] text-gray-400">Maks 300 karakter</span>
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
                        Pastikan seluruh catatan sudah diteliti sebelum mencetak rapor siswa.
                    </span>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Semua Catatan Wali Kelas
                    </button>
                </div>
            @endif
        </div>
    </form>
</div>

<script>
    function applySuggestion(studentId) {
        const textarea = document.getElementById('note_' + studentId);
        if (textarea) {
            textarea.value = textarea.getAttribute('data-suggestion');
            textarea.focus();
            flashHighlight(textarea);
        }
    }

    function autoFillEmptyNotes() {
        const textareas = document.querySelectorAll('textarea[name^="catatan["]');
        let filledCount = 0;
        textareas.forEach(textarea => {
            if (!textarea.value.trim()) {
                textarea.value = textarea.getAttribute('data-suggestion');
                flashHighlight(textarea);
                filledCount++;
            }
        });
        alert(filledCount + ' kolom catatan wali kelas yang kosong berhasil diisi dengan formula template standar.');
    }

    async function generateAiNote(studentId) {
        const textarea = document.getElementById('note_' + studentId);
        const btn = document.getElementById('btn_ai_' + studentId);
        const icon = document.getElementById('ai_icon_' + studentId);
        const label = document.getElementById('ai_label_' + studentId);

        if (!btn || !textarea) return;

        btn.disabled = true;
        const oldLabel = label.textContent;
        icon.textContent = '⏳';
        label.textContent = 'Menulis AI...';

        try {
            const response = await fetch("{{ route('erapor.cetak.ai-catatan-wali') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    siswa_id: studentId,
                    tahun_ajaran_id: '{{ $targetYear->id }}',
                    semester: '{{ $semester }}'
                })
            });

            const data = await response.json();
            if (data.success && data.catatan) {
                textarea.value = data.catatan;
                flashHighlight(textarea);
            } else {
                alert(data.message || 'Gagal memproses AI. Menggunakan template standar sebagai fallback.');
                applySuggestion(studentId);
            }
        } catch (error) {
            console.error('Error generating AI note:', error);
            applySuggestion(studentId);
        } finally {
            btn.disabled = false;
            icon.textContent = '✨';
            label.textContent = oldLabel;
        }
    }

    async function generateAllAiNotes() {
        const textareas = document.querySelectorAll('textarea[name^="catatan["]');
        const emptyIds = [];
        textareas.forEach(ta => {
            if (!ta.value.trim()) {
                const id = ta.id.replace('note_', '');
                if (id) emptyIds.push(id);
            }
        });

        if (emptyIds.length === 0) {
            alert('Seluruh siswa sudah memiliki catatan wali kelas.');
            return;
        }

        const batchBtn = document.getElementById('btn_batch_ai');
        const batchIcon = document.getElementById('batch_ai_icon');
        const batchLabel = document.getElementById('batch_ai_label');
        batchBtn.disabled = true;

        let processed = 0;
        for (const id of emptyIds) {
            batchIcon.textContent = '⏳';
            batchLabel.textContent = `AI: ${processed + 1}/${emptyIds.length}...`;
            await generateAiNote(id);
            processed++;
        }

        batchBtn.disabled = false;
        batchIcon.textContent = '✨';
        batchLabel.textContent = 'Generate Semua via AI';
        alert(`Selesai! Sebanyak ${processed} catatan siswa berhasil di-generate secara personal via AI.`);
    }

    function flashHighlight(element) {
        element.classList.add('ring-2', 'ring-purple-400', 'bg-purple-50/30');
        setTimeout(() => {
            element.classList.remove('ring-2', 'ring-purple-400', 'bg-purple-50/30');
        }, 1200);
    }
</script>
@endsection
