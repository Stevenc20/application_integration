@php
    $inputHarianActive = request()->routeIs('operational.input_harian');
    $lkhActive = request()->routeIs('supervisor.reports.daily_production');
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <!-- Production Entry Header -->
    <li class="px-4 mt-2 mb-2">
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

    <!-- Reporting Header -->
    <li class="px-4 mt-6 mb-2">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Reporting System</span>
    </li>

    <!-- LKH -->
    @if(auth()->user()->hasFeature('daily_report'))
    <li class="menu-item">
        <a href="{{ route('supervisor.reports.daily_production') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {{ $lkhActive ? 'bg-primary-red text-white shadow-md shadow-red-200' : 'text-gray-600 hover:bg-red-50 hover:text-primary-red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $lkhActive ? 'text-white' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0112 19V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 13v6a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-semibold tracking-wide">LKH</span>
        </a>
    </li>
    @endif

</ul>