@php
    $dashboardActive = request()->routeIs('supervisor.dashboard') || request()->routeIs('foreman.dashboard');
    $inputHarianActive = request()->routeIs('operational.input_harian');
    
    $operasionalActive =
        request()->routeIs('downtime.history') ||
        request()->routeIs('operational.dandori') ||
        request()->routeIs('supervisor.job.index') ||
        request()->routeIs('supervisor.breaktime.index') ||
        request()->routeIs('supervisor.handwork.index') ||
        request()->routeIs('supervisor.qcheck.index') ||
        request()->routeIs('monitoring.history') ||
        request()->routeIs('operational.repair_reject.index');

    $reportActive = request()->routeIs('supervisor.reports.*');

    // Arrow SVG helper
    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';

    // Fetch dynamic lines for Dashboard submenu (cached 5 min)
    $sidebarLines = \Cache::remember('sidebar_active_lines', 300, function() {
        return \App\Models\LineMaster::where('status', 'active')->select('line_name')->distinct()->get();
    });
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard Header -->
    <li class="px-4 mt-2 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dashboard System</span>
    </li>

    <!-- Dashboard Group Dropdown -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="font-semibold tracking-wide">Dashboards</span>
            </div>
            {!! sprintf($arrow, $dashboardActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $dashboardActive ? '' : 'hidden' }} menu-sub">
            @if(auth()->user()->hasFeature('dashboard'))
            <li>
                <a href="{{ route('supervisor.dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.dashboard') && !request()->has('line') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard Overview</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('dashboard'))
            @foreach($sidebarLines as $sl)
                @php
                    $isLineActive = request()->routeIs('supervisor.dashboard') && request()->query('line') === $sl->line_name;
                @endphp
                <li>
                    <a href="{{ route('supervisor.dashboard', ['line' => $sl->line_name]) }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ $isLineActive ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Dashboard {{ $sl->line_name }}</span>
                    </a>
                </li>
            @endforeach
            @endif
        </ul>
    </li>

    <!-- Production Entry Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Production Entry</span>
    </li>

    <!-- Input Harian -->
    @if(auth()->user()->hasFeature('input_harian'))
    <li class="menu-item">
        <a href="{{ route('operational.input_harian') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $inputHarianActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $inputHarianActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span class="font-semibold tracking-wide">Input Harian</span>
        </a>
    </li>
    @endif

    <!-- Data Operasional Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Data Operasional</span>
    </li>

    <!-- Operational Dropdown -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $operasionalActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $operasionalActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="font-medium">Data Operasional</span>
            </div>
            {!! sprintf($arrow, $operasionalActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $operasionalActive ? '' : 'hidden' }} menu-sub">
            @if(auth()->user()->hasFeature('data_job'))
            <li>
                <a href="{{ route('supervisor.job.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.job.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <span>Data Job</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('trouble_history'))
            <li>
                <a href="{{ route('downtime.history') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('downtime.history') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Trouble History</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('dandori'))
            <li>
                <a href="{{ route('operational.dandori') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.dandori') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Data Dandori</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('breaktime'))
            <li>
                <a href="{{ route('supervisor.breaktime.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.breaktime.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span>Data BreakTime</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('handwork'))
            <li>
                <a href="{{ route('supervisor.handwork.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.handwork.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                    <span>Data Handwork</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('qcheck'))
            <li>
                <a href="{{ route('supervisor.qcheck.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.qcheck.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Data Q-Check</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('line_monitoring'))
            <li>
                <a href="{{ route('monitoring.history', ['type' => 'downtime']) }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('monitoring.history') && request('type') == 'downtime' ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>History Downtime</span>
                </a>
            </li>
            <li>
                <a href="{{ route('monitoring.history', ['type' => 'break']) }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('monitoring.history') && request('type') == 'break' ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span>History Break Time</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('repair_reject'))
            <li>
                <a href="{{ route('operational.repair_reject.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.repair_reject.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                    <span>History Repair & Reject</span>
                </a>
            </li>
            @endif
        </ul>
    </li>



    <!-- Laporan Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Reporting System</span>
    </li>

    <!-- Reports Dropdown -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $reportActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $reportActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-medium">Laporan</span>
            </div>
            {!! sprintf($arrow, $reportActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $reportActive ? '' : 'hidden' }} menu-sub">
            @if(auth()->user()->hasFeature('daily_report'))
            <li>
                <a href="{{ route('supervisor.reports.daily_production') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.daily_production') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>LKH</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('performance_report'))
            <li>
                <a href="{{ route('supervisor.reports.performance') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.performance') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <span>Laporan Performance</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
</ul>
