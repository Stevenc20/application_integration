@extends('layouts.app')

@section('title', 'Production Order')

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

    .btn-red { background: #dc2626; color: white; }
    .btn-red:hover { background: #b91c1c; }
    
    .btn-blue { background: #2563eb; color: white; }
    .btn-blue:hover { background: #1d4ed8; }

    /* FILTERS */
    .filter-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .filter-input, .filter-select {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 13px;
        background: #fff;
        outline: none;
    }
    .filter-input:focus, .filter-select:focus {
        border-color: #94a3b8;
    }
    .filter-search-input {
        flex: 1;
        min-width: 250px;
    }
    .btn-search {
        background: #334155;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-search:hover { background: #1e293b; }
    
    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-reset:hover { background: #e2e8f0; }

    /* TABLE */
    .table-responsive {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    thead th {
        background: var(--red-main);
        color: white;
        padding: 14px 20px;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }
    tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .col-order {
        color: #3b82f6;
        font-family: monospace;
        font-weight: 700;
        font-size: 13px;
    }
    .col-material-kode {
        color: #64748b;
        font-size: 11px;
        font-family: monospace;
        display: block;
        margin-bottom: 4px;
    }
    .col-material-nama {
        color: #334155;
        font-weight: 800;
        font-size: 13px;
    }
    .col-center {
        text-align: center;
    }
    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .action-links a {
        text-decoration: none;
        font-weight: 600;
        font-size: 11px;
    }
    .action-detail { color: #3b82f6; font-weight: 800; }
    .action-edit { color: #d97706; font-weight: 800; }
    .action-delete { color: #dc2626; font-weight: 800; background: none; border: none; padding: 0; font-family: inherit; cursor: pointer; font-size: 11px; }
    .action-divider { color: #cbd5e1; margin: 0 4px; }
    .action-print { color: #10b981; font-weight: 800; }

    .bulk-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 13px;
    }
    .bulk-count {
        color: #1e3a8a;
        font-weight: 700;
    }
    .btn-bulk-release {
        background: #1d4ed8;
        color: white;
        border: none;
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-bulk-release:hover { background: #1e40af; }
    .btn-bulk-cancel {
        background: transparent;
        color: #4b5563;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-bulk-cancel:hover { color: #111827; }

    /* TABLE FOOTER & PAGINATION */
    .table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        padding-top: 16px;
        font-size: 13px;
        color: #64748b;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .pagination {
        display: flex;
        gap: 6px;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pagination .page-item .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s;
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: var(--red-main);
    }
    .pagination .page-item.active .page-link {
        background: var(--red-main);
        color: white;
        border-color: var(--red-main);
    }
    .pagination .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f8fafc;
        cursor: not-allowed;
    }

</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">precision_manufacturing</span> Production Order</h2>
            <div class="hero-meta">Daftar perintah produksi untuk pengolahan material</div>
        </div>
    </div>

    <div class="content-body">
        
        <div class="card">
            <div class="card-top">
                <div class="card-title">Daftar Production Order</div>
                <div class="btn-group">
                    <a href="{{ route('production_orders.print_all', ['search' => request('search'), 'status' => request('status')]) }}" target="_blank" class="btn btn-red"><span class="material-icons" style="font-size:14px;">picture_as_pdf</span> Print PDF</a>
                    <a href="{{ route('production_orders.create') }}" class="btn btn-blue"><span style="font-size:14px;font-weight:bold;">+</span> Buat Production Order</a>
                </div>
            </div>

            <form action="{{ route('production_orders.index') }}" method="GET" class="filter-row">
                <input type="text" name="search" value="{{ request('search') }}" class="filter-input filter-search-input" placeholder="No. Order / material...">
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="filter-input">
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="filter-input">
                 <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ in_array(request('status'), ['draft', 'created']) ? 'selected' : '' }}>Created / Draft</option>
                    <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Released</option>
                    <option value="in_progress" {{ in_array(request('status'), ['in_progress', 'goods_issued']) ? 'selected' : '' }}>In Progress / Goods Issued</option>
                    <option value="completed" {{ in_array(request('status'), ['completed', 'confirmed']) ? 'selected' : '' }}>Completed / Confirmed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <button type="submit" class="btn-search"><span class="material-icons">search</span>Cari</button>
                <a href="{{ route('production_orders.index') }}" class="btn-reset">Kembali</a>
            </form>

            {{-- Bulk Action Bar --}}
            <form method="POST" action="{{ route('production_orders.bulk_release') }}" id="bulkForm" onsubmit="return confirm('Release semua Production Order yang dipilih?')">
                @csrf
                <div class="bulk-bar" id="bulkBar" style="display:none">
                    <span class="bulk-count" id="bulkCount">0 dipilih</span>
                    <button type="submit" class="btn-bulk-release">Release Semua yang Dipilih</button>
                    <button type="button" onclick="clearSelection()" class="btn-bulk-cancel">Batal Pilih</button>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">
                                    <input type="checkbox" id="checkAll" style="cursor: pointer;" title="Pilih semua Created">
                                </th>
                                <th>No. Order</th>
                                <th>Material</th>
                                <th class="col-center">Qty Plan</th>
                                <th class="col-center">Qty Prod</th>
                                <th class="col-center">Tgl Mulai</th>
                                <th class="col-center">Tgl Selesai</th>
                                <th class="col-center">Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td style="text-align: center;">
                                    @if($order->status === 'created')
                                    <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="row-check" style="cursor: pointer;">
                                    @endif
                                </td>
                                <td><span class="col-order">{{ $order->order_number }}</span></td>
                                <td>
                                    <span class="col-material-kode">{{ $order->material->kode ?? '-' }}</span>
                                    <span class="col-material-nama">{{ $order->material->nama ?? '-' }}</span>
                                </td>
                                <td class="col-center" style="font-weight:600;">{{ number_format($order->quantity_planned, 3, ',', '.') }}</td>
                                <td class="col-center" style="font-weight:600;">{{ number_format($order->quantity_produced, 3, ',', '.') }}</td>
                                <td class="col-center">{{ $order->planned_start_date ? $order->planned_start_date->format('d/m/Y') : '-' }}</td>
                                <td class="col-center">{{ $order->planned_end_date ? $order->planned_end_date->format('d/m/Y') : '-' }}</td>
                                <td class="col-center">
                                    @php
                                        $statusColors = [
                                            'draft' => 'background: #f1f5f9; color: #64748b;',
                                            'created' => 'background: #f1f5f9; color: #64748b;',
                                            'released' => 'background: #e0f2fe; color: #0284c7;',
                                            'in_progress' => 'background: #fef9c3; color: #a16207;',
                                            'goods_issued' => 'background: #fef9c3; color: #a16207;',
                                            'confirmed' => 'background: #dcfce7; color: #16a34a;',
                                            'completed' => 'background: #dcfce7; color: #15803d; border: 1px solid #16a34a;',
                                            'cancelled' => 'background: #fee2e2; color: #dc2626;'
                                        ];
                                        $style = $statusColors[strtolower($order->status)] ?? 'background: #f1f5f9; color: #64748b;';
                                    @endphp
                                    <span class="badge-status" style="{{ $style }}">
                                        {{ str_replace('_', ' ', $order->status) }}
                                    </span>
                                </td>
                                <td class="action-links">
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <a href="{{ route('production_orders.show', $order->id) }}" class="action-detail">Detail</a>
                                        @if($order->status === 'created')
                                        <span class="action-divider">|</span>
                                        <a href="{{ route('production_orders.edit', $order->id) }}" class="action-edit">Edit</a>
                                        <span class="action-divider">|</span>
                                        <form method="POST" action="{{ route('production_orders.destroy', $order->id) }}" style="display: inline;" onsubmit="return confirm('Hapus Production Order {{ $order->order_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-delete">Hapus</button>
                                        </form>
                                        @endif
                                        <span class="action-divider">|</span>
                                        <a target="_blank" href="{{ route('production_orders.print', $order->id) }}" class="action-print">Print</a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">Belum ada data Production Order.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="table-footer">
                <div class="showing-data">
                    Menampilkan {{ $orders->firstItem() ?? 0 }} &ndash; {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} data
                </div>
                
                @if($orders->hasPages())
                <div class="pagination-wrap">
                    {{ $orders->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
                @endif
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        const checkAll  = document.getElementById('checkAll');
        const bulkBar   = document.getElementById('bulkBar');
        const bulkCount = document.getElementById('bulkCount');

        function updateBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            if (checked.length > 0) {
                bulkBar.style.removeProperty('display');
                bulkCount.textContent = checked.length + ' dipilih';
            } else {
                bulkBar.style.display = 'none';
                if (checkAll) checkAll.checked = false;
            }
        }

        function clearSelection() {
            document.querySelectorAll('.row-check:checked').forEach(c => c.checked = false);
            if (checkAll) checkAll.checked = false;
            updateBar();
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
                updateBar();
            });
        }

        document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', updateBar));
    </script>
    @endpush
@endsection
