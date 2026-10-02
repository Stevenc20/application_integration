@php
    $user = auth()->user();
    $hasPullAheadAlert = false;
    $pullAheadMessage = '';
    $pullAheadUrl = '#';
    $pullAheadLabel = 'Buka';

    if ($user && $user->isRole(['ppc', 'manager'])) {
        $count = \App\Models\PullAheadRequest::where('status', 'PENDING')
                    ->where('is_read_by_ppc', false)
                    ->count();
        if ($count > 0) {
            $hasPullAheadAlert = true;
            $pullAheadMessage = "Ada $count Request Pull Ahead baru dari Leader yang menunggu Approval.";
            $pullAheadUrl = route('ppc.pull_ahead.index');
            $pullAheadLabel = 'Tinjau Request';
        }
    } elseif ($user && (str_contains(strtolower($user->role), 'leader') || str_contains(strtolower($user->role), 'supervisor'))) {
        $count = \App\Models\PullAheadRequest::whereIn('status', ['APPROVED', 'REJECTED', 'APPLIED'])
                    ->where('is_read_by_leader', false)
                    ->where('requested_by', $user->id)
                    ->count();
        if ($count > 0) {
            $hasPullAheadAlert = true;
            $pullAheadMessage = "Ada $count update status pada Request Pull Ahead Anda.";
            $pullAheadUrl = route('operational.input_harian');
            $pullAheadLabel = 'Buka Jadwal';
        }
    }
@endphp

<div id="pullAheadAlertRoot"
     data-poll-url="{{ route('pull_ahead.pending_count') }}"
     data-mark-read-url="{{ route('pull_ahead.mark_read') }}"
     data-csrf="{{ csrf_token() }}"
     data-initial-url="{{ $pullAheadUrl }}">

    <div id="pullAheadAlertOverlay"
         class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
         style="{{ $hasPullAheadAlert ? '' : 'display:none' }}">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-center border border-blue-100 mx-4">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 ring-8 ring-blue-50/50">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Pemberitahuan Sistem</h3>
            <p id="pullAheadAlertMessage" class="text-gray-600 mb-6 leading-relaxed">{{ $pullAheadMessage }}</p>
            <div class="flex flex-col gap-3">
                <a id="pullAheadAlertLink" href="{{ $pullAheadUrl }}"
                   class="w-full inline-flex justify-center items-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition-all">
                    {{ $pullAheadLabel }}
                </a>
                <button type="button" id="pullAheadAlertClose"
                        class="w-full text-gray-500 hover:text-gray-800 hover:bg-gray-50 font-medium py-2.5 rounded-xl transition-colors">
                    Tutup &amp; Tandai Dibaca
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('pullAheadAlertRoot');
    if (!root) return;

    var overlay   = document.getElementById('pullAheadAlertOverlay');
    var messageEl = document.getElementById('pullAheadAlertMessage');
    var linkEl    = document.getElementById('pullAheadAlertLink');
    var closeBtn  = document.getElementById('pullAheadAlertClose');
    var pollUrl      = root.dataset.pollUrl;
    var markReadUrl  = root.dataset.markReadUrl;
    var initialUrl   = root.dataset.initialUrl;
    var csrf         = root.dataset.csrf;
    var dismissed = {{ $hasPullAheadAlert ? 'false' : 'true' }};

    function show(res) {
        overlay.style.display = 'flex';
        if (res && res.message) messageEl.textContent = res.message;
        if (res && res.label) linkEl.textContent = res.label;
        linkEl.setAttribute('href', (res && res.url) ? res.url : initialUrl);
    }

    closeBtn.addEventListener('click', function () {
        overlay.style.display = 'none';
        dismissed = true;
        fetch(markReadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).catch(function () {});
    });

    function poll() {
        if (dismissed) return;
        fetch(pollUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) {
                if (res && res.success && res.count > 0) show(res);
            })
            .catch(function () {});
    }

    setInterval(poll, 20000);
    setTimeout(poll, 3000);
})();
</script>