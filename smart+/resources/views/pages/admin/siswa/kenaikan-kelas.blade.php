@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Kenaikan Kelas Massal</h2>
        <a href="{{ route('admin.siswa.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    @if($siswasByKelas->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-center text-gray-500 dark:text-gray-400">Tidak ada data kelas yang dapat diatur kenaikannya.</p>
        </div>
    @else
        <form action="{{ route('admin.siswa.proses-kenaikan-kelas') }}" method="POST">
            @csrf

            {{-- Step 1: Pilih Tahun Ajaran --}}
            <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-4 flex items-center gap-3 text-lg font-semibold text-gray-800 dark:text-white/90">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">1</span>
                    Pilih Tahun Ajaran Baru
                </h3>
                <div class="max-w-md">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Tahun Ajaran Tujuan <span class="text-error-500">*</span></label>
                    <select name="tahun_ajaran_tujuan" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        @foreach($tahunAjarans as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->nama }} {{ $ta->is_aktif ? '(Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Step 2: Mapping Kelas --}}
            <div class="mb-6">
                <h3 class="mb-2 flex items-center gap-3 text-lg font-semibold text-gray-800 dark:text-white/90">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">2</span>
                    Atur Mapping Kelas
                </h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Pilih kelas tujuan untuk masing-masing kelas. Hilangkan centang pada nama siswa jika siswa tersebut <strong class="text-gray-700 dark:text-white/80">Tinggal Kelas</strong>.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2" x-data="{ expanded: {} }">
                @foreach($siswasByKelas as $kelas => $siswas)
                    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                        {{-- Card Header --}}
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-800 dark:text-white/90">
                                    Kelas: <span class="font-bold">{{ $kelas }}</span>
                                </h4>
                                <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $siswas->count() }} Siswa
                                </span>
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-6">
                            {{-- Dropdown Kelas Tujuan --}}
                            <div class="mb-5">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Naik Ke Kelas:</label>
                                <select name="kelas_tujuan[{{ $kelas }}]"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                    <option value="">-- Tidak Ada Perubahan / Abaikan --</option>
                                    @foreach($kelases as $k)
                                        <option value="{{ $k }}">{{ $k }}</option>
                                    @endforeach
                                    <option value="Alumni">Lulus / Alumni</option>
                                </select>
                            </div>

                            {{-- Expandable Student List --}}
                            <div>
                                <button type="button" @click="expanded['{{ $kelas }}'] = !expanded['{{ $kelas }}']"
                                    class="flex w-full items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <span>Lihat Daftar Siswa (Centang = Naik)</span>
                                    <svg class="h-4 w-4 transition-transform duration-200"
                                        :class="expanded['{{ $kelas }}'] ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <div x-show="expanded['{{ $kelas }}']" x-collapse class="mt-3 max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-800">
                                    @foreach($siswas as $siswa)
                                        <label class="flex cursor-pointer items-center justify-between border-b border-gray-100 px-4 py-2.5 transition hover:bg-gray-50 last:border-b-0 dark:border-gray-800 dark:hover:bg-white/[0.02]">
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="siswa_naik[{{ $kelas }}][]" value="{{ $siswa->id }}" checked
                                                    class="h-4.5 w-4.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900" />
                                                <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $siswa->nama_lengkap }}</span>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $siswa->nisn }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Submit Button --}}
            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('admin.siswa.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin memproses kenaikan kelas ini secara massal? Pastikan tahun ajaran dan mapping kelas sudah benar!')"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Proses Kenaikan Kelas
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
