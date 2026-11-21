@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Backup & Restore Database</h2>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Export Section -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Backup Database
            </h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">
                Unduh seluruh data database saat ini dalam format <code>.sql</code>. Lakukan backup secara berkala untuk menghindari kehilangan data.
            </p>
            <a href="{{ route('admin.backup.export') }}"
                class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white transition w-full shadow-theme-xs">
                Export Database (Backup)
            </a>
        </div>

        <!-- Import Section -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <h3 class="text-xl font-medium text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-error-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Restore Database
            </h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">
                Unggah file <code>.sql</code> hasil backup sebelumnya untuk memulihkan data. <br/> <strong class="text-error-500">Peringatan:</strong> Proses ini akan menimpa seluruh data yang ada saat ini.
            </p>

            <form action="{{ route('admin.backup.import') }}" method="POST" enctype="multipart/form-data" x-data="{ showWarning: false }">
                @csrf
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">File Backup (.sql)</label>
                    <input type="file" name="backup_file" accept=".sql" required
                        class="w-full cursor-pointer rounded-lg border-[1.5px] border-gray-300 bg-transparent font-medium outline-none transition file:mr-5 file:border-collapse file:cursor-pointer file:border-0 file:border-r file:border-solid file:border-gray-300 file:bg-gray-50 file:px-5 file:py-3 file:hover:bg-gray-100 focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:file:border-gray-700 dark:file:bg-white/[0.03] dark:file:text-white/90 dark:focus:border-brand-500" />
                    @error('backup_file')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    @click.prevent="if(confirm('PERINGATAN!\n\nProses ini akan menimpa (overwrite) seluruh database saat ini dengan data dari file backup.\nApakah Anda yakin ingin melanjutkan?')) { $el.closest('form').submit() }"
                    class="bg-error-500 hover:bg-error-600 inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white transition w-full shadow-theme-xs">
                    Import Database (Restore)
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
