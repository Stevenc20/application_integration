@extends('layouts.app')

@section('title', 'Detail SKM ' . $skm->skm_number)

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
                <div class="text-[11px] font-bold text-rose-300 uppercase tracking-wider">Nomor SKM</div>
                <h1 class="text-2xl font-black text-white tracking-tight font-mono">{{ $skm->skm_number }}</h1>
            </div>
        </div>
        <div class="relative flex items-center gap-2 flex-wrap">
            <a href="{{ route('summary_kanban.excel', $skm) }}" class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm inline-flex items-center gap-1.5">
                <span class="material-icons text-base">download</span> Excel
            </a>
            <a href="{{ route('summary_kanban.pdf', $skm) }}" target="_blank" class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm inline-flex items-center gap-1.5">
                <span class="material-icons text-base">picture_as_pdf</span> PDF
            </a>
            @if(in_array($skm->status, ['draft','sent']) && $skm->purchaseOrders->isEmpty())
            <form method="POST" action="{{ route('summary_kanban.generate-po', $skm) }}" onsubmit="return confirm('Buat Purchase Order dari SKM ini?')" class="inline">
                @csrf
                <button class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm inline-flex items-center gap-1.5">
                    <span class="material-icons text-base">assignment</span> Generate PO
                </button>
            </form>
            @endif
            @if($skm->status === 'draft' && $skm->purchaseOrders->isEmpty())
            <form method="POST" action="{{ route('summary_kanban.status', $skm) }}" class="inline">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="sent">
                <button class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm">Tandai Dikirim</button>
            </form>
            <form method="POST" action="{{ route('summary_kanban.status', $skm) }}" onsubmit="return confirm('Batalkan SKM ini?')" class="inline">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button class="bg-white/10 hover:bg-white/20 text-rose-200 font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm">Batalkan</button>
            </form>
            @elseif(in_array($skm->status, ['sent','partial_received']))
            <form method="POST" action="{{ route('summary_kanban.status', $skm) }}" onsubmit="return confirm('Batalkan SKM ini?')" class="inline">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button class="bg-white/10 hover:bg-white/20 text-rose-200 font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm">Batalkan</button>
            </form>
            @endif
            @if($skm->status === 'draft' && $skm->purchaseOrders->isEmpty())
            <form method="POST" action="{{ route('summary_kanban.destroy', $skm) }}" onsubmit="return confirm('Hapus SKM {{ $skm->skm_number }}?')" class="inline">
                @csrf @method('DELETE')
                <button class="bg-red-500/30 hover:bg-red-500/50 text-red-100 font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm">Hapus</button>
            </form>
            @endif
            <a href="{{ route('summary_kanban.index') }}" class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm">Kembali</a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm text-sm font-semibold">
        <span class="material-icons text-emerald-400 text-lg">check_circle</span> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm text-sm font-semibold">
        <span class="material-icons text-red-400 text-lg">error</span> {{ session('error') }}
    </div>
    @endif

    {{-- Info card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 flex flex-wrap gap-5 justify-between items-start">
            <div class="text-sm text-slate-500 space-y-1">
                <span>Tanggal Order: <strong class="text-slate-800">{{ $skm->order_date->format('d M Y') }}</strong></span>
                @php $firstItem = $skm->items->first(); @endphp
                @if($firstItem?->expected_delivery_date)
                <span class="ml-4">Est. Pengiriman: <strong class="text-blue-600">{{ $firstItem->expected_delivery_date->format('d M Y') }}</strong></span>
                @endif
                @if($firstItem?->storageLocation)
                <span class="ml-4">Lokasi Gudang: <strong class="text-blue-600">{{ $firstItem->storageLocation->code }} — {{ $firstItem->storageLocation->name }}</strong></span>
                @endif
                <br>
                <span>Dibuat oleh: <strong class="text-slate-800">{{ $skm->createdBy->name ?? '-' }}</strong></span>
                <span class="ml-4">Dibuat pada: <strong class="text-slate-800">{{ $skm->created_at->format('d/m/Y H:i') }}</strong></span>
                <span class="ml-4">
                    Status:
                    @php
                        $cls = match($skm->status) {
                            'draft'            => 'bg-slate-100 text-slate-500',
                            'sent'             => 'bg-blue-100 text-blue-700',
                            'partial_received' => 'bg-amber-100 text-amber-700',
                            'completed'        => 'bg-emerald-100 text-emerald-700',
                            'cancelled'        => 'bg-red-100 text-red-700',
                            default            => 'bg-slate-100 text-slate-500',
                        };
                    @endphp
                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold {{ $cls }}">{{ $skm->status_label }}</span>
                </span>
            </div>
        </div>
        @if($skm->notes)
        <div class="px-5 pb-4 text-sm text-slate-400 italic">{{ $skm->notes }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-5 pb-5">
            <div class="bg-blue-50 rounded-xl p-4 text-center">
                <div class="text-2xl font-black text-blue-600">{{ $skm->items->count() }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">Total Item</div>
            </div>
            <div class="bg-purple-50 rounded-xl p-4 text-center">
                <div class="text-2xl font-black text-purple-600">{{ $skm->items->sum('num_cards') }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">Total Kartu</div>
            </div>
            <div class="bg-emerald-50 rounded-xl p-4 text-center">
                <div class="text-2xl font-black text-emerald-600">{{ $skm->items->pluck('vendor_id')->filter()->unique()->count() }}</div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">Vendor Terlibat</div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-800">Detail Item SKM</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800 text-white">
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">#</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">Material</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">Vendor</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-right">Stok Saat SKM</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-right">Min. Stok</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-right">Qty/Kartu</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-right">Jml Kartu</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-right">Total Order</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($skm->items as $i => $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-2.5 px-3.5 text-slate-400 text-[11px]">{{ $i + 1 }}</td>
                        <td class="py-2.5 px-3.5">
                            <div class="font-mono font-bold text-slate-800 text-xs">{{ $item->material->code ?? '-' }}</div>
                            <div class="text-slate-500 text-[11px]">{{ $item->material->name ?? '-' }}</div>
                            <div class="text-slate-400 text-[10px]">{{ $item->material->unit_of_measure ?? '' }}</div>
                        </td>
                        <td class="py-2.5 px-3.5 text-slate-500 text-[11px]">{{ $item->vendor->name ?? '-' }}</td>
                        <td class="py-2.5 px-3.5 text-right font-bold {{ (float)$item->current_stock < (float)$item->min_stock ? 'text-red-500' : 'text-emerald-600' }}">
                            {{ fmt_qty($item->current_stock) }}
                        </td>
                        <td class="py-2.5 px-3.5 text-right text-slate-500">{{ fmt_qty($item->min_stock) }}</td>
                        <td class="py-2.5 px-3.5 text-right">{{ number_format($item->kanban_qty, 0) }}</td>
                        <td class="py-2.5 px-3.5 text-right font-bold text-blue-600">{{ $item->num_cards }}</td>
                        <td class="py-2.5 px-3.5 text-right font-black text-slate-800 text-sm">{{ number_format($item->order_qty, 0) }}</td>
                        <td class="py-2.5 px-3.5 text-slate-400 text-[11px]">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-6 text-slate-400 italic">Tidak ada item.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Linked POs --}}
    @if($skm->purchaseOrders->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-800">Purchase Order Terkait</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 text-slate-500">
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">No. PO</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">Vendor</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">Est. Pengiriman</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap">Lokasi Tujuan</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-center">Status PO</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-right">Total</th>
                        <th class="py-2.5 px-3.5 font-bold whitespace-nowrap text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($skm->purchaseOrders as $po)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-2.5 px-3.5 font-mono font-bold text-blue-600">{{ $po->po_number ?? $po->no_po ?? '-' }}</td>
                        <td class="py-2.5 px-3.5">{{ $po->vendor->name ?? '-' }}</td>
                        <td class="py-2.5 px-3.5 text-slate-500 text-[11px]">
                            {{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('d M Y') : '-' }}
                        </td>
                        <td class="py-2.5 px-3.5 text-slate-500 text-[11px]">
                            {{ $po->storageLocation ? $po->storageLocation->code . ' - ' . $po->storageLocation->name : '-' }}
                        </td>
                        <td class="py-2.5 px-3.5 text-center">
                            @php
                                $poStatus = $po->status ?? 'draft';
                                $poCls = match($poStatus) {
                                    'draft'              => 'bg-slate-100 text-slate-500',
                                    'approved'           => 'bg-blue-100 text-blue-700',
                                    'partially_received' => 'bg-amber-100 text-amber-700',
                                    'received'           => 'bg-emerald-100 text-emerald-700',
                                    'cancelled'          => 'bg-red-100 text-red-700',
                                    default              => 'bg-slate-100 text-slate-500',
                                };
                                $poLabel = match($poStatus) {
                                    'draft'              => 'Draft',
                                    'approved'           => 'Approved',
                                    'partially_received' => 'Diterima Sebagian',
                                    'received'           => 'Diterima Semua',
                                    'cancelled'          => 'Dibatalkan',
                                    default              => ucfirst($poStatus),
                                };
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold {{ $poCls }}">{{ $poLabel }}</span>
                        </td>
                        <td class="py-2.5 px-3.5 text-right font-bold">Rp {{ number_format($po->total_amount ?? 0, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-3.5 text-center">
                            <a href="{{ route('purchase_orders.show', $po) }}" class="text-blue-600 font-bold text-xs">Lihat PO</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
