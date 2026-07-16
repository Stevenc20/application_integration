<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Customer</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
            border: none;
        }
        .header td {
            vertical-align: bottom;
            border: none;
            padding: 0;
        }
        .logo-text {
            color: #2563eb;
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        .sub-logo {
            color: #64748b;
            font-size: 11px;
            margin: 0;
        }
        .title-text {
            color: #2563eb;
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin: 0 0 5px 0;
        }
        .sub-title {
            color: #64748b;
            font-size: 10px;
            text-align: right;
            margin: 0;
        }
        
        .filter-box {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #475569;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th {
            background-color: #1e3a8a; /* Dark blue as in screenshot */
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 6px;
            text-align: left;
        }
        table.data-table th.center {
            text-align: center;
        }
        table.data-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 6px;
            font-size: 10px;
            vertical-align: middle;
        }
        table.data-table tr.empty-row td {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
        .total-text {
            text-align: left;
            margin-bottom: 20px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    <h1 class="logo-text">IPPI</h1>
                    <p class="sub-logo">Integrated Production & Inventory System</p>
                </td>
                <td style="width: 50%;">
                    <h2 class="title-text">DAFTAR CUSTOMER</h2>
                    <p class="sub-title">Dicetak: {{ $dateStr }} | Oleh: {{ auth()->user()->name ?? 'mahardika' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="filter-box">
        Filter aktif: {{ $filterStr }} &nbsp;|&nbsp; <strong>Total: {{ $customers->count() }} customer</strong>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 15%;">KODE</th>
                <th style="width: 25%;">NAMA CUSTOMER</th>
                <th style="width: 20%;">CONTACT PERSON</th>
                <th style="width: 15%;">EMAIL</th>
                <th style="width: 12%;">TELEPON</th>
                <th style="width: 8%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $c)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $c->kode }}</strong></td>
                <td>{{ $c->nama }}</td>
                <td>{{ $c->kontak ?: '-' }}</td>
                <td>{{ $c->email ?: '-' }}</td>
                <td>{{ $c->telepon ?: '-' }}</td>
                <td>{{ $c->status }}</td>
            </tr>
            @empty
            <tr class="empty-row">
                <td colspan="7">Tidak ada data.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-text">
        Total {{ $customers->count() }} customer ditampilkan.
    </div>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh sistem IPPI - {{ $dateStr }}
    </div>

</body>
</html>
