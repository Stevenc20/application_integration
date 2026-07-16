<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PPC Dashboard')</title>

    <!-- DNS Prefetch + Preconnect for CDNs -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://code.jquery.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://unpkg.com">

    @php
        $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
        $hasHot = file_exists(public_path('hot'));

        $buildCssUrl = '';
        $buildJsUrl = '';

        if (!$isLocal || !$hasHot) {
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                if (isset($manifest['resources/css/app.css']['file'])) {
                    $buildCssUrl = asset('build/' . $manifest['resources/css/app.css']['file']);
                }
                if (isset($manifest['resources/js/app.js']['file'])) {
                    $buildJsUrl = asset('build/' . $manifest['resources/js/app.js']['file']);
                }
            }
        }
    @endphp

    @if($isLocal && $hasHot)
        <style>
            *,*::before,*::after{box-sizing:border-box}
            html,body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
            body{background-color:#f9fafb;color:#374151;display:flex;height:100dvh;overflow:hidden}
            #layout-menu{position:fixed;top:0;left:0;z-index:50}
            #mainWrapper{display:flex;flex:1;flex-direction:column;height:100dvh;overflow:hidden}
            @media(min-width:768px){#mainWrapper{margin-left:16rem}}
            #mainWrapper main{flex:1;overflow:auto;background-color:#f9fafb;padding:1rem}
            @media(min-width:768px){#mainWrapper main{padding:1.5rem}}
            .hidden{display:none}
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            *,*::before,*::after{box-sizing:border-box}
            html,body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
            body{background-color:#f9fafb;color:#374151;display:flex;height:100dvh;overflow:hidden}
            #layout-menu{position:fixed;top:0;left:0;z-index:50}
            #mainWrapper{display:flex;flex:1;flex-direction:column;height:100dvh;overflow:hidden}
            @media(min-width:768px){#mainWrapper{margin-left:16rem}}
            #mainWrapper main{flex:1;overflow:auto;background-color:#f9fafb;padding:1rem}
            @media(min-width:768px){#mainWrapper main{padding:1.5rem}}
            .hidden{display:none}
        </style>
        @if($buildCssUrl)
            <link rel="stylesheet" href="{{ $buildCssUrl }}">
        @endif
        @if($buildJsUrl)
            <script type="module" src="{{ $buildJsUrl }}"></script>
        @endif
    @endif

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"></noscript>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .bg-primary-red { background-color: #C0392B; }
        .text-primary-red { color: #C0392B; }
        .border-primary-red { border-color: #C0392B; }
        .hover-bg-primary-red:hover { background-color: #4c0519; }
        .bg-active-red { background-color: #C0392B; }
        [class^="bx-"],[class*=" bx-"]{width:1em;display:inline-block;text-align:center}
        .fa,.fas,.far,.fal,.fab{width:1.25em;display:inline-block;text-align:center}
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex h-[100dvh] overflow-hidden">

    @include('components.sidebar')

    <div id="mainWrapper" class="flex-1 flex flex-col h-[100dvh] md:ml-64 overflow-hidden">
        @include('components.navbar')

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6 custom-scrollbar flex flex-col min-h-0">
            <div class="flex-1 pb-20">
                @yield('content')
            </div>

            @include('components.footer')
        </main>
    </div>

    @if(auth()->check() && in_array(strtolower(auth()->user()->role), ['supervisor', 'leader', 'foreman']))
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function updateClock() {
                const clockEl = document.getElementById('liveClockTopbar');
                if(clockEl) {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
                }
            }
            setInterval(updateClock, 1000);
            updateClock();

            window.fetchGlobalTimer = function() {
                const popout = document.getElementById('global-timer-popout');
                if(!popout) return;

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
            setInterval(window.fetchGlobalTimer, 30000);
        });
    </script>
    @stack('modals')
    @yield('scripts')
    @stack('scripts')
</body>
</html>
