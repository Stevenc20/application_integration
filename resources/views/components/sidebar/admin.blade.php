@php
    $dashboardActive = request()->routeIs('admin.dashboard');
    $userActive = request()->routeIs('admin.users.*');
    $masterActive = request()->routeIs('master.job') || request()->routeIs('master.karyawan');

    $arrow = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 arrow %s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    @if(auth()->user()->hasFeature('dashboard'))
    <li class="menu-item">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard</span>
        </a>
    </li>
    @endif

    <!-- System Management Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">System Management</span>
    </li>

    @if(auth()->user()->hasFeature('user_management'))
    <li class="menu-item">
        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $userActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $userActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
            </svg>
            <span class="font-semibold tracking-wide">User Management</span>
        </a>
    </li>
    @endif

    <!-- Master Data -->
    <li class="menu-item relative">
        <a href="javascript:void(0);" class="menu-toggle flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 {{ $masterActive ? 'bg-red-50 text-primary-red' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $masterActive ? 'text-primary-red' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                <span class="font-medium">Master Data</span>
            </div>
            {!! sprintf($arrow, $masterActive ? 'rotate-90' : '') !!}
        </a>
        <ul class="list-none ml-9 mt-1 space-y-1 {{ $masterActive ? '' : 'hidden' }} menu-sub">
            @if(auth()->user()->hasFeature('job_master'))
            <li>
                <a href="{{ route('master.job') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('master.job') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Job Master</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->hasFeature('data_karyawan'))
            <li>
                <a href="{{ route('master.karyawan') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('master.karyawan') ? 'bg-red-600 text-white font-medium' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Data Karyawan</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

</ul>
