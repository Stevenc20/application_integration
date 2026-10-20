@php
    $pendingSignRole = strtolower(auth()->user()->role ?? '');
    $pendingSignItems = collect();
    if (str_starts_with($pendingSignRole, 'leader')) {
        $pendingSignItems = \App\Models\HambatanJalur::where('status', 'pic_signed')
            ->orderByDesc('signed_at')
            ->get()
            ->filter(function ($h1) use ($pendingSignRole) {
                $letter = trim(str_ireplace('press', '', (string) $h1->line_name));
                return ('leader ' . strtolower($letter)) === $pendingSignRole;
            })
            ->values();
    } elseif ($pendingSignRole === 'foreman') {
        $pendingSignItems = \App\Models\HambatanJalur::where('status', 'leader_signed')
            ->orderByDesc('leader_signed_at')
            ->get();
    }
@endphp
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

            {{-- MONITOR TV BUTTON --}}
            <a href="{{ parse_url(route('supervisor.monitor'), PHP_URL_PATH) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 text-xs font-black transition-all shadow-sm" title="Buka Monitor TV Fullscreen (Production Press Achievement)">
                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="max-sm:hidden">Monitor TV</span>
            </a>

            {{-- LHJ PENDING SIGN --}}
            @if(str_starts_with($pendingSignRole, 'leader') || $pendingSignRole === 'foreman')
            <div class="relative" id="lhjSignWrapper">
                <button id="lhjSignBtn" class="relative flex items-center gap-2 p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors focus:outline-none" title="LHJ menunggu tanda tangan Anda">
                    <i class="bx bxs-edit-alt text-xl"></i>
                    <span id="lhjSignBadge" class="{{ $pendingSignItems->isNotEmpty() ? '' : 'hidden' }} text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">{{ $pendingSignItems->count() }}</span>
                    <span id="lhjSignDot" class="{{ $pendingSignItems->isNotEmpty() ? '' : 'hidden' }} absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" style="animation: notifPulse 1.5s ease-in-out infinite"></span>
                </button>
                <div id="lhjSignDropdown" class="absolute right-0 mt-2 w-[min(420px,calc(100vw-2.5rem))] max-w-[420px] bg-white rounded-xl shadow-lg border border-gray-100 opacity-0 invisible scale-95 transition-all duration-200 origin-top-right z-50 max-h-[36rem] overflow-y-auto">
                    <div id="lhjSignList" class="divide-y divide-gray-50">
                        @foreach($pendingSignItems as $p1)
                        <a href="{{ route('hambatan-jalur.show', $p1->id) }}" class="flex items-start gap-3 px-5 py-3 hover:bg-red-50/40 transition-colors border-b border-gray-50 last:border-b-0">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <i class="bx bx-check text-sm"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-gray-800 truncate">{{ $p1->line_name }} · {{ $p1->job_no }}</span>
                                <span class="block text-xs text-gray-500 truncate">{{ $p1->nama_part }} ({{ $p1->jenis_hambatan }})</span>
                            </span>
                            <span class="ml-auto shrink-0 text-[10px] font-black text-red-600 border border-red-200 bg-red-50 rounded-md px-2 py-1">TTD</span>
                        </a>
                        @endforeach
                    </div>
                    <div id="lhjSignEmpty" class="{{ $pendingSignItems->isNotEmpty() ? 'hidden ' : '' }}p-4 text-center text-xs text-gray-400">Tidak ada LHJ menunggu tanda tangan</div>
                </div>
                <div id="lhjInfoPopover" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-[1200]">
                    <span class="absolute -top-1.5 right-3 h-3 w-3 rotate-45 border-t border-l border-gray-200 bg-white rounded-[2px]"></span>
                    <div class="relative px-4 py-3.5 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-800">LHJ Perlu Tanda Tangan</p>
                            <p class="text-[11px] font-semibold text-red-600 mt-0.5"><i class="bx bx-info-circle align-middle mr-0.5"></i>WAJIB TTD — menunggu tanda tangan Anda</p>
                        </div>
                        <button type="button" id="lhjInfoClose" class="shrink-0 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg p-1.5 -m-1 transition-colors" title="Tutup">
                            <i class="bx bx-x text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif

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
                        <a href="{{ route('device_link.scanner') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            Tautkan Perangkat (QR)
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
                } else if (n.recovery_id) {
                    a.href = '{{ route("ppc.planning.recovery.index") }}';
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
                    } else if (n.recovery_id) {
                        window.location.href = '{{ route("ppc.planning.recovery.index") }}';
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

        // === LHJ PENDING SIGN ===
        const lhjBtn = document.getElementById('lhjSignBtn');
        const lhjDropdown = document.getElementById('lhjSignDropdown');
        const lhjList = document.getElementById('lhjSignList');
        const lhjEmpty = document.getElementById('lhjSignEmpty');
        const lhjPopover = document.getElementById('lhjInfoPopover');
        let lhjPopoverDismissed = false;

        function fetchPendingLhj() {
            if (!lhjBtn) return;
            fetch('{{ route("hambatan-jalur.pending-sign") }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                const badge = document.getElementById('lhjSignBadge');
                const dot = document.getElementById('lhjSignDot');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.classList.remove('hidden');
                    dot.classList.remove('hidden');
                    if (lhjPopover && !lhjPopoverDismissed) {
                        lhjPopover.classList.remove('hidden');
                    }
                } else {
                    badge.classList.add('hidden');
                    dot.classList.add('hidden');
                    lhjPopoverDismissed = false;
                    if (lhjPopover) lhjPopover.classList.add('hidden');
                }
                if (lhjDropdown && lhjDropdown.classList.contains('visible')) {
                    renderPendingLhj(data);
                }
            });
        }

        function renderPendingLhj(data) {
            lhjList.innerHTML = '';
            if (!data.items || data.items.length === 0) {
                lhjEmpty.classList.remove('hidden');
                return;
            }
            lhjEmpty.classList.add('hidden');
            data.items.forEach(function(item) {
                const a = document.createElement('a');
                a.href = item.url;
                a.className = 'flex items-start gap-3 px-5 py-3 hover:bg-red-50/40 transition-colors border-b border-gray-50 last:border-b-0';
                a.innerHTML =
                    '<span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600"><i class="bx bx-check text-sm"></i></span>' +
                    '<span class="min-w-0"><span class="block text-sm font-bold text-gray-800 truncate">' + (item.line_name || '-') + ' · ' + (item.job_no || '-') + '</span>' +
                    '<span class="block text-xs text-gray-500 truncate">' + (item.nama_part || '-') + ' (' + (item.jenis_hambatan || '-') + ')</span></span>' +
                    '<span class="ml-auto shrink-0 text-[10px] font-black text-red-600 border border-red-200 bg-red-50 rounded-md px-2 py-1">TTD</span>';
                lhjList.appendChild(a);
            });
        }

        if (lhjBtn && lhjDropdown) {
            lhjBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = lhjDropdown.classList.contains('opacity-0');
                if (isHidden) {
                    lhjDropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
                    lhjDropdown.classList.add('opacity-100', 'visible', 'scale-100');
                    fetchPendingLhj();
                } else {
                    lhjDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
                    lhjDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
                }
            });
            document.addEventListener('click', function(e) {
                if (!lhjBtn.contains(e.target) && !lhjDropdown.contains(e.target)) {
                    lhjDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
                    lhjDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
                }
            });
            const lhjInfoClose = document.getElementById('lhjInfoClose');
            if (lhjInfoClose) {
                lhjInfoClose.addEventListener('click', function(e) {
                    e.stopPropagation();
                    lhjPopoverDismissed = true;
                    if (lhjPopover) lhjPopover.classList.add('hidden');
                });
            }
        }

        fetchPendingLhj();
        setInterval(fetchPendingLhj, 15000);

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