@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Edit Kartu RFID</h2>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.rfid.update', $rfid) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div x-data="searchableSelect({{ json_encode($siswas->map(fn($s) => ['id' => $s->id, 'name' => $s->nama_lengkap, 'code' => $s->nisn])) }}, {{ json_encode($gurus->map(fn($g) => ['id' => $g->id, 'name' => $g->nama_lengkap, 'code' => $g->nip])) }}, '{{ old('siswa_id', $rfid->siswa_id ?? '') }}', '{{ old('guru_id', $rfid->guru_id ?? '') ? 'guru' : 'siswa' }}')">

                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipe Pengguna <span class="text-error-500">*</span></label>
                    <div class="mb-3 flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipe" value="siswa" x-model="tipe" @change="selectedId=''; selectedName=''; open=false">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Siswa</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipe" value="guru" x-model="tipe" @change="selectedId=''; selectedName=''; open=false">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Guru</span>
                        </label>
                    </div>

                    <input type="hidden" :name="tipe === 'guru' ? 'guru_id' : 'siswa_id'" :value="selectedId">

                    <div class="relative" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-left text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <span x-text="selectedName || ('Pilih ' + (tipe === 'guru' ? 'Guru' : 'Siswa'))"></span>
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                            <div class="p-2">
                                <input type="text" x-model="search" placeholder="Ketik untuk mencari..."
                                    class="h-9 w-full rounded border border-gray-200 px-3 text-sm focus:border-brand-300 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                            </div>
                            <div class="max-h-48 overflow-y-auto">
                                <template x-for="item in filtered" :key="item.id">
                                    <button type="button" @click="selectItem(item)"
                                        class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-white/5"
                                        :class="selectedId == item.id ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 dark:text-gray-300'">
                                        <span class="font-medium" x-text="item.name"></span>
                                        <span class="ml-1 text-xs text-gray-400" x-text="'(' + item.code + ')'"></span>
                                    </button>
                                </template>
                                <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ditemukan</div>
                            </div>
                        </div>
                    </div>

                    @error('siswa_id')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                    @error('guru_id')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">UID <span class="text-error-500">*</span></label>
                    <input type="text" name="uid" value="{{ old('uid', $rfid->uid) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('uid')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $rfid->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Aktif</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <a href="{{ route('admin.rfid.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
                    Batal
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Perbarui
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function searchableSelect(siswaItems, guruItems, initialSiswaId, initialTipe) {
    return {
        tipe: initialTipe || 'siswa',
        search: '',
        open: false,
        selectedId: initialSiswaId || '',
        selectedName: '',
        siswaItems: siswaItems,
        guruItems: guruItems,
        get currentItems() {
            return this.tipe === 'guru' ? this.guruItems : this.siswaItems;
        },
        get filtered() {
            const q = this.search.toLowerCase();
            return this.currentItems.filter(i =>
                i.name.toLowerCase().includes(q) || i.code.toLowerCase().includes(q)
            );
        },
        selectItem(item) {
            this.selectedId = item.id;
            this.selectedName = item.name + ' (' + item.code + ')';
            this.open = false;
            this.search = '';
        },
        init() {
            if (this.selectedId) {
                const found = this.currentItems.find(i => i.id == this.selectedId);
                if (found) this.selectItem(found);
            }
        }
    };
}
</script>
@endpush
