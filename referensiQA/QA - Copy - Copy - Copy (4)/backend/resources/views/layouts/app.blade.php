<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ rtrim(url('/'), '/') }}">

    <title>{{ $title ?? 'QA System' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#e11d48">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>

    <!-- Fabric.js (Canvas Annotation) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js" defer></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8FAFC]">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: true }">
        <!-- Sidebar -->
        <aside 
            class="bg-[#0F172A] text-white fixed lg:static z-50 h-full transition-all duration-300 overflow-hidden shadow-2xl"
            :class="sidebarOpen ? 'w-64' : 'w-0 lg:w-20'"
        >
            @php
                $isQpr = request()->is('qpr*');
                $logoBg = $isQpr ? 'bg-gradient-to-br from-sky-500 to-blue-600' : 'bg-red-600';
                $activeMenuBg = $isQpr ? 'bg-gradient-to-r from-sky-500 to-blue-500 shadow-sky-500/30' : 'bg-red-600 shadow-red-600/20';
            @endphp
            
            <div class="p-6 flex items-center gap-3 border-b border-slate-800 h-20">
                <div class="w-8 h-8 {{ $logoBg }} rounded-lg flex items-center justify-center font-black text-white shrink-0 transition-colors">Q</div>
                <span class="font-bold text-lg tracking-tight" x-show="sidebarOpen" x-transition>QA System</span>
            </div>

            <nav class="p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-160px)] custom-scrollbar">
                @php
                    $role = auth()->user()->role ?? 'Guest';
                    $rawMenus = [
                        'Admin' => [
                            ['label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['label' => 'Rekap Bulanan', 'route' => '/li/rekap', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['label' => 'LI Form', 'route' => '/li/create?new=1', 'icon' => 'M12 4v16m8-8H4'],
                            ['label' => 'LI List', 'route' => '/li', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['label' => 'Summary/LHI', 'route' => '/li/summary', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['label' => 'QPR Form', 'route' => '/qpr/create', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                            ['label' => 'QPR List', 'route' => '/qpr', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                            ['label' => 'User Management', 'route' => '/admin/users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                            ['label' => 'Master Mesin / Line', 'route' => '/admin/machines', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                            ['label' => 'Master Defect', 'route' => '/admin/defects', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                            ['label' => 'Standar Inspeksi', 'route' => '/li/master-template', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ],
                        'Group Leader' => [
                            ['label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['label' => 'LI Form', 'route' => '/li/create?new=1', 'icon' => 'M12 4v16m8-8H4'],
                            ['label' => 'LI List', 'route' => '/li', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['label' => 'QPR List', 'route' => '/qpr', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                        ],
                        'Foreman' => [
                            ['label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['label' => 'LI List', 'route' => '/li', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['label' => 'QPR List', 'route' => '/qpr', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                        ],
                        'Supervisor' => [
                            ['label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['label' => 'Rekap Bulanan', 'route' => '/li/rekap', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['label' => 'LI List', 'route' => '/li', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['label' => 'Summary/LHI', 'route' => '/li/summary', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['label' => 'QPR List', 'route' => '/qpr', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                        ],
                    ];

                    $menus = $rawMenus[$role] ?? [
                        ['label' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['label' => 'LI List', 'route' => '/li', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ];
                @endphp

                @foreach($menus as $menu)
                @php
                    $isActive = request()->is(trim($menu['route'], '/')) || 
                                (trim($menu['route'], '/') === 'li' && request()->is('li/*'));
                @endphp
                <a href="{{ $menu['route'] }}" 
                   class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all duration-300 group {{ $isActive ? $activeMenuBg . ' text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <div class="shrink-0 transition-transform duration-300 group-hover:scale-110">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $menu['icon'] }}" />
                        </svg>
                    </div>
                    <span class="font-black text-[13px] tracking-wide whitespace-nowrap overflow-hidden transition-all duration-300"
                          :class="sidebarOpen ? 'w-auto opacity-100' : 'w-0 opacity-0'">
                        {{ $menu['label'] }}
                    </span>
                </a>
                @endforeach
            </nav>

            <div class="absolute bottom-0 w-full p-4 border-t border-slate-800 bg-[#0F172A]">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="flex items-center gap-4 px-4 py-3 rounded-xl w-full text-slate-400 hover:bg-red-600/10 hover:text-red-500 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="font-semibold text-sm" x-show="sidebarOpen" x-transition>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-40">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 hover:bg-slate-100 rounded-lg lg:hidden">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div>
                        <h2 class="font-bold text-slate-800 text-lg tracking-tight">{{ $pageTitle ?? 'Dashboard' }}</h2>
                        <p class="text-xs text-slate-400 font-medium tracking-wide uppercase">{{ auth()->user()->role ?? 'Role' }} • QA SECTION</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ auth()->user()->employee_id ?? '-' }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-400 shadow-sm">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6 lg:p-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')

    @auth
    @php $authRole = auth()->user()->role; $authId = auth()->user()->id; $authName = auth()->user()->name; @endphp
    @if(in_array($authRole, ['Foreman', 'Group Leader']))
    {{-- GLOBAL INTERCOM PAGER (runs on every page for GL/Foreman) --}}
    <div id="global-intercom-overlay"
         style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(127,0,0,0.92); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:16px;">
        <div style="background:#0f172a; border:2px solid #ef4444; border-radius:28px; width:100%; max-width:420px; padding:32px; text-align:center; color:white; position:relative; overflow:hidden;">
            <div style="position:absolute;inset:0;background:rgba(239,68,68,0.05);border-radius:26px;animation:intercomPulse 1.5s ease-in-out infinite;pointer-events:none;"></div>
            <div style="position:relative;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                <div style="position:absolute;width:80px;height:80px;border-radius:50%;background:rgba(239,68,68,0.2);animation:intercomPing 1.2s ease-out infinite;"></div>
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#ef4444,#f43f5e);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;position:relative;z-index:1;animation:intercomBounce 0.8s ease-in-out infinite alternate;">🚨</div>
            </div>
            <div style="font-size:10px;font-weight:900;letter-spacing:4px;color:#ef4444;text-transform:uppercase;margin-bottom:4px;animation:intercomPulse 1s ease-in-out infinite;">PANGGILAN DARURAT JALUR</div>
            <div style="font-size:20px;font-weight:900;color:white;margin-bottom:16px;">BUTUH BANTUAN!</div>
            <div id="global-intercom-info" style="background:rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:16px;margin-bottom:20px;text-align:left;font-size:11px;font-weight:700;">
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;margin-bottom:8px;border-bottom:1px solid rgba(255,255,255,0.1);">
                    <span style="color:#94a3b8;font-size:8px;text-transform:uppercase;letter-spacing:2px;">LINE / LOKASI</span>
                    <span id="gi-lokasi" style="color:#f87171;font-weight:900;text-transform:uppercase;">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding-bottom:8px;margin-bottom:8px;border-bottom:1px solid rgba(255,255,255,0.1);">
                    <span style="color:#94a3b8;font-size:8px;text-transform:uppercase;letter-spacing:2px;">PART NAME</span>
                    <span id="gi-part" style="color:white;font-weight:900;">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:8px;text-transform:uppercase;letter-spacing:2px;">JOB NO</span>
                    <span id="gi-job" style="color:white;">—</span>
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <div style="font-size:8px;font-weight:900;color:#64748b;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;">PILIH JAWABAN CEPAT:</div>
                <div id="gi-presets" style="display:flex;flex-direction:column;gap:8px;"></div>
            </div>
            <div id="gi-selected-msg" style="display:none;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:12px;margin-bottom:16px;font-size:11px;font-style:italic;color:#6ee7b7;font-weight:700;"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <button onclick="globalIntercomRespond('decline')"
                        style="padding:14px;background:#1e293b;color:#94a3b8;border:none;border-radius:16px;font-weight:900;font-size:10px;text-transform:uppercase;letter-spacing:1px;cursor:pointer;transition:all 0.2s;">
                    ✕ Sibuk
                </button>
                <button id="gi-accept-btn" onclick="globalIntercomRespond('accept')"
                        style="padding:14px;background:#059669;color:white;border:none;border-radius:16px;font-weight:900;font-size:10px;text-transform:uppercase;letter-spacing:1px;cursor:pointer;animation:intercomPulse 1.5s ease-in-out infinite;transition:all 0.2s;">
                    📞 Terima &amp; Kirim
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes intercomPulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
        @keyframes intercomPing { 0%{transform:scale(1);opacity:0.8} 100%{transform:scale(2);opacity:0} }
        @keyframes intercomBounce { from{transform:translateY(0)} to{transform:translateY(-6px)} }
    </style>

    <script>
    (function() {
        const INTERCOM_USER_ID   = '{{ $authId }}';
        const INTERCOM_USER_ROLE = '{{ $authRole }}';
        const INTERCOM_USER_NAME = '{{ $authName }}';
        const PRESET_MESSAGES    = [
            '✅ Saya meluncur ke jalur sekarang!',
            '⏳ Tunggu 5 menit, sedang ada inspeksi lain',
            '🔄 Sedang tanggung, mohon ditunggu',
            '🤝 Segera ke sana bersama Foreman',
        ];

        let activeCallId      = null;
        let selectedPreset    = PRESET_MESSAGES[0];
        let sirenNode         = null;
        let sirenCtx          = null;
        let isShowing         = false;

        // Build preset buttons once
        const presetsDiv = document.getElementById('gi-presets');
        PRESET_MESSAGES.forEach((msg, i) => {
            const btn = document.createElement('button');
            btn.textContent = msg;
            btn.dataset.msg = msg;
            btn.id = 'gi-preset-' + i;
            btn.style.cssText = 'width:100%;text-align:left;padding:10px 14px;border-radius:10px;border:1px solid;font-size:10px;font-weight:700;cursor:pointer;transition:all 0.2s;background:rgba(0,0,0,0.3);color:#94a3b8;border-color:#334155;';
            btn.onclick = () => selectPreset(msg, i);
            presetsDiv.appendChild(btn);
        });
        selectPreset(PRESET_MESSAGES[0], 0);

        function selectPreset(msg, idx) {
            selectedPreset = msg;
            document.querySelectorAll('#gi-presets button').forEach((b, i) => {
                if (i === idx) {
                    b.style.background = 'rgba(16,185,129,0.15)';
                    b.style.color = '#6ee7b7';
                    b.style.borderColor = '#059669';
                } else {
                    b.style.background = 'rgba(0,0,0,0.3)';
                    b.style.color = '#94a3b8';
                    b.style.borderColor = '#334155';
                }
            });
            const msgDiv = document.getElementById('gi-selected-msg');
            msgDiv.style.display = 'block';
            msgDiv.textContent = '"' + msg + '"';
        }

        function playSiren() {
            try {
                sirenCtx = new (window.AudioContext || window.webkitAudioContext)();
                sirenNode = sirenCtx.createOscillator();
                const gain = sirenCtx.createGain();
                sirenNode.connect(gain);
                gain.connect(sirenCtx.destination);
                sirenNode.type = 'sawtooth';
                gain.gain.setValueAtTime(0.3, sirenCtx.currentTime);
                // Sweep up-down for siren effect
                sirenNode.frequency.setValueAtTime(600, sirenCtx.currentTime);
                for (let t = 0; t < 60; t += 1.2) {
                    sirenNode.frequency.linearRampToValueAtTime(1100, sirenCtx.currentTime + t + 0.6);
                    sirenNode.frequency.linearRampToValueAtTime(600,  sirenCtx.currentTime + t + 1.2);
                }
                sirenNode.start();
            } catch (e) { console.warn('Audio not available', e); }
        }

        function stopSiren() {
            try { if (sirenNode) { sirenNode.stop(); sirenNode = null; } } catch(e){}
            try { if (sirenCtx) { sirenCtx.close(); sirenCtx = null; } } catch(e){}
        }

        function showOverlay(call) {
            isShowing = true;
            activeCallId = call.lembar_inspeksi_id;
            const li = call.lembar_inspeksi || {};
            document.getElementById('gi-lokasi').textContent = li.lokasi || '—';
            document.getElementById('gi-part').textContent   = li.part_name || '—';
            document.getElementById('gi-job').textContent    = li.job_no || '—';
            const overlay = document.getElementById('global-intercom-overlay');
            overlay.style.display = 'flex';
            playSiren();
        }

        function hideOverlay() {
            isShowing = false;
            activeCallId = null;
            stopSiren();
            document.getElementById('global-intercom-overlay').style.display = 'none';
        }

        window.globalIntercomRespond = async function(action) {
            if (!activeCallId) return;
            stopSiren();
            const liId = activeCallId;
            hideOverlay();
            try {
                await window.axios.post('/api/intercom/respond', {
                    lembar_inspeksi_id: liId,
                    action: action,
                    responder_name: INTERCOM_USER_NAME,
                    message: action === 'accept' ? selectedPreset : 'GL/Foreman sedang sibuk.'
                });
            } catch (e) { console.error('Intercom respond error', e); }
        };

        // Poll every 4 seconds
        async function pollIncoming() {
            try {
                const res = await window.axios.get(
                    `/api/intercom/active-incoming?user_id=${encodeURIComponent(INTERCOM_USER_ID)}&role=${encodeURIComponent(INTERCOM_USER_ROLE)}`
                );
                const call = res.data?.data;
                if (call && !isShowing) {
                    showOverlay(call);
                } else if (!call && isShowing) {
                    hideOverlay();
                }
            } catch (e) {
                // silently ignore network errors
            }
        }

        // Start polling after page load
        window.addEventListener('load', () => {
            setInterval(pollIncoming, 4000);
        });
    })();
    </script>
    @endif
    
    @endauth

    {{-- Priority Popup Notification --}}
    @auth
    <x-approval-notif />
    @endauth
</body>
</html>
