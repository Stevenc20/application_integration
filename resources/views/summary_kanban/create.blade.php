@extends('layouts.app')

@section('title', 'Buat SKM - Summary Kanban Material')

@section('content')
<div class="space-y-6">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl">assignment</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Buat Summary Kanban Material</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Sistem mendeteksi {{ count($pending) }} item perlu dipesan berdasarkan kalkulasi kanban beredar.</p>
            </div>
        </div>
        <a href="{{ route('summary_kanban.index') }}" class="relative bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-5 py-2.5 transition-all text-sm backdrop-blur-sm">Batal</a>
    </div>

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm text-sm font-semibold">
        <span class="material-icons text-red-400 text-lg">error</span> {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-800 flex items-center justify-between">
            <span>Detail SKM</span>
        </div>

        <form method="POST" action="{{ route('summary_kanban.store') }}" id="skm-form">
            @csrf

            <div class="flex flex-wrap gap-4 p-5 items-end">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Tanggal Order *</label>
                    <input type="date" name="order_date" value="{{ user_now()->format('Y-m-d') }}" required class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Est. Pengiriman</label>
                    <input type="date" name="expected_delivery_date" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div class="flex flex-col gap-1 min-w-[200px]">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Lokasi Gudang Tujuan</label>
                    <select name="storage_location_id" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                        <option value="">— Pilih Lokasi —</option>
                        @foreach($storageLocations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->code }} - {{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Catatan SKM (Opsional)</label>
                    <input type="text" name="notes" placeholder="Catatan..." class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-800 text-white">
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap w-9">
                                <input type="checkbox" id="check-all" checked class="w-4 h-4 rounded border-slate-400">
                            </th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap">Material</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap">Vendor</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right">Stok Saat Ini</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right" title="Kanban per hari × (LT+SS+Proses)">Total Kanban Beredar</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right" title="floor(stok ÷ qty/kartu)">Stok (kanban)</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right">Outstanding</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right">Qty/Kartu</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right w-[90px]">Jml Kartu *</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap text-right">Total Order</th>
                            <th class="py-2.5 px-3 font-bold whitespace-nowrap w-40">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($pending as $idx => $p)
                        <tr id="row-{{ $idx }}" class="hover:bg-slate-50 transition-colors">
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" name="items[{{ $idx }}][selected]" value="1"
                                       class="row-check" checked style="width:16px;height:16px"
                                       onchange="toggleRow({{ $idx }}, this.checked)">
                                <input type="hidden" name="items[{{ $idx }}][material_id]" value="{{ $p['material']->id }}">
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-mono font-bold text-blue-600 text-xs">{{ $p['material']->code }}</div>
                                <div class="text-slate-500 text-[11px]">{{ $p['material']->name }}</div>
                                <div class="text-slate-400 text-[10px]">{{ $p['material']->unit_of_measure ?? '' }}</div>
                                @if($p['rm_sheet_demand'] > 0)
                                <div class="text-indigo-500 text-[10px]">Demand: {{ number_format($p['rm_sheet_demand'], 0) }} sht</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                @if($p['material']->vendor)
                                    {{ $p['material']->vendor->name }}
                                @else
                                    <span class="text-red-500 font-bold">Belum ada vendor</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right text-red-500 font-bold">{{ number_format($p['current_stock'], 0) }}</td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-800">
                                {{ $p['total_kanban'] }}
                                @if($p['kanban_per_day'] > 0)
                                <div class="text-slate-400 text-[10px] font-normal">{{ $p['kanban_per_day'] }}/hr × 6hr</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right text-slate-500">{{ $p['stock_kanban'] }}</td>
                            <td class="py-2.5 px-3 text-right text-orange-500 font-bold">
                                {{ $p['outstanding_kanban'] }}
                                @if($p['outstanding_qty'] > 0)
                                <div class="text-slate-400 text-[10px] font-normal">{{ number_format($p['outstanding_qty'], 0) }} sht</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right">{{ number_format($p['kanban_qty'], 0) }}</td>
                            <td class="py-2.5 px-3">
                                <input type="number" name="items[{{ $idx }}][num_cards]"
                                       value="{{ $p['num_cards_suggest'] }}" min="1" required
                                       class="border border-slate-300 rounded w-20 text-right px-2 py-1.5 text-sm outline-none focus:border-rose-400 num-cards-input"
                                       data-kanban="{{ $p['kanban_qty'] }}"
                                       data-row="{{ $idx }}"
                                       oninput="calcTotal({{ $idx }})">
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-800" id="total-{{ $idx }}">
                                {{ number_format($p['order_qty_suggest'], 0) }}
                            </td>
                            <td class="py-2.5 px-3">
                                <input type="text" name="items[{{ $idx }}][notes]"
                                       class="border border-slate-300 rounded w-full px-2 py-1.5 text-xs outline-none focus:border-rose-400" placeholder="Opsional...">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between px-5 py-4 border-t border-slate-100">
                <span class="text-xs text-slate-500">
                    <span id="selected-count">{{ count($pending) }}</span> dari {{ count($pending) }} item dipilih
                </span>
                <button type="submit" id="submit-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2.5 transition-all text-sm">Generate SKM</button>
            </div>
        </form>
    </div>

</div>

<script>
document.getElementById('check-all').addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach((cb, idx) => {
        cb.checked = this.checked;
        toggleRow(idx, this.checked);
    });
    updateCount();
});

function toggleRow(idx, checked) {
    const row = document.getElementById('row-' + idx);
    const inputs = row.querySelectorAll('input:not([type=checkbox])');
    inputs.forEach(i => { i.disabled = !checked; });
    row.style.opacity = checked ? '1' : '0.4';
    updateCount();
}

function calcTotal(idx) {
    const input    = document.querySelector('[data-row="' + idx + '"]');
    const kanbanQty = parseFloat(input.dataset.kanban) || 0;
    const numCards  = parseInt(input.value) || 0;
    document.getElementById('total-' + idx).textContent = (kanbanQty * numCards).toLocaleString('id-ID');
}

function updateCount() {
    const selected = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('selected-count').textContent = selected;
    document.getElementById('submit-btn').disabled = selected === 0;
}

document.getElementById('skm-form').addEventListener('submit', function () {
    document.querySelectorAll('.row-check').forEach((cb, idx) => {
        if (!cb.checked) {
            const row = document.getElementById('row-' + idx);
            row.querySelectorAll('input').forEach(i => i.name = '');
        }
    });
});
</script>

@endsection
