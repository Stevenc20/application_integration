@extends('layouts.app')

@section('title', 'Tambah Material')

@section('content')
<div class="space-y-6">

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Tambah Material</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Buat data master material baru (RM/WIP/FP)</p>
            </div>
        </div>
        <a href="{{ route('materials.index') }}" class="relative bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- Form Alert --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Main Form Card --}}
    <form action="{{ route('materials.store') }}" method="POST">
        @csrf
        <input type="hidden" name="status" value="Aktif">
        <input type="hidden" name="stok" value="0">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col max-w-4xl mx-auto">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-black text-lg text-slate-800">Informasi Material</h3>
            </div>
            
            <div class="p-6 space-y-6">
                {{-- Row 1 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kode Material <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-blue-600 font-mono outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400"
                               placeholder="Contoh: RM-001">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipe <span class="text-rose-500">*</span></label>
                        <select name="tipe" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all cursor-pointer appearance-none">
                            <option value="RM">RM - Bahan Baku</option>
                            <option value="WIP">WIP - Work In Progress</option>
                            <option value="FP">FP - Finished Product</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2 --}}
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Material <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all"
                               placeholder="Masukkan nama lengkap material...">
                    </div>
                </div>

                {{-- Row 3 --}}
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Deskripsi</label>
                        <textarea name="deskripsi" rows="3"
                                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all placeholder-slate-400"
                                  placeholder="Opsional, informasi tambahan..."></textarea>
                    </div>
                </div>

                {{-- Row 4 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unit of Measure (UoM) <span class="text-rose-500">*</span></label>
                        <input type="text" name="uom" value="PCS" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Harga Standard <span class="text-rose-500">*</span></label>
                        <input type="number" name="harga" value="0" min="0" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-black text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all text-right">
                    </div>
                </div>

                {{-- Row 5 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100 pb-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Qty per Case / Karton</label>
                        <input type="number" name="qty_case" value="0" min="0"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        <p class="text-[10px] text-slate-400 font-semibold mt-1.5 ml-1">Isi 0 jika tidak digunakan</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Minimal Stok (Alert)</label>
                        <input type="number" name="min_stok" value="0" min="0" step="0.01"
                               class="w-full bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-sm font-black text-rose-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all">
                        <p class="text-[10px] text-rose-400 font-semibold mt-1.5 ml-1">Sistem akan memberi alert jika stok di bawah batas ini</p>
                    </div>
                </div>

                {{-- Sub Section: Vendor --}}
                <div>
                    <h4 class="text-sm font-black text-slate-700 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        Metode Order & Vendor
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 border border-slate-200 rounded-2xl p-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Sistem Order <span class="text-rose-500">*</span></label>
                            <select name="sistem_order" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all cursor-pointer appearance-none">
                                <option value="MRP">MRP (Perencanaan Bulanan)</option>
                                <option value="SKM">SKM (Sistem Kanban Manual)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Vendor Planning</label>
                            <select name="vendor" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 transition-all cursor-pointer appearance-none">
                                <option value="">-- Pilih Vendor --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->kode }}">{{ $vendor->kode }} - {{ $vendor->nama }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400 font-semibold mt-1.5 ml-1">Wajib diisi jika metode SKM</p>
                        </div>
                        
                        <div class="md:col-span-2 mt-2">
                            <label class="flex items-center gap-3 cursor-pointer group w-max">
                                <div class="relative flex items-center">
                                    <input type="checkbox" id="diproses_vendor" name="diproses_vendor" 
                                           class="peer w-5 h-5 cursor-pointer appearance-none rounded border-2 border-slate-300 checked:bg-blue-500 checked:border-blue-500 transition-all hover:border-blue-400">
                                    <svg class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-slate-700 group-hover:text-blue-600 transition-colors">Diproses di Vendor (WIP / FP)</span>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <div class="px-6 py-5 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('materials.index') }}" class="bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-sm">Batal</a>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-md shadow-rose-200">Simpan Material</button>
            </div>
        </div>
    </form>
</div>
@endsection
