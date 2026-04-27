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

    function closeSidebar(){
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);


    document.addEventListener('keydown', function (e) {
        if(e.key === 'Escape'){
            closeSidebar();
        }
    });

});

// animation and logic navbar
const navbar = document.getElementById('mainNavbar');

window.addEventListener('scroll', () => {
    if (window.scrollY > 10) {
        navbar.classList.remove('bg-gray-900');
        navbar.classList.add('bg-gray-900/70', 'backdrop-blur-md');
    } else {
        navbar.classList.remove('bg-gray-900/70', 'backdrop-blur-md');
        navbar.classList.add('bg-gray-900');
    }
}); 
 
const btn = document.getElementById('userMenuBtn');
const dropdown = document.getElementById('userDropdown');

btn.addEventListener('click', (e) => {
    e.stopPropagation();

    dropdown.classList.toggle('opacity-0');
    dropdown.classList.toggle('scale-95');
    dropdown.classList.toggle('invisible');

    dropdown.classList.toggle('opacity-100');
    dropdown.classList.toggle('scale-100');
    dropdown.classList.toggle('visible');
});

window.addEventListener('click', function(e){
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('opacity-0','scale-95','invisible');
        dropdown.classList.remove('opacity-100','scale-100','visible');
    }
});

// modal user admin 
window.openModal = function () {
    const modal = document.getElementById('userModal');
    const backdrop = document.getElementById('modalBackdrop');
    const box = document.getElementById('modalBox');

    if (!modal) return;

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
window.openEditModal = function (id, name, nip, role) {
    const modal = document.getElementById('editModal');

    // isi data ke form
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_nip').value = nip;
    document.getElementById('edit_role').value = role;

    // set action form
    const form = document.getElementById('editForm');
    form.action = `/admin/users/${id}`;

    // tampilkan modal
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
function updateClock(){
    const now = new Date();
    document.getElementById('liveClock').innerText =
        now.toLocaleTimeString();
}
setInterval(updateClock,1000);
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