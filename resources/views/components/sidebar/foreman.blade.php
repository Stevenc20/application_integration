@php
    $dashboardActive = request()->routeIs('foreman.dashboard') || request()->routeIs('supervisor.dashboard');
    $inputHarianActive = request()->routeIs('operational.input_harian');
    
    $operasionalActive =
        request()->routeIs('production_entry') ||
        request()->routeIs('supervisor.downtime.monitoring') ||
        request()->routeIs('supervisor.downtime.history') ||
        request()->routeIs('operational.dandori') ||
        request()->routeIs('operational.idle') ||
        request()->routeIs('operational.break') ||
        request()->routeIs('operational.handwork') ||
        request()->routeIs('operational.qcheck');

    $reportActive = request()->routeIs('supervisor.reports.*');

    // Arrow SVG helper
    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';

    // Fetch dynamic lines for Dashboard submenu (exactly like supervisor.blade.php)
    $sidebarLines = \App\Models\LineMaster::where('status', 'active')->select('line_name')->distinct()->get();
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
            <li>
                <a href="{{ route('foreman.dashboard') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('foreman.dashboard') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    Dashboard Overview
                </a>
            </li>
            @foreach($sidebarLines as $sl)
                @php
                    $isLineActive = request()->routeIs('supervisor.dashboard') && request()->query('line') === $sl->line_name;
                @endphp
                <li>
                    <a href="{{ route('supervisor.dashboard', ['line' => $sl->line_name]) }}" class="block px-3 py-2 text-sm rounded-lg transition {{ $isLineActive ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                        Dashboard {{ $sl->line_name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </li>

    <!-- Production Entry Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Production Entry</span>
    </li>

    <!-- Input Harian -->
    <li class="menu-item">
        <a href="{{ route('operational.input_harian') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $inputHarianActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $inputHarianActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span class="font-semibold tracking-wide">Input Harian</span>
        </a>
    </li>

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
            <li><a href="{{ route('production_entry') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('production_entry') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data Job</a></li>
            <li><a href="{{ route('supervisor.downtime.monitoring') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.downtime.monitoring') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Downtime Aktif</a></li>
            <li><a href="{{ route('supervisor.downtime.history') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.downtime.history') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Trouble History</a></li>
            <li><a href="{{ route('operational.dandori') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.dandori') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data Dandori</a></li>
            <li><a href="{{ route('operational.idle') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.idle') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data IdleTime</a></li>
            <li><a href="{{ route('operational.break') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.break') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data BreakTime</a></li>
            <li><a href="{{ route('operational.handwork') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.handwork') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data Handwork</a></li>
            <li><a href="{{ route('operational.qcheck') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.qcheck') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data Q-Check</a></li>
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
            <li><a href="{{ route('supervisor.reports.daily_production') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.daily_production') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Laporan Kerja Harian</a></li>
            <li><a href="{{ route('supervisor.reports.performance') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.performance') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Laporan Performance</a></li>
        </ul>
    </li>
</ul>
