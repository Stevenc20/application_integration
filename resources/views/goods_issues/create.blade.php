@extends('layouts.app')

@section('title', 'Buat Goods Issue')

@push('styles')
<style>
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
    <div class="bg-white border-b border-slate-200 px-7 py-4 flex items-center justify-between">
        <h2 class="text-lg font-black text-slate-800">Buat Goods Issue</h2>
    </div>

    <div class="p-6 md:p-8 bg-slate-50/50 min-h-[calc(100vh-70px)]">
        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
            <ul class="text-red-600 text-xs font-semibold space-y-1 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 text-red-600 text-xs font-bold">
            {{ session('error') }}
        </div>
        @endif

        <form action="{{ route('goods_issues.store') }}" method="POST" id="gi-form">
            @csrf

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="text-sm font-black text-slate-700 pb-3 mb-5 border-b border-slate-100">
                    Buat Goods Issue
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Issue <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_issue" required value="{{ old('tanggal_issue', date('Y-m-d')) }}" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Dari Storage Location <span class="text-red-500">*</span></label>
                        <select name="storage_location_id" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('storage_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->kode }} — {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tipe Issue <span class="text-red-500">*</span></label>
                        <select name="tipe_issue" id="tipe-issue-select" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="Pemakaian Internal" {{ old('tipe_issue') == 'Pemakaian Internal' ? 'selected' : '' }}>Pemakaian Internal</option>
                            <option value="Kirim ke Vendor" {{ old('tipe_issue') == 'Kirim ke Vendor' ? 'selected' : '' }}>Kirim ke Vendor</option>
                            <option value="Kirim ke Customer" {{ old('tipe_issue') == 'Kirim ke Customer' ? 'selected' : '' }}>Kirim ke Customer</option>
                        </select>
                        <span id="tipe-issue-help" class="text-[10px] text-slate-400 mt-1">Stok dikeluarkan untuk konsumsi produksi internal.</span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Lokasi Tujuan (opsional)</label>
                        <select name="dest_location" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="">-- Pilih Lokasi Tujuan --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->kode }} — {{ $loc->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 mb-5">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Keterangan</label>
                    <textarea name="keterangan" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 resize-vertical" placeholder="Masukkan keterangan tambahan jika ada..." rows="2">{{ old('keterangan') }}</textarea>
                </div>

                {{-- Items Section --}}
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 mt-5">
                    <div class="text-xs font-black text-slate-700 pb-2 mb-3 border-b border-slate-200 flex justify-between items-center">
                        <span>Material yang Dikeluarkan</span>
                        <button type="button" onclick="addRow()" class="bg-slate-700 hover:bg-slate-800 text-white rounded-lg px-3 py-1 text-[10px] font-bold transition-colors inline-flex items-center gap-1">
                            <span class="material-icons text-xs">add</span> + Tambah Baris
                        </button>
                    </div>
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="px-3 py-2 text-left font-bold text-slate-500 w-[55%]">Material</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-500 w-[15%]">Qty</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-500 w-[25%]">Note / ID Packing</th>
                                <th class="px-3 py-2 w-[5%]"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-200">
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-100">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-colors">Post Goods Issue</button>
                    <a href="{{ route('goods_issues.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-5 py-2.5 rounded-xl text-xs font-bold transition-colors border border-slate-200">Batal</a>
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
            <td class="px-3 py-2 relative">
                <input type="text" id="mat-text-${r}" placeholder="Ketik kode atau nama material..." autocomplete="off"
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400"
                    oninput="matSearch(${r}, this)" onkeydown="matKeydown(${r}, this, event)" onblur="hideMat(${r})" required>
                <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}" required>
                <ul id="mat-list-${r}" class="suggestions-list"></ul>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${r}][qty]" value="1" min="0.001" step="0.001" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" required>
            </td>
            <td class="px-3 py-2">
                <input type="text" name="items[${r}][note]" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" placeholder="Contoh: PKG-0042">
            </td>
            <td class="px-3 py-2 text-center align-middle">
                <button type="button" onclick="document.getElementById('row-${r}').remove()" class="text-red-500 hover:bg-red-50 p-1.5 rounded-lg transition-colors inline-flex items-center justify-center">
                    <span class="material-icons text-sm">close</span>
                </button>
            </td>
        `;
        document.getElementById('items-body').appendChild(tr);
    }

    addRow();

    const tipeIssueHelp = {
        'Pemakaian Internal': 'Stok dikeluarkan untuk konsumsi produksi internal.',
        'Kirim ke Vendor': 'Stok dikeluarkan untuk pengiriman ke Subkontraktor/Vendor.',
        'Kirim ke Customer': 'Stok dikeluarkan untuk pengiriman ke Customer.'
    };

    const selectEl = document.getElementById('tipe-issue-select');
    const helpEl = document.getElementById('tipe-issue-help');

    if (selectEl && helpEl) {
        helpEl.textContent = tipeIssueHelp[selectEl.value] || tipeIssueHelp['Pemakaian Internal'];

        selectEl.addEventListener('change', function() {
            helpEl.textContent = tipeIssueHelp[this.value] || '';
        });
    }
</script>
@endpush
