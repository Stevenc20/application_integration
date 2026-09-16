@extends('layouts.app')

@section('title', 'Summary Kanban Material (SKM)')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }
    .page-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--navy-dark);
    }
    .page-date {
        font-size: 13px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .content-body {
        padding: 24px 28px;
        background: #f8fafc;
        min-height: calc(100vh - 70px);
    }

    /* WIDGETS GRID */
    .widgets-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .widget-box {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .widget-number {
        font-size: 28px;
        font-weight: 800;
        color: var(--navy-dark);
        line-height: 1;
        margin-bottom: 8px;
    }
    .widget-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
    }
    .widget-box.alert-widget {
        border: 2px solid #ef4444;
        background: #fffafa;
    }
    .widget-box.alert-widget .widget-number { color: #ef4444; }
    .widget-box.alert-widget .widget-label  { color: #ef4444; }
    .widget-box.safe-widget {
        border: 1px solid #e2e8f0;
    }
    .widget-box.safe-widget .widget-number  { color: #94a3b8; }

    /* ALERT BANNER */
    .alert-banner {
        background: #fffafa;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        gap: 16px;
    }
    .alert-text-main {
        font-size: 15px;
        font-weight: 800;
        color: #b91c1c;
        margin-bottom: 4px;
    }
    .alert-text-sub {
        font-size: 13px;
        color: #dc2626;
        font-weight: 500;
    }
    .success-banner {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        gap: 16px;
    }
    .success-text { font-size: 14px; font-weight: 600; color: #166534; }

    /* CARD STYLES */
    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .card-header-basic {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 15px;
        font-weight: 800;
        color: var(--navy-dark);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-subtext {
        padding: 10px 20px;
        font-size: 12px;
        color: #94a3b8;
    }

    /* TABLES */
    .table-responsive { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    thead th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 800;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .table-blue-header thead th {
        background: var(--navy-dark);
        color: #fff;
        border-bottom: none;
    }
    .table-orange-header thead th {
        background: #ffedd5;
        color: #9a3412;
        border-bottom: 1px solid #fdba74;
    }
    tbody td {
        padding: 11px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #f8fafc; }

    .empty-state { text-align: center; padding: 30px; color: #94a3b8; font-style: italic; }

    /* Column specifics */
    .col-material-kode { font-family: monospace; font-weight: 800; color: var(--navy-dark); display: block; font-size: 12px; }
    .col-material-nama { color: #64748b; font-size: 11px; }
    .col-stok { color: #ef4444; font-weight: 700; }
    .col-kanban-total { color: var(--navy-dark); font-weight: 800; }
    .col-orange { color: #f97316; font-weight: 700; }
    .col-saran { color: var(--navy-dark); font-weight: 800; }

    /* Status badges */
    .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
    .badge-gray   { background: #f1f5f9; color: #64748b; }
    .badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-yellow { background: #fef9c3; color: #a16207; }
    .badge-green  { background: #dcfce7; color: #166534; }
    .badge-red    { background: #fee2e2; color: #dc2626; }

    /* Buttons */
    .btn { display: inline-flex; align-items: center; gap: 6px; border: none; border-radius: 6px; padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; transition: opacity .15s; }
    .btn:hover { opacity: .85; }
    .btn-red     { background: #ef4444; color: #fff; }
    .btn-blue    { background: #2563eb; color: #fff; }
    .btn-green   { background: #10b981; color: #fff; }
    .btn-outline { background: #fff; color: var(--navy-dark); border: 1px solid #e2e8f0; }

    /* Demand section */
    .demand-form-wrap { padding: 16px 20px; }
    .file-row { display: flex; gap: 12px; align-items: center; }
    .file-row input[type=file] { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 10px; font-size: 13px; }

    @media (max-width: 1024px) { .widgets-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 640px) {
        .widgets-grid { grid-template-columns: repeat(2, 1fr); }
        .alert-banner, .success-banner { flex-direction: column; }
        .file-row { flex-direction: column; }
    }
</style>
@endpush

@section('content')

    <div class="page-header">
        <div class="page-title">Summary Kanban Material (SKM)</div>
        <div class="page-date">
            {{ now()->format('d M Y') }}
        </div>
    </div>

    <div class="content-body">

        @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#166534;font-weight:600;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#dc2626;font-weight:600;font-size:13px;">
            ✗ {{ session('error') }}
        </div>
        @endif

        {{-- WIDGETS --}}
        <div class="widgets-grid">
            <div class="widget-box">
                <div class="widget-number">{{ $stats['total'] }}</div>
                <div class="widget-label">Total SKM</div>
            </div>
            <div class="widget-box">
                <div class="widget-number" style="color:#64748b">{{ $stats['draft'] }}</div>
                <div class="widget-label">Draft</div>
            </div>
            <div class="widget-box">
                <div class="widget-number" style="color:#2563eb">{{ $stats['sent'] }}</div>
                <div class="widget-label">Dikirim</div>
            </div>
            <div class="widget-box">
                <div class="widget-number" style="color:#d97706">{{ $stats['partial_received'] }}</div>
                <div class="widget-label">Sebagian</div>
            </div>
            <div class="widget-box">
                <div class="widget-number" style="color:#16a34a">{{ $stats['completed'] }}</div>
                <div class="widget-label">Selesai</div>
            </div>
            <div class="widget-box {{ $stats['pending'] > 0 ? 'alert-widget' : 'safe-widget' }}">
                <div class="widget-number">{{ $stats['pending'] }}</div>
                <div class="widget-label">Perlu Order</div>
            </div>
        </div>

        {{-- ALERT / STATUS BANNER --}}
        @if($stats['pending'] > 0)
        <div class="alert-banner">
            <div>
                <div class="alert-text-main">{{ $stats['pending'] }} material SKM stoknya di bawah minimum!</div>
                <div class="alert-text-sub">Buat dokumen SKM sekarang untuk memesan material yang dibutuhkan.</div>
            </div>
            <a href="{{ route('summary_kanban.create') }}" class="btn btn-red">Buat SKM Sekarang</a>
        </div>
        @else
        <div class="success-banner">
            <div class="success-text">Semua stok material SKM mencukupi. Tidak ada item yang perlu dipesan.</div>
            <a href="{{ route('summary_kanban.create') }}" class="btn btn-blue">Buat SKM Manual</a>
        </div>
        @endif

        {{-- RIWAYAT SKM --}}
        <div class="card">
            <div class="card-header-basic">Riwayat SKM</div>
            <div class="table-responsive table-blue-header">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor SKM</th>
                            <th>Tanggal</th>
                            <th style="text-align:right">Jml Item</th>
                            <th style="text-align:center">Status</th>
                            <th>Dibuat oleh</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td style="font-family:monospace;font-weight:700;color:#2563eb">{{ $order->skm_number }}</td>
                            <td>{{ $order->order_date->format('d/m/Y') }}</td>
                            <td style="text-align:right;font-weight:600">{{ $order->items_count }}</td>
                            <td style="text-align:center">
                                @php
                                    $cls = match($order->status) {
                                        'draft'            => 'badge-gray',
                                        'sent'             => 'badge-blue',
                                        'partial_received' => 'badge-yellow',
                                        'completed'        => 'badge-green',
                                        'cancelled'        => 'badge-red',
                                        default            => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $cls }}">{{ $order->status_label }}</span>
                            </td>
                            <td style="color:#64748b">{{ $order->createdBy->name ?? '-' }}</td>
                            <td style="text-align:center">
                                <div style="display:flex;justify-content:center;gap:12px">
                                    <a href="{{ route('summary_kanban.show', $order) }}" style="color:#2563eb;font-weight:600;font-size:12px">Detail</a>
                                    @if($order->status === 'draft')
                                    <form method="POST" action="{{ route('summary_kanban.destroy', $order) }}"
                                          onsubmit="return confirm('Hapus SKM {{ $order->skm_number }}?')">
                                        @csrf @method('DELETE')
                                        <button style="color:#ef4444;font-weight:600;font-size:12px;background:none;border:none;cursor:pointer">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="empty-state">Belum ada dokumen SKM.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px">{{ $orders->links() }}</div>
        </div>

        {{-- PREVIEW ITEM PERLU DIPESAN --}}
        @if(!empty($pending))
        <div class="card">
            <div class="card-header-basic">Preview Item Perlu Dipesan</div>
            <div class="card-subtext">
                Kalkulasi kanban beredar: LT 3 hari + SS 2 hari + Proses 1 hari = 6 hari × kanban/hari.
                Klik "Buat SKM Sekarang" untuk memprosesnya.
            </div>
            <div class="table-responsive table-orange-header">
                <table>
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Vendor</th>
                            <th style="text-align:right">Stok Saat Ini</th>
                            <th style="text-align:right">Total Kanban</th>
                            <th style="text-align:right">Stok (kanban)</th>
                            <th style="text-align:right">Outstanding</th>
                            <th style="text-align:right">Qty/Kartu</th>
                            <th style="text-align:right">Saran Kartu</th>
                            <th style="text-align:right">Total Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $p)
                        <tr>
                            <td>
                                <span class="col-material-kode">{{ $p['material']->code }}</span>
                                <span class="col-material-nama">{{ $p['material']->name }}</span>
                            </td>
                            <td style="color:#64748b">{{ $p['material']->vendor->name ?? '-' }}</td>
                            <td class="col-stok" style="text-align:right">{{ number_format($p['current_stock'], 0) }}</td>
                            <td class="col-kanban-total" style="text-align:right">{{ $p['total_kanban'] }}</td>
                            <td style="text-align:right;color:#64748b">{{ $p['stock_kanban'] }}</td>
                            <td class="col-orange" style="text-align:right">{{ $p['outstanding_kanban'] }}</td>
                            <td style="text-align:right">{{ number_format($p['kanban_qty'], 0) }}</td>
                            <td class="col-saran" style="text-align:right">{{ $p['num_cards_suggest'] }}</td>
                            <td class="col-kanban-total" style="text-align:right">{{ number_format($p['order_qty_suggest'], 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- DATA DEMAND FP BULAN BERJALAN --}}
        <div class="card">
            <div class="card-header-basic">
                Data Demand FP Bulan Berjalan
                <div style="display:flex;gap:8px">
                    <a href="{{ route('summary_kanban.demands.template') }}" class="btn btn-green" style="font-size:12px;padding:6px 12px">
                        Download Template
                    </a>
                    @if($demands->isNotEmpty())
                    <form method="POST" action="{{ route('summary_kanban.demands.clear') }}"
                          onsubmit="return confirm('Hapus semua demand aktif?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline" style="font-size:12px;padding:6px 12px;color:#dc2626;border-color:#fca5a5">
                            Hapus Semua
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="card-subtext">
                Demand digunakan untuk menghitung total kanban beredar. Import sekali per bulan — data akan tetap aktif sampai diganti import baru.
                @if($demands->isNotEmpty())
                <span style="color:#2563eb;font-weight:600">
                    Periode aktif: {{ $demands->first()->period ?? '-' }} ({{ $demands->count() }} material FP/WIP)
                </span>
                @endif
            </div>
            <div class="demand-form-wrap">
                <form method="POST" action="{{ route('summary_kanban.demands.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div style="font-size:12px;font-weight:600;color:#334155;margin-bottom:8px">Upload File Excel (Demand Bulan Ini)</div>
                    <div class="file-row">
                        <input type="file" name="file" accept=".xlsx,.xls" required>
                        <button class="btn btn-blue" type="submit">Import &amp; Ganti Demand</button>
                    </div>
                </form>
            </div>

            @if($demands->isNotEmpty())
            <div class="table-responsive" style="padding:0 0 16px">
                <table>
                    <thead>
                        <tr>
                            <th>Material FP/WIP</th>
                            <th style="text-align:right">Demand (pcs)</th>
                            <th style="text-align:right">Hari Kerja</th>
                            <th>Periode</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($demands as $d)
                        <tr>
                            <td>
                                <span style="font-family:monospace;font-weight:700;color:#1e3a5f;font-size:12px">{{ $d->material->code ?? '-' }}</span>
                                <span style="color:#64748b;margin-left:6px">{{ $d->material->name ?? '-' }}</span>
                            </td>
                            <td style="text-align:right;font-weight:700">{{ number_format($d->demand_qty, 0) }}</td>
                            <td style="text-align:right">{{ $d->working_days }}</td>
                            <td>{{ $d->period ?? '-' }}</td>
                            <td style="color:#94a3b8">{{ $d->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="margin:0 20px 16px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e;">
                Belum ada demand aktif. Kanban dihitung berdasarkan min_stock sebagai fallback sampai demand diimport.
            </div>
            @endif
        </div>

    </div>

@endsection
