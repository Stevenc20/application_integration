@extends('layouts.app')

@section('title', 'Storage Location')

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

    .toolbar-group { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

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
    .btn-outline { background: white; border: 1px solid #ddd; color: #555; }
    .btn-outline:hover { border-color: var(--red-main); color: var(--red-main); transform: translateY(-1px); }

    /* Search Box */
    .search-box { display: flex; align-items: center; gap: 8px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 6px 12px; }
    .search-box .material-icons { font-size: 16px; color: #999; }
    .search-box input { border: none; background: transparent; outline: none; font-size: 12px; font-family: inherit; width: 220px; }

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

    .location-code-link {
        color: var(--navy-dark);
        font-weight: 800;
        font-size: 12px;
        font-family: monospace;
        text-decoration: none;
    }
    .location-code-link:hover {
        text-decoration: underline;
        color: var(--red-main);
    }

    /* Pill Badges */
    .pill-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .pill-rm {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .pill-wip {
        background: #fef3c7;
        color: #d97706;
    }
    .pill-fp {
        background: #dcfce7;
        color: #15803d;
    }
    .pill-scrap {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
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
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal { background: white; border-radius: 12px; padding: 24px; width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
    .modal h3 { font-size: 16px; font-weight: 800; color: var(--navy-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .modal h3 .material-icons { font-size: 20px; color: var(--red-main); }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; }
    .form-group input, .form-group select { border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; font-size: 12px; font-family: inherit; outline: none; }
    .form-group input:focus, .form-group select:focus { border-color: var(--red-main); }
    
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-cancel { background: #eee; color: #555; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .btn-save { background: var(--red-main); color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }
    .btn-save:hover { background: var(--red-dark); }

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
        .toolbar-group { flex-direction: column; align-items: stretch; }
        .search-box { width: 100% !important; }
        .search-box input { width: 100%; }
        .form-row { grid-template-columns: 1fr; }
        .modal { width: 95%; margin: 10px; }
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
            <h2><span class="material-icons">location_on</span> Storage Location</h2>
            <div class="hero-meta">Kelola data area penyimpanan material, deskripsi lokasi, tipe material, dan klasifikasi scrap</div>
        </div>
    </div>

    <div class="content-body">
        <div class="card">
            {{-- Toolbar Header --}}
            <div class="card-header">
                {{-- Left: Search form --}}
                <form action="{{ route('storage_locations.index') }}" method="GET" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; flex:1;">
                    <div class="search-box">
                        <span class="material-icons">search</span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode / nama lokasi...">
                    </div>
                    <button type="submit" class="btn-search-go"><span class="material-icons">search</span>Cari</button>
                    @if($search)
                    <a href="{{ route('storage_locations.index') }}" class="btn-search-reset">Kembali</a>
                    @endif
                </form>

                {{-- Right: Actions buttons --}}
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <a href="{{ route('storage_locations.export', ['search' => $search]) }}" class="action-btn btn-green">
                        <span class="material-icons">file_download</span> Export Excel
                    </a>
                    <a href="{{ route('storage_locations.template') }}" class="action-btn btn-outline">
                        <span class="material-icons">receipt_long</span> Template
                    </a>
                    <button type="button" class="action-btn btn-navy" onclick="openModal('importModal')">
                        <span class="material-icons">upload</span> Import Excel
                    </button>
                    <a href="{{ route('storage_locations.print_pdf', ['search' => $search]) }}" class="action-btn btn-red">
                        <span class="material-icons">print</span> Print PDF
                    </a>
                    <a href="{{ route('storage_locations.create') }}" class="action-btn btn-navy" style="background:var(--navy-dark);">
                        <span class="material-icons">add</span> + Tambah Lokasi
                    </a>
                </div>
            </div>

            {{-- Table wrap --}}
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Tipe Material</th>
                            <th>Scrap</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storageLocations as $location)
                        <tr>
                            <td>
                                <a href="javascript:void(0)" class="location-code-link" onclick="showDetail({{ json_encode($location) }})">
                                    {{ $location->kode }}
                                </a>
                            </td>
                            <td style="font-weight: 700; color: #333;">{{ $location->nama }}</td>
                            <td>{{ $location->deskripsi ?? '-' }}</td>
                            <td>
                                @if($location->tipe_material === 'RM')
                                    <span class="pill-badge pill-rm">RM</span>
                                @elseif($location->tipe_material === 'WIP')
                                    <span class="pill-badge pill-wip">WIP</span>
                                @elseif($location->tipe_material === 'FP')
                                    <span class="pill-badge pill-fp">FP</span>
                                @else
                                    <span class="pill-badge">{{ $location->tipe_material }}</span>
                                @endif
                            </td>
                            <td>
                                @if($location->is_scrap)
                                    <span class="pill-badge pill-scrap">Scrap</span>
                                @else
                                    <span style="color:#bbb">—</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div class="action-links" style="justify-content: center;">
                                    <a href="javascript:void(0)" class="link-detail" onclick="showDetail({{ json_encode($location) }})">Detail</a>
                                    <a href="javascript:void(0)" class="link-edit" onclick="showEdit({{ json_encode($location) }})">Edit</a>
                                    <form action="{{ route('storage_locations.destroy', $location->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lokasi {{ $location->nama }}?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-delete">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 40px; display: block; margin-bottom: 8px;">search_off</span>
                                Belum ada data storage location.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($storageLocations->total() > 0 && $storageLocations->lastPage() > 1)
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $storageLocations->firstItem() ?? 0 }}-{{ $storageLocations->lastItem() ?? 0 }}</strong> dari <strong>{{ $storageLocations->total() }}</strong> storage location
                </div>
                
                <div class="pagination">
                    {{-- Previous Page Link --}}
                    @if($storageLocations->onFirstPage())
                        <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
                    @else
                        <a href="{{ $storageLocations->previousPageUrl() }}" class="page-btn"><span class="material-icons">chevron_left</span></a>
                    @endif

                    {{-- Pagination Pages --}}
                    @php
                        $start = max(1, $storageLocations->currentPage() - 2);
                        $end = min($storageLocations->lastPage(), $storageLocations->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $storageLocations->url(1) }}" class="page-btn">1</a>
                        @if($start > 2)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $storageLocations->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $storageLocations->url($page) }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $storageLocations->lastPage())
                        @if($end < $storageLocations->lastPage() - 1)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                        <a href="{{ $storageLocations->url($storageLocations->lastPage()) }}" class="page-btn">{{ $storageLocations->lastPage() }}</a>
                    @endif

                    {{-- Next Page Link --}}
                    @if($storageLocations->hasMorePages())
                        <a href="{{ $storageLocations->nextPageUrl() }}" class="page-btn"><span class="material-icons">chevron_right</span></a>
                    @else
                        <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL: TAMBAH LOKASI --}}
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <h3><span class="material-icons">add_circle</span> Tambah Lokasi Baru</h3>
            <form action="{{ route('storage_locations.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_kode">Kode Lokasi <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="add_kode" placeholder="Contoh: 1101-S" required>
                    </div>
                    <div class="form-group">
                        <label for="add_tipe_material">Tipe Material</label>
                        <select name="tipe_material" id="add_tipe_material" class="filter-select">
                            <option value="">Semua Tipe / Gudang Umum</option>
                            <option value="RM">RM (Raw Material)</option>
                            <option value="WIP">WIP (Work in Progress)</option>
                            <option value="FP">FP (Finished Product)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_nama">Nama Lokasi <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="add_nama" placeholder="Contoh: Gudang IRM" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_deskripsi">Deskripsi</label>
                    <input type="text" name="deskripsi" id="add_deskripsi" placeholder="Keterangan penyimpanan lokasi">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_is_scrap">Klasifikasi Scrap <span style="color: red;">*</span></label>
                    <select name="is_scrap" id="add_is_scrap" class="filter-select" required>
                        <option value="0">Bukan Scrap</option>
                        <option value="1">Scrap</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EDIT LOKASI --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h3><span class="material-icons">edit</span> Edit Lokasi</h3>
            <form action="{{ route('storage_locations.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_kode">Kode Lokasi <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="edit_kode" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tipe_material">Tipe Material</label>
                        <select name="tipe_material" id="edit_tipe_material" class="filter-select">
                            <option value="">Semua Tipe / Gudang Umum</option>
                            <option value="RM">RM (Raw Material)</option>
                            <option value="WIP">WIP (Work in Progress)</option>
                            <option value="FP">FP (Finished Product)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_nama">Nama Lokasi <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_deskripsi">Deskripsi</label>
                    <input type="text" name="deskripsi" id="edit_deskripsi">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_is_scrap">Klasifikasi Scrap <span style="color: red;">*</span></label>
                    <select name="is_scrap" id="edit_is_scrap" class="filter-select" required>
                        <option value="0">Bukan Scrap</option>
                        <option value="1">Scrap</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: DETAIL LOKASI --}}
    <div class="modal-overlay" id="detailModal">
        <div class="modal" style="max-width: 650px; width: 90%;">
            <h3><span class="material-icons">info</span> Detail Storage Location</h3>
            <div style="margin-bottom: 20px;">
                <table class="detail-table">
                    <tr>
                        <td class="label-td">Kode Lokasi</td>
                        <td class="value-td" id="detail_kode" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Nama Lokasi</td>
                        <td class="value-td" id="detail_nama"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Deskripsi</td>
                        <td class="value-td" id="detail_deskripsi"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Tipe Material</td>
                        <td class="value-td" id="detail_tipe_material"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Klasifikasi Scrap</td>
                        <td class="value-td" id="detail_is_scrap"></td>
                    </tr>
                </table>
            </div>

            <div id="detail_stocks_container" style="margin-top: 20px; display: none;">
                <h4 style="font-size: 13px; font-weight: 800; color: var(--navy-dark); margin-bottom: 10px; border-bottom: 2px solid #eaeaea; padding-bottom: 6px;">Stok di Lokasi Ini</h4>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #f1f5f9; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 10; border-bottom: 2px solid #eaeaea;">
                            <tr>
                                <th style="padding: 8px; text-align: left; font-weight: 800; color: #555;">Kode Material</th>
                                <th style="padding: 8px; text-align: left; font-weight: 800; color: #555;">Nama Material</th>
                                <th style="padding: 8px; text-align: right; font-weight: 800; color: #555;">Qty</th>
                                <th style="padding: 8px; text-align: left; font-weight: 800; color: #555;">UoM</th>
                            </tr>
                        </thead>
                        <tbody id="detail_stocks_body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="margin-top: 24px;">
                <button type="button" class="btn-cancel" onclick="closeModal('detailModal')" style="width: 100%;">Tutup</button>
            </div>
        </div>
    </div>

    {{-- MODAL: IMPORT EXCEL --}}
    <div class="modal-overlay" id="importModal">
        <div class="modal" style="max-width: 440px;">
            <h3><span class="material-icons">upload_file</span> Import Excel</h3>
            <form action="{{ route('storage_locations.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="material-icons" style="font-size: 48px; color: var(--red-main);">upload_file</span>
                    <p style="font-size: 13px; color: #555; margin-top: 8px;">Upload template Excel yang telah diisi data master storage location.</p>
                    <a href="{{ route('storage_locations.template') }}" style="font-size: 12px; color: var(--red-main); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;">
                        <span class="material-icons" style="font-size: 16px;">download</span> Download Template Storage Location
                    </a>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="excel_file">Pilih File Excel (.xlsx, .xls) <span style="color: red;">*</span></label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required style="padding: 6px 10px;">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('importModal')">Batal</button>
                    <button type="submit" class="btn-save">Upload & Import</button>
                </div>
            </form>
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

    function showDetail(loc) {
        document.getElementById('detail_kode').innerText = loc.kode;
        document.getElementById('detail_nama').innerText = loc.nama;
        document.getElementById('detail_deskripsi').innerText = loc.deskripsi || '-';
        document.getElementById('detail_tipe_material').innerText = loc.tipe_material || 'Semua Tipe';
        document.getElementById('detail_is_scrap').innerText = loc.is_scrap ? 'Scrap' : 'Bukan Scrap';

        // Populate stocks table
        const tbody = document.getElementById('detail_stocks_body');
        tbody.innerHTML = '';
        if (loc.stocks && loc.stocks.length > 0) {
            loc.stocks.forEach(stock => {
                const materialCode = stock.material ? stock.material.code : '-';
                const materialName = stock.material ? stock.material.name : '-';
                const uom = stock.material ? (stock.material.unit_of_measure || 'PCS') : 'PCS';
                const qtyVal = stock.qty !== undefined ? stock.qty : (stock.quantity !== undefined ? stock.quantity : 0);
                
                const formattedQty = parseFloat(qtyVal).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                const qtyClass = qtyVal <= 0 ? 'text-red-600' : 'text-green-700';

                const tr = document.createElement('tr');
                tr.className = 'border-b hover:bg-gray-50';
                tr.innerHTML = `
                    <td style="padding: 8px; font-family: monospace; color: var(--navy-dark); font-weight: bold;">${materialCode}</td>
                    <td style="padding: 8px; color: #333;">${materialName}</td>
                    <td style="padding: 8px; text-align: right; font-weight: bold;" class="${qtyClass}">${formattedQty}</td>
                    <td style="padding: 8px; color: #666;">${uom}</td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('detail_stocks_container').style.display = 'block';
        } else {
            document.getElementById('detail_stocks_container').style.display = 'none';
        }

        openModal('detailModal');
    }

    function showEdit(loc) {
        document.getElementById('edit_id').value = loc.id;
        document.getElementById('edit_kode').value = loc.kode;
        document.getElementById('edit_nama').value = loc.nama;
        document.getElementById('edit_deskripsi').value = loc.deskripsi || '';
        document.getElementById('edit_tipe_material').value = loc.tipe_material || '';
        document.getElementById('edit_is_scrap').value = loc.is_scrap ? '1' : '0';
        openModal('editModal');
    }

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('open');
        }
    }
</script>
@endpush
