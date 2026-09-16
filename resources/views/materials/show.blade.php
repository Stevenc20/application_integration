@extends('layouts.app')

@section('title', 'Detail Material')

@section('content')
<div class="space-y-6">

    {{-- Success Alert --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl flex items-center gap-3 border border-emerald-200 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <span class="text-sm font-bold">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-blue-800 via-indigo-700 to-blue-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Detail Material</h1>
                <p class="text-indigo-200 text-sm font-semibold mt-1">{{ $material->kode }} &mdash; {{ $material->nama }}</p>
            </div>
        </div>
        <div class="relative">
            @if($material->status === 'Aktif')
                <span class="bg-emerald-500/20 text-emerald-200 text-xs font-black px-4 py-1.5 rounded-full border border-emerald-400/30">Aktif</span>
            @else
                <span class="bg-slate-500/20 text-slate-300 text-xs font-black px-4 py-1.5 rounded-full border border-slate-400/30">Tidak Aktif</span>
            @endif
        </div>
    </div>

    {{-- Stock Alert --}}
    @if($material->stok < $material->min_stok)
    <div class="bg-rose-50 text-rose-700 p-4 rounded-xl flex items-start gap-3 border border-rose-200 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>
            <span class="text-sm font-bold">Stok Minim!</span>
            <p class="text-xs font-medium mt-1">Total stok {{ number_format($material->stok, 3, '.', '.') }} di bawah minimum {{ number_format($material->min_stok, 3, '.', '.') }} {{ $material->uom }}</p>
        </div>
    </div>
    @endif

    {{-- Detail Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Left: Detail Info --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Informasi Material
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kode Material</span>
                    <p class="text-lg font-black text-blue-700 mt-0.5">{{ $material->kode }}</p>
                </div>
                <div class="border-t border-slate-100 pt-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">Nama</span>
                        <span class="text-sm font-bold text-slate-800">{{ $material->nama }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">Tipe</span>
                        @php
                            $tipeClass = match($material->tipe) {
                                'WIP' => 'bg-amber-100 text-amber-700',
                                'FP' => 'bg-emerald-100 text-emerald-700',
                                'RM' => 'bg-blue-100 text-blue-700',
                                default => 'bg-slate-100 text-slate-600'
                            };
                        @endphp
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider {{ $tipeClass }}">{{ $material->tipe }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">UoM</span>
                        <span class="text-sm font-bold text-slate-800">{{ $material->uom }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">Harga Standar</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($material->standard_price ?? 0, 2, '.', ',') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">Qty / Case</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($material->qty_case, 3, '.', '.') }} {{ $material->uom }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">Min. Stok</span>
                        <span class="text-sm font-bold text-slate-800">{{ number_format($material->min_stok, 3, '.', '.') }} {{ $material->uom }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500">Stok Saat Ini</span>
                        <span class="text-sm font-bold {{ $material->stok < $material->min_stok ? 'text-rose-600' : 'text-slate-800' }}">{{ number_format($material->stok, 3, '.', '.') }} {{ $material->uom }}</span>
                    </div>
                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-500 block mb-1">Deskripsi</span>
                        <p class="text-sm font-medium text-slate-600">{{ $material->deskripsi ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center gap-2">
                <button type="button" onclick="openMaterialModal('editModal')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5 shadow-sm shadow-amber-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </button>
                <button type="button" data-id="{{ $material->id }}" data-nama="{{ $material->nama }}" onclick="openMaterialDeleteModal(this)" class="bg-rose-500 hover:bg-rose-600 text-white font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5 shadow-sm shadow-rose-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
                <a href="{{ route('materials.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-xs flex items-center gap-1.5 ml-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>
            </div>
        </div>

        {{-- Right: Tables --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Stock Per Location --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Stok Per Gudang
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-y border-slate-200">
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest">Storage Location</th>
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Qty Tersedia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($locations as $loc)
                                @php $qty = (float) $material->stocks->where('storage_location_id', $loc->id)->sum('qty'); @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-6 text-sm font-semibold text-slate-700">{{ $loc->nama }}</td>
                                    <td class="py-3 px-6 text-sm font-black text-right {{ $qty > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ number_format($qty, 3, '.', '.') }} {{ $material->uom }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-10 text-center text-sm font-medium text-slate-400">Tidak ada storage location terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Movement History --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-black text-slate-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        Riwayat Pergerakan Stok (10 Terakhir)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-y border-slate-200">
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest">Tanggal</th>
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest">Tipe</th>
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest">Referensi</th>
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Qty</th>
                                <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Stok Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($latestMovements as $move)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-6 text-xs font-semibold text-slate-500">{{ $move['tanggal'] }}</td>
                                    <td class="py-3 px-6">
                                        @if($move['tipe'] === 'GR')
                                            <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-0.5 rounded-md">GR</span>
                                        @else
                                            <span class="bg-rose-100 text-rose-700 text-[10px] font-black px-2 py-0.5 rounded-md">GI</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-6 text-xs font-bold text-blue-700">{{ $move['referensi'] }}</td>
                                    <td class="py-3 px-6 text-xs font-black text-slate-700 text-right">{{ number_format($move['qty'], 3, '.', '.') }}</td>
                                    <td class="py-3 px-6 text-xs font-black text-slate-700 text-right">{{ number_format($move['stok_akhir'], 3, '.', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-sm font-medium text-slate-400">Belum ada riwayat pergerakan stok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL: EDIT --}}
<div id="editModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9000] hidden items-center justify-center transition-opacity" style="opacity: 0;">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden transform scale-95 transition-transform" id="editModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Material
            </h3>
            <button onclick="closeMaterialModal('editModal')" class="text-slate-400 hover:text-amber-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
        <form action="{{ route('materials.update') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="id" value="{{ $material->id }}">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Kode Material <span class="text-rose-500">*</span></label>
                    <input type="text" name="kode" value="{{ $material->kode }}" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400 cursor-pointer">
                        <option value="Aktif" {{ $material->status === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="Tidak Aktif" {{ $material->status === 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Nama Material <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" value="{{ $material->nama }}" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Tipe <span class="text-rose-500">*</span></label>
                    <select name="tipe" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400 cursor-pointer">
                        <option value="WIP" {{ $material->tipe === 'WIP' ? 'selected' : '' }}>WIP</option>
                        <option value="FP" {{ $material->tipe === 'FP' ? 'selected' : '' }}>FP</option>
                        <option value="RM" {{ $material->tipe === 'RM' ? 'selected' : '' }}>RM</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">UoM <span class="text-rose-500">*</span></label>
                    <input type="text" name="uom" value="{{ $material->uom }}" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Qty/Case <span class="text-rose-500">*</span></label>
                    <input type="number" name="qty_case" value="{{ $material->qty_case }}" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Min Stok <span class="text-rose-500">*</span></label>
                    <input type="number" name="min_stok" value="{{ $material->min_stok }}" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Stok Saat Ini <span class="text-rose-500">*</span></label>
                <input type="number" name="stok" value="{{ $material->stok }}" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeMaterialModal('editModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm">Batal</button>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm shadow-md shadow-amber-200">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DELETE CONFIRMATION --}}
<div id="deleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9000] hidden items-center justify-center transition-opacity" style="opacity: 0;">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 transition-transform p-6 text-center" id="deleteModalContent">
        <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-lg font-black text-slate-800 mb-2">Hapus Material</h3>
        <p class="text-sm font-medium text-slate-500 mb-6">Apakah Anda yakin ingin menghapus material <span class="font-bold text-slate-700" id="deleteMaterialName"></span>?</p>
        <form id="deleteForm" method="POST" class="flex justify-center gap-3">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeMaterialModal('deleteModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-6 rounded-xl transition-all text-sm">Batal</button>
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-md shadow-rose-200">Ya, Hapus</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openMaterialModal(id) {
        const modal = document.getElementById(id);
        const content = document.getElementById(id + 'Content');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.style.opacity = '1';
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeMaterialModal(id) {
        const modal = document.getElementById(id);
        const content = document.getElementById(id + 'Content');
        modal.style.opacity = '0';
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }

    function openMaterialDeleteModal(button) {
        const id = button.dataset.id;
        const nama = button.dataset.nama;
        document.getElementById('deleteMaterialName').textContent = nama;
        document.getElementById('deleteForm').action = '{{ route('materials.destroy', ':id') }}'.replace(':id', id);
        openMaterialModal('deleteModal');
    }

    window.onclick = function(event) {
        if (event.target.id === 'editModal' || event.target.id === 'deleteModal') {
            closeMaterialModal(event.target.id);
        }
    }
</script>
@endpush
