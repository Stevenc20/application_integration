@extends('layouts.app')

@section('title', 'Detail Purchase Order')

<style>
    /* Inline styles needed for JS-generated content and modal */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px; }
    .modal-overlay.open { display: flex; }
    .progress-bar-wrap { width: 100%; background: #e2e8f0; border-radius: 9999px; height: 6px; overflow: hidden; margin-top: 6px; }
    .progress-bar-fill { background: #10b981; height: 100%; border-radius: 9999px; }
</style>

@section('content')
<div class="space-y-6">
    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl text-white/80">receipt</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Detail Purchase Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Informasi detail mengenai nomor PO, status persetujuan, vendor, gudang penyimpanan, dan riwayat penerimaan barang masuk</p>
            </div>
        </div>
    </div>

    {{-- Header Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nomor Purchase Order</p>
                <p class="text-2xl font-black font-mono" style="color: var(--navy-dark);">{{ $purchaseOrder->po_number }}</p>
                <p class="text-sm font-bold text-slate-500">{{ $purchaseOrder->vendor->nama ?? '-' }}</p>
            </div>
            <div class="text-right flex flex-col items-end gap-3">
                @php
                    $statusColors = [
                        'draft' => 'bg-slate-100 text-slate-700',
                        'approved' => 'bg-blue-100 text-blue-700',
                        'received' => 'bg-emerald-100 text-emerald-700',
                        'cancelled' => 'bg-rose-100 text-rose-700',
                        'partially_received' => 'bg-amber-100 text-amber-700',
                    ];
                    $statClass = $statusColors[$purchaseOrder->status] ?? 'bg-slate-100 text-slate-700';
                @endphp
                <span class="px-4 py-1.5 rounded-full text-xs font-black tracking-wider uppercase {{ $statClass }}">
                    {{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}
                </span>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($purchaseOrder->status === 'draft')
                        @php
                            $today       = \Carbon\Carbon::today();
                            $delivDate   = $purchaseOrder->expected_delivery_date;
                            $daysLeft    = $delivDate ? $today->diffInDays($delivDate, false) : null;
                            $pastDeadline = $delivDate && $today->gt($delivDate);
                            $willAutoApprove = $delivDate && !$pastDeadline && $daysLeft <= 2;
                        @endphp

                        @if($pastDeadline)
                            <div class="flex items-center gap-1.5 px-3 py-2 bg-rose-50 border border-rose-200 rounded-xl text-[11px] font-bold text-rose-600">
                                <span class="material-icons text-sm">warning</span>
                                Tidak bisa approve — est. pengiriman terlewat
                            </div>
                        @else
                            <form method="POST" action="{{ route('purchase_orders.approve', $purchaseOrder->id) }}" class="inline">
                                @csrf
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                                    <span class="material-icons text-sm">check_circle</span> Approve
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="bg-amber-100 hover:bg-amber-200 text-amber-700 font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">edit</span> Edit
                        </a>
                    @endif

                    @if($purchaseOrder->status === 'approved' && $purchaseOrder->skm_order_id)
                        <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="bg-amber-100 hover:bg-amber-200 text-amber-700 font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">edit</span> Edit
                        </a>
                    @endif

                    @if($purchaseOrder->status === 'approved' || $purchaseOrder->status === 'partially_received')
                        <a href="{{ route('goods_receipts.index', ['po_id' => $purchaseOrder->id]) }}" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                            <span class="material-icons text-sm">inventory</span> Buat GR
                        </a>
                    @endif

                    @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                        <form method="POST" action="{{ route('purchase_orders.cancel', $purchaseOrder->id) }}" onsubmit="return confirm('Batalkan PO ini?')" class="inline">
                            @csrf
                            <button class="bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                                <span class="material-icons text-sm">cancel</span> Cancel
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('purchase_orders.detail_pdf', $purchaseOrder->id) }}" target="_blank" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                        <span class="material-icons text-sm">print</span> PDF
                    </a>
                    <a href="{{ route('purchase_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5">
                        <span class="material-icons text-sm">arrow_back</span> Kembali
                    </a>
                </div>

                {{-- Auto approval info --}}
                @if($purchaseOrder->status === 'draft' && !$pastDeadline && $daysLeft !== null)
                    <div class="text-[10px] text-slate-400 flex items-center gap-1">
                        <span class="material-icons text-xs">schedule</span>
                        @if($willAutoApprove)
                            @if($daysLeft == 0)
                                Auto-approve hari ini (H-0)
                            @elseif($daysLeft == 1)
                                Auto-approve besok (H-1)
                            @else
                                Auto-approve H-2 ({{ $delivDate->copy()->subDays(2)->format('d M Y') }})
                            @endif
                        @else
                            Auto-approve pada {{ $delivDate->copy()->subDays(2)->format('d M Y') }} ({{ $daysLeft - 2 }} hari lagi)
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 border-t border-slate-100 pt-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Tgl Order</p>
                <p class="text-sm font-bold text-slate-700 mt-1">{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d F Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Est. Terima</p>
                <p class="text-sm font-bold text-slate-700 mt-1">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d F Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Lokasi Gudang</p>
                <p class="text-sm font-bold text-slate-700 mt-1">{{ $purchaseOrder->storageLocation->code ?? '-' }} - {{ $purchaseOrder->storageLocation->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dibuat Oleh</p>
                <p class="text-sm font-bold text-slate-700 mt-1">{{ $purchaseOrder->createdBy->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dibuat Pada</p>
                <p class="text-sm font-bold text-slate-700 mt-1">{{ $purchaseOrder->created_at ? $purchaseOrder->created_at->format('d/m/Y H:i') : '-' }}</p>
            </div>
        </div>

        @if($purchaseOrder->approved_at)
        <div class="bg-slate-50 rounded-xl px-4 py-2 inline-block text-xs text-slate-600">
            <strong>Disetujui:</strong> {{ $purchaseOrder->approved_at->format('d M Y H:i') }} oleh <strong>{{ $purchaseOrder->approved_by }}</strong>
        </div>
        @endif

        @if($purchaseOrder->notes)
        <div class="border-t border-dashed border-slate-200 pt-4 text-sm text-slate-600">
            <strong>Catatan:</strong> {{ $purchaseOrder->notes }}
        </div>
        @endif
    </div>

    {{-- Items Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                <span class="material-icons text-rose-600">list</span> Item Purchase Order
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Material</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[120px]">Qty Order</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[120px]">Qty Terima</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[140px]">Harga Satuan</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[140px]">Total</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center w-[140px]">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchaseOrder->items as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="font-mono text-xs font-bold" style="color: var(--navy-dark);">{{ $item->material->code ?? '-' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $item->material->nama ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4 text-xs font-bold text-right">
                            {{ number_format($item->qty, 3) }} <span class="text-[10px] text-slate-300 font-normal">{{ $item->material->uom ?? '' }}</span>
                        </td>
                        <td class="py-3 px-4 text-xs font-bold text-right text-emerald-600">
                            {{ number_format($item->qty_received ?? 0, 3) }} <span class="text-[10px] text-slate-300 font-normal">{{ $item->material->uom ?? '' }}</span>
                        </td>
                        <td class="py-3 px-4 text-xs text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-right" style="color: var(--navy-dark);">{{ number_format(($item->qty * $item->unit_price), 0) }}</td>
                        <td class="py-3 px-4">
                            @php 
                                $pct = $item->qty > 0 ? min(100, (($item->qty_received ?? 0) / $item->qty) * 100) : 0; 
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="text-[9px] font-bold text-slate-400">{{ round($pct) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-bold text-sm">
                        <td colspan="4" class="py-3 px-4 text-right text-slate-700">Total PO:</td>
                        <td class="py-3 px-4 text-right" style="color: var(--red-main);">{{ number_format($purchaseOrder->total_amount, 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- GR History Card --}}
    @if($purchaseOrder->goodsReceipts->count() > 0)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                <span class="material-icons text-rose-600">history</span> Riwayat Goods Receipt (GR)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">No. GR</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tanggal Terima</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Lokasi Gudang</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Status</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center w-[100px]">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchaseOrder->goodsReceipts as $gr)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs font-mono font-bold" style="color: var(--navy-dark);">{{ $gr->no_gr }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $gr->tanggal_terima ? $gr->tanggal_terima->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $gr->storageLocation->nama ?? '-' }}</td>
                        <td class="py-3 px-4 text-xs">
                            @php
                                $grStatusClass = $gr->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border {{ $grStatusClass }} {{ $gr->status === 'posted' ? 'border-emerald-200' : 'border-slate-200' }}">
                                {{ $gr->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-center">
                            <button type="button" class="bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 px-2 py-1 rounded text-[10px] font-bold transition-colors" onclick="showGrDetail({{ $gr->id }})">Lihat</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    </div>

    {{-- MODAL: DETAIL GR --}}
    <div class="modal-overlay" id="grDetailModal">
        <div style="background: white; border-radius: 16px; padding: 28px; width: 100%; max-width: 600px; box-shadow: 0 10px 30px rgba(0,0,0,.2); max-height: 90vh; overflow-y: auto;">
            <h3 style="font-size: 16px; font-weight: 800; color: var(--navy-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <span class="material-icons" style="font-size: 20px; color: var(--red-main);">info</span> Detail Goods Receipt
            </h3>
            <div class="mb-5">
                <table class="w-full border-collapse">
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-3 text-xs font-bold text-slate-400 w-[35%]">Nomor GR</td>
                        <td class="py-2.5 text-xs font-semibold text-slate-800" id="detail_no_gr" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-3 text-xs font-bold text-slate-400 w-[35%]">Tanggal Terima</td>
                        <td class="py-2.5 text-xs font-semibold text-slate-800" id="detail_tanggal_terima"></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-3 text-xs font-bold text-slate-400 w-[35%]">Storage Location</td>
                        <td class="py-2.5 text-xs font-semibold text-slate-800" id="detail_location"></td>
                    </tr>
                    <tr>
                        <td class="py-2.5 pr-3 text-xs font-bold text-slate-400 w-[35%]">Status</td>
                        <td class="py-2.5 text-xs font-semibold text-slate-800" id="detail_status"></td>
                    </tr>
                </table>
            </div>

            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 mt-4">
                <p class="text-[11px] font-black" style="color: var(--navy-dark); margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">RINCIAN MATERIAL DITERIMA</p>
                <table class="w-full border-collapse text-[11px]">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="py-2 pr-2 text-left font-bold text-slate-500">Kode</th>
                            <th class="py-2 pr-2 text-left font-bold text-slate-500">Nama Material</th>
                            <th class="py-2 text-right font-bold text-slate-500">Qty Diterima</th>
                        </tr>
                    </thead>
                    <tbody id="detail_items_table_body">
                        {{-- Rows injected by JS --}}
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm w-full" onclick="closeGrModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openGrModal() {
            document.getElementById('grDetailModal').classList.add('open');
        }
        
        function closeGrModal() {
            document.getElementById('grDetailModal').classList.remove('open');
        }

        function showGrDetail(grId) {
            fetch(`goods-receipts/${grId}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('detail_no_gr').innerText = data.no_gr;
                    
                    const dateParts = data.tanggal_terima.split('-');
                    document.getElementById('detail_tanggal_terima').innerText = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                    
                    document.getElementById('detail_location').innerText = data.storage_location_nama;
                    document.getElementById('detail_status').innerText = data.status;

                    const tbody = document.getElementById('detail_items_table_body');
                    tbody.innerHTML = '';
                    
                    data.items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid #eee';
                        tr.innerHTML = `
                            <td style="padding: 8px; font-family: monospace; font-weight: bold; color: var(--navy-dark);">${item.material_kode}</td>
                            <td style="padding: 8px;">${item.material_nama}</td>
                            <td style="padding: 8px; text-align: right; font-weight: bold; color: #10b981;">${parseFloat(item.qty).toLocaleString('id-ID', {minimumFractionDigits: 3, maximumFractionDigits: 3})}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    openGrModal();
                })
                .catch(err => {
                    alert('Gagal memuat rincian GR.');
                });
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('open');
            }
        }
    </script>
@endsection
