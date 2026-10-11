@auth
@php
    $lhjUser = auth()->user();
    $lhjRole = strtolower($lhjUser->role ?? '');
    $pendingLhj = collect();

    if (str_starts_with($lhjRole, 'leader')) {
        $pendingLhj = \App\Models\HambatanJalur::where('status', 'pic_signed')
            ->orderByDesc('signed_at')
            ->get()
            ->filter(function ($hambatan) use ($lhjRole) {
                $letter = trim(str_ireplace('press', '', (string) $hambatan->line_name));
                return ('leader ' . strtolower($letter)) === $lhjRole;
            })
            ->values();
    } elseif ($lhjRole === 'foreman') {
        $pendingLhj = \App\Models\HambatanJalur::where('status', 'leader_signed')
            ->orderByDesc('leader_signed_at')
            ->get();
    }
@endphp
@if($pendingLhj->isNotEmpty())
<div id="lhjPendingRoot">
    <div id="lhjPendingOverlay"
         class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-6"
         style="display:flex">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full flex flex-col border border-gray-200 max-h-[80dvh]">
            <div class="px-6 pt-6 pb-4 text-center border-b border-gray-100 shrink-0">
                <div class="w-16 h-16 bg-gray-900 text-white rounded-full flex items-center justify-center mx-auto mb-4 ring-8 ring-gray-100">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1 text-center">Laporan Hambatan Jalur Menunggu Tanda Tangan</h3>
                <p class="text-sm text-gray-500 text-center">Ada {{ $pendingLhj->count() }} LHJ menunggu tanda tangan Anda. Segera tandatangani agar laporan dianggap SAH.</p>
            </div>
            <div class="space-y-3 p-4 overflow-y-auto min-h-0 max-h-[42dvh]">
                @foreach($pendingLhj as $hambatan)
                <div class="border border-gray-200 rounded-xl p-3 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $hambatan->line_name }} · {{ $hambatan->jenis_hambatan }}</div>
                        <div class="text-sm font-bold text-gray-900 truncate">{{ $hambatan->job_no }} — {{ $hambatan->nama_part }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $hambatan->problem }}</div>
                    </div>
                    <a href="{{ route('hambatan-jalur.show', $hambatan->id) }}"
                       class="lhj-sign-link shrink-0 inline-flex items-center justify-center gap-1 bg-gray-900 hover:bg-gray-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow transition-all">
                        Tanda Tangan
                    </a>
                </div>
                @endforeach
            </div>
            <div class="border-t border-gray-100 p-3 shrink-0">
                <button type="button" id="lhjLaterBtn"
                        class="w-full text-gray-500 hover:text-gray-800 hover:bg-gray-100 font-semibold py-2.5 rounded-xl transition-colors">
                    Nanti saja (tandatangani nanti)
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('lhjPendingOverlay');
    var laterBtn = document.getElementById('lhjLaterBtn');
    if (!overlay) return;

    var snoozeKey = 'lhj-sign-prompt';
    var snoozeUntil = parseInt(sessionStorage.getItem(snoozeKey) || '0', 10);
    if (snoozeUntil > Date.now()) {
        overlay.style.display = 'none';
    }

    function hideLhjPrompt() {
        overlay.style.display = 'none';
    }

    function syncFromServer() {
        var u = '{{ route("hambatan-jalur.pending-sign") }}';
        fetch(u, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                if (data.count === 0) {
                    sessionStorage.removeItem(snoozeKey);
                    hideLhjPrompt();
                }
            });
    }

    if (laterBtn) {
        laterBtn.addEventListener('click', function () {
            hideLhjPrompt();
            sessionStorage.setItem(snoozeKey, String(Date.now() + 5 * 60 * 1000));
        });
    }

    document.querySelectorAll('.lhj-sign-link').forEach(function (link) {
        link.addEventListener('click', function () {
            sessionStorage.setItem(snoozeKey, String(Date.now() + 10 * 60 * 1000));
        });
    });

    syncFromServer();
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) syncFromServer();
    });
})();
</script>
@endif
@endauth