@extends('layouts.app')

@section('title', 'Stock Overview')

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
    }
    .hero-title-block h2 .material-icons { font-size: 32px; opacity: 0.8; }
    .hero-meta {
        color: rgba(255,255,255,0.75);
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
    }

    /* ===== CONTENT BODY ===== */
    .content-body { padding: 20px 28px; }

    .card { background: white; border-radius: 12px; border: 1px solid #eee; overflow: visible !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .card-header { padding: 14px 20px; border-bottom: 1px solid #f5f5f5; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; overflow: visible !important; }

    /* Actions Bar Custom Styles */
    .action-btn { 
        height: 38px;
        padding: 0 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        white-space: nowrap;
        text-decoration: none;
    }
    .btn-green { background: #10b981; color: white; }
    .btn-green:hover { background: #059669; transform: translateY(-1px); }

    /* Filter Form */
    .filter-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
        margin-bottom: 10px;
    }
    .filter-input {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 12px;
        font-family: inherit;
        background: #f9f9f9;
        outline: none;
        height: 34px;
        box-sizing: border-box;
    }
    .filter-input:focus {
        border-color: var(--red-main);
        background: white;
    }
    .filter-select {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 0 12px;
        font-size: 12px;
        font-family: inherit;
        background: #f9f9f9;
        outline: none;
        height: 34px;
        min-width: 140px;
        box-sizing: border-box;
    }
    .filter-select:focus {
        border-color: var(--red-main);
        background: white;
    }

    /* Checkbox styling */
    .filter-checkbox-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        cursor: pointer;
        user-select: none;
        margin-right: 8px;
    }
    .filter-checkbox-wrap input {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    .btn-search-go {
        background: var(--navy-dark);
        color: white;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 12px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        height: 34px;
        box-sizing: border-box;
    }
    .btn-search-go:hover {
        background: var(--navy-mid);
    }
    .btn-search-reset {
        background: #eee;
        color: #555;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 12px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        height: 34px;
        box-sizing: border-box;
    }
    .btn-search-reset:hover {
        background: #ddd;
    }
    .btn-mutations-log {
        background: var(--navy-dark);
        color: white;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 12px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        height: 34px;
        box-sizing: border-box;
    }
    .btn-mutations-log:hover {
        background: var(--navy-mid);
    }

    /* Table wrap */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    thead tr { background: #f8f9fa; border-bottom: 2px solid #eaeaea; }
    thead th { padding: 10px 12px; text-align: left; font-weight: 800; color: #555; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .1s; }
    tbody tr:hover { background: #fdfdfd; }
    tbody td { padding: 8px 12px; color: #333; white-space: nowrap; vertical-align: middle; }

    .material-code-link {
        color: var(--navy-dark);
        font-weight: 800;
        font-size: 12px;
        font-family: monospace;
        text-decoration: none;
    }
    .material-code-link:hover {
        text-decoration: underline;
        color: var(--red-main);
    }

    /* Status Badges */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        display: inline-block;
        text-align: center;
    }
    .status-badge.normal { background: #f0fdf4; color: #15803d; }
    .status-badge.rendah { background: #fffbeb; color: #d97706; }
    .status-badge.habis { background: #fef2f2; color: #ef4444; }

    /* Quantity Colors */
    .qty-display { font-weight: 800; font-size: 12px; }
    .qty-display.normal { color: #15803d; }
    .qty-display.rendah { color: #d97706; }
    .qty-display.habis { color: #ef4444; }

    /* Pagination */
    .pagination-wrap { padding: 16px 24px 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
    .pagination-info { font-size: 12px; color: #737780; }
    .pagination-info strong { color: #333; }
    .pagination { display: flex; gap: 4px; align-items: center; }
    .page-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e5e5; background: white; font-size: 12px; font-weight: 600; color: #555; display: flex; align-items: center; justify-content: center; text-decoration: none; }
    .page-btn:hover { border-color: var(--red-main); color: var(--red-main); }
    .page-btn.active { background: var(--red-main); color: white; border-color: var(--red-main); }
    .page-btn.disabled { opacity: .35; pointer-events: none; }
    .page-btn .material-icons { font-size: 15px; }

    /* Modal Overlay & Modal */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px; }
    .modal-overlay.open { display: flex; }
    .modal { background: white; border-radius: 12px; padding: 24px; width: 100%; max-width: 850px; box-shadow: 0 10px 30px rgba(0,0,0,.2); max-height: 90vh; overflow-y: auto; }
    .modal h3 { font-size: 16px; font-weight: 800; color: var(--navy-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .modal h3 .material-icons { font-size: 20px; color: var(--red-main); }
    
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-cancel { background: #eee; color: #555; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }

    @media (max-width: 768px) {
        .hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
        }
        .content-body { padding: 15px; }
        .card-header { flex-direction: column; align-items: stretch; }
        .filter-row { flex-direction: column; align-items: stretch; }
        .filter-input, .filter-select { width: 100%; }
        .modal { width: 95%; margin: 10px; }
    }
</style>
@endpush

@section('content')

    {{-- Hero block matching rundown incoming style --}}
    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">inventory_2</span> Stock Overview</h2>
            <div class="hero-meta">Ringkasan ketersediaan pasokan material, minimum limit pengawasan stok, dan mutasi terpadu</div>
        </div>
    </div>

    <div class="content-body">
        <div class="card">
            {{-- Toolbar Header --}}
            <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 14px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                    <div style="font-size: 15px; font-weight: 800; color: var(--navy-dark);">Stock Overview</div>
                    <div>
                        <a href="{{ route('stock_overviews.export', ['search' => $search, 'location_id' => $locationId, 'status' => $status, 'min_stock' => $minStockOnly ? 1 : null]) }}" class="action-btn btn-green">
                            <span class="material-icons">file_download</span> Export Excel
                        </a>
                    </div>
                </div>

                {{-- Filters form matches the screenshot layout --}}
                <form action="{{ route('stock_overviews.index') }}" method="GET" class="filter-row">
                    <input type="text" name="search" value="{{ $search }}" class="filter-input" placeholder="Kode/nama material..." style="width: 250px;">
                    
                    <select name="location_id" class="filter-select">
                        <option value="Semua Lokasi">Semua Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                                {{ $loc->nama }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="filter-select">
                        <option value="Semua Status">Semua Status</option>
                        <option value="Normal" {{ $status == 'Normal' ? 'selected' : '' }}>Normal</option>
                        <option value="Rendah" {{ $status == 'Rendah' ? 'selected' : '' }}>Rendah</option>
                        <option value="Habis" {{ $status == 'Habis' ? 'selected' : '' }}>Habis</option>
                    </select>

                    <label class="filter-checkbox-wrap">
                        <input type="checkbox" name="min_stock" value="1" {{ $minStockOnly ? 'checked' : '' }}>
                        <span>Stok Minim</span>
                    </label>

                    <button type="submit" class="btn-search-go"><span class="material-icons">search</span>Cari</button>
                    @if($search || ($locationId && $locationId !== 'Semua Lokasi') || ($status && $status !== 'Semua Status') || $minStockOnly)
                        <a href="{{ route('stock_overviews.index') }}" class="btn-search-reset">Kembali</a>
                    @endif
                    <button type="button" class="btn-mutations-log" style="background:#1e293b;" onclick="openModal('mutationsModal')">
                        Riwayat Mutasi
                    </button>
                </form>
            </div>

            {{-- Table wrap --}}
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Material</th>
                            <th>Tipe</th>
                            <th>Lokasi</th>
                            <th style="text-align: right;">Qty Stok</th>
                            <th style="text-align: center;">Stok di Vendor</th>
                            <th style="text-align: center;">UoM</th>
                            <th style="text-align: right;">Min. Stok</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $stock)
                        @php
                            $qty = $stock->qty;
                            $min = $stock->material->min_stok ?? 0;
                            
                            if ($qty <= 0) {
                                $class = 'habis';
                                $label = 'Habis';
                            } elseif ($qty <= $min) {
                                $class = 'rendah';
                                $label = 'Rendah';
                            } else {
                                $class = 'normal';
                                $label = 'Normal';
                            }
                        @endphp
                        <tr>
                            <td>
                                <a href="javascript:void(0)" class="material-code-link">
                                    {{ $stock->material->kode ?? '-' }}
                                </a>
                            </td>
                            <td style="font-weight: 700; color: #333;">{{ $stock->material->nama ?? '-' }}</td>
                            <td>{{ $stock->material->tipe ?? '-' }}</td>
                            <td>{{ $stock->storageLocation->nama ?? '-' }}</td>
                            <td style="text-align: right;">
                                <span class="qty-display {{ $class }}">
                                    {{ number_format($qty, 0) }}
                                </span>
                            </td>
                            <td style="text-align: center; color: #bbb;">
                                {{ $stock->qty_vendor > 0 ? number_format($stock->qty_vendor, 0) : '—' }}
                            </td>
                            <td style="text-align: center; font-weight: 700; color: #666;">
                                {{ $stock->material->uom ?? '-' }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #444;">
                                {{ number_format($min, 0) }}
                            </td>
                            <td style="text-align: center;">
                                <span class="status-badge {{ $class }}">
                                    {{ $label }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 40px; display: block; margin-bottom: 8px;">inventory_2</span>
                                Tidak ada data stok material yang cocok.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($stocks->total() > 0 && $stocks->lastPage() > 1)
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $stocks->firstItem() ?? 0 }}-{{ $stocks->lastItem() ?? 0 }}</strong> dari <strong>{{ $stocks->total() }}</strong> Material Stock
                </div>
                
                <div class="pagination">
                    {{-- Previous Page Link --}}
                    @if($stocks->onFirstPage())
                        <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
                    @else
                        <a href="{{ $stocks->previousPageUrl() }}" class="page-btn"><span class="material-icons">chevron_left</span></a>
                    @endif

                    {{-- Pagination Pages --}}
                    @php
                        $start = max(1, $stocks->currentPage() - 2);
                        $end = min($stocks->lastPage(), $stocks->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $stocks->url(1) }}" class="page-btn">1</a>
                        @if($start > 2)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $stocks->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $stocks->url($page) }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $stocks->lastPage())
                        @if($end < $stocks->lastPage() - 1)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                        <a href="{{ $stocks->url($stocks->lastPage()) }}" class="page-btn">{{ $stocks->lastPage() }}</a>
                    @endif

                    {{-- Next Page Link --}}
                    @if($stocks->hasMorePages())
                        <a href="{{ $stocks->nextPageUrl() }}" class="page-btn"><span class="material-icons">chevron_right</span></a>
                    @else
                        <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL: RIWAYAT MUTASI --}}
    <div class="modal-overlay" id="mutationsModal">
        <div class="modal">
            <h3><span class="material-icons">history</span> Riwayat Mutasi Pasokan (GR & GI)</h3>
            
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; position: sticky; top: 0; z-index: 10;">
                            <th style="padding: 8px;">Tanggal</th>
                            <th style="padding: 8px;">Tipe</th>
                            <th style="padding: 8px;">Dokumen</th>
                            <th style="padding: 8px;">Lokasi</th>
                            <th style="padding: 8px;">Kode</th>
                            <th style="padding: 8px;">Nama Material</th>
                            <th style="padding: 8px; text-align: right;">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutations as $m)
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 8px;">{{ \Carbon\Carbon::parse($m['tanggal'])->format('d/m/Y') }}</td>
                            <td style="padding: 8px; font-weight: 800; color: {{ $m['color'] }};">{{ $m['tipe'] }}</td>
                            <td style="padding: 8px; font-family: monospace; font-weight: bold;">{{ $m['dokumen'] }}</td>
                            <td style="padding: 8px;">{{ $m['lokasi'] }}</td>
                            <td style="padding: 8px; font-family: monospace;">{{ $m['kode'] }}</td>
                            <td style="padding: 8px;">{{ $m['nama'] }}</td>
                            <td style="padding: 8px; text-align: right; font-weight: bold; color: {{ $m['color'] }};">
                                {{ $m['qty'] }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">
                                Belum ada riwayat mutasi transaksi posting.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('mutationsModal')" style="width: 100%;">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('open');
        }
    }
</script>
@endpush
