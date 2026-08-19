@php
    $dashboardActive = (request()->routeIs('supervisor.dashboard') || request()->routeIs('supervisor.overview')) && !request('line');
    $lineDashboardActive = request()->routeIs('supervisor.dashboard') && request('line');
    $inputHarianActive = request()->routeIs('operational.input_harian');
    
    $operasionalActive =
        request()->routeIs('downtime.history') ||
        request()->routeIs('operational.dandori') ||
        request()->routeIs('supervisor.job.index') ||
        request()->routeIs('supervisor.breaktime.index') ||
        request()->routeIs('supervisor.handwork.index') ||
        request()->routeIs('supervisor.qcheck.index') ||
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

        </ul>
    </li>
</ul>
