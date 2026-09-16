@extends('layouts.app')

@section('title', 'Bill of Materials')

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
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
    }

    .content-body {
        padding: 24px 28px;
        background: #f8fafc;
        min-height: calc(100vh - 70px);
    }

    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
    }
    
    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--red-main);
    }
    
    .btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        border: none;
    }

    .btn-green { background: #10b981; color: white; }
    .btn-green:hover { background: #059669; }
    
    .btn-red { background: #dc2626; color: white; }
    .btn-red:hover { background: #b91c1c; }
    
    .btn-outline-blue { background: transparent; color: #3b82f6; border: 1px solid #bfdbfe; }
    .btn-outline-blue:hover { background: #eff6ff; }
    
    .btn-blue { background: #2563eb; color: white; }
    .btn-blue:hover { background: #1d4ed8; }

    /* FILTERS */
    .filter-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .filter-input {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 13px;
        background: #fff;
        outline: none;
    }
    .filter-input:focus {
        border-color: #94a3b8;
    }
    .btn-search {
        background: #334155;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-search:hover { background: #1e293b; }
    
    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-reset:hover { background: #e2e8f0; }

    /* TABLE */
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
        vertical-align: top;
    }
    
    .col-bom {
        color: #3b82f6;
        font-family: monospace;
        font-weight: 700;
        font-size: 13px;
    }
    .col-material-kode {
        color: #64748b;
        font-size: 11px;
        font-family: monospace;
        display: block;
        margin-bottom: 4px;
    }
    .col-material-nama {
        color: #334155;
        font-weight: 800;
        font-size: 13px;
    }
    .col-center {
        text-align: center;
    }
    .badge-status {
        background: #dcfce7;
        color: #16a34a;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
    }

    .action-links a {
        text-decoration: none;
        font-weight: 600;
        margin-right: 8px;
        font-size: 11px;
    }
    .action-detail { color: #3b82f6; }
    .action-edit { color: #eab308; }
    .action-delete { color: #ef4444; }

    /* TABLE FOOTER & PAGINATION */
    .table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        padding-top: 16px;
        font-size: 13px;
        color: #64748b;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .pagination {
        display: flex;
        gap: 6px;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pagination .page-item .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s;
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: var(--red-main);
    }
    .pagination .page-item.active .page-link {
        background: var(--red-main);
        color: white;
        border-color: var(--red-main);
    }
    .pagination .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f8fafc;
        cursor: not-allowed;
    }

    /* ACTION LINKS (Permanent colors + bold) */
    .action-link {
        text-decoration: none;
        font-weight: 800; /* Bold */
        transition: all 0.3s ease;
    }
    .link-detail {
        color: #2563eb;
    }
    .link-detail:hover { text-shadow: 0 0 8px rgba(37, 99, 235, 0.4); }
    
    .link-edit {
        color: #eab308;
    }
    .link-edit:hover { text-shadow: 0 0 8px rgba(234, 179, 8, 0.4); }
    
    .link-hapus {
        color: #ef4444;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
    }
    .link-hapus:hover { text-shadow: 0 0 8px rgba(239, 68, 68, 0.4); }

    @media (max-width: 768px) {
        .hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
        }
        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">view_list</span> Bill of Materials</h2>
            <div class="hero-meta">Daftar komposisi bahan baku (BOM) untuk proses produksi</div>
        </div>
    </div>

    <div class="content-body">
        
        {{-- Import Errors --}}
        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="card" style="margin-bottom: 20px; border-color: #fbd5d5; background-color: #fef2f2; padding: 16px;">
            <p style="font-weight: bold; color: #dc2626; font-size: 14px; margin-top: 0; margin-bottom: 8px;">
                <span class="material-icons" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">error_outline</span>
                Detail Masalah Saat Import Excel:
            </p>
            <ul style="color: #b91c1c; font-size: 12px; margin: 0; padding-left: 20px; line-height: 1.5;">
                @foreach(session('import_errors') as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card">
            <div class="card-top">
                <div class="card-title">Daftar Bill of Materials (BOM)</div>
                <div class="btn-group">
                    <a href="{{ route('boms.export') }}" class="btn btn-green"><span class="material-icons" style="font-size:14px;">file_download</span> Export Excel</a>
                    <a href="{{ route('boms.print_pdf', ['search' => request('search')]) }}" class="btn btn-red" target="_blank"><span class="material-icons" style="font-size:14px;">picture_as_pdf</span> Print PDF</a>
                    <a href="{{ route('boms.template') }}" class="btn btn-outline-blue"><span class="material-icons" style="font-size:14px;">file_download</span> Download Template</a>
                    <button type="button" class="btn btn-blue" onclick="openModal('importModal')"><span class="material-icons" style="font-size:14px;">publish</span> Import Excel</button>
                    <a href="{{ route('boms.create') }}" class="btn btn-blue"><span style="font-size:14px;font-weight:bold;">+</span> Buat Manual</a>
                </div>
            </div>

            <form action="{{ route('boms.index') }}" method="GET" class="filter-row">
                <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="No. BOM / nama material...">
                <button type="submit" class="btn-search"><span class="material-icons">search</span>Cari</button>
                <a href="{{ route('boms.index') }}" class="btn-reset">Kembali</a>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;" class="col-center">No.</th>
                            <th style="width: 15%;">No. BOM</th>
                            <th style="width: 25%;">Material (Hasil)</th>
                            <th class="col-center" style="width: 8%;">BQ</th>
                            <th style="width: 30%;">Material Asal (Komponen)</th>
                            <th class="col-center" style="width: 10%;">Status</th>
                            <th class="col-center" style="width: 12%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boms as $bom)
                        <tr>
                            <td class="col-center">{{ $loop->iteration + ($boms->firstItem() - 1) }}</td>
                            <td><span class="col-bom" style="color: var(--navy-dark); font-weight: 700;">{{ $bom->bom_number }}</span></td>
                            <td>
                                <div style="color: #64748b; font-size: 11px; margin-bottom: 2px;">{{ $bom->material->kode ?? '' }}</div>
                                <div style="color: #0f172a; font-weight: 700; font-size: 12px;">{{ $bom->material->nama ?? '-' }}</div>
                            </td>
                            <td class="col-center" style="font-weight:700; color: #334155;">{{ fmt_qty($bom->base_quantity) }}</td>
                            <td>
                                @foreach($bom->items as $bi)
                                <div style="{{ !$loop->first ? 'margin-top: 6px; padding-top: 6px; border-top: 1px solid #f1f5f9;' : '' }}">
                                    <span style="font-family: monospace; font-size: 11px; font-weight: bold; color: #2563eb;">{{ $bi->material?->kode }}</span>
                                    <span style="margin-left: 4px; font-size: 12px; color: #334155;">{{ $bi->material?->nama }}</span>
                                    <span style="color: #64748b; font-size: 11px; margin-left: 4px;">({{ fmt_qty($bi->quantity) }} {{ $bi->unit }})</span>
                                </div>
                                @endforeach
                            </td>
                            <td class="col-center">
                                <span class="badge-status" style="{{ $bom->status === 'active' ? 'background:#dcfce7; color:#16a34a;' : 'background:#f1f5f9; color:#64748b;' }}">
                                    {{ $bom->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="col-center" style="font-size: 11px;">
                                <div style="display: flex; flex-direction: row; gap: 10px; justify-content: center; align-items: center;">
                                    <a href="{{ route('boms.show', $bom->id) }}" class="action-link link-detail">Detail</a>
                                    <a href="{{ route('boms.edit', $bom->id) }}" class="action-link link-edit">Edit</a>
                                    <form method="POST" action="{{ route('boms.destroy', $bom->id) }}" onsubmit="return confirm('Hapus BOM ini?')" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-link link-hapus">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Belum ada data Bill of Materials.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="showing-data">
                    Menampilkan {{ $boms->firstItem() ?? 0 }} &ndash; {{ $boms->lastItem() ?? 0 }} dari {{ $boms->total() }} data
                </div>
                
                @if($boms->hasPages())
                <div class="pagination-wrap">
                    {{ $boms->links('pagination::bootstrap-4') }}
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- MODAL: IMPORT EXCEL --}}
    <div class="modal-overlay" id="importModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9000; align-items:center; justify-content:center;">
        <div class="modal" style="background:white; border-radius:12px; padding:24px; width:100%; max-width:440px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h3 style="font-size:16px; font-weight:800; color:var(--navy-dark); margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="color:var(--red-main);">upload_file</span> Import Excel BOM
            </h3>
            <form action="{{ route('boms.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="material-icons" style="font-size: 48px; color: var(--red-main);">upload_file</span>
                    <p style="font-size: 13px; color: #555; margin-top: 8px;">Upload template Excel yang telah diisi data BOM.</p>
                    <a href="{{ route('boms.template') }}" style="font-size: 12px; color: var(--red-main); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;">
                        <span class="material-icons" style="font-size: 16px;">download</span> Download Template
                    </a>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px; display:flex; flex-direction:column; gap:5px;">
                    <label for="excel_file" style="font-size:11px; font-weight:700; color:#555; text-transform:uppercase;">Pilih File Excel (.xlsx, .xls) <span style="color: red;">*</span></label>
                    <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required style="padding: 6px 10px; border:1px solid #ddd; border-radius:6px; font-size:12px;">
                </div>
                
                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeModal('importModal')" style="background:#eee; color:#555; border:none; border-radius:6px; padding:8px 16px; font-size:12px; font-weight:700; cursor:pointer;">Batal</button>
                    <button type="submit" style="background:var(--red-main); color:white; border:none; border-radius:6px; padding:8px 16px; font-size:12px; font-weight:700; cursor:pointer;">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    }
</script>
@endpush
