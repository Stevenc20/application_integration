<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin Panel')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @php
        $isLocal = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
        $hasHot = file_exists(public_path('hot'));
        
        $buildCssUrl = '';
        $buildJsUrl = '';
        
        if (!$isLocal || !$hasHot) {
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                if (isset($manifest['resources/css/supervisor.css']['file'])) {
                    $buildCssUrl = asset('build/' . $manifest['resources/css/supervisor.css']['file']);
                }
                if (isset($manifest['resources/js/app.js']['file'])) {
                    $buildJsUrl = asset('build/' . $manifest['resources/js/app.js']['file']);
                }
            }
        }
    @endphp

    @if($isLocal && $hasHot)
        @vite(['resources/css/supervisor.css', 'resources/js/app.js'])
    @else
        @if($buildCssUrl)
            <link rel="stylesheet" href="{{ $buildCssUrl }}">
        @endif
        @if($buildJsUrl)
            <script type="module" src="{{ $buildJsUrl }}"></script>
        @endif
    @endif


    <style>
        *,*::before,*::after{box-sizing:border-box}
        html,body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
        body{background-color:#f9fafb;color:#374151;display:flex;height:100dvh;overflow:hidden}
        #layout-menu{position:fixed;top:0;left:0;z-index:50;width:16rem;height:100dvh;background:#fff;overflow-y:auto;overflow-x:hidden;padding:1.25rem 1.25rem 0.5rem;box-shadow:0 10px 15px -3px rgba(0,0,0,0.05),0 4px 6px -2px rgba(0,0,0,0.025)}
        @media(min-width:768px){#layout-menu{transform:translateX(0)}}
        @media(max-width:767px){#layout-menu{transform:translateX(-100%)}}
        #mainWrapper{display:flex;flex:1;flex-direction:column;height:100dvh;overflow:hidden}
        @media(min-width:768px){#mainWrapper{margin-left:16rem}}
        #mainWrapper main{flex:1;overflow:auto;padding:1rem}
        @media(min-width:768px){#mainWrapper main{padding:1.5rem}}
        ::-webkit-scrollbar{width:8px;height:8px}
        ::-webkit-scrollbar-track{background:#f1f1f1}
        ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
        ::-webkit-scrollbar-thumb:hover{background:#94a3b8}
        .bg-primary-red{background-color:#C0392B}
        .text-primary-red{color:#C0392B}
        .border-primary-red{border-color:#C0392B}
    </style>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></noscript>
    @yield('head')
</head>
<body>

    @include('components.sidebar')

    <div id="mainWrapper">
        @include('components.navbar')

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 custom-scrollbar flex flex-col min-h-0">
            <div class="flex-1 pb-12">
                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
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
        });
    </script>
    @stack('modals')
    @yield('scripts')
    @stack('scripts')
</body>
</html>
