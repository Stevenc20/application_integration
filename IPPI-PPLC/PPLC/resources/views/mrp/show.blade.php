@extends('layouts.app')

@section('title', 'Hasil MRP Run')

@push('styles')
<style>
    /* ===== HERO ===== */
    .hero {
        background: var(--red-main);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }
    .hero-title-block h2 {
        font-size: 28px;
        font-weight: 900;
        color: white;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
    }
    .hero-title-block h2 .material-icons { font-size: 32px; opacity: 0.8; }
    .hero-meta {
        color: rgba(255,255,255,0.75);
        font-size: 13px;
        font-weight: 500;
        margin-top: 6px;
    }

    .content-body {
        padding: 24px 28px;
        background: #f8fafc;
        min-height: calc(100vh - 70px);
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
    }

    .card-title-mrp {
        font-size: 16px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 6px;
    }
    .card-subtitle-mrp {
        font-size: 12px;
        color: #94a3b8;
        margin-bottom: 20px;
    }

    /* STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .stat-card {
        padding: 16px;
        border-radius: 8px;
        font-size: 13px;
    }
    .stat-blue { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .stat-red { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
    .stat-yellow { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        margin-top: 4px;
    }

    .table-responsive {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    thead th {
        background: var(--red-main);
        color: white;
        padding: 12px 16px;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }
    tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .badge-rec {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        display: inline-block;
    }
    .badge-purchase { background: #fef2f2; color: #991b1b; }
    .badge-production { background: #fef9c3; color: #854d0e; }

    .btn-action-top {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-excel { background: #16a34a; color: white; border: none; }
    .btn-excel:hover { background: #15803d; }
    .btn-pdf { background: #dc2626; color: white; border: none; }
    .btn-pdf:hover { background: #b91c1c; }
    .btn-gray { background: #e2e8f0; color: #334155; border: none; }
    .btn-gray:hover { background: #cbd5e1; }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">account_tree</span> MRP - Material Requirements Planning</h2>
            <div class="hero-meta">Detail Hasil Eksekusi MRP Run</div>
        </div>
    </div>

    <div class="content-body">
        
        <!-- CARD TOP: META & ACTIONS -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:16px;">
                <div>
                    <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; font-weight:700;">MRP Run</div>
                    <div style="font-size:20px; font-weight:800; color:#1e3a8a;">
                        {{ $mrpRun->created_at ? $mrpRun->created_at->format('d M Y H:i') : '-' }} WIB
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:4px;">
                        Dijalankan oleh: <strong>{{ $mrpRun->runBy->name ?? '-' }}</strong>
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <a href="{{ route('mrp.excel', $mrpRun->id) }}" class="btn-action-top btn-excel">
                        <span class="material-icons" style="font-size:16px;">download</span> Export Excel
                    </a>
                    <a href="{{ route('mrp.pdf', $mrpRun->id) }}" target="_blank" class="btn-action-top btn-pdf">
                        <span class="material-icons" style="font-size:16px;">picture_as_pdf</span> Print PDF
                    </a>
                    <a href="{{ route('mrp.index') }}" class="btn-action-top btn-gray">Kembali</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div>Total Material</div>
                    <div class="stat-value">{{ $mrpRun->results->count() }}</div>
                </div>
                <div class="stat-card stat-red">
                    <div>Perlu Pengadaan (PO)</div>
                    <div class="stat-value">{{ $mrpRun->results->where('recommendation_type', 'purchase')->count() }}</div>
                </div>
                <div class="stat-card stat-yellow">
                    <div>Perlu Produksi</div>
                    <div class="stat-value">{{ $mrpRun->results->where('recommendation_type', 'production')->count() }}</div>
                </div>
            </div>
        </div>

        <!-- CARD BOTTOM: RESULTS TABLE -->
        <div class="card">
            <div class="card-title-mrp">Detail Hasil MRP</div>
            <div class="card-subtitle-mrp" style="margin-bottom:8px;">
                Formula: <strong>Gross</strong> = BOM explosion multi-level (FP &rarr; WIP &rarr; RM) &nbsp;|&nbsp;
                <strong>Net</strong> = Gross - Stok Tersedia - Sisa PO &nbsp;|&nbsp;
                <strong>+Safety 20%</strong> &nbsp;|&nbsp;
                <strong>Order</strong> = round-up ke Qty/Case
            </div>
            <div style="font-size:11px; color:#b45309; background:#fffbeb; border:1px solid #fef3c7; padding:8px 12px; border-radius:6px; margin-bottom:20px;">
                * Stok Tersedia = Stok RM aktual + Stok FP/WIP dikonversi ke RM via BOM (stok FP &divide; base qty &times; qty komponen)
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th style="text-align:right;">Gross Req.</th>
                            <th style="text-align:right;">Sisa PO</th>
                            <th style="text-align:right;">Net Req.</th>
                            <th style="text-align:right;">Safety 20%</th>
                            <th style="text-align:right;">Total + Safety</th>
                            <th style="text-align:right;">Qty/Case</th>
                            <th style="text-align:right;">Rekomendasi Order</th>
                            <th style="text-align:right;">Stok Tersedia*</th>
                            <th style="text-align:center;">Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mrpRun->results->sortBy(fn($r) => $r->recommendation_type === 'purchase' ? 0 : 1) as $result)
                        @php
                            $withSafety = (float)$result->net_requirement + (float)$result->safety_stock_qty;
                        @endphp
                        <tr style="{{ $result->recommendation_type === 'purchase' ? 'background: #fef2f2/30;' : '' }}">
                            <td>
                                <div style="font-weight:bold; font-family:monospace; color:#1e3a8a;">{{ $result->material->kode ?? '' }}</div>
                                <div style="font-weight:500; color:#334155;">{{ $result->material->nama ?? '-' }}</div>
                                <div style="font-size:10px; color:#94a3b8;">{{ $result->material->uom ?? '' }}</div>
                            </td>
                            <td style="text-align:right;">{{ number_format($result->gross_requirement, 3) }}</td>
                            <td style="text-align:right; color:#15803d;">
                                {{ (float)$result->open_po_qty > 0 ? number_format($result->open_po_qty, 3) : '-' }}
                            </td>
                            <td style="text-align:right; font-weight:600;">{{ number_format($result->net_requirement, 3) }}</td>
                            <td style="text-align:right; color:#b45309;">+{{ number_format($result->safety_stock_qty, 3) }}</td>
                            <td style="text-align:right; font-weight:600; color:#1e3a8a;">{{ number_format($withSafety, 3) }}</td>
                            <td style="text-align:right; color:#64748b;">
                                {{ (float)$result->qty_per_case > 0 ? number_format($result->qty_per_case, 3) : '-' }}
                            </td>
                            <td style="text-align:right; font-weight:800; font-size:14px; color:#1e293b;">
                                {{ number_format($result->recommended_quantity, 3) }}
                            </td>
                            <td style="text-align:right; font-weight:600; color: {{ (float)$result->current_stock < (float)$result->gross_requirement ? '#dc2626' : '#15803d' }}">
                                {{ number_format($result->current_stock, 3) }}
                            </td>
                            <td style="text-align:center;">
                                @if($result->recommendation_type === 'purchase')
                                <span class="badge-rec badge-purchase">Buat PO</span>
                                @else
                                <span class="badge-rec badge-production">Produksi</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:30px; color:#94a3b8; font-style:italic;">Tidak ada hasil MRP.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
