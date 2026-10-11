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
    }
@endphp
@if($pendingLhj->isNotEmpty())
<div id="lhjPendingRoot">
    <div id="lhjPendingOverlay"
         class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         style="display:flex">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-amber-200 max-h-[80dvh] overflow-y-auto">
            <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 ring-8 ring-amber-50/50">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-1 text-center">Laporan Hambatan Jalur Menunggu Tanda Tangan</h3>
            <p class="text-sm text-gray-500 mb-4 text-center">Ada {{ $pendingLhj->count() }} LHJ untuk line Anda yang sudah ditandatangani PIC. Segera tandatangani agar laporan dianggap SAH.</p>
            <div class="space-y-3">
                @foreach($pendingLhj as $hambatan)
                <div class="border border-amber-200 rounded-xl p-3 bg-amber-50/50 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $hambatan->line_name }} · {{ $hambatan->jenis_hambatan }}</div>
                        <div class="text-sm font-bold text-gray-800 truncate">{{ $hambatan->job_no }} — {{ $hambatan->nama_part }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ $hambatan->problem }}</div>
                    </div>
                    <a href="{{ route('hambatan-jalur.show', $hambatan->id) }}"
                       class="lhj-sign-link shrink-0 inline-flex items-center gap-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-sm font-bold py-2 px-3 rounded-lg shadow transition-all">
                        Tanda Tangan
                    </a>
                </div>
                @endforeach
            </div>
            <div class="flex flex-col gap-2 mt-5">
                <button type="button" id="lhjLaterBtn"
                        class="w-full text-gray-500 hover:text-gray-800 hover:bg-gray-50 font-semibold py-2.5 rounded-xl transition-colors">
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

    if (laterBtn) {
        laterBtn.addEventListener('click', function () {
            overlay.style.display = 'none';
            sessionStorage.setItem(snoozeKey, String(Date.now() + 5 * 60 * 1000));
        });
    }

    document.querySelectorAll('.lhj-sign-link').forEach(function (link) {
        link.addEventListener('click', function () {
            sessionStorage.setItem(snoozeKey, String(Date.now() + 10 * 60 * 1000));
        });
    });
})();
</script>
@endif
@endauth