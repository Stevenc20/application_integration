{{-- QA Sidebar Cache Buster --}}
@php
    $dashboardActive = request()->routeIs('quality.dashboard');
    $qualityActive = request()->routeIs('quality.defect_monitoring') || request()->routeIs('quality.reject_analysis') || request()->routeIs('qa.*');

    // Arrow SVG helper
    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard -->
    <li class="menu-item">
        <a href="{{ route('quality.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard</span>
        </a>
    </li>

    <!-- Quality Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Quality Management</span>
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
            <li><a href="{{ route('quality.defect_monitoring') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('quality.defect_monitoring') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Defect Monitoring</a></li>
            <li><a href="{{ route('quality.reject_analysis') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('quality.reject_analysis') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Reject Analysis</a></li>
            <li><a href="{{ route('qa.li.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('qa.li.*') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Lembar Inspeksi</a></li>
            <li><a href="{{ route('qa.qpr.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('qa.qpr.*') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">QPR</a></li>
            <li><a href="{{ route('qa.item-check.index') }}" class="block px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('qa.item-check.*') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">Item Check</a></li>
        </ul>
    </li>

</ul>

