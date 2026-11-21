@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-bold text-black dark:text-white">
        Detail Update History
    </h2>
    <a href="{{ route('admin.update-history.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 py-2 px-6 text-center font-medium text-white hover:bg-opacity-90" style="background-color: #6B7280;">
        Kembali
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white p-6 shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="mb-5 flex flex-wrap gap-4 items-center">
        <span class="inline-flex rounded bg-primary bg-opacity-10 py-1 px-3 text-sm font-medium text-primary">
            {{ $history->versi }}
        </span>
        <span class="text-gray-500 text-sm">
            Dirilis pada: {{ $history->tanggal->format('d M Y') }}
        </span>
        @if(!$history->is_published)
            <span class="inline-flex rounded bg-warning bg-opacity-10 py-1 px-3 text-xs font-medium text-warning">Status: Draft</span>
        @endif
    </div>

    <h3 class="mb-4 text-2xl font-semibold text-black dark:text-white">
        {{ $history->judul }}
    </h3>

    <div class="border-t border-stroke pt-5 dark:border-strokedark quill-content">
        <!-- Render Quill HTML safely, we use {!! !!} since only super_admin can create it -->
        {!! $history->deskripsi !!}
    </div>
</div>

<style>
    /* Basic styling for Quill content */
    .quill-content h1 { font-size: 2em; font-weight: bold; margin-bottom: 0.5em; }
    .quill-content h2 { font-size: 1.5em; font-weight: bold; margin-bottom: 0.5em; }
    .quill-content h3 { font-size: 1.17em; font-weight: bold; margin-bottom: 0.5em; }
    .quill-content p { margin-bottom: 1em; }
    .quill-content ul { list-style-type: disc; margin-left: 2em; margin-bottom: 1em; }
    .quill-content ol { list-style-type: decimal; margin-left: 2em; margin-bottom: 1em; }
    .quill-content blockquote { border-left: 4px solid #ccc; padding-left: 1em; color: #666; margin-bottom: 1em; }
    .quill-content pre { background: #f4f4f4; padding: 1em; overflow-x: auto; margin-bottom: 1em; }
</style>
@endsection
