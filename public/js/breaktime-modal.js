(function () {
    'use strict';

    const cfg = () => window.BreaktimeModalConfig || {};
    const el = (id) => document.getElementById(id);

    let previewDate = null;
    let previewShift = null;
    let rows = [];

    function headers(json = true) {
        const h = {
            'X-CSRF-TOKEN': cfg().csrf || '',
            Accept: 'application/json',
        };
        if (json) {
            h['Content-Type'] = 'application/json';
        }
        return h;
    }

    async function api(url, options = {}) {
        const res = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: { ...headers(!(options.body instanceof FormData)), ...(options.headers || {}) },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || data.error || 'Request gagal');
        }
        return data;
    }

    function confirmAction(message, onYes) {
        const cModal = el('bt-modal-confirm');
        if (!cModal) return;
        el('bt-modal-confirm-message').textContent = message;
        cModal.classList.remove('hidden');

        const yesBtn = el('bt-modal-confirm-yes');
        const noBtn = el('bt-modal-confirm-no');
        const backdrop = el('bt-modal-confirm-backdrop');

        const cleanup = () => {
            cModal.classList.add('hidden');
            yesBtn.removeEventListener('click', handleYes);
            noBtn.removeEventListener('click', handleNo);
            backdrop.removeEventListener('click', handleNo);
        };

        function handleYes() {
            cleanup();
            onYes();
        }
        function handleNo() {
            cleanup();
        }

        yesBtn.addEventListener('click', handleYes);
        noBtn.addEventListener('click', handleNo);
        backdrop.addEventListener('click', handleNo);
    }

    function renderTable() {
        const body = el('breaktime-table-body');
        if (!body) return;

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 font-medium">Belum ada parameter. Tambah breaktime atau jalankan seeder.</td></tr>';
            return;
        }

        body.innerHTML = rows.map((r) => {
            const activeBadge = r.is_active
                ? '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 border border-emerald-200">Aktif</span>'
                : '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-zinc-100 text-zinc-500 border border-zinc-200">Nonaktif</span>';

            const typeLower = (r.type || '').toLowerCase();
            const typeClass = typeLower === 'cinkorak'
                ? 'bg-violet-100 text-violet-700 border-violet-200'
                : 'bg-amber-100 text-amber-700 border-amber-200';
            const typeText = typeLower === 'cinkorak' ? 'CINGKORAK' : 'BREAK';

            return `<tr class="hover:bg-[#FFF8F8] transition" data-id="${r.id}">
                <td class="px-3 py-2.5 font-bold text-gray-900">${escapeHtml(r.label)}</td>
                <td class="px-3 py-2.5 capitalize text-gray-700 font-semibold">${escapeHtml(r.hari)}</td>
                <td class="px-3 py-2.5 text-gray-600 font-medium">${escapeHtml(r.shift || 'Semua')}</td>
                <td class="px-3 py-2.5 text-center font-mono text-xs font-semibold text-slate-800">${r.waktu_mulai}</td>
                <td class="px-3 py-2.5 text-center font-mono text-xs font-semibold text-slate-800">${r.waktu_selesai}</td>
                <td class="px-3 py-2.5 text-center"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border ${typeClass}">${typeText}</span></td>
                <td class="px-3 py-2.5 text-center">${activeBadge}</td>
                <td class="px-3 py-2.5 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                        <button type="button" class="h-8 w-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 inline-flex items-center justify-center transition" data-action="edit" title="Edit Parameter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button type="button" class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 inline-flex items-center justify-center transition" data-action="toggle" title="Ubah Status Parameter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                        </button>
                        <button type="button" class="h-8 w-8 rounded-lg bg-red-50 hover:bg-red-100 text-[#991B1B] inline-flex items-center justify-center transition" data-action="delete" title="Hapus Parameter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        body.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', onRowAction);
        });
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function onRowAction(e) {
        const tr = e.target.closest('tr');
        const id = parseInt(tr?.dataset.id || '0', 10);
        const row = rows.find((r) => r.id === id);
        if (!row) return;

        const action = e.target.closest('[data-action]').dataset.action;
        if (action === 'edit') {
            openForm(row);
        } else if (action === 'toggle') {
            toggleRow(id).catch((err) => window.showToast({ type: 'error', title: 'Gagal', message: err.message }));
        } else if (action === 'delete') {
            deleteRow(id).catch((err) => window.showToast({ type: 'error', title: 'Gagal', message: err.message }));
        }
    }

    async function loadRows() {
        const data = await api(cfg().routes.index);
        rows = data.data || [];
        renderTable();
        await loadPreview();
    }

    async function loadPreview() {
        const date = previewDate || cfg().defaultDate;
        const shift = previewShift || cfg().defaultShift;
        const url = `${cfg().routes.preview}?date=${encodeURIComponent(date)}&shift=${encodeURIComponent(shift)}`;

        el('breaktime-preview-date').textContent = date;
        el('breaktime-preview-shift').textContent = shift;

        try {
            const data = await api(url, { method: 'GET', headers: headers(false) });
            const list = el('breaktime-preview-list');
            const windows = data.data || [];
            if (!windows.length) {
                list.innerHTML = '<span class="text-xs text-gray-400 font-semibold">Tidak ada break untuk filter aktif.</span>';
                return;
            }
            list.innerHTML = windows.map((w) =>
                `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-black bg-amber-100 text-amber-900 border border-amber-200 shadow-sm">${escapeHtml(w.label || w.type)} <span class="font-mono opacity-80 font-bold">${w.start}–${w.finish}</span></span>`
            ).join('');
        } catch (err) {
            el('breaktime-preview-list').innerHTML = `<span class="text-xs text-red-600 font-bold">${escapeHtml(err.message)}</span>`;
        }
    }

    function openForm(row = null) {
        const panel = el('breaktime-form-panel');
        const form = el('breaktime-form');
        if (!panel || !form) return;

        panel.classList.remove('hidden');
        el('breaktime-form-title').textContent = row ? 'Edit Breaktime' : 'Tambah Breaktime';
        el('breaktime-form-id').value = row?.id || '';
        form.label.value = row?.label || '';
        form.hari.value = row?.hari || 'semua';
        form.shift.value = row?.shift || '';
        form.waktu_mulai.value = row?.waktu_mulai || '12:00';
        form.waktu_selesai.value = row?.waktu_selesai || '12:45';
        form.type.value = row?.type === 'cinkorak' ? 'cinkorak' : 'istirahat';
        form.is_active.checked = row ? !!row.is_active : true;
        triggerModalSimulation();
    }

    function closeForm() {
        el('breaktime-form-panel')?.classList.add('hidden');
        el('breaktime-form')?.reset();
        if (el('breaktime-form-id')) el('breaktime-form-id').value = '';
    }

    function formPayload() {
        const form = el('breaktime-form');
        return {
            label: form.label.value.trim(),
            hari: form.hari.value,
            shift: form.shift.value.trim() || null,
            waktu_mulai: form.waktu_mulai.value,
            waktu_selesai: form.waktu_selesai.value,
            type: form.type.value,
            is_active: form.is_active.checked,
        };
    }

    async function saveFormRow() {
        const id = (el('breaktime-form-id')?.value || '').trim();
        const payload = formPayload();

        // PHASE 7: Safety Validation Checks
        if (payload.waktu_selesai <= payload.waktu_mulai) {
            window.showToast({
                type: 'error',
                title: 'Validasi Gagal',
                message: 'Waktu selesai harus setelah waktu mulai!'
            });
            return;
        }

        // Duplicate checks
        const dupName = rows.find(r => r.id != id && r.label.toLowerCase() === payload.label.toLowerCase() && r.hari === payload.hari && (r.shift === payload.shift || !r.shift && !payload.shift));
        if (dupName) {
            window.showToast({
                type: 'error',
                title: 'Validasi Gagal',
                message: `Nama parameter "${payload.label}" sudah digunakan pada hari & shift yang sama.`
            });
            return;
        }

        // Overlap checks
        const overlap = rows.find(r => {
            if (r.id == id) return false;
            if (r.hari !== payload.hari && r.hari !== 'semua' && payload.hari !== 'semua') return false;
            if (r.shift && payload.shift && r.shift.toLowerCase() !== payload.shift.toLowerCase()) return false;

            return (payload.waktu_mulai < r.waktu_selesai && payload.waktu_selesai > r.waktu_mulai);
        });
        if (overlap) {
            window.showToast({
                type: 'error',
                title: 'Tumpang Tindih',
                message: `Waktu berbenturan dengan break "${overlap.label}" (${overlap.waktu_mulai} - ${overlap.waktu_selesai})`
            });
            return;
        }

        // PHASE 5: Loading State
        const saveBtn = el('breaktime-form-save');
        const spinner = el('breaktime-save-spinner');
        const btnText = el('breaktime-save-text');

        saveBtn.disabled = true;
        saveBtn.classList.add('opacity-70', 'cursor-not-allowed');
        spinner.classList.remove('hidden');
        btnText.textContent = 'Menyimpan...';

        try {
            if (id) {
                await api(`${cfg().routes.update}/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(payload),
                });
                window.showToast({ type: 'success', title: 'Parameter Diperbarui', message: 'Timeline baru telah dikalkulasi.' });
            } else {
                await api(cfg().routes.store, {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                window.showToast({ type: 'success', title: 'Parameter Ditambahkan', message: 'Timeline baru telah dikalkulasi.' });
            }

            closeForm();
            await loadRows();
        } catch (err) {
            window.showToast({ type: 'error', title: 'Gagal Menyimpan', message: err.message });
        } finally {
            saveBtn.disabled = false;
            saveBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            spinner.classList.add('hidden');
            btnText.textContent = 'Simpan Baris';
        }
    }

    async function toggleRow(id) {
        confirmAction('Ubah status aktif parameter breaktime ini?', async () => {
            try {
                await api(`${cfg().routes.toggle}/${id}/toggle`, { method: 'PATCH', body: '{}' });
                window.showToast({ type: 'success', title: 'Status Diubah', message: 'Parameter status berhasil diperbarui.' });
                await loadRows();
            } catch (err) {
                window.showToast({ type: 'error', title: 'Gagal', message: err.message });
            }
        });
    }

    async function deleteRow(id) {
        confirmAction('Hapus parameter breaktime ini? Tindakan ini akan me-regenerate total seluruh timeline.', async () => {
            try {
                await api(`${cfg().routes.destroy}/${id}`, { method: 'DELETE' });
                window.showToast({ type: 'success', title: 'Parameter Dihapus', message: 'Parameter breaktime telah dihapus dari sistem.' });
                await loadRows();
            } catch (err) {
                window.showToast({ type: 'error', title: 'Gagal', message: err.message });
            }
        });
    }

    async function regenerateAll() {
        try {
            const data = await api(cfg().routes.regenerate, { method: 'POST', body: '{}' });
            window.showToast({ type: 'success', title: 'Kalkulasi Ulang Selesai', message: data.message || 'Timeline diregenerate.' });
        } catch (err) {
            window.showToast({ type: 'error', title: 'Kalkulasi Gagal', message: err.message });
        }
    }

    function openModal(trigger) {
        const modal = el('breaktime-modal');
        if (!modal) return;

        if (trigger?.dataset?.breaktimeDate) {
            previewDate = trigger.dataset.breaktimeDate;
        }
        if (trigger?.dataset?.breaktimeShift) {
            previewShift = trigger.dataset.breaktimeShift;
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        loadRows().catch((err) => window.showToast({ type: 'error', title: 'Gagal Memuat', message: err.message }));
    }

    function closeModal() {
        el('breaktime-modal')?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        closeForm();
    }

    function bindEvents() {
        document.querySelectorAll('[data-breaktime-close]').forEach((node) => {
            node.addEventListener('click', closeModal);
        });

        el('breaktime-btn-add')?.addEventListener('click', () => openForm());
        el('breaktime-form-cancel')?.addEventListener('click', closeForm);
        el('breaktime-form-save')?.addEventListener('click', () => {
            saveFormRow().catch((err) => window.showToast({ type: 'error', title: 'Gagal', message: err.message }));
        });
        el('breaktime-btn-regenerate')?.addEventListener('click', () => {
            regenerateAll().catch((err) => window.showToast({ type: 'error', title: 'Gagal', message: err.message }));
        });
        el('breaktime-btn-apply')?.addEventListener('click', () => {
            regenerateAll()
                .then(() => {
                    window.showToast({ type: 'success', title: 'Menyinkronkan...', message: 'Memuat ulang antarmuka dashboard.' });
                    setTimeout(() => window.location.reload(), 800);
                })
                .catch((err) => window.showToast({ type: 'error', title: 'Gagal', message: err.message }));
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !el('breaktime-modal')?.classList.contains('hidden')) {
                closeModal();
            }
        });

        const form = el('breaktime-form');
        if (form) {
            form.label.addEventListener('input', debounceModalSimulate);
            form.hari.addEventListener('change', debounceModalSimulate);
            form.shift.addEventListener('input', debounceModalSimulate);
            form.waktu_mulai.addEventListener('input', debounceModalSimulate);
            form.waktu_selesai.addEventListener('input', debounceModalSimulate);
            form.type.addEventListener('change', debounceModalSimulate);
            form.is_active.addEventListener('change', debounceModalSimulate);
        }
    }

    let modalSimulateTimeout = null;
    async function triggerModalSimulation() {
        const statusEl = el('breaktime-live-impact-status');
        const contentEl = el('breaktime-live-impact-content');
        const form = el('breaktime-form');
        if (!statusEl || !contentEl || !form) return;

        const payload = {
            label: form.label.value.trim() || 'PROPOSED',
            hari: form.hari.value,
            shift: form.shift.value.trim() || previewShift || 'Shift Pagi',
            waktu_mulai: form.waktu_mulai.value,
            waktu_selesai: form.waktu_selesai.value,
            type: form.type.value,
            is_active: form.is_active.checked,
            date: previewDate || cfg().defaultDate || new Date().toISOString().split('T')[0],
        };

        if (!payload.waktu_mulai || !payload.waktu_selesai) {
            statusEl.textContent = 'UP TO DATE';
            statusEl.className = 'text-[8px] bg-amber-200 text-amber-950 px-2 py-0.5 rounded-full font-black';
            contentEl.innerHTML = 'Perubahan waktu akan memaksa timeline regenerasi pada shift terpilih.';
            return;
        }

        statusEl.textContent = 'CALCULATING...';
        statusEl.className = 'text-[8px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-black animate-pulse';

        try {
            const url = `${cfg().routes.index}/simulate`;
            const res = await fetch(url, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.affected && data.affected.length > 0) {
                statusEl.textContent = 'AFFECTED';
                statusEl.className = 'text-[8px] bg-red-100 text-[#9F1D1D] px-2 py-0.5 rounded-full font-black animate-pulse';

                let html = '<div class="text-[9px] font-bold text-red-950 mb-1">Downstream jobs shifted:</div>';
                html += '<ul class="list-disc pl-4 space-y-0.5 text-gray-700 font-semibold">';
                data.affected.forEach(job => {
                    html += `<li><strong>${job.job_no}</strong>: <span class="line-through text-gray-400">${job.old_start}–${job.old_finish}</span> → <span class="text-[#9F1D1D]">${job.new_start}–${job.new_finish}</span></li>`;
                });
                html += '</ul>';
                contentEl.innerHTML = html;
            } else {
                statusEl.textContent = 'NO IMPACT';
                statusEl.className = 'text-[8px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-black';
                contentEl.innerHTML = 'Timeline tidak terpengaruh atau parameter sesuai batas aman.';
            }
        } catch (e) {
            statusEl.textContent = 'ERROR';
            statusEl.className = 'text-[8px] bg-red-200 text-red-900 px-2 py-0.5 rounded-full font-black';
            contentEl.textContent = 'Gagal memuat preview dampak: ' + e.message;
        }
    }

    function debounceModalSimulate() {
        clearTimeout(modalSimulateTimeout);
        modalSimulateTimeout = setTimeout(triggerModalSimulation, 300);
    }

    window.BreaktimeModal = { open: openModal, close: closeModal };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindEvents);
    } else {
        bindEvents();
    }
})();
