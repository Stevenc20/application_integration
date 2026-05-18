<header class="bg-white shadow-sm z-[200] shrink-0 relative">
    <div class="flex items-center justify-between px-4 py-3 md:px-6">
        <div class="flex items-center">
            <button id="openSidebar" class="mr-4 text-gray-500 hover:text-primary-red focus:outline-none md:hidden">
                <i class="bx bx-menu text-2xl"></i>
            </button>
            <h2 class="text-xl font-semibold text-gray-800">@yield('header_title', 'Application')</h2>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500 hidden sm:block font-medium" id="liveClockTopbar"></span>
            
            <div class="relative">
                <button id="userDropdownBtn" class="flex items-center focus:outline-none hover:bg-gray-50 p-1 rounded-lg transition-colors">
                    <div class="w-8 h-8 rounded-full bg-red-100 text-primary-red flex items-center justify-center font-bold mr-2 ring-2 ring-transparent hover:ring-red-200 transition">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="hidden sm:block text-left mr-1">
                        <p class="text-sm font-semibold text-gray-800 leading-tight">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500 capitalize leading-tight">{{ auth()->user()->role ?? 'Role' }}</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hidden sm:block transition-transform duration-200" id="userDropdownArrow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Profile Dropdown Modal -->
                <div id="userDropdownModal" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 opacity-0 invisible scale-95 transition-all duration-200 origin-top-right z-50">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 rounded-t-xl sm:hidden">
                        <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->role ?? 'Role' }}</p>
                    </div>
                    <div class="p-2 space-y-1">
                        <a href="#" class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            My Profile
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ route('logout') }}" class="flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('userDropdownBtn');
        const modal = document.getElementById('userDropdownModal');
        const arrow = document.getElementById('userDropdownArrow');

        if (btn && modal) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = modal.classList.contains('opacity-0');
                
                if (isHidden) {
                    modal.classList.remove('opacity-0', 'invisible', 'scale-95');
                    modal.classList.add('opacity-100', 'visible', 'scale-100');
                    if (arrow) arrow.classList.add('rotate-180');
                } else {
                    closeDropdown();
                }
            });

            document.addEventListener('click', function(e) {
                if (!btn.contains(e.target) && !modal.contains(e.target)) {
                    closeDropdown();
                }
            });
        }

        function closeDropdown() {
            if (modal && !modal.classList.contains('opacity-0')) {
                modal.classList.add('opacity-0', 'invisible', 'scale-95');
                modal.classList.remove('opacity-100', 'visible', 'scale-100');
                if (arrow) arrow.classList.remove('rotate-180');
            }
        }
    });
</script>