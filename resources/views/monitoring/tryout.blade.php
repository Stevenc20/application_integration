@extends(auth()->user()->role === 'ppc' ? 'layouts.ppc' : 'layouts.supervisor')

@php
    $title = 'History Tryout';
    $isEditable = true;
@endphp

@section('title', $title)
@section('header_title', $title)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="text-orange-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <h1 class="text-2xl font-bold text-gray-800">{{ $title }}</h1>
        </div>
    </div>

    @if($uneditedCount > 0)
    <div class="bg-amber-50 border-l-4 border-amber-400 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="text-sm font-bold text-amber-800">Ada {{ $uneditedCount }} data tryout yang belum diisi detailnya.</p>
            <p class="text-xs text-amber-700 mt-0.5">Klik tombol edit <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-white border border-amber-300 text-amber-700 text-[10px] font-bold mx-0.5"><svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit</span> pada baris yang ingin dilengkapi.</p>
        </div>
    </div>
    @endif

    <form method="GET" class="flex flex-wrap items-end gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal</label>
            <input type="date" name="date" value="{{ $selectedDate }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Line</label>
            <select name="line"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Semua Line</option>
                @foreach($lines as $l)
                    <option value="{{ $l }}" {{ $selectedLine === $l ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Shift</label>
            <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                <button type="button" onclick="setShiftFilter(0)"
                    class="px-3 py-2 text-xs font-bold transition-all {{ $selectedShift === 0 ? 'bg-orange-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">Semua</button>
                <button type="button" onclick="setShiftFilter(1)"
                    class="px-3 py-2 text-xs font-bold transition-all {{ $selectedShift === 1 ? 'bg-orange-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">Shift Pagi</button>
                <button type="button" onclick="setShiftFilter(2)"
                    class="px-3 py-2 text-xs font-bold transition-all {{ $selectedShift === 2 ? 'bg-orange-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">Shift Malam</button>
            </div>
            <input type="hidden" name="shift" id="shiftInput" value="{{ $selectedShift }}">
        </div>
        <button type="submit"
                class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm font-bold hover:bg-orange-600 transition">
            Filter
        </button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Problem</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">PIC</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Job No</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Line</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mulai</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Selesai</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Durasi</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $i => $dt)
                    @php
                        $start = $dt->start_time ? \Carbon\Carbon::parse($dt->start_time) : null;
                        $end = $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time) : null;
                        $durSeconds = $dt->duration_seconds
                            ? (int)$dt->duration_seconds
                            : ($start && $end ? abs($end->diffInSeconds($start)) : ($start ? abs(now()->diffInSeconds($start)) : 0));
                        $durMinutes = $durSeconds / 60;
                        $isUnedited = !$dt->problem || $dt->problem === '-' || $dt->problem === '';
                    @endphp
                    <tr class="hover:bg-gray-50 transition {{ $isUnedited ? 'bg-red-50/30' : '' }}">
                        <td class="px-4 py-3 text-gray-400 font-mono">{{ $records->firstItem() + $i }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                {{ $dt->jenis_downtime }}
                            </span>
                            @if($isUnedited)
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 font-black text-[8px] uppercase tracking-wider">Baru</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-[250px] truncate {{ $isUnedited ? 'text-amber-600 font-bold' : 'text-gray-700' }}" title="{{ $dt->problem ?? '-' }}">{{ $dt->problem ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $dt->pic ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $dt->jobMaster?->job_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $dt->jobMaster?->line ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $start ? $start->format('H:i:s') : '-' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $end ? $end->format('H:i:s') : 'Running' }}</td>
                        <td class="px-4 py-3 font-mono text-gray-800">@fmtMin($durMinutes)</td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="openEditDowntime({{ $dt->id }})"
                                    class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button onclick="deleteBreakEntry({{ $dt->id }})"
                                    class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all ml-1" title="Hapus">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-400">Tidak ada data {{ $title }} untuk tanggal ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $records->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="editDowntimeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-[9999] items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-gray-100 overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between bg-orange-50">
            <div>
                <h3 class="font-bold text-orange-800 text-lg">Edit Try Out</h3>
                <p class="text-sm text-orange-600 mt-0.5">Lengkapi detail try out</p>
            </div>
            <a href="{{ url()->current() }}?date={{ $selectedDate }}&line={{ $selectedLine }}&shift={{ $selectedShift }}" class="w-8 h-8 rounded-lg bg-white border border-orange-200 text-orange-400 hover:text-orange-600 flex items-center justify-center transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="editDtId" value="">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Jenis Downtime</label>
                    <select id="editJenis" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-200 outline-none transition bg-gray-50" disabled>
                        <option value="try out">Try Out</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Problem <span class="text-red-500">*</span></label>
                    <input type="text" id="editProblem" placeholder="Masalah yang terjadi..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Penyebab</label>
                    <input type="text" id="editPenyebab" placeholder="Penyebab masalah..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Action</label>
                    <input type="text" id="editAction" placeholder="Tindakan yang diambil..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">PIC</label>
                    <input type="text" id="editPIC" placeholder="Nama penanggung jawab..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-200 outline-none transition">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <a href="{{ url()->current() }}?date={{ $selectedDate }}&line={{ $selectedLine }}&shift={{ $selectedShift }}" id="editModalBatalBtn" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-bold text-sm transition-all text-center">Batal</a>
                <button id="editModalSaveBtn" class="flex-1 px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm shadow-lg shadow-orange-100 transition-all flex items-center justify-center gap-2" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.HistoryConfig = {
    csrfToken: @json(csrf_token()),
    userName: @json(auth()->user()->name ?? '')
};

window.downtimeList = @json($downtimeList);

function setShiftFilter(s) {
    document.getElementById('shiftInput').value = s;
    document.querySelector('form').submit();
}

function openEditDowntime(id) {
    var dt = window.downtimeList.find(function(d) { return d.id === id; });
    if (!dt) return;
    document.getElementById('editDtId').value = dt.id;
    document.getElementById('editJenis').value = dt.jenis;
    document.getElementById('editProblem').value = (!dt.problem || dt.problem === '-') ? '' : dt.problem;
    document.getElementById('editPenyebab').value = (!dt.penyebab || dt.penyebab === '-') ? '' : dt.penyebab;
    document.getElementById('editAction').value = (!dt.action || dt.action === '-') ? '' : dt.action;
    document.getElementById('editPIC').value = (!dt.pic || dt.pic === '-') ? (window.HistoryConfig.userName || '') : dt.pic;
    document.getElementById('editDowntimeModal').style.display = 'flex';
}

function closeEditModal() {
    var el = document.getElementById('editDowntimeModal');
    if (el) el.style.display = 'none';
}

function deleteBreakEntry(id) {
    if (!confirm('Hapus entry tryout ini?')) return;
    fetch('/operational/downtime/' + id + '/delete', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': window.HistoryConfig.csrfToken }
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert('Gagal menghapus: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(function() {
        location.reload();
    });
}

function saveEditDowntime() {
    var id = document.getElementById('editDtId').value;
    if (!id) return;

    var data = {
        jenis_downtime: document.getElementById('editJenis').value,
        problem: document.getElementById('editProblem').value,
        penyebab: document.getElementById('editPenyebab').value,
        action: document.getElementById('editAction').value,
        pic: document.getElementById('editPIC').value
    };

    fetch('/operational/downtime/' + id + '/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.HistoryConfig.csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
        if (res.success) {
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(function(err) {
        location.reload();
    });
}

(function() {
    var modal = document.getElementById('editDowntimeModal');
    if (!modal) return;

    var saveBtn = document.getElementById('editModalSaveBtn');
    if (saveBtn) saveBtn.onclick = saveEditDowntime;

    modal.onclick = function(e) {
        if (e.target === modal) modal.style.display = 'none';
    };
})();
</script>
@endsection