<div
    x-data="{
        open: false,
        confirmed: false,
        deleteSyncedBanks: false,
        submitting: false,
        exam: {
            action: '',
            nama: '',
            kode_ujian: '',
            mapel: '',
            kelas: '',
            guru: '',
            questions_count: 0,
            attempts_count: 0,
            in_progress_count: 0,
            synced_banks_count: 0,
            return_query: '',
        },
        get hasDependents() {
            return Number(this.exam.questions_count || 0) > 0 || Number(this.exam.attempts_count || 0) > 0;
        },
        openModal(payload) {
            this.exam = Object.assign({
                action: '',
                nama: '',
                kode_ujian: '',
                mapel: '',
                kelas: '',
                guru: '',
                questions_count: 0,
                attempts_count: 0,
                in_progress_count: 0,
                synced_banks_count: 0,
                return_query: '',
            }, payload || {});
            this.confirmed = false;
            this.deleteSyncedBanks = false;
            this.submitting = false;
            this.open = true;
        },
        closeModal() {
            if (this.submitting) {
                return;
            }
            this.open = false;
        }
    }"
    x-cloak
    x-show="open"
    @open-delete-exam-modal.window="openModal($event.detail)"
    @keydown.escape.window="closeModal()"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="delete-exam-modal-title"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
        @click="closeModal()"
    ></div>

    {{-- Dialog Container --}}
    <div class="relative z-10 flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.stop
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl"
        >
            <form
                :action="exam.action"
                method="POST"
                @submit="if ((hasDependents && !confirmed) || submitting) { $event.preventDefault(); return; } submitting = true;"
            >
                @csrf
                @method('DELETE')
                <input type="hidden" name="force_delete" value="1" :disabled="!hasDependents">
                <input type="hidden" name="return_query" :value="exam.return_query">

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 id="delete-exam-modal-title" class="text-lg font-bold text-slate-900">
                                Hapus Jadwal Ujian
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Tindakan ini akan menghapus jadwal ujian secara permanen dari tampilan Admin, Guru, maupun Siswa.
                            </p>
                        </div>
                    </div>

                    {{-- Target Exam Summary --}}
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-slate-900" x-text="exam.nama"></p>
                            <span
                                class="rounded bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200"
                                x-text="exam.kode_ujian || '-'"
                            ></span>
                        </div>
                        <p class="mt-1 text-xs text-slate-600">
                            <span x-text="exam.mapel || '-'"></span>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>Kelas <span x-text="exam.kelas || '-'"></span></span>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>PIC: <span x-text="exam.guru || '-'"></span></span>
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">
                                <span x-text="exam.questions_count"></span>&nbsp;Soal
                            </span>
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                <span x-text="exam.attempts_count"></span>&nbsp;Peserta
                            </span>
                        </div>
                    </div>

                    {{-- In-progress Warning --}}
                    <div
                        x-show="Number(exam.in_progress_count || 0) > 0"
                        class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800"
                    >
                        <p class="font-bold">
                            Perhatian: Ada <span x-text="exam.in_progress_count"></span> siswa yang sedang mengerjakan ujian ini!
                        </p>
                        <p class="mt-0.5">
                            Jika dihapus sekarang, sesi pengerjaan aktif siswa tersebut akan langsung terhenti dan tidak dapat dilanjutkan.
                        </p>
                    </div>

                    {{-- Dependent Data Permanent Warning --}}
                    <div
                        x-show="hasDependents"
                        class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800"
                    >
                        <p class="font-bold">Penghapusan Data Terkait (Tidak Dapat Dibatalkan):</p>
                        <ul class="mt-1.5 list-disc space-y-1 pl-4">
                            <li>Seluruh butir soal pada paket ujian ini (<span class="font-semibold" x-text="exam.questions_count"></span> soal) akan ikut terhapus.</li>
                            <li>Seluruh riwayat pengerjaan, jawaban, lampiran foto essay, dan nilai siswa (<span class="font-semibold" x-text="exam.attempts_count"></span> peserta) akan ikut terhapus.</li>
                            <li>Jadwal ujian ini tidak akan lagi muncul di dashboard maupun daftar ujian Guru dan Siswa.</li>
                        </ul>
                    </div>

                    {{-- Optional Synced Bank Cleanup --}}
                    <label
                        x-show="Number(exam.synced_banks_count || 0) > 0"
                        class="mt-4 flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700 hover:bg-slate-50"
                    >
                        <input
                            type="checkbox"
                            name="delete_synced_banks"
                            value="1"
                            x-model="deleteSyncedBanks"
                            :disabled="Number(exam.synced_banks_count || 0) <= 0"
                            class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                        >
                        <span>
                            <span class="block font-semibold text-slate-800">
                                Hapus juga salinan Bank Soal yang tersinkron dari ujian ini (<span x-text="exam.synced_banks_count"></span> bank)
                            </span>
                            <span class="mt-0.5 block text-slate-500">
                                Biarkan tidak dicentang jika guru masih ingin menyimpan salinan soalnya di Bank Soal.
                            </span>
                        </span>
                    </label>

                    {{-- Mandatory Confirmation Checkbox when exam has questions or attempts --}}
                    <label
                        x-show="hasDependents"
                        class="mt-4 flex cursor-pointer items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-900"
                    >
                        <input
                            type="checkbox"
                            x-model="confirmed"
                            class="mt-0.5 rounded border-rose-300 text-rose-600 focus:ring-rose-500"
                        >
                        <span class="font-semibold">
                            Saya memahami bahwa jadwal ujian beserta seluruh soal, jawaban, lampiran, dan nilai peserta akan dihapus permanen.
                        </span>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="closeModal()"
                        :disabled="submitting"
                        class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-100 disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="(hasDependents && !confirmed) || submitting"
                        class="inline-flex justify-center rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-text="submitting ? 'Menghapus...' : (hasDependents ? 'Hapus Permanen' : 'Hapus Jadwal Ujian')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
