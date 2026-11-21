@extends('layouts.auth')

@section('content')
    <div class="text-center">
        <div class="mb-6">
            <span class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-warning-50 dark:bg-warning-500/10">
                <svg class="w-10 h-10 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9.364-7.364l-1.414 1.414M6.05 6.05L4.636 7.464M12 3v2m0 14v2m9-9h-2M3 12H5m14.364 4.364l-1.414-1.414M6.05 17.95l-1.414 1.414" />
                </svg>
            </span>
        </div>
        <h1 class="mb-3 text-title-xl font-semibold text-gray-800 dark:text-white/90">401</h1>
        <p class="mb-6 text-gray-500 dark:text-gray-400">Sesi Berakhir</p>
        <p class="mb-8 text-sm text-gray-400 dark:text-gray-500">Silakan masuk kembali untuk melanjutkan.</p>
        <a href="{{ route('login') }}"
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">
            Masuk
        </a>
    </div>
@endsection
