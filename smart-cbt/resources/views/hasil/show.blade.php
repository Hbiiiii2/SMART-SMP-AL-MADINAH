<x-app-layout>
    <x-slot name="header">
        <div>
            <nav class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                <a href="{{ auth()->user()->isGuru() ? route('guru.hasil.index') : route('hasil.index') }}" class="hover:text-slate-800">Daftar Hasil</a>
                <span>/</span>
                <span class="font-medium text-slate-800">Detail Siswa</span>
            </nav>
            <h2 class="text-xl font-bold text-slate-900">Riwayat Hasil: {{ $siswa->nama }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                NIS: {{ $siswa->nis }} &bull; Kelas: {{ $siswa->kelasData?->nama_kelas ?? $siswa->kelas ?? '-' }}
            </p>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ zoomImage: null }" @keydown.escape.window="zoomImage = null">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <a href="{{ auth()->user()->isGuru() ? route('guru.hasil.export.siswa', $siswa) : route('hasil.export.siswa', $siswa) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Nilai Siswa
                </a>
                
                <a href="{{ auth()->user()->isGuru() ? route('guru.hasil.index') : route('hasil.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    &larr; Kembali
                </a>
            </div>

            {{-- Metric Cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai PG</p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ $attempt->nilai_pg !== null ? number_format($attempt->nilai_pg, 2) : '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai Essay</p>
                    <p class="mt-2 text-2xl font-bold text-slate-800">{{ $attempt->nilai_essay !== null ? number_format($attempt->nilai_essay, 2) : '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai Akhir</p>
                    <p class="mt-2 text-2xl font-extrabold text-blue-600">{{ $attempt->nilai_akhir !== null ? number_format($attempt->nilai_akhir, 2) : '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status Ujian</p>
                    <div class="mt-2">
                        @if ($attempt->status === 'needs_grading')
                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Menunggu Penilaian Essay</span>
                        @elseif ($attempt->status === 'completed' || $attempt->status === 'submitted')
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Selesai</span>
                        @elseif ($attempt->status === 'expired')
                            <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Waktu Habis</span>
                        @else
                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">{{ ucfirst($attempt->status) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm mb-6">
                <div class="border-b border-slate-200 px-5 py-4 flex justify-between items-center flex-wrap gap-3">
                    <div>
                        <h3 class="font-bold text-slate-900">Rekap Jawaban: {{ $attempt->exam->nama ?? '-' }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Mata Pelajaran: {{ $attempt->exam->mataPelajaran?->nama ?? '-' }} &bull; Nilai Akhir: <strong class="text-slate-800">{{ $attempt->nilai_akhir !== null ? number_format($attempt->nilai_akhir, 2) : '-' }}</strong></p>
                    </div>
                    <div>
                        <a href="{{ auth()->user()->isGuru() ? route('guru.hasil.export.rekap', $attempt->id) : route('hasil.export.rekap', $attempt->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 shadow-sm transition hover:bg-blue-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export Rekap Jawaban
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 w-12 text-center">No</th>
                                <th class="px-5 py-3 w-28">Tipe Soal</th>
                                <th class="px-5 py-3 w-1/3">Pertanyaan</th>
                                <th class="px-5 py-3 text-center">Jawaban Siswa</th>
                                <th class="px-5 py-3 text-center">Kunci</th>
                                <th class="px-5 py-3 text-center w-28">Status</th>
                                <th class="px-5 py-3 text-center w-48">Skor & Penilaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($answers as $index => $ans)
                                @php
                                    $isCorrect = $ans->is_correct;
                                    $hasPhotos = $ans->attachments->isNotEmpty();
                                    $hasAnswered = !empty($ans->jawaban) || $hasPhotos;
                                    $isPG = $ans->question->tipe === 'pilihan_ganda' || $ans->question->tipe === 'pg';
                                    $tipeLabel = match ($ans->question->tipe) {
                                        'pg', 'pilihan_ganda' => 'Pilihan Ganda',
                                        'essay_1' => 'Essay 1',
                                        'essay_2' => 'Essay 2',
                                        default => 'Essay',
                                    };
                                    
                                    $jawabanDisplay = $ans->jawaban ?: '';
                                    if ($hasAnswered && $isPG && in_array(strtolower($ans->jawaban), ['a','b','c','d','e'])) {
                                        $opt = strtolower($ans->jawaban);
                                        $field = 'opsi_' . $opt;
                                        $imgField = 'opsi_' . $opt . '_image';
                                        $imgHtml = $ans->question->$imgField ? '<div class="my-1"><img src="' . Storage::url($ans->question->$imgField) . '" class="max-h-20 rounded border border-slate-200 bg-white object-contain" alt="Gambar Jawaban"></div>' : '';
                                        $jawabanDisplay = '<strong>' . strtoupper($ans->jawaban) . '.</strong> ' . $imgHtml . ($ans->question->$field ?? '');
                                    }

                                    $kunciDisplay = $ans->question->kunci ?: '-';
                                    if ($isPG && !empty($ans->question->kunci) && in_array(strtolower($ans->question->kunci), ['a','b','c','d','e'])) {
                                        $opt = strtolower($ans->question->kunci);
                                        $field = 'opsi_' . $opt;
                                        $imgField = 'opsi_' . $opt . '_image';
                                        $imgHtml = $ans->question->$imgField ? '<div class="my-1"><img src="' . Storage::url($ans->question->$imgField) . '" class="max-h-20 rounded border border-slate-200 bg-white object-contain" alt="Gambar Kunci"></div>' : '';
                                        $kunciDisplay = '<strong>' . strtoupper($ans->question->kunci) . '.</strong> ' . $imgHtml . ($ans->question->$field ?? '');
                                    }
                                    
                                    if (!$hasAnswered) {
                                        $statusText = 'Tidak Dijawab';
                                        $statusClass = 'bg-slate-100 text-slate-600';
                                    } elseif ($isPG) {
                                        $statusText = $isCorrect ? 'Benar' : 'Salah';
                                        $statusClass = $isCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800';
                                    } else {
                                        $statusText = $ans->sudah_dinilai ? 'Sudah Dinilai' : 'Belum Dinilai';
                                        $statusClass = $ans->sudah_dinilai ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800';
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 text-center font-medium text-slate-900">{{ $index + 1 }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $tipeLabel }}</td>
                                    <td class="px-5 py-4">
                                        @if ($ans->question->image)
                                            <button type="button" @click="zoomImage = '{{ Storage::url($ans->question->image) }}'" class="mb-2 block">
                                                <img src="{{ Storage::url($ans->question->image) }}" class="max-h-20 rounded border border-slate-200 bg-white object-contain hover:opacity-90" alt="Gambar Soal">
                                            </button>
                                        @endif
                                        <div class="max-h-24 overflow-hidden text-slate-600 text-sm rich-text-content break-words">{!! $ans->question->pertanyaan !!}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($isPG)
                                            <div class="text-slate-900 text-sm rich-text-content break-words">{!! $jawabanDisplay !== '' ? $jawabanDisplay : '-' !!}</div>
                                        @else
                                            <div class="space-y-2 text-left">
                                                @if (!empty($ans->jawaban))
                                                    <div class="whitespace-pre-line rounded-lg border border-slate-200 bg-slate-50/70 p-2.5 text-sm text-slate-900 break-words">{{ $ans->jawaban }}</div>
                                                @endif

                                                @if ($hasPhotos)
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($ans->attachments as $photoIdx => $attachment)
                                                            @php $photoUrl = route('lampiran-jawaban.show', $attachment); @endphp
                                                            <div class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-xs">
                                                                <button type="button"
                                                                        @click="zoomImage = '{{ $photoUrl }}'"
                                                                        class="block focus:outline-none"
                                                                        title="Klik untuk memperbesar Foto {{ $photoIdx + 1 }}">
                                                                    <img src="{{ $photoUrl }}"
                                                                         class="h-24 w-24 rounded object-cover transition group-hover:opacity-90"
                                                                         alt="Jawaban Foto {{ $photoIdx + 1 }}">
                                                                </button>
                                                                <div class="mt-1 flex items-center justify-between gap-1 px-1 text-[10px] text-slate-500">
                                                                    <span class="font-semibold text-slate-700">Foto {{ $photoIdx + 1 }}</span>
                                                                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Buka</a>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if (!$hasAnswered)
                                                    <span class="text-sm text-slate-400">-</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 text-sm rich-text-content break-words">{!! $isPG ? $kunciDisplay : '(Essay)' !!}</td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        @if ($isPG)
                                            <span class="font-bold text-slate-800">{{ $ans->skor !== null ? number_format($ans->skor, 1) : '0.0' }}</span>
                                            <span class="text-xs text-slate-400">/ {{ $ans->question->bobot ?: 1 }}</span>
                                        @else
                                            <form action="{{ route(auth()->user()->isGuru() ? 'guru.hasil.grade-essay' : 'hasil.grade-essay', $attempt->id) }}" method="POST" class="space-y-1.5 text-left">
                                                @csrf
                                                <input type="hidden" name="answer_id" value="{{ $ans->id }}">
                                                <div class="flex items-center gap-1">
                                                    <input type="number" name="skor" step="0.1" min="0" max="{{ $ans->question->bobot ?: 1 }}" value="{{ $ans->skor !== null ? (float)$ans->skor : 0 }}" class="w-16 rounded border-slate-300 px-2 py-0.5 text-xs text-center font-bold focus:border-blue-500 focus:ring-blue-500" required>
                                                    <span class="text-xs text-slate-500">/ {{ $ans->question->bobot ?: 1 }}</span>
                                                </div>
                                                <div>
                                                    <input type="text" name="catatan_guru" value="{{ $ans->catatan_guru }}" placeholder="Catatan..." class="w-full rounded border-slate-300 px-2 py-0.5 text-xs placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500">
                                                </div>
                                                <button type="submit" class="w-full rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                                                    Simpan Nilai
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-slate-500">Belum ada jawaban yang tersimpan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">Riwayat Ujian</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $riwayatUjian->count() }} ujian tercatat.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Ujian</th>
                                <th class="px-5 py-3">Mata Pelajaran</th>
                                <th class="px-5 py-3">Tahun/Semester</th>
                                <th class="px-5 py-3 text-center">Nilai</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Mulai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($riwayatUjian as $ru)
                                @php
                                    $statusLabel = [
                                        'in_progress' => 'Sedang Berlangsung',
                                        'needs_grading' => 'Menunggu Penilaian',
                                        'submitted' => 'Selesai',
                                        'completed' => 'Selesai',
                                        'expired' => 'Waktu Habis'
                                    ][$ru->status] ?? ucfirst($ru->status);
                                    
                                    $statusClass = [
                                        'submitted' => 'bg-emerald-50 text-emerald-700',
                                        'completed' => 'bg-emerald-50 text-emerald-700',
                                        'needs_grading' => 'bg-amber-50 text-amber-700',
                                        'expired' => 'bg-rose-50 text-rose-700',
                                        'in_progress' => 'bg-blue-50 text-blue-700'
                                    ][$ru->status] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $ru->exam->nama ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $ru->exam->mataPelajaran?->nama ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $ru->exam->tahun_ajaran ?? '-' }} / {{ $ru->exam->semester ?? '-' }}</td>
                                    <td class="px-5 py-4 text-center text-lg font-black text-slate-900">{{ $ru->nilai_akhir !== null ? number_format($ru->nilai_akhir, 2) : '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-500">{{ $ru->started_at ? $ru->started_at->format('d M Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-500">Belum ada riwayat ujian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Modal Zoom Foto Jawaban Siswa --}}
        <template x-if="zoomImage">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-sm"
                 @click="zoomImage = null">
                <div class="relative flex max-h-full max-w-4xl flex-col items-center">
                    <button type="button"
                            @click="zoomImage = null"
                            class="mb-2 self-end rounded-full bg-white/20 p-2 text-white hover:bg-white/30">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <img :src="zoomImage" class="max-h-[85vh] max-w-full rounded-xl object-contain shadow-2xl" @click.stop>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
