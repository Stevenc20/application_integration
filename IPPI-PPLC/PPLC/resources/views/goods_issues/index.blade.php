@extends('layouts.app')

@section('title', 'Goods Issue')

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
    .btn-red { background: #dc2626; color: white; }
    .btn-red:hover { background: #b91c1c; transform: translateY(-1px); }
    .btn-navy { background: var(--navy-mid); color: white; }
    .btn-navy:hover { background: var(--navy-light); transform: translateY(-1px); }
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

    /* Table wrap */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    thead tr { background: #f8f9fa; border-bottom: 2px solid #eaeaea; }
    thead th { padding: 10px 12px; text-align: left; font-weight: 800; color: #555; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .1s; }
    tbody tr:hover { background: #fdfdfd; }
    tbody td { padding: 8px 12px; color: #333; white-space: nowrap; vertical-align: middle; }

    .gi-code-link {
        color: var(--navy-dark);
        font-weight: 800;
        font-size: 12px;
        font-family: monospace;
        text-decoration: none;
    }
    .gi-code-link:hover {
        text-decoration: underline;
        color: var(--red-main);
    }

    .action-links {
        display: flex;
        gap: 12px;
    }
    .action-links a, .action-links button {
        background: transparent;
        border: none;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
    }
    .link-detail { color: var(--navy-dark); }
    .link-detail:hover { text-decoration: underline; }
    .link-edit { color: #f59e0b; }
    .link-edit:hover { text-decoration: underline; }
    .link-delete { color: #ef4444; }
    .link-delete:hover { text-decoration: underline; }

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

    /* Alerts */
    .alert { margin: 20px 28px 0; padding: 12px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .alert.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .alert.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    /* Modal Overlay & Modal */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px; }
    .modal-overlay.open { display: flex; }
    .modal { background: white; border-radius: 12px; padding: 24px; width: 100%; max-width: 680px; box-shadow: 0 10px 30px rgba(0,0,0,.2); max-height: 90vh; overflow-y: auto; }
    .modal h3 { font-size: 16px; font-weight: 800; color: var(--navy-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .modal h3 .material-icons { font-size: 20px; color: var(--red-main); }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; }
    .form-group input, .form-group select, .form-group textarea { border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; font-size: 12px; font-family: inherit; outline: none; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--red-main); }
    
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-cancel { background: #eee; color: #555; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .btn-save { background: var(--red-main); color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .btn-save:hover { background: var(--red-dark); }

    /* Item Section inside Modal */
    .items-container-box {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 16px;
        background: #fafafa;
        margin-top: 15px;
    }
    .items-header {
        font-size: 12px;
        font-weight: 800;
        color: var(--navy-dark);
        margin-bottom: 12px;
        border-bottom: 1px solid #eee;
        padding-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btn-add-item-row {
        background: var(--navy-mid);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 10px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-add-item-row:hover { background: var(--navy-light); }

    .item-row-entry {
        display: grid;
        grid-template-columns: 3fr 2fr 40px;
        gap: 10px;
        align-items: center;
        margin-bottom: 8px;
    }
    .btn-remove-row {
        background: #fecaca;
        color: #b91c1c;
        border: none;
        border-radius: 4px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    /* Detail Modal Table */
    .detail-table {
        width: 100%;
        border-collapse: collapse;
    }
    .detail-table tr {
        border-bottom: 1px solid #f1f5f9;
    }
    .detail-table tr:last-child {
        border-bottom: none;
    }
    .detail-table td {
        padding: 10px 8px;
        font-size: 13px;
    }
    .detail-table td.label-td {
        font-weight: 700;
        color: #64748b;
        width: 35%;
    }
    .detail-table td.value-td {
        color: #0f172a;
        font-weight: 600;
    }

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
        .form-row { grid-template-columns: 1fr; }
        .modal { width: 95%; margin: 10px; }
        .item-row-entry { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="alert success">
        <span class="material-icons">check_circle</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="alert error">
        <span class="material-icons">error</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert error">
        <span class="material-icons">error</span>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    {{-- Hero block matching rundown incoming style --}}
    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">outbox</span> Goods Issue</h2>
            <div class="hero-meta">Kelola pengeluaran pasokan barang keluar dan pencatatan pemotongan stok pada storage location</div>
        </div>
    </div>

    <div class="content-body">
        <div class="card">
            {{-- Toolbar Header --}}
            <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 14px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                    <div style="font-size: 15px; font-weight: 800; color: var(--navy-dark);">Goods Issue</div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('goods_issues.export', ['search' => $search, 'start_date' => $startDate, 'end_date' => $endDate, 'location_id' => $locationId]) }}" class="action-btn btn-green">
                            <span class="material-icons">file_download</span> Export Excel
                        </a>
                        <a href="{{ route('goods_issues.print_pdf', ['search' => $search, 'start_date' => $startDate, 'end_date' => $endDate, 'location_id' => $locationId]) }}" class="action-btn btn-red">
                            <span class="material-icons">print</span> Print PDF
                        </a>
                        <a href="{{ route('goods_issues.create') }}" class="action-btn btn-navy" style="background:var(--navy-dark); text-decoration: none;">
                            <span class="material-icons">add</span> + Buat GI
                        </a>
                    </div>
                </div>

                {{-- Filters form matches the screenshot layout --}}
                <form action="{{ route('goods_issues.index') }}" method="GET" class="filter-row">
                    <input type="text" name="search" value="{{ $search }}" class="filter-input" placeholder="No. GI..." style="width: 200px;">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="filter-input" placeholder="dd/mm/yyyy" style="width: 140px;">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="filter-input" placeholder="dd/mm/yyyy" style="width: 140px;">

                    <select name="location_id" class="filter-select">
                        <option value="Semua Lokasi">Semua Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                                {{ $loc->nama }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn-search-go"><span class="material-icons">search</span>Cari</button>
                    @if($search || $startDate || $endDate || ($locationId && $locationId !== 'Semua Lokasi'))
                        <a href="{{ route('goods_issues.index') }}" class="btn-search-reset">Kembali</a>
                    @endif
                </form>
            </div>

            {{-- Table wrap --}}
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. GI</th>
                            <th>Tanggal</th>
                            <th>Dari Lokasi</th>
                            <th>Keterangan</th>
                            <th style="width: 180px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($goodsIssues as $gi)
                        <tr>
                            <td>
                                <a href="javascript:void(0)" class="gi-code-link" onclick="showDetail({{ $gi->id }})">
                                    {{ $gi->no_gi }}
                                </a>
                            </td>
                            <td>{{ $gi->tanggal_issue->format('d/m/Y') }}</td>
                            <td style="font-weight: 700; color: #333;">{{ $gi->storageLocation->nama ?? '-' }}</td>
                            <td>{{ $gi->keterangan ?? '-' }}</td>
                            <td style="text-align: center;">
                                <div class="action-links" style="justify-content: center;">
                                    <a href="javascript:void(0)" class="link-detail" onclick="showDetail({{ $gi->id }})">Detail</a>
                                    <a href="javascript:void(0)" class="link-edit" onclick="showEdit({{ $gi->id }})">Edit</a>
                                    <form action="{{ route('goods_issues.destroy', $gi->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus GI {{ $gi->no_gi }}?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-delete">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 40px; display: block; margin-bottom: 8px;">search_off</span>
                                Belum ada data Goods Issue.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($goodsIssues->total() > 0 && $goodsIssues->lastPage() > 1)
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $goodsIssues->firstItem() ?? 0 }}-{{ $goodsIssues->lastItem() ?? 0 }}</strong> dari <strong>{{ $goodsIssues->total() }}</strong> Goods Issue
                </div>
                
                <div class="pagination">
                    {{-- Previous Page Link --}}
                    @if($goodsIssues->onFirstPage())
                        <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
                    @else
                        <a href="{{ $goodsIssues->previousPageUrl() }}" class="page-btn"><span class="material-icons">chevron_left</span></a>
                    @endif

                    {{-- Pagination Pages --}}
                    @php
                        $start = max(1, $goodsIssues->currentPage() - 2);
                        $end = min($goodsIssues->lastPage(), $goodsIssues->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $goodsIssues->url(1) }}" class="page-btn">1</a>
                        @if($start > 2)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $goodsIssues->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $goodsIssues->url($page) }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $goodsIssues->lastPage())
                        @if($end < $goodsIssues->lastPage() - 1)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                        <a href="{{ $goodsIssues->url($goodsIssues->lastPage()) }}" class="page-btn">{{ $goodsIssues->lastPage() }}</a>
                    @endif

                    {{-- Next Page Link --}}
                    @if($goodsIssues->hasMorePages())
                        <a href="{{ $goodsIssues->nextPageUrl() }}" class="page-btn"><span class="material-icons">chevron_right</span></a>
                    @else
                        <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>



    {{-- MODAL: EDIT GI --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h3><span class="material-icons">edit</span> Edit Goods Issue</h3>
            <form action="{{ route('goods_issues.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_no_gi">Nomor GI <span style="color: red;">*</span></label>
                        <input type="text" name="no_gi" id="edit_no_gi" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tanggal_issue">Tanggal Issue <span style="color: red;">*</span></label>
                        <input type="date" name="tanggal_issue" id="edit_tanggal_issue" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="edit_storage_location_id">Dari Storage Location <span style="color: red;">*</span></label>
                        <select name="storage_location_id" id="edit_storage_location_id" required>
                            <option value="">Pilih Lokasi...</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->kode }} - {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_keterangan">Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="2"></textarea>
                </div>

                {{-- Dynamic Items Container --}}
                <div class="items-container-box">
                    <div class="items-header">
                        <span>DAFTAR ITEM MATERIAL YANG DIKELUARKAN</span>
                        <button type="button" class="btn-add-item-row" onclick="addItemRow('edit_items_list')">+ Tambah Baris</button>
                    </div>
                    <div id="edit_items_list">
                        {{-- Rows appended here --}}
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: DETAIL GI --}}
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <h3><span class="material-icons">info</span> Detail Goods Issue</h3>
            <div style="margin-bottom: 20px;">
                <table class="detail-table">
                    <tr>
                        <td class="label-td">Nomor GI</td>
                        <td class="value-td" id="detail_no_gi" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Tanggal Issue</td>
                        <td class="value-td" id="detail_tanggal_issue"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Dari Storage Location</td>
                        <td class="value-td" id="detail_location"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Keterangan</td>
                        <td class="value-td" id="detail_keterangan"></td>
                    </tr>
                </table>
            </div>

            {{-- Items Table --}}
            <div class="items-container-box" style="background: white;">
                <div class="items-header">RINCIAN MATERIAL DIKELUARKAN</div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding: 8px;">Kode</th>
                            <th style="padding: 8px;">Nama Material</th>
                            <th style="padding: 8px; text-align: right;">Qty Dikeluarkan</th>
                            <th style="padding: 8px; text-align: center;">UOM</th>
                        </tr>
                    </thead>
                    <tbody id="detail_items_table_body">
                        {{-- Rows injected by JS --}}
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('detailModal')" style="width: 100%;">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    let addRowIndex = 1;
    let editRowIndex = 0;

    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    function addItemRow(containerId) {
        const container = document.getElementById(containerId);
        const prefix = containerId.startsWith('add') ? 'add' : 'edit';
        const index = prefix === 'add' ? addRowIndex : editRowIndex;
        const rowId = `${prefix}_row_${index}`;

        const rowHtml = `
            <div class="item-row-entry" id="${rowId}">
                <select name="items[${index}][material_id]" required class="filter-select" style="min-width:100px;">
                    <option value="">Pilih Material...</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}">{{ $material->kode }} - {{ $material->nama }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.001" name="items[${index}][qty]" placeholder="Qty Dikeluarkan..." required class="filter-input" style="height:34px;">
                <button type="button" class="btn-remove-row" onclick="removeRow('${rowId}')">
                    <span class="material-icons" style="font-size:16px;">delete</span>
                </button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', rowHtml);
        
        if (prefix === 'add') {
            addRowIndex++;
        } else {
            editRowIndex++;
        }
    }

    function removeRow(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
        }
    }

    function showDetail(giId) {
        fetch(`/goods-issues/${giId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('detail_no_gi').innerText = data.no_gi;
                
                const dateParts = data.tanggal_issue.split('-');
                document.getElementById('detail_tanggal_issue').innerText = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                
                document.getElementById('detail_location').innerText = data.storage_location_nama;
                document.getElementById('detail_keterangan').innerText = data.keterangan;

                // Load items
                const tbody = document.getElementById('detail_items_table_body');
                tbody.innerHTML = '';
                
                data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #eee';
                    tr.innerHTML = `
                        <td style="padding: 8px; font-family: monospace; font-weight: bold; color: var(--navy-dark);">${item.material_kode}</td>
                        <td style="padding: 8px;">${item.material_nama}</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold; color: #ef4444;">${parseFloat(item.qty).toLocaleString('en-US', {minimumFractionDigits: 3, maximumFractionDigits: 3})}</td>
                        <td style="padding: 8px; text-align: center; color: #666;">${item.material_uom}</td>
                    `;
                    tbody.appendChild(tr);
                });

                openModal('detailModal');
            })
            .catch(err => {
                alert('Gagal memuat rincian GI.');
            });
    }

    function showEdit(giId) {
        fetch(`/goods-issues/${giId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_no_gi').value = data.no_gi;
                document.getElementById('edit_tanggal_issue').value = data.tanggal_issue;
                document.getElementById('edit_storage_location_id').value = data.storage_location_id;
                document.getElementById('edit_keterangan').value = data.keterangan !== '-' ? data.keterangan : '';

                // Load items dynamically
                const container = document.getElementById('edit_items_list');
                container.innerHTML = '';
                editRowIndex = 0;

                data.items.forEach(item => {
                    const rowId = `edit_row_${editRowIndex}`;
                    const rowHtml = `
                        <div class="item-row-entry" id="${rowId}">
                            <select name="items[${editRowIndex}][material_id]" required class="filter-select" style="min-width:100px;">
                                <option value="${item.material_id}" selected>${item.material_kode} - ${item.material_nama}</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->kode }} - {{ $material->nama }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" name="items[${editRowIndex}][qty]" value="${item.qty}" placeholder="Qty Dikeluarkan..." required class="filter-input" style="height:34px;">
                            <button type="button" class="btn-remove-row" onclick="removeRow('${rowId}')">
                                <span class="material-icons" style="font-size:16px;">delete</span>
                            </button>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', rowHtml);
                    editRowIndex++;
                });

                openModal('editModal');
            })
            .catch(err => {
                alert('Gagal memuat data edit GI.');
            });
    }

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('open');
        }
    }
</script>
@endpush
