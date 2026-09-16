@extends('layouts.app')

@section('title', 'MRP - Material Requirements Planning')

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

    /* CARD 1: DEMAND UPLOAD */
    .upload-container {
        border: 1px solid #e0f2fe;
        background: #f0f9ff;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 20px;
    }
    @media(min-width: 768px) {
        .upload-container {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }
    .upload-box-left {
        flex: 1;
    }
    .upload-label {
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
        display: block;
    }
    .file-input-wrapper {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }
    .file-input-custom {
        flex: 1;
        min-width: 250px;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 13px;
        color: #64748b;
    }
    .btn-import {
        background: #2563eb;
        color: white;
        border: none;
        padding: 8px 24px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-import:hover { background: #1d4ed8; }

    .upload-box-right {
        text-align: left;
        min-width: 150px;
    }
    @media(min-width: 768px) {
        .upload-box-right {
            text-align: right;
        }
    }
    .template-label {
        font-size: 11px;
        color: #94a3b8;
        margin-bottom: 6px;
        display: block;
    }
    .btn-template {
        background: transparent;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-block;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-template:hover { background: #eff6ff; border-color: #2563eb; }
    
    .format-info {
        font-size: 11px;
        color: #2563eb;
        margin-top: 8px;
    }

    .empty-state {
        text-align: center;
        padding: 30px;
        color: #94a3b8;
        font-style: italic;
        font-size: 13px;
    }

    /* CARD 2: JALANKAN MRP */
    .card-jalankan {
        display: flex;
        flex-direction: column;
        gap: 16px;
        justify-content: space-between;
        align-items: flex-start;
    }
    @media(min-width: 768px) {
        .card-jalankan {
            flex-direction: row;
            align-items: center;
        }
    }
    .btn-run-mrp {
        background: #2563eb;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .btn-run-mrp:hover:not(:disabled) {
        background: #1d4ed8;
    }
    .btn-run-mrp:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        color: #94a3b8;
    }

    /* CARD 3: RIWAYAT */
    .card-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .btn-delete-all {
        background: transparent;
        color: #dc2626;
        border: none;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        padding: 4px 8px;
    }
    .btn-delete-all:hover {
        text-decoration: underline;
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
        padding: 14px 20px;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }
    tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .badge-tipe {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        display: inline-block;
    }
    .badge-fp { background: #fae8ff; color: #a21caf; }
    .badge-wip { background: #dbeafe; color: #1d4ed8; }

    .btn-action {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        margin-right: 12px;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 12px;
    }
    .btn-action:hover {
        text-decoration: underline;
    }
    .btn-action-danger {
        color: #dc2626;
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">account_tree</span> MRP - Material Requirements Planning</h2>
            <div class="hero-meta">Sistem Perencanaan Kebutuhan Material Terpadu</div>
        </div>
    </div>

    <div class="content-body">
        
        @if(session('success'))
            <div class="alert alert-success" style="background:#bbf7d0; color:#15803d; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid #86efac;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="background:#fecaca; color:#b91c1c; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid #fca5a5;">
                {{ session('error') }}
            </div>
        @endif
        
        <!-- CARD 1: DEMAND ORDER CUSTOMER -->
        <div class="card">
            <div class="card-header-flex">
                <div>
                    <div class="card-title-mrp">Demand Order Customer</div>
                    <div class="card-subtitle-mrp" style="margin-bottom:0;">Import file Excel berisi daftar order FP/WIP dari customer. MRP akan mengeksplosi secara multi-level ke bahan baku (RM) via BOM.</div>
                </div>
                @if($demands->isNotEmpty())
                    <form method="POST" action="{{ route('mrp.demands.clear') }}" onsubmit="return confirm('Hapus semua demand aktif?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-delete-all">Hapus Semua ({{ $demands->count() }})</button>
                    </form>
                @endif
            </div>

            <div class="upload-container">
                <div class="upload-box-left">
                    <span class="upload-label">Upload File Excel Demand (.xlsx / .xls)</span>
                    <form method="POST" action="{{ route('mrp.demands.import') }}" enctype="multipart/form-data" class="file-input-wrapper">
                        @csrf
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required class="file-input-custom">
                        <button type="submit" class="btn-import">Import</button>
                    </form>
                    <div class="format-info">Format: Kolom A = Kode Material FP/WIP | Kolom B = Qty Order | Kolom C = Customer (opsional) | Kolom D = Notes (opsional). Baris 1 = header (dilewati otomatis).</div>
                </div>
                <div class="upload-box-right">
                    <span class="template-label">Belum punya template?</span>
                    <a href="{{ route('mrp.demands.template') }}" class="btn-template">Unduh Template</a>
                </div>
            </div>

            @if($demands->isEmpty())
                <div class="empty-state">Belum ada demand. Upload file Excel di atas.</div>
            @else
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 15%;">Kode</th>
                                <th style="width: 25%;">Nama Material</th>
                                <th style="width: 10%; text-align:center;">Tipe</th>
                                <th style="width: 15%; text-align:right;">Qty Order</th>
                                <th style="width: 15%;">Customer</th>
                                <th style="width: 10%;">Catatan</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($demands as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td style="font-weight:bold; font-family:monospace; color:#1e3a8a;">{{ $d->material->kode ?? '' }}</td>
                                <td>{{ $d->material->nama ?? '-' }}</td>
                                <td style="text-align:center;">
                                    <span class="badge-tipe {{ strtolower($d->material->tipe) == 'fp' ? 'badge-fp' : 'badge-wip' }}">
                                        {{ $d->material->tipe ?? '' }}
                                    </span>
                                </td>
                                <td style="text-align:right; font-weight:bold;">{{ number_format($d->order_quantity, 3) }}</td>
                                <td>{{ $d->customer_name ?? '-' }}</td>
                                <td style="color:#64748b; font-size:11px;">{{ $d->notes ?? '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('mrp.demands.destroy', $d->id) }}" onsubmit="return confirm('Hapus demand ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action btn-action-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- CARD 2: JALANKAN MRP -->
        <div class="card card-jalankan">
            <div>
                <div class="card-title-mrp">Jalankan MRP</div>
                <div class="card-subtitle-mrp" style="margin-bottom:0;">Formula: Gross = BOM explosion multi-level (FP&rarr;WIP&rarr;RM) &rarr; Net = Gross - Stok - Sisa PO (approved/partial) &rarr; +Safety 20% &rarr; Order = round-up ke Qty/Case.</div>
            </div>
            <form method="POST" action="{{ route('mrp.run') }}" onsubmit="return confirm('Jalankan MRP Run sekarang dengan {{ $demands->count() }} demand?')">
                @csrf
                <button type="submit" class="btn-run-mrp" @disabled($demands->isEmpty())>
                    Jalankan MRP {{ $demands->isNotEmpty() ? '('.$demands->count().' item)' : '' }}
                </button>
            </form>
        </div>

        <!-- CARD 3: RIWAYAT MRP RUN -->
        <div class="card">
            <div class="card-header-flex">
                <div>
                    <div class="card-title-mrp">Riwayat MRP Run</div>
                    <div class="card-subtitle-mrp" style="margin-bottom:0;">Daftar eksekusi MRP planning yang telah dilakukan sebelumnya.</div>
                </div>
                <a href="{{ route('mrp.export-pdf') }}" target="_blank" class="btn-import" style="background:#dc2626; color:white; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-icons" style="font-size:16px;">picture_as_pdf</span> Print PDF
                </a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Tanggal Run</th>
                            <th style="width: 25%; text-align:right;">Jml Hasil</th>
                            <th style="width: 30%;">Dijalankan oleh</th>
                            <th style="width: 20%; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->created_at ? $run->created_at->format('d M Y H:i') : '-' }} WIB</td>
                            <td style="text-align:right; font-weight:bold;">{{ $run->results ? $run->results->count() : 0 }} material</td>
                            <td>{{ $run->runBy->name ?? '-' }}</td>
                            <td style="text-align:center;">
                                <div style="display:flex; justify-content:center; gap:8px;">
                                    <a href="{{ route('mrp.show', $run->id) }}" class="btn-action">Lihat Hasil</a>
                                    <form method="POST" action="{{ route('mrp.destroy', $run->id) }}" onsubmit="return confirm('Hapus MRP Run ini? Semua data hasil akan dihapus.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action btn-action-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px; color:#94a3b8; font-style:italic;">Belum ada riwayat MRP Run.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($runs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $runs->hasPages())
                <div style="margin-top:20px;">
                    {{ $runs->links() }}
                </div>
            @endif
        </div>

    </div>

@endsection
