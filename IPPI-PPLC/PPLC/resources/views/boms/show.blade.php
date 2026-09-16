@extends('layouts.app')

@section('title', 'Detail BOM')

@push('styles')
<style>
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

    /* HEADER CARD INFO */
    .bom-info-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 20px;
    }
    .bom-info-label {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .bom-info-number {
        font-size: 24px;
        font-weight: 800;
        color: #3b82f6;
        margin-bottom: 12px;
        font-family: monospace;
    }
    .bom-info-material {
        font-size: 14px;
        color: #475569;
        font-weight: 500;
        margin-bottom: 12px;
    }
    .bom-info-qty {
        font-size: 13px;
        color: #334155;
        font-weight: 600;
    }
    
    .bom-info-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .badge-status {
        background: #dcfce7;
        color: #16a34a;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }
    .btn-edit {
        background: #eab308;
        color: white;
        text-decoration: none;
        padding: 8px 24px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 13px;
    }
    .btn-edit:hover { background: #ca8a04; }
    .btn-back {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        padding: 8px 24px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 13px;
    }
    .btn-back:hover { background: #cbd5e1; }

    /* KOMPONEN TABEL */
    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 20px;
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
        background: #f8fafc;
        color: #334155;
        padding: 14px 20px;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
        border-bottom: 2px solid #e2e8f0;
    }
    tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
        color: #475569;
        font-weight: 500;
    }
    .col-kode {
        color: #3b82f6;
        font-family: monospace;
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">account_tree</span> Detail BOM: {{ $bom->bom_number }}</h2>
        </div>
    </div>

    <div class="content-body">
        
        <!-- CARD INFO BOM -->
        <div class="card bom-info-wrapper">
            <div>
                <div class="bom-info-label">Nomor BOM</div>
                <div class="bom-info-number">{{ $bom->bom_number }}</div>
                <div class="bom-info-material">
                    <span style="font-family: monospace; font-weight: bold; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 12px; margin-right: 6px;">{{ $bom->material->kode ?? '' }}</span>
                    <strong>{{ $bom->material->nama ?? '-' }}</strong>
                </div>
                <div class="bom-info-qty" style="margin-top: 10px;">
                    Qty Base: <strong>{{ fmt_qty($bom->base_quantity) }}</strong> {{ $bom->material->uom ?? 'PCS' }}
                </div>
                @if($bom->description)
                <div style="margin-top: 8px; font-size: 12px; color: #64748b;">
                    Catatan: {{ $bom->description }}
                </div>
                @endif
            </div>
            <div class="bom-info-actions">
                <span class="badge-status" style="{{ $bom->status === 'active' ? 'background:#dcfce7; color:#16a34a;' : 'background:#f1f5f9; color:#64748b;' }}">
                    {{ $bom->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </span>
                <a href="{{ route('boms.edit', $bom->id) }}" class="btn-edit">Edit</a>
                <a href="{{ route('boms.index') }}" class="btn-back">Kembali</a>
            </div>
        </div>

        <!-- CARD KOMPONEN -->
        <div class="card">
            <div class="card-title">Komponen BOM ({{ $bom->items->count() }})</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Kode</th>
                            <th>Nama Material</th>
                            <th>Tipe</th>
                            <th style="text-align: right;">Qty</th>
                            <th>UoM</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bom->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="col-kode">{{ $item->material->kode ?? '' }}</td>
                            <td>{{ $item->material->nama ?? '-' }}</td>
                            <td>
                                <span style="font-weight: bold; color: {{ $item->material->tipe === 'RM' ? '#e11d48' : '#2563eb' }}">
                                    {{ $item->material->tipe ?? '-' }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: bold; color: #0f172a;">{{ fmt_qty($item->quantity) }}</td>
                            <td>{{ $item->unit ?? ($item->material->uom ?? '-') }}</td>
                            <td>{{ $item->notes ?: '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Tidak ada komponen di dalam BOM ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection
