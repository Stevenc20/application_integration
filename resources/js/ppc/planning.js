/**
 * Production Planning Engine
 * Handles inline updates, modal interactions, excel imports, and drag-drop reorder.
 */

import Sortable from 'sortablejs';

window.PlanningEngine = {
    init: function (config) {
        this.config = config;
        this.setupEventListeners();
        this.initDragDrop();
        this.setupAddJobAutocomplete();
    },

    setupEventListeners: function () {
        const planDateFilter = document.getElementById('planDateFilter');
        if (planDateFilter) {
            planDateFilter.addEventListener('change', (e) => {
                window.location.href = `${this.config.indexUrl}?date=${e.target.value}&press=${this.config.currentPress}`;
            });
        }

        const importForm = document.getElementById('importForm');
    },

    initDragDrop: function () {
        const tbody = document.getElementById('planTableBody');
        if (!tbody) return;

        var _blockDrag = false;

        Sortable.create(tbody, {
            filter: '[data-no-drag]',
            handle: '.drag-handle, .drag-handle-block',
            forceFallback: true,
            animation: 200,
            easing: 'cubic-bezier(0.25, 0.1, 0.25, 1.0)',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onStart: function (evt) {
                document.body.style.cursor = 'grabbing';
                _blockDrag = evt.originalEvent.target.closest('.drag-handle-block') !== null;
            },
            onEnd: function () {
                document.body.style.cursor = '';
                _blockDrag = false;
            },
            onMove: function (evt) {
                const targetTr = evt.related;
                if (targetTr && targetTr.hasAttribute('data-no-drag')) {
                    return false;
                }
                return true;
            },
            onUpdate: function () {
                if (_blockDrag) {
                    const members = tbody.querySelectorAll('tr[data-recovery-member]');
                    const end = tbody.querySelector('tr.recovery-end');
                    let ref = tbody.querySelector('.drag-handle-block')?.closest('tr');
                    if (!ref) return;
                    members.forEach(function (m) {
                        if (m.parentNode === tbody) {
                            tbody.insertBefore(m, ref.nextSibling);
                            ref = m;
                        }
                    });
                    if (end && end.parentNode === tbody) {
                        tbody.insertBefore(end, ref.nextSibling);
                    }
                }

                // Update visual numbering
                let num = 1;
                tbody.querySelectorAll('tr:not([data-no-drag]) .row-number').forEach(function (el) {
                    el.textContent = num++;
                });

                // Collect ids in new visual order
                const ids = [];
                tbody.querySelectorAll('tr[data-id]').forEach(function (tr) {
                    var id = tr.dataset.id;
                    if (id) ids.push(parseInt(id));
                });

                PlanningEngine.saveReorder(ids);
            }
        });
    },

    saveReorder: async function (ids) {
        const reorderBtn = document.getElementById('reorderStatus');
        if (reorderBtn) {
            reorderBtn.textContent = 'MENYIMPAN...';
            reorderBtn.classList.remove('hidden');
        }

        try {
            const response = await fetch('/ppc/planning/production-plan/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                body: JSON.stringify({ ids })
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                console.error('Reorder failed:', data.message);
                if (reorderBtn) {
                    reorderBtn.textContent = 'GAGAL';
                    reorderBtn.classList.add('text-red-600');
                    setTimeout(() => { reorderBtn.classList.add('hidden'); }, 3000);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (reorderBtn) {
                reorderBtn.textContent = 'ERROR';
                reorderBtn.classList.add('text-red-600');
                setTimeout(() => { reorderBtn.classList.add('hidden'); }, 3000);
            }
        }
    },

    openImportModal: function () {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },

    closeImportModal: function () {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    updateInline: async function (id, field, value, options = {}) {
        try {
            const response = await fetch('/ppc/planning/production-plan/update-inline', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                body: JSON.stringify({
                    id,
                    field,
                    value,
                    manual_override: Boolean(options.manualOverride)
                })
            });
            const data = await response.json();
            if (data.success) {
                const calcFields = ['plan', 'qty_plt', 'ct_detik', 'status', 'total_mesin', 'reg_active', 'dct'];
                if (calcFields.includes(field)) {
                    window.location.reload();
                }
            } else {
                alert('Gagal mengupdate data: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan data.');
        }
    },

    recalculateMetrics: async function (id) {
        try {
            const response = await fetch(`/ppc/planning/production-plan/${id}/recalculate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                }
            });
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Gagal merecalculate data.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat merecalculate data.');
        }
    },

    handleImport: function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = 'IMPORTING...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': this.config.csrfToken
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Import gagal: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan fatal saat import.');
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            });
    },

    // ──────────────────────────────
    // ADD JOB MODAL
    // ──────────────────────────────
    openAddJobModal: function () {
        const modal = document.getElementById('addJobModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            // Clear any previous values
            document.getElementById('addJobNoInput').value = '';
            document.getElementById('addJobNoInput').focus();
            this.clearMasterPreview();
            const submitBtn = modal.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = false;
        }
    },

    closeAddJobModal: function () {
        const modal = document.getElementById('addJobModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    clearMasterPreview: function () {
        document.getElementById('addJobSuggestions').classList.add('hidden');
        document.getElementById('addJobSuggestions').innerHTML = '';
        document.getElementById('addJobMasterPreview').classList.add('hidden');
        document.getElementById('addJobMasterPreview').innerHTML = '';
        document.getElementById('addJobMasterVal').value = '';
        document.getElementById('addJobTypePltVal').value = '';
        document.getElementById('addJobQtyPltVal').value = '';
        document.getElementById('addJobCt').value = '';
        document.getElementById('addJobDct').value = '';
        document.getElementById('addJobRegActive').value = '';
    },

    addJobSearchTimeout: null,

    setupAddJobAutocomplete: function () {
        const input = document.getElementById('addJobNoInput');
        if (!input) return;

        input.addEventListener('input', () => {
            clearTimeout(this.addJobSearchTimeout);
            const q = input.value.trim();
            if (q.length < 2) {
                document.getElementById('addJobSuggestions').classList.add('hidden');
                this.clearMasterPreview();
                return;
            }
            this.addJobSearchTimeout = setTimeout(() => {
                this.searchMasterData(q);
            }, 300);
        });

        // Submit handler
        const form = document.getElementById('addJobForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerText = 'MENYIMPAN...';

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-CSRF-TOKEN': this.config.csrfToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Gagal menambah job: ' + (data.message || 'Unknown error'));
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'TAMBAH JOB';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menyimpan.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'TAMBAH JOB';
                    });
            });
        }

        // Close suggestions on click outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#addJobNoInput') && !e.target.closest('#addJobSuggestions')) {
                document.getElementById('addJobSuggestions').classList.add('hidden');
            }
        });
    },

    searchMasterData: function (q) {
        const url = '/ppc/master-stamping/search?q=' + encodeURIComponent(q);
        fetch(url)
            .then(response => response.json())
            .then(items => {
                const container = document.getElementById('addJobSuggestions');
                container.innerHTML = '';
                if (!items || items.length === 0) {
                    container.classList.add('hidden');
                    return;
                }
                items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-3 border-b border-slate-100 hover:bg-emerald-50 cursor-pointer text-sm font-semibold text-slate-700 transition-colors flex items-center justify-between';
                    div.innerHTML = '<span>' + this.escHtml(item.job_no) + '</span><span class="text-xs text-slate-400 font-normal">' + this.escHtml(item.job_master) + '</span>';
                    div.addEventListener('click', () => {
                        this.selectMasterItem(item);
                        container.classList.add('hidden');
                    });
                    container.appendChild(div);
                });
                container.classList.remove('hidden');
            })
            .catch(() => {
                document.getElementById('addJobSuggestions').classList.add('hidden');
            });
    },

    selectMasterItem: function (item) {
        document.getElementById('addJobNoInput').value = item.job_no || '';
        document.getElementById('addJobMasterVal').value = item.job_master || '';
        document.getElementById('addJobTypePltVal').value = item.type_pallet || '';
        document.getElementById('addJobQtyPltVal').value = item.qty_pallet || 0;
        document.getElementById('addJobCt').value = item.ct_detik || '';
        document.getElementById('addJobDct').value = item.dct || '';
        document.getElementById('addJobRegActive').value = item.reg_active || '';

        // Show master preview
        const preview = document.getElementById('addJobMasterPreview');
        preview.classList.remove('hidden');
        preview.innerHTML = '<div class="font-black text-emerald-900 text-xs uppercase tracking-wider">' + this.escHtml(item.job_master || '') + '</div>'
            + '<div class="flex gap-4 mt-1 text-[11px] text-emerald-700">'
            + '<span>Type: <strong>' + this.escHtml(item.type_pallet || '—') + '</strong></span>'
            + '<span>Qty/Plt: <strong>' + (item.qty_pallet || '0') + '</strong></span>'
            + '<span>CT: <strong>' + (item.ct_detik || '0') + '</strong></span>'
            + '</div>';
    },

    escHtml: function (str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
};
