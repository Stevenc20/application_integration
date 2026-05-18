@php
    $dashboardActive = request()->routeIs('board.dashboard');
    $monitoringActive = request()->routeIs('monitoring.*') || request()->routeIs('supervisor.downtime.monitoring');
    $planActive = request()->routeIs('supervisor.planning.production_line') || request()->routeIs('ppc.planning.production_plan');
    $productionActive = request()->routeIs('production_entry') || request()->routeIs('production_recap') || request()->routeIs('job');
    $approvalActive = request()->routeIs('supervisor.approval.production') || request()->routeIs('supervisor.approval.quality');
    $qualityActive = request()->routeIs('supervisor.quality.*');
    $downtimeActive = request()->routeIs('supervisor.downtime.*') || request()->routeIs('supervisor.downtime.tren');
    $reportActive = request()->routeIs('supervisor.reports.*');

    $operasionalActive =
        request()->routeIs('operational.input_harian') ||
        request()->routeIs('operational.dandori') ||
        request()->routeIs('supervisor.idletime.index') ||
        request()->routeIs('supervisor.breaktime.index') ||
        request()->routeIs('supervisor.handwork.index') ||
        request()->routeIs('supervisor.qcheck.index');

    $grafikActive =
        request()->routeIs('supervisor.grafik.output_line') ||
        request()->routeIs('grafik.quality') ||
        request()->routeIs('supervisor.grafik.downtime_item') ||
        request()->routeIs('supervisor.grafik.downtime_type') ||
        request()->routeIs('supervisor.grafik.downtime_machine');

    // Arrow SVG helper
    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard -->
    <li class="menu-item">
        <a href="{{ route('board.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
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

    <!-- Operational -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $operasionalActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $operasionalActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="font-medium">Operational</span>
            </div>
            {!! sprintf($arrow, $operasionalActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $operasionalActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('operational.input_harian') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.input_harian') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Input Harian</a></li>
            <li><a href="{{ route('operational.dandori') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('operational.dandori') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Data Dandori</a></li>
            <li><a href="{{ route('supervisor.idletime.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.idletime.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Idle Time</a></li>
            <li><a href="{{ route('supervisor.breaktime.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.breaktime.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Break Time</a></li>
            <li><a href="{{ route('supervisor.handwork.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.handwork.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Handwork</a></li>
            <li><a href="{{ route('supervisor.qcheck.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.qcheck.index') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Q-Check</a></li>
        </ul>
    </li>

    <!-- Grafik -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $grafikActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $grafikActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <span class="font-medium">Grafik</span>
            </div>
            {!! sprintf($arrow, $grafikActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $grafikActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('supervisor.grafik.output_line') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.grafik.output_line') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Tren Output</a></li>
            <li><a href="{{ route('grafik.quality') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('grafik.quality') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Pencapaian Kualitas</a></li>
            <li><a href="{{ route('supervisor.grafik.downtime_item') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.grafik.downtime_item') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Tren Downtime</a></li>
            <li><a href="{{ route('supervisor.grafik.downtime_type') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.grafik.downtime_type') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Downtime per Tipe</a></li>
            <li><a href="{{ route('supervisor.grafik.downtime_machine') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.grafik.downtime_machine') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Downtime Mesin</a></li>
        </ul>
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
            <li><a href="{{ route('supervisor.planning.production_line') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.planning.production_line') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Production Line</a></li>
            <li><a href="{{ route('ppc.planning.production_plan') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('ppc.planning.production_plan') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Production Plan</a></li>
        </ul>
    </li>

    <!-- Production -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $productionActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $productionActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span class="font-medium">Production</span>
            </div>
            {!! sprintf($arrow, $productionActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $productionActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('production_entry') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('production_entry') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Production Data</a></li>
            <li><a href="{{ route('production_recap') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('production_recap') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Production Recap</a></li>
        </ul>
    </li>

    <!-- Approval -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $approvalActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $approvalActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">Approval</span>
            </div>
            {!! sprintf($arrow, $approvalActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $approvalActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('supervisor.approval.production') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.approval.production') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Production Approval</a></li>
            <li><a href="{{ route('supervisor.approval.quality') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.approval.quality') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Quality Approval</a></li>
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
            <li><a href="{{ route('supervisor.quality.defect_monitoring') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.quality.defect_monitoring') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Defect Monitoring</a></li>
            <li><a href="{{ route('supervisor.quality.reject_analysis') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.quality.reject_analysis') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Reject Analysis</a></li>
        </ul>
    </li>

    <!-- Downtime Control -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $downtimeActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $downtimeActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">Downtime Control</span>
            </div>
            {!! sprintf($arrow, $downtimeActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $downtimeActive ? '' : 'hidden' }} menu-sub">
            <li><a href="{{ route('supervisor.downtime.tren') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.downtime.tren') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Tren Downtime</a></li>
            <li><a href="{{ route('supervisor.downtime.history') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('supervisor.downtime.history') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Trouble History</a></li>
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

