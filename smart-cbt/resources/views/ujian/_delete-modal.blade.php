<div
    id="delete-exam-modal"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="delete-exam-modal-title"
>
    {{-- Backdrop --}}
    <div
        id="delete-exam-modal-backdrop"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
        onclick="window.closeDeleteExamModal()"
    ></div>

    {{-- Dialog Container --}}
    <div class="relative z-10 flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
        <div
            onclick="event.stopPropagation()"
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl"
        >
            <form id="delete-exam-form" action="" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" id="delete-exam-force-input" name="force_delete" value="1" disabled>
                <input type="hidden" id="delete-exam-return-query" name="return_query" value="">
                <input type="hidden" id="delete-exam-return-route" name="return_route" value="">

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
                            <p id="delete-exam-nama" class="text-sm font-bold text-slate-900"></p>
                            <span
                                id="delete-exam-kode"
                                class="rounded bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200"
                            >-</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-600">
                            <span id="delete-exam-mapel">-</span>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>Kelas <span id="delete-exam-kelas">-</span></span>
                            <span class="mx-1 text-slate-300">&bull;</span>
                            <span>PIC: <span id="delete-exam-guru">-</span></span>
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">
                                <span id="delete-exam-questions-count">0</span>&nbsp;Soal
                            </span>
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                <span id="delete-exam-attempts-count">0</span>&nbsp;Peserta
                            </span>
                        </div>
                    </div>

                    {{-- In-progress Warning --}}
                    <div
                        id="delete-exam-in-progress-box"
                        style="display: none;"
                        class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800"
                    >
                        <p class="font-bold">
                            Perhatian: Ada <span id="delete-exam-in-progress-count">0</span> siswa yang sedang mengerjakan ujian ini!
                        </p>
                        <p class="mt-0.5">
                            Jika dihapus sekarang, sesi pengerjaan aktif siswa tersebut akan langsung terhenti dan tidak dapat dilanjutkan.
                        </p>
                    </div>

                    {{-- Dependent Data Permanent Warning --}}
                    <div
                        id="delete-exam-dependents-box"
                        style="display: none;"
                        class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800"
                    >
                        <p class="font-bold">Penghapusan Data Terkait (Tidak Dapat Dibatalkan):</p>
                        <ul class="mt-1.5 list-disc space-y-1 pl-4">
                            <li>Seluruh butir soal pada paket ujian ini (<span id="delete-exam-questions-count-warn" class="font-semibold">0</span> soal) akan ikut terhapus.</li>
                            <li>Seluruh riwayat pengerjaan, jawaban, lampiran foto essay, dan nilai siswa (<span id="delete-exam-attempts-count-warn" class="font-semibold">0</span> peserta) akan ikut terhapus.</li>
                            <li>Jadwal ujian ini tidak akan lagi muncul di dashboard maupun daftar ujian Guru dan Siswa.</li>
                        </ul>
                    </div>

                    {{-- Optional Synced Bank Cleanup --}}
                    <label
                        id="delete-exam-synced-banks-label"
                        style="display: none;"
                        class="mt-4 flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700 hover:bg-slate-50"
                    >
                        <input
                            type="checkbox"
                            id="delete-exam-synced-banks-checkbox"
                            name="delete_synced_banks"
                            value="1"
                            disabled
                            class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                        >
                        <span>
                            <span class="block font-semibold text-slate-800">
                                Hapus juga salinan Bank Soal yang tersinkron dari ujian ini (<span id="delete-exam-synced-banks-count">0</span> bank)
                            </span>
                            <span class="mt-0.5 block text-slate-500">
                                Biarkan tidak dicentang jika guru masih ingin menyimpan salinan soalnya di Bank Soal.
                            </span>
                        </span>
                    </label>

                    {{-- Mandatory Confirmation Checkbox when exam has questions or attempts --}}
                    <label
                        id="delete-exam-confirm-label"
                        style="display: none;"
                        class="mt-4 flex cursor-pointer items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-900"
                    >
                        <input
                            type="checkbox"
                            id="delete-exam-confirm-checkbox"
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
                        id="delete-exam-cancel-btn"
                        onclick="window.closeDeleteExamModal()"
                        class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-100 disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        id="delete-exam-submit-btn"
                        class="inline-flex justify-center rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span id="delete-exam-submit-text">Hapus Jadwal Ujian</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('delete-exam-modal');
    const form = document.getElementById('delete-exam-form');
    if (!modal || !form) {
        return;
    }

    const forceInput = document.getElementById('delete-exam-force-input');
    const returnQueryInput = document.getElementById('delete-exam-return-query');
    const returnRouteInput = document.getElementById('delete-exam-return-route');

    const namaEl = document.getElementById('delete-exam-nama');
    const kodeEl = document.getElementById('delete-exam-kode');
    const mapelEl = document.getElementById('delete-exam-mapel');
    const kelasEl = document.getElementById('delete-exam-kelas');
    const guruEl = document.getElementById('delete-exam-guru');
    const questionsCountEl = document.getElementById('delete-exam-questions-count');
    const attemptsCountEl = document.getElementById('delete-exam-attempts-count');

    const inProgressBox = document.getElementById('delete-exam-in-progress-box');
    const inProgressCountEl = document.getElementById('delete-exam-in-progress-count');

    const dependentsBox = document.getElementById('delete-exam-dependents-box');
    const questionsCountWarnEl = document.getElementById('delete-exam-questions-count-warn');
    const attemptsCountWarnEl = document.getElementById('delete-exam-attempts-count-warn');

    const syncedBanksLabel = document.getElementById('delete-exam-synced-banks-label');
    const syncedBanksCheckbox = document.getElementById('delete-exam-synced-banks-checkbox');
    const syncedBanksCountEl = document.getElementById('delete-exam-synced-banks-count');

    const confirmLabel = document.getElementById('delete-exam-confirm-label');
    const confirmCheckbox = document.getElementById('delete-exam-confirm-checkbox');

    const cancelBtn = document.getElementById('delete-exam-cancel-btn');
    const submitBtn = document.getElementById('delete-exam-submit-btn');
    const submitText = document.getElementById('delete-exam-submit-text');

    let currentHasDependents = false;
    let isSubmitting = false;

    function parsePayload(source) {
        if (!source) {
            return {};
        }
        if (source.dataset && typeof source.dataset.exam === 'string') {
            try {
                return JSON.parse(source.dataset.exam);
            } catch (e) {
                return {};
            }
        }
        if (typeof source === 'object') {
            return source;
        }
        return {};
    }

    function syncSubmitButton() {
        const blocked = (currentHasDependents && !confirmCheckbox.checked) || isSubmitting;
        submitBtn.disabled = blocked;
        cancelBtn.disabled = isSubmitting;
        if (isSubmitting) {
            submitText.textContent = 'Menghapus...';
        } else {
            submitText.textContent = currentHasDependents ? 'Hapus Permanen' : 'Hapus Jadwal Ujian';
        }
    }

    window.openDeleteExamModal = function (source) {
        const payload = parsePayload(source);
        const questionsCount = Number(payload.questions_count || 0);
        const attemptsCount = Number(payload.attempts_count || 0);
        const inProgressCount = Number(payload.in_progress_count || 0);
        const syncedBanksCount = Number(payload.synced_banks_count || 0);

        currentHasDependents = questionsCount > 0 || attemptsCount > 0;
        isSubmitting = false;

        form.setAttribute('action', payload.action || '');
        forceInput.disabled = !currentHasDependents;
        returnQueryInput.value = payload.return_query || '';
        returnRouteInput.value = payload.return_route || '';

        namaEl.textContent = payload.nama || '-';
        kodeEl.textContent = payload.kode_ujian || '-';
        mapelEl.textContent = payload.mapel || '-';
        kelasEl.textContent = payload.kelas || '-';
        guruEl.textContent = payload.guru || '-';
        questionsCountEl.textContent = String(questionsCount);
        attemptsCountEl.textContent = String(attemptsCount);

        inProgressCountEl.textContent = String(inProgressCount);
        inProgressBox.style.display = inProgressCount > 0 ? 'block' : 'none';

        questionsCountWarnEl.textContent = String(questionsCount);
        attemptsCountWarnEl.textContent = String(attemptsCount);
        dependentsBox.style.display = currentHasDependents ? 'block' : 'none';

        syncedBanksCountEl.textContent = String(syncedBanksCount);
        syncedBanksCheckbox.checked = false;
        syncedBanksCheckbox.disabled = syncedBanksCount <= 0;
        syncedBanksLabel.style.display = syncedBanksCount > 0 ? 'flex' : 'none';

        confirmCheckbox.checked = false;
        confirmLabel.style.display = currentHasDependents ? 'flex' : 'none';

        syncSubmitButton();
        modal.style.display = 'block';
        document.body.classList.add('overflow-y-hidden');
    };

    window.closeDeleteExamModal = function () {
        if (isSubmitting) {
            return;
        }
        modal.style.display = 'none';
        document.body.classList.remove('overflow-y-hidden');
    };

    confirmCheckbox.addEventListener('change', syncSubmitButton);

    form.addEventListener('submit', function (event) {
        if ((currentHasDependents && !confirmCheckbox.checked) || isSubmitting || !form.getAttribute('action')) {
            event.preventDefault();
            return;
        }
        isSubmitting = true;
        syncSubmitButton();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            window.closeDeleteExamModal();
        }
    });

    window.addEventListener('open-delete-exam-modal', function (event) {
        window.openDeleteExamModal(event.detail);
    });
})();
</script>
