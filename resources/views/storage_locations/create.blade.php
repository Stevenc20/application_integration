@extends('layouts.app')

@section('title', 'Tambah Storage Location')

@section('content')
<div class="space-y-6">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl">location_on</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Tambah Storage Location</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Buat lokasi penyimpanan baru</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden max-w-xl">
        <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-800">Tambah Storage Location</div>
        <div class="p-6">
            <form action="{{ route('storage_locations.store') }}" method="POST">
                @csrf

                <div class="flex gap-4 mb-4">
                    <div class="flex-1 flex flex-col gap-1.5">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Kode *</label>
                        <input type="text" name="kode" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full" required>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Nama *</label>
                        <input type="text" name="nama" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full" required>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Deskripsi</label>
                    <textarea name="deskripsi" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full resize-y min-h-[60px]"></textarea>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Tipe Material</label>
                    <select name="tipe_material" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full">
                        <option value="">Semua Tipe (RM / WIP / FP)</option>
                        <option value="RM">RM (Bahan Baku)</option>
                        <option value="WIP">WIP (Barang Setengah Jadi)</option>
                        <option value="FP">FP (Barang Jadi)</option>
                    </select>
                    <div class="text-[11px] text-slate-400 mt-1">Material baru hanya akan otomatis muncul di lokasi yang tipenya cocok (atau lokasi tanpa tipe).</div>
                </div>

                <div class="flex items-center gap-2 mt-4 mb-6">
                    <input type="hidden" name="is_scrap" value="0">
                    <input type="checkbox" id="is_scrap" name="is_scrap" value="1" class="w-4 h-4 rounded border-slate-300 text-slate-800 focus:ring-slate-500 cursor-pointer">
                    <label for="is_scrap" class="text-sm font-bold text-slate-700 cursor-pointer">Lokasi Scrap (stok di sini <span class="text-red-500">tidak</span> dihitung dalam MRP)</label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-7 py-2.5 transition-all text-sm">Simpan</button>
                    <a href="{{ route('storage_locations.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg px-7 py-2.5 transition-all text-sm inline-flex items-center">Batal</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
