@extends('layouts.app')

@section('title', 'Buat BOM')

@section('content')
<div class="space-y-6">

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Buat BOM</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Buat Bill of Materials Baru untuk Produksi</p>
            </div>
        </div>
        <a href="{{ route('boms.index') }}" class="relative bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- Error Alert --}}
    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-start gap-3 border border-red-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <div class="text-sm font-semibold">
            <p class="mb-1">Terdapat kesalahan pengisian form:</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Main Form Card --}}
    <form action="{{ route('boms.store') }}" method="POST" id="bom-form">
        @csrf
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-visible flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-black text-lg text-slate-800">Informasi BOM Utama</h3>
            </div>
            
            <div class="p-6 space-y-6">
                {{-- Row 1 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Material Hasil (FP/WIP) <span class="text-rose-500">*</span></label>
                        <input type="text" id="fp-search"
                               value="{{ old('material_id') ? ($materials->firstWhere('id', old('material_id'))?->kode.' - '.$materials->firstWhere('id', old('material_id'))?->nama) : '' }}"
                               placeholder="Ketik kode atau nama material..."
                               autocomplete="off"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400"
                               oninput="fpSearch(this)"
                               onkeydown="fpKeydown(event)"
                               required>
                        <input type="hidden" name="material_id" id="fp-id" value="{{ old('material_id') }}">
                        
                        {{-- Suggestions Dropdown --}}
                        <div id="fp-suggestions" class="absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto hidden mt-1 overflow-hidden divide-y divide-slate-100"></div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Qty Base <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="base_quantity" value="{{ old('base_quantity', 1) }}" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400" 
                                   min="0.001" step="0.001" required>
                        </div>
                    </div>
                </div>

                {{-- Row 2 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Berlaku Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" name="valid_from" value="{{ old('valid_from', date('Y-m-d')) }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Berlaku Hingga</label>
                        <input type="date" name="valid_to" value="{{ old('valid_to') }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                    </div>
                </div>

                {{-- Row 3 --}}
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Deskripsi BOM</label>
                        <input type="text" name="description" value="{{ old('description') }}" 
                               placeholder="Masukkan deskripsi BOM..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400">
                    </div>
                </div>

                {{-- Row 4 Checkbox --}}
                <div>
                    <label class="flex items-center gap-3 cursor-pointer group w-max">
                        <div class="relative flex items-center">
                            <input type="checkbox" id="status_aktif" name="status" value="active" checked 
                                   class="peer w-5 h-5 cursor-pointer appearance-none rounded border-2 border-slate-300 checked:bg-emerald-500 checked:border-emerald-500 transition-all hover:border-emerald-400">
                            <svg class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-slate-700 group-hover:text-emerald-600 transition-colors">BOM Aktif</span>
                    </label>
                </div>
            </div>

            {{-- Komponen BOM Header --}}
            <div class="px-6 py-5 border-y border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="font-black text-lg text-slate-800">Daftar Komponen</h3>
                <button type="button" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5 shadow-md shadow-emerald-200 whitespace-nowrap" onclick="addRow()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Tambah Komponen
                </button>
            </div>

            <div class="overflow-x-auto min-h-[300px]">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200">
                            <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest w-[45%]">MATERIAL KOMPONEN</th>
                            <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center w-[15%]">QTY</th>
                            <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center w-[15%]">UoM</th>
                            <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest w-[20%]">CATATAN</th>
                            <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center w-[5%]"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="divide-y divide-slate-100 bg-white">
                        {{-- Rows will be added dynamically --}}
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-5 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('boms.index') }}" class="bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-sm">Batal</a>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-md shadow-rose-200">Simpan BOM</button>
            </div>
        </div>
    </form>
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
        if (!q) { 
            box.classList.add('hidden'); 
            return; 
        }
        const matches = materials.filter(m =>
            m.kode.toLowerCase().includes(q) || m.nama.toLowerCase().includes(q)
        ).slice(0, 20);
        
        if (!matches.length) { 
            box.classList.add('hidden'); 
            return; 
        }
        
        box.innerHTML = matches.map(m =>
            `<div class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition-colors flex items-center justify-between group" data-id="${m.id}" data-label="${m.kode} - ${m.nama}" data-uom="${m.uom ?? ''}">
                <div class="flex items-center gap-3">
                    <span class="font-mono font-bold text-blue-600">${m.kode}</span>
                    <span class="font-medium text-slate-700 group-hover:text-blue-900">${m.nama}</span>
                </div>
                <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-700">${m.tipe}</span>
            </div>`
        ).join('');
        box.classList.remove('hidden');
    }

    document.getElementById('fp-suggestions').addEventListener('click', function(e) {
        const item = e.target.closest('[data-id]');
        if (!item) return;
        document.getElementById('fp-search').value = item.dataset.label;
        document.getElementById('fp-id').value = item.dataset.id;
        this.classList.add('hidden');
    });

    function fpKeydown(e) {
        const box = document.getElementById('fp-suggestions');
        if (box.classList.contains('hidden')) return;
        const inp = document.getElementById('fp-search');
        const items = box.querySelectorAll('[data-id]');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
            items.forEach((el, i) => {
                if (i === inp._activeIdx) {
                    el.classList.add('bg-blue-50');
                } else {
                    el.classList.remove('bg-blue-50');
                }
            });
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
            items.forEach((el, i) => {
                if (i === inp._activeIdx) {
                    el.classList.add('bg-blue-50');
                } else {
                    el.classList.remove('bg-blue-50');
                }
            });
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                const el = items[inp._activeIdx];
                inp.value = el.dataset.label;
                document.getElementById('fp-id').value = el.dataset.id;
                box.classList.add('hidden');
            }
        } else if (e.key === 'Escape') {
            box.classList.add('hidden');
        }
    }

    // Component row autocomplete
    function addRow(mid=null, qty=1, uom='', label='', notes='') {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 transition-colors group";
        tr.innerHTML = `
            <td class="py-4 px-6 relative">
                <input type="text" id="comp-search-${r}"
                       value="${label}"
                       placeholder="Ketik kode atau nama..."
                       autocomplete="off"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400"
                       oninput="compSearch(${r}, this)"
                       onkeydown="compKeydown(${r}, event)">
                <input type="hidden" name="items[${r}][material_id]" id="comp-id-${r}" value="${mid ?? ''}">
                <div id="comp-sug-${r}" class="absolute z-50 w-full min-w-[320px] bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto hidden mt-1 overflow-hidden divide-y divide-slate-100 left-6"></div>
            </td>
            <td class="py-4 px-6">
                <input type="number" name="items[${r}][quantity]" value="${qty}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-black text-emerald-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all text-center" min="0.001" step="0.001" required>
            </td>
            <td class="py-4 px-6">
                <input type="text" name="items[${r}][unit]" id="comp-uom-${r}" value="${uom}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all text-center placeholder-slate-400" placeholder="PCS">
            </td>
            <td class="py-4 px-6">
                <input type="text" name="items[${r}][notes]" value="${notes}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400" placeholder="Opsional">
            </td>
            <td class="py-4 px-6 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors opacity-0 group-hover:opacity-100" title="Hapus Baris">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
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
            this.classList.add('hidden');
        });
        r++;
    }

    function compSearch(idx, input) {
        input._activeIdx = -1;
        const q = input.value.trim().toLowerCase();
        const box = document.getElementById(`comp-sug-${idx}`);
        document.getElementById(`comp-id-${idx}`).value = '';
        if (!q) { box.classList.add('hidden'); return; }
        const matches = materials.filter(m =>
            m.kode.toLowerCase().includes(q) || m.nama.toLowerCase().includes(q)
        ).slice(0, 20);
        if (!matches.length) { box.classList.add('hidden'); return; }
        box.innerHTML = matches.map(m =>
            `<div class="px-3 py-2 cursor-pointer hover:bg-blue-50 transition-colors flex items-center justify-between group" data-id="${m.id}" data-row="${idx}" data-label="${m.kode} - ${m.nama}" data-uom="${m.uom ?? ''}">
                <div class="flex items-center gap-2">
                    <span class="font-mono font-bold text-blue-600 text-xs">${m.kode}</span>
                    <span class="font-medium text-slate-700 text-xs group-hover:text-blue-900">${m.nama}</span>
                </div>
                <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-700">${m.uom ?? '-'}</span>
            </div>`
        ).join('');
        box.classList.remove('hidden');
    }

    function compKeydown(idx, e) {
        const box = document.getElementById(`comp-sug-${idx}`);
        if (!box || box.classList.contains('hidden')) return;
        const inp = document.getElementById(`comp-search-${idx}`);
        const items = box.querySelectorAll('[data-id]');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
            items.forEach((el, i) => {
                if (i === inp._activeIdx) {
                    el.classList.add('bg-blue-50');
                } else {
                    el.classList.remove('bg-blue-50');
                }
            });
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
            items.forEach((el, i) => {
                if (i === inp._activeIdx) {
                    el.classList.add('bg-blue-50');
                } else {
                    el.classList.remove('bg-blue-50');
                }
            });
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                const el = items[inp._activeIdx];
                inp.value = el.dataset.label;
                document.getElementById(`comp-id-${idx}`).value = el.dataset.id;
                document.getElementById(`comp-uom-${idx}`).value = el.dataset.uom || '';
                box.classList.add('hidden');
            }
        } else if (e.key === 'Escape') {
            box.classList.add('hidden');
        }
    }

    // Close all dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#fp-search') && !e.target.closest('#fp-suggestions')) {
            document.getElementById('fp-suggestions').classList.add('hidden');
        }
        if (!e.target.closest('[id^="comp-search-"]') && !e.target.closest('[id^="comp-sug-"]')) {
            document.querySelectorAll('[id^="comp-sug-"]').forEach(b => b.classList.add('hidden'));
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
