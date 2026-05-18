/**
 * Production Planning Engine
 * Handles inline updates, modal interactions, and excel imports.
 */

window.PlanningEngine = {
    init: function (config) {
        this.config = config;
        this.setupEventListeners();
    },

    setupEventListeners: function () {
        const planDateFilter = document.getElementById('planDateFilter');
        if (planDateFilter) {
            planDateFilter.addEventListener('change', (e) => {
                window.location.href = `${this.config.indexUrl}?date=${e.target.value}&press=${this.config.currentPress}`;
            });
        }

        const importForm = document.getElementById('importForm');
        // if (importForm) {
        //     importForm.addEventListener('submit', (e) => this.handleImport(e));
        // }
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

    updateInline: function (id, field, value) {
        fetch('/ppc/planning/production-plan/update-inline', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken
            },
            body: JSON.stringify({ id, field, value })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const calcFields = ['plan', 'qty_plt', 'ct_detik', 'status', 'total_mesin', 'reg_active'];
                    if (calcFields.includes(field)) {
                        window.location.reload();
                    }
                } else {
                    alert('Gagal mengupdate data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan data.');
            });
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
    }
};
