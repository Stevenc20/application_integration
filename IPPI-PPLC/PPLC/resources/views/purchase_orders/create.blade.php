@extends('layouts.app')

@section('title', 'Buat Purchase Order')

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
        margin-bottom: 24px;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--navy-dark);
        margin-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
    }

    .form-input, .form-select, .form-textarea {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        outline: none;
        box-sizing: border-box;
        width: 100%;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: var(--red-main);
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        border: none;
        transition: all 0.15s ease;
    }

    .btn-blue { background: #2563eb; color: white; }
    .btn-blue:hover { background: #1d4ed8; }
    .btn-green { background: #10b981; color: white; }
    .btn-green:hover { background: #059669; }
    .btn-gray { background: #64748b; color: white; }
    .btn-gray:hover { background: #475569; }

    /* IMPORT PANEL */
    .import-panel {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .import-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .import-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e40af;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .import-hint {
        font-size: 12px;
        color: #2563eb;
        margin-bottom: 14px;
        line-height: 1.5;
    }

    /* TABLE */
    .table-wrap {
        overflow-x: auto;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    th {
        background: #f8fafc;
        padding: 12px 16px;
        font-weight: 700;
        color: #475569;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    td {
        padding: 10px 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .grand-total-row td {
        background: #f8fafc;
        font-weight: 800;
        font-size: 14px;
        color: var(--navy-dark);
        border-bottom: none;
    }

    /* TYPEAHEAD LIST */
    .suggestions-box {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 50;
        margin-top: 2px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        list-style: none;
        padding: 0;
    }
    .suggestions-box li {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    .suggestions-box li:hover {
        background: #f1f5f9;
    }
    .suggestions-box li:last-child {
        border-bottom: none;
    }

    .btn-remove {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fee2e2;
        padding: 6px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-remove:hover {
        background: #ef4444;
        color: white;
    }
</style>
@endpush

@section('content')
    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">add_shopping_cart</span> Buat Purchase Order</h2>
            <div class="hero-meta">Buat Purchase Order baru dengan mengisikan vendor, gudang, dan item material secara manual atau import Excel</div>
        </div>
    </div>

    <div class="content-body">
        <form method="POST" action="{{ route('purchase_orders.store') }}" id="po-form">
            @csrf

            <div class="card">
                <div class="form-section-title">
                    <span class="material-icons">info</span> Informasi Utama PO
                </div>

                {{-- Gudang --}}
                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Lokasi Gudang *</label>
                        <select name="storage_location_id" id="location-select" class="form-select" required onchange="onLocationChange(this)">
                            <option value="">-- Pilih Lokasi Gudang --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                                {{ old('storage_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->code }} - {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="justify-content: flex-end; padding-bottom: 6px;">
                        <p id="location-hint" class="text-xs text-gray-500 italic" style="font-size: 12px; color: #64748b; margin: 0;">
                            Pilih lokasi gudang terlebih dahulu untuk menampilkan material yang sesuai.
                        </p>
                    </div>
                </div>

                {{-- Vendor & Tanggal --}}
                <div class="form-grid">
                    <div class="form-group" style="position: relative;">
                        <label>Vendor *</label>
                        <input type="text" id="vendor-search"
                               value="{{ old('vendor_id') ? ($vendors->firstWhere('id', old('vendor_id'))?->kode.' - '.$vendors->firstWhere('id', old('vendor_id'))?->nama) : '' }}"
                               placeholder="Ketik kode atau nama vendor..."
                               autocomplete="off"
                               class="form-input"
                               oninput="vendorSearch(this)"
                               onkeydown="vendorKeydown(event)">
                        <input type="hidden" name="vendor_id" id="vendor-id-hidden" value="{{ old('vendor_id') }}">
                        <ul id="vendor-suggestions" class="suggestions-box" style="display: none;"></ul>
                    </div>

                    <div class="form-grid" style="margin-bottom: 0; gap: 12px;">
                        <div class="form-group">
                            <label>Tanggal Order *</label>
                            <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Est. Pengiriman</label>
                            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" rows="2" class="form-textarea" placeholder="Catatan tambahan... (opsional)">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Import Panel --}}
            <div class="import-panel">
                <div class="import-header">
                    <div class="import-title">
                        <span class="material-icons" style="font-size: 20px;">file_upload</span> Import Item dari Excel
                    </div>
                    <a href="{{ route('purchase_orders.import-template') }}" class="btn btn-gray" style="padding: 6px 12px; font-size: 12px; background: white; color: #1e40af; border: 1px solid #bfdbfe;">
                        <span class="material-icons" style="font-size: 16px;">download</span> Download Template
                    </a>
                </div>
                <div class="import-hint">
                    Download template, isi Kode Material + Qty + Harga Satuan, lalu upload. Item akan otomatis masuk ke tabel di bawah.<br>
                    <strong>Penting:</strong> Lokasi gudang harus dipilih terlebih dahulu agar filter material sesuai.
                </div>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <input type="file" id="import-file" accept=".xlsx,.xls" style="font-size: 13px;">
                    <button type="button" id="import-btn" onclick="doImport()" class="btn btn-blue" style="padding: 8px 16px; font-size: 13px;">
                        Upload & Import
                    </button>
                </div>
                <div id="import-result" style="display: none; margin-top: 16px;"></div>
            </div>

            {{-- Items --}}
            <div class="card">
                <div class="form-section-title" style="justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons">list</span> Item PO
                    </span>
                    <button type="button" onclick="addItem()" id="add-item-btn" class="btn btn-green" style="padding: 6px 12px; font-size: 12px;" disabled>
                        + Tambah Item
                    </button>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Material *</th>
                                <th style="width: 120px; text-align: right;">Qty *</th>
                                <th style="width: 160px; text-align: right;">Harga Satuan *</th>
                                <th style="width: 160px; text-align: right;">Total</th>
                                <th style="width: 50px; text-align: center;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body"></tbody>
                        <tfoot>
                            <tr class="grand-total-row">
                                <td colspan="3" style="text-align: right;">Total PO:</td>
                                <td style="text-align: right;" id="grand-total">0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-blue">Simpan PO</button>
                <a href="{{ route('purchase_orders.index') }}" class="btn btn-gray">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->kode,'name'=>$m->nama,'price'=>$m->standard_price,'type'=>$m->tipe]);
            $locationMapJson = $locations->mapWithKeys(fn($l) => [$l->kode => $l->tipe_material]);
            $vendorJson = $vendors->map(fn($v) => ['id'=>$v->id,'code'=>$v->kode,'name'=>$v->nama,'vendor_type'=>$v->vendor_type]);
        @endphp
        const allMaterials = @json($materialJson);
        const locationCodeTypeMap = @json($locationMapJson);
        const allVendors = @json($vendorJson);
        let rowIndex = 0;
        let filteredMaterials = [];
        let importedGroups = null;

        // ── Vendor autocomplete ───────────────────────────────────────────
        function vendorSearch(input) {
            input._activeIdx = -1;
            const q = input.value.trim().toLowerCase();
            const box = document.getElementById('vendor-suggestions');
            document.getElementById('vendor-id-hidden').value = '';
            if (!q) { box.style.display = 'none'; return; }
            const matches = allVendors.filter(v =>
                v.code.toLowerCase().includes(q) || v.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!matches.length) { box.style.display = 'none'; return; }
            box.innerHTML = matches.map(v =>
                `<li data-id="${v.id}" data-label="${v.code} - ${v.name}" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9;">
                    <span class="font-mono" style="color:#2563eb; font-weight:700;">${v.code}</span>
                    <span style="margin-left: 8px; color:#334155;">${v.name}</span>
                </li>`
            ).join('');
            box.style.display = 'block';
        }

        document.addEventListener('click', function(e) {
            const input = document.getElementById('vendor-search');
            const box   = document.getElementById('vendor-suggestions');
            if (input && !input.contains(e.target) && box && !box.contains(e.target)) {
                box.style.display = 'none';
            }
        });

        document.getElementById('vendor-suggestions').addEventListener('click', function(e) {
            const item = e.target.closest('[data-id]');
            if (!item) return;
            document.getElementById('vendor-search').value = item.dataset.label;
            document.getElementById('vendor-id-hidden').value = item.dataset.id;
            this.style.display = 'none';
            onVendorSelect(item.dataset.id);
        });

        function onVendorSelect(vendorId) {
            const vendor = allVendors.find(v => String(v.id) === String(vendorId));
            const isProcess = vendor && vendor.vendor_type === 'process';
            window._selectedVendorIsProcess = isProcess;
            const locSel = document.getElementById('location-select');
            if (locSel.value) onLocationChange(locSel);
        }

        function vendorKeydown(e) {
            const box = document.getElementById('vendor-suggestions');
            if (box.style.display === 'none') return;
            const items = box.querySelectorAll('[data-id]');
            if (!items.length) return;
            const inp = document.getElementById('vendor-search');
            if (inp._activeIdx === undefined) inp._activeIdx = -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                inp._activeIdx = Math.min(inp._activeIdx + 1, items.length - 1);
                items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                inp._activeIdx = Math.max(inp._activeIdx - 1, 0);
                items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                    const sel = items[inp._activeIdx];
                    inp.value = sel.dataset.label;
                    document.getElementById('vendor-id-hidden').value = sel.dataset.id;
                    box.style.display = 'none';
                    onVendorSelect(sel.dataset.id);
                }
            } else if (e.key === 'Escape') {
                box.style.display = 'none';
            }
        }

        // Validate vendor selected before submit
        document.getElementById('po-form').addEventListener('submit', function(e) {
            if (!document.getElementById('vendor-id-hidden').value) {
                e.preventDefault();
                document.getElementById('vendor-search').focus();
                document.getElementById('vendor-search').style.borderColor = '#ef4444';
                alert('Pilih vendor dari daftar saran terlebih dahulu.');
            }
        });

        // ── Location Change ──────────────────────────────────────────────
        function onLocationChange(sel) {
            const code = sel.options[sel.selectedIndex]?.dataset?.code;
            const materialType = code ? (locationCodeTypeMap[code] ?? null) : null;
            const isProcess = window._selectedVendorIsProcess === true;

            if (!code) {
                filteredMaterials = [];
            } else if (materialType && !isProcess) {
                filteredMaterials = allMaterials.filter(m => m.type === materialType);
            } else {
                filteredMaterials = allMaterials;
            }

            const hint = document.getElementById('location-hint');
            const btn  = document.getElementById('add-item-btn');

            if (!code) {
                hint.textContent = 'Pilih lokasi gudang terlebih dahulu untuk menampilkan material yang sesuai.';
                btn.disabled = true;
            } else if (filteredMaterials.length) {
                const label = isProcess
                    ? `Vendor proses — menampilkan semua material (${filteredMaterials.length} item).`
                    : (materialType
                        ? `Menampilkan material tipe ${materialType} (${filteredMaterials.length} item).`
                        : `Menampilkan semua material (${filteredMaterials.length} item).`);
                hint.textContent = label;
                btn.disabled = false;
            } else {
                hint.textContent = 'Tidak ada material untuk lokasi ini.';
                btn.disabled = true;
            }

            // Clear existing rows when location changes
            document.getElementById('items-body').innerHTML = '';
            rowIndex = 0;
            calcGrand();
        }

        // ── Material typeahead per row ─────────────────────────────────
        function matSearch(r, inp) {
            inp._activeIdx = -1;
            const q = inp.value.trim().toLowerCase();
            document.getElementById(`mat-id-${r}`).value = '';
            const list = document.getElementById(`mat-list-${r}`);
            if (!q || !filteredMaterials.length) { list.style.display = 'none'; return; }
            const hits = filteredMaterials.filter(m =>
                m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!hits.length) { list.style.display = 'none'; return; }
            list.innerHTML = hits.map(m =>
                `<li data-id="${m.id}" data-label="${m.code} - ${m.name}" data-price="${m.price}"
                    style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom: 1px solid #f1f5f9;list-style:none;"
                    onmousedown="matPick(${r}, this)">
                    <b>${m.code}</b> &mdash; ${m.name}
                </li>`
            ).join('');
            list.style.display = 'block';
        }

        function matPick(r, li) {
            const inp = document.getElementById(`mat-text-${r}`);
            inp.value = li.dataset.label;
            document.getElementById(`mat-id-${r}`).value = li.dataset.id;
            document.getElementById(`mat-list-${r}`).style.display = 'none';
            const row = inp.closest('tr');
            const priceInp = row.querySelector('.price');
            if (priceInp) { priceInp.value = li.dataset.price || 0; calcRow(priceInp); }
        }

        function matKeydown(r, inp, e) {
            const list = document.getElementById(`mat-list-${r}`);
            if (list.style.display === 'none') return;
            const items = list.querySelectorAll('li');
            if (!items.length) return;
            if (inp._activeIdx === undefined) inp._activeIdx = -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                inp._activeIdx = Math.min(inp._activeIdx + 1, items.length - 1);
                items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                inp._activeIdx = Math.max(inp._activeIdx - 1, 0);
                items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (inp._activeIdx >= 0 && inp._activeIdx < items.length) matPick(r, items[inp._activeIdx]);
            } else if (e.key === 'Escape') {
                list.style.display = 'none';
            }
        }

        function matHide(r) {
            setTimeout(() => { const l = document.getElementById(`mat-list-${r}`); if (l) l.style.display = 'none'; }, 150);
        }

        function addItem() {
            if (!filteredMaterials.length) return;
            const tbody = document.getElementById('items-body');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="position:relative;overflow:visible;min-width:200px;">
                    <input type="text" id="mat-text-${rowIndex}" autocomplete="off" placeholder="Ketik kode/nama..."
                        class="form-input" style="padding: 6px 10px; font-size: 13px;"
                        oninput="matSearch(${rowIndex}, this)"
                        onkeydown="matKeydown(${rowIndex}, this, event)"
                        onblur="matHide(${rowIndex})">
                    <input type="hidden" name="items[${rowIndex}][material_id]" id="mat-id-${rowIndex}" required>
                    <ul id="mat-list-${rowIndex}" class="suggestions-box" style="display:none; width: 100%;"></ul>
                </td>
                <td><input type="number" name="items[${rowIndex}][quantity]" class="form-input qty" style="padding: 6px 10px; font-size: 13px; text-align: right;" min="0.001" step="0.001" value="1" onchange="calcRow(this)" required></td>
                <td><input type="number" name="items[${rowIndex}][unit_price]" class="form-input price" style="padding: 6px 10px; font-size: 13px; text-align: right;" min="0" step="0.01" value="0" onchange="calcRow(this)" required></td>
                <td style="text-align: right; font-weight: 700;" class="row-total">0</td>
                <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove();calcGrand()" class="btn-remove"><span class="material-icons" style="font-size: 18px;">delete</span></button></td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
        }

        function calcRow(el) {
            const row = el.closest('tr');
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            const total = qty * price;
            row.querySelector('.row-total').textContent = total.toLocaleString('id-ID', {minimumFractionDigits:0});
            calcGrand();
        }

        function calcGrand() {
            let grand = 0;
            document.querySelectorAll('.row-total').forEach(el => {
                grand += parseFloat(el.textContent.replace(/\./g,'').replace(',','.')) || 0;
            });
            document.getElementById('grand-total').textContent = grand.toLocaleString('id-ID', {minimumFractionDigits:0});
        }

        // ── Excel Import Logic ───────────────────────────────────────────
        async function doImport() {
            const fileInput = document.getElementById('import-file');

            if (!fileInput.files.length) {
                showImportMsg('error', ['Pilih file Excel terlebih dahulu.']);
                return;
            }
            const locationSel = document.getElementById('location-select');
            if (!locationSel.value) {
                showImportMsg('error', ['Pilih Lokasi Gudang terlebih dahulu sebelum import, agar filter material sesuai.']);
                return;
            }

            const btn = document.getElementById('import-btn');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            const fd = new FormData();
            fd.append('file', fileInput.files[0]);
            fd.append('_token', '{{ csrf_token() }}');

            try {
                const res  = await fetch('{{ route('purchase_orders.import-excel') }}', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.items && data.items.length > 0) {
                    if (data.order_date) {
                        document.querySelector('[name="order_date"]').value = data.order_date;
                    }

                    const groupMap = {};
                    data.items.forEach(item => {
                        const key = item.expected_delivery_date || '__nodate__';
                        if (!groupMap[key]) groupMap[key] = { delivery_date: item.expected_delivery_date || null, items: [] };
                        groupMap[key].items.push(item);
                    });
                    importedGroups = Object.values(groupMap);
                    showImportPreview(importedGroups, data.errors || []);
                } else if (data.errors && data.errors.length > 0) {
                    showImportMsg('error', data.errors);
                } else {
                    showImportMsg('error', ['Tidak ada item yang dapat diimport. Periksa format file.']);
                }
            } catch (e) {
                showImportMsg('error', ['Gagal memproses file. Pastikan format Excel sesuai template.']);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Upload & Import';
            }
        }

        function fmtDate(d) {
            if (!d) return '<span style="font-style: italic; color:#94a3b8;">Tanpa tanggal</span>';
            const dt = new Date(d + 'T00:00:00');
            return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        function showImportPreview(groups, errors) {
            const box = document.getElementById('import-result');
            const totalItems = groups.reduce((s, g) => s + g.items.length, 0);

            let html = `<div style="font-weight: 700; color: #1e3a8a; margin-bottom: 12px;">
                Preview: <strong>${totalItems} item</strong> akan dibuat menjadi <strong>${groups.length} PO</strong> berdasarkan tanggal estimasi pengiriman.
            </div>`;

            groups.forEach((g, i) => {
                html += `<div style="margin-bottom: 8px; padding: 12px; background: white; border: 1px solid #bfdbfe; border-radius: 6px;">
                    <div style="font-weight: 700; font-size: 13px; color: #2563eb; margin-bottom: 4px;">
                        PO ${i + 1} — Est. Kirim: ${fmtDate(g.delivery_date)}
                        <span style="color: #64748b; font-weight: normal;">&nbsp;(${g.items.length} item)</span>
                    </div>
                    <div style="font-size: 12px; color: #475569;">
                        ${g.items.map(it => `<span style="display:inline-block; margin-right: 8px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">${it.material_code} &times; ${it.quantity}</span>`).join('')}
                    </div>
                </div>`;
            });

            if (errors.length > 0) {
                html += `<div style="margin-top: 12px; padding: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; font-size: 12px; color: #b45309;">
                    <div style="font-weight: 700; margin-bottom: 4px;">Baris yang dilewati:</div>
                    ${errors.map(e => `<div>• ${e}</div>`).join('')}
                </div>`;
            }

            html += `<div style="margin-top: 16px; display: flex; gap: 8px;">
                <button type="button" id="create-po-btn" onclick="createImportedPOs()" class="btn btn-blue" style="font-size:13px;">
                    Buat ${groups.length} PO Sekarang
                </button>
                <button type="button" onclick="cancelImport()" class="btn btn-gray" style="font-size:13px;">
                    Batal
                </button>
            </div>`;

            box.className = '';
            box.style.display = 'block';
            box.innerHTML = html;
        }

        async function createImportedPOs() {
            if (!importedGroups) return;

            const vendorId   = document.getElementById('vendor-id-hidden').value;
            const locationId = document.getElementById('location-select').value;
            const orderDate  = document.querySelector('[name="order_date"]').value;
            const notes      = document.querySelector('[name="notes"]').value;

            if (!vendorId)   { alert('Pilih Vendor terlebih dahulu.'); return; }
            if (!locationId) { alert('Pilih Lokasi Gudang terlebih dahulu.'); return; }
            if (!orderDate)  { alert('Isi Tanggal Order terlebih dahulu.'); return; }

            const btn = document.getElementById('create-po-btn');
            btn.disabled = true;
            btn.textContent = 'Membuat PO...';

            try {
                const res = await fetch('{{ route('purchase_orders.import-create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        vendor_id:           vendorId,
                        storage_location_id: locationId,
                        order_date:          orderDate,
                        notes:               notes,
                        groups: importedGroups.map(g => ({
                            delivery_date: g.delivery_date,
                            items: g.items.map(it => ({
                                material_id: it.material_id,
                                quantity:    it.quantity,
                                unit_price:  it.unit_price,
                            })),
                        })),
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    const box = document.getElementById('import-result');
                    box.innerHTML = `<div style="font-weight: 700; color: #15803d; margin-bottom: 8px;">✓ Berhasil membuat ${data.po_numbers.length} Purchase Order:</div>
                        ${data.po_numbers.map(p => `<div style="margin-left: 8px; font-family: monospace;">• ${p.po_number}</div>`).join('')}
                        <div style="margin-top: 8px; font-size: 12px; color: #166534;">Mengalihkan ke daftar PO...</div>`;
                    setTimeout(() => { window.location.href = data.redirect; }, 2000);
                } else {
                    showImportMsg('error', [data.message || 'Gagal membuat PO.']);
                    btn.disabled = false;
                    btn.textContent = `Buat ${importedGroups.length} PO Sekarang`;
                }
            } catch (e) {
                showImportMsg('error', ['Terjadi kesalahan. Silakan coba lagi.']);
                btn.disabled = false;
                btn.textContent = `Buat ${importedGroups.length} PO Sekarang`;
            }
        }

        function cancelImport() {
            importedGroups = null;
            document.getElementById('import-result').style.display = 'none';
            document.getElementById('import-file').value = '';
        }

        function showImportMsg(type, lines) {
            const box = document.getElementById('import-result');
            const styles = {
                success: 'background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d;',
                warn:    'background: #fffbeb; border: 1px solid #fde68a; color: #b45309;',
                error:   'background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;',
            };
            box.style.cssText = `padding: 12px; border-radius: 6px; font-size: 13px; ${styles[type] || styles.error}`;
            box.innerHTML = lines.map(l => `<div>• ${l}</div>`).join('');
            box.style.display = 'block';
        }
    </script>
@endsection
