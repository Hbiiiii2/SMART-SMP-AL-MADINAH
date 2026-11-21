@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
            Tambah Tugas
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400" href="{{ route('guru.tugas.index') }}">Tugas /</a></li>
                <li class="text-sm font-medium text-brand-500 dark:text-brand-400">Tambah</li>
            </ol>
        </nav>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 py-4 px-6 dark:border-gray-800">
            <h3 class="font-semibold text-gray-800 dark:text-white/90">Form Tugas</h3>
        </div>
        <form action="{{ route('guru.tugas.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-6">
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">Judul Tugas <span class="text-error-500">*</span></label>
                    <input type="text" name="judul" value="{{ old('judul') }}" required placeholder="Masukkan judul tugas" class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition-colors focus:border-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:focus:border-brand-400" />
                    @error('judul') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                </div>

                <div class="mb-5 flex flex-col gap-6 xl:flex-row">
                    <div class="w-full xl:w-1/2">
                        <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">Kelas <span class="text-error-500">*</span></label>
                        <select name="kelas" id="kelas" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition-colors focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-400">
                            <option value="">Pilih Kelas</option>
                            @foreach($kelasOptions as $kelas)
                                <option value="{{ $kelas }}" {{ old('kelas') == $kelas ? 'selected' : '' }}>{{ $kelas }}</option>
                            @endforeach
                        </select>
                        @error('kelas') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="w-full xl:w-1/2">
                        <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">Mata Pelajaran <span class="text-error-500">*</span></label>
                        <select name="mata_pelajaran_id" id="mata_pelajaran_id" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition-colors focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-400">
                            <option value="">Pilih Mata Pelajaran</option>
                            @foreach($mapelOptions as $mapel)
                                <option value="{{ $mapel->id }}" {{ old('mata_pelajaran_id') == $mapel->id ? 'selected' : '' }}>{{ $mapel->nama }}</option>
                            @endforeach
                        </select>
                        @error('mata_pelajaran_id') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-5">
                    <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">Deskripsi Tugas <span class="text-error-500">*</span></label>
                    <textarea name="deskripsi" required rows="4" placeholder="Deskripsi tugas dan instruksi pengerjaan" class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition-colors focus:border-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:focus:border-brand-400">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                </div>

                <div class="mb-5 flex flex-col gap-6 xl:flex-row">
                    <div class="w-full xl:w-1/2">
                        <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">Tenggat Waktu <span class="text-error-500">*</span></label>
                        <input type="datetime-local" name="tenggat_waktu" value="{{ old('tenggat_waktu') }}" required class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition-colors focus:border-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:focus:border-brand-400" />
                        @error('tenggat_waktu') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="w-full xl:w-1/2">
                        <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">File Lampiran (Opsional, Max: 10MB)</label>
                        <input type="file" name="file_lampiran" class="w-full cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition-colors hover:border-brand-500 focus:border-brand-500 file:mr-4 file:rounded file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-800 dark:bg-white/[0.03] dark:text-white/90 dark:file:bg-white/[0.05] dark:file:text-white/90 dark:hover:file:bg-white/[0.1]" />
                        @error('file_lampiran') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mb-5">
                    <label class="mb-2 block text-sm font-semibold text-gray-800 dark:text-white/90">Tipe Pengumpulan <span class="text-error-500">*</span></label>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="tipe_pengumpulan[]" value="link_url" class="h-5 w-5 rounded border-gray-200 text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:checked:bg-brand-500" {{ (is_array(old('tipe_pengumpulan')) && in_array('link_url', old('tipe_pengumpulan'))) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-800 dark:text-white/90">Link URL</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="tipe_pengumpulan[]" value="file" class="h-5 w-5 rounded border-gray-200 text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-white/[0.03] dark:checked:bg-brand-500" {{ (is_array(old('tipe_pengumpulan')) && in_array('file', old('tipe_pengumpulan'))) || !old('tipe_pengumpulan') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-800 dark:text-white/90">File Upload</span>
                        </label>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <strong>Note:</strong> Jika siswa ingin mengumpulkan tugas berupa video, arahkan siswa untuk menggunakan "Link URL" dan pastikan akses link video (misal di Google Drive) diubah menjadi "Everyone with the link" atau "Siapa saja yang memiliki tautan".
                    </p>
                    @error('tipe_pengumpulan') <span class="mt-1 block text-sm text-error-500">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="flex w-full justify-center rounded-lg bg-brand-500 p-3 text-sm font-medium text-white hover:bg-brand-600 transition-colors">
                    Simpan Tugas
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kelasSelect = document.getElementById('kelas');
        const mapelSelect = document.getElementById('mata_pelajaran_id');
        const oldMapelId = "{{ old('mata_pelajaran_id') }}";

        function updateMataPelajaran() {
            const kelas = kelasSelect.value;
            
            // Hapus options yang ada selain placeholder
            mapelSelect.innerHTML = '<option value="">Pilih Mata Pelajaran</option>';
            
            if (!kelas) {
                mapelSelect.disabled = true;
                return;
            }

            mapelSelect.disabled = true;
            
            fetch(`{{ route('guru.get-mata-pelajaran') }}?kelas=${encodeURIComponent(kelas)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(mapel => {
                        const option = document.createElement('option');
                        option.value = mapel.id;
                        option.textContent = mapel.nama;
                        if (oldMapelId && oldMapelId == mapel.id) {
                            option.selected = true;
                        }
                        mapelSelect.appendChild(option);
                    });
                    mapelSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching mata pelajaran:', error);
                    mapelSelect.disabled = false;
                });
        }

        kelasSelect.addEventListener('change', updateMataPelajaran);
        
        // Initial load for old values
        updateMataPelajaran();
    });
</script>
@endpush
