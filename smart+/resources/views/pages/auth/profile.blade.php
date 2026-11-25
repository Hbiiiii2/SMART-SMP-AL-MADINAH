@extends('layouts.app')

@php
    $role = Auth::user()->role ?? '';
    $prefix = match ($role) {
        'guru' => 'guru',
        'kepala_sekolah' => 'ks',
        'siswa' => 'siswa',
        'direktur' => 'direktur',
        'staff_akademik' => 'staff',
        default => 'admin',
    };
@endphp

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Profil Saya</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola informasi profil Anda</p>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <form method="POST" action="{{ route($prefix . '.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-5">
                    <div class="flex items-center gap-5">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-50 text-2xl font-bold text-brand-500 dark:bg-brand-500/10">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            <span class="mt-1 inline-flex items-center rounded-full {{ $user->isAkunDemo() ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' }} px-2.5 py-0.5 text-xs font-medium">
                                {{ $user->roleLabel() }}
                            </span>
                        </div>
                    </div>

                    @if($user->isAkunDemo())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/20 dark:text-amber-300">
                            <p class="font-semibold">Informasi Akun Demo</p>
                            <p class="mt-1">
                                Akun ini bersifat preview (read-only). Perubahan profil dan password dinonaktifkan.
                                @if($user->expired_at)
                                    Masa berlaku sampai <strong>{{ $user->expired_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</strong> ({{ $user->expired_at->diffForHumans() }}).
                                @endif
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Nama Lengkap
                        </label>
                        <input type="text" name="name" value="{{ $user->name }}" readonly disabled
                            class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-500 cursor-not-allowed focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white/50" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Email<span class="text-error-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required {{ $user->isAkunDemo() ? 'readonly disabled' : '' }}
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 {{ $user->isAkunDemo() ? 'bg-gray-50 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:text-white/50' : '' }}" />
                        @error('email')
                            <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        @unless($user->isAkunDemo())
                            <button type="submit"
                                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">
                                Simpan Perubahan
                            </button>
                        @endunless
                        <a href="{{ route($prefix . '.dashboard') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                            {{ $user->isAkunDemo() ? 'Kembali ke Dashboard' : 'Batal' }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
