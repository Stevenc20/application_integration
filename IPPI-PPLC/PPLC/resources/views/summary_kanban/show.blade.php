@extends('layouts.app')

@section('title', 'Detail SKM ' . $skm->skm_number)

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
        flex-wrap: wrap;
        gap: 12px;
    }
    .page-title { font-size: 20px; font-weight: 800; color: var(--navy-dark); }
    .content-body { padding: 24px 28px; background: #f8fafc; min-height: calc(100vh - 70px); }
    .card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,.02); margin-bottom: 24px; overflow: hidden; }
    .card-header-basic { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 15px; font-weight: 800; color: var(--navy-dark); display: flex; justify-content: space-between; align-items: center; }
    .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 20px; }
    .summary-card { border-radius: 8px; padding: 16px; text-align: center; }
    .summary-card .val { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
    .summary-card .lbl { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    thead th { background: var(--navy-dark); color: #fff; padding: 10px 14px; font-weight: 700; white-space: nowrap; }
    .thead-gray thead th { background: #f1f5f9; color: #64748b; }
    tbody td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #f8fafc; }
    .badge { display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700; }
    .badge-gray   { background:#f1f5f9;color:#64748b; }
    .badge-blue   { background:#dbeafe;color:#1d4ed8; }
    .badge-yellow { background:#fef9c3;color:#a16207; }
    .badge-green  { background:#dcfce7;color:#166534; }
    .badge-red    { background:#fee2e2;color:#dc2626; }
    .btn { display:inline-flex;align-items:center;gap:6px;border:none;border-radius:6px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s; }
    .btn:hover { opacity:.85; }
    .btn-blue   { background:#2563eb;color:#fff; }
    .btn-green  { background:#10b981;color:#fff; }
    .btn-red    { background:#ef4444;color:#fff; }
    .btn-purple { background:#7c3aed;color:#fff; }
    .btn-gray   { background:#e2e8f0;color:#334155; }
    .actions-wrap { display:flex;flex-wrap:wrap;gap:8px; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div style="font-size:11px;color:#94a3b8;margin-bottom:2px">Nomor SKM</div>
        <div style="font-size:22px;font-weight:800;color:#2563eb;font-family:monospace">{{ $skm->skm_number }}</div>
    </div>
    <div class="actions-wrap">
        {{-- Export --}}
        <a href="{{ route('summary_kanban.excel', $skm) }}" class="btn btn-green">
            <span class="material-icons" style="font-size:16px">download</span> Excel
        </a>
        <a href="{{ route('summary_kanban.pdf', $skm) }}" target="_blank" class="btn btn-red">
            <span class="material-icons" style="font-size:16px">picture_as_pdf</span> PDF
        </a>

        {{-- Generate PO --}}
        @if(in_array($skm->status, ['draft','sent']) && $skm->purchaseOrders->isEmpty())
        <form method="POST" action="{{ route('summary_kanban.generate-po', $skm) }}"
              onsubmit="return confirm('Buat Purchase Order dari SKM ini?')">
            @csrf
            <button class="btn btn-purple">
                <span class="material-icons" style="font-size:16px">assignment</span> Generate PO
            </button>
        </form>
        @endif

        {{-- Status actions --}}
        @if($skm->status === 'draft' && $skm->purchaseOrders->isEmpty())
        <form method="POST" action="{{ route('summary_kanban.status', $skm) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="sent">
            <button class="btn btn-blue">Tandai Dikirim</button>
        </form>
        <form method="POST" action="{{ route('summary_kanban.status', $skm) }}"
              onsubmit="return confirm('Batalkan SKM ini?')">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="cancelled">
            <button class="btn btn-gray">Batalkan</button>
        </form>
        @elseif(in_array($skm->status, ['sent','partial_received']))
        <form method="POST" action="{{ route('summary_kanban.status', $skm) }}"
              onsubmit="return confirm('Batalkan SKM ini?')">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="cancelled">
            <button class="btn btn-gray">Batalkan</button>
        </form>
        @endif

        {{-- Delete --}}
        @if($skm->status === 'draft' && $skm->purchaseOrders->isEmpty())
        <form method="POST" action="{{ route('summary_kanban.destroy', $skm) }}"
              onsubmit="return confirm('Hapus SKM {{ $skm->skm_number }}?')">
            @csrf @method('DELETE')
            <button class="btn btn-red">Hapus</button>
        </form>
        @endif

        <a href="{{ route('summary_kanban.index') }}" class="btn btn-gray">Kembali</a>
    </div>
</div>

<div class="content-body">

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#166534;font-weight:600;font-size:13px;">
        ✓ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#dc2626;font-weight:600;font-size:13px;">
        ✗ {{ session('error') }}
    </div>
    @endif

    {{-- Info card --}}
    <div class="card">
        <div style="padding:20px;display:flex;flex-wrap:wrap;gap:20px;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:13px;color:#64748b;margin-bottom:8px">
                    <span>Tanggal Order: <b style="color:#1e293b">{{ $skm->order_date->format('d M Y') }}</b></span>
                    @php $firstItem = $skm->items->first(); @endphp
                    @if($firstItem?->expected_delivery_date)
                    <span>Est. Pengiriman: <b style="color:#2563eb">{{ $firstItem->expected_delivery_date->format('d M Y') }}</b></span>
                    @endif
                    @if($firstItem?->storageLocation)
                    <span>Lokasi Gudang: <b style="color:#2563eb">{{ $firstItem->storageLocation->code }} — {{ $firstItem->storageLocation->name }}</b></span>
                    @endif
                    <span>Dibuat oleh: <b style="color:#1e293b">{{ $skm->createdBy->name ?? '-' }}</b></span>
                    <span>Dibuat pada: <b style="color:#1e293b">{{ $skm->created_at->format('d/m/Y H:i') }}</b></span>
                    <span>
                        Status:
                        @php
                            $cls = match($skm->status) {
                                'draft'            => 'badge-gray',
                                'sent'             => 'badge-blue',
                                'partial_received' => 'badge-yellow',
                                'completed'        => 'badge-green',
                                'cancelled'        => 'badge-red',
                                default            => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $cls }}">{{ $skm->status_label }}</span>
                    </span>
                </div>
                @if($skm->notes)
                <div style="font-size:13px;color:#94a3b8;font-style:italic">{{ $skm->notes }}</div>
                @endif
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card" style="background:#eff6ff">
                <div class="val" style="color:#2563eb">{{ $skm->items->count() }}</div>
                <div class="lbl">Total Item</div>
            </div>
            <div class="summary-card" style="background:#faf5ff">
                <div class="val" style="color:#7c3aed">{{ $skm->items->sum('num_cards') }}</div>
                <div class="lbl">Total Kartu</div>
            </div>
            <div class="summary-card" style="background:#f0fdf4">
                <div class="val" style="color:#16a34a">{{ $skm->items->pluck('vendor_id')->filter()->unique()->count() }}</div>
                <div class="lbl">Vendor Terlibat</div>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="card-header-basic">Detail Item SKM</div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Material</th>
                        <th>Vendor</th>
                        <th style="text-align:right">Stok Saat SKM</th>
                        <th style="text-align:right">Min. Stok</th>
                        <th style="text-align:right">Qty/Kartu</th>
                        <th style="text-align:right">Jml Kartu</th>
                        <th style="text-align:right">Total Order</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skm->items as $i => $item)
                    <tr>
                        <td style="color:#94a3b8;font-size:11px">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-family:monospace;font-weight:700;color:#1e3a5f;font-size:12px">{{ $item->material->code ?? '-' }}</div>
                            <div style="font-size:11px;color:#64748b">{{ $item->material->name ?? '-' }}</div>
                            <div style="font-size:10px;color:#94a3b8">{{ $item->material->unit_of_measure ?? '' }}</div>
                        </td>
                        <td style="font-size:11px;color:#64748b">{{ $item->vendor->name ?? '-' }}</td>
                        <td style="text-align:right;{{ (float)$item->current_stock < (float)$item->min_stock ? 'color:#dc2626' : 'color:#16a34a' }};font-weight:700">
                            {{ fmt_qty($item->current_stock) }}
                        </td>
                        <td style="text-align:right;color:#64748b">{{ fmt_qty($item->min_stock) }}</td>
                        <td style="text-align:right">{{ number_format($item->kanban_qty, 0) }}</td>
                        <td style="text-align:right;font-weight:700;color:#2563eb">{{ $item->num_cards }}</td>
                        <td style="text-align:right;font-weight:800;color:#1e3a5f;font-size:14px">{{ number_format($item->order_qty, 0) }}</td>
                        <td style="font-size:11px;color:#94a3b8">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;font-style:italic">Tidak ada item.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Linked POs --}}
    @if($skm->purchaseOrders->isNotEmpty())
    <div class="card">
        <div class="card-header-basic">Purchase Order Terkait</div>
        <div style="overflow-x:auto">
            <table>
                <thead class="thead-gray">
                    <tr>
                        <th>No. PO</th>
                        <th>Vendor</th>
                        <th>Est. Pengiriman</th>
                        <th>Lokasi Tujuan</th>
                        <th style="text-align:center">Status PO</th>
                        <th style="text-align:right">Total</th>
                        <th style="text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($skm->purchaseOrders as $po)
                    <tr>
                        <td style="font-family:monospace;font-weight:700;color:#2563eb">{{ $po->po_number ?? $po->no_po ?? '-' }}</td>
                        <td>{{ $po->vendor->name ?? '-' }}</td>
                        <td style="font-size:11px;color:#64748b">
                            {{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('d M Y') : '-' }}
                        </td>
                        <td style="font-size:11px;color:#64748b">
                            {{ $po->storageLocation ? $po->storageLocation->code . ' - ' . $po->storageLocation->name : '-' }}
                        </td>
                        <td style="text-align:center">
                            @php
                                $poStatus = $po->status ?? 'draft';
                                $poCls = match($poStatus) {
                                    'draft'              => 'badge-gray',
                                    'approved'           => 'badge-blue',
                                    'partially_received' => 'badge-yellow',
                                    'received'           => 'badge-green',
                                    'cancelled'          => 'badge-red',
                                    default              => 'badge-gray',
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
                            <span class="badge {{ $poCls }}">{{ $poLabel }}</span>
                        </td>
                        <td style="text-align:right;font-weight:700">Rp {{ number_format($po->total_amount ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align:center">
                            <a href="{{ route('purchase_orders.show', $po) }}" style="color:#2563eb;font-weight:600;font-size:12px">Lihat PO</a>
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
