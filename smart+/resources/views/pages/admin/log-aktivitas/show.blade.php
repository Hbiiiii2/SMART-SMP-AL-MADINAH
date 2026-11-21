@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <a href="{{ route('admin.log-aktivitas.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-brand-500 hover:underline">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Detail Log Aktivitas</h2>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400">Waktu</label>
                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $log->created_at->format('l, d F Y H:i:s') }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400">User</label>
                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $log->user->name ?? 'System' }} {{ $log->user ? '(' . $log->user->email . ')' : '' }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</label>
                    @php
                        $badge = match(true) {
                            str_starts_with($log->aksi, 'create') => 'bg-success-50 text-success-700',
                            str_starts_with($log->aksi, 'update') => 'bg-warning-50 text-warning-700',
                            str_starts_with($log->aksi, 'delete') => 'bg-error-50 text-error-700',
                            str_starts_with($log->aksi, 'login') => 'bg-blue-50 text-blue-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $badge }}">{{ $log->aksi }}</span>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400">IP Address</label>
                    <p class="font-mono text-sm text-gray-800 dark:text-white/90">{{ $log->ip_address ?? '-' }}</p>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400">Deskripsi</label>
                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-white/90">{{ $log->deskripsi ?? '-' }}</div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400">User Agent</label>
                <div class="rounded-lg bg-gray-50 p-4 font-mono text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400 break-all">{{ $log->user_agent ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
