<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar BOM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 24px 30px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 15px; font-weight: bold; color: #1d4ed8; }
        .company-sub { font-size: 8px; color: #555; margin-top: 1px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #1d4ed8; }
        .doc-sub { font-size: 8px; color: #555; margin-top: 2px; }
        .filter-bar { background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 3px; padding: 5px 10px; margin-bottom: 12px; font-size: 8px; color: #374151; }
        .filter-bar span { font-weight: bold; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr { background: #1d4ed8; color: #fff; }
        thead th { padding: 5px 7px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 5px 7px; font-size: 9px; }
        tbody td.right { text-align: right; }
        tbody td.center { text-align: center; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #dc2626; }
        .badge-draft { background: #fef9c3; color: #a16207; }
        .badge-rm { background: #f3f4f6; color: #374151; }
        .badge-wip { background: #fef9c3; color: #a16207; }
        .badge-fp { background: #dcfce7; color: #15803d; }
        .footer { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 6px; text-align: center; font-size: 7.5px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            <div class="company-name">IPPI</div>
            <div class="company-sub">Integrated Production &amp; Inventory System</div>
        </div>
        <div class="header-right">
            <div class="doc-title">DAFTAR BILL OF MATERIAL (BOM)</div>
            <div class="doc-sub">Dicetak: {{ user_now()->format('d M Y, H:i') }} {{ user_tz_label() }} &nbsp;|&nbsp; Oleh: {{ auth()->user()->name ?? '-' }}</div>
        </div>
    </div>

    <div class="filter-bar">
        Filter aktif:
        @if($filters['search'] ?? null) <span>Pencarian:</span> "{{ $filters['search'] }}" &nbsp; @endif
        @if($filters['date_from'] ?? null) <span>Dari:</span> {{ $filters['date_from'] }} &nbsp; @endif
        @if($filters['date_to'] ?? null) <span>Sampai:</span> {{ $filters['date_to'] }} &nbsp; @endif
        @if(!($filters['search'] ?? null) && !($filters['date_from'] ?? null) && !($filters['date_to'] ?? null)) Semua data @endif
        &nbsp;|&nbsp; <span>Total:</span> {{ count($boms) }} BOM
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:14%">No. BOM</th>
                <th style="width:14%">Kode Material</th>
                <th>Nama Material (Hasil)</th>
                <th class="center" style="width:7%">Tipe</th>
                <th class="right" style="width:10%">Base Qty</th>
                <th class="center" style="width:10%">Jml Komponen</th>
                <th class="center" style="width:10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($boms as $i => $bom)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td style="font-family: monospace; color: #1d4ed8;">{{ $bom->bom_number }}</td>
                <td style="font-family: monospace;">{{ $bom->material->kode ?? '-' }}</td>
                <td>{{ $bom->material->nama ?? '-' }}</td>
                <td class="center">
                    <span class="badge badge-{{ strtolower($bom->material->tipe ?? '') }}">{{ $bom->material->tipe ?? '-' }}</span>
                </td>
                <td class="right">{{ number_format((float)$bom->base_quantity, 3) }}</td>
                <td class="center">{{ $bom->items_count }}</td>
                <td class="center">
                    <span class="badge badge-{{ $bom->status }}">{{ ucfirst($bom->status === 'active' ? 'Aktif' : 'Nonaktif') }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; color:#9ca3af; padding:16px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="font-size:8px; color:#6b7280;">Total {{ count($boms) }} BOM ditampilkan.</div>
    <div class="footer">Dokumen ini dihasilkan secara otomatis oleh sistem IPPI &mdash; {{ user_now()->format('d M Y H:i') }} {{ user_tz_label() }}</div>
</div>
</body>
</html>
