@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="space-y-6">
    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl text-white/80">edit</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Edit Purchase Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Edit rincian Purchase Order: {{ $purchaseOrder->po_number }}</p>
            </div>
        </div>
        <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="relative bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg whitespace-nowrap">
            <span class="material-icons text-sm">arrow_back</span>
            Kembali
        </a>
    </div>

    {{-- SKM Warning --}}
    @if($purchaseOrder->skm_order_id && $purchaseOrder->status === 'approved')
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3 text-amber-700 text-sm font-semibold">
        <span class="material-icons">info</span>
        <span>PO ini digenerate dari <strong>SKM</strong> dan berstatus <strong>Approved</strong>. Perubahan yang disimpan tidak mengubah status PO.</span>
    </div>
    @endif

    <form method="POST" action="{{ route('purchase_orders.update', $purchaseOrder->id) }}" id="po-form">
        @csrf

        {{-- Informasi Utama PO --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                    <span class="material-icons text-rose-600">info</span> Informasi Utama PO
                </h3>
            </div>
            <div class="p-6 space-y-6">
                {{-- Nomor PO --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor PO <span class="text-rose-500">*</span></label>
                        <input type="text" name="po_number" value="{{ old('po_number', $purchaseOrder->po_number) }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-blue-600 font-mono outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all" required>
                        @error('po_number')<p class="text-rose-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Gudang --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Lokasi Gudang <span class="text-rose-500">*</span></label>
                        <select name="storage_location_id" id="location-select" required onchange="onLocationChange(this)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all cursor-pointer appearance-none">
                            <option value="">-- Pilih Lokasi Gudang --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                                {{ old('storage_location_id', $purchaseOrder->storage_location_id) == $loc->id ? 'selected' : '' }}>
                                {{ $loc->code }} - {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end pb-1">
                        <p id="location-hint" class="text-xs text-slate-400 italic">Pilih lokasi gudang untuk menampilkan material yang sesuai.</p>
                    </div>
                </div>

                {{-- Vendor & Tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Vendor <span class="text-rose-500">*</span></label>
                        <select name="vendor_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all cursor-pointer appearance-none">
                            <option value="">-- Pilih Vendor --</option>
                            @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id', $purchaseOrder->vendor_id) == $v->id ? 'selected' : '' }}>
                                {{ $v->code }} - {{ $v->nama }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal Order <span class="text-rose-500">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', $purchaseOrder->order_date ? $purchaseOrder->order_date->format('Y-m-d') : '') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Est. Pengiriman</label>
                            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Catatan tambahan... (opsional)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Item PO --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                        <span class="material-icons text-rose-600">list</span> Item PO
                    </h3>
                    <button type="button" onclick="addItem()" id="add-item-btn" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-4 rounded-xl transition-all text-sm border border-emerald-200 flex items-center gap-1">
                        <span class="material-icons text-sm">add</span> Tambah Item
                    </button>
                </div>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-y border-slate-200">
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Material *</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[120px]">Qty *</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[160px]">Harga Satuan *</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right w-[160px]">Total</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center w-[50px]"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="divide-y divide-slate-100"></tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold">
                            <td colspan="3" class="py-3 px-4 text-right text-sm text-slate-700">Total PO:</td>
                            <td class="py-3 px-4 text-right text-sm" style="color: var(--red-main);" id="grand-total">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-6 rounded-xl transition-all text-sm flex items-center gap-2">
                <span class="material-icons text-sm">save</span> Perbarui PO
            </button>
            <a href="{{ route('purchase_orders.show', $purchaseOrder->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-6 rounded-xl transition-all text-sm flex items-center gap-2">
                Batal
            </a>
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
                        style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; font-weight: 500; color: #334155; outline: none; transition: all 0.15s;"
                        onfocus="this.style.borderColor='#e11d48';this.style.background='white'"
                        onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"
                        oninput="matSearch(${r}, this)"
                        onkeydown="matKeydown(${r}, this, event)"
                        onblur="matHide(${r})">
                    <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}" value="${mid ?? ''}" required>
                    <ul id="mat-list-${r}" style="display:none; position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; max-height: 200px; overflow-y: auto; z-index: 50; margin-top: 2px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); list-style: none; padding: 0;"></ul>
                </td>
                <td><input type="number" name="items[${r}][quantity]" class="qty" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; text-align: right; font-weight: 500; color: #334155; outline: none; transition: all 0.15s;" min="0.001" step="0.001" value="${qty}" onchange="calcRow(this)" required onfocus="this.style.borderColor='#e11d48';this.style.background='white'" onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"></td>
                <td><input type="number" name="items[${r}][unit_price]" class="price" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; text-align: right; font-weight: 500; color: #334155; outline: none; transition: all 0.15s;" min="0" step="0.01" value="${price}" onchange="calcRow(this)" required onfocus="this.style.borderColor='#e11d48';this.style.background='white'" onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"></td>
                <td style="text-align: right; font-weight: 700;" class="row-total">${(qty*price).toLocaleString('id-ID', {minimumFractionDigits: 0})}</td>
                <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove();calcGrand()" style="background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; padding: 6px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#ef4444';this.style.color='white'" onmouseout="this.style.background='#fef2f2';this.style.color='#ef4444'"><span class="material-icons" style="font-size: 18px;">delete</span></button></td>
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
