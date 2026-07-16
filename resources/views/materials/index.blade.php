@extends('layouts.app')

@section('title', 'Master Material')

@section('content')
<div class="space-y-6">

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ $errors->first() }}</span>
    </div>
    @endif

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
                <h1 class="text-2xl font-black text-white tracking-tight">Master Material</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Kelola data master material, UoM, stok minimum, dan kuantitas per case</p>
            </div>
        </div>
        
        <div class="relative flex gap-3 flex-wrap">
            <button onclick="openMaterialModal('importModal')" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-4 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Import Excel
            </button>
            <a href="{{ route('materials.create') }}" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-xl border border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Material
            </a>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                <form action="{{ route('materials.index') }}" method="GET" class="flex items-center gap-2 w-full lg:w-auto flex-wrap">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[240px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode / nama..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400">
                    </div>
                    
                    <select name="tipe" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer" onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="WIP" {{ $tipe === 'WIP' ? 'selected' : '' }}>WIP (Work in Progress)</option>
                        <option value="FP" {{ $tipe === 'FP' ? 'selected' : '' }}>FP (Finished Product)</option>
                        <option value="RM" {{ $tipe === 'RM' ? 'selected' : '' }}>RM (Raw Material)</option>
                    </select>

                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                    @if($search || $tipe)
                        <a href="{{ route('materials.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                    @endif
                </form>

                <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
                    <a href="{{ route('materials.template') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        Template
                    </a>
                    <a href="{{ route('materials.export', ['search' => $search, 'tipe' => $tipe]) }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Excel
                    </a>
                    <a href="{{ route('materials.print_pdf', ['search' => $search, 'tipe' => $tipe]) }}" target="_blank" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-rose-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-12 text-center">#</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">KODE</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">NAMA</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">TIPE</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">UOM</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">QTY/CASE</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">MIN STOK</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">STOK</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">STATUS</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($materials as $index => $material)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs font-medium text-slate-400 text-center">{{ $materials->firstItem() + $index }}</td>
                        <td class="py-3 px-4 text-xs font-black text-blue-600 font-mono text-center">
                            <a href="{{ route('materials.show', $material->id) }}" class="hover:underline hover:text-rose-600 transition-colors">{{ $material->kode }}</a>
                        </td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-800">{{ $material->nama }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($material->tipe === 'WIP')
                                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">WIP</span>
                            @elseif($material->tipe === 'FP')
                                <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">FP</span>
                            @elseif($material->tipe === 'RM')
                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">RM</span>
                            @else
                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">{{ $material->tipe }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-center">{{ $material->uom }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-700 text-center">{{ number_format($material->qty_case, 0, ',', '.') }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-700 text-center">{{ number_format($material->min_stok, 0, ',', '.') }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="text-sm font-black {{ $material->stok < $material->min_stok ? 'text-rose-600' : 'text-slate-800' }}">{{ number_format($material->stok, 0, ',', '.') }}</span>
                            @if($material->stok < $material->min_stok)
                                <span class="bg-rose-50 border border-rose-200 text-rose-600 px-1.5 py-0.5 rounded ml-1 text-[9px] font-bold inline-flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    Minim
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $material->status === 'Aktif' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                {{ $material->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('materials.show', $material->id) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors" title="Detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                <a href="{{ route('materials.edit', $material->id) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-colors" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </a>
                                <button type="button" data-id="{{ $material->id }}" data-nama="{{ $material->nama }}" onclick="openMaterialDeleteModal(this)" class="p-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-slate-500 font-medium">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                Tidak ada data material ditemukan.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($materials->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $materials->firstItem() ?? 0 }}-{{ $materials->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $materials->total() }}</span> material
            </div>
            <div>
                {{ $materials->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- MODAL: TAMBAH MATERIAL --}}
<div id="addModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9000] hidden items-center justify-center transition-opacity" style="opacity: 0;">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden transform scale-95 transition-transform" id="addModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Tambah Material Baru
            </h3>
            <button onclick="closeMaterialModal('addModal')" class="text-slate-400 hover:text-rose-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
        </div>
        
        <form action="{{ route('materials.store') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Kode Material <span class="text-rose-500">*</span></label>
                    <input type="text" name="kode" placeholder="Contoh: ISF PH068" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400 cursor-pointer">
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Nama Material <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" placeholder="Contoh: PH-068" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Tipe Material <span class="text-rose-500">*</span></label>
                    <select name="tipe" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400 cursor-pointer">
                        <option value="WIP">WIP (Work in Progress)</option>
                        <option value="FP">FP (Finished Product)</option>
                        <option value="RM">RM (Raw Material)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">UoM (Unit) <span class="text-rose-500">*</span></label>
                    <input type="text" name="uom" placeholder="PCS / SHT / COIL" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Qty/Case <span class="text-rose-500">*</span></label>
                    <input type="number" name="qty_case" value="0" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Min Stok <span class="text-rose-500">*</span></label>
                    <input type="number" name="min_stok" value="0" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Stok Awal <span class="text-rose-500">*</span></label>
                <input type="number" name="stok" value="0" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeMaterialModal('addModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm">Batal</button>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm shadow-md shadow-rose-200">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: EDIT MATERIAL --}}
<div id="editModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9000] hidden items-center justify-center transition-opacity" style="opacity: 0;">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden transform scale-95 transition-transform" id="editModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit Material
            </h3>
            <button onclick="closeMaterialModal('editModal')" class="text-slate-400 hover:text-amber-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
        </div>
        
        <form action="{{ route('materials.update') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Kode Material <span class="text-rose-500">*</span></label>
                    <input type="text" name="kode" id="edit_kode" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Status <span class="text-rose-500">*</span></label>
                    <select name="status" id="edit_status" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400 cursor-pointer">
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Nama Material <span class="text-rose-500">*</span></label>
                <input type="text" name="nama" id="edit_nama" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Tipe Material <span class="text-rose-500">*</span></label>
                    <select name="tipe" id="edit_tipe" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400 cursor-pointer">
                        <option value="WIP">WIP (Work in Progress)</option>
                        <option value="FP">FP (Finished Product)</option>
                        <option value="RM">RM (Raw Material)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">UoM (Unit) <span class="text-rose-500">*</span></label>
                    <input type="text" name="uom" id="edit_uom" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Qty/Case <span class="text-rose-500">*</span></label>
                    <input type="number" name="qty_case" id="edit_qty_case" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Min Stok <span class="text-rose-500">*</span></label>
                    <input type="number" name="min_stok" id="edit_min_stok" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Stok Saat Ini <span class="text-rose-500">*</span></label>
                <input type="number" name="stok" id="edit_stok" min="0" required class="w-full text-sm border border-slate-200 rounded-xl p-2.5 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeMaterialModal('editModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm">Batal</button>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm shadow-md shadow-amber-200">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: IMPORT EXCEL --}}
<div id="importModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9000] hidden items-center justify-center transition-opacity" style="opacity: 0;">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 transition-transform" id="importModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Import Excel Material
            </h3>
            <button onclick="closeMaterialModal('importModal')" class="text-slate-400 hover:text-rose-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
        </div>
        
        <form action="{{ route('materials.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500 mb-2">Upload template Excel yang telah diisi data master material.</p>
                <a href="{{ route('materials.template') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download Template
                </a>
            </div>
            
            <div class="mb-6">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Pilih File Excel (.xlsx, .xls) <span class="text-rose-500">*</span></label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-600 hover:file:bg-rose-100 border border-slate-200 rounded-xl p-1 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeMaterialModal('importModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm">Batal</button>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm shadow-md shadow-rose-200">Upload & Import</button>
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
        // Small delay for transition
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

    function showEdit(material) {
        document.getElementById('edit_id').value = material.id;
        document.getElementById('edit_kode').value = material.kode;
        document.getElementById('edit_nama').value = material.nama;
        document.getElementById('edit_tipe').value = material.tipe;
        document.getElementById('edit_uom').value = material.uom;
        document.getElementById('edit_qty_case').value = material.qty_case;
        document.getElementById('edit_min_stok').value = material.min_stok;
        document.getElementById('edit_stok').value = material.stok;
        document.getElementById('edit_status').value = material.status;
        openMaterialModal('editModal');
    }

    function openMaterialDeleteModal(button) {
        const id = button.dataset.id;
        const nama = button.dataset.nama;
        document.getElementById('deleteMaterialName').textContent = nama;
        document.getElementById('deleteForm').action = '{{ route('materials.destroy', ':id') }}'.replace(':id', id);
        openMaterialModal('deleteModal');
    }

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.id === 'addModal' || event.target.id === 'editModal' || event.target.id === 'importModal' || event.target.id === 'deleteModal') {
            closeMaterialModal(event.target.id);
        }
    }
</script>
@endpush
