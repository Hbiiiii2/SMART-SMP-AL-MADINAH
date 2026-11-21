@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
            Update History Aplikasi
        </h2>
        
        @if(auth()->user()->role === 'super_admin')
        <a href="{{ route('admin.update-history.create') }}" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Update
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-6" x-data="{ selectedHistory: {{ $histories->first()->id ?? 'null' }} }">
        
        <!-- Sidebar / Master -->
        <div class="w-full md:w-1/4 flex flex-col gap-4">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 py-4 px-6 dark:border-gray-800">
                    <h3 class="font-medium text-gray-800 dark:text-white/90">
                        Release list
                    </h3>
                </div>
                
                <div class="flex flex-col py-2">
                    @foreach($histories as $history)
                    @if($history->is_published || auth()->user()->role === 'super_admin')
                    <button 
                        @click="selectedHistory = {{ $history->id }}"
                        :class="selectedHistory === {{ $history->id }} ? 'bg-brand-500/10 text-brand-500 border-l-4 border-brand-500 dark:bg-brand-500/20' : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.02] border-l-4 border-transparent'"
                        class="flex items-center justify-between px-6 py-3 text-left font-medium transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            {{ $history->versi }}
                            @if(!$history->is_published)
                                <span class="inline-flex rounded-lg bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Draft</span>
                            @endif
                        </span>
                    </button>
                    @endif
                    @endforeach
                </div>
                
                @if($histories->count() == 0)
                <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
                    Belum ada release.
                </div>
                @endif
            </div>
        </div>

        <!-- Main Content / Detail -->
        <div class="w-full md:w-3/4">
            @foreach($histories as $index => $history)
            @if($history->is_published || auth()->user()->role === 'super_admin')
            <div 
                x-show="selectedHistory === {{ $history->id }}" 
                x-transition
                class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
                style="display: {{ $index === 0 ? 'block' : 'none' }};"
            >
                <div class="border-b border-gray-200 py-4 px-6 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <h3 class="font-medium text-gray-800 dark:text-white/90">
                            Release {{ $history->versi }}
                        </h3>
                        @if($index === 0 && $history->is_published)
                            <span class="inline-flex rounded-lg bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                Latest
                            </span>
                        @endif
                    </div>

                    @if(auth()->user()->role === 'super_admin')
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.update-history.edit', $history->id) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                            Edit
                        </a>
                        <form action="{{ route('admin.update-history.destroy', $history->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus history ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-3 py-1.5 text-xs font-medium text-error-700 transition hover:bg-error-100 dark:border-error-700 dark:bg-error-500/10 dark:text-error-400">
                                Hapus
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                <div class="p-6">
                    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mb-8 pb-6 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded-full bg-brand-500/20 flex items-center justify-center text-brand-500 font-bold text-sm">
                                SA
                            </div>
                            <span class="text-gray-800 dark:text-white/90 font-medium">super_admin</span>
                        </div>
                        <span>released this {{ $history->created_at->diffForHumans() }}</span>
                        <span class="flex items-center gap-1">
                            <svg class="fill-current w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                            {{ $history->created_at->format('d M Y, H:i') }}
                        </span>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90 mb-6">
                        {{ $history->judul }}
                    </h1>

                    <!-- Markdown/Quill Content -->
                    <div class="prose prose-sm sm:prose-base dark:prose-invert max-w-none text-gray-800 dark:text-gray-300">
                        {!! $history->deskripsi !!}
                    </div>
                </div>
                
            </div>
            @endif
            @endforeach
            
            @if($histories->count() == 0)
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-xl font-medium text-gray-800 dark:text-white/90 mb-2">Belum ada release history.</h3>
                <p class="text-gray-500 dark:text-gray-400">Pembaruan sistem akan muncul di sini.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Adjust Quill output styling if necessary */
.prose ul {
    list-style-type: disc;
    padding-left: 1.5em;
    margin-top: 1em;
    margin-bottom: 1em;
}
.prose ol {
    list-style-type: decimal;
    padding-left: 1.5em;
    margin-top: 1em;
    margin-bottom: 1em;
}
.prose p {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}
</style>
@endsection
