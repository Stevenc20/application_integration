@extends('layouts.app')

@section('title', 'Tambah Material')

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
        padding: 32px;
        max-width: 800px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 28px;
    }

    /* FORM STYLES */
    .form-row {
        display: flex;
        gap: 24px;
        margin-bottom: 20px;
    }
    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .form-label {
        font-size: 12px;
        color: #475569;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .form-input, .form-select, .form-textarea {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        outline: none;
        width: 100%;
        box-sizing: border-box;
    }
    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: var(--navy-dark);
    }

    .help-text {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 6px;
    }

    /* SUB CARD / SECTION */
    .sub-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px 24px;
        margin-top: 24px;
        margin-bottom: 30px;
    }
    .sub-section-title {
        font-size: 13px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 16px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
    }
    .checkbox-group input {
        width: 16px;
        height: 16px;
        accent-color: var(--navy-dark);
    }
    .checkbox-group label {
        font-size: 13px;
        color: #334155;
        font-weight: 600;
        cursor: pointer;
    }

    /* ACTION BUTTONS */
    .form-actions {
        display: flex;
        gap: 12px;
    }
    .btn-submit {
        background: #2563eb; /* Blue primary matching screenshot */
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 28px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-submit:hover { opacity: 0.9; }
    
    .btn-cancel {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        border-radius: 6px;
        padding: 10px 28px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
    }
    .btn-cancel:hover { background: #cbd5e1; }

    @media (max-width: 600px) {
        .form-row {
            flex-direction: column;
            gap: 20px;
        }
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">inventory_2</span> Tambah Material</h2>
        </div>
    </div>

    <div class="content-body">
        
        <div class="card">
            <div class="card-title">Tambah Material Baru</div>
            
            @if(session('success'))
            <div style="background: #dcfce7; color: #166534; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                <span class="material-icons" style="font-size: 18px;">check_circle</span>
                {{ session('success') }}
            </div>
            @endif

            <form action="{{ route('materials.store') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="Aktif">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode Material *</label>
                        <input type="text" name="kode" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe *</label>
                        <select name="tipe" class="form-select" required>
                            <option value="RM">RM - Bahan Baku</option>
                            <option value="WIP">WIP - Work In Progress</option>
                            <option value="FP">FP - Finished Product</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Nama Material *</label>
                        <input type="text" name="nama" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-textarea"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Unit of Measure *</label>
                        <input type="text" name="uom" class="form-input" value="PCS" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Standard *</label>
                        <input type="number" name="harga" class="form-input" value="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Qty per Case / Karton</label>
                        <input type="number" name="qty_case" class="form-input" value="0" min="0">
                        <div class="help-text">Isi 0 jika tidak digunakan</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Minimal Stok</label>
                        <input type="number" name="min_stok" class="form-input" value="0" min="0">
                        <div class="help-text">Alert jika stok total di bawah nilai ini</div>
                    </div>
                </div>
                
                <!-- STOK AWAL HIDDEN UNTUK KOMPATIBILITAS DB -->
                <input type="hidden" name="stok" value="0">

                <div class="sub-section">
                    <div class="sub-section-title">Metode Order</div>
                    
                    <div class="form-row" style="margin-bottom: 0;">
                        <div class="form-group">
                            <label class="form-label">Sistem Order *</label>
                            <select name="sistem_order" class="form-select">
                                <option value="MRP">MRP (Perencanaan Bulanan)</option>
                                <option value="SKM">SKM (Sistem Kanban Manual)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vendor Planning (MRP/SKM)</label>
                            <select name="vendor" class="form-select">
                                <option value="">-- Pilih Vendor --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->kode }}">{{ $vendor->kode }} - {{ $vendor->nama }}</option>
                                @endforeach
                            </select>
                            <div class="help-text">Wajib diisi jika metode SKM</div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="diproses_vendor" name="diproses_vendor">
                        <label for="diproses_vendor">Diproses di Vendor (WIP / FP)</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan</button>
                    <a href="{{ route('materials.index') }}" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>

    </div>

@endsection
