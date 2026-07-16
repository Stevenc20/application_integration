<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Goods Receipt PDF</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #333;
            margin: 10px;
            line-height: 1.2;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
        }
        .ippi-logo {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }
        .ippi-sub {
            font-size: 8px;
            color: #666;
            margin-top: 1px;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            margin-top: 8px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-info {
            font-size: 8px;
            color: #555;
            margin-top: 3px;
        }
        .meta-flex {
            width: 100%;
        }
        .meta-flex td {
            padding: 0;
            border: none;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .table-data th {
            background-color: #1e3a8a;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            padding: 6px 5px;
            text-align: left;
            border: 1px solid #1e3a8a;
        }
        .table-data td {
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .table-data tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .code-td {
            font-family: 'Courier', monospace;
            font-weight: bold;
            color: #1d4ed8;
        }
        .footer {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="meta-flex">
            <tr>
                <td>
                    <div class="ippi-logo">IPPI</div>
                    <div class="ippi-sub">Integrated Production & Inventory System</div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div class="meta-info">
                        <strong>Dicetak:</strong> {{ $dateStr }} &nbsp;|&nbsp; <strong>Oleh:</strong> Administrator
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">DAFTAR GOODS RECEIPT</div>
    
    <div class="meta-info" style="border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; margin-bottom: 8px;">
        <strong>Filter aktif:</strong> {{ $filterStr }} &nbsp;|&nbsp; <strong>Total:</strong> {{ $goodsReceipts->count() }} Goods Receipt
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">NO</th>
                <th style="width: 15%;">NO. GR</th>
                <th style="width: 15%;">NO. PO</th>
                <th style="width: 32%;">VENDOR</th>
                <th style="width: 11%;">TGL TERIMA</th>
                <th style="width: 15%;">LOKASI</th>
                <th style="width: 8%; text-align: center;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($goodsReceipts as $index => $gr)
            <tr>
                <td style="text-align: center; color: #666;">{{ $index + 1 }}</td>
                <td class="code-td">{{ $gr->no_gr }}</td>
                <td style="font-family: monospace;">{{ $gr->purchaseOrder->no_po ?? '-' }}</td>
                <td style="font-weight: bold;">{{ $gr->purchaseOrder->vendor->nama ?? '-' }}</td>
                <td>{{ $gr->tanggal_terima->format('d/m/Y') }}</td>
                <td>{{ $gr->storageLocation->nama ?? '-' }}</td>
                <td style="text-align: center; font-weight: bold; color: {{ $gr->status === 'posted' ? '#15803d' : '#4b5563' }};">
                    {{ $gr->status }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <table class="meta-flex">
            <tr>
                <td>
                    Total {{ $goodsReceipts->count() }} Goods Receipt ditampilkan.
                </td>
                <td style="text-align: right;">
                    Dokumen ini dihasilkan secara otomatis oleh sistem IPPI &copy; {{ now()->format('d M Y, H:i') }} WIB
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
