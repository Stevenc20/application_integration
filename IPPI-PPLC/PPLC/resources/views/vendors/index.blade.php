@extends('layouts.app')

@section('title', 'Daftar Vendor')

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
    .search-box input { border: none; background: transparent; outline: none; font-size: 12px; font-family: inherit; width: 180px; }

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

    .vendor-code {
        color: var(--navy-dark);
        font-weight: 800;
        font-size: 12px;
        font-family: monospace;
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
    .pill-process {
        background: #f3e8ff;
        color: #7e22ce;
    }
    .pill-coil {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .pill-default {
        background: #f1f5f9;
        color: #475569;
    }
    .pill-active {
        background: #dcfce7;
        color: #15803d;
    }
    .pill-inactive {
        background: #fef2f2;
        color: #b91c1c;
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
            <h2><span class="material-icons">business</span> Vendor</h2>
            <div class="hero-meta">Kelola data master vendor proses makloon dan coil center bahan baku</div>
        </div>
    </div>

    <div class="content-body">
        <div class="card">
            {{-- Toolbar Header --}}
            <div class="card-header">
                {{-- Left: Search form --}}
                <form action="{{ route('vendors.index') }}" method="GET" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; flex:1;">
                    <div class="search-box" style="width: 240px;">
                        <span class="material-icons">search</span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode / nama...">
                    </div>
                    <button type="submit" class="btn-search-go"><span class="material-icons">search</span>Cari</button>
                    @if($search)
                    <a href="{{ route('vendors.index') }}" class="btn-search-reset">Kembali</a>
                    @endif
                </form>

                {{-- Right: Actions buttons --}}
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <a href="{{ route('vendors.export', ['search' => $search]) }}" class="action-btn btn-green">
                        <span class="material-icons">file_download</span> Export
                    </a>
                    <a href="{{ route('vendors.template') }}" class="action-btn btn-outline">
                        <span class="material-icons">receipt_long</span> Template
                    </a>
                    <button type="button" class="action-btn btn-navy" onclick="openModal('importModal')">
                        <span class="material-icons">upload</span> Import
                    </button>
                    <a href="{{ route('vendors.print_pdf', ['search' => $search]) }}" class="action-btn btn-red">
                        <span class="material-icons">print</span> Print PDF
                    </a>
                    <button type="button" class="action-btn btn-navy" style="background:var(--navy-dark);" onclick="openModal('addModal')">
                        <span class="material-icons">add</span> Add Vendor
                    </button>
                </div>
            </div>

            {{-- Table wrap --}}
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No.</th>
                            <th style="text-align: center;">Kode</th>
                            <th style="text-align: center;">Nama</th>
                            <th style="text-align: center;">Tipe</th>
                            <th style="text-align: center;">Alamat</th>
                            <th style="text-align: center;">Kontak</th>
                            <th style="text-align: center;">Email</th>
                            <th style="text-align: center;">Telepon</th>
                            <th style="text-align: center;">Status</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $index => $vendor)
                        <tr>
                            <td style="text-align: center;">{{ $vendors->firstItem() + $index }}</td>
                            <td style="text-align: center;"><span class="vendor-code">{{ $vendor->kode }}</span></td>
                            <td style="font-weight: 700; color: #333; text-align: center;">{{ $vendor->nama }}</td>
                            <td style="text-align: center;">
                                @if(str_contains(strtolower($vendor->tipe), 'coil'))
                                    <span class="pill-badge pill-coil">{{ $vendor->tipe }}</span>
                                @elseif(str_contains(strtolower($vendor->tipe), 'process') || str_contains(strtolower($vendor->tipe), 'makloon'))
                                    <span class="pill-badge pill-process">{{ $vendor->tipe }}</span>
                                @else
                                    <span class="pill-badge pill-default">{{ $vendor->tipe }}</span>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $vendor->alamat ?? '-' }}</td>
                            <td style="text-align: center;">{{ $vendor->kontak ?? '-' }}</td>
                            <td style="text-align: center;">{{ $vendor->email ?? '-' }}</td>
                            <td style="text-align: center;">{{ $vendor->telepon ?? '-' }}</td>
                            <td style="text-align: center;">
                                <span class="pill-badge {{ $vendor->status === 'Aktif' ? 'pill-active' : 'pill-inactive' }}">
                                    {{ $vendor->status }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-links" style="justify-content: center;">
                                    <a href="javascript:void(0)" class="link-detail" onclick="showDetail({{ json_encode($vendor) }})">Detail</a>
                                    <a href="javascript:void(0)" class="link-edit" onclick="showEdit({{ json_encode($vendor) }})">Edit</a>
                                    <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus vendor {{ $vendor->nama }}?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-delete">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <span class="material-icons" style="font-size: 40px; display: block; margin-bottom: 8px;">search_off</span>
                                Tidak ada data vendor ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($vendors->total() > 0 && $vendors->lastPage() > 1)
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $vendors->firstItem() ?? 0 }}-{{ $vendors->lastItem() ?? 0 }}</strong> dari <strong>{{ $vendors->total() }}</strong> vendor
                </div>
                
                <div class="pagination">
                    {{-- Previous Page Link --}}
                    @if($vendors->onFirstPage())
                        <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
                    @else
                        <a href="{{ $vendors->previousPageUrl() }}" class="page-btn"><span class="material-icons">chevron_left</span></a>
                    @endif

                    {{-- Pagination Pages --}}
                    @php
                        $start = max(1, $vendors->currentPage() - 2);
                        $end = min($vendors->lastPage(), $vendors->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $vendors->url(1) }}" class="page-btn">1</a>
                        @if($start > 2)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                    @endif

                    @for($page = $start; $page <= $end; $page++)
                        @if($page == $vendors->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $vendors->url($page) }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($end < $vendors->lastPage())
                        @if($end < $vendors->lastPage() - 1)
                            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                        @endif
                        <a href="{{ $vendors->url($vendors->lastPage()) }}" class="page-btn">{{ $vendors->lastPage() }}</a>
                    @endif

                    {{-- Next Page Link --}}
                    @if($vendors->hasMorePages())
                        <a href="{{ $vendors->nextPageUrl() }}" class="page-btn"><span class="material-icons">chevron_right</span></a>
                    @else
                        <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- MODAL: TAMBAH VENDOR --}}
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <h3><span class="material-icons">add_circle</span> Tambah Vendor Baru</h3>
            <form action="{{ route('vendors.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_kode">Kode Vendor <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="add_kode" placeholder="Contoh: 1202208" required>
                    </div>
                    <div class="form-group">
                        <label for="add_status">Status <span style="color: red;">*</span></label>
                        <select name="status" id="add_status" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_nama">Nama Vendor <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="add_nama" placeholder="Contoh: PT. TRI CENTRUM FORTUNA" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_tipe">Tipe Vendor <span style="color: red;">*</span></label>
                    <select name="tipe" id="add_tipe" required>
                        <option value="Process / Makloon">Process / Makloon</option>
                        <option value="Coil Center (Supplier Bahan Baku)">Coil Center (Supplier Bahan Baku)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_alamat">Alamat</label>
                    <input type="text" name="alamat" id="add_alamat" placeholder="-">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="add_kontak">Contact Person</label>
                    <input type="text" name="kontak" id="add_kontak" placeholder="-">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="add_email">Email</label>
                        <input type="email" name="email" id="add_email" placeholder="-">
                    </div>
                    <div class="form-group">
                        <label for="add_telepon">Telepon</label>
                        <input type="text" name="telepon" id="add_telepon" placeholder="-">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: EDIT VENDOR --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h3><span class="material-icons">edit</span> Edit Vendor</h3>
            <form action="{{ route('vendors.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_kode">Kode Vendor <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="edit_kode" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status <span style="color: red;">*</span></label>
                        <select name="status" id="edit_status" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_nama">Nama Vendor <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_tipe">Tipe Vendor <span style="color: red;">*</span></label>
                    <select name="tipe" id="edit_tipe" required>
                        <option value="Process / Makloon">Process / Makloon</option>
                        <option value="Coil Center (Supplier Bahan Baku)">Coil Center (Supplier Bahan Baku)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_alamat">Alamat</label>
                    <input type="text" name="alamat" id="edit_alamat" placeholder="-">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_kontak">Contact Person</label>
                    <input type="text" name="kontak" id="edit_kontak">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" name="email" id="edit_email">
                    </div>
                    <div class="form-group">
                        <label for="edit_telepon">Telepon</label>
                        <input type="text" name="telepon" id="edit_telepon">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: DETAIL VENDOR --}}
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <h3><span class="material-icons">info</span> Detail Vendor</h3>
            <div style="margin-bottom: 20px;">
                <table class="detail-table">
                    <tr>
                        <td class="label-td">Kode Vendor</td>
                        <td class="value-td" id="detail_kode" style="font-family: monospace; color: var(--red-main);"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Nama Vendor</td>
                        <td class="value-td" id="detail_nama"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Tipe Vendor</td>
                        <td class="value-td" id="detail_tipe"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Alamat</td>
                        <td class="value-td" id="detail_alamat"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Contact Person</td>
                        <td class="value-td" id="detail_kontak"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Email</td>
                        <td class="value-td" id="detail_email"></td>
                    </tr>
                    <tr>
                        <td class="label-td">No. Telepon</td>
                        <td class="value-td" id="detail_telepon"></td>
                    </tr>
                    <tr>
                        <td class="label-td">Status</td>
                        <td class="value-td" id="detail_status"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('detailModal')" style="width: 100%;">Tutup</button>
            </div>
        </div>
    </div>

    {{-- MODAL: IMPORT EXCEL --}}
    <div class="modal-overlay" id="importModal">
        <div class="modal" style="max-width: 440px;">
            <h3><span class="material-icons">upload_file</span> Import Excel</h3>
            <form action="{{ route('vendors.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="material-icons" style="font-size: 48px; color: var(--red-main);">upload_file</span>
                    <p style="font-size: 13px; color: #555; margin-top: 8px;">Upload template Excel yang telah diisi data vendor.</p>
                    <a href="{{ route('vendors.template') }}" style="font-size: 12px; color: var(--red-main); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;">
                        <span class="material-icons" style="font-size: 16px;">download</span> Download Template Vendor
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

    function showDetail(vendor) {
        document.getElementById('detail_kode').innerText = vendor.kode;
        document.getElementById('detail_nama').innerText = vendor.nama;
        document.getElementById('detail_tipe').innerText = vendor.tipe;
        document.getElementById('detail_alamat').innerText = vendor.alamat || '-';
        document.getElementById('detail_kontak').innerText = vendor.kontak || '-';
        document.getElementById('detail_email').innerText = vendor.email || '-';
        document.getElementById('detail_telepon').innerText = vendor.telepon || '-';
        document.getElementById('detail_status').innerText = vendor.status;
        openModal('detailModal');
    }

    function showEdit(vendor) {
        document.getElementById('edit_id').value = vendor.id;
        document.getElementById('edit_kode').value = vendor.kode;
        document.getElementById('edit_nama').value = vendor.nama;
        document.getElementById('edit_tipe').value = vendor.tipe;
        document.getElementById('edit_alamat').value = vendor.alamat || '';
        document.getElementById('edit_kontak').value = vendor.kontak || '';
        document.getElementById('edit_email').value = vendor.email || '';
        document.getElementById('edit_telepon').value = vendor.telepon || '';
        document.getElementById('edit_status').value = vendor.status;
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
