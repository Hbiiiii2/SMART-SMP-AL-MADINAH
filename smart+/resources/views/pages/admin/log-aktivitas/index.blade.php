@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Log Aktivitas</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Riwayat aktivitas semua pengguna sistem</p>
        </div>
        <a href="{{ route('admin.log-aktivitas.laravel-logs') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Laravel Log
        </a>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('admin.log-aktivitas.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, deskripsi, IP..."
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:w-72" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Aksi</label>
                <select name="aksi_filter" onchange="this.form.submit()"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua Aksi</option>
                    @foreach($aksiList as $aksi)
                        <option value="{{ $aksi }}" {{ request('aksi_filter') === $aksi ? 'selected' : '' }}>{{ $aksi }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">User</label>
                <select name="user_filter" onchange="this.form.submit()"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua User</option>
                    @foreach($userList as $user)
                        <option value="{{ $user->id }}" {{ request('user_filter') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-medium text-white transition">Filter</button>
            @if(request('search') || request('aksi_filter') || request('user_filter') || request('date_from') || request('date_to'))
                <a href="{{ route('admin.log-aktivitas.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Reset</a>
            @endif
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800/50 bg-gray-50 dark:bg-white/[0.03]">
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Waktu</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">User</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Deskripsi</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">IP Address</th>
                        <th class="px-5 py-3 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($logs as $i => $log)
                    @php
                        $badge = match(true) {
                            str_starts_with($log->aksi, 'create') => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                            str_starts_with($log->aksi, 'update') => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
                            str_starts_with($log->aksi, 'delete') => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                            str_starts_with($log->aksi, 'login') => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                            str_starts_with($log->aksi, 'export') => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="px-5 py-3 font-medium text-gray-800 dark:text-white/90">{{ $log->user->name ?? 'System' }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">{{ $log->aksi }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ $log->deskripsi ?? '-' }}</td>
                        <td class="px-5 py-3 text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.log-aktivitas.show', $log) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada log aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
