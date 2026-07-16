@extends('layouts.app')

@section('title', 'Edit BOM')

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

</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">account_tree</span> Edit BOM: {{ $bom->bom_number }}</h2>
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
            <div class="card-title">Edit BOM: {{ $bom->bom_number }}</div>
            
            <form action="{{ route('boms.update', $bom->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Material Hasil (FP/WIP) *</label>
                        <select name="material_id" class="form-select" required>
                            @foreach($materials as $m)
                            <option value="{{ $m->id }}" {{ old('material_id', $bom->material_id) == $m->id ? 'selected' : '' }}>{{ $m->kode }} - {{ $m->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qty Base *</label>
                        <input type="number" name="base_quantity" value="{{ old('base_quantity', (float) $bom->base_quantity) }}" class="form-input" min="0.001" step="0.001" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Berlaku Mulai *</label>
                        <input type="date" name="valid_from" value="{{ old('valid_from', $bom->valid_from?->format('Y-m-d')) }}" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berlaku Hingga</label>
                        <input type="date" name="valid_to" value="{{ old('valid_to', $bom->valid_to?->format('Y-m-d')) }}" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="description" value="{{ old('description', $bom->description) }}" class="form-input">
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="status_aktif" name="status" value="active" {{ $bom->status === 'active' ? 'checked' : '' }}>
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
                    <button type="submit" class="btn-submit">Perbarui BOM</button>
                    <a href="{{ route('boms.show', $bom->id) }}" class="btn-cancel">Batal</a>
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
            'uom' => $m->uom
        ]);
        $existingJson = $bom->items->map(fn($i) => [
            'material_id' => $i->material_id,
            'quantity' => (float) $i->quantity,
            'unit' => $i->unit,
            'notes' => $i->notes ?? ''
        ]);
    @endphp
    const materials = @json($materialJson);
    const existing = @json($existingJson);
    let r = 0;

    function addRow(mid=null, qty=1, uom='', notes=''){
        const opts = materials.map(m=>`<option value="${m.id}" data-uom="${m.uom || ''}" ${mid==m.id?'selected':''}>${m.kode} - ${m.nama}</option>`).join('');
        const tr = document.createElement('tr');
        tr.innerHTML=`
            <td>
                <select name="items[${r}][material_id]" class="komponen-input" required onchange="fillUom(this)">
                    <option value="">-- Pilih --</option>
                    ${opts}
                </select>
            </td>
            <td>
                <input type="number" name="items[${r}][quantity]" value="${qty}" class="komponen-input" style="text-align: right;" min="0.001" step="0.001" required>
            </td>
            <td>
                <input type="text" name="items[${r}][unit]" id="comp-uom-${r}" value="${uom}" class="komponen-input uom-field" placeholder="PCS">
            </td>
            <td>
                <input type="text" name="items[${r}][notes]" value="${notes}" class="komponen-input" placeholder="Opsional">
            </td>
            <td style="text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" class="btn-delete">&times;</button>
            </td>
        `;
        document.getElementById('items-body').appendChild(tr); r++;
    }

    function fillUom(sel){
        const opt=sel.options[sel.selectedIndex];
        const row = sel.name.match(/\d+/)[0];
        document.getElementById(`comp-uom-${row}`).value=opt.dataset.uom || '';
    }

    existing.forEach(i=>addRow(i.material_id, i.quantity, i.unit, i.notes));
    if(!existing.length) addRow();
</script>
@endpush
