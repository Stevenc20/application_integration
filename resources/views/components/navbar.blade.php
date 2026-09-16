<header class="bg-white shadow-sm z-30 shrink-0 relative">
    <div class="flex items-center justify-between px-4 py-3 md:px-6">
        <div class="flex items-center">
            <button id="openSidebar" class="mr-4 text-gray-500 hover:text-primary-red focus:outline-none md:hidden">
                <i class="bx bx-menu text-2xl"></i>
            </button>
            <h2 class="text-xl font-semibold text-gray-800">@yield('header_title', 'Application')</h2>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500 max-sm:hidden sm:block font-medium" id="liveClockTopbar"></span>
            
            {{-- NOTIFICATION BELL --}}
            <div class="relative" id="notificationBellWrapper">
                <button id="notificationBellBtn" class="relative flex items-center gap-2 p-2 text-gray-500 hover:text-primary-red hover:bg-gray-50 rounded-lg transition-colors focus:outline-none">
                    <i class="bx bx-bell text-xl"></i>
                    <span id="notificationBadgeText" class="hidden text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">0 Notifikasi</span>
                    <span id="notificationDot" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full hidden" style="animation: notifPulse 1.5s ease-in-out infinite"></span>
                </button>
                <div id="notificationDropdown" class="fixed top-16 left-4 right-4 sm:absolute sm:top-auto sm:-right-4 sm:left-auto sm:w-[450px] mt-2 bg-white rounded-xl shadow-lg border border-gray-100 opacity-0 invisible scale-95 transition-all duration-200 origin-top-right z-50 max-h-[36rem] overflow-y-auto">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <p class="text-base font-bold text-gray-800">Notifikasi</p>
                    </div>
                    <div id="notificationList" class="divide-y divide-gray-50">
                        <div class="p-4 text-center text-xs text-gray-400">Memuat...</div>
                    </div>
                    <div id="notificationEmpty" class="hidden p-4 text-center text-xs text-gray-400">Tidak ada notifikasi</div>
                    <div class="border-t border-gray-100">
                        <a href="{{ route('notifications.index') }}" class="block px-5 py-3 text-center text-sm font-semibold text-primary-red hover:bg-red-50 transition-colors rounded-b-xl">
                            Lihat Semua Notifikasi
                        </a>
                    </div>
                </div>
            </div>

            <div class="relative">
                <button id="userDropdownBtn" class="flex items-center focus:outline-none hover:bg-gray-50 p-1 rounded-lg transition-colors">
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('uploads/'.auth()->user()->avatar) }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover mr-2 ring-2 ring-transparent hover:ring-red-200 transition">
                    @else
                        <div class="w-8 h-8 rounded-full bg-red-100 text-primary-red flex items-center justify-center font-bold mr-2 ring-2 ring-transparent hover:ring-red-200 transition">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </div>
                    @endif
                    <div class="max-sm:hidden sm:block text-left mr-1">
                        <p class="text-sm font-semibold text-gray-800 leading-tight">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500 capitalize leading-tight">{{ auth()->user()->role ?? 'Role' }}</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 max-sm:hidden sm:block transition-transform duration-200" id="userDropdownArrow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
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
        // === NOTIFICATION BELL ===
        const notifBtn = document.getElementById('notificationBellBtn');
        const notifDropdown = document.getElementById('notificationDropdown');
        const notifList = document.getElementById('notificationList');
        const notifEmpty = document.getElementById('notificationEmpty');

        function fetchNotifications() {
            fetch('{{ url("/notifications/unread") }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const badgeText = document.getElementById('notificationBadgeText');
                const dot = document.getElementById('notificationDot');
                if (data.count > 0) {
                    badgeText.textContent = data.count + ' Notifikasi';
                    badgeText.classList.remove('hidden');
                    dot.classList.remove('hidden');
                } else {
                    badgeText.classList.add('hidden');
                    dot.classList.add('hidden');
                }
                if (notifDropdown.classList.contains('visible')) {
                    renderNotifications(data);
                }
            });
        }

        function renderNotifications(data) {
            notifList.innerHTML = '';
            if (!data.notifications || data.notifications.length === 0) {
                notifEmpty.classList.remove('hidden');
                return;
            }
            notifEmpty.classList.add('hidden');
            data.notifications.forEach(n => {
                const a = document.createElement('a');
                a.className = 'notif-item block px-5 py-4 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0';
                if (n.hambatan_id) {
                    a.href = '{{ url("/hambatan-jalur") }}/' + n.hambatan_id;
                    a.classList.add('cursor-pointer');
                } else {
                    a.href = '#';
                    a.classList.add('cursor-default');
                }
                a.innerHTML = '<p class="text-sm font-semibold text-gray-800 leading-relaxed break-words">' + n.message + '</p><p class="text-xs text-gray-400 mt-1">' + n.created_at + '</p>';
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (n.id) {
                        fetch('{{ url("/notifications") }}/' + n.id + '/read', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' } });
                    }
                    if (n.hambatan_id) {
                        window.location.href = '{{ url("/hambatan-jalur") }}/' + n.hambatan_id;
                    }
                });
                notifList.appendChild(a);
            });
        }

        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = notifDropdown.classList.contains('opacity-0');
                if (isHidden) {
                    notifDropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
                    notifDropdown.classList.add('opacity-100', 'visible', 'scale-100');
                    fetchNotifications();
                } else {
                    notifDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
                    notifDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
                }
            });
            document.addEventListener('click', function(e) {
                if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
                    notifDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
                }
            });
        }

        // Poll every 5s for instant notification
        fetchNotifications();
        setInterval(fetchNotifications, 5000);

        // === USER DROPDOWN ===
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
<style>
@keyframes notifPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.8); opacity: 0.3; }
}
</style>