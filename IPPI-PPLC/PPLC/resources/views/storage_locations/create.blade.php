@extends('layouts.app')

@section('title', 'Tambah Storage Location')

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
        max-width: 700px; /* Slightly smaller width based on screenshot proportions */
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

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
        margin-bottom: 30px;
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
    .checkbox-group label span {
        color: #ef4444; /* Highlight 'tidak' text */
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
            <h2><span class="material-icons">location_on</span> Tambah Storage Location</h2>
        </div>
    </div>

    <div class="content-body">
        
        <div class="card">
            <div class="card-title">Tambah Storage Location</div>
            
            <form action="{{ route('storage_locations.store') }}" method="POST">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode *</label>
                        <input type="text" name="kode" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama *</label>
                        <input type="text" name="nama" class="form-input" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-textarea"></textarea>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Tipe Material</label>
                        <select name="tipe_material" class="form-select filter-select">
                            <option value="">Semua Tipe (RM / WIP / FP)</option>
                            <option value="RM">RM (Bahan Baku)</option>
                            <option value="WIP">WIP (Barang Setengah Jadi)</option>
                            <option value="FP">FP (Barang Jadi)</option>
                        </select>
                        <div class="help-text">Material baru hanya akan otomatis muncul di lokasi yang tipenya cocok (atau lokasi tanpa tipe).</div>
                    </div>
                </div>

                <div class="checkbox-group">
                    <!-- Hidden input so unchecked sends '0' -->
                    <input type="hidden" name="is_scrap" value="0">
                    <input type="checkbox" id="is_scrap" name="is_scrap" value="1">
                    <label for="is_scrap">Lokasi Scrap (stok di sini <span>tidak</span> dihitung dalam MRP)</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan</button>
                    <a href="{{ route('storage_locations.index') }}" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>

    </div>

@endsection
