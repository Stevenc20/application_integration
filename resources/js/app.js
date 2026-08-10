import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('layout-menu');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);


    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

});

// animation and logic navbar
const navbar = document.getElementById('mainNavbar');

if (navbar) {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            navbar.classList.remove('bg-gray-900');
            navbar.classList.add('bg-gray-900/70', 'backdrop-blur-md');
        } else {
            navbar.classList.remove('bg-gray-900/70', 'backdrop-blur-md');
            navbar.classList.add('bg-gray-900');
        }
    });
}

const btn = document.getElementById('userMenuBtn');
const dropdown = document.getElementById('userDropdown');

if (btn && dropdown) {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();

        dropdown.classList.toggle('opacity-0');
        dropdown.classList.toggle('scale-95');
        dropdown.classList.toggle('invisible');

        dropdown.classList.toggle('opacity-100');
        dropdown.classList.toggle('scale-100');
        dropdown.classList.toggle('visible');
    });

    window.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('opacity-0', 'scale-95', 'invisible');
            dropdown.classList.remove('opacity-100', 'scale-100', 'visible');
        }
    });
}

// modal user admin 
window.openModal = function () {
    const modal = document.getElementById('userModal');
    const backdrop = document.getElementById('modalBackdrop');
    const box = document.getElementById('modalBox');

    if (!modal) return;

    // reset role cards
    const addRoleVal = document.getElementById('add_role_val');
    if (addRoleVal) addRoleVal.value = '';
    document.querySelectorAll('#userModal .role-card').forEach(c => {
        c.className = 'role-card flex items-center gap-3 p-3 rounded-2xl border-2 border-gray-100 hover:border-gray-300 hover:bg-gray-50 cursor-pointer transition-all duration-200 group';
    });

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        backdrop?.classList.remove('opacity-0');
        backdrop?.classList.add('opacity-100');

        box?.classList.remove('opacity-0', 'scale-95');
        box?.classList.add('opacity-100', 'scale-100');
    }, 10);
};

window.closeModal = function () {
    const modal = document.getElementById('userModal');
    const backdrop = document.getElementById('modalBackdrop');
    const box = document.getElementById('modalBox');

    if (!modal) return;

    backdrop?.classList.add('opacity-0');
    backdrop?.classList.remove('opacity-100');

    box?.classList.add('opacity-0', 'scale-95');
    box?.classList.remove('opacity-100', 'scale-100');

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
};

// modal edit
window.openEditModal = function (id, name, nrp, role) {
    const modal = document.getElementById('editModal');
    if (!modal) return;

    document.getElementById('edit_name').value = name;
    document.getElementById('edit_nrp').value = nrp;

    const norm = typeof window.normalizeRole === 'function' ? window.normalizeRole(role) : role;
    document.getElementById('edit_role_val').value = norm;

    const form = document.getElementById('editForm');
    form.action = `/admin/users/${id}`;

    // highlight role card
    if (typeof window.setEditRole === 'function') {
        window.setEditRole(norm);
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.closeEditModal = function () {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

// delete
window.openDeleteModal = function (id) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');

    form.action = `/admin/users/${id}`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.closeDeleteModal = function () {
    const modal = document.getElementById('deleteModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
};


// jam operator
function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        clockEl.innerText = `${h}:${m}:${s}`;
    }
}
setInterval(updateClock, 1000);
updateClock();


// =======================
// FILL JOB DATA
// =======================

window.fillJobData = function () {
    const select = document.getElementById('job_id');
    if (!select) return;

    const option = select.options[select.selectedIndex];

    document.getElementById('job_number').value =
        option.getAttribute('data-number') || '';

    document.getElementById('job_name').value =
        option.getAttribute('data-name') || '';

    document.getElementById('line').value =
        option.getAttribute('data-line') || '';

    document.getElementById('capacity').value =
        option.getAttribute('data-capacity') || '';
};

// =======================
// QA MODULE LAZY LOADING
// =======================
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import notificationsFactory from './qa/notifications';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.data('notifications', notificationsFactory);

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

    // fabric.js — ONLY on LI and Item Check form pages with signature canvas
    if (isLiFormPage || isItemCheckFormPage) {
        loadPromises.push(
            import('fabric').then(({ fabric }) => {
                window.fabric = fabric;
            }).catch(() => {})
        );
    }

    if (isLiFormPage || isItemCheckFormPage) {
        await Promise.all(loadPromises);
        loadPromises.length = 0;
    }

    if (isLiPage || isLiFormPage) {
        loadPromises.push(import('./qa/liForm').catch(() => {}));
    }

    if (isItemCheckPage || isItemCheckFormPage) {
        loadPromises.push(import('./qa/itemCheckForm').catch(() => {}));
    }

    if (isAdminPage) {
        loadPromises.push(import('./qa/userManagement').catch(() => {}));
    }

    if (isQcPage) {
        loadPromises.push(import('./qa/qcWorklist').catch(() => {}));
    }

    if (isApprovalPage) {
        loadPromises.push(import('./qa/approval').catch(() => {}));
    }

    if (isQprPage) {
        loadPromises.push(
            Promise.all([import('./qa/qprForm'), import('./qa/qprList')]).catch(e => {
                console.error('Gagal memuat modul QPR.', e);
            })
        );
    }

    await Promise.all(loadPromises);
    Alpine.start();
})();