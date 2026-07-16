<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat MRP Run</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 8pt; color: #1a1a1a; padding: 20px; }
        .header { margin-bottom: 15px; border-bottom: 2px solid #dc2626; padding-bottom: 8px; }
        .header h1 { font-size: 14pt; font-weight: bold; color: #1e293b; }
        .header p { font-size: 8pt; color: #64748b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead tr { background-color: #dc2626; color: #fff; }
        thead th { padding: 6px 4px; text-align: center; font-size: 7.5pt; font-weight: bold; border: 1px solid #dc2626; }
        thead th:first-child { text-align: left; padding-left: 8px; }
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        tbody td { padding: 6px 4px; font-size: 7.5pt; border: 1px solid #e2e8f0; text-align: center; vertical-align: middle; }
        tbody td:first-child { text-align: left; padding-left: 8px; }
        .footer { margin-top: 15px; font-size: 7pt; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Riwayat MRP Run</h1>
        <p>Daftar seluruh eksekusi MRP yang telah dilakukan</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Tanggal Run</th>
                <th style="width: 15%;">Jumlah Hasil</th>
                <th style="width: 25%;">Dijalankan Oleh</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 25%;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($runs as $run)
            <tr>
                <td>{{ $run->created_at ? $run->created_at->format('d M Y H:i') : '-' }} WIB</td>
                <td style="font-weight: bold; color: #1e3a5f;">{{ $run->results_count ?? $run->results->count() }} material</td>
                <td>{{ $run->runBy->name ?? '-' }}</td>
                <td><span style="color: #15803d; font-weight: bold;">{{ ucfirst($run->status ?? 'completed') }}</span></td>
                <td style="color: #64748b;">{{ $run->notes ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada riwayat MRP Run.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dicetak: {{ now()->format('d M Y H:i') }} WIB</div>
</body>
</html>
