@extends('layouts.app')

@section('title', 'Purchase Order')

@push('styles')
<style>
    /* ===== HERO ===== */
    .hero {
        background: var(--red-main);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }
    .hero-title-block h2 {
        font-size: 28px;
        font-weight: 900;
        color: white;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
    }
    .hero-title-block h2 .material-icons { font-size: 32px; opacity: 0.8; }
    .hero-meta {
        color: rgba(255,255,255,0.75);
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
    }

    .content-body {
        padding: 24px 28px;
        background: #f8fafc;
        min-height: calc(100vh - 70px);
    }

    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
    }
    
    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--red-main);
    }
    
    .btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        border: none;
    }

    .btn-green { background: #10b981; color: white; }
    .btn-green:hover { background: #059669; }
    
    .btn-red { background: #dc2626; color: white; }
    .btn-red:hover { background: #b91c1c; }
    
    .btn-blue { background: #2563eb; color: white; }
    .btn-blue:hover { background: #1d4ed8; }

    .btn-gray { background: #64748b; color: white; }
    .btn-gray:hover { background: #475569; }

    /* FILTERS */
    .filter-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        width: 100%;
    }
    .filter-row .filter-input {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 13px;
        background: #fff;
        outline: none;
        box-sizing: border-box;
        height: 38px;
        flex: 1 1 150px;
        min-width: 150px;
    }
    .filter-row .filter-input:focus {
        border-color: var(--red-main);
    }
    .filter-row .custom-select-container.filter-select {
        flex: 1 1 180px;
        min-width: 180px;
        max-width: 280px;
    }
    .filter-row .custom-select-container.filter-select .custom-select-btn {
        width: 100%;
        height: 38px;
        box-sizing: border-box;
    }

    /* TABLE */
    .table-wrap {
        overflow-x: auto;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    th {
        background: #f8fafc;
        padding: 12px 16px;
        font-weight: 700;
        color: #475569;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    td {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }
    tr:last-child td {
        border-bottom: none;
    }
    tr:hover td {
        background: #f8fafc;
    }

    .font-mono {
        font-family: monospace;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-draft { background: #f1f5f9; color: #475569; }
    .badge-approved { background: #dbeafe; color: #1d4ed8; }
    .badge-received { background: #dcfce7; color: #15803d; }
    .badge-cancelled { background: #fef2f2; color: #b91c1c; }
    .badge-partial { background: #fef9c3; color: #a16207; }

    /* Alerts */
    .alert {
        margin: 20px 28px 0;
        padding: 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
</style>
@endpush

@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="alert alert-success">
        <span class="material-icons">check_circle</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        <span class="material-icons">error</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">shopping_cart</span> Purchase Order</h2>
            <div class="hero-meta">Kelola pesanan pembelian barang ke vendor, pelacakan pengiriman, status PO, dan rincian kuantitas item</div>
        </div>
    </div>

    <div class="content-body">
        <div class="card">
            <div class="card-top">
                <div class="card-title">Daftar Purchase Order</div>
                <div class="btn-group">
                    <a href="{{ route('purchase_orders.export', request()->query()) }}" class="btn btn-green">
                        <span class="material-icons">file_download</span> Export Excel
                    </a>
                    <a href="{{ route('purchase_orders.print_pdf', request()->query()) }}" target="_blank" class="btn btn-red">
                        <span class="material-icons">print</span> Print PDF
                    </a>
                    <a href="{{ route('purchase_orders.create') }}" class="btn btn-blue">
                        <span class="material-icons">add</span> Buat PO
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('purchase_orders.index') }}" class="filter-row" id="filterForm">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="No. PO / Nama Vendor..." class="filter-input" style="flex: 2; min-width: 240px;">
                <button type="submit" class="btn-search-go"><span class="material-icons">search</span>Cari</button>
                <a href="{{ route('purchase_orders.index') }}" class="btn btn-gray" style="height: 38px;">Back</a>

                <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal order" class="filter-input">
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai tanggal order" class="filter-input">
                
                <select name="vendor_id" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Vendor</option>
                    @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ (string) request('vendor_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->nama }}</option>
                    @endforeach
                </select>

                <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ strtolower(request('status')) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ strtolower(request('status')) === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="partially_received" {{ (strtolower(request('status')) === 'partially_received' || strtolower(request('status')) === 'partial_received' || strtolower(request('status')) === 'partially received') ? 'selected' : '' }}>Partial Received</option>
                    <option value="received" {{ strtolower(request('status')) === 'received' ? 'selected' : '' }}>Received</option>
                    <option value="cancelled" {{ strtolower(request('status')) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. PO</th>
                            <th>Vendor</th>
                            <th>Tgl Order</th>
                            <th>Est. Terima</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pos as $po)
                        <tr>
                            <td class="font-mono" style="font-weight: 700; color: var(--navy-dark);">{{ $po->po_number }}</td>
                            <td>{{ $po->vendor->nama ?? '-' }}</td>
                            <td>{{ $po->order_date ? $po->order_date->format('d/m/Y') : '-' }}</td>
                            <td>{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d/m/Y') : '-' }}</td>
                            <td style="color: #64748b; font-size: 12px;">{{ Str::limit($po->notes ?? '-', 30) }}</td>
                            <td>
                                <span class="badge 
                                    {{ $po->status === 'draft' ? 'badge-draft' : '' }}
                                    {{ $po->status === 'approved' ? 'badge-approved' : '' }}
                                    {{ $po->status === 'received' ? 'badge-received' : '' }}
                                    {{ $po->status === 'cancelled' ? 'badge-cancelled' : '' }}
                                    {{ $po->status === 'partially_received' ? 'badge-partial' : '' }}
                                ">{{ ucwords(str_replace('_', ' ', $po->status)) }}</span>
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('purchase_orders.show', $po->id) }}" class="btn btn-blue" style="padding: 4px 12px; font-size: 11px;">Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 48px; display: block; margin-bottom: 8px;">sentiment_dissatisfied</span>
                                Belum ada Purchase Order.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $pos->links() }}</div>
        </div>
    </div>
@endsection
