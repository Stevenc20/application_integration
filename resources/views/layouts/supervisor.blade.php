<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Supervisor Dashboard')</title>
    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom scrollbar for better look */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .bg-primary-red { background-color: #991b1b; } /* Tailwind red-800 */
        .text-primary-red { color: #991b1b; }
        .border-primary-red { border-color: #991b1b; }
        .hover-bg-primary-red:hover { background-color: #7f1d1d; } /* Tailwind red-900 */
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="bg-primary-red text-white w-64 flex-shrink-0 hidden md:flex flex-col h-full shadow-lg transition-transform duration-300 z-20" id="sidebar">
        <div class="p-4 flex items-center justify-between border-b border-red-700">
            <h1 class="text-xl font-bold tracking-wider">SUPERVISOR</h1>
            <button id="closeSidebar" class="md:hidden text-white hover:text-gray-300">
                <i class="bx bx-x text-2xl"></i>
            </button>
        </div>
        
        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            <a href="#" class="flex items-center px-4 py-3 hover-bg-primary-red transition-colors {{ request()->routeIs('supervisor.dashboard') ? 'bg-red-900 border-l-4 border-white' : '' }}">
                <i class="bx bxs-dashboard text-xl mr-3"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <div class="px-4 mt-6 mb-2 text-xs font-semibold text-red-300 uppercase tracking-wider">
                Operasional
            </div>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-briefcase text-lg mr-3"></i>
                <span>Data Job</span>
            </a>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-time text-lg mr-3"></i>
                <span>Idle Time</span>
            </a>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-coffee text-lg mr-3"></i>
                <span>Breaktime</span>
            </a>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-wrench text-lg mr-3"></i>
                <span>Handwork</span>
            </a>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-check-shield text-lg mr-3"></i>
                <span>Quality Check</span>
            </a>

            <div class="px-4 mt-6 mb-2 text-xs font-semibold text-red-300 uppercase tracking-wider">
                Analitik & Laporan
            </div>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-line-chart text-lg mr-3"></i>
                <span>Grafik Output</span>
            </a>
            <a href="#" class="flex items-center px-4 py-2 hover-bg-primary-red transition-colors">
                <i class="bx bx-bar-chart-alt-2 text-lg mr-3"></i>
                <span>Grafik Downtime</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-red-700">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-white text-primary-red flex items-center justify-center font-bold">
                    S
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">Supervisor User</p>
                    <a href="#" class="text-xs text-red-300 hover:text-white transition">Logout</a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Header & Main Content -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Topbar -->
        <header class="bg-white shadow-sm z-10">
            <div class="flex items-center justify-between px-4 py-3 md:px-6">
                <div class="flex items-center">
                    <button id="openSidebar" class="mr-4 text-gray-500 hover:text-primary-red focus:outline-none md:hidden">
                        <i class="bx bx-menu text-2xl"></i>
                    </button>
                    <h2 class="text-xl font-semibold text-gray-800">@yield('header_title', 'Supervisor')</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500 hidden sm:block" id="liveClockTopbar"></span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6">
            @yield('content')
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const openSidebarBtn = document.getElementById('openSidebar');
            const closeSidebarBtn = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function openSidebar() {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('absolute', 'inset-y-0', 'left-0');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('absolute', 'inset-y-0', 'left-0');
                overlay.classList.add('hidden');
            }

            if(openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
            if(closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
            if(overlay) overlay.addEventListener('click', closeSidebar);

            // Live clock
            function updateClock() {
                const clockEl = document.getElementById('liveClockTopbar');
                if(clockEl) {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
                }
            }
            setInterval(updateClock, 1000);
            updateClock();
        });
    </script>
    @yield('scripts')
</body>
</html>
