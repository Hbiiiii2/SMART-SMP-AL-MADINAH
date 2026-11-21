@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Laravel Log Files</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Log error dan exception dari framework</p>
        </div>
        <a href="{{ route('admin.log-aktivitas.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Log Aktivitas
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">File Log</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800/50 max-h-[500px] overflow-y-auto">
                    @forelse($files as $file)
                        <a href="{{ route('admin.log-aktivitas.laravel-logs', ['file' => $file['name']]) }}"
                            class="flex items-center justify-between px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-white/[0.02] {{ $selectedFile === $file['name'] ? 'bg-brand-50 dark:bg-brand-500/5' : '' }}">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-800 dark:text-white/90 truncate">{{ $file['name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $file['modified'] }}</div>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ round($file['size'] / 1024, 1) }} KB</span>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-400">Tidak ada file log.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            @if($selectedFile && $logContent)
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $selectedFile }}</h3>
                        <span class="text-xs text-gray-400">{{ count($logContent) }} baris terakhir</span>
                    </div>
                    <div class="overflow-x-auto">
                        <pre class="p-4 text-xs leading-relaxed text-gray-600 dark:text-gray-400 font-mono whitespace-pre-wrap break-all max-h-[500px] overflow-y-auto">@foreach($logContent as $line)
{{ $line }}@endforeach</pre>
                    </div>
                </div>
            @elseif($selectedFile)
                <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-sm text-gray-400">File log kosong.</p>
                </div>
            @else
                <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                    <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm text-gray-400">Pilih file log di sebelah kiri untuk melihat isinya.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
