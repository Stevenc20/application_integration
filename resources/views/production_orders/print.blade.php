<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label - {{ $productionOrder->order_number }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 2mm 2mm 3mm 2mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            width: 54mm;
            background: #fff;
            color: #000;
            padding: 4px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .title {
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .order-number {
            font-size: 10pt;
            font-weight: bold;
            margin: 1mm 0;
        }
        .barcode-wrap {
            margin: 2mm 0;
            display: flex;
            justify-content: center;
        }
        .barcode-wrap svg {
            width: 100% !important;
            height: 12mm !important;
            display: block;
        }
        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }
        .divider-solid {
            border: none;
            border-top: 1px solid #000;
            margin: 2mm 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            line-height: 1.2;
        }
        .info-label {
            color: #333;
            min-width: 18mm;
        }
        .info-value {
            text-align: right;
            font-weight: bold;
        }
        .material-name {
            font-size: 7.5pt;
            margin-bottom: 1.5mm;
            line-height: 1.2;
            font-weight: bold;
        }
        .section-title {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1mm;
        }
        .comp-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1mm;
        }
        .comp-table td {
            padding: 0.5mm 0;
            font-size: 7pt;
            vertical-align: top;
            line-height: 1.2;
        }
        .comp-code {
            font-weight: bold;
            width: 22mm;
        }
        .comp-qty {
            text-align: right;
            white-space: nowrap;
        }
        .comp-name {
            font-size: 6.5pt;
            color: #333;
        }
        .footer {
            font-size: 6.5pt;
            color: #333;
            margin-top: 2mm;
            text-align: center;
        }
        .no-print {
            margin-bottom: 10px;
            text-align: center;
        }
        .no-print button {
            padding: 4px 8px;
            font-size: 10px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    {{-- Header --}}
    <div class="center">
        <div class="title">Production Order</div>
        <div class="order-number">{{ $productionOrder->order_number }}</div>
        <div class="barcode-wrap">{!! $barcode !!}</div>
    </div>

    <hr class="divider-solid">

    {{-- Material & Qty --}}
    <div class="info-row">
        <span class="info-label">Item</span>
        <span class="info-value">{{ $productionOrder->material->kode }}</span>
    </div>
    <div class="material-name">{{ $productionOrder->material->nama }}</div>
    <div class="info-row">
        <span class="info-label">Qty Plan</span>
        <span class="info-value">{{ number_format($productionOrder->quantity_planned, 3) }} {{ $productionOrder->material->uom }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Tgl Mulai</span>
        <span class="info-value">{{ $productionOrder->planned_start_date?->format('d/m/Y') ?? '-' }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Tgl Selesai</span>
        <span class="info-value">{{ $productionOrder->planned_end_date?->format('d/m/Y') ?? '-' }}</span>
    </div>

    {{-- Components --}}
    @if($productionOrder->components->isNotEmpty())
    <hr class="divider">
    <div class="section-title">Komponen Bahan</div>
    @foreach($productionOrder->components as $comp)
    <table class="comp-table">
        <tr>
            <td class="comp-code">{{ $comp->material->kode }}</td>
            <td class="comp-qty">{{ number_format($comp->quantity_required, 3) }} {{ $comp->material->uom }}</td>
        </tr>
        <tr>
            <td colspan="2" class="comp-name">{{ $comp->material->nama }}</td>
        </tr>
    </table>
    @endforeach
    @endif

    <hr class="divider">
    <div class="footer">Dicetak: {{ now()->format('d/m/Y H:i') }} WIB</div>

    <script>
        window.onload = function () { 
            // Auto trigger print only if printing directly
            if (!window.location.search.includes('noprint')) {
                window.print();
            }
        };
    </script>
</body>
</html>
