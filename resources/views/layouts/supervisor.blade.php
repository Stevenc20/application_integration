<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Supervisor Dashboard')</title>
    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Boxicons & Font Awesome -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom scrollbar for better look */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        /* Elegant dark red theme - comfortable for all eyes */
        .bg-primary-red { background-color: #C0392B; } /* Tailwind rose-900 */
        .text-primary-red { color: #C0392B; }
        .border-primary-red { border-color: #C0392B; }
        .hover-bg-primary-red:hover { background-color: #4c0519; } /* Tailwind rose-950 */
        .bg-active-red { background-color: #C0392B; } /* Tailwind rose-700 */
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex h-[100dvh] overflow-hidden">

    <!-- Include Modular Sidebar -->
    @include('components.sidebar')

    <!-- Mobile Header & Main Content -->
    <!-- md:ml-64 karena sidebar memiliki w-64 dan fixed -->
    <div class="flex-1 flex flex-col h-[100dvh] md:ml-64 overflow-hidden">
        <!-- Topbar -->
        @include('components.navbar')

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6 custom-scrollbar flex flex-col">
            <div class="flex-1 pb-12">
                @yield('content')
            </div>
            
            @include('components.footer')
        </main>
    </div>

    @if(auth()->check() && in_array(strtolower(auth()->user()->role), ['supervisor', 'leader', 'foreman']))
    <!-- Global Active Job Timer Popout -->
    <div id="global-timer-popout" class="fixed bottom-6 right-6 z-[100] hidden bg-gray-900 text-white rounded-2xl shadow-2xl p-4 flex items-center gap-4 cursor-pointer hover:scale-105 transition-all border border-gray-700">
        <div class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </div>
        <div>
            <div class="text-[10px] text-gray-400 font-medium uppercase tracking-widest">Lagi Berjalan</div>
            <div id="global-timer-job" class="font-bold text-sm">Job</div>
        </div>
        <div id="global-timer-time" class="font-mono text-lg font-black text-green-400 tracking-widest ml-2 bg-gray-800 px-3 py-1 rounded-lg">
            00:00:00
        </div>
    </div>
    @endif

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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

            // Global Timer Poll
            window.fetchGlobalTimer = function() {
                const popout = document.getElementById('global-timer-popout');
                if(!popout) return; // Safety check

                fetch('/operational/active-job')
                .then(res => res.json())
                .then(data => {
                    if(data.running) {
                        popout.classList.remove('hidden');
                        document.getElementById('global-timer-job').innerText = data.job_number;
                        popout.onclick = () => window.location.href = data.url;
                        
                        if(window.globalTimerInterval) {
                            clearInterval(window.globalTimerInterval);
                            window.globalTimerInterval = null;
                        }
                        
                        let baseSeconds = parseInt(data.total_seconds || 0);
                        let startTime = new Date(data.start_time);
                        
                        window.globalTimerInterval = setInterval(() => {
                            let diffInSeconds = Math.floor((new Date() - startTime) / 1000);
                            let current = baseSeconds + Math.max(0, diffInSeconds);
                            
                            let h = String(Math.floor(current/3600)).padStart(2,'0');
                            let m = String(Math.floor((current%3600)/60)).padStart(2,'0');
                            let s = String(current%60).padStart(2,'0');
                            document.getElementById('global-timer-time').innerText = `${h}:${m}:${s}`;
                        }, 1000);
                        
                    } else {
                        popout.classList.add('hidden');
                        if(window.globalTimerInterval) {
                            clearInterval(window.globalTimerInterval);
                            window.globalTimerInterval = null;
                        }
                    }
                }).catch(e => console.log('Global timer fetch error', e));
            }
            
            window.fetchGlobalTimer();
            setInterval(window.fetchGlobalTimer, 30000); // sync every 30s
        });
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>

