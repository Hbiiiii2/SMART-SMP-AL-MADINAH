@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Edit Update History</h2>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.update-history.update', $history->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Versi <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="versi" value="{{ $history->versi }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                    @error('versi')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Judul Pembaruan <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="judul" value="{{ $history->judul }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                    @error('judul')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Deskripsi Detail <span class="text-error-500">*</span>
                    </label>
                    <!-- Quill Editor Container -->
                    <div id="editor-container" class="mb-2 bg-white text-gray-800 dark:bg-gray-900 dark:text-white/90">{!! $history->deskripsi !!}</div>
                    <!-- Hidden input to store Quill HTML content -->
                    <input type="hidden" name="deskripsi" id="deskripsi" value="{{ $history->deskripsi }}">
                    @error('deskripsi')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="md:col-span-2 mt-2">
                    <div x-data="{ checkboxToggle: {{ $history->is_published ? 'true' : 'false' }} }">
                        <label for="checkboxPublish"
                            class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                            <div class="relative">
                                <input type="checkbox" id="checkboxPublish" name="is_published" value="1" class="sr-only"
                                    @change="checkboxToggle = !checkboxToggle" {{ $history->is_published ? 'checked' : '' }} />
                                <div :class="checkboxToggle ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                                    class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                                    <span :class="checkboxToggle ? '' : 'opacity-0'">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            Publish (centang agar bisa dilihat Admin Sekolah)
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <a href="{{ route('admin.update-history.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit" id="submitBtn"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Include Quill Stylesheet -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<!-- Include Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Sync Quill content to hidden input before submitting form
        document.getElementById('submitBtn').addEventListener('click', function() {
            var html = quill.root.innerHTML;
            if (quill.getText().trim().length === 0 && !html.includes('<img')) {
                document.getElementById('deskripsi').value = '';
            } else {
                document.getElementById('deskripsi').value = html;
            }
        });
    });
</script>

<style>
    .ql-container {
        min-height: 16rem;
        height: auto;
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
        font-family: inherit;
        border: 1px solid #d1d5db !important; /* gray-300 */
        border-top: none !important;
    }
    .dark .ql-container {
        border-color: #374151 !important; /* gray-700 */
    }
    .ql-toolbar {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        border: 1px solid #d1d5db !important; /* gray-300 */
    }
    .dark .ql-snow .ql-toolbar, .dark .ql-snow.ql-toolbar {
        border-color: #374151; /* gray-700 */
    }
    .dark .ql-snow .ql-container, .dark .ql-snow.ql-container {
        border-color: #374151;
    }
    .dark .ql-snow .ql-picker {
        color: rgba(255, 255, 255, 0.9);
    }
    .dark .ql-snow .ql-stroke {
        stroke: rgba(255, 255, 255, 0.9);
    }
    .dark .ql-snow .ql-fill {
        fill: rgba(255, 255, 255, 0.9);
    }
</style>
@endsection
