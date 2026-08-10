{{-- QA Sidebar Cache Buster --}}
@php
    $itemActive = function ($route) {
        return request()->routeIs($route);
    };

    $itemClass = function ($active) {
        return $active
            ? 'bg-primary-red text-white shadow-md shadow-red-200'
            : 'text-gray-600 hover:bg-red-50 hover:text-primary-red';
    };

    $iconClass = function ($active) {
        return $active ? 'text-white' : 'text-gray-400';
    };
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard -->
    <li class="menu-item">
        <a href="{{ route('quality.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('quality.dashboard')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('quality.dashboard')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard</span>
        </a>
    </li>

    <!-- Quality Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Quality Management</span>
    </li>

    <!-- Defect Monitoring -->
    <li class="menu-item">
        <a href="{{ route('quality.defect_monitoring') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('quality.defect_monitoring')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('quality.defect_monitoring')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="font-semibold tracking-wide">Defect Monitoring</span>
        </a>
    </li>

    <!-- Reject Analysis -->
    <li class="menu-item">
        <a href="{{ route('quality.reject_analysis') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('quality.reject_analysis')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('quality.reject_analysis')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 10h18M3 14h18M3 18h12"/>
            </svg>
            <span class="font-semibold tracking-wide">Reject Analysis</span>
        </a>
    </li>

    <!-- Lembar Inspeksi -->
    <li class="menu-item">
        <a href="{{ route('qa.li.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('qa.li.index')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('qa.li.index')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-semibold tracking-wide">Lembar Inspeksi</span>
        </a>
    </li>

    <!-- Master Lembar Inspeksi -->
    <li class="menu-item">
        <a href="{{ route('qa.li.master-template') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('qa.li.master-template')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('qa.li.master-template')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="font-semibold tracking-wide">Master Lembar Inspeksi</span>
        </a>
    </li>

    <!-- Laporan Harian Inspeksi -->
    <li class="menu-item">
        <a href="{{ route('qa.li.summary') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('qa.li.summary')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('qa.li.summary')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-semibold tracking-wide">Laporan Harian Inspeksi</span>
        </a>
    </li>

    <!-- Item Check -->
    <li class="menu-item">
        <a href="{{ route('qa.item-check.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('qa.item-check.*')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('qa.item-check.*')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-semibold tracking-wide">Item Check</span>
        </a>
    </li>

    <!-- QPR -->
    <li class="menu-item">
        <a href="{{ route('qa.qpr.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('qa.qpr.index')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('qa.qpr.index')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-semibold tracking-wide">QPR</span>
        </a>
    </li>

    <!-- Registrasi QPR -->
    <li class="menu-item">
        <a href="{{ route('qa.qpr.registration') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive('qa.qpr.registration')) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive('qa.qpr.registration')) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="font-semibold tracking-wide">Registrasi QPR</span>
        </a>
    </li>

    <!-- Leaderboard Kinerja QC -->
    <li class="menu-item">
        <a href="{{ route('qa.qc.rapor-leader') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $itemClass($itemActive(['qa.qc.rapor-leader', 'qa.qc.rapor'])) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $iconClass($itemActive(['qa.qc.rapor-leader', 'qa.qc.rapor'])) }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="font-semibold tracking-wide">Leaderboard Kinerja QC</span>
        </a>
    </li>

</ul>
