import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import axios from 'axios';
import notificationsFactory from './notifications';

// Set global objects
Alpine.plugin(collapse);
window.Alpine = Alpine;
window.axios = axios;

// Configure axios
const baseUrl = document.querySelector('meta[name="app-url"]')?.getAttribute('content') || '';
window.axios.defaults.baseURL = baseUrl;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    window.axios.defaults.withCredentials = true;
}

// Register Global Components
Alpine.data('notifications', notificationsFactory);

// ─── PAGE-SPECIFIC LAZY LOADING ────────────────────────────────────────────
// Hanya muat modul yang dibutuhkan per halaman, bukan semua sekaligus.
// Menggunakan async/await agar Alpine.start() dipanggil SETELAH modul selesai di-load.
(async () => {
    const path = window.location.pathname;

    const isLiFormPage   = /\/li\/(create|[0-9]+\/edit)/.test(path);
    const isLiPage       = /\/li(\/|$)/.test(path);
    const isItemCheckFormPage = /\/item-check\/[0-9]+\/form/.test(path);
    const isItemCheckPage     = /\/item-check(\/|$)/.test(path);
    const isQprPage      = /\/qpr(\/|$)/.test(path);
    const isApprovalPage = /\/approval(\/|$)/.test(path);
    const isAdminPage    = /\/admin(\/|$)/.test(path);
    const isQcPage       = /\/qc(\/|$)/.test(path);

    const loadPromises = [];

    // fabric.js (~1MB!) — HANYA di halaman form LI yang pakai kanvas TTD
    if (isLiFormPage || isItemCheckFormPage) {
        loadPromises.push(
            import('fabric').then(({ fabric }) => {
                window.fabric = fabric;
            }).catch(() => {})
        );
    }

    // Tunggu fabric di-load dulu jika ada, karena liForm mungkin butuh window.fabric
    if (isLiFormPage || isItemCheckFormPage) {
        await Promise.all(loadPromises);
        loadPromises.length = 0; // reset
    }

    // liForm.js — HANYA di halaman LI (list & form)
    if (isLiPage || isLiFormPage) {
        loadPromises.push(import('./liForm').catch(() => {}));
    }

    // itemCheckForm.js - HANYA di halaman item check
    if (isItemCheckPage || isItemCheckFormPage) {
        loadPromises.push(import('./itemCheckForm').catch(() => {}));
    }

    // userManagement.js — HANYA di halaman admin
    if (isAdminPage) {
        loadPromises.push(import('./userManagement').catch(() => {}));
    }

    // qcWorklist.js — HANYA di halaman QC
    if (isQcPage) {
        loadPromises.push(import('./qcWorklist').catch(() => {}));
    }

    // approval.js — HANYA di halaman approval
    if (isApprovalPage) {
        loadPromises.push(import('./approval').catch(() => {}));
    }

    // QPR modules — HANYA di halaman /qpr
    if (isQprPage) {
        loadPromises.push(
            Promise.all([import('./qprForm'), import('./qprList')]).catch(e => {
                console.error('Gagal memuat modul QPR.', e);
            })
        );
    }

    // Tunggu semua modul selesai di-load
    await Promise.all(loadPromises);

    // START ALPINE ONCE SETELAH SEMUA MODUL SIAP
    Alpine.start();
})();
