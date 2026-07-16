<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .page { padding: 30px 36px; }

        /* Header */
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #dc2626; padding-bottom: 14px; margin-bottom: 16px; }
        .company-name { font-size: 17px; font-weight: bold; color: #dc2626; }
        .company-sub { font-size: 9px; color: #555; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 16px; font-weight: bold; color: #dc2626; letter-spacing: 1px; }
        .doc-title .po-number { font-size: 13px; font-weight: bold; font-family: monospace; color: #333; margin-top: 2px; }
        .barcode-wrap { margin-top: 6px; text-align: right; }
        .barcode-wrap img { height: 36px; }
        .barcode-text { font-size: 8px; color: #6b7280; text-align: center; margin-top: 1px; letter-spacing: 1px; font-family: monospace; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-approved { background: #dbeafe; color: #1d4ed8; }
        .status-received { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .status-partially_received { background: #fef9c3; color: #a16207; }

        /* Info Grid */
        .info-grid { display: table; width: 100%; margin-bottom: 16px; border: 1px solid #e5e7eb; border-radius: 4px; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 6px 12px; vertical-align: top; width: 25%; border-right: 1px solid #f3f4f6; }
        .info-cell:last-child { border-right: none; }
        .info-label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .info-value { font-size: 10px; font-weight: bold; color: #111; }

        /* Vendor box */
        .vendor-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; }
        .vendor-title { font-size: 8px; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; margin-bottom: 4px; }
        .vendor-name { font-size: 12px; font-weight: bold; color: #991b1b; }
        .vendor-detail { font-size: 9px; color: #374151; margin-top: 2px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead tr { background: #dc2626; color: #fff; }
        thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 6px 8px; font-size: 10px; vertical-align: middle; }
        tbody td.right { text-align: right; }
        tbody td.center { text-align: center; }
        .mat-code { font-family: monospace; font-size: 9px; color: #dc2626; display: block; }
        .mat-name { font-size: 10px; color: #111; }

        /* Total row */
        .total-row td { background: #fef2f2; font-weight: bold; border-top: 2px solid #dc2626; }

        /* Summary */
        .summary { display: table; width: 100%; margin-bottom: 16px; }
        .summary-left { display: table-cell; width: 60%; vertical-align: top; padding-right: 20px; }
        .summary-right { display: table-cell; width: 40%; vertical-align: top; }
        .total-box { border: 2px solid #dc2626; border-radius: 4px; padding: 10px 14px; background: #fef2f2; text-align: right; }
        .total-label { font-size: 9px; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px; }
        .total-amount { font-size: 18px; font-weight: bold; color: #991b1b; margin-top: 2px; }

        /* Notes */
        .notes-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 12px; background: #fffbeb; }
        .notes-label { font-size: 8px; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; margin-bottom: 3px; }
        .notes-text { font-size: 10px; color: #374151; }

        /* Signature */
        .signature-section { display: table; width: 100%; margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        .sig-col { display: table-cell; width: 33.33%; text-align: center; padding: 0 10px; }
        .sig-title { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 40px; }
        .sig-line { border-top: 1px solid #374151; padding-top: 4px; font-size: 9px; color: #374151; }
        .sig-name { font-size: 10px; font-weight: bold; }
        .sig-role { font-size: 8px; color: #6b7280; }

        /* Footer */
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; font-size: 8px; color: #9ca3af; }

        /* Delivery info */
        .delivery-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 8px 12px; margin-bottom: 16px; }
        .delivery-title { font-size: 8px; color: #15803d; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="company-name">PPLC</div>
            <div class="company-sub">Production Planning &amp; Logistics Control</div>
        </div>
        <div class="doc-title">
            <h1>PURCHASE ORDER</h1>
            <div class="po-number">{{ $purchaseOrder->po_number }}</div>
            <div style="margin-top:4px;">
                <span class="status-badge status-{{ $purchaseOrder->status }}">
                    {{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}
                </span>
            </div>
            <div class="barcode-wrap">
                <img src="{{ $barcodeBase64 }}" alt="{{ $purchaseOrder->po_number }}" style="height:36px;">
                <div class="barcode-text">{{ $purchaseOrder->po_number }}</div>
            </div>
        </div>
    </div>

    {{-- Info Grid --}}
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Tanggal Order</div>
                <div class="info-value">{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d M Y') : '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Est. Pengiriman</div>
                <div class="info-value">{{ $purchaseOrder->expected_delivery_date?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Gudang Tujuan</div>
                <div class="info-value">{{ $purchaseOrder->storageLocation?->code }} - {{ $purchaseOrder->storageLocation?->name ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Dibuat Oleh</div>
                <div class="info-value">{{ $purchaseOrder->createdBy->name ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Vendor --}}
    <div class="vendor-box">
        <div class="vendor-title">Kepada Yth. / To Vendor</div>
        <div class="vendor-name">{{ $purchaseOrder->vendor->nama ?? '-' }}</div>
        @if($purchaseOrder->vendor?->alamat)
        <div class="vendor-detail">{{ $purchaseOrder->vendor->alamat }}</div>
        @endif
        @if($purchaseOrder->vendor?->kontak)
        <div class="vendor-detail">Contact: {{ $purchaseOrder->vendor->kontak }}
            @if($purchaseOrder->vendor?->telepon) &nbsp;|&nbsp; Telp: {{ $purchaseOrder->vendor->telepon }} @endif
        </div>
        @endif
        @if($purchaseOrder->vendor?->email)
        <div class="vendor-detail">Email: {{ $purchaseOrder->vendor->email }}</div>
        @endif
    </div>

    {{-- Delivery Info --}}
    <div class="delivery-info">
        <div class="delivery-title">Informasi Pengiriman</div>
        <div style="font-size:10px; color:#166534;">
            Mohon kirimkan barang ke gudang: <strong>{{ $purchaseOrder->storageLocation?->code }} - {{ $purchaseOrder->storageLocation?->name }}</strong>
            @if($purchaseOrder->expected_delivery_date)
            &nbsp;&mdash;&nbsp; Estimasi tiba paling lambat: <strong>{{ $purchaseOrder->expected_delivery_date->format('d M Y') }}</strong>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:16%">Kode Material</th>
                <th>Nama Material</th>
                <th class="center" style="width:10%">UoM</th>
                <th class="right" style="width:13%">Qty Order</th>
                <th class="right" style="width:13%">Qty Terima</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($purchaseOrder->items as $i => $item)
            @php $totalQty += $item->qty; @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td><span class="mat-code">{{ $item->material->kode }}</span></td>
                <td><span class="mat-name">{{ $item->material->nama }}</span></td>
                <td class="center">{{ $item->material->uom ?? '-' }}</td>
                <td class="right">{{ number_format($item->qty, 3) }}</td>
                <td class="right">{{ number_format($item->qty_received ?? 0, 3) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="right">TOTAL QTY</td>
                <td class="right">{{ number_format($totalQty, 3) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Summary & Notes --}}
    <div class="summary">
        <div class="summary-left">
            @if($purchaseOrder->notes)
            <div class="notes-box">
                <div class="notes-label">Catatan / Notes</div>
                <div class="notes-text">{{ $purchaseOrder->notes }}</div>
            </div>
            @endif
            <div style="margin-top: {{ $purchaseOrder->notes ? '10px' : '0' }}; font-size: 9px; color: #6b7280;">
                Dokumen ini dicetak pada {{ user_now()->format('d M Y, H:i') }} {{ user_tz_label() }}
            </div>
        </div>
        <div class="summary-right">
            <div class="total-box">
                <div class="total-label">Total Qty Keseluruhan</div>
                <div class="total-amount" style="font-size:22px;">{{ number_format($totalQty, 3, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Signature --}}
    <div class="signature-section">
        <div class="sig-col">
            <div class="sig-title">Dibuat Oleh</div>
            <div class="sig-line">
                <div class="sig-name">{{ $purchaseOrder->createdBy->name ?? '-' }}</div>
                <div class="sig-role">Purchasing</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-title">Disetujui Oleh</div>
            <div class="sig-line">
                <div class="sig-name">&nbsp;</div>
                <div class="sig-role">Manager / Approval</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-title">Diterima Oleh</div>
            <div class="sig-line">
                <div class="sig-name">&nbsp;</div>
                <div class="sig-role">Vendor</div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        {{ $purchaseOrder->po_number }} &mdash; Dokumen ini dihasilkan secara otomatis oleh sistem PPLC &mdash; Halaman 1 dari 1
    </div>

</div>
</body>
</html>
