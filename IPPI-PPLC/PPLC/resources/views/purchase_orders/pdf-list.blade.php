<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Purchase Order</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; background: #fff; }
        .page { padding: 24px 30px; }

        /* Header */
        .header { display: table; width: 100%; border-bottom: 2px solid #dc2626; padding-bottom: 10px; margin-bottom: 14px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 15px; font-weight: bold; color: #dc2626; }
        .company-sub { font-size: 8px; color: #555; margin-top: 1px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #dc2626; letter-spacing: 0.5px; }
        .doc-sub { font-size: 8px; color: #555; margin-top: 2px; }

        /* Filter info */
        .filter-bar { background: #fef2f2; border: 1px solid #fecaca; border-radius: 3px; padding: 5px 10px; margin-bottom: 12px; font-size: 8px; color: #374151; }
        .filter-bar span { font-weight: bold; color: #dc2626; margin-right: 4px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr { background: #dc2626; color: #fff; }
        thead th { padding: 5px 7px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 5px 7px; font-size: 9px; vertical-align: middle; }
        tbody td.right { text-align: right; }
        tbody td.center { text-align: center; }

        /* Status badges */
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7.5px; font-weight: bold; text-transform: capitalize; }
        .badge-draft { background: #e5e7eb; color: #374151; }
        .badge-approved { background: #dbeafe; color: #1d4ed8; }
        .badge-received { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }
        .badge-partially_received { background: #fef9c3; color: #a16207; }

        /* Summary */
        .summary-box { display: table; width: 100%; margin-top: 6px; }
        .summary-left { display: table-cell; width: 65%; vertical-align: bottom; font-size: 8px; color: #6b7280; }
        .summary-right { display: table-cell; width: 35%; vertical-align: top; }
        .total-box { border: 1px solid #dc2626; border-radius: 3px; padding: 7px 12px; background: #fef2f2; text-align: right; }
        .total-label { font-size: 7.5px; color: #dc2626; text-transform: uppercase; letter-spacing: 0.3px; }
        .total-amount { font-size: 13px; font-weight: bold; color: #991b1b; margin-top: 1px; }

        /* Footer */
        .footer { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 6px; text-align: center; font-size: 7.5px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">PPLC</div>
            <div class="company-sub">Production Planning &amp; Logistics Control</div>
        </div>
        <div class="header-right">
            <div class="doc-title">DAFTAR PURCHASE ORDER</div>
            <div class="doc-sub">Dicetak: {{ user_now()->format('d M Y, H:i') }} {{ user_tz_label() }} &nbsp;|&nbsp; Oleh: {{ auth()->user()->name ?? '-' }}</div>
        </div>
    </div>

    {{-- Active Filters --}}
    <div class="filter-bar">
        Filter aktif:
        @if($filters['search'] ?? null) <span>Pencarian:</span> "{{ $filters['search'] }}" &nbsp; @endif
        @if($filters['status'] ?? null) <span>Status:</span> {{ ucfirst(str_replace('_',' ',$filters['status'])) }} &nbsp; @endif
        @if($filters['date_from'] ?? null) <span>Dari:</span> {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }} &nbsp; @endif
        @if($filters['date_to'] ?? null) <span>Sampai:</span> {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }} &nbsp; @endif
        @if(!($filters['search'] ?? null) && !($filters['status'] ?? null) && !($filters['date_from'] ?? null) && !($filters['date_to'] ?? null)) Semua data @endif
        &nbsp;|&nbsp; <span>Total data:</span> {{ count($pos) }} PO
    </div>

    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:16%">No. PO</th>
                <th>Vendor</th>
                <th style="width:11%">Tgl Order</th>
                <th style="width:11%">Est. Terima</th>
                <th class="right" style="width:14%">Total Amount</th>
                <th class="center" style="width:12%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pos as $i => $po)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td style="font-family: monospace; color: #dc2626; font-weight: bold;">{{ $po->po_number }}</td>
                <td>{{ $po->vendor->nama ?? '-' }}</td>
                <td>{{ $po->order_date ? $po->order_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $po->expected_delivery_date?->format('d/m/Y') ?? '-' }}</td>
                <td class="right">{{ number_format($po->total_amount, 0) }}</td>
                <td class="center">
                    <span class="badge badge-{{ $po->status }}">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#9ca3af; padding: 16px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Summary --}}
    <div class="summary-box">
        <div class="summary-left">
            Total {{ count($pos) }} Purchase Order ditampilkan.
        </div>
        <div class="summary-right">
            <div class="total-box">
                <div class="total-label">Grand Total (IDR)</div>
                <div class="total-amount">Rp {{ number_format($pos->sum('total_amount'), 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh sistem PPLC &mdash; {{ user_now()->format('d M Y H:i') }} {{ user_tz_label() }}
    </div>

</div>
</body>
</html>
