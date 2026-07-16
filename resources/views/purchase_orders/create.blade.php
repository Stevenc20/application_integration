@extends('layouts.app')

@section('title', 'Buat Purchase Order')

@section('content')
<div class="space-y-6">
    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl text-white/80">add_shopping_cart</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Buat Purchase Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Buat Purchase Order baru dengan mengisikan vendor, gudang, dan item material secara manual atau import Excel</p>
            </div>
        </div>
        <a href="{{ route('purchase_orders.index') }}" class="relative bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg whitespace-nowrap">
            <span class="material-icons text-sm">arrow_back</span>
            Kembali ke Daftar
        </a>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ $errors->first() }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('purchase_orders.store') }}" id="po-form">
        @csrf

        {{-- Informasi Utama PO --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                    <span class="material-icons text-rose-600">info</span> Informasi Utama PO
                </h3>
            </div>
            <div class="p-6 space-y-6">
                {{-- Gudang --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Lokasi Gudang <span class="text-rose-500">*</span></label>
                        <select name="storage_location_id" id="location-select" required onchange="onLocationChange(this)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all cursor-pointer appearance-none">
                            <option value="">-- Pilih Lokasi Gudang --</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                                {{ old('storage_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->code }} - {{ $loc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end pb-1">
                        <p id="location-hint" class="text-xs text-slate-400 italic">Pilih lokasi gudang terlebih dahulu untuk menampilkan material yang sesuai.</p>
                    </div>
                </div>

                {{-- Vendor & Tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Vendor <span class="text-rose-500">*</span></label>
                        <input type="text" id="vendor-search"
                               value="{{ old('vendor_id') ? ($vendors->firstWhere('id', old('vendor_id'))?->kode.' - '.$vendors->firstWhere('id', old('vendor_id'))?->nama) : '' }}"
                               placeholder="Ketik kode atau nama vendor..."
                               autocomplete="off"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400"
                               oninput="vendorSearch(this)"
                               onkeydown="vendorKeydown(event)">
                        <input type="hidden" name="vendor_id" id="vendor-id-hidden" value="{{ old('vendor_id') }}">
                        <ul id="vendor-suggestions" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; max-height: 200px; overflow-y: auto; z-index: 50; margin-top: 2px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); list-style: none; padding: 0;"></ul>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal Order <span class="text-rose-500">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Est. Pengiriman</label>
                            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Catatan tambahan... (opsional)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Import Panel --}}
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-sm text-blue-800 flex items-center gap-2">
                    <span class="material-icons text-lg">file_upload</span> Import Item dari Excel
                </h3>
                <a href="{{ route('purchase_orders.import-template') }}" class="bg-white hover:bg-blue-100 text-blue-700 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-blue-200">
                    <span class="material-icons text-sm">download</span> Download Template
                </a>
            </div>
            <p class="text-xs text-blue-600 leading-relaxed">
                Download template, isi Kode Material + Qty + Harga Satuan, lalu upload. Item akan otomatis masuk ke tabel di bawah.<br>
                <strong class="font-bold">Penting:</strong> Lokasi gudang harus dipilih terlebih dahulu agar filter material sesuai.
            </p>
            <div class="flex items-center gap-3 flex-wrap">
                <input type="file" id="import-file" accept=".xlsx,.xls" class="text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 file:cursor-pointer">
                <button type="button" id="import-btn" onclick="doImport()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-sm flex items-center gap-1.5">
                    <span class="material-icons text-sm">upload</span> Upload & Import
                </button>
            </div>
            <div id="import-result" style="display: none;"></div>
        </div>

        {{-- Item PO --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-black text-lg text-slate-800 flex items-center gap-2">
                        <span class="material-icons text-rose-600">list</span> Item PO
                    </h3>
                    <button type="button" onclick="addItem()" id="add-item-btn" disabled class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-4 rounded-xl transition-all text-sm border border-emerald-200 flex items-center gap-1">
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
                <span class="material-icons text-sm">save</span> Simpan PO
            </button>
            <a href="{{ route('purchase_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-3 px-6 rounded-xl transition-all text-sm flex items-center gap-2">
                Batal
            </a>
        </div>
    </form>
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
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all"
                        oninput="matSearch(${rowIndex}, this)"
                        onkeydown="matKeydown(${rowIndex}, this, event)"
                        onblur="matHide(${rowIndex})">
                    <input type="hidden" name="items[${rowIndex}][material_id]" id="mat-id-${rowIndex}" required>
                    <ul id="mat-list-${rowIndex}" style="display:none; position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; max-height: 200px; overflow-y: auto; z-index: 50; margin-top: 2px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); list-style: none; padding: 0;"></ul>
                </td>
                <td><input type="number" name="items[${rowIndex}][quantity]" class="qty" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; text-align: right; font-weight: 500; color: #334155; outline: none; transition: all 0.15s;" min="0.001" step="0.001" value="1" onchange="calcRow(this)" required onfocus="this.style.borderColor='#e11d48';this.style.background='white'" onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"></td>
                <td><input type="number" name="items[${rowIndex}][unit_price]" class="price" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; font-size: 13px; text-align: right; font-weight: 500; color: #334155; outline: none; transition: all 0.15s;" min="0" step="0.01" value="0" onchange="calcRow(this)" required onfocus="this.style.borderColor='#e11d48';this.style.background='white'" onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"></td>
                <td style="text-align: right; font-weight: 700;" class="row-total">0</td>
                <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove();calcGrand()" style="background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; padding: 6px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#ef4444';this.style.color='white'" onmouseout="this.style.background='#fef2f2';this.style.color='#ef4444'"><span class="material-icons" style="font-size: 18px;">delete</span></button></td>
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
