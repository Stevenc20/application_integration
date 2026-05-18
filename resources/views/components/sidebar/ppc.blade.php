@php
    $dashboardActive = request()->routeIs('ppc.dashboard');
    $monitoringActive = request()->routeIs('monitoring.*') || request()->routeIs('supervisor.downtime.monitoring');
    $planActive = request()->routeIs('ppc.planning.*') || request()->routeIs('master.job');
    $reportActive = request()->routeIs('supervisor.reports.*');

    // Arrow SVG helper
    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard -->
    <li class="menu-item">
        <a href="{{ route('ppc.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard</span>
        </a>
    </li>

    <!-- Manufacturing Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Manufacturing</span>
    </li>

    <!-- Monitoring -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $monitoringActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $monitoringActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span class="font-medium">Monitoring</span>
            </div>
            {!! sprintf($arrow, $monitoringActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $monitoringActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('monitoring.line') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('monitoring.line') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Line Monitoring</a></li>
            <li><a href="{{ route('monitoring.machine_status') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('monitoring.machine_status') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Machine Status</a></li>
            <li><a href="{{ route('supervisor.downtime.monitoring') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.downtime.monitoring') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Downtime Aktif</a></li>
        </ul>
    </li>

    <!-- Management & Analytics Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Management & Analytics</span>
    </li>

    <!-- Planning -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $planActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $planActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="font-medium">Planning</span>
            </div>
            {!! sprintf($arrow, $planActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $planActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('ppc.planning.production_plan') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('ppc.planning.production_plan') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Production Plan</a></li>
            <li><a href="{{ route('master.job') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('master.job') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Job Master</a></li>
        </ul>
    </li>

    <!-- Reports -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $reportActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $reportActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="font-medium">Reports</span>
            </div>
            {!! sprintf($arrow, $reportActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $reportActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('supervisor.reports.daily_production') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.daily_production') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Daily Production</a></li>
            <li><a href="{{ route('supervisor.reports.performance') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.performance') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Performance Report</a></li>
        </ul>
    </li>
</ul>
