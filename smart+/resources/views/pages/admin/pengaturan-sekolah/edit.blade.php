@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Pengaturan Sekolah</h2>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.pengaturan-sekolah.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Sekolah <span class="text-error-500">*</span></label>
                    <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('nama_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Alamat Sekolah</label>
                    <textarea name="alamat_sekolah" rows="3"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">{{ old('alamat_sekolah', $pengaturan->alamat_sekolah ?? '') }}</textarea>
                    @error('alamat_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">No. Telepon Sekolah</label>
                    <input type="text" name="no_telepon_sekolah" value="{{ old('no_telepon_sekolah', $pengaturan->no_telepon_sekolah ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('no_telepon_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Email Sekolah</label>
                    <input type="email" name="email_sekolah" value="{{ old('email_sekolah', $pengaturan->email_sekolah ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('email_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Logo Sekolah</label>
                    @if(!empty($pengaturan->logo) && file_exists(public_path('storage/' . $pengaturan->logo)))
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $pengaturan->logo) }}" alt="Logo Sekolah" class="h-20 w-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        </div>
                    @endif
                    <input type="file" name="logo" accept="image/jpg,image/jpeg,image/png"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('logo')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Masuk</label>
                    <input type="time" name="jam_masuk" value="{{ old('jam_masuk', substr($pengaturan->jam_masuk ?? '', 0, 5)) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('jam_masuk')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Pulang</label>
                    <input type="time" name="jam_pulang" value="{{ old('jam_pulang', substr($pengaturan->jam_pulang ?? '', 0, 5)) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('jam_pulang')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Toleransi Terlambat (menit)</label>
                    <input type="number" name="toleransi_terlambat_menit" value="{{ old('toleransi_terlambat_menit', $pengaturan->toleransi_terlambat_menit ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('toleransi_terlambat_menit')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Toleransi Alpha (menit)</label>
                    <p class="mb-1.5 text-xs text-gray-400">Menit setelah jam pelajaran selesai sebelum status otomatis Alpha</p>
                    <input type="number" name="toleransi_alpha_menit" value="{{ old('toleransi_alpha_menit', $pengaturan->toleransi_alpha_menit ?? 30) }}"
                        min="0" max="120"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('toleransi_alpha_menit')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Radius Absensi (meter)</label>
                    <input type="number" name="radius_absensi_meter" value="{{ old('radius_absensi_meter', $pengaturan->radius_absensi_meter ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('radius_absensi_meter')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Absensi Siswa <span class="text-error-500">*</span></label>
                    @if($isSuperAdmin)
                        <select name="metode_absensi_siswa"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                            <option value="">Pilih Metode Siswa</option>
                            <option value="RFID" {{ old('metode_absensi_siswa', $pengaturan->metode_absensi_siswa ?? '') == 'RFID' ? 'selected' : '' }}>RFID</option>
                            <option value="Zonasi" {{ old('metode_absensi_siswa', $pengaturan->metode_absensi_siswa ?? '') == 'Zonasi' ? 'selected' : '' }}>Zonasi</option>
                        </select>
                    @else
                        <div class="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ $pengaturan->metode_absensi_siswa ?? 'RFID' }}
                            <span class="ml-2 text-xs text-gray-400">(Hanya Super Admin)</span>
                        </div>
                        <input type="hidden" name="metode_absensi_siswa" value="{{ $pengaturan->metode_absensi_siswa }}">
                    @endif
                    @error('metode_absensi_siswa')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Absensi Guru <span class="text-error-500">*</span></label>
                    @if($isSuperAdmin)
                        <select name="metode_absensi_guru"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                            <option value="RFID" {{ old('metode_absensi_guru', $pengaturan->metode_absensi_guru ?? 'RFID') == 'RFID' ? 'selected' : '' }}>RFID (Tap kiosk)</option>
                            <option value="Hybrid" {{ old('metode_absensi_guru', $pengaturan->metode_absensi_guru ?? '') == 'Hybrid' ? 'selected' : '' }}>Hybrid (Manual + RFID)</option>
                            <option value="Zonasi" {{ old('metode_absensi_guru', $pengaturan->metode_absensi_guru ?? '') == 'Zonasi' ? 'selected' : '' }}>Zonasi (Lokasi)</option>
                        </select>
                    @else
                        <div class="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ $pengaturan->metode_absensi_guru ?? 'RFID' }}
                            <span class="ml-2 text-xs text-gray-400">(Hanya Super Admin)</span>
                        </div>
                        <input type="hidden" name="metode_absensi_guru" value="{{ $pengaturan->metode_absensi_guru }}">
                    @endif
                    @error('metode_absensi_guru')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                @if($isSuperAdmin)
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Absensi Staff Akademik <span class="text-error-500">*</span></label>
                    <select name="metode_absensi_staff"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="RFID" {{ old('metode_absensi_staff', $pengaturan->metode_absensi_staff ?? 'RFID') == 'RFID' ? 'selected' : '' }}>RFID (Tap kiosk)</option>
                        <option value="Hybrid" {{ old('metode_absensi_staff', $pengaturan->metode_absensi_staff ?? '') == 'Hybrid' ? 'selected' : '' }}>Hybrid (Manual + RFID)</option>
                        <option value="Zonasi" {{ old('metode_absensi_staff', $pengaturan->metode_absensi_staff ?? '') == 'Zonasi' ? 'selected' : '' }}>Zonasi (Lokasi)</option>
                    </select>
                    @error('metode_absensi_staff')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>
                @endif
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
