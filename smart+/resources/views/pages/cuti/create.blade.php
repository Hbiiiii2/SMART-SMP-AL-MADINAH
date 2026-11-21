@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-md p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Buat Pengajuan Cuti</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Isi form di bawah ini untuk mengajukan cuti.</p>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @php
            $storeRoute = auth()->user()->isGuru() ? route('guru.cuti.store') : route('staff.cuti.store');
            $indexRoute = auth()->user()->isGuru() ? route('guru.cuti.index') : route('staff.cuti.index');
        @endphp
        <form action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
            @csrf
            
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">Jenis Cuti <span class="text-error-500">*</span></label>
                <select name="jenis_cuti" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:focus:border-brand-500">
                    <option value="">Pilih Jenis Cuti</option>
                    <option value="Sakit">Sakit</option>
                    <option value="Izin">Izin</option>
                    <option value="Tahunan">Tahunan</option>
                    <option value="Melahirkan">Melahirkan</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">Tanggal Mulai <span class="text-error-500">*</span></label>
                    <input type="date" name="tanggal_mulai" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">Tanggal Selesai <span class="text-error-500">*</span></label>
                    <input type="date" name="tanggal_selesai" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">Alasan <span class="text-error-500">*</span></label>
                <textarea name="alasan" rows="4" required class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:focus:border-brand-500" placeholder="Jelaskan alasan cuti..."></textarea>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white/90">Lampiran (Opsional)</label>
                <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 dark:border-gray-700 dark:focus:border-brand-500">
                <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, PDF. Maksimal 2MB.</p>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <a href="{{ $indexRoute }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Batal</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Ajukan Cuti</button>
            </div>
        </form>
    </div>
</div>
@endsection
