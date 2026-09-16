@extends('layouts.app')

@section('title', 'Edit Material')

@section('content')
<div class="space-y-6">

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-amber-700 via-orange-600 to-amber-500 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Edit Material: {{ $material->kode }}</h1>
                <p class="text-amber-100 text-sm font-semibold mt-1">Perbarui data master material</p>
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

    {{-- Error Alert --}}
    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-start gap-3 border border-red-100 shadow-sm max-w-4xl mx-auto">
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
    <form action="{{ route('materials.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $material->id }}">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col max-w-4xl mx-auto">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-black text-lg text-slate-800">Informasi Material</h3>
            </div>
            
            <div class="p-6 space-y-6">
                {{-- Row 1 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kode Material <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode" value="{{ old('kode', $material->kode) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-blue-600 font-mono outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all placeholder-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status <span class="text-rose-500">*</span></label>
                        <select name="status" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all cursor-pointer appearance-none">
                            <option value="Aktif" {{ old('status', $material->status) === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="Tidak Aktif" {{ old('status', $material->status) === 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2 --}}
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Material <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama', $material->nama) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all">
                    </div>
                </div>

                {{-- Row 3 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipe <span class="text-rose-500">*</span></label>
                        <select name="tipe" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all cursor-pointer appearance-none">
                            <option value="RM" {{ old('tipe', $material->tipe) === 'RM' ? 'selected' : '' }}>RM - Bahan Baku</option>
                            <option value="WIP" {{ old('tipe', $material->tipe) === 'WIP' ? 'selected' : '' }}>WIP - Work In Progress</option>
                            <option value="FP" {{ old('tipe', $material->tipe) === 'FP' ? 'selected' : '' }}>FP - Finished Product</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Unit of Measure (UoM) <span class="text-rose-500">*</span></label>
                        <input type="text" name="uom" value="{{ old('uom', $material->uom) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all uppercase">
                    </div>
                </div>

                {{-- Row 4 --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Qty per Case / Karton</label>
                        <input type="number" name="qty_case" value="{{ old('qty_case', $material->qty_case) }}" min="0"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Minimal Stok (Alert)</label>
                        <input type="number" name="min_stok" value="{{ old('min_stok', $material->min_stok) }}" min="0" step="0.01"
                               class="w-full bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm font-black text-amber-600 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all">
                    </div>
                </div>

                {{-- Row 5 --}}
                <div class="grid grid-cols-1 gap-6 border-b border-slate-100 pb-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Stok Saat Ini</label>
                        <input type="number" name="stok" value="{{ old('stok', $material->stok) }}" min="0" step="0.01" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-black text-slate-700 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all">
                        <p class="text-[10px] text-slate-400 font-semibold mt-1.5 ml-1">Perbarui jika ada perubahan manual</p>
                    </div>
                </div>

            </div>

            <div class="px-6 py-5 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 rounded-b-2xl">
                <a href="{{ route('materials.index') }}" class="bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-sm">Batal</a>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-md shadow-amber-200">Simpan Perubahan</button>
            </div>
        </div>
    </form>
</div>
@endsection
