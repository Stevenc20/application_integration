@php
    $dashboardActive = request()->routeIs('super-admin.dashboard');
    $userActive = request()->routeIs('super-admin.users.*');
    $featureActive = request()->routeIs('super-admin.features.*');
    $assignmentActive = request()->routeIs('super-admin.assignments.*');
    $recycleBinActive = request()->routeIs('super-admin.recycle-bin.*');
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <li class="menu-item">
        <a href="{{ route('super-admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $dashboardActive ? 'bg-red-600 text-white font-medium' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $dashboardActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-semibold">Dashboard</span>
        </a>
    </li>

    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">System Management</span>
    </li>

    <li class="menu-item">
        <a href="{{ route('super-admin.users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $userActive ? 'bg-red-600 text-white font-medium' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $userActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
            </svg>
            <span class="font-semibold">Users</span>
        </a>
    </li>

    <li class="menu-item">
        <a href="{{ route('super-admin.features.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $featureActive ? 'bg-red-600 text-white font-medium' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $featureActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
            <span class="font-semibold">Features</span>
        </a>
    </li>

    <li class="menu-item">
        <a href="{{ route('super-admin.assignments.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $assignmentActive ? 'bg-red-600 text-white font-medium' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $assignmentActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="font-semibold">Line Assignments</span>
        </a>
    </li>

    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Data Management</span>
    </li>

    <li class="menu-item">
        <a href="{{ route('super-admin.recycle-bin.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $recycleBinActive ? 'bg-red-600 text-white font-medium' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $recycleBinActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <span class="font-semibold">Recycle Bin</span>
        </a>
    </li>

</ul>
