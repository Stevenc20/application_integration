<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Production Order</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc2626; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #1e293b; font-size: 18px; }
        .header p { margin: 4px 0 0; color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
        .text-center { text-align: center; }
        .meta-info { font-size: 9px; color: #94a3b8; text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>DAFTAR PRODUCTION ORDER</h2>
        <p>Semua Data Production Order</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No.</th>
                <th style="width: 15%;">No. Order</th>
                <th style="width: 30%;">Material</th>
                <th class="text-center" style="width: 10%;">Qty Plan</th>
                <th class="text-center" style="width: 15%;">Tgl Mulai</th>
                <th class="text-center" style="width: 15%;">Tgl Selesai</th>
                <th class="text-center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td style="font-weight:bold; color:#1e293b;">{{ $order->order_number }}</td>
                <td>
                    <div style="color: #64748b; font-size: 9px;">{{ $order->material->kode ?? '' }}</div>
                    <div style="font-weight:bold;">{{ $order->material->nama ?? '-' }}</div>
                </td>
                <td class="text-center" style="font-weight:bold;">{{ number_format($order->quantity_planned, 3) }}</td>
                <td class="text-center">{{ $order->planned_start_date ? $order->planned_start_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $order->planned_end_date ? $order->planned_end_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center" style="text-transform: uppercase; font-weight: bold; color: {{ strtolower($order->status) == 'completed' ? '#16a34a' : (strtolower($order->status) == 'cancelled' ? '#dc2626' : '#0284c7') }}">
                    {{ str_replace('_', ' ', $order->status) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="meta-info">
        Dicetak pada: {{ now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>
