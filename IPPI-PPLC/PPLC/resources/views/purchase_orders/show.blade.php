@extends('layouts.app')

@section('title', 'Detail Purchase Order')

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
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--navy-dark);
        margin-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .header-info-wrap {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 24px;
    }

    .po-title-block {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .po-label {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
    }
    .po-number {
        font-size: 24px;
        font-weight: 900;
        color: var(--navy-dark);
        font-family: monospace;
    }
    .po-vendor-name {
        font-size: 16px;
        font-weight: 700;
        color: #475569;
    }

    .po-status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-draft { background: #f1f5f9; color: #475569; }
    .status-approved { background: #dbeafe; color: #1d4ed8; }
    .status-received { background: #dcfce7; color: #15803d; }
    .status-cancelled { background: #fef2f2; color: #b91c1c; }
    .status-partial { background: #fef9c3; color: #a16207; }

    .po-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        border: none;
        transition: all 0.15s ease;
    }

    .btn-blue { background: #2563eb; color: white; }
    .btn-blue:hover { background: #1d4ed8; }
    .btn-green { background: #10b981; color: white; }
    .btn-green:hover { background: #059669; }
    .btn-red { background: #dc2626; color: white; }
    .btn-red:hover { background: #b91c1c; }
    .btn-yellow { background: #eab308; color: white; }
    .btn-yellow:hover { background: #ca8a04; }
    .btn-gray { background: #64748b; color: white; }
    .btn-gray:hover { background: #475569; }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        border-top: 1px solid #e2e8f0;
        padding-top: 20px;
        margin-top: 20px;
    }
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .info-label {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
    }
    .info-value {
        font-size: 14px;
        font-weight: 700;
        color: #334155;
    }

    /* TABLE */
    .table-wrap {
        overflow-x: auto;
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

    .font-mono {
        font-family: monospace;
    }

    .progress-bar-wrap {
        width: 100%;
        background: #e2e8f0;
        border-radius: 9999px;
        height: 6px;
        overflow: hidden;
        margin-top: 6px;
    }
    .progress-bar-fill {
        background: #10b981;
        height: 100%;
        border-radius: 9999px;
    }

    /* Modal Overlay & Modal */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px; }
    .modal-overlay.open { display: flex; }
    .modal { background: white; border-radius: 12px; padding: 24px; width: 100%; max-width: 600px; box-shadow: 0 10px 30px rgba(0,0,0,.2); max-height: 90vh; overflow-y: auto; }
    .modal h3 { font-size: 16px; font-weight: 800; color: var(--navy-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .modal h3 .material-icons { font-size: 20px; color: var(--red-main); }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

    .detail-table {
        width: 100%;
        border-collapse: collapse;
    }
    .detail-table tr {
        border-bottom: 1px solid #f1f5f9;
    }
    .detail-table tr:last-child {
        border-bottom: none;
    }
    .detail-table td {
        padding: 10px 8px;
        font-size: 13px;
    }
    .detail-table td.label-td {
        font-weight: 700;
        color: #64748b;
        width: 35%;
    }
    .detail-table td.value-td {
        color: #0f172a;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">receipt</span> Detail Purchase Order</h2>
            <div class="hero-meta">Informasi detail mengenai nomor PO, status persetujuan, vendor, gudang penyimpanan, dan riwayat penerimaan barang masuk</div>
        </div>
    </div>

    <div class="content-body">
        {{-- Header Card --}}
        <div class="card">
            <div class="header-info-wrap">
                <div class="po-title-block">
                    <span class="po-label">Nomor Purchase Order</span>
                    <span class="po-number">{{ $purchaseOrder->po_number }}</span>
                    <span class="po-vendor-name">{{ $purchaseOrder->vendor->nama ?? '-' }}</span>
                </div>
                <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <span class="po-status-badge 
                        {{ $purchaseOrder->status === 'draft' ? 'status-draft' : '' }}
                        {{ $purchaseOrder->status === 'approved' ? 'status-approved' : '' }}
                        {{ $purchaseOrder->status === 'received' ? 'status-received' : '' }}
                        {{ $purchaseOrder->status === 'cancelled' ? 'status-cancelled' : '' }}
                        {{ $purchaseOrder->status === 'partially_received' ? 'status-partial' : '' }}
                    ">{{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }}</span>

                    <div class="po-actions">
                        @if($purchaseOrder->status === 'draft')
                            @php
                                $today       = \Carbon\Carbon::today();
                                $delivDate   = $purchaseOrder->expected_delivery_date;
                                $daysLeft    = $delivDate ? $today->diffInDays($delivDate, false) : null;
                                $pastDeadline = $delivDate && $today->gt($delivDate);
                                $willAutoApprove = $delivDate && !$pastDeadline && $daysLeft <= 2;
                            @endphp

                            @if($pastDeadline)
                                <div style="display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; font-size: 12px; color: #dc2626; font-weight: 700;">
                                    <span class="material-icons" style="font-size: 16px;">warning</span>
                                    Tidak bisa approve — est. pengiriman terlewat
                                </div>
                            @else
                                <form method="POST" action="{{ route('purchase_orders.approve', $purchaseOrder->id) }}" style="display: inline-block;">
                                    @csrf
                                    <button class="btn btn-blue">Approve</button>
                                </form>
                            @endif

                            <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="btn btn-yellow">Edit</a>
                        @endif

                        @if($purchaseOrder->status === 'approved' && $purchaseOrder->skm_order_id)
                            <a href="{{ route('purchase_orders.edit', $purchaseOrder->id) }}" class="btn btn-yellow">Edit</a>
                        @endif

                        @if($purchaseOrder->status === 'approved' || $purchaseOrder->status === 'partially_received')
                            <a href="{{ route('goods_receipts.index', ['po_id' => $purchaseOrder->id]) }}" class="btn btn-green">Buat GR</a>
                        @endif

                        @if(!in_array($purchaseOrder->status, ['received', 'cancelled']))
                            <form method="POST" action="{{ route('purchase_orders.cancel', $purchaseOrder->id) }}" onsubmit="return confirm('Batalkan PO ini?')" style="display: inline-block;">
                                @csrf
                                <button class="btn btn-red">Cancel</button>
                            </form>
                        @endif

                        <a href="{{ route('purchase_orders.detail_pdf', $purchaseOrder->id) }}" target="_blank" class="btn btn-red" style="background: #be123c;">
                            <span class="material-icons" style="font-size:16px;">print</span> Print PDF
                        </a>
                        <a href="{{ route('purchase_orders.index') }}" class="btn btn-gray">Kembali</a>
                    </div>

                    {{-- Auto approval info --}}
                    @if($purchaseOrder->status === 'draft' && !$pastDeadline && $daysLeft !== null)
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                            <span class="material-icons" style="font-size: 14px;">schedule</span>
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

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Tgl Order</span>
                    <span class="info-value">{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d F Y') : '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Est. Terima</span>
                    <span class="info-value">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d F Y') : '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lokasi Gudang</span>
                    <span class="info-value">{{ $purchaseOrder->storageLocation->code ?? '-' }} - {{ $purchaseOrder->storageLocation->name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dibuat Oleh</span>
                    <span class="info-value">{{ $purchaseOrder->createdBy->name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dibuat Pada</span>
                    <span class="info-value">{{ $purchaseOrder->created_at ? $purchaseOrder->created_at->format('d/m/Y H:i') : '-' }}</span>
                </div>
            </div>

            @if($purchaseOrder->approved_at)
            <div style="margin-top: 16px; font-size: 12px; color: #475569; background: #f8fafc; padding: 8px 12px; border-radius: 6px; display: inline-block;">
                <strong>Disetujui:</strong> {{ $purchaseOrder->approved_at->format('d M Y H:i') }} oleh <strong>{{ $purchaseOrder->approved_by }}</strong>
            </div>
            @endif

            @if($purchaseOrder->notes)
            <div style="margin-top: 16px; font-size: 13px; color: #475569; border-top: 1px dashed #e2e8f0; padding-top: 16px;">
                <strong>Catatan:</strong> {{ $purchaseOrder->notes }}
            </div>
            @endif
        </div>

        {{-- Items Card --}}
        <div class="card">
            <div class="card-title">
                <span class="material-icons">list</span> Item Purchase Order
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th style="text-align: right; width: 120px;">Qty Order</th>
                            <th style="text-align: right; width: 120px;">Qty Terima</th>
                            <th style="text-align: right; width: 140px;">Harga Satuan</th>
                            <th style="text-align: right; width: 140px;">Total</th>
                            <th style="width: 140px; text-align: center;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>
                                <div class="font-mono" style="font-weight: 700; color: var(--navy-dark);">{{ $item->material->code ?? '-' }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $item->material->nama ?? '-' }}</div>
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($item->qty, 3) }} <span style="font-weight: normal; font-size: 11px; color: #94a3b8;">{{ $item->material->uom ?? '' }}</span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #10b981;">
                                {{ number_format($item->qty_received ?? 0, 3) }} <span style="font-weight: normal; font-size: 11px; color: #94a3b8;">{{ $item->material->uom ?? '' }}</span>
                            </td>
                            <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--navy-dark);">{{ number_format(($item->qty * $item->unit_price), 0) }}</td>
                            <td>
                                @php 
                                    $pct = $item->qty > 0 ? min(100, (($item->qty_received ?? 0) / $item->qty) * 100) : 0; 
                                @endphp
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width: {{ $pct }}%;"></div>
                                    </div>
                                    <span style="font-size: 10px; font-weight: 700; color: #64748b; margin-top: 4px;">{{ round($pct) }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8fafc; font-weight: 800; font-size: 14px;">
                            <td colspan="4" style="text-align: right; padding: 12px 16px;">Total PO:</td>
                            <td style="text-align: right; padding: 12px 16px; color: var(--red-main);">{{ number_format($purchaseOrder->total_amount, 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- GR History Card --}}
        @if($purchaseOrder->goodsReceipts->count() > 0)
        <div class="card">
            <div class="card-title">
                <span class="material-icons">history</span> Riwayat Goods Receipt (GR)
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. GR</th>
                            <th>Tanggal Terima</th>
                            <th>Lokasi Gudang</th>
                            <th>Status</th>
                            <th style="width: 100px; text-align: center;">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->goodsReceipts as $gr)
                        <tr>
                            <td class="font-mono" style="font-weight: 700; color: var(--navy-dark);">{{ $gr->no_gr }}</td>
                            <td>{{ $gr->tanggal_terima ? $gr->tanggal_terima->format('d/m/Y') : '-' }}</td>
                            <td>{{ $gr->storageLocation->nama ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $gr->status === 'posted' ? 'status-received' : 'status-draft' }}">
                                    {{ $gr->status }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn btn-blue" style="padding: 4px 12px; font-size: 11px;" onclick="showGrDetail({{ $gr->id }})">Lihat</button>
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
        <div class="modal">
            <h3><span class="material-icons">info</span> Detail Goods Receipt</h3>
            <div style="margin-bottom: 20px;">
                <table class="detail-table">
                    <tr>
                        <td class="label-td">Nomor GR</td>
                        <td class="value-td" id="detail_no_gr" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Tanggal Terima</td>
                        <td class="value-td" id="detail_tanggal_terima"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Storage Location</td>
                        <td class="value-td" id="detail_location"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Status</td>
                        <td class="value-td" id="detail_status"></td>
                    </tr>
                </table>
            </div>

            <div style="border: 1px solid #eee; border-radius: 8px; padding: 16px; background: #fafafa; margin-top: 15px;">
                <div style="font-size: 12px; font-weight: 800; color: var(--navy-dark); margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 6px;">RINCIAN MATERIAL DITERIMA</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding: 8px; text-align: left;">Kode</th>
                            <th style="padding: 8px; text-align: left;">Nama Material</th>
                            <th style="padding: 8px; text-align: right;">Qty Diterima</th>
                        </tr>
                    </thead>
                    <tbody id="detail_items_table_body">
                        {{-- Rows injected by JS --}}
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-gray" onclick="closeGrModal()" style="width: 100%;">Tutup</button>
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
            fetch(`/goods-receipts/${grId}`)
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
