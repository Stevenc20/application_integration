<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SKM {{ $skm->skm_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1a1a1a; }
        .header { border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header-left h1 { font-size: 14pt; font-weight: bold; color: #1e3a5f; }
        .header-left p { font-size: 8pt; color: #555; margin-top: 2px; }
        .header-right { text-align: right; font-size: 8pt; color: #555; }
        .header-right .skm-num { font-size: 16pt; font-weight: bold; color: #1e3a5f; font-family: DejaVu Sans Mono, monospace; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-card { border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; text-align: center; width: 50%; }
        .summary-card .val { font-size: 16pt; font-weight: bold; color: #1e3a5f; }
        .summary-card .lbl { font-size: 7pt; color: #888; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background-color: #1e3a5f; color: #fff; }
        thead th { padding: 5px 4px; font-size: 8pt; font-weight: bold; border: 1px solid #1e3a5f; text-align: right; }
        thead th:nth-child(1), thead th:nth-child(2), thead th:nth-child(3) { text-align: left; }
        tbody tr:nth-child(even) { background-color: #f4f7fb; }
        tbody td { padding: 4px 4px; font-size: 8.5pt; border: 1px solid #e0e0e0; text-align: right; }
        tbody td:nth-child(1) { text-align: center; color: #888; font-size: 7.5pt; }
        tbody td:nth-child(2) { text-align: left; }
        .mat-code { font-family: DejaVu Sans Mono, monospace; font-weight: bold; color: #1e3a5f; font-size: 8pt; }
        .mat-name { font-size: 8.5pt; color: #1a1a1a; }
        .order-qty { font-weight: bold; font-size: 11pt; color: #1e3a5f; }
        .status-badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7.5pt; font-weight: bold; background: #dbeafe; color: #1d4ed8; }
        .footer { margin-top: 14px; font-size: 7pt; color: #aaa; display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 4px; }
        .sign-area { margin-top: 20px; width: 100%; border-collapse: collapse; }
        .sign-box { border-top: 1px solid #999; padding-top: 6px; padding-left: 10px; padding-right: 10px; font-size: 8pt; text-align: center; color: #555; width: 33.33%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>SUMMARY KANBAN MATERIAL</h1>
            <p>Tanggal Order: <b>{{ $skm->order_date->format('d F Y') }}</b>
            @php $firstItem = $skm->items->first(); @endphp
            @if($firstItem?->expected_delivery_date)
                &nbsp;&nbsp; Est. Pengiriman: <b>{{ $firstItem->expected_delivery_date->format('d F Y') }}</b>
            @endif
            @if($firstItem?->storageLocation)
                &nbsp;&nbsp; Gudang: <b>{{ $firstItem->storageLocation->code }} — {{ $firstItem->storageLocation->name }}</b>
            @endif
            </p>
            <p>Dibuat oleh: <b>{{ $skm->createdBy->name ?? '-' }}</b></p>
            @if($skm->notes)<p style="color:#666; font-style:italic">{{ $skm->notes }}</p>@endif
        </div>
        <div class="header-right">
            <div class="skm-num">{{ $skm->skm_number }}</div>
            <div style="margin-top:2px">
                <span class="status-badge">{{ $skm->status_label }}</span>
            </div>
        </div>
    </div>

    <table class="summary">
        <tr>
            <td class="summary-card">
                <div class="val">{{ $skm->items->count() }}</div>
                <div class="lbl">Total Item</div>
            </td>
            <td class="summary-card">
                <div class="val">{{ number_format($skm->items->sum('order_qty'), 0) }}</div>
                <div class="lbl">Total Qty Order</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th style="text-align:left">Material</th>
                <th style="text-align:left">Vendor</th>
                <th>Stok Saat SKM</th>
                <th>Min. Stok</th>
                <th>Qty/Kartu</th>
                <th>Jml Kartu</th>
                <th>Total Order</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($skm->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <div class="mat-code">{{ $item->material->code ?? '-' }}</div>
                    <div class="mat-name">{{ $item->material->name ?? '-' }}</div>
                    <div style="font-size:7pt; color:#888">{{ $item->material->unit_of_measure ?? '' }}</div>
                </td>
                <td style="text-align:left;font-size:7.5pt;color:#555">{{ $item->vendor->name ?? '-' }}</td>
                <td style="{{ (float)$item->current_stock < (float)$item->min_stock ? 'color:#c0392b' : 'color:#16a34a' }};font-weight:bold">
                    {{ number_format($item->current_stock, 3) }}
                </td>
                <td>{{ number_format($item->min_stock, 3) }}</td>
                <td>{{ number_format($item->kanban_qty, 0) }}</td>
                <td style="font-weight:bold;color:#1e3a5f">{{ $item->num_cards }}</td>
                <td class="order-qty">{{ number_format($item->order_qty, 0) }}</td>
                <td style="text-align:left;font-size:7.5pt;color:#666">{{ $item->notes ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sign-area">
        <tr>
            <td class="sign-box">Dibuat oleh<br><br><br>( {{ $skm->createdBy->name ?? '_______________' }} )</td>
            <td class="sign-box">Disetujui oleh<br><br><br>( _______________ )</td>
            <td class="sign-box">Diterima Vendor<br><br><br>( _______________ )</td>
        </tr>
    </table>

    <div class="footer">
        <span>Dicetak: {{ user_now()->format('d M Y H:i') }} {{ user_tz_label() }}</span>
        <span>{{ $skm->skm_number }} &mdash; CONFIDENTIAL</span>
    </div>
</body>
</html>
