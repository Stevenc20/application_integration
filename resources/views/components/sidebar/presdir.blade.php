@php
    $dashboardActive = request()->routeIs('presdir.dashboard');
    $monitoringActive = request()->routeIs('monitoring.*');
    $recapActive = request()->routeIs('production_recap');
    $reportActive = request()->routeIs('supervisor.reports.*');
    $qualityActive = request()->routeIs('quality.*');

    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard -->
    <li class="menu-item">
        <a href="{{ route('presdir.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard</span>
        </a>
    </li>

    <!-- Strategic Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Strategic</span>
    </li>

    <!-- Line Monitoring -->
    <li class="menu-item">
        <a href="{{ route('monitoring.line') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('monitoring.line') ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('monitoring.line') ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span class="font-semibold tracking-wide">Line Monitoring</span>
        </a>
    </li>

    <!-- Production Recap -->
    <li class="menu-item">
        <a href="{{ route('production_recap') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $recapActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $recapActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-semibold tracking-wide">Production Recap</span>
        </a>
    </li>

    <!-- Grafik -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('grafik.quality') || request()->routeIs('supervisor.grafik.*') ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('grafik.quality') || request()->routeIs('supervisor.grafik.*') ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <span class="font-medium">Grafik</span>
            </div>
            {!! sprintf($arrow, request()->routeIs('grafik.quality') || request()->routeIs('supervisor.grafik.*') ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ request()->routeIs('grafik.quality') || request()->routeIs('supervisor.grafik.*') ? '' : 'hidden' }} menu-sub">
            <li>
                <a href="{{ route('grafik.quality') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('grafik.quality') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    <span>Pencapaian Kualitas</span>
                </a>
            </li>
            <li>
                <a href="{{ route('supervisor.grafik.downtime_item') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.grafik.downtime_item') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    <span>Tren Downtime</span>
                </a>
            </li>
        </ul>
    </li>

    <!-- Quality Control -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $qualityActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $qualityActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span class="font-medium">Quality Control</span>
            </div>
            {!! sprintf($arrow, $qualityActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $qualityActive ? '' : 'hidden' }} menu-sub">
            @if(auth()->user()->hasFeature('quality_control_defect'))
            <li>
    <a href="{{ route('quality.defect_monitoring') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('quality.defect_monitoring') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <span>Defect Monitoring</span>
    </a>
</li>
            @endif
            @if(auth()->user()->hasFeature('quality_control_reject'))
            <li>
    <a href="{{ route('quality.reject_analysis') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('quality.reject_analysis') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span>Reject Analysis</span>
    </a>
</li>
            @endif
        </ul>
    </li>

    <!-- Reporting Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Reporting</span>
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
            <li>
                <a href="{{ route('supervisor.reports.daily_production') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.daily_production') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>LKH</span>
                </a>
            </li>
            <li>
                <a href="{{ route('supervisor.reports.performance') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.reports.performance') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <span>Performance</span>
                </a>
            </li>
        </ul>
    </li>

</ul>
