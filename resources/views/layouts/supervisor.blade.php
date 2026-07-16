<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supervisor Dashboard')</title>
    
    <!-- DNS Prefetch + Preconnect CDNs FIRST so browser connects early -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://code.jquery.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <!-- Legacy JS (100% compatible all browsers - no ES modules) -->
    @php
        $buildCssUrl = '';
        $polyfillsLegacy = '';
        $appLegacy = '';
        
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/supervisor.css']['file'])) {
                $buildCssUrl = asset('build/' . $manifest['resources/css/supervisor.css']['file']);
            }
        }
        
        $polyfillsFiles = glob(public_path('build/assets/polyfills-legacy-*.js'));
        if (!empty($polyfillsFiles)) {
            $polyfillsLegacy = asset('build/assets/' . basename($polyfillsFiles[0]));
        }
        $appLegacyFiles = glob(public_path('build/assets/app-legacy-*.js'));
        if (!empty($appLegacyFiles)) {
            $appLegacy = asset('build/assets/' . basename($appLegacyFiles[0]));
        }
    @endphp

    <style>
        *,*::before,*::after{box-sizing:border-box}
        html,body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
        body{background-color:#f9fafb;color:#374151;display:flex;height:100dvh;overflow:hidden}
        #layout-menu{position:fixed;top:0;left:0;z-index:50}
        #mainWrapper{display:flex;flex:1;flex-direction:column;height:100dvh;overflow:hidden}
        @media(min-width:768px){#mainWrapper{margin-left:16rem}}
        #mainWrapper main{flex:1;overflow:auto;background-color:#f9fafb;padding:1rem;min-height:0}
        @media(min-width:768px){#mainWrapper main{padding:1.5rem}}
        .hidden{display:none}
    </style>
    @if($buildCssUrl)
        <link rel="stylesheet" href="{{ $buildCssUrl }}">
    @endif
    @if($polyfillsLegacy)
        <script src="{{ $polyfillsLegacy }}"></script>
    @endif
    @if($appLegacy)
        <script src="{{ $appLegacy }}"></script>
    @endif
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"></noscript>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex h-[100dvh] overflow-hidden">

    <!-- Error Suppressor (deferred from head to avoid render-blocking) -->
    <script>(function(){var a=console.error;console.error=function(...b){if(b[0]){var c=typeof b[0]==='string'?b[0]:(b[0].message||b[0].toString());if(c.includes('message channel closed')||c.includes('asynchronous response')||c.includes('extension'))return}a.apply(console,b)};window.addEventListener('unhandledrejection',function(b){if(b.reason){var c=b.reason.message||b.reason.toString()||'';if(c.includes('message channel closed')||c.includes('asynchronous response')||c.includes('listener indicated')){b.preventDefault();b.stopPropagation()}}},!0)})();</script>

    <!-- Non-critical inline styles (scrollbar, icon widths, utility colors) -->
    <style>
        /* Custom scrollbar for better look */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        /* Elegant dark red theme - comfortable for all eyes */
        .bg-primary-red { background-color: #C0392B; }
        .text-primary-red { color: #C0392B; }
        .border-primary-red { border-color: #C0392B; }
        .hover-bg-primary-red:hover { background-color: #4c0519; }
        .bg-active-red { background-color: #C0392B; }
        /* Prevent CLS from async icon fonts */
        [class^="bx-"],[class*=" bx-"]{width:1em;display:inline-block;text-align:center}
        .fa,.fas,.far,.fal,.fab{width:1.25em;display:inline-block;text-align:center}
    </style>

    <!-- Include Modular Sidebar -->
    @include('components.sidebar')

    <!-- Mobile Header & Main Content -->
    <!-- md:ml-64 karena sidebar memiliki w-64 dan fixed -->
    <div id="mainWrapper" class="flex-1 flex flex-col h-[100dvh] md:ml-64 overflow-hidden">
        <!-- Topbar -->
        @include('components.navbar')

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6 custom-scrollbar flex flex-col min-h-0">
            <div class="flex-1 pb-20">
                @yield('content')
            </div>
            
            @include('components.footer')
        </main>
    </div>

    @if(auth()->check() && (strtolower(auth()->user()->role) === 'foreman' || str_starts_with(strtolower(auth()->user()->role), 'leader')))
    @php
        $popoutLine = null;
        $role = strtolower(auth()->user()->role);
        if (str_starts_with($role, 'leader')) {
            $suffix = trim(substr($role, 6));
            if ($suffix !== '') {
                $suffix = strtoupper($suffix);
                if (in_array($suffix, ['A', 'B', 'C', 'D'])) {
                    $popoutLine = 'Line ' . $suffix;
                }
            }
            if (!$popoutLine) {
                $assignment = \App\Models\LineAssignment::where('leader_user_id', auth()->id())->first();
                if ($assignment) {
                    $popoutLine = $assignment->line_name;
                }
            }
        } elseif ($role === 'shearing') {
            $popoutLine = 'Shearing';
        } elseif ($role === 'handwork') {
            $popoutLine = 'Handwork';
        }
    @endphp
    <!-- Global Active Job Timer Popout -->
    <div id="global-timer-popout" data-line="{{ $popoutLine }}" class="fixed bottom-6 right-6 z-[100] hidden bg-gray-900 text-white rounded-2xl shadow-2xl p-4 flex items-center gap-4 cursor-pointer hover:scale-105 transition-all border border-gray-700">
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

    <!-- Scripts: defer so they don't block first paint / LCP -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
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

                let url = "{{ url('/operational/active-job') }}";
                const line = popout.getAttribute('data-line');
                if (line) url += `?line=${encodeURIComponent(line)}`;

                fetch(url)
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
    @stack('modals')
    @yield('scripts')
    @stack('scripts')
</body>
</html>

