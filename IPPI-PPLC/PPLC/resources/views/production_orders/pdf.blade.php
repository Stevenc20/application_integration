<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Production Order {{ $order->order_number ?? '' }}</title>
    <style>
        body { 
            font-family: monospace, "Courier New", Courier; 
            font-size: 11px; 
            margin: 0;
            padding: 10px;
            color: #000;
        }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        
        .title { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .sub-title { font-size: 12px; font-weight: bold; margin-bottom: 8px; }
        
        .barcode-container { margin: 10px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .label { width: 100px; }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .komponen-title {
            font-weight: bold;
            margin-bottom: 6px;
        }
        
        .footer {
            font-size: 9px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    @php
        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = base64_encode($generator->getBarcode($order->order_number ?? '000000', $generator::TYPE_CODE_128));
    @endphp

    <div class="text-center mb-4">
        <div class="title">PRODUCTION ORDER</div>
        <div class="sub-title">{{ $order->order_number ?? '' }}</div>
        
        <div class="barcode-container">
            <img src="data:image/png;base64,{{ $barcode }}" style="height: 60px; width: 80%; max-width: 300px;">
        </div>
    </div>

    <table>
        <tr>
            <td class="label">Item</td>
            <td class="fw-bold col-right" style="text-align: right;">{{ $order->material->kode ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2" style="color: #555;">{{ $order->material->nama ?? '-' }}</td>
        </tr>
        <tr><td colspan="2" style="height: 10px;"></td></tr>
        <tr>
            <td class="label">Tgl Mulai</td>
            <td class="fw-bold col-right" style="text-align: right;">{{ $order->planned_start_date ? $order->planned_start_date->format('d/m/Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tgl Selesai</td>
            <td class="fw-bold col-right" style="text-align: right;">{{ $order->planned_end_date ? $order->planned_end_date->format('d/m/Y') : '-' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="komponen-title">KOMPONEN BAHAN</div>
    <table>
        @foreach($order->components ?? [] as $comp)
        <tr>
            <td class="fw-bold">
                {{ $comp->material->kode ?? '' }}
            </td>
            <td class="fw-bold col-right" style="text-align: right;">
                {{ number_format($comp->quantity_required, 3) }} {{ $comp->material->uom ?? '' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="color: #555;">
                {{ $comp->material->nama ?? '-' }}
            </td>
        </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <div class="footer text-center">
        Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
    </div>
</body>
</html>
