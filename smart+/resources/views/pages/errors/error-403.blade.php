@extends('layouts.auth')

@section('content')
    <div class="text-center">
        <div class="mb-6">
            <span class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-error-50 dark:bg-error-500/10">
                <svg class="w-10 h-10 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </span>
        </div>
        <h1 class="mb-3 text-title-xl font-semibold text-gray-800 dark:text-white/90">403</h1>
        <p class="mb-6 text-gray-500 dark:text-gray-400">Akses Ditolak</p>
        <p class="mb-8 text-sm text-gray-400 dark:text-gray-500">{{ $message ?? 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}</p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <button type="button" onclick="window.history.back()"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                Kembali
            </button>
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">
                {{ auth()->check() ? 'Ke Dashboard' : 'Kembali ke Beranda' }}
            </a>
        </div>
    </div>
@endsection
