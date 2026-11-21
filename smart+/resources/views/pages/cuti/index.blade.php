@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Pengajuan Cuti</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar pengajuan cuti dan riwayat cuti.</p>
        </div>
        
        @if(auth()->user()->isGuru() || auth()->user()->isStaffAkademik())
        <div>
            @php
                $createRoute = auth()->user()->isGuru() ? route('guru.cuti.create') : route('staff.cuti.create');
            @endphp
            <a href="{{ $createRoute }}" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                Buat Pengajuan Cuti
            </a>
        </div>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-success-50 p-4 text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-error-700 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50 dark:border-gray-800/50 dark:bg-gray-800/20">
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Jenis Cuti</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($cutiList as $cuti)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $cuti->user->name }}</p>
                            <span class="text-xs text-gray-500">{{ $cuti->user->role }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $cuti->jenis_cuti }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->format('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @php $badge = match($cuti->status) {
                                'disetujui' => 'bg-success-50 text-success-700 dark:bg-success-500/10',
                                'ditolak' => 'bg-error-50 text-error-700 dark:bg-error-500/10',
                                'pending' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/10',
                                default => 'bg-gray-50 text-gray-700',
                            }; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                                {{ ucfirst($cuti->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <!-- Process Form for Kepsek/Direktur -->
                            @if($cuti->status == 'pending' && (auth()->user()->isKepalaSekolah() || auth()->user()->isDirektur()))
                                @php
                                    $processRouteName = auth()->user()->isKepalaSekolah() ? 'ks.cuti.process' : 'direktur.cuti.process';
                                @endphp
                                <form action="{{ route($processRouteName, $cuti->id) }}" method="POST" class="inline-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="status" value="disetujui">
                                    <button type="submit" class="text-xs font-medium text-success-500 hover:text-success-600">Setujui</button>
                                </form>
                                <form action="{{ route($processRouteName, $cuti->id) }}" method="POST" class="inline-flex gap-2 ml-2">
                                    @csrf
                                    <input type="hidden" name="status" value="ditolak">
                                    <button type="submit" class="text-xs font-medium text-error-500 hover:text-error-600">Tolak</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">View</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Belum ada data pengajuan cuti.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
