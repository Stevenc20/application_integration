@php
    $indexActive = request()->routeIs('hambatan-jalur.*');
    $user = auth()->user();
@endphp

<ul class="list-none space-y-1 m-0 p-0">

    <li class="px-4 mt-2 mb-2">
        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Hambatan Jalur</span>
    </li>

    @if(auth()->user()->hasFeature('hambatan_jalur'))
    <li class="menu-item">
        <a href="{{ route('hambatan-jalur.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300
                  {{ $indexActive
                     ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg shadow-red-500/25'
                     : 'text-slate-600 hover:bg-red-50 hover:text-red-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ $indexActive ? 'text-white' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <span class="font-semibold tracking-wide">Dashboard Hambatan</span>
        </a>
    </li>
    @endif

</ul>
