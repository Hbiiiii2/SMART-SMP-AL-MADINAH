@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Edit User</h2>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.users.update', $user) }}" method="POST"
            x-data="{
                role: @js(old('role', $user->role)),
                expiredAt: @js(old('expired_at', $user->expired_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '')),
                setPreset(days) {
                    const d = new Date();
                    d.setDate(d.getDate() + days);
                    const pad = (n) => String(n).padStart(2, '0');
                    this.expiredAt = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                }
            }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama <span class="text-error-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('name')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Email <span class="text-error-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('email')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Role <span class="text-error-500">*</span></label>
                    <select name="role" x-model="role"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="">Pilih Role</option>
                        <option value="super_admin" {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin_sekolah" {{ old('role', $user->role) == 'admin_sekolah' ? 'selected' : '' }}>Admin Sekolah</option>
                        <option value="kepala_sekolah" {{ old('role', $user->role) == 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                        <option value="direktur" {{ old('role', $user->role) == 'direktur' ? 'selected' : '' }}>Direktur</option>
                        <option value="guru" {{ old('role', $user->role) == 'guru' ? 'selected' : '' }}>Guru</option>
                        <option value="staff_akademik" {{ old('role', $user->role) == 'staff_akademik' ? 'selected' : '' }}>Staff Akademik</option>
                        <option value="siswa" {{ old('role', $user->role) == 'siswa' ? 'selected' : '' }}>Siswa</option>
                        <option value="akun_demo" {{ old('role', $user->role) == 'akun_demo' ? 'selected' : '' }}>Akun Demo (Preview Only)</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="role === 'akun_demo'" x-cloak class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-800/60 dark:bg-amber-950/20">
                    <label class="mb-2 block text-sm font-medium text-amber-900 dark:text-amber-300">
                        Masa Berlaku Akun Demo (WIB) <span class="text-error-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:items-center">
                        <input type="datetime-local" name="expired_at" x-model="expiredAt" :required="role === 'akun_demo'"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-amber-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-amber-700 dark:bg-gray-900 dark:text-white/90" />
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="setPreset(1)" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-300">+1 Hari</button>
                            <button type="button" @click="setPreset(3)" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-300">+3 Hari</button>
                            <button type="button" @click="setPreset(7)" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-300">+7 Hari</button>
                            <button type="button" @click="setPreset(14)" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-300">+14 Hari</button>
                            <button type="button" @click="setPreset(30)" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-300">+30 Hari</button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                        Akun Demo hanya memiliki akses preview (read-only) dan otomatis nonaktif/ditolak saat melewati tanggal & jam kedaluwarsa.
                    </p>
                    @error('expired_at')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Aktif</span>
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Password <small class="text-gray-400">(Kosongkan jika tidak diubah)</small></label>
                    <input type="password" name="password"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('password')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <a href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Perbarui
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
