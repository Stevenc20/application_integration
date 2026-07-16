@extends('layouts.app')

@section('title', 'Detail Material - Laravel')

@push('styles')
<style>
    /* ===== HERO ===== */
    .hero {
        background: #f8fafc;
        padding: 16px 28px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .hero h2 {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    /* ===== CONTENT BODY ===== */
    .content-body {
        padding: 24px 28px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 4fr 6fr;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ===== CARDS ===== */
    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 24px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0;
        margin-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
    }

    /* ===== FORMS & LABELS ===== */
    .detail-item {
        margin-bottom: 16px;
    }
    .detail-item:last-child {
        margin-bottom: 0;
    }
    .detail-label {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .detail-value {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
    }

    /* ===== TABLES ===== */
    .table-custom {
        width: 100%;
        border-collapse: collapse;
    }
    .table-custom th {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
        padding: 10px 12px;
        text-align: left;
    }
    .table-custom td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
    }
    .table-custom tr:last-child td {
        border-bottom: none;
    }

    /* ===== ALERTS ===== */
    .alert-warning-custom {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    .alert-warning-custom .material-icons {
        color: #ef4444;
        font-size: 20px;
    }
    .alert-warning-custom span {
        font-size: 13px;
        font-weight: 700;
        color: #991b1b;
    }

    /* ===== MODAL ===== */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 9000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.open {
        display: flex;
    }
    .modal {
        background: white;
        border-radius: 12px;
        padding: 24px;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 10px 30px rgba(0,0,0,.2);
    }
    .modal h3 {
        font-size: 16px;
        font-weight: 800;
        color: var(--navy-dark);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 0;
    }
    .modal h3 .material-icons {
        font-size: 20px;
        color: var(--red-main);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .form-group label {
        font-size: 11px;
        font-weight: 700;
        color: #555;
        text-transform: uppercase;
    }
    .form-group input, .form-group select {
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 12px;
        font-family: inherit;
        outline: none;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: var(--red-main);
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-cancel {
        background: #eee;
        color: #555;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-save {
        background: var(--red-main);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-save:hover {
        background: var(--red-dark);
    }

    .btn-detail-edit:hover {
        opacity: 0.9;
    }
</style>
@endpush

@section('content')
    <div class="hero">
        <h2>Detail Material</h2>
    </div>

    <div class="content-body">
        @if(session('success'))
            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                <span class="material-icons" style="font-size: 20px;">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="detail-grid">
            {{-- Left Column: Details --}}
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Kode Material</span>
                        <h1 style="font-size: 24px; font-weight: 800; color: #1e3a8a; margin: 4px 0 0 0;">{{ $material->kode }}</h1>
                    </div>
                    <div>
                        @if($material->status === 'Aktif')
                            <span style="background-color: #dcfce7; color: #16a34a; font-size: 12px; font-weight: 700; padding: 6px 16px; border-radius: 9999px;">Aktif</span>
                        @else
                            <span style="background-color: #f1f5f9; color: #64748b; font-size: 12px; font-weight: 700; padding: 6px 16px; border-radius: 9999px;">Tidak Aktif</span>
                        @endif
                    </div>
                </div>

                @if($material->stok < $material->min_stok)
                    <div class="alert-warning-custom">
                        <span class="material-icons">warning</span>
                        <span>Stok Minim! Total stok {{ number_format($material->stok, 3, '.', '.') }} di bawah minimum {{ number_format($material->min_stok, 3, '.', '.') }} {{ $material->uom }}</span>
                    </div>
                @endif

                <div style="display: flex; flex-direction: column; gap: 16px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value">{{ $material->nama }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tipe</div>
                        <div class="detail-value">{{ $material->tipe }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">UoM</div>
                        <div class="detail-value">{{ $material->uom }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Harga Std</div>
                        <div class="detail-value">{{ number_format($material->standard_price ?? 0, 2, '.', ',') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Qty per Case</div>
                        <div class="detail-value">{{ number_format($material->qty_case, 3, '.', '.') }} {{ $material->uom }} / case</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Minimal Stok</div>
                        <div class="detail-value">{{ number_format($material->min_stok, 3, '.', '.') }} {{ $material->uom }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Deskripsi</div>
                        <div class="detail-value">{{ $material->deskripsi ?? '-' }}</div>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 28px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    <button type="button" class="btn-detail-edit" onclick="openModal('editModal')" style="background-color: #f59e0b; color: white; border: none; border-radius: 6px; padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer; transition: background 0.2s;">Edit</button>
                    
                    <form action="{{ route('materials.destroy', $material->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus material {{ $material->nama }}?');" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background-color: #ef4444; color: white; border: none; border-radius: 6px; padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer; transition: background 0.2s;">Hapus</button>
                    </form>
                    
                    <a href="{{ route('materials.index') }}" style="background-color: #e2e8f0; color: #475569; border: none; border-radius: 6px; padding: 10px 20px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;">Kembali</a>
                </div>
            </div>

            {{-- Right Column: Tables --}}
            <div>
                {{-- Stok Per Gudang --}}
                <div class="card" style="padding: 20px;">
                    <h3 class="card-title" style="margin-bottom: 12px;">Stok Per Gudang</h3>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="color: #64748b;">Storage Location</th>
                                <th style="text-align: right; color: #64748b;">Qty Tersedia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $loc)
                                @php
                                    $qty = (float) $material->stocks->where('storage_location_id', $loc->id)->sum('qty');
                                @endphp
                                <tr>
                                    <td style="font-weight: 500;">{{ $loc->nama }}</td>
                                    <td style="text-align: right; font-weight: 700; color: {{ $qty > 0 ? '#16a34a' : '#ef4444' }};">
                                        {{ number_format($qty, 3, '.', '.') }} {{ $material->uom }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="text-align: center; color: #94a3b8; padding: 20px;">Tidak ada storage location terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Riwayat Pergerakan Stok --}}
                <div class="card" style="padding: 20px;">
                    <h3 class="card-title" style="margin-bottom: 12px;">Riwayat Pergerakan Stok (10 Terakhir)</h3>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="color: #64748b; width: 100px;">Tanggal</th>
                                <th style="color: #64748b; width: 60px;">Tipe</th>
                                <th style="color: #64748b;">Referensi</th>
                                <th style="text-align: right; color: #64748b; width: 100px;">Qty</th>
                                <th style="text-align: right; color: #64748b; width: 100px;">Stok Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestMovements as $move)
                                <tr>
                                    <td style="color: #64748b; font-weight: 500;">{{ $move['tanggal'] }}</td>
                                    <td>
                                        @if($move['tipe'] === 'GR')
                                            <span style="background-color: #dcfce7; color: #16a34a; font-weight: 700; border-radius: 4px; padding: 2px 8px; font-size: 11px;">GR</span>
                                        @else
                                            <span style="background-color: #fee2e2; color: #ef4444; font-weight: 700; border-radius: 4px; padding: 2px 8px; font-size: 11px;">GI</span>
                                        @endif
                                    </td>
                                    <td style="color: #1e3a8a; font-weight: 600;">{{ $move['referensi'] }}</td>
                                    <td style="text-align: right; font-weight: 700; color: #1e293b;">
                                        {{ number_format($move['qty'], 3, '.', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #1e293b;">
                                        {{ number_format($move['stok_akhir'], 3, '.', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada riwayat pergerakan stok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: EDIT MATERIAL --}}
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <h3><span class="material-icons">edit</span> Edit Material</h3>
            <form action="{{ route('materials.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_id" value="{{ $material->id }}">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_kode">Kode Material <span style="color: red;">*</span></label>
                        <input type="text" name="kode" id="edit_kode" value="{{ $material->kode }}" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status <span style="color: red;">*</span></label>
                        <select name="status" id="edit_status" required>
                            <option value="Aktif" {{ $material->status === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="Tidak Aktif" {{ $material->status === 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_nama">Nama Material <span style="color: red;">*</span></label>
                    <input type="text" name="nama" id="edit_nama" value="{{ $material->nama }}" required>
                </div>

                <div class="form-row" style="margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="edit_tipe">Tipe Material <span style="color: red;">*</span></label>
                        <select name="tipe" id="edit_tipe" required>
                            <option value="WIP" {{ $material->tipe === 'WIP' ? 'selected' : '' }}>WIP (Work in Progress)</option>
                            <option value="FP" {{ $material->tipe === 'FP' ? 'selected' : '' }}>FP (Finished Product)</option>
                            <option value="RM" {{ $material->tipe === 'RM' ? 'selected' : '' }}>RM (Raw Material)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_uom">UoM (Unit of Measure) <span style="color: red;">*</span></label>
                        <input type="text" name="uom" id="edit_uom" value="{{ $material->uom }}" required>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="edit_qty_case">Qty/Case <span style="color: red;">*</span></label>
                        <input type="number" name="qty_case" id="edit_qty_case" value="{{ $material->qty_case }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_min_stok">Min Stok <span style="color: red;">*</span></label>
                        <input type="number" name="min_stok" id="edit_min_stok" value="{{ $material->min_stok }}" min="0" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="edit_stok">Stok Saat Ini <span style="color: red;">*</span></label>
                    <input type="number" name="stok" id="edit_stok" value="{{ $material->stok }}" min="0" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
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

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('open');
        }
    }
</script>
@endpush
