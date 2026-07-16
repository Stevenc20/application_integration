@extends('layouts.app')

@section('title', 'Buat BOM')

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
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 24px;
    }

    /* FORM STYLES */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 20px;
    }
    .form-group {
        flex: 1;
        min-width: 250px;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .form-label {
        font-size: 12px;
        color: #475569;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .form-input, .form-select {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        outline: none;
    }
    .form-input:focus, .form-select:focus {
        border-color: #94a3b8;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 30px;
    }
    .checkbox-group input {
        width: 16px;
        height: 16px;
        accent-color: #3b82f6;
    }
    .checkbox-group label {
        font-size: 13px;
        color: #334155;
        font-weight: 600;
        cursor: pointer;
    }

    /* KOMPONEN SECTION */
    .komponen-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .komponen-title {
        font-size: 14px;
        font-weight: 800;
        color: #334155;
    }
    .btn-add {
        background: #10b981;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-add:hover { background: #059669; }

    .komponen-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    .komponen-table th {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        text-align: left;
        padding-bottom: 8px;
        background: #f8fafc;
        padding: 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    .komponen-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }
    .komponen-table td:first-child,
    .komponen-table th:first-child {
        padding-left: 0;
        background: transparent;
    }
    .komponen-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        color: #334155;
    }
    .btn-delete {
        color: #ef4444;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 16px;
        font-weight: 800;
        padding: 4px;
    }

    /* ACTION BUTTONS */
    .form-actions {
        display: flex;
        gap: 12px;
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

    /* Autocomplete list styles */
    .suggestions-box {
        position: absolute;
        z-index: 1000;
        width: 100%;
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 0 0 6px 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        max-h: 200px;
        overflow-y: auto;
        top: 100%;
        left: 0;
    }
    .suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12px;
    }
    .suggestion-item:hover {
        background: #eff6ff;
    }
    .suggestion-item .item-code {
        font-family: monospace;
        font-weight: 700;
        color: #2563eb;
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">account_tree</span> Buat BOM</h2>
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

        <div class="card">
            <div class="card-title">Buat Bill of Materials Baru</div>
            
            <form action="{{ route('boms.store') }}" method="POST" id="bom-form">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Material Hasil (FP/WIP) *</label>
                        <input type="text" id="fp-search"
                               value="{{ old('material_id') ? ($materials->firstWhere('id', old('material_id'))?->kode.' - '.$materials->firstWhere('id', old('material_id'))?->nama) : '' }}"
                               placeholder="Ketik kode atau nama material..."
                               autocomplete="off"
                               class="form-input"
                               oninput="fpSearch(this)"
                               onkeydown="fpKeydown(event)"
                               required>
                        <input type="hidden" name="material_id" id="fp-id" value="{{ old('material_id') }}">
                        <div id="fp-suggestions" class="suggestions-box" style="display:none;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qty Base *</label>
                        <input type="number" name="base_quantity" value="{{ old('base_quantity', 1) }}" class="form-input" min="0.001" step="0.001" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Berlaku Mulai *</label>
                        <input type="date" name="valid_from" value="{{ old('valid_from', date('Y-m-d')) }}" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berlaku Hingga</label>
                        <input type="date" name="valid_to" value="{{ old('valid_to') }}" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-input" placeholder="Masukkan deskripsi BOM...">
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="status_aktif" name="status" value="active" checked>
                    <label for="status_aktif">Aktif</label>
                </div>

                <!-- KOMPONEN BOM -->
                <div class="komponen-header">
                    <div class="komponen-title">Komponen BOM</div>
                    <button type="button" class="btn-add" onclick="addRow()">+ Tambah Komponen</button>
                </div>
                
                <table class="komponen-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Material Komponen</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 15%;">UoM</th>
                            <th style="width: 15%;">Catatan</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body"></tbody>
                </table>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan BOM</button>
                    <a href="{{ route('boms.index') }}" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    @php
        $materialJson = $materials->map(fn($m) => [
            'id' => $m->id,
            'kode' => $m->kode,
            'nama' => $m->nama,
            'tipe' => $m->tipe,
            'uom' => $m->uom
        ]);
    @endphp
    const materials = @json($materialJson);
    let r = 0;

    // Material Hasil autocomplete
    function fpSearch(input) {
        input._activeIdx = -1;
        const q = input.value.trim().toLowerCase();
        const box = document.getElementById('fp-suggestions');
        document.getElementById('fp-id').value = '';
        if (!q) { box.style.display = 'none'; return; }
        const matches = materials.filter(m =>
            m.kode.toLowerCase().includes(q) || m.nama.toLowerCase().includes(q)
        ).slice(0, 20);
        if (!matches.length) { box.style.display = 'none'; return; }
        box.innerHTML = matches.map(m =>
            `<div class="suggestion-item" data-id="${m.id}" data-label="${m.kode} - ${m.nama}" data-uom="${m.uom ?? ''}">
                <span class="item-code">${m.kode}</span>
                <span style="margin-left:8px; color:#334155;">${m.nama}</span>
                <span style="margin-left:4px; color:#94a3b8; font-size:10px;">(${m.tipe})</span>
            </div>`
        ).join('');
        box.style.display = 'block';
    }

    document.getElementById('fp-suggestions').addEventListener('click', function(e) {
        const item = e.target.closest('[data-id]');
        if (!item) return;
        document.getElementById('fp-search').value = item.dataset.label;
        document.getElementById('fp-id').value = item.dataset.id;
        this.style.display = 'none';
    });

    function fpKeydown(e) {
        const box = document.getElementById('fp-suggestions');
        if (box.style.display === 'none') return;
        const inp = document.getElementById('fp-search');
        const items = box.querySelectorAll('[data-id]');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
            items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
            items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                const el = items[inp._activeIdx];
                inp.value = el.dataset.label;
                document.getElementById('fp-id').value = el.dataset.id;
                box.style.display = 'none';
            }
        } else if (e.key === 'Escape') {
            box.style.display = 'none';
        }
    }

    // Component row autocomplete
    function addRow(mid=null, qty=1, uom='', label='', notes='') {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div style="position:relative;">
                    <input type="text" id="comp-search-${r}"
                           value="${label}"
                           placeholder="Ketik kode atau nama material..."
                           autocomplete="off"
                           class="komponen-input"
                           oninput="compSearch(${r}, this)"
                           onkeydown="compKeydown(${r}, event)">
                    <input type="hidden" name="items[${r}][material_id]" id="comp-id-${r}" value="${mid ?? ''}">
                    <div id="comp-sug-${r}" class="suggestions-box" style="display:none; min-width:320px;"></div>
                </div>
            </td>
            <td>
                <input type="number" name="items[${r}][quantity]" value="${qty}"
                       class="komponen-input" style="text-align: right;" min="0.001" step="0.001" required>
            </td>
            <td>
                <input type="text" name="items[${r}][unit]" id="comp-uom-${r}" value="${uom}"
                       class="komponen-input" placeholder="PCS">
            </td>
            <td>
                <input type="text" name="items[${r}][notes]" value="${notes}"
                       class="komponen-input" placeholder="Opsional">
            </td>
            <td style="text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" class="btn-delete">&times;</button>
            </td>
        `;
        document.getElementById('items-body').appendChild(tr);

        document.getElementById(`comp-sug-${r}`).addEventListener('click', function(e) {
            const item = e.target.closest('[data-id]');
            if (!item) return;
            const row = item.dataset.row;
            document.getElementById(`comp-search-${row}`).value = item.dataset.label;
            document.getElementById(`comp-id-${row}`).value = item.dataset.id;
            document.getElementById(`comp-uom-${row}`).value = item.dataset.uom || '';
            this.style.display = 'none';
        });
        r++;
    }

    function compSearch(idx, input) {
        input._activeIdx = -1;
        const q = input.value.trim().toLowerCase();
        const box = document.getElementById(`comp-sug-${idx}`);
        document.getElementById(`comp-id-${idx}`).value = '';
        if (!q) { box.style.display = 'none'; return; }
        const matches = materials.filter(m =>
            m.kode.toLowerCase().includes(q) || m.nama.toLowerCase().includes(q)
        ).slice(0, 20);
        if (!matches.length) { box.style.display = 'none'; return; }
        box.innerHTML = matches.map(m =>
            `<div class="suggestion-item" data-id="${m.id}" data-row="${idx}" data-label="${m.kode} - ${m.nama}" data-uom="${m.uom ?? ''}">
                <span class="item-code">${m.kode}</span>
                <span style="margin-left:8px; color:#334155;">${m.nama}</span>
                <span style="margin-left:4px; color:#94a3b8; font-size:10px;">(${m.uom ?? '-'})</span>
            </div>`
        ).join('');
        box.style.display = 'block';
    }

    function compKeydown(idx, e) {
        const box = document.getElementById(`comp-sug-${idx}`);
        if (!box || box.style.display === 'none') return;
        const inp = document.getElementById(`comp-search-${idx}`);
        const items = box.querySelectorAll('[data-id]');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
            items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
            items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                const el = items[inp._activeIdx];
                inp.value = el.dataset.label;
                document.getElementById(`comp-id-${idx}`).value = el.dataset.id;
                document.getElementById(`comp-uom-${idx}`).value = el.dataset.uom || '';
                box.style.display = 'none';
            }
        } else if (e.key === 'Escape') {
            box.style.display = 'none';
        }
    }

    // Close all dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#fp-search') && !e.target.closest('#fp-suggestions')) {
            document.getElementById('fp-suggestions').style.display = 'none';
        }
        if (!e.target.closest('[id^="comp-search-"]') && !e.target.closest('[id^="comp-sug-"]')) {
            document.querySelectorAll('[id^="comp-sug-"]').forEach(b => b.style.display = 'none');
        }
    });

    // Validate on submit
    document.getElementById('bom-form').addEventListener('submit', function(e) {
        if (!document.getElementById('fp-id').value) {
            e.preventDefault();
            document.getElementById('fp-search').focus();
            alert('Pilih material hasil dari daftar saran terlebih dahulu.');
            return;
        }
        let ok = true;
        document.querySelectorAll('[id^="comp-id-"]').forEach(function(hidden) {
            if (!hidden.value) {
                const idx = hidden.id.replace('comp-id-', '');
                const inp = document.getElementById('comp-search-' + idx);
                if (inp) { ok = false; }
            }
        });
        if (!ok) {
            e.preventDefault();
            alert('Pilih material komponen dari daftar saran untuk semua baris.');
        }
    });

    // Add first row by default
    addRow();
</script>
@endpush
