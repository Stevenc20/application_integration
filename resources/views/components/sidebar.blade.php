@php
    $user = auth()->user();
    $role = $user->role ?? 'member'; // Default ke member jika role kosong

    // Normalize role for sidebar view loading
    $normalizedRole = strtolower($role);
    if (str_starts_with($normalizedRole, 'leader') || $normalizedRole === 'shearing' || $normalizedRole === 'handwork') {
        $normalizedRole = 'operator';
    }

    // Tentukan file komponen sidebar mana yang akan di-load berdasarkan role
    $sidebarView = 'components.sidebar.' . $normalizedRole;
    
    // Tentukan route dashboard umum untuk logo click
    $dashboardRoute = 'login';
    if ($normalizedRole == "admin") {
        $dashboardRoute = 'admin.dashboard';
    } elseif ($normalizedRole == "supervisor") {
        $dashboardRoute = 'supervisor.dashboard';
    } elseif ($normalizedRole == "operator") {
        $dashboardRoute = 'operator.dashboard';
    } elseif ($normalizedRole == "foreman") {
        $dashboardRoute = 'foreman.dashboard';
    } elseif ($normalizedRole == "ppc") {
        $dashboardRoute = 'ppc.dashboard';
    } elseif ($normalizedRole == "quality") {
        $dashboardRoute = 'quality.dashboard';
    }
@endphp

<div>
    <!-- Overlay untuk versi Mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 hidden z-40 bg-black bg-opacity-50 md:hidden transition-opacity"></div>

    <!-- MAIN ASIDE (WRAPPER) - Menggunakan style scroll asli -->
    <aside id="layout-menu"
        class="fixed top-0 left-0 z-50 w-64 h-[100dvh] bg-white p-5 pb-2 shadow-lg
        overflow-y-scroll overflow-x-hidden custom-scrollbar
        transform -translate-x-full md:translate-x-0
        transition-transform duration-300">

        <!-- HEADER / LOGO -->
        <div class="app-brand demo flex items-center justify-between">
            <a href="{{ route($dashboardRoute) }}" class="flex items-center pr-1 app-brand-link">
                <span class="flex pr-3">
                    <img class="w-12" src="{{ asset('images/ippi_logo.png') }}" alt="Logo IPPI">
                </span>
                <span class="font-semibold text-lg text-gray-800">PT IPPI</span>
            </a>
            
            <!-- Tombol Close (Khusus Mobile) -->
            <button id="sidebarClose" class="md:hidden text-gray-400 hover:text-red-600 focus:outline-none transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- MENU CONTENT -->
        <nav class="pt-6">
            {{-- Dynamic Include berdasarkan Role --}}
            @if(View::exists($sidebarView))
                @include($sidebarView)
            @else
                @include('components.sidebar.member')
            @endif
        </nav>
        
    </aside>
</div>

<!-- Script Toggle Menu & Sub-menu -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Sub-Menu
        document.body.addEventListener('click', function(e) {
            const toggleElement = e.target.closest('.menu-toggle');
            
            if (toggleElement && document.getElementById('layout-menu').contains(toggleElement)) {
                if(toggleElement.tagName.toLowerCase() === 'a' && toggleElement.getAttribute('href') === 'javascript:void(0);') {
                    e.preventDefault();
                }

                const parent = toggleElement.closest('li') || toggleElement.closest('.menu-item');
                if (!parent) return;

                const submenu = parent.querySelector('.menu-sub');
                const arrow = toggleElement.querySelector('.arrow');

                if (submenu) {
                    submenu.classList.toggle('hidden');
                }
                if (arrow) {
                    arrow.classList.toggle('rotate-90');
                }
                
                parent.classList.toggle('active');
            }
        });

        // Toggle Sidebar Mobile
        const layoutMenu = document.getElementById('layout-menu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarCloseBtn = document.getElementById('sidebarClose');
        
        const sidebarOpenBtns = document.querySelectorAll('#openSidebar, .layout-menu-toggle');
        
        function openSidebar() {
            layoutMenu.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
            setTimeout(() => {
                sidebarOverlay.classList.remove('opacity-0');
                sidebarOverlay.classList.add('opacity-100');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }

        function closeSidebar() {
            layoutMenu.classList.add('-translate-x-full');
            sidebarOverlay.classList.remove('opacity-100');
            sidebarOverlay.classList.add('opacity-0');
            setTimeout(() => {
                sidebarOverlay.classList.add('hidden');
            }, 300);
            document.body.classList.remove('overflow-hidden');
        }

        sidebarOpenBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openSidebar();
            });
        });

        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', closeSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }
    });
</script>
