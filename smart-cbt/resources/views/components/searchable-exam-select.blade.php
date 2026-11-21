@props([
    'name' => 'exam_id',
    'exams' => [],
    'selected' => '',
    'placeholder' => 'Semua Ujian',
    'autoSubmit' => false,
    'id' => null,
])

@php
    $componentId = $id ?? 'exam-picker-' . uniqid();
    $selectedStr = (string) ($selected ?? '');
@endphp

<div
    x-data="{
        open: false,
        search: '',
        filterMode: 'all',
        selectedId: @js($selectedStr),
        items: @js($exams),
        autoSubmit: @js((bool) $autoSubmit),
        get selectedItem() {
            if (!this.selectedId) return null;
            return this.items.find((i) => String(i.id) === String(this.selectedId)) || null;
        },
        get filteredItems() {
            const q = this.search.trim().toLowerCase();
            return this.items.filter((item) => {
                if (this.filterMode === 'sudah_ujian' && Number(item.attempts_count) <= 0) return false;
                if (this.filterMode === 'belum_ujian' && Number(item.attempts_count) > 0) return false;
                if (!q) return true;
                const haystack = [
                    item.nama,
                    item.kode_ujian,
                    item.mapel,
                    item.kelas,
                    item.guru,
                    item.tahun_ajaran,
                    item.semester,
                ].filter(Boolean).join(' ').toLowerCase();
                return haystack.includes(q);
            });
        },
        selectItem(id) {
            this.selectedId = id === null ? '' : String(id);
            this.open = false;
            if (this.autoSubmit) {
                this.$nextTick(() => {
                    this.$refs.hiddenInput.form?.submit();
                });
            }
        }
    }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="relative"
>
    <input x-ref="hiddenInput" type="hidden" id="{{ $componentId }}" name="{{ $name }}" :value="selectedId">

    <div class="relative flex items-center">
        <button
            type="button"
            @click="open = !open; if (open) { $nextTick(() => $refs.searchInput?.focus()); }"
            class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-sm transition hover:border-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            :aria-expanded="open"
        >
            <div class="min-w-0 flex-1">
                <template x-if="selectedItem">
                    <div class="flex items-center gap-2 truncate">
                        <span class="truncate font-semibold text-slate-900" x-text="selectedItem.nama"></span>
                        <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600" x-text="selectedItem.kelas"></span>
                    </div>
                </template>
                <template x-if="!selectedItem">
                    <span class="block truncate text-slate-500">{{ $placeholder }}</span>
                </template>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <span
                    x-show="selectedId !== ''"
                    x-cloak
                    @click.stop="selectItem(null)"
                    title="Hapus pilihan ujian"
                    class="inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
                <svg class="h-4 w-4 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        class="absolute left-0 right-0 z-50 mt-1.5 w-full min-w-[290px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="border-b border-slate-100 bg-slate-50/70 p-2.5 space-y-2">
            <div class="relative">
                <svg class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <input
                    x-ref="searchInput"
                    x-model="search"
                    type="text"
                    placeholder="Cari nama ujian, kode, mapel, kelas..."
                    class="w-full rounded-lg border border-slate-300 bg-white py-1.5 pl-8 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div class="flex flex-wrap gap-1">
                <button
                    type="button"
                    @click="filterMode = 'all'"
                    class="rounded-md px-2 py-1 text-[11px] font-semibold transition"
                    :class="filterMode === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                >
                    Semua (<span x-text="items.length"></span>)
                </button>
                <button
                    type="button"
                    @click="filterMode = 'sudah_ujian'"
                    class="rounded-md px-2 py-1 text-[11px] font-semibold transition"
                    :class="filterMode === 'sudah_ujian' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                >
                    Sudah Pernah Ujian (<span x-text="items.filter(i => Number(i.attempts_count) > 0).length"></span>)
                </button>
                <button
                    type="button"
                    @click="filterMode = 'belum_ujian'"
                    class="rounded-md px-2 py-1 text-[11px] font-semibold transition"
                    :class="filterMode === 'belum_ujian' ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                >
                    Belum Ujian (<span x-text="items.filter(i => Number(i.attempts_count) <= 0).length"></span>)
                </button>
            </div>
        </div>

        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
            <button
                type="button"
                @click="selectItem(null)"
                class="flex w-full items-center justify-between px-3 py-2 text-left text-xs transition hover:bg-slate-50"
                :class="selectedId === '' ? 'bg-blue-50/70 font-bold text-blue-700' : 'text-slate-600'"
            >
                <span>{{ $placeholder }}</span>
                <span x-show="selectedId === ''" class="text-blue-600">✓</span>
            </button>

            <template x-for="item in filteredItems" :key="item.id">
                <button
                    type="button"
                    @click="selectItem(item.id)"
                    class="flex w-full flex-col gap-1 px-3 py-2.5 text-left transition hover:bg-slate-50"
                    :class="String(selectedId) === String(item.id) ? 'bg-blue-50/70' : ''"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-slate-900" x-text="item.nama"></p>
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                <span class="font-mono text-slate-600" x-text="item.kode_ujian"></span>
                                &bull;
                                <span x-text="item.mapel"></span>
                                &bull;
                                <span>Kelas <span x-text="item.kelas"></span></span>
                                <template x-if="item.tahun_ajaran">
                                    <span>&bull; <span x-text="item.tahun_ajaran"></span></span>
                                </template>
                            </p>
                        </div>
                        <span x-show="String(selectedId) === String(item.id)" class="shrink-0 text-xs font-bold text-blue-600">✓</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :class="Number(item.attempts_count) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500'"
                            x-text="Number(item.attempts_count) > 0 ? ('Sudah Ujian • ' + item.attempts_count + ' peserta') : 'Belum Pernah Ujian'"
                        ></span>
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="Number(item.questions_count) > 0 ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700'"
                            x-text="Number(item.questions_count) > 0 ? (item.questions_count + ' Soal') : 'Belum Ada Soal'"
                        ></span>
                    </div>
                </button>
            </template>

            <div x-show="filteredItems.length === 0" class="px-4 py-6 text-center text-xs text-slate-500">
                Tidak ada ujian yang cocok dengan pencarian.
            </div>
        </div>
    </div>
</div>
