@php
    $dashboardActive = request()->routeIs('production.dashboard');
    $recapActive = request()->routeIs('production_recap');
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Dashboard -->
    @if(auth()->user()->hasFeature('dashboard'))
    <li class="menu-item">
        <a href="{{ route('production.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard</span>
        </a>
    </li>
    @endif

    <!-- Reporting Section Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Reporting</span>
    </li>

    <!-- Production Recap -->
    @if(auth()->user()->hasFeature('production_recap'))
    <li class="menu-item">
        <a href="{{ route('production_recap') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $recapActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $recapActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span class="font-semibold tracking-wide">Production Recap</span>
        </a>
    </li>
    @endif

</ul>
