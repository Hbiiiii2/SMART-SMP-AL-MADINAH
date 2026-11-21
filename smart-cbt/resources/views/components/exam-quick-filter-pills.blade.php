@props([
    'routeName',
    'summary' => [],
])

@php
    $baseParams = request()->except(['soal_status', 'pelaksanaan', 'status', 'page']);
    $activeSoal = request('soal_status');
    $activePelaksanaan = request('pelaksanaan');
    $activeStatus = request('status');
    $isAllActive = !$activeSoal && !$activePelaksanaan && !$activeStatus;

    $pills = [
        [
            'label' => 'Semua Ujian',
            'count' => $summary['total'] ?? 0,
            'href' => route($routeName, $baseParams),
            'active' => $isAllActive,
            'activeClass' => 'bg-slate-900 text-white border-slate-900',
            'idleClass' => 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-slate-100 text-slate-600',
        ],
        [
            'label' => 'Sudah Ada Soal',
            'count' => $summary['sudah_ada_soal'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['soal_status' => 'sudah_ada'])),
            'active' => $activeSoal === 'sudah_ada',
            'activeClass' => 'bg-blue-600 text-white border-blue-600',
            'idleClass' => 'bg-white text-blue-700 border-blue-200 hover:bg-blue-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-blue-50 text-blue-700',
        ],
        [
            'label' => 'Belum Ada Soal',
            'count' => $summary['belum_ada_soal'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['soal_status' => 'belum_ada'])),
            'active' => $activeSoal === 'belum_ada',
            'activeClass' => 'bg-amber-600 text-white border-amber-600',
            'idleClass' => 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-amber-50 text-amber-700',
        ],
        [
            'label' => 'Sudah Pernah Ujian',
            'count' => $summary['sudah_pernah_ujian'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['pelaksanaan' => 'sudah_ujian'])),
            'active' => $activePelaksanaan === 'sudah_ujian',
            'activeClass' => 'bg-indigo-600 text-white border-indigo-600',
            'idleClass' => 'bg-white text-indigo-700 border-indigo-200 hover:bg-indigo-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-indigo-50 text-indigo-700',
        ],
        [
            'label' => 'Belum Pernah Ujian',
            'count' => $summary['belum_pernah_ujian'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['pelaksanaan' => 'belum_ujian'])),
            'active' => $activePelaksanaan === 'belum_ujian',
            'activeClass' => 'bg-slate-700 text-white border-slate-700',
            'idleClass' => 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-slate-100 text-slate-600',
        ],
        [
            'label' => 'Status Aktif',
            'count' => $summary['aktif'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['status' => 'aktif'])),
            'active' => $activeStatus === 'aktif',
            'activeClass' => 'bg-emerald-600 text-white border-emerald-600',
            'idleClass' => 'bg-white text-emerald-700 border-emerald-200 hover:bg-emerald-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-emerald-50 text-emerald-700',
        ],
        [
            'label' => 'Draft',
            'count' => $summary['draft'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['status' => 'draft'])),
            'active' => $activeStatus === 'draft',
            'activeClass' => 'bg-amber-500 text-white border-amber-500',
            'idleClass' => 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-amber-50 text-amber-700',
        ],
        [
            'label' => 'Selesai',
            'count' => $summary['selesai'] ?? 0,
            'href' => route($routeName, array_merge($baseParams, ['status' => 'selesai'])),
            'active' => $activeStatus === 'selesai',
            'activeClass' => 'bg-slate-800 text-white border-slate-800',
            'idleClass' => 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
            'badgeActive' => 'bg-white/20 text-white',
            'badgeIdle' => 'bg-slate-100 text-slate-600',
        ],
    ];
@endphp

<div class="flex flex-wrap items-center gap-2">
    @foreach ($pills as $pill)
        <a
            href="{{ $pill['href'] }}"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold shadow-sm transition {{ $pill['active'] ? $pill['activeClass'] : $pill['idleClass'] }}"
        >
            <span>{{ $pill['label'] }}</span>
            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $pill['active'] ? $pill['badgeActive'] : $pill['badgeIdle'] }}">
                {{ number_format((int) $pill['count']) }}
            </span>
        </a>
    @endforeach
</div>
