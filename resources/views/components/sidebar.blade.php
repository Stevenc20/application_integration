@php
    $user = auth()->user();
    $role = $user->role ?? 'member'; // Default ke member jika role kosong

    // Normalize role for sidebar view loading
    $normalizedRole = strtolower($role);
    if (str_starts_with($normalizedRole, 'leader')) {
        $normalizedRole = 'leader';
    } elseif ($normalizedRole === 'shearing' || $normalizedRole === 'handwork') {
        $normalizedRole = 'operator';
    }
    $hambatanRoles = ['dies_shop', 'plant_service', 'produksi'];
    if (in_array($normalizedRole, $hambatanRoles)) {
        $normalizedRole = 'hambatan';
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
    } elseif ($normalizedRole == "irm") {
        $dashboardRoute = 'irm.dashboard';
    } elseif ($normalizedRole == "logistik") {
        $dashboardRoute = 'logistik.dashboard';
    } elseif ($normalizedRole == "manager") {
        $dashboardRoute = 'manager.dashboard';
    } elseif ($normalizedRole == "kadiv") {
        $dashboardRoute = 'kadiv.dashboard';
    } elseif ($normalizedRole == "direktur") {
        $dashboardRoute = 'direktur.dashboard';
    } elseif ($normalizedRole == "presdir") {
        $dashboardRoute = 'presdir.dashboard';
    }
@endphp

<div>
    <style>
        #sidebarMinimize { display: inline-flex !important; }
        @media (max-width: 767px) { #sidebarMinimize { display: none !important; } }
        #layout-menu { transition: width 0.3s ease, padding 0.3s ease, transform 0.3s ease !important; }

        body.sidebar-minimized #layout-menu {
            width: 5rem !important;
            padding: 0.5rem !important;
        }
        body.sidebar-minimized #layout-menu .app-brand {
            flex-direction: column-reverse !important;
            align-items: center !important;
            gap: 0.15rem !important;
        }
        body.sidebar-minimized #layout-menu .app-brand > .flex.items-center.gap-1 {
            align-self: flex-end !important;
        }
        body.sidebar-minimized #layout-menu .app-brand-link {
            justify-content: center !important;
            padding-right: 0 !important;
            width: 100% !important;
        }
        body.sidebar-minimized #layout-menu .app-brand-link span.flex {
            padding-right: 0 !important;
            width: 100% !important;
            justify-content: center !important;
        }
        body.sidebar-minimized #layout-menu .app-brand-link span.flex img {
            width: 3rem !important;
        }
        body.sidebar-minimized #layout-menu .app-brand-link span:not(.flex),
        body.sidebar-minimized #layout-menu nav span,
        body.sidebar-minimized #layout-menu nav .px-4.mb-2 {
            display: none !important;
        }
        body.sidebar-minimized #layout-menu nav a {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            gap: 0 !important;
        }
        body.sidebar-minimized #layout-menu nav a > div {
            gap: 0 !important;
            justify-content: center !important;
        }
        body.sidebar-minimized #layout-menu nav a svg.arrow,
        body.sidebar-minimized #layout-menu nav a .arrow {
            display: none !important;
        }
        body.sidebar-minimized #layout-menu nav .menu-sub {
            display: none !important;
        }
        body.sidebar-minimized .sidebar-minimize-icon {
            transform: rotate(180deg);
        }

        /* Content wrapper margin — works for ALL layouts */
        @media (min-width: 768px) {
            body.sidebar-minimized #mainWrapper,
            body.sidebar-minimized > div[class*="flex-1"] {
                margin-left: 5rem !important;
            }
        }
    </style>

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
            
            <div class="flex items-center gap-1">
                <!-- Tombol Minimize (Desktop Only) -->
                <button id="sidebarMinimize" class="text-gray-400 hover:text-primary-red focus:outline-none transition-colors p-1" title="Minimize sidebar" type="button">
                    <span class="sidebar-minimize-icon font-bold text-lg" style="display:inline-block">&#x276E;</span>
                </button>
                <!-- Tombol Close (Khusus Mobile) -->
                <button id="sidebarClose" class="md:hidden text-gray-400 hover:text-red-600 focus:outline-none transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
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
            if (window.innerWidth < 768) {
                document.body.classList.remove('sidebar-minimized');
                localStorage.setItem('sidebarMin', '');
            }
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

        // Sidebar Minimize
        const minimizeBtn = document.getElementById('sidebarMinimize');
        if (minimizeBtn) {
            function applyMin(min) {
                document.body.classList.toggle('sidebar-minimized', min);
            }
            if (localStorage.getItem('sidebarMin') === '1') applyMin(true);
            minimizeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const min = !document.body.classList.contains('sidebar-minimized');
                applyMin(min);
                localStorage.setItem('sidebarMin', min ? '1' : '');
            });
        }
    });
</script>
