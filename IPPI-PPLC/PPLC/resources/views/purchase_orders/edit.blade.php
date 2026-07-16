@extends('layouts.app')

@section('title', 'Edit Purchase Order')

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
            <h2><span class="material-icons">edit</span> Edit Purchase Order</h2>
            <div class="hero-meta">Edit rincian Purchase Order: {{ $purchaseOrder->po_number }}</div>
        </div>
    </div>

    <div class="content-body">
        @if($purchaseOrder->skm_order_id && $purchaseOrder->status === 'approved')
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #b45309; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons">info</span>
            <span>PO ini digenerate dari <strong>SKM</strong> dan berstatus <strong>Approved</strong>. Perubahan yang disimpan tidak mengubah status PO.</span>
        </div>
        @endif

        <form method="POST" action="{{ route('purchase_orders.update', $purchaseOrder->id) }}" id="po-form">
            @csrf
            
            <div class="card">
                <div class="form-section-title">
                    <span class="material-icons">info</span> Informasi Utama PO
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nomor EDN *</label>
                        <input type="text" name="po_number" value="{{ old('po_number', $purchaseOrder->po_number) }}"
                               class="form-input font-mono" required>
                        @error('po_number')<p style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Gudang --}}
                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Lokasi Gudang *</label>
                        <select name="storage_location_id" id="location-select" class="form-select" required onchange="onLocationChange(this)">
                            <option value="">-- Pilih Lokasi Gudang --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                                {{ old('storage_location_id', $purchaseOrder->storage_location_id) == $loc->id ? 'selected' : '' }}>
                                {{ $loc->code }} - {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="justify-content: flex-end; padding-bottom: 6px;">
                        <p id="location-hint" class="text-xs text-gray-500 italic" style="font-size: 12px; color: #64748b; margin: 0;">
                            Pilih lokasi gudang untuk menampilkan material yang sesuai.
                        </p>
                    </div>
                </div>

                {{-- Vendor & Tanggal --}}
                <div class="form-grid">
                    <div class="form-group">
                        <label>Vendor *</label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">-- Pilih Vendor --</option>
                            @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id', $purchaseOrder->vendor_id) == $v->id ? 'selected' : '' }}>
                                {{ $v->code }} - {{ $v->nama }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-grid" style="margin-bottom: 0; gap: 12px;">
                        <div class="form-group">
                            <label>Tanggal Order *</label>
                            <input type="date" name="order_date" value="{{ old('order_date', $purchaseOrder->order_date ? $purchaseOrder->order_date->format('Y-m-d') : '') }}" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Est. Pengiriman</label>
                            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '') }}" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" rows="2" class="form-textarea" placeholder="Catatan tambahan... (opsional)">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                </div>
            </div>

            {{-- Items --}}
            <div class="card">
                <div class="form-section-title" style="justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons">list</span> Item PO
                    </span>
                    <button type="button" onclick="addItem()" id="add-item-btn" class="btn btn-green" style="padding: 6px 12px; font-size: 12px;">
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
                <button type="submit" class="btn btn-blue">Perbarui PO</button>
                <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="btn btn-gray">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->kode,'name'=>$m->nama,'price'=>$m->standard_price,'type'=>$m->tipe]);
            $locationMapJson = $locations->mapWithKeys(fn($l) => [$l->kode => $l->tipe_material]);
            $existingJson = $purchaseOrder->items->map(fn($i) => ['material_id'=>$i->material_id,'material_code'=>$i->material->code ?? '','material_name'=>$i->material->nama ?? '','quantity'=>$i->qty,'unit_price'=>$i->unit_price]);
        @endphp
        const allMaterials = @json($materialJson);
        const locationCodeTypeMap = @json($locationMapJson);
        const existingItems = @json($existingJson);
        let rowIndex = 0;
        let filteredMaterials = [];

        function onLocationChange(sel, keepRows = false) {
            const code = sel.options[sel.selectedIndex]?.dataset?.code;
            const materialType = code ? (locationCodeTypeMap[code] ?? null) : null;

            if (!code) {
                filteredMaterials = [];
            } else if (materialType) {
                filteredMaterials = allMaterials.filter(m => m.type === materialType);
            } else {
                filteredMaterials = allMaterials;
            }

            const hint = document.getElementById('location-hint');
            if (!code) {
                hint.textContent = 'Pilih lokasi gudang untuk menampilkan material yang sesuai.';
            } else if (filteredMaterials.length) {
                hint.textContent = materialType
                    ? `Menampilkan material tipe ${materialType} (${filteredMaterials.length} item).`
                    : `Menampilkan semua material (${filteredMaterials.length} item).`;
            } else {
                hint.textContent = 'Tidak ada material untuk lokasi ini.';
            }

            if (!keepRows) {
                document.getElementById('items-body').innerHTML = '';
                rowIndex = 0;
                calcGrand();
            }
        }

        function addItem(mid=null, qty=1, price=0, mcode='', mname='') {
            const tbody = document.getElementById('items-body');
            const r = rowIndex;
            const labelVal = (mid && mcode) ? `${mcode} - ${mname}` : '';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="position:relative;overflow:visible;min-width:200px;">
                    <input type="text" id="mat-text-${r}" autocomplete="off" placeholder="Ketik kode/nama..." value="${labelVal}"
                        class="form-input" style="padding: 6px 10px; font-size: 13px;"
                        oninput="matSearch(${r}, this)"
                        onkeydown="matKeydown(${r}, this, event)"
                        onblur="matHide(${r})">
                    <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}" value="${mid ?? ''}" required>
                    <ul id="mat-list-${r}" class="suggestions-box" style="display:none; width: 100%;"></ul>
                </td>
                <td><input type="number" name="items[${r}][quantity]" class="form-input qty" style="padding: 6px 10px; font-size: 13px; text-align: right;" min="0.001" step="0.001" value="${qty}" onchange="calcRow(this)" required></td>
                <td><input type="number" name="items[${r}][unit_price]" class="form-input price" style="padding: 6px 10px; font-size: 13px; text-align: right;" min="0" step="0.01" value="${price}" onchange="calcRow(this)" required></td>
                <td style="text-align: right; font-weight: 700;" class="row-total">${(qty*price).toLocaleString('id-ID', {minimumFractionDigits: 0})}</td>
                <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove();calcGrand()" class="btn-remove"><span class="material-icons" style="font-size: 18px;">delete</span></button></td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
            calcGrand();
        }

        // ── Material typeahead per row ─────────────────────────────────
        function matSearch(r, inp) {
            inp._activeIdx = -1;
            const q = inp.value.trim().toLowerCase();
            document.getElementById(`mat-id-${r}`).value = '';
            const list = document.getElementById(`mat-list-${r}`);
            const materials = filteredMaterials.length ? filteredMaterials : allMaterials;
            if (!q || !materials.length) { list.style.display = 'none'; return; }
            const hits = materials.filter(m =>
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

        function calcRow(el){const r=el.closest('tr');const t=(parseFloat(r.querySelector('.qty').value)||0)*(parseFloat(r.querySelector('.price').value)||0);r.querySelector('.row-total').textContent=t.toLocaleString('id-ID',{minimumFractionDigits:0});calcGrand();}
        
        function calcGrand(){let g=0;document.querySelectorAll('.row-total').forEach(e=>g+=parseFloat(e.textContent.replace(/\./g,'').replace(',','.'))||0);document.getElementById('grand-total').textContent=g.toLocaleString('id-ID',{minimumFractionDigits:0});}

        // Init: set location, load existing items
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('location-select');
            if (sel.value) {
                onLocationChange(sel, true);
            }
            existingItems.forEach(i => addItem(i.material_id, i.quantity, i.unit_price, i.material_code, i.material_name));
        });
    </script>
@endsection
