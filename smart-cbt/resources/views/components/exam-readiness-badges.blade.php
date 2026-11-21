@props(['exam', 'showStatus' => true])

@php
    $effectiveStatus = $exam->effectiveStatus();
    $questionsCount = (int) ($exam->questions_count ?? 0);
    $attemptsCount = (int) ($exam->exam_attempts_count ?? 0);
    $finishedCount = (int) ($exam->finished_attempts_count ?? 0);
    $needsGradingCount = (int) ($exam->needs_grading_count ?? 0);

    $statusBadge = match ($effectiveStatus) {
        'aktif' => ['Aktif', 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200', 'bg-emerald-500'],
        'terjadwal' => ['Terjadwal', 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200', 'bg-sky-500'],
        'selesai' => ['Selesai', 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200', 'bg-slate-500'],
        default => ['Draft', 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200', 'bg-amber-500'],
    };
@endphp

<div class="flex flex-wrap items-center gap-1.5">
    @if ($showStatus)
        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusBadge[1] }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $statusBadge[2] }}"></span>
            {{ $statusBadge[0] }}
        </span>
    @endif

    @if ($questionsCount > 0)
        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold text-blue-700 ring-1 ring-inset ring-blue-200">
            {{ $questionsCount }} Soal &bull; Sudah Dibuat
        </span>
    @else
        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
            0 Soal &bull; Belum Dibuat
        </span>
    @endif

    @if ($attemptsCount > 0)
        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200">
            Sudah Pernah Ujian ({{ $attemptsCount }} peserta{{ $finishedCount > 0 ? ", {$finishedCount} selesai" : '' }})
        </span>
    @else
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500">
            Belum Pernah Ujian
        </span>
    @endif

    @if ($needsGradingCount > 0)
        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
            {{ $needsGradingCount }} Perlu Nilai Essay
        </span>
    @endif
</div>
