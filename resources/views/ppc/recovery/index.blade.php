@php
    $title = 'Recovery';
    $activeNav = 'Recovery';
    $activeTab = request('tab', 'queue');
@endphp
@extends('layouts.ppc')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">RECOVERY</h1>
            <p class="text-sm text-slate-500 mt-1">
                @if($activeTab === 'queue')
                    Item yang menunggu persetujuan untuk dijadwalkan ulang.
                @else
                    Riwayat semua item recovery (approved, rejected, scheduled, completed).
                @endif
            </p>
        </div>
        <a href="{{ route('ppc.planning.production_plan') }}"
           class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black transition-all">
            &larr; KEMBALI
        </a>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-6 bg-slate-100 rounded-2xl p-1 w-fit">
        <a href="{{ route('ppc.planning.recovery.index', ['tab' => 'queue']) }}"
           class="px-5 py-2.5 text-xs font-black rounded-xl transition-all {{ $activeTab === 'queue' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            QUEUE
            @if($queueCount > 0)
            <span class="ml-1.5 px-1.5 py-0.5 bg-amber-500 text-white rounded-full text-[10px]">{{ $queueCount }}</span>
            @endif
        </a>
        <a href="{{ route('ppc.planning.recovery.index', ['tab' => 'history']) }}"
           class="px-5 py-2.5 text-xs font-black rounded-xl transition-all {{ $activeTab === 'history' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            HISTORY
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('ppc.planning.recovery.index') }}" class="mb-6">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="flex flex-wrap gap-3 items-end">
            @if($activeTab === 'history')
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    <option value="">All Status</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Dari Tgl</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Sampai Tgl</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Shift</label>
                <select name="shift" class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">All Shift</option>
                    <option value="Shift Pagi" {{ request('shift') == 'Shift Pagi' ? 'selected' : '' }}>Shift Pagi</option>
                    <option value="Shift Malam" {{ request('shift') == 'Shift Malam' ? 'selected' : '' }}>Shift Malam</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Press</label>
                <select name="press" class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">All Press</option>
                    @foreach($presses as $p)
                    <option value="{{ $p }}" {{ request('press') == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Job No / Job Master..."
                       class="px-3 py-2 border border-slate-200 rounded-xl text-xs w-48 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black transition-all shadow-lg shadow-amber-200">
                    FILTER
                </button>
                <a href="{{ route('ppc.planning.recovery.index', ['tab' => $activeTab]) }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black transition-all">
                    RESET
                </a>
            </div>
        </div>
    </form>

    {{-- Tab Content: QUEUE --}}
    @if($activeTab === 'queue')
        {{-- Bulk Actions --}}
        <div class="flex items-center gap-3 mb-4">
            <button onclick="approveSelectedItems()"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-lg shadow-emerald-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    id="approveBtn" disabled>
                APPROVE SELECTED
            </button>
            <button onclick="rejectSelectedItems()"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-all shadow-lg shadow-rose-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    id="rejectBtn" disabled>
                REJECT SELECTED
            </button>
            <span class="text-xs text-slate-400" id="selectedCount">0 selected</span>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            @if($items->count())
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900 text-slate-300 font-bold uppercase tracking-wider">
                            <th class="text-center py-3 px-2 w-10">
                                <input type="checkbox" id="checklistAll" onchange="toggleAllCheckboxes()"
                                       class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 cursor-pointer">
                            </th>
                            <th class="text-left py-3 px-3">Tgl Asal</th>
                            <th class="text-left py-3 px-3">Shift</th>
                            <th class="text-left py-3 px-3">Press</th>
                            <th class="text-left py-3 px-3">Job No</th>
                            <th class="text-left py-3 px-3">Job Master</th>
                            <th class="text-right py-3 px-3">Plan</th>
                            <th class="text-right py-3 px-3">OK</th>
                            <th class="text-right py-3 px-3">Sisa</th>
                            <th class="text-right py-3 px-3">Durasi (mnt)</th>
                            <th class="text-center py-3 px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                        <tr class="hover:bg-amber-50/30 transition-colors">
                            <td class="text-center py-2.5 px-2">
                                <input type="checkbox" name="recovery_item" value="{{ $item->id }}"
                                       class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 cursor-pointer item-checkbox"
                                       onchange="updateSelectedCount()">
                            </td>
                            <td class="py-2.5 px-3 text-slate-500">{{ $item->source_date ? \Carbon\Carbon::parse($item->source_date)->format('d M Y') : ($item->original_date ? \Carbon\Carbon::parse($item->original_date)->format('d M Y') : '-') }}</td>
                            <td class="py-2.5 px-3 text-slate-500">{{ $item->source_shift ?? ($item->original_shift_name ?? '-') }}</td>
                            <td class="py-2.5 px-3 font-medium text-slate-600">{{ $item->press_name }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-700">{{ $item->job_no }}</td>
                            <td class="py-2.5 px-3 text-slate-500 max-w-[200px] truncate">{{ $item->job_master }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($item->plan_qty) }}</td>
                            <td class="py-2.5 px-3 text-right text-emerald-600 font-semibold">{{ number_format($item->ok) }}</td>
                            <td class="py-2.5 px-3 text-right text-rose-600 font-bold">{{ number_format($item->recovery_qty > 0 ? $item->recovery_qty : $item->plan_qty) }}</td>
                            <td class="py-2.5 px-3 text-right text-slate-600 font-medium">{{ $item->duration_minutes ? number_format($item->duration_minutes, 1) : '-' }}</td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="approveItem({{ $item->id }})"
                                            class="px-2 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-lg text-[10px] font-bold transition-all"
                                            title="Approve">&#10003;</button>
                                    <button onclick="rejectItem({{ $item->id }})"
                                            class="px-2 py-1 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg text-[10px] font-bold transition-all"
                                            title="Reject">&#10007;</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">{{ $items->links() }}</div>
            @else
            <div class="text-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-slate-400 font-semibold text-sm">Tidak ada recovery pending.</p>
                <p class="text-slate-300 text-xs mt-1">Item recovery akan muncul di sini setelah proses cut-off.</p>
            </div>
            @endif
        </div>
    @endif

    {{-- Tab Content: HISTORY --}}
    @if($activeTab === 'history')
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            @if($items->count())
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900 text-slate-300 font-bold uppercase tracking-wider">
                            <th class="text-left py-3 px-3">Tgl Asal</th>
                            <th class="text-left py-3 px-3">Shift</th>
                            <th class="text-left py-3 px-3">Press</th>
                            <th class="text-left py-3 px-3">Job No</th>
                            <th class="text-left py-3 px-3">Job Master</th>
                            <th class="text-right py-3 px-3">Plan</th>
                            <th class="text-right py-3 px-3">OK</th>
                            <th class="text-right py-3 px-3">Sisa</th>
                            <th class="text-right py-3 px-3">Durasi (mnt)</th>
                            <th class="text-left py-3 px-3">Status</th>
                            <th class="text-left py-3 px-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                        @php
                            $statusColors = [
                                'waiting_approval' => 'bg-amber-100 text-amber-700',
                                'in_production'    => 'bg-purple-100 text-purple-700',
                                'approved'         => 'bg-sky-100 text-sky-700',
                                'rejected'         => 'bg-rose-100 text-rose-700',
                                'scheduled'        => 'bg-emerald-100 text-emerald-700',
                                'completed'        => 'bg-slate-100 text-slate-700',
                            ];
                            $statusColor = $statusColors[$item->status] ?? 'bg-slate-100 text-slate-700';

                            // Build keterangan
                            switch ($item->status) {
                                case 'rejected':
                                    $rejector = $item->rejector?->name ?? 'System';
                                    $rejDate = $item->rejected_at ? \Carbon\Carbon::parse($item->rejected_at)->format('d M Y H:i') : '-';
                                    $notes = $item->rejection_notes ? ' Alasan: "'.$item->rejection_notes.'".' : '';
                                    $keterangan = "Ditolak oleh {$rejector} pada {$rejDate}.{$notes} Bisa diajukan ulang jika kapasitas tersedia.";
                                    break;
                                case 'waiting_approval':
                                    $keterangan = 'Menunggu approval. Belum masuk antrian produksi.';
                                    break;
                                case 'approved':
                                    $keterangan = 'Disetujui. Menunggu dijadwalkan oleh scheduler pada shift berikutnya.';
                                    break;
                                case 'scheduled':
                                    $linkedPlan = $item->productionPlan ? "({$item->productionPlan->plan_date} {$item->productionPlan->shift_name})" : '';
                                    $keterangan = "Sudah dijadwalkan {$linkedPlan}. Akan diproses sesuai timeline produksi.";
                                    break;
                                case 'in_production':
                                    $keterangan = 'Sedang diproduksi. Tidak dapat dijadwalkan ulang.';
                                    break;
                                case 'completed':
                                    $keterangan = 'Produksi selesai diproses.';
                                    break;
                                default:
                                    $keterangan = 'Menunggu proses.';
                                    break;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-2.5 px-3 text-slate-500">{{ $item->source_date ? \Carbon\Carbon::parse($item->source_date)->format('d M Y') : ($item->original_date ? \Carbon\Carbon::parse($item->original_date)->format('d M Y') : '-') }}</td>
                            <td class="py-2.5 px-3 text-slate-500">{{ $item->source_shift ?? ($item->original_shift_name ?? '-') }}</td>
                            <td class="py-2.5 px-3 font-medium text-slate-600">{{ $item->press_name }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-700">{{ $item->job_no }}</td>
                            <td class="py-2.5 px-3 text-slate-500 max-w-[200px] truncate">{{ $item->job_master }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($item->plan_qty) }}</td>
                            <td class="py-2.5 px-3 text-right text-emerald-600 font-semibold">{{ number_format($item->ok) }}</td>
                            <td class="py-2.5 px-3 text-right text-rose-600 font-bold">{{ number_format($item->recovery_qty > 0 ? $item->recovery_qty : $item->plan_qty) }}</td>
                            <td class="py-2.5 px-3 text-right text-slate-600 font-medium">{{ $item->duration_minutes ? number_format($item->duration_minutes, 1) : '-' }}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusColor }}">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[10px] max-w-[300px] leading-relaxed">{{ $keterangan }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">{{ $items->links() }}</div>
            @else
            <div class="text-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-slate-400 font-semibold text-sm">Belum ada item recovery.</p>
                <p class="text-slate-300 text-xs mt-1">Item akan muncul setelah proses cut-off atau import.</p>
            </div>
            @endif
        </div>
    @endif
</div>

{{-- CONFIRM MODAL (Tailwind) --}}
<div id="confirmModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeConfirm()"></div>
    <div class="relative w-full max-w-sm mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="flex flex-col items-center text-center">
                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-4 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">KONFIRMASI</h3>
                <p class="text-slate-500 text-sm mt-2" id="confirmMessage">—</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeConfirm()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">BATAL</button>
                <button type="button" onclick="executeConfirm()" class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold transition-all shadow-lg shadow-emerald-200">LANJUTKAN</button>
            </div>
        </div>
    </div>
</div>

{{-- TOAST NOTIFICATION (Tailwind) --}}
<div id="toast" class="fixed top-6 right-6 z-[99999] hidden items-center gap-3 transition-all duration-500">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 px-6 py-4 flex items-center gap-3">
        <span id="toastIcon" class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </span>
        <span id="toastMessage" class="text-sm font-semibold text-slate-700">—</span>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function toggleAllCheckboxes() {
        const checked = document.getElementById('checklistAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const count = checked.length;
        const el = document.getElementById('selectedCount');
        const approveBtn = document.getElementById('approveBtn');
        const rejectBtn = document.getElementById('rejectBtn');
        if (el) el.textContent = count + ' selected';
        if (approveBtn) approveBtn.disabled = count === 0;
        if (rejectBtn) rejectBtn.disabled = count === 0;
    }

    function approveSelectedItems() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length === 0) {
            showToast('Pilih minimal satu item yang akan di-approve.', 'warning');
            return;
        }
        showConfirm('Approve ' + checked.length + ' item recovery yang dipilih?', function() {
            const itemIds = Array.from(checked).map(cb => parseInt(cb.value));
            fetch('{{ route("ppc.planning.recovery.approve_items") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_ids: itemIds })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { location.reload(); }
                else { showToast(data.message || 'Gagal approve recovery.', 'error'); }
            })
            .catch(() => showToast('Error saat approve recovery.', 'error'));
        });
    }

    function rejectSelectedItems() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length === 0) {
            showToast('Pilih minimal satu item yang akan di-reject.', 'warning');
            return;
        }
        const notes = prompt('Alasan reject (opsional):');
        const itemIds = Array.from(checked).map(cb => parseInt(cb.value));
        fetch('{{ route("ppc.planning.recovery.reject_items") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_ids: itemIds, notes: notes || '' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { location.reload(); }
            else { showToast(data.message || 'Gagal reject recovery.', 'error'); }
        })
        .catch(() => showToast('Error saat reject recovery.', 'error'));
    }

    function approveItem(id) {
        showConfirm('Approve item ini?', function() {
            fetch('{{ route("ppc.planning.recovery.approve_items") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_ids: [id] })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { location.reload(); }
                else { showToast(data.message || 'Gagal approve.', 'error'); }
            })
            .catch(() => showToast('Error.', 'error'));
        });
    }

    function rejectItem(id) {
        const notes = prompt('Alasan reject (opsional):');
        fetch('{{ route("ppc.planning.recovery.reject_item", ["id" => "__ID__"]) }}'.replace('__ID__', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ notes: notes || '' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { location.reload(); }
            else { showToast(data.message || 'Gagal reject.', 'error'); }
        })
        .catch(() => showToast('Error.', 'error'));
    }

    // Confirm Modal helpers
    let confirmCallback = null;

    function showConfirm(message, onConfirm) {
        document.getElementById('confirmMessage').textContent = message;
        confirmCallback = onConfirm;
        document.getElementById('confirmModal').classList.remove('hidden');
        document.getElementById('confirmModal').classList.add('flex');
    }

    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('flex');
        document.getElementById('confirmModal').classList.add('hidden');
        confirmCallback = null;
    }

    function executeConfirm() {
        const cb = confirmCallback;
        closeConfirm();
        if (cb) cb();
    }

    // Toast notification helpers
    function showToast(message, type) {
        const el = document.getElementById('toast');
        const msgEl = document.getElementById('toastMessage');
        const iconEl = document.getElementById('toastIcon');
        msgEl.textContent = message;

        const icons = {
            success: { bg: 'bg-emerald-100', text: 'text-emerald-600', path: 'M5 13l4 4L19 7' },
            error: { bg: 'bg-rose-100', text: 'text-rose-600', path: 'M6 18L18 6M6 6l12 12' },
            warning: { bg: 'bg-amber-100', text: 'text-amber-600', path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z' },
        };
        const cfg = icons[type] || icons.success;
        iconEl.className = 'w-6 h-6 rounded-full ' + cfg.bg + ' ' + cfg.text + ' flex items-center justify-center shrink-0';
        iconEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + cfg.path + '" /></svg>';

        el.classList.remove('hidden');
        el.classList.add('flex');
        setTimeout(function() {
            el.classList.remove('flex');
            el.classList.add('hidden');
        }, 4000);
    }
</script>
@endpush

