@extends('layouts.app')

@section('title', 'Buat Production Order')

@push('styles')
<style>
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
        margin-bottom: 24px;
        overflow: visible;
    }

    .card-title {
        font-size: 15px;
        font-weight: 800;
        color: #1e293b;
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-body {
        padding: 24px;
    }

    /* FORM STYLES */
    .form-row {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }
    .form-group {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
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
    }
    .form-input:focus { border-color: var(--navy-dark); }

    /* TABLE STYLES */
    .pro-table {
        width: 100%;
        border-collapse: collapse;
    }
    .pro-table th {
        background: var(--red-main);
        color: white;
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        padding: 12px 16px;
        border: none;
    }
    .pro-table td {
        padding: 12px 8px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }
    .pro-table input, .pro-table select {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        width: 100%;
        box-sizing: border-box;
        outline: none;
    }
    .pro-table input:focus, .pro-table select:focus { border-color: var(--navy-dark); }
    .btn-remove {
        color: #ef4444;
        background: none;
        border: none;
        cursor: pointer;
        padding: 6px;
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
        margin-top: 20px;
    }
    .btn-submit {
        background: var(--red-main);
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
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
    }
    .btn-cancel:hover { background: #cbd5e1; }
    
    .help-text {
        font-size: 11px;
        color: #94a3b8;
        padding: 12px 24px;
    }

    /* Autocomplete list styles */
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

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">precision_manufacturing</span> Buat Production Order</h2>
        </div>
    </div>

    <div class="content-body">
        
        @if ($errors->any())
        <div class="card" style="border-color: #fbd5d5; background-color: #fef2f2; padding: 16px; margin-bottom: 20px;">
            <ul style="color: #b91c1c; font-size: 12px; margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('production_orders.store') }}" method="POST" id="po-form">
            @csrf

            <!-- Section 1: Rentang Tanggal Rencana -->
            <div class="card">
                <div class="card-title">Parameter Rencana Produksi</div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tgl Mulai Rencana *</label>
                            <input type="date" name="planned_start_date" class="form-input" required value="{{ old('planned_start_date', date('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tgl Selesai Rencana *</label>
                            <input type="date" name="planned_end_date" class="form-input" required value="{{ old('planned_end_date', date('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 16px;">
                        <label class="form-label">Catatan Umum (Opsional)</label>
                        <textarea name="general_notes" class="form-input" rows="2" placeholder="Masukkan catatan umum untuk semua order dalam batch ini...">{{ old('general_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Daftar Item Production Order -->
            <div class="card" style="overflow: visible;">
                <div class="card-title">
                    Daftar Production Order
                    <button type="button" class="btn-add-row" onclick="addRow()"><span class="material-icons" style="font-size: 14px;">add</span> Tambah Baris</button>
                </div>
                
                 <table class="pro-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">No. Order *</th>
                            <th style="width: 40%;">Material *</th>
                            <th style="width: 15%; text-align: right;">Qty Planned *</th>
                            <th style="width: 15%;">Catatan Item</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                </table>
                <div class="help-text">
                    * Komponen BOM otomatis dibuat saat Production Order disimpan.
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Simpan Semua</button>
                <a href="{{ route('production_orders.index') }}" class="btn-cancel">Batal</a>
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
            <td>
                <input type="text" name="orders[${r}][order_number]" placeholder="Nomor PO/Order..." class="form-input" style="font-family: monospace;" required>
            </td>
            <td style="position:relative; overflow:visible;">
                <input type="text" id="mat-text-${r}" placeholder="Ketik kode / nama..." autocomplete="off"
                    class="form-input"
                    oninput="matSearch(${r}, this)" onkeydown="matKeydown(${r}, this, event)" onblur="hideMat(${r})" required>
                <input type="hidden" name="orders[${r}][material_id]" id="mat-id-${r}" required>
                <ul id="mat-list-${r}" class="suggestions-list"></ul>
            </td>
            <td>
                <input type="number" name="orders[${r}][quantity_planned]" value="1" min="0.001" step="0.001" class="form-input" style="text-align: right;" required>
            </td>
            <td>
                <input type="text" name="orders[${r}][notes]" class="form-input" placeholder="opsional">
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="btn-remove" onclick="document.getElementById('row-${r}').remove()">
                    <span class="material-icons" style="font-size:16px;">close</span>
                </button>
            </td>
        `;
        document.getElementById('items-body').appendChild(tr);
    }

    // Start with one empty row
    addRow();
</script>
@endpush
