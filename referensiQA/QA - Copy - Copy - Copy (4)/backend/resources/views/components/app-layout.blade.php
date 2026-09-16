@props(['pageTitle' => 'QA System', 'title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ rtrim(url('/'), '/') }}">
    @auth
    <meta name="auth-user" content="{{ json_encode(['id' => auth()->id(), 'name' => auth()->user()->name, 'role' => auth()->user()->role, 'department' => auth()->user()->department]) }}">
    @endauth
    <title>{{ $title ?? $pageTitle }} | QA System</title>
    <link rel="preload" href="{{ asset('SidebarBG.jpg') }}" as="image">
    <link rel="preload" href="{{ asset('IPPI.png') }}" as="image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; height: 100vh; height: 100dvh; }
        [x-cloak] { display: none !important; }
        .app-shell { height: 100vh; height: 100dvh; overflow: hidden; }
        .app-sidebar { height: 100vh; height: 100dvh; max-height: 100dvh; max-height: 100dvh; padding-bottom: env(safe-area-inset-bottom); }
        .app-sidebar-nav { overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }
        .app-main { min-height: 0; }

        #global-skeleton-loader { display: none; }
        body.page-loading #global-skeleton-loader { display: block; }
        body.page-loading #main-page-content { display: none; opacity: 0; }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 page-loading">


<div class="app-shell flex" x-data="notifications">

    {{-- Backdrop menu mobile/tablet --}}
    <div x-show="mobileNavOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeMobileNav()"
         class="app-sidebar-backdrop fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>

    {{-- SIDEBAR: drawer di HP, fixed di tablet/desktop --}}
    <aside class="app-sidebar text-white flex flex-col shadow-2xl overflow-hidden print:hidden
                   fixed inset-y-0 left-0 z-50 w-[min(18rem,88vw)] md:w-64
                   transition-[transform,width] duration-300 ease-out
                   md:relative md:z-auto md:shrink-0 md:translate-x-0"
           :class="[
               mobileNavOpen ? 'translate-x-0' : '-translate-x-full',
               sidebarOpen ? 'md:w-64' : 'md:w-[4.5rem]'
           ]"
           style="background-color: #450a0a;">

        {{-- Background Image --}}
        <div class="absolute inset-0 z-0" style="
            background-image: url('{{ asset('SidebarBG.jpg') }}');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            opacity: 0.6;
        "></div>

        {{-- Dark overlay gradient for readability --}}
        <div class="absolute inset-0 z-0" style="background: linear-gradient(180deg, rgba(10,0,0,0.6) 0%, rgba(20,0,0,0.45) 40%, rgba(10,0,0,0.75) 100%);"></div>

        {{-- Subtle glow accent top-right --}}
        <div class="absolute top-0 right-0 w-32 h-32 z-0 pointer-events-none" style="background: radial-gradient(circle, rgba(220,38,38,0.15) 0%, transparent 70%);"></div>
        {{-- Logo --}}
        <div class="relative z-10 shrink-0 flex items-center justify-between gap-3 px-3 sm:px-4 h-14 sm:h-16 border-b border-white/10">
            <div class="flex items-center min-w-0 flex-1 overflow-hidden transition-all duration-300 gap-3"
                 :class="isDesktop && !sidebarOpen ? 'md:justify-center md:gap-0' : 'gap-3'">
                {{-- IPPI Logo: constrain width when collapsed to avoid clipping --}}
                <div class="shrink-0 rounded-lg transition-all duration-300 h-8 w-8 flex items-center justify-center overflow-hidden" 
                     :class="isDesktop && !sidebarOpen ? 'h-7 w-7' : 'h-8 w-8'">
                    <img src="{{ asset('IPPI.png') }}" alt="IPPI" class="w-full h-full object-contain transition-all duration-300 text-transparent">
                </div>
                {{-- Mobile label --}}
                <span class="font-bold text-sm text-white whitespace-nowrap transition-opacity duration-200 md:hidden opacity-100"
                      :class="mobileNavOpen ? 'opacity-100' : 'opacity-0'">QA System</span>
                {{-- Desktop two-line label --}}
                <div class="hidden md:flex md:flex-col md:justify-center transition-all duration-300 overflow-hidden"
                     :class="sidebarOpen ? 'opacity-100 max-w-[160px]' : 'opacity-0 max-w-0 pointer-events-none'">
                    <p class="font-black text-sm text-white whitespace-nowrap leading-tight">Quality Assurance</p>
                    <p class="text-[10px] text-slate-400 font-semibold whitespace-nowrap tracking-wide mt-1">PT. Inti Pantja Press Industri</p>
                </div>
            </div>
            <button type="button" @click="closeMobileNav()" class="md:hidden touch-target p-2 -mr-1 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800" aria-label="Tutup menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="app-sidebar-nav relative z-10 flex-1 min-h-0 p-3 space-y-2 overflow-y-auto overflow-x-hidden">
            @php
                $role  = auth()->user()->role ?? 'Guest';
                $dash  = ['dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'];
                $rekp  = ['li/rekap', 'Rekap Bulanan', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'];
                $liF   = ['li/create', 'Buat Master LI', 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'];
                $liL   = ['li', 'Master LI', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'];
                $liS   = ['li/summary', 'Summary/LHI', 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'];
                $qprF  = ['qpr/create', 'QPR Form', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'];
                $qprL  = ['qpr', 'QPR List', 'M4 6h16M4 10h16M4 14h16M4 18h16'];
                $qprR  = ['qpr/registration', 'Registrasi QPR', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'];
                $users = ['admin/users', 'Users', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
                $qcW   = ['qc/worklist', 'Antrian Kerja QC', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'];
                $rapor = ['rapor-qc', 'Rapor Kinerja QC', 'M13 10V3L4 14h7v7l9-11h-7z'];
                $itCk  = ['item-check', 'Item Check', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'];

                $menus = match($role) {
                    'Admin' => [$dash, $liF, $liL, $itCk, $rekp, $liS, $qprF, $qprL, $qprR, $users, ['admin/machines', 'Master Mesin / Line', 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'], ['admin/defects', 'Master Defect', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'], ['li/master-template', 'Standar Inspeksi', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'], $qcW, $rapor],

                    'Leader', 'Group Leader' => [$dash, $liL, $itCk, $liS, $qprL, $qprR, $rapor],

                    'Foreman'    => [$dash, $liL, $itCk, $liS, $qprL, $qprR, $rapor],
                    'Supervisor' => [$dash, $liL, $itCk, $rekp, $liS, $qprL, $qprR, $rapor],
                    'Operator', 'QC' => [$dash, $liL, $itCk, $liS, $qprL, $qprR, $rapor],
                    default      => [$dash, $liL, $itCk, $qprL],
                };
            @endphp

            @foreach($menus as [$route, $label, $icon])
            @php $isActive = request()->is(ltrim(url($route), url(''))); @endphp
            <a href="{{ url($route) }}" @click="onNavClick()"
               class="flex items-center px-3 py-3 sm:py-2.5 rounded-xl transition-all group overflow-hidden min-h-[48px] md:min-h-0 gap-3 {{ $isActive ? 'bg-gradient-to-r from-red-800 to-red-600 text-white shadow-md shadow-red-900/40 font-bold border border-red-700/50' : 'text-slate-300 hover:bg-[#4c0b0b] hover:text-white' }}"
               :class="isDesktop && !sidebarOpen ? 'md:justify-center md:px-2 md:gap-0' : 'gap-3'">
                <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-white-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $icon }}"/>
                </svg>
                <span class="text-sm font-semibold whitespace-nowrap transition-opacity duration-200 md:hidden">{{ $label }}</span>
                <span class="text-sm font-semibold whitespace-nowrap transition-opacity duration-200 hidden md:inline opacity-100"
                      :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none w-0 overflow-hidden'">{{ $label }}</span>
                
                {{-- Dynamic Badge for Approval --}}
                @if($label === 'Master Template')
                <template x-if="liCount > 0">
                    <span class="ml-auto bg-red-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center shadow-lg shadow-red-600/40"
                          x-text="liCount"
                          x-show="sidebarOpen || mobileNavOpen"></span>
                </template>
                @endif

                {{-- Dynamic Badge for QPR List --}}
                @if($label === 'QPR List')
                <template x-if="qprCount > 0">
                    <span class="ml-auto bg-amber-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center shadow-lg shadow-amber-500/40"
                          x-text="qprCount"
                          x-show="sidebarOpen || mobileNavOpen"></span>
                </template>
                @endif
            </a>
            @endforeach
        </nav>



        {{-- Logout --}}
        <div class="relative z-10 shrink-0 p-3 border-t border-white/10">
            <form action="{{ route('logout') }}" method="POST" @submit="sessionStorage.clear(); localStorage.removeItem('user')">
                @csrf
                <button type="submit"
                    class="flex items-center w-full px-3 py-3 sm:py-2.5 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all overflow-hidden min-h-[48px] md:min-h-0 gap-3"
                    :class="isDesktop && !sidebarOpen ? 'md:justify-center md:px-2 md:gap-0' : 'gap-3'">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="text-sm font-semibold whitespace-nowrap md:hidden">Logout</span>
                    <span class="text-sm font-semibold whitespace-nowrap hidden md:inline opacity-100"
                          :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none w-0 overflow-hidden'">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN (area scroll konten halaman) --}}
    <div class="app-main flex-1 flex flex-col min-w-0 overflow-hidden">
        {{-- TOPBAR --}}
        <header class="shrink-0 min-h-14 sm:h-[72px] bg-white/80 backdrop-blur-xl border-b border-white/50 flex items-center justify-between gap-2 px-3 sm:px-4 md:px-8 sticky top-0 z-30 safe-bottom print:hidden"
                style="box-shadow: 0 4px 30px rgba(0,0,0,0.03);">
            
            <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                {{-- Hamburger Menu --}}
                <button type="button" @click="toggleSidebar()"
                    class="touch-target w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100/80 text-slate-500 transition-all shrink-0 border border-transparent hover:border-slate-200/60"
                    aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                
                {{-- Page Title --}}
                <div class="min-w-0 flex flex-col justify-center">
                    <h2 class="font-black text-slate-800 text-[15px] sm:text-[17px] tracking-tight truncate leading-none mb-1">{{ $pageTitle }}</h2>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:gap-5 shrink-0">
                {{-- Notifications Bell & Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    {{-- Bell Button --}}
                    <button @click="open = !open" @click.away="open = false" 
                            class="relative p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors outline-none focus:ring-2 focus:ring-red-100">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        {{-- Red dot indicator --}}
                        <template x-if="liCount > 0 || qprCount > 0">
                            <span class="absolute top-1.5 right-2 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
                        </template>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-[0_12px_40px_-12px_rgba(0,0,0,0.15)] border border-slate-100 overflow-hidden z-50"
                         style="display: none;">
                        
                        <div class="px-4 py-3 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Notifikasi</h3>
                            <template x-if="liCount > 0 || qprCount > 0">
                                <span class="bg-red-50 text-red-600 text-[9px] font-black px-2 py-0.5 rounded-full border border-red-100" x-text="(liCount + qprCount) + ' Baru'"></span>
                            </template>
                        </div>
                        
                        <div class="max-h-[320px] overflow-y-auto">
                            <template x-if="liCount === 0 && qprCount === 0">
                                <div class="px-4 py-8 text-center flex flex-col items-center">
                                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-500">Semua sudah selesai!</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada yang perlu diapprove.</p>
                                </div>
                            </template>
                            
                            {{-- LI Approval / Action Notice --}}
                            @php
                                $currentUserRole = strtolower(trim(auth()->user()->role ?? ''));
                                if (in_array($currentUserRole, ['operator', 'qc'])) {
                                    $notifLiTitle = 'Butuh Inspeksi Aktual';
                                    $notifLiDesc  = 'Terdapat jadwal produksi hari ini untuk diinspeksi.';
                                    $notifLiLink  = '/item-check';
                                } elseif (in_array($currentUserRole, ['leader', 'group leader'])) {
                                    $notifLiTitle = 'Butuh Verifikasi / Revisi';
                                    $notifLiDesc  = 'dokumen menunggu verifikasi atau perbaikan.';
                                    $notifLiLink  = '/li';
                                } else {
                                    $notifLiTitle = 'Butuh Tindakan Lembar Inspeksi';
                                    $notifLiDesc  = 'dokumen sedang menunggu tindakan Anda.';
                                    $notifLiLink  = '/li';
                                }
                            @endphp
                            <template x-if="liCount > 0">
                                <a href="{{ url($notifLiLink) }}" class="flex gap-3 p-4 hover:bg-slate-50 transition-colors border-b border-slate-50 group">
                                    <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                        <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-800 leading-tight mb-1">{{ $notifLiTitle }}</p>
                                        <p class="text-[11px] text-slate-500 leading-snug"><span x-text="liCount" class="font-black text-red-500"></span> {{ $notifLiDesc }}</p>
                                    </div>
                                </a>
                            </template>
                            
                            {{-- QPR Notice (if any) --}}
                            <template x-if="qprCount > 0">
                                <a href="{{ url('/qpr') }}" class="flex gap-3 p-4 hover:bg-slate-50 transition-colors group">
                                    <template x-if="pendingData && pendingData.qprs && pendingData.qprs.some(q => (q.qpr ? q.qpr.status : q.status) === 'Waiting A3 Report')">
                                        <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:rotate-3 transition-transform shadow-sm">
                                            <svg class="w-4.5 h-4.5 text-rose-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                    </template>
                                    <template x-if="!(pendingData && pendingData.qprs && pendingData.qprs.some(q => (q.qpr ? q.qpr.status : q.status) === 'Waiting A3 Report'))">
                                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                            <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                    </template>
                                    <div>
                                        <template x-if="pendingData && pendingData.qprs && pendingData.qprs.some(q => (q.qpr ? q.qpr.status : q.status) === 'Waiting A3 Report')">
                                            <div>
                                                <p class="text-sm font-black text-rose-700 leading-tight mb-1">A3 Report Dibutuhkan</p>
                                                <p class="text-[11px] text-rose-600 leading-snug font-medium">Terdapat langkah perbaikan berstatus NG berturut-turut. Segera proses A3 Report!</p>
                                            </div>
                                        </template>
                                        <template x-if="!(pendingData && pendingData.qprs && pendingData.qprs.some(q => (q.qpr ? q.qpr.status : q.status) === 'Waiting A3 Report'))">
                                            <div>
                                                <p class="text-sm font-black text-slate-800 leading-tight mb-1">Butuh Approval QPR</p>
                                                <p class="text-[11px] text-slate-500 leading-snug"><span x-text="qprCount" class="font-black text-amber-600"></span> laporan QPR menunggu tindakan.</p>
                                            </div>
                                        </template>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- User Profile --}}
                <div class="flex items-center gap-3 pl-3 sm:pl-5 border-l border-slate-200/60">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-black text-slate-800 truncate max-w-[150px] leading-tight ml-auto w-max">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold mt-1 ml-auto w-max">{{ auth()->user()->employee_id }}</p>
                    </div>
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-[14px] bg-gradient-to-br from-red-100 to-rose-200 border border-white flex items-center justify-center font-black text-red-600 text-sm shrink-0 relative"
                         style="box-shadow: 0 4px 12px rgba(225,29,72,0.15);">
                        {{ substr(auth()->user()->name, 0, 1) }}
                        {{-- Online indicator --}}
                        <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"></div>
                    </div>
                </div>
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <main class="flex-1 min-h-0 p-3 sm:p-4 md:p-6 lg:p-8 overflow-y-auto overflow-x-hidden -webkit-overflow-scrolling-touch relative print:p-0 print:overflow-visible">
            
            {{-- Global Skeleton Loader --}}
            <div id="global-skeleton-loader" class="w-full animate-pulse">
                @if(request()->is('dashboard') || request()->is('/'))
                    {{-- ══ DASHBOARD SKELETON ══ --}}
                    <div class="space-y-4">
                        {{-- TOP ROW: BANNER + CALENDAR --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="lg:col-span-2 h-[220px] bg-slate-200 rounded-[32px] w-full"></div>
                            <div class="lg:col-span-1 h-[220px] bg-slate-200 rounded-[24px] w-full"></div>
                        </div>

                        {{-- BOTTOM ROW: SUMMARY + WIDGETS --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            {{-- LEFT (Cards & Table) --}}
                            <div class="lg:col-span-2 space-y-4">
                                {{-- Period Control --}}
                                <div class="bg-white p-4 rounded-[24px] border border-slate-100 h-[72px] flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-200"></div>
                                        <div class="w-32 h-4 bg-slate-200 rounded"></div>
                                    </div>
                                    <div class="flex gap-2">
                                        <div class="w-24 h-9 bg-slate-200 rounded-full"></div>
                                        <div class="w-24 h-9 bg-slate-200 rounded-full"></div>
                                        <div class="w-24 h-9 bg-slate-200 rounded-full hidden sm:block"></div>
                                        <div class="w-24 h-9 bg-slate-200 rounded-full hidden sm:block"></div>
                                    </div>
                                </div>

                                {{-- LI Summary Cards --}}
                                <div>
                                    <div class="h-4 w-40 bg-slate-200 rounded-full mb-3 ml-2"></div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div class="h-[140px] bg-white rounded-[24px] border border-slate-100 p-4 flex flex-col justify-between">
                                            <div class="w-10 h-10 rounded-full bg-slate-200"></div>
                                            <div>
                                                <div class="w-20 h-3 bg-slate-200 rounded mb-2"></div>
                                                <div class="w-12 h-8 bg-slate-200 rounded"></div>
                                            </div>
                                        </div>
                                        <div class="h-[140px] bg-white rounded-[24px] border border-slate-100 p-4 flex flex-col justify-between">
                                            <div class="w-10 h-10 rounded-full bg-slate-200"></div>
                                            <div>
                                                <div class="w-20 h-3 bg-slate-200 rounded mb-2"></div>
                                                <div class="w-12 h-8 bg-slate-200 rounded"></div>
                                            </div>
                                        </div>
                                        <div class="h-[140px] bg-white rounded-[24px] border border-slate-100 p-4 flex flex-col justify-between">
                                            <div class="w-10 h-10 rounded-full bg-slate-200"></div>
                                            <div>
                                                <div class="w-20 h-3 bg-slate-200 rounded mb-2"></div>
                                                <div class="w-12 h-8 bg-slate-200 rounded"></div>
                                            </div>
                                        </div>
                                        <div class="h-[140px] bg-white rounded-[24px] border border-slate-100 p-4 flex flex-col justify-between">
                                            <div class="w-10 h-10 rounded-full bg-slate-200"></div>
                                            <div>
                                                <div class="w-20 h-3 bg-slate-200 rounded mb-2"></div>
                                                <div class="w-12 h-8 bg-slate-200 rounded"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- QPR Summary Cards --}}
                                <div>
                                    <div class="h-4 w-48 bg-slate-200 rounded-full mb-3 ml-2 mt-2"></div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="h-16 bg-white rounded-[16px] border border-slate-100 flex items-center px-4">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 mr-3"></div>
                                            <div class="flex-1"><div class="w-full h-3 bg-slate-200 rounded"></div></div>
                                        </div>
                                        <div class="h-16 bg-white rounded-[16px] border border-slate-100 flex items-center px-4">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 mr-3"></div>
                                            <div class="flex-1"><div class="w-full h-3 bg-slate-200 rounded"></div></div>
                                        </div>
                                        <div class="h-16 bg-white rounded-[16px] border border-slate-100 flex items-center px-4">
                                            <div class="w-8 h-8 rounded-full bg-slate-200 mr-3"></div>
                                            <div class="flex-1"><div class="w-full h-3 bg-slate-200 rounded"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT (Charts & Tracking) --}}
                            <div class="lg:col-span-1 space-y-4">
                                {{-- Chart Widget --}}
                                <div class="bg-white rounded-[24px] border border-slate-100 h-[280px] p-6">
                                    <div class="flex gap-3 mb-6">
                                        <div class="w-12 h-12 rounded-[16px] bg-slate-200"></div>
                                        <div class="space-y-2 py-1">
                                            <div class="w-32 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-24 h-3 bg-slate-200 rounded"></div>
                                        </div>
                                    </div>
                                    <div class="w-full h-32 bg-slate-100 rounded-xl"></div>
                                </div>

                                {{-- Tracking Widget --}}
                                <div class="bg-white rounded-[24px] border border-slate-100 h-[300px] p-6">
                                    <div class="flex gap-3 mb-6">
                                        <div class="w-12 h-12 rounded-[16px] bg-slate-200"></div>
                                        <div class="space-y-2 py-1">
                                            <div class="w-32 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-24 h-3 bg-slate-200 rounded"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex gap-4"><div class="w-12 h-12 rounded-xl bg-slate-200 shrink-0"></div><div class="space-y-2 flex-1 py-1"><div class="w-full h-4 bg-slate-200 rounded"></div><div class="w-2/3 h-3 bg-slate-200 rounded"></div></div></div>
                                        <div class="flex gap-4"><div class="w-12 h-12 rounded-xl bg-slate-200 shrink-0"></div><div class="space-y-2 flex-1 py-1"><div class="w-full h-4 bg-slate-200 rounded"></div><div class="w-2/3 h-3 bg-slate-200 rounded"></div></div></div>
                                        <div class="flex gap-4"><div class="w-12 h-12 rounded-xl bg-slate-200 shrink-0"></div><div class="space-y-2 flex-1 py-1"><div class="w-full h-4 bg-slate-200 rounded"></div><div class="w-2/3 h-3 bg-slate-200 rounded"></div></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif(request()->is('li/summary') || request()->is('qpr/registration'))
                    {{-- ══ SUMMARY LHI SKELETON ══ --}}
                    <div class="max-w-[1160px] mx-auto mt-8">
                        {{-- Skeleton: Filter Bar --}}
                        <div class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            {{-- Title --}}
                            <div class="space-y-2 w-[250px]">
                                <div class="h-6 bg-slate-200 rounded-full w-3/4"></div>
                                <div class="h-3 bg-slate-200 rounded-full w-1/2"></div>
                            </div>

                            {{-- Filters & Actions --}}
                            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 lg:ml-auto">
                                {{-- 3 Selectors --}}
                                <div class="flex gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
                                    <div class="h-9 w-[120px] bg-slate-200 rounded-xl"></div>
                                    <div class="h-9 w-[100px] bg-slate-200 rounded-xl"></div>
                                    <div class="h-9 w-[140px] bg-slate-200 rounded-xl"></div>
                                </div>
                                <div class="hidden lg:block w-px h-8 bg-slate-200 mx-1"></div>
                                {{-- Pagination & Print --}}
                                <div class="flex gap-3">
                                    <div class="h-[42px] w-[130px] bg-slate-200 rounded-xl"></div>
                                    <div class="h-[42px] w-[100px] bg-slate-200 rounded-xl"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Skeleton: Kertas Report --}}
                        <div class="bg-white border border-slate-200 p-6 rounded-sm">
                            {{-- Header Kertas --}}
                            <div class="flex justify-between items-start border-t-[3px] border-slate-300 pt-3 pb-4">
                                {{-- Left (Company) --}}
                                <div class="space-y-2">
                                    <div class="h-3 w-40 bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-32 bg-slate-200 rounded-full"></div>
                                </div>
                                {{-- Center (Title) --}}
                                <div class="space-y-3 flex flex-col items-center flex-1 px-4">
                                    <div class="h-5 w-64 bg-slate-200 rounded-full"></div>
                                    <div class="flex gap-4">
                                        <div class="h-3 w-24 bg-slate-200 rounded-full"></div>
                                        <div class="h-3 w-32 bg-slate-200 rounded-full"></div>
                                    </div>
                                </div>
                                {{-- Right (Sigs) --}}
                                <div class="flex gap-2 h-[75px]">
                                    <div class="w-[60px] border border-slate-200 bg-slate-50 flex flex-col">
                                        <div class="flex-1"></div>
                                        <div class="h-3 border-t border-slate-200 bg-slate-100"></div>
                                    </div>
                                    <div class="w-[60px] border border-slate-200 bg-slate-50 flex flex-col">
                                        <div class="flex-1"></div>
                                        <div class="h-3 border-t border-slate-200 bg-slate-100"></div>
                                    </div>
                                    <div class="w-[60px] border border-slate-200 bg-slate-50 flex flex-col">
                                        <div class="flex-1"></div>
                                        <div class="h-3 border-t border-slate-200 bg-slate-100"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Table Body --}}
                            <div class="border border-slate-300">
                                {{-- Table Header 2 rows --}}
                                <div class="h-8 border-b border-slate-300 bg-slate-100"></div>
                                <div class="h-8 border-b border-slate-300 bg-slate-50"></div>
                                
                                {{-- Table Rows --}}
                                @for($i = 0; $i < 10; $i++)
                                <div class="h-6 border-b border-slate-200 flex">
                                    <div class="w-10 border-r border-slate-200"></div>
                                    <div class="flex-1 border-r border-slate-200"></div>
                                    <div class="w-20 border-r border-slate-200"></div>
                                    <div class="w-32 border-r border-slate-200"></div>
                                    <div class="w-[30%]"></div>
                                </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                @elseif(request()->is('qpr'))
                    {{-- ══ QPR LIST SKELETON ══ --}}
                    {{-- Search & Action Bar --}}
                    <div class="bg-white p-3 sm:p-4 rounded-[24px] sm:rounded-[32px] border border-slate-100 mb-6 shadow-sm flex flex-col sm:flex-row gap-3">
                        <div class="flex-1 h-[52px] bg-slate-100 rounded-[20px] flex items-center px-4 justify-between">
                            <div class="w-1/3 h-4 bg-slate-200 rounded-full"></div>
                            <div class="w-32 h-8 bg-slate-200 rounded-xl"></div>
                        </div>
                        <div class="flex gap-2">
                            <div class="w-[52px] h-[52px] bg-slate-200 rounded-[20px] shrink-0"></div>
                            <div class="w-[160px] h-[52px] bg-slate-200 rounded-[20px] shrink-0"></div>
                        </div>
                    </div>

                    {{-- Tabs Skeleton --}}
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide flex-1">
                            <div class="h-[38px] w-24 bg-slate-300 rounded-full shrink-0"></div>
                            <div class="h-[38px] w-32 bg-slate-200 rounded-full shrink-0"></div>
                            <div class="h-[38px] w-36 bg-slate-200 rounded-full shrink-0"></div>
                            <div class="h-[38px] w-36 bg-slate-200 rounded-full shrink-0"></div>
                            <div class="h-[38px] w-28 bg-slate-200 rounded-full shrink-0"></div>
                            <div class="h-[38px] w-28 bg-slate-200 rounded-full shrink-0"></div>
                        </div>
                        <div class="hidden sm:block w-24 h-6 bg-slate-200 rounded-full shrink-0 ml-4"></div>
                    </div>

                    {{-- Card Grid Skeleton --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                        @for($i = 0; $i < 8; $i++)
                        <div class="bg-white rounded-[32px] border border-slate-100 p-6 shadow-sm flex flex-col min-h-[340px]">
                            {{-- Header --}}
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-20 h-6 bg-slate-200 rounded-full"></div>
                                <div class="w-8 h-8 bg-slate-100 rounded-full"></div>
                            </div>
                            
                            {{-- Title & Subtitle --}}
                            <div class="space-y-3 mb-6">
                                <div class="w-full h-6 bg-slate-200 rounded-full"></div>
                                <div class="w-3/4 h-4 bg-slate-100 rounded-full"></div>
                            </div>

                            {{-- Details --}}
                            <div class="space-y-4 mb-6 flex-1">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-100 rounded-xl shrink-0"></div>
                                    <div class="space-y-2 flex-1">
                                        <div class="w-16 h-2 bg-slate-200 rounded-full"></div>
                                        <div class="w-1/2 h-3 bg-slate-200 rounded-full"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-100 rounded-xl shrink-0"></div>
                                    <div class="space-y-2 flex-1">
                                        <div class="w-20 h-2 bg-slate-200 rounded-full"></div>
                                        <div class="w-2/3 h-3 bg-slate-200 rounded-full"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-100 rounded-xl shrink-0"></div>
                                    <div class="space-y-2 flex-1">
                                        <div class="w-16 h-2 bg-slate-200 rounded-full"></div>
                                        <div class="w-24 h-3 bg-slate-200 rounded-full"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Button --}}
                            <div class="w-full h-12 bg-slate-100 rounded-2xl"></div>
                        </div>
                        @endfor
                    </div>

                @elseif(request()->is('li/create', 'li/*/edit'))
                    {{-- ══ LI FORM SKELETON ══ --}}
                    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden animate-pulse min-h-[600px] flex flex-col">
                        {{-- Header --}}
                        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div class="flex items-center gap-6">
                                <div class="w-[120px] h-10 bg-slate-200 rounded-lg"></div>
                                <div class="h-10 w-px bg-slate-300"></div>
                                <div class="space-y-2">
                                    <div class="h-6 w-56 bg-slate-300 rounded-full"></div>
                                    <div class="h-4 w-32 bg-slate-200 rounded-full"></div>
                                </div>
                            </div>
                            <div class="w-24 h-8 bg-slate-300 rounded-full"></div>
                        </div>
                        
                        {{-- Tabs / Stepper --}}
                        <div class="flex gap-6 px-8 py-4 border-b border-slate-100 justify-center">
                            <div class="w-48 h-10 bg-slate-200 rounded-full"></div>
                            <div class="w-48 h-10 bg-slate-100 rounded-full"></div>
                            <div class="w-48 h-10 bg-slate-100 rounded-full"></div>
                        </div>
                        
                        {{-- Body Grid (Identitas & Signatures) --}}
                        <div class="flex flex-col lg:flex-row p-8 gap-8">
                            {{-- Identitas (Left) --}}
                            <div class="flex-[2] space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2"><div class="h-3 w-16 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                    <div class="space-y-2"><div class="h-3 w-20 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                    <div class="space-y-2"><div class="h-3 w-24 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                    <div class="space-y-2"><div class="h-3 w-16 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                    <div class="space-y-2"><div class="h-3 w-24 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                    <div class="space-y-2"><div class="h-3 w-20 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                </div>
                                {{-- Section Formula/Table --}}
                                <div class="h-32 w-full bg-slate-100 rounded-2xl"></div>
                            </div>
                            {{-- Signatures (Right) --}}
                            <div class="flex-1 flex gap-2 border border-slate-100 rounded-2xl p-3 bg-slate-50 min-h-[300px]">
                                <div class="flex-1 bg-white rounded-xl border border-slate-100"></div>
                                <div class="flex-1 bg-white rounded-xl border border-slate-100"></div>
                                <div class="flex-1 bg-white rounded-xl border border-slate-100"></div>
                            </div>
                        </div>
                        
                        {{-- Bottom Mockup --}}
                        <div class="mt-auto border-t border-slate-200">
                            <div class="h-10 bg-slate-200"></div>
                            <div class="h-20 bg-slate-50"></div>
                        </div>
                    </div>

                @elseif(request()->is('qpr/create', 'qpr/*/edit'))
                    {{-- ══ QPR FORM SKELETON ══ --}}
                    <div class="max-w-5xl mx-auto space-y-6 animate-pulse mt-4">
                        {{-- Header Outside --}}
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-200 rounded-xl"></div>
                                <div class="space-y-2">
                                    <div class="h-6 w-40 bg-slate-200 rounded-md"></div>
                                    <div class="h-3 w-32 bg-slate-100 rounded-md"></div>
                                </div>
                            </div>
                            <div class="h-8 w-24 bg-slate-200 rounded-xl"></div>
                        </div>

                        {{-- Stepper Card --}}
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex gap-4 overflow-hidden">
                            @for($i=0; $i<5; $i++)
                                <div class="flex items-center gap-3 {{ $i < 4 ? 'flex-1' : '' }}">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 shrink-0"></div>
                                    <div class="space-y-2">
                                        <div class="h-4 w-20 bg-slate-200 rounded"></div>
                                        <div class="h-2 w-16 bg-slate-100 rounded"></div>
                                    </div>
                                    @if($i < 4)
                                        <div class="flex-1 h-0.5 mx-4 bg-slate-100 rounded-full"></div>
                                    @endif
                                </div>
                            @endfor
                        </div>

                        {{-- Form Sections Card --}}
                        <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm space-y-6 min-h-[400px]">
                            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                                <div class="w-1.5 h-6 bg-slate-200 rounded-full"></div>
                                <div class="h-5 w-32 bg-slate-200 rounded"></div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2"><div class="h-3 w-16 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                <div class="space-y-2"><div class="h-3 w-20 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                <div class="space-y-2"><div class="h-3 w-24 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                <div class="space-y-2"><div class="h-3 w-16 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                <div class="space-y-2"><div class="h-3 w-24 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                                <div class="space-y-2"><div class="h-3 w-20 bg-slate-200 rounded"></div><div class="h-[52px] w-full bg-slate-100 rounded-xl"></div></div>
                            </div>
                        </div>
                    </div>

                @elseif(request()->is('qpr/*/preview'))
                    {{-- ══ QPR PREVIEW SKELETON ══ --}}
                    <div class="max-w-5xl mx-auto space-y-6 animate-pulse mt-4">
                        {{-- Header Outside --}}
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-200 rounded-xl"></div>
                                <div class="space-y-2">
                                    <div class="h-6 w-40 bg-slate-200 rounded-md"></div>
                                    <div class="h-3 w-32 bg-slate-100 rounded-md"></div>
                                </div>
                            </div>
                            <div class="h-8 w-24 bg-slate-200 rounded-xl"></div>
                        </div>

                        {{-- Main Paper Card --}}
                        <div class="bg-white rounded-[24px] border border-slate-200 flex flex-col overflow-hidden">
                            {{-- Top Header --}}
                            <div class="flex border-b border-slate-200 bg-white">
                                <div class="w-[200px] p-6 border-r border-slate-200 flex flex-col items-center justify-center">
                                    <div class="h-10 w-24 bg-slate-200 rounded-md mb-3"></div>
                                    <div class="h-2 w-32 bg-slate-100 rounded"></div>
                                </div>
                                <div class="flex-1 p-8">
                                    <div class="grid grid-cols-3 gap-6 pb-6 border-b border-slate-100">
                                        <div class="flex gap-4 items-center"><div class="w-10 h-10 bg-slate-100 rounded-xl shrink-0"></div><div class="space-y-2"><div class="h-3 w-12 bg-slate-100 rounded"></div><div class="h-4 w-24 bg-slate-200 rounded"></div></div></div>
                                        <div class="flex gap-4 items-center"><div class="w-10 h-10 bg-slate-100 rounded-xl shrink-0"></div><div class="space-y-2"><div class="h-3 w-12 bg-slate-100 rounded"></div><div class="h-4 w-24 bg-slate-200 rounded"></div></div></div>
                                        <div class="flex gap-4 items-center"><div class="w-10 h-10 bg-slate-100 rounded-xl shrink-0"></div><div class="space-y-2"><div class="h-3 w-12 bg-slate-100 rounded"></div><div class="h-4 w-24 bg-slate-200 rounded"></div></div></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-6 pt-6">
                                        <div class="flex gap-4 items-center"><div class="w-10 h-10 bg-slate-100 rounded-xl shrink-0"></div><div class="space-y-2"><div class="h-3 w-16 bg-slate-100 rounded"></div><div class="h-4 w-32 bg-slate-200 rounded"></div></div></div>
                                        <div class="flex gap-4 items-center"><div class="w-10 h-10 bg-slate-100 rounded-xl shrink-0"></div><div class="space-y-2"><div class="h-3 w-16 bg-slate-100 rounded"></div><div class="h-4 w-32 bg-slate-200 rounded"></div></div></div>
                                    </div>
                                </div>
                            </div>

                            {{-- 3 Columns --}}
                            <div class="grid grid-cols-[1fr_1.5fr_1.5fr] border-b border-slate-200 bg-white">
                                <div class="p-6 border-r border-slate-200 space-y-6">
                                    <div class="h-4 w-24 bg-slate-200 rounded"></div>
                                    <div class="h-10 w-full bg-slate-50 border border-slate-100 rounded-lg"></div>
                                    <div class="h-10 w-full bg-slate-50 border border-slate-100 rounded-lg"></div>
                                </div>
                                <div class="p-6 border-r border-slate-200 flex flex-col justify-center space-y-6">
                                    <div class="h-8 w-full bg-slate-50 border border-slate-100 rounded-lg"></div>
                                    <div class="h-8 w-full bg-slate-50 border border-slate-100 rounded-lg"></div>
                                    <div class="h-8 w-full bg-slate-50 border border-slate-100 rounded-lg"></div>
                                </div>
                                <div class="p-6 space-y-6">
                                    <div class="h-4 w-32 bg-slate-200 rounded"></div>
                                    <div class="space-y-3">
                                        <div class="flex gap-3 items-center"><div class="w-5 h-5 bg-slate-200 rounded shrink-0"></div><div class="h-4 w-24 bg-slate-100 rounded"></div></div>
                                        <div class="flex gap-3 items-center"><div class="w-5 h-5 bg-slate-200 rounded shrink-0"></div><div class="h-4 w-24 bg-slate-100 rounded"></div></div>
                                        <div class="flex gap-3 items-center"><div class="w-5 h-5 bg-slate-200 rounded shrink-0"></div><div class="h-4 w-24 bg-slate-100 rounded"></div></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sketch Placeholder --}}
                            <div class="h-[250px] w-full bg-slate-50/50 flex flex-col p-6 border-b border-slate-200">
                                <div class="h-6 w-32 bg-slate-200 rounded mb-4"></div>
                                <div class="flex-1 flex items-center justify-center">
                                    <div class="w-32 h-20 bg-slate-200 rounded-xl"></div>
                                </div>
                            </div>
                            
                            {{-- Strip Details --}}
                            <div class="flex justify-between bg-white border-b border-slate-200 h-[80px]">
                                <div class="w-1/4 border-r border-slate-200"></div>
                                <div class="w-1/4 border-r border-slate-200"></div>
                                <div class="w-1/4 border-r border-slate-200"></div>
                                <div class="w-1/4 border-r border-slate-200"></div>
                            </div>
                        </div>
                    </div>

                @elseif(request()->is('li/master-template'))
                    {{-- ══ MASTER TEMPLATE SKELETON ══ --}}
                    {{-- Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="space-y-2">
                            <div class="h-8 w-64 bg-slate-200 rounded-lg"></div>
                            <div class="h-4 w-96 bg-slate-200 rounded-full"></div>
                        </div>
                        <div class="flex gap-3">
                            <div class="h-[42px] w-40 bg-slate-200 rounded-xl"></div>
                            <div class="h-[42px] w-48 bg-slate-300 rounded-xl"></div>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="mb-5">
                        <div class="h-12 w-full bg-slate-200 rounded-xl"></div>
                    </div>

                    {{-- Filter Tabs --}}
                    <div class="flex gap-2 overflow-x-auto pb-4 mb-2 scrollbar-hide">
                        <div class="h-9 w-24 bg-slate-300 rounded-full shrink-0"></div>
                        <div class="h-9 w-20 bg-slate-200 rounded-full shrink-0"></div>
                        <div class="h-9 w-20 bg-slate-200 rounded-full shrink-0"></div>
                        <div class="h-9 w-16 bg-slate-200 rounded-full shrink-0"></div>
                        <div class="h-9 w-20 bg-slate-200 rounded-full shrink-0"></div>
                    </div>

                    {{-- Table Skeleton --}}
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden pb-4">
                        <div class="border-b border-slate-100 flex items-center px-6 py-4">
                            <div class="w-[15%] h-3 bg-slate-200 rounded"></div>
                            <div class="w-[15%] h-3 bg-slate-200 rounded ml-6"></div>
                            <div class="flex-1 h-3 bg-slate-200 rounded ml-6"></div>
                            <div class="w-[10%] h-3 bg-slate-200 rounded hidden md:block"></div>
                            <div class="w-[30%] h-3 bg-slate-200 rounded hidden lg:block ml-6"></div>
                            <div class="w-16 h-3 bg-slate-200 rounded ml-6 text-center"></div>
                        </div>
                        <div class="divide-y divide-slate-50">
                            @for($i = 0; $i < 10; $i++)
                            <div class="flex items-center px-6 py-5">
                                <div class="w-[15%] h-4 bg-slate-200 rounded-full"></div>
                                <div class="w-[15%] h-4 bg-slate-200 rounded-full ml-6"></div>
                                <div class="flex-1 h-4 bg-slate-200 rounded-full ml-6"></div>
                                <div class="w-[10%] h-6 bg-slate-200 rounded ml-6 hidden md:block"></div>
                                <div class="w-[30%] flex gap-2 ml-6 hidden lg:flex">
                                    <div class="h-6 w-20 bg-slate-200 rounded"></div>
                                    <div class="h-6 w-20 bg-slate-200 rounded"></div>
                                </div>
                                <div class="w-16 h-8 bg-slate-200 rounded-lg ml-6"></div>
                            </div>
                            @endfor
                        </div>
                    </div>

                @elseif(request()->is('rapor-qc'))
                    {{-- ══ RAPOR KINERJA QC SKELETON ══ --}}
                    <div class="max-w-6xl mx-auto pb-16 px-6">
                        {{-- Fake Header (Skeleton) --}}
                        <div class="flex items-center justify-between py-6 animate-pulse">
                            <div class="space-y-2.5">
                                <div class="h-8 w-48 bg-slate-200 rounded-lg"></div>
                                <div class="h-4 w-32 bg-slate-200 rounded-full"></div>
                            </div>
                            <div class="h-10 w-[200px] bg-slate-200 rounded-full"></div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            {{-- Skeleton Kiri (Podium & List) --}}
                            <div class="lg:col-span-2 space-y-4">
                                {{-- Skeleton Podium --}}
                                <div class="bg-slate-200 animate-pulse rounded-3xl h-72 flex items-end justify-center gap-6 px-8 pb-0 overflow-hidden border border-slate-100">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-16 h-16 bg-slate-300 rounded-full border-4 border-slate-100"></div>
                                        <div class="w-16 h-3 bg-slate-300 rounded-full mt-1"></div>
                                        <div class="w-10 h-3 bg-slate-300 rounded-full"></div>
                                        <div class="w-32 h-20 bg-slate-300/60 rounded-t-2xl mt-2 border-t border-x border-slate-300"></div>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 -mt-6">
                                        <div class="w-20 h-20 bg-slate-300 rounded-full border-4 border-slate-100"></div>
                                        <div class="w-24 h-4 bg-slate-300 rounded-full mt-1"></div>
                                        <div class="w-12 h-4 bg-slate-300 rounded-full"></div>
                                        <div class="w-32 h-28 bg-slate-300/60 rounded-t-2xl mt-2 border-t border-x border-slate-300"></div>
                                    </div>
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-16 h-16 bg-slate-300 rounded-full border-4 border-slate-100"></div>
                                        <div class="w-16 h-3 bg-slate-300 rounded-full mt-1"></div>
                                        <div class="w-10 h-3 bg-slate-300 rounded-full"></div>
                                        <div class="w-32 h-14 bg-slate-300/60 rounded-t-2xl mt-2 border-t border-x border-slate-300"></div>
                                    </div>
                                </div>

                                {{-- Skeleton List Rank --}}
                                <div class="bg-white rounded-3xl border border-slate-100 p-5 space-y-4 animate-pulse shadow-sm">
                                    <div class="flex justify-between items-center mb-2 px-1">
                                        <div class="w-32 h-2.5 bg-slate-200 rounded-full"></div>
                                        <div class="w-32 h-2.5 bg-slate-200 rounded-full"></div>
                                    </div>
                                    @for($i = 0; $i < 5; $i++)
                                        <div class="flex items-center gap-4 py-2 border-t border-slate-50">
                                            <div class="w-5 h-4 bg-slate-200 rounded"></div>
                                            <div class="w-10 h-10 bg-slate-200 rounded-full shrink-0"></div>
                                            <div class="flex-1 space-y-2">
                                                <div class="w-1/3 h-3.5 bg-slate-200 rounded-full"></div>
                                                <div class="w-1/4 h-2.5 bg-slate-200 rounded-full"></div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div class="w-8 h-4 bg-slate-200 rounded"></div>
                                                <div class="w-px h-6 bg-slate-200"></div>
                                                <div class="w-10 h-4 bg-slate-200 rounded"></div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            {{-- Skeleton Kanan (Summary Panels) --}}
                            <div class="space-y-4">
                                {{-- Skeleton Panel 1 --}}
                                <div class="bg-white border border-slate-100 rounded-3xl p-5 space-y-4 animate-pulse shadow-sm">
                                    <div class="w-24 h-2.5 bg-slate-200 rounded-full mb-4"></div>
                                    @for($i = 0; $i < 3; $i++)
                                        <div class="flex justify-between items-center py-2.5 border-b border-slate-50 last:border-0">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-slate-200 rounded-2xl shrink-0"></div>
                                                <div class="space-y-2.5">
                                                    <div class="w-20 h-2 bg-slate-200 rounded-full"></div>
                                                    <div class="w-12 h-4 bg-slate-200 rounded-full"></div>
                                                </div>
                                            </div>
                                            <div class="w-12 h-5 bg-slate-200 rounded-full"></div>
                                        </div>
                                    @endfor
                                </div>

                                {{-- Skeleton Panel 2 (Per Line) --}}
                                <div class="bg-white border border-slate-100 rounded-3xl p-5 space-y-4 animate-pulse shadow-sm">
                                    <div class="w-20 h-2.5 bg-slate-200 rounded-full mb-4"></div>
                                    @for($i = 0; $i < 4; $i++)
                                        <div class="space-y-2">
                                            <div class="flex justify-between">
                                                <div class="w-12 h-2.5 bg-slate-200 rounded-full"></div>
                                                <div class="w-8 h-2.5 bg-slate-200 rounded-full"></div>
                                            </div>
                                            <div class="w-full h-2 bg-slate-200 rounded-full"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                @else
                    {{-- ══ TABLE / GENERIC SKELETON ══ --}}
                    {{-- Top Cards Skeleton --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
                        <div class="bg-slate-200 h-[120px] rounded-[24px] w-full"></div>
                        <div class="bg-slate-200 h-[120px] rounded-[24px] w-full"></div>
                        <div class="bg-slate-200 h-[120px] rounded-[24px] w-full"></div>
                        <div class="bg-slate-200 h-[120px] rounded-[24px] w-full"></div>
                    </div>

                    {{-- Search & Action Bar Skeleton --}}
                    <div class="bg-white p-3 sm:p-4 rounded-3xl border border-slate-100 mb-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row gap-3">
                            {{-- Search input --}}
                            <div class="flex-1 h-[52px] bg-slate-200 rounded-2xl w-full"></div>
                            {{-- Buttons --}}
                            <div class="flex gap-2">
                                <div class="h-[52px] w-[52px] bg-slate-200 rounded-2xl shrink-0"></div>
                                <div class="h-[52px] w-[130px] bg-slate-200 rounded-2xl shrink-0"></div>
                            </div>
                        </div>
                        
                        {{-- Filters --}}
                        <div class="flex flex-wrap items-center gap-3 mt-4">
                            <div class="h-9 w-36 bg-slate-200 rounded-xl"></div>
                            <div class="h-9 w-48 bg-slate-200 rounded-xl"></div>
                        </div>
                    </div>

                    {{-- Tabs/Status Skeleton --}}
                    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
                        <div class="h-[38px] w-36 bg-slate-300 rounded-full shrink-0"></div>
                        <div class="h-[38px] w-28 bg-slate-200 rounded-full shrink-0"></div>
                        <div class="h-[38px] w-48 bg-slate-200 rounded-full shrink-0"></div>
                        <div class="h-[38px] w-40 bg-slate-200 rounded-full shrink-0"></div>
                        <div class="h-[38px] w-40 bg-slate-200 rounded-full shrink-0"></div>
                    </div>

                    {{-- Table/List Skeleton --}}
                    <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden">
                        {{-- Header --}}
                        <div class="flex items-center justify-between py-5 px-6 bg-slate-50 border-b border-slate-100">
                            <div class="h-3 w-16 bg-slate-300 rounded"></div>
                            <div class="h-3 w-32 bg-slate-300 rounded hidden sm:block"></div>
                            <div class="h-3 w-20 bg-slate-300 rounded hidden md:block text-center"></div>
                            <div class="h-3 w-16 bg-slate-300 rounded hidden lg:block text-center"></div>
                            <div class="h-3 w-12 bg-slate-300 rounded text-right"></div>
                        </div>
                        
                        {{-- Rows --}}
                        <div class="divide-y divide-slate-50">
                            <div class="flex items-center justify-between gap-4 px-6 py-5">
                                <div class="flex-1 max-w-[200px] space-y-2">
                                    <div class="h-4 w-full bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-2/3 bg-slate-200 rounded-full"></div>
                                </div>
                                <div class="flex-1 max-w-[300px] space-y-2 hidden sm:block">
                                    <div class="h-4 w-3/4 bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-1/2 bg-slate-200 rounded-full"></div>
                                </div>
                                <div class="h-6 w-16 bg-slate-200 rounded-full hidden md:block"></div>
                                <div class="h-6 w-20 bg-slate-200 rounded-md hidden lg:block"></div>
                                <div class="flex gap-2 justify-end min-w-[80px]">
                                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-6 py-5">
                                <div class="flex-1 max-w-[200px] space-y-2">
                                    <div class="h-4 w-5/6 bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-1/2 bg-slate-200 rounded-full"></div>
                                </div>
                                <div class="flex-1 max-w-[300px] space-y-2 hidden sm:block">
                                    <div class="h-4 w-2/3 bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-1/3 bg-slate-200 rounded-full"></div>
                                </div>
                                <div class="h-6 w-16 bg-slate-200 rounded-full hidden md:block"></div>
                                <div class="h-6 w-20 bg-slate-200 rounded-md hidden lg:block"></div>
                                <div class="flex gap-2 justify-end min-w-[80px]">
                                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-6 py-5">
                                <div class="flex-1 max-w-[200px] space-y-2">
                                    <div class="h-4 w-full bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-3/4 bg-slate-200 rounded-full"></div>
                                </div>
                                <div class="flex-1 max-w-[300px] space-y-2 hidden sm:block">
                                    <div class="h-4 w-full bg-slate-200 rounded-full"></div>
                                    <div class="h-3 w-1/2 bg-slate-200 rounded-full"></div>
                                </div>
                                <div class="h-6 w-16 bg-slate-200 rounded-full hidden md:block"></div>
                                <div class="h-6 w-20 bg-slate-200 rounded-md hidden lg:block"></div>
                                <div class="flex gap-2 justify-end min-w-[80px]">
                                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                                    <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actual Page Content --}}
            <div id="main-page-content" class="transition-opacity duration-300 w-full ease-out">
                {{ $slot }}
            </div>
        </main>
    </div>


<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>

<script>


window.hideSkeleton = function() {
    document.body.classList.remove('page-loading');
    const mainContent = document.getElementById('main-page-content');
    if(mainContent) {
        requestAnimationFrame(() => {
            mainContent.style.opacity = '1';
        });
    }
};

document.addEventListener('alpine:initialized', () => {
    // Jika tidak ada komponen yang meminta defer, langsung sembunyikan skeleton
    if (!window.deferSkeletonHide) {
        window.hideSkeleton();
    } else {
        // Fallback: Pastikan skeleton hilang maksimal dalam 3 detik (3000ms)
        // meskipun data dari API lambat atau gagal
        setTimeout(() => window.hideSkeleton(), 3000);
    }
});

window.addEventListener('page-ready', () => {
    window.hideSkeleton();
});

// Revert skeleton on bfcache restore (back button)
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.hideSkeleton();
    }
});
</script>

@stack('scripts')

{{-- RECURRING PENDING TASKS NOTIFIER (DUAL-TONE: RED HEADER + WHITE BODY) --}}
@auth
<div id="qa-global-notifier" style="position: fixed; bottom: 32px; right: -420px; z-index: 999999; transition: right 0.6s cubic-bezier(0.16, 1, 0.3, 1); pointer-events: none; width: 340px;">
    
    <div class="relative bg-white rounded-2xl w-full shadow-[0_20px_50px_-15px_rgba(225,29,72,0.3)] overflow-hidden border border-red-100 flex flex-col pointer-events-auto group">
        
        <!-- Header Merah Dominan -->
        <div class="bg-gradient-to-r from-red-600 to-rose-600 px-4 py-3 flex items-center justify-between shadow-sm relative overflow-hidden">
            <!-- Dekorasi kilauan halus di header -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>

            <div class="flex items-center gap-2.5 relative z-10">
                <div class="relative">
                    <span class="absolute inset-0 rounded-full bg-white animate-ping opacity-40 duration-1000"></span>
                    <svg class="w-4 h-4 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h4 class="font-bold text-white text-[13px] tracking-wide flex items-center gap-2">
                    PEMBERITAHUAN
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-white"></span>
                    </span>
                </h4>
            </div>
            <!-- Tombol Tutup -->
            <button onclick="dismissQaNotifier()" class="text-white/80 hover:text-white bg-black/10 hover:bg-black/20 p-1.5 rounded-full transition-colors flex-shrink-0 relative z-10">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        
        <!-- Body Putih Bersih -->
        <div class="px-4 py-3.5 bg-white relative">
            <p id="qa-notifier-msg" class="text-[12.5px] text-slate-600 leading-relaxed font-medium mb-3"></p>
            <a id="qa-notifier-link" href="/item-check" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors border border-red-100 hover:border-red-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                Lihat Detail
            </a>
            <!-- Progress Bar (Auto-Close Countdown) -->
            <div class="absolute bottom-0 left-0 h-[3.5px] bg-gradient-to-r from-red-600 to-rose-500" id="qa-notifier-progress" style="width: 100%;"></div>
        </div>
    </div>
</div>

<script>
(function() {
    const role = '{{ strtolower(trim(auth()->user()->role ?? "")) }}';
    const validRoles = ['foreman', 'supervisor', 'qc', 'leader', 'group leader'];
    if (!validRoles.includes(role)) return;

    let isShowing = false;
    let hideTimeout;

    function playDing() {
        try {
            // Mencegah error console: Jangan mainkan suara jika user belum pernah berinteraksi (klik) dengan halaman
            if (navigator.userActivation && !navigator.userActivation.hasBeenActive) {
                return;
            }

            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') ctx.resume();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.5);
            gain.gain.setValueAtTime(0.5, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.5);
        } catch(e) {}
    }

    window.dismissQaNotifier = function() {
        const el = document.getElementById('qa-global-notifier');
        const bar = document.getElementById('qa-notifier-progress');
        if(el) {
            el.style.right = '-420px';
        }
        if (bar) {
            bar.style.transition = 'none';
        }
        isShowing = false;
        clearTimeout(hideTimeout);
    };

    function showNotifier(msg) {
        const el = document.getElementById('qa-global-notifier');
        const bar = document.getElementById('qa-notifier-progress');
        if (!el) return;
        
        document.getElementById('qa-notifier-msg').innerHTML = msg;
        
        el.style.right = '32px'; 
        
        if (!isShowing) {
            playDing();
            isShowing = true;
        }

        // Animasikan Progress Bar menurun selama 30 detik
        if (bar) {
            bar.style.transition = 'none';
            bar.style.width = '100%';
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    bar.style.transition = 'width 30s linear';
                    bar.style.width = '0%';
                });
            });
        }

        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            window.dismissQaNotifier();
        }, 30000); 
    }

    async function checkPending() {
        try {
            let items = [];
            if (window.axios) {
                const res = await window.axios.get('/api/inspeksi?per_page=100');
                items = res.data?.data || res.data || [];
            } else {
                const res = await fetch('/api/inspeksi?per_page=100', { headers: { 'Accept': 'application/json' }});
                const data = await res.json();
                items = data.data || data || [];
            }
            
            let pendingCount = 0;
            let actionText = 'diproses';
            let detailLink = '/item-check';
            
            if (role === 'group leader') {
                pendingCount = items.filter(i => i.status === 'draft' || i.status === 'revision').length;
                actionText = 'diperbaiki/dilanjutkan (Revisi ke Leader)';
                detailLink = '/item-check';
            } else if (role === 'foreman') {
                pendingCount = items.filter(i => i.status === 'waiting_foreman').length;
                actionText = 'diperiksa (Butuh Checked dari Foreman)';
                detailLink = '/item-check';
            } else if (role === 'supervisor') {
                pendingCount = items.filter(i => i.status === 'waiting_supervisor').length;
                actionText = 'disetujui (Butuh Approval dari SPV)';
                detailLink = '/item-check';
            } else if (role === 'qc') {
                pendingCount = items.filter(i => i.status === 'locked' || i.status === 'ready_for_qc' || i.status === 'waiting_qc_approval').length;
                actionText = 'diinspeksi aktual (Harus diperiksa QC)';
                detailLink = '/qc/worklist';
            } else if (role === 'leader') {
                pendingCount = items.filter(i => i.status === 'waiting_qc_approval' || i.status === 'waiting_verification').length;
                actionText = 'diverifikasi (Approval QC Leader)';
                detailLink = '/item-check';
            }
            
            if (pendingCount > 0) {
                const link = document.getElementById('qa-notifier-link');
                if (link) link.href = detailLink;
                showNotifier(`Ada <b class="text-red-600">${pendingCount} Lembar Inspeksi</b> yang butuh ${actionText} segera.`);
            } else {
                window.dismissQaNotifier();
            }
        } catch(e) {}
    }

    setTimeout(checkPending, 3000);
    
    setInterval(() => {
        if (!isShowing) checkPending();
    }, 35000);

})();
</script>
@endauth

{{-- ======================================================================================== --}}
{{-- PENDING APPROVAL NOTIFICATION MODAL (PREMIUM)                                            --}}
{{-- ======================================================================================== --}}
@auth
<style>
    @keyframes notif-float-1 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(12px,-12px) scale(1.1)} }
    @keyframes notif-float-2 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-10px,10px) scale(1.15)} }
    @keyframes notif-bell-ring { 0%,100%{transform:rotate(0)} 20%{transform:rotate(-12deg)} 40%{transform:rotate(10deg)} 60%{transform:rotate(-8deg)} 80%{transform:rotate(6deg)} }
    .notif-blob-a { animation: notif-float-1 5s ease-in-out infinite; }
    .notif-blob-b { animation: notif-float-2 6s ease-in-out infinite 1.5s; }
    .notif-bell   { animation: notif-bell-ring 2s ease-in-out 0.5s; }
    .notif-ping   { animation: ping 1.8s cubic-bezier(0,0,.2,1) infinite; }
    .notif-card-hover { transition: all 0.2s ease; }
    .notif-card-hover:hover { transform: translateY(-2px); }
</style>

<div x-data="{
        showPopup: false,
        closing: false,
        qprCount: 0,
        liCount: 0,
        icCount: 0,
        firstIcId: null,
        closePopup() {
            this.closing = true;
            setTimeout(() => { this.showPopup = false; this.closing = false; }, 250);
        }
     }"
     x-on:show-priority-popup.window="qprCount = $event.detail.qprCount; liCount = $event.detail.liCount; icCount = $event.detail.icCount; firstIcId = ($event.detail.ics && $event.detail.ics.length > 0) ? $event.detail.ics[0].id : null; showPopup = true"
     x-show="showPopup"
     x-cloak
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" @click="closePopup()"></div>

    {{-- Modal --}}
    <div x-show="showPopup"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         :class="closing ? 'pointer-events-none' : ''"
         class="relative w-full max-w-md bg-white rounded-3xl overflow-hidden"
         style="box-shadow: 0 30px 80px -12px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);">

        {{-- HEADER --}}
        <div class="relative overflow-hidden flex flex-col items-center justify-center text-center"
             style="min-height:200px; padding: 2.5rem 2rem 3rem; background: linear-gradient(160deg, #0f172a 0%, #1e1035 50%, #0f172a 100%);">

            {{-- Background blobs --}}
            <div class="notif-blob-a absolute rounded-full pointer-events-none"
                 style="width:240px;height:240px;top:-60px;left:-60px;background:radial-gradient(circle,rgba(244,63,94,0.35) 0%,transparent 70%);"></div>
            <div class="notif-blob-b absolute rounded-full pointer-events-none"
                 style="width:200px;height:200px;bottom:-40px;right:-40px;background:radial-gradient(circle,rgba(249,115,22,0.3) 0%,transparent 70%);"></div>
            <div class="absolute inset-0 pointer-events-none"
                 style="background:radial-gradient(ellipse at 50% 100%,rgba(139,92,246,0.12) 0%,transparent 60%);"></div>

            {{-- Close --}}
            <button @click="closePopup()"
                    class="absolute top-4 right-4 w-9 h-9 rounded-full flex items-center justify-center text-white/40 hover:text-white hover:bg-white/10 transition-all"
                    style="z-index:10;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Bell icon --}}
            <div class="relative mb-5" style="z-index:10;">
                <span class="notif-ping absolute inset-0 rounded-2xl"
                      style="background:rgba(244,63,94,0.4);"></span>
                <div class="notif-bell relative w-[68px] h-[68px] rounded-2xl flex items-center justify-center"
                     style="background:linear-gradient(135deg,#f43f5e,#f97316);box-shadow:0 12px 30px -8px rgba(244,63,94,0.7);">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
            </div>

            {{-- Text --}}
            <div style="z-index:10; position:relative;">
                <p style="font-size:10px;font-weight:900;letter-spacing:.18em;color:#fb7185;text-transform:uppercase;margin-bottom:6px;">
                    &#9889; Tindakan Diperlukan
                </p>
                <h2 style="font-size:24px;font-weight:900;color:#ffffff;line-height:1.2;margin:0 0 6px;">
                    Prioritas Hari Ini
                </h2>
                <p style="font-size:13px;color:#94a3b8;margin:0;">
                    Ada dokumen yang menunggu Anda
                </p>
            </div>
        </div>

        {{-- BODY --}}
        <div class="bg-white px-5 pt-5 space-y-3">

            {{-- QPR --}}
            <template x-if="qprCount > 0">
                <a href="{{ url('/qpr') }}" class="notif-card-hover flex items-center gap-4 p-4 rounded-2xl border cursor-pointer"
                   style="border-color:#fde68a;background:linear-gradient(135deg,#fffbeb,#fff7ed);box-shadow:0 4px 16px -6px rgba(245,158,11,0.25);">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white"
                         style="background:linear-gradient(135deg,#f59e0b,#ea580c);box-shadow:0 6px 16px -4px rgba(245,158,11,0.55);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                            <span style="font-size:13px;font-weight:800;color:#1e293b;">Quality Problem Report</span>
                            <span x-text="qprCount + ' QPR'"
                                  style="font-size:10px;font-weight:900;color:#fff;background:#f59e0b;padding:2px 8px;border-radius:999px;"></span>
                        </div>
                        <p style="font-size:11px;color:#78716c;">Laporan butuh verifikasi &amp; approval Anda</p>
                    </div>
                    <svg class="w-4 h-4 shrink-0" style="color:#d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </template>

            {{-- LI --}}
            <template x-if="liCount > 0">
                <a href="{{ url('/li/master-template') }}" class="notif-card-hover flex items-center gap-4 p-4 rounded-2xl border cursor-pointer"
                   style="border-color:#fecdd3;background:linear-gradient(135deg,#fff1f2,#fdf4ff);box-shadow:0 4px 16px -6px rgba(244,63,94,0.2);">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white"
                         style="background:linear-gradient(135deg,#f43f5e,#db2777);box-shadow:0 6px 16px -4px rgba(244,63,94,0.5);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                            <span style="font-size:13px;font-weight:800;color:#1e293b;">Lembar Inspeksi</span>
                            <span x-text="liCount + ' LI'"
                                  style="font-size:10px;font-weight:900;color:#fff;background:#f43f5e;padding:2px 8px;border-radius:999px;"></span>
                        </div>
                        <p style="font-size:11px;color:#78716c;">Dokumen butuh Tanda Tangan Anda</p>
                    </div>
                    <svg class="w-4 h-4 shrink-0" style="color:#e11d48;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </template>

            {{-- ITEM CHECK --}}
            <template x-if="icCount > 0">
                <a :href="firstIcId ? '/item-check/' + firstIcId + '/form' : '{{ url('/item-check') }}'" class="notif-card-hover flex items-center gap-4 p-4 rounded-2xl border cursor-pointer"
                   style="border-color:#c7d2fe;background:linear-gradient(135deg,#eef2ff,#faf5ff);box-shadow:0 4px 16px -6px rgba(99,102,241,0.2);">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-white"
                         style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 6px 16px -4px rgba(99,102,241,0.5);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                            <span style="font-size:13px;font-weight:800;color:#1e293b;">Item Check (Harian)</span>
                            <span x-text="icCount + ' Item Check'"
                                  style="font-size:10px;font-weight:900;color:#fff;background:#6366f1;padding:2px 8px;border-radius:999px;"></span>
                        </div>
                        @if(auth()->user()->role === 'Operator')
                            <p style="font-size:11px;color:#ef4444;font-weight:600;">Dokumen ditolak GL, butuh Revisi Anda</p>
                        @else
                            <p style="font-size:11px;color:#78716c;">Dokumen butuh Verifikasi Anda</p>
                        @endif
                    </div>
                    <svg class="w-4 h-4 shrink-0" style="color:#4f46e5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </template>
        </div>

        {{-- FOOTER --}}
        <div class="px-5 py-5 flex gap-3">
            <button @click="closePopup()"
                    class="flex-1 rounded-2xl font-bold text-sm border transition-all hover:bg-slate-50"
                    style="padding:14px;color:#94a3b8;border-color:#e2e8f0;">
                Nanti Saja
            </button>
            <template x-if="qprCount > 0">
                <a href="{{ url('/qpr') }}" @click="closePopup()"
                   class="flex-[2] rounded-2xl font-black text-sm text-white flex items-center justify-center gap-2 transition-all hover:opacity-90 active:scale-[.98]"
                   style="padding:14px;background:linear-gradient(135deg,#dc2626,#ea580c);box-shadow:0 8px 24px -6px rgba(220,38,38,0.5);">
                    Proses Sekarang
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </template>
            <template x-if="qprCount === 0 && liCount > 0">
                <a href="{{ url('/li/master-template') }}" @click="closePopup()"
                   class="flex-[2] rounded-2xl font-black text-sm text-white flex items-center justify-center gap-2 transition-all hover:opacity-90 active:scale-[.98]"
                   style="padding:14px;background:linear-gradient(135deg,#dc2626,#db2777);box-shadow:0 8px 24px -6px rgba(220,38,38,0.5);">
                    Proses Sekarang
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </template>
            <template x-if="qprCount === 0 && liCount === 0 && icCount > 0">
                <a :href="firstIcId ? '/item-check/' + firstIcId + '/form' : '{{ url('/item-check') }}'" @click="closePopup()"
                   class="flex-[2] rounded-2xl font-black text-sm text-white flex items-center justify-center gap-2 transition-all hover:opacity-90 active:scale-[.98]"
                   style="padding:14px;background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 8px 24px -6px rgba(79,70,229,0.5);">
                    Proses Sekarang
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </template>
        </div>
    </div>
</div>
@endauth

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- GLOBAL INTERCOM OVERLAY — GL / FOREMAN (ALL PAGES)                 --}}
{{-- Overlay ini TIDAK bisa ditutup dari device GL/Foreman sendiri.      --}}
{{-- Hanya bisa padam setelah GL check-in fisik di tablet operator.     --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div x-show="showIntercomOverlay" x-cloak
     style="display:none;"
     class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-red-950 overflow-hidden">

    {{-- Animated radial pulse rings --}}
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="w-[700px] h-[700px] rounded-full border border-red-600/20 animate-ping" style="animation-duration:2s;"></div>
        <div class="w-[500px] h-[500px] rounded-full border border-red-500/20 animate-ping absolute" style="animation-duration:1.5s;animation-delay:.3s"></div>
        <div class="w-[300px] h-[300px] rounded-full border border-red-400/30 animate-ping absolute" style="animation-duration:1.2s;animation-delay:.6s"></div>
    </div>

    <div class="relative z-10 flex flex-col items-center text-center px-4 md:px-6 max-w-lg w-full">

        {{-- Icon --}}
        <div class="relative mb-3 md:mb-4">
            <div class="absolute inset-0 bg-red-500/30 rounded-full animate-ping scale-[1.3]"></div>
            <div class="w-16 h-16 md:w-20 md:h-20 bg-red-600 rounded-full flex items-center justify-center shadow-2xl shadow-red-500/60 relative z-10">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
        </div>

        {{-- Header --}}
        <p class="text-[9px] md:text-[10px] font-black text-red-400 uppercase tracking-[3px] md:tracking-[4px] animate-pulse mb-1">🚨 PANGGILAN DARURAT JALUR</p>
        <h1 class="text-2xl md:text-3xl font-black text-white leading-tight mb-1">OPERATOR BUTUH<br>BANTUAN ANDA!</h1>
        <p class="text-[10px] md:text-xs text-red-300/80 font-bold">Segera pergi ke jalur di bawah ini</p>

        {{-- Info Box --}}
        <div class="mt-3 md:mt-5 w-full bg-red-900/40 border border-red-700/50 rounded-2xl p-4 text-left space-y-2 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <span class="text-[8px] md:text-[9px] font-black text-red-400 uppercase tracking-widest">LINE / LOKASI</span>
                <span class="text-sm md:text-base font-black text-white uppercase animate-pulse" x-text="intercomAlert?.lembar_inspeksi?.lokasi || '—'"></span>
            </div>
            <div class="border-t border-red-800/50 pt-2 flex items-center justify-between">
                <span class="text-[8px] md:text-[9px] font-black text-red-400 uppercase tracking-widest">PART NAME</span>
                <span class="text-[10px] md:text-xs font-bold text-red-100 text-right truncate max-w-[200px]" x-text="intercomAlert?.lembar_inspeksi?.part_name || '—'"></span>
            </div>
            <div class="border-t border-red-800/50 pt-2 flex items-center justify-between">
                <span class="text-[8px] md:text-[9px] font-black text-red-400 uppercase tracking-widest">JOB NO</span>
                <span class="text-[10px] md:text-xs font-bold text-red-100" x-text="intercomAlert?.lembar_inspeksi?.job_no || '—'"></span>
            </div>
        </div>

        {{-- Notice: cannot dismiss --}}
        <div class="mt-3 md:mt-4 px-4 py-2 bg-red-900/30 border border-red-700/40 rounded-xl">
            <p class="text-[8px] md:text-[9px] text-red-300 font-bold leading-tight">
                ⚠️ Notifikasi ini <span class="text-white">hanya bisa dimatikan di tablet operator</span> setelah Anda tiba di lapangan.
            </p>
        </div>

        {{-- Response buttons (only sends status, doesn't close overlay) --}}
        <div class="mt-4 md:mt-5 grid grid-cols-2 gap-3 w-full">
            <button type="button" @click="respondIntercom('decline')"
                    class="py-3 bg-slate-800/80 hover:bg-slate-700 text-slate-300 rounded-xl text-[9px] md:text-[10px] font-black uppercase tracking-widest transition-all active:scale-95 backdrop-blur border border-slate-700">
                ✕ Tidak Hadir
            </button>
            <button type="button" @click="respondIntercom('accept')"
                    class="py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-[9px] md:text-[10px] font-black uppercase tracking-widest transition-all active:scale-95 shadow-lg shadow-emerald-700/40">
                MENUJU KE JALUR
            </button>
        </div>
        <p class="mt-2 md:mt-3 text-[7px] md:text-[8px] text-red-500/60 font-bold">Layar ini tidak dapat ditutup dari perangkat Anda.</p>
    </div>
</div>

</div>
</body>
</html>

