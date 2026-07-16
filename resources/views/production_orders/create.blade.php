@extends('layouts.app')

@section('title', 'Buat Production Order')

@section('content')
<div class="space-y-6">

    {{-- Error Alert --}}
    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-start gap-3 border border-red-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <div>
            <span class="text-sm font-bold">Terdapat kesalahan:</span>
            <ul class="text-xs font-medium mt-1 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Buat Production Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Buat satu atau beberapa Production Order secara batch</p>
            </div>
        </div>
    </div>

    <form action="{{ route('production_orders.store') }}" method="POST" id="po-form" class="space-y-5">
        @csrf

        {{-- Section 1: Parameter Rencana Produksi --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Parameter Rencana Produksi
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Tgl Mulai Rencana *</label>
                        <input type="date" name="planned_start_date" required value="{{ old('planned_start_date', date('Y-m-d')) }}"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Tgl Selesai Rencana *</label>
                        <input type="date" name="planned_end_date" required value="{{ old('planned_end_date', date('Y-m-d')) }}"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Catatan Umum (Opsional)</label>
                    <textarea name="general_notes" rows="2" placeholder="Masukkan catatan umum untuk semua order dalam batch ini..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all placeholder:text-slate-400">{{ old('general_notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Section 2: Daftar Item Production Order --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Daftar Production Order
                </h2>
                <button type="button" onclick="addRow()"
                    class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-3 rounded-xl transition-all text-xs flex items-center gap-1.5 shadow-sm shadow-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Tambah Baris
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-50 border-y border-slate-200">
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[22%]">No. Order *</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[38%]">Material *</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[15%] text-right">Qty Planned *</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[20%]">Catatan Item</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[5%]"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="divide-y divide-slate-100 bg-white">
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100">
                <p class="text-[11px] font-medium text-slate-400">* Komponen BOM otomatis dibuat saat Production Order disimpan.</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4">
            <button type="submit"
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm flex items-center gap-2 shadow-lg shadow-red-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                Simpan Semua
            </button>
            <a href="{{ route('production_orders.index') }}"
                class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Batal
            </a>
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
                onmousedown="pickMat(event, ${r})" style="padding:8px 12px;cursor:pointer;font-size:12px;border-bottom:1px solid #f1f5f9;">
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
        tr.className = 'hover:bg-slate-50 transition-colors';
        tr.innerHTML = `
            <td class="py-3 px-4">
                <input type="text" name="orders[${r}][order_number]" placeholder="Nomor PO/Order..." required
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-mono font-bold text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all placeholder:text-slate-400">
            </td>
            <td class="py-3 px-4 relative" style="overflow:visible;">
                <input type="text" id="mat-text-${r}" placeholder="Ketik kode / nama..." autocomplete="off" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all placeholder:text-slate-400"
                    oninput="matSearch(${r}, this)" onkeydown="matKeydown(${r}, this, event)" onblur="hideMat(${r})">
                <input type="hidden" name="orders[${r}][material_id]" id="mat-id-${r}" required>
                <ul id="mat-list-${r}" style="display:none;position:absolute;top:100%;left:16px;right:16px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:160px;overflow-y:auto;z-index:999;list-style:none;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.1);"></ul>
            </td>
            <td class="py-3 px-4">
                <input type="number" name="orders[${r}][quantity_planned]" value="1" min="0.001" step="0.001" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-bold text-slate-700 text-right outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all">
            </td>
            <td class="py-3 px-4">
                <input type="text" name="orders[${r}][notes]" placeholder="opsional"
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-xs font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition-all placeholder:text-slate-400">
            </td>
            <td class="py-3 px-4 text-center">
                <button type="button" onclick="document.getElementById('row-${r}').remove()"
                    class="p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        `;
        document.getElementById('items-body').appendChild(tr);
    }

    // Start with one empty row
    addRow();
</script>
@endpush
