<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil MRP Run — {{ $mrpRun->created_at ? $mrpRun->created_at->format('d M Y H:i') : '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 8pt; color: #1a1a1a; padding: 20px; }
        .header { margin-bottom: 15px; border-bottom: 2px solid #dc2626; padding-bottom: 8px; }
        .header h1 { font-size: 14pt; font-weight: bold; color: #1e293b; }
        .header p { font-size: 8pt; color: #64748b; margin-top: 4px; }
        .summary-box {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .summary-card {
            display: table-cell;
            width: 25%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            background: #f8fafc;
        }
        .summary-card .val { font-size: 14pt; font-weight: bold; }
        .summary-card .lbl { font-size: 7.5pt; color: #64748b; margin-top: 2px; }
        .blue { color: #1e3a5f; }
        .red { color: #dc2626; }
        .yellow { color: #b45309; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead tr { background-color: #dc2626; color: #fff; }
        thead th { padding: 6px 4px; text-align: right; font-size: 7.5pt; font-weight: bold; border: 1px solid #dc2626; }
        thead th:first-child, thead th:nth-child(2) { text-align: left; }
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        tbody tr.purchase { background-color: #fef2f2; }
        tbody td { padding: 6px 4px; font-size: 7.5pt; border: 1px solid #e2e8f0; text-align: right; vertical-align: middle; }
        tbody td:first-child { text-align: left; font-family: monospace; font-weight: bold; color: #1e3a5f; }
        tbody td:nth-child(2) { text-align: left; }
        
        .badge-po { background: #fee2e2; color: #b91c1c; border-radius: 3px; padding: 2px 5px; font-size: 7pt; font-weight: bold; display: inline-block; }
        .badge-prod { background: #fef9c3; color: #92400e; border-radius: 3px; padding: 2px 5px; font-size: 7pt; font-weight: bold; display: inline-block; }
        .order-qty { font-size: 9pt; font-weight: bold; color: #1e293b; }
        .note { font-size: 7pt; color: #64748b; margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 8px; line-height: 1.4; }
        .footer { margin-top: 15px; font-size: 7pt; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hasil MRP Run &mdash; {{ $mrpRun->created_at ? $mrpRun->created_at->format('d M Y H:i') : '' }} WIB</h1>
        <p>Dijalankan oleh: <b>{{ $mrpRun->runBy->name ?? '-' }}</b> &nbsp;|&nbsp; Total: <b>{{ $results->count() }}</b> material</p>
    </div>

    <div class="summary-box">
        <div class="summary-card" style="border-right: none;">
            <div class="val blue">{{ $results->count() }}</div>
            <div class="lbl">Total Material</div>
        </div>
        <div class="summary-card" style="border-right: none;">
            <div class="val red">{{ $results->where('recommendation_type','purchase')->count() }}</div>
            <div class="lbl">Perlu Buat PO</div>
        </div>
        <div class="summary-card" style="border-right: none;">
            <div class="val yellow">{{ $results->where('recommendation_type','production')->count() }}</div>
            <div class="lbl">Perlu Produksi</div>
        </div>
        <div class="summary-card">
            <div class="val blue">{{ $results->where('net_requirement',0)->count() }}</div>
            <div class="lbl">Stok Cukup</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align:left; width: 10%;">Kode</th>
                <th style="text-align:left; width: 24%;">Nama Material</th>
                <th style="width: 5%;">Satuan</th>
                <th style="width: 7%;">Gross Req.</th>
                <th style="width: 7%;">Stok*</th>
                <th style="width: 7%;">Sisa PO</th>
                <th style="width: 7%;">Net Req.</th>
                <th style="width: 7%;">Safety 20%</th>
                <th style="width: 7%;">Total+Safety</th>
                <th style="width: 7%;">Qty/Case</th>
                <th style="width: 7%;">Order Vendor</th>
                <th style="width: 5%;">Rek.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $r)
            @php $withSafety = (float)$r->net_requirement + (float)$r->safety_stock_qty; @endphp
            <tr class="{{ $r->recommendation_type === 'purchase' ? 'purchase' : '' }}">
                <td>{{ $r->material->kode ?? '-' }}</td>
                <td style="text-align:left;">{{ $r->material->nama ?? '-' }}</td>
                <td>{{ $r->material->uom ?? '-' }}</td>
                <td>{{ number_format($r->gross_requirement, 2) }}</td>
                <td style="{{ (float)$r->current_stock < (float)$r->gross_requirement ? 'color:#dc2626' : 'color:#15803d' }}">{{ number_format($r->current_stock, 2) }}</td>
                <td style="color:#15803d">{{ (float)$r->open_po_qty > 0 ? number_format($r->open_po_qty, 2) : '-' }}</td>
                <td style="font-weight:bold">{{ number_format($r->net_requirement, 2) }}</td>
                <td style="color:#b45309">+{{ number_format($r->safety_stock_qty, 2) }}</td>
                <td style="color:#1e3a8a; font-weight:bold">{{ number_format($withSafety, 2) }}</td>
                <td style="color:#666">{{ (float)$r->qty_per_case > 0 ? number_format($r->qty_per_case, 0) : '-' }}</td>
                <td class="order-qty">{{ number_format($r->recommended_quantity, 2) }}</td>
                <td style="text-align:center">
                    @if($r->recommendation_type === 'purchase')
                        <span class="badge-po">Buat PO</span>
                    @else
                        <span class="badge-prod">Produksi</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="note">
        * Stok Tersedia = Stok RM aktual + Stok FP/WIP dikonversi ke RM via BOM. Stok lokasi scrap tidak dihitung.<br>
        Formula: Net = Gross &minus; Stok Tersedia &minus; Sisa PO &nbsp;&rarr;&nbsp; +Safety 20% &nbsp;&rarr;&nbsp; Order = round-up ke Qty/Case
    </div>
    <div class="footer">Dicetak: {{ now()->format('d M Y H:i') }} WIB</div>
</body>
</html>
