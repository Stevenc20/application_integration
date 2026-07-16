@extends('layouts.app')

@section('title', 'Buat Goods Issue')

@push('styles')
<style>
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
        margin-bottom: 24px;
        overflow: visible;
        padding: 24px;
    }

    .card-title {
        font-size: 15px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* FORM STYLES */
    .form-row {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .form-group {
        flex: 1;
        min-width: 250px;
        display: flex;
        flex-direction: column;
    }
    .form-group.full-width {
        flex: 0 0 100%;
        min-width: 100%;
    }
    .form-label {
        font-size: 12px;
        color: #475569;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .form-input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        outline: none;
        width: 100%;
        box-sizing: border-box;
        height: 40px;
        background: #fff;
    }
    .form-input:focus { border-color: var(--navy-dark); }
    textarea.form-input {
        height: auto;
        min-height: 80px;
        resize: vertical;
    }

    /* TABLE STYLES */
    .items-section-header {
        font-size: 13px;
        font-weight: 800;
        color: #1e293b;
        margin-top: 24px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .gi-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 24px;
    }
    .gi-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-align: left;
        padding: 12px 16px;
        border-bottom: 2px solid #e2e8f0;
    }
    .gi-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }
    .gi-table input, .gi-table select {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        width: 100%;
        box-sizing: border-box;
        outline: none;
        height: 38px;
    }
    .gi-table input:focus, .gi-table select:focus { border-color: var(--navy-dark); }
    
    .btn-remove {
        color: #ef4444;
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 4px;
    }
    .btn-remove:hover { background: #fee2e2; }

    /* BUTTONS */
    .btn-add-row {
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-add-row:hover { background: #059669; }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    .btn-submit {
        background: #f97316;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-submit:hover { opacity: 0.9; }
    
    .btn-cancel {
        background: #f1f5f9;
        color: #64748b;
        text-decoration: none;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        border: 1px solid #e2e8f0;
    }
    .btn-cancel:hover { background: #e2e8f0; }

    /* Suggestions List */
    .suggestions-list {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        max-height: 160px;
        overflow-y: auto;
        z-index: 999;
        list-style: none;
        margin: 0;
        padding: 4px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }
    .suggestions-list li {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .suggestions-list li:hover {
        background: #EFF6FF;
    }
</style>
@endpush

@section('content')

    <div style="background: white; border-bottom: 1px solid #e2e8f0; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Buat Goods Issue</h2>
    </div>

    <div class="content-body">
        
        @if ($errors->any())
        <div style="border: 1px solid #fbd5d5; background-color: #fef2f2; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <ul style="color: #b91c1c; font-size: 12px; margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('error'))
        <div style="border: 1px solid #fbd5d5; background-color: #fef2f2; border-radius: 8px; padding: 16px; margin-bottom: 20px; color: #b91c1c; font-size: 12px; font-weight: bold;">
            {{ session('error') }}
        </div>
        @endif

        <form action="{{ route('goods_issues.store') }}" method="POST" id="gi-form">
            @csrf

            <div class="card">
                <div class="card-title">Buat Goods Issue</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Issue *</label>
                        <input type="date" name="tanggal_issue" class="form-input" required value="{{ old('tanggal_issue', date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dari Storage Location *</label>
                        <select name="storage_location_id" class="form-input" required>
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('storage_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->kode }} — {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe Issue *</label>
                        <select name="tipe_issue" id="tipe-issue-select" class="form-input" required>
                            <option value="Pemakaian Internal" {{ old('tipe_issue') == 'Pemakaian Internal' ? 'selected' : '' }}>Pemakaian Internal</option>
                            <option value="Kirim ke Vendor" {{ old('tipe_issue') == 'Kirim ke Vendor' ? 'selected' : '' }}>Kirim ke Vendor</option>
                            <option value="Kirim ke Customer" {{ old('tipe_issue') == 'Kirim ke Customer' ? 'selected' : '' }}>Kirim ke Customer</option>
                        </select>
                        <span id="tipe-issue-help" style="font-size: 11px; color: #94a3b8; margin-top: 6px;">Stok dikeluarkan untuk konsumsi produksi internal.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi Tujuan (opsional)</label>
                        <select name="dest_location" class="form-input">
                            <option value="">-- Pilih Lokasi Tujuan --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->kode }} — {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-bottom: 24px;">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-input" placeholder="Masukkan keterangan tambahan jika ada...">{{ old('keterangan') }}</textarea>
                </div>

                <div class="items-section-header">
                    <span>Material yang Dikeluarkan</span>
                    <button type="button" class="btn-add-row" onclick="addRow()"><span class="material-icons" style="font-size: 14px;">add</span> + Tambah Baris</button>
                </div>

                <table class="gi-table">
                    <thead>
                        <tr>
                            <th style="width: 55%;">Material</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 25%;">Note / ID Packing</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        {{-- rows appended by javascript --}}
                    </tbody>
                </table>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Post Goods Issue</button>
                    <a href="{{ route('goods_issues.index') }}" class="btn-cancel">Batal</a>
                </div>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script>
    @php
        $materialsJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->kode,'name'=>$m->nama]);
    @endphp
    const MATERIALS = @json($materialsJson);
    let rowIdx = 0;

    function matSearch(r, inp) {
        inp._activeIdx = -1;
        const q = inp.value.trim().toLowerCase();
        document.getElementById(`mat-id-${r}`).value = '';
        const list = document.getElementById(`mat-list-${r}`);
        if (!q) { list.style.display = 'none'; return; }
        const hits = MATERIALS.filter(m => m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)).slice(0, 20);
        if (!hits.length) { list.style.display = 'none'; return; }
        list.innerHTML = hits.map(m =>
            `<li data-id="${m.id}" data-label="${m.code} - ${m.name}" data-r="${r}"
                onmousedown="pickMat(event, ${r})">
                <b>${m.code}</b> &mdash; ${m.name}
            </li>`
        ).join('');
        list.style.display = 'block';
    }
    
    function pickMat(e, r) {
        const li = e.currentTarget;
        document.getElementById(`mat-text-${r}`).value = li.dataset.label;
        document.getElementById(`mat-id-${r}`).value   = li.dataset.id;
        document.getElementById(`mat-list-${r}`).style.display = 'none';
    }
    
    function hideMat(r) {
        setTimeout(() => {
            const l = document.getElementById(`mat-list-${r}`);
            if (l) l.style.display = 'none';
        }, 180);
    }

    function matKeydown(r, inp, e) {
        const list = document.getElementById(`mat-list-${r}`);
        if (!list || list.style.display === 'none') return;
        const items = list.querySelectorAll('li');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
            items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
            items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                const el = items[inp._activeIdx];
                document.getElementById(`mat-text-${r}`).value = el.dataset.label;
                document.getElementById(`mat-id-${r}`).value   = el.dataset.id;
                list.style.display = 'none';
            }
        } else if (e.key === 'Escape') {
            list.style.display = 'none';
        }
    }

    function addRow() {
        const r = rowIdx++;
        const tr = document.createElement('tr');
        tr.id = `row-${r}`;
        tr.innerHTML = `
            <td style="position:relative; overflow:visible;">
                <input type="text" id="mat-text-${r}" placeholder="Ketik kode atau nama material..." autocomplete="off"
                    class="form-input"
                    oninput="matSearch(${r}, this)" onkeydown="matKeydown(${r}, this, event)" onblur="hideMat(${r})" required>
                <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}" required>
                <ul id="mat-list-${r}" class="suggestions-list"></ul>
            </td>
            <td>
                <input type="number" name="items[${r}][qty]" value="1" min="0.001" step="0.001" class="form-input" required>
            </td>
            <td>
                <input type="text" name="items[${r}][note]" class="form-input" placeholder="Contoh: PKG-0042">
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="btn-remove" onclick="document.getElementById('row-${r}').remove()">
                    <span class="material-icons" style="font-size:16px;">close</span>
                </button>
            </td>
        `;
        document.getElementById('items-body').appendChild(tr);
    }

    // Start with one row
    addRow();

    // Tipe Issue helper text updater
    const tipeIssueHelp = {
        'Pemakaian Internal': 'Stok dikeluarkan untuk konsumsi produksi internal.',
        'Kirim ke Vendor': 'Stok dikeluarkan untuk pengiriman ke Subkontraktor/Vendor.',
        'Kirim ke Customer': 'Stok dikeluarkan untuk pengiriman ke Customer.'
    };
    
    const selectEl = document.getElementById('tipe-issue-select');
    const helpEl = document.getElementById('tipe-issue-help');
    
    if (selectEl && helpEl) {
        // Set initial helper text on page load
        helpEl.textContent = tipeIssueHelp[selectEl.value] || tipeIssueHelp['Pemakaian Internal'];

        selectEl.addEventListener('change', function() {
            helpEl.textContent = tipeIssueHelp[this.value] || '';
        });
    }
</script>
@endpush
