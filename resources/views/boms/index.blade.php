@extends('layouts.app')

@section('title', 'Bill of Materials')

@section('content')
<div class="space-y-6">

    {{-- Import Errors --}}
    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="bg-red-50 text-red-600 p-5 rounded-2xl border border-red-100 shadow-sm">
        <h4 class="font-bold flex items-center gap-2 mb-2 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Detail Masalah Saat Import Excel:
        </h4>
        <ul class="list-disc pl-6 text-xs space-y-1">
            @foreach(session('import_errors') as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Bill of Materials</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Daftar komposisi bahan baku (BOM) untuk proses produksi</p>
            </div>
        </div>
        
        <div class="relative flex gap-3 flex-wrap">
            <button onclick="openModal('importModal')" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-4 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Import Excel
            </button>
            <a href="{{ route('boms.create') }}" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-xl border border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Buat Manual
            </a>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                <form action="{{ route('boms.index') }}" method="GET" class="flex items-center gap-2 w-full lg:w-auto">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full max-w-[300px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. BOM / material..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400">
                    </div>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('boms.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                    @endif
                </form>

                <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
                    <a href="{{ route('boms.template') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        Template
                    </a>
                    <a href="{{ route('boms.export') }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Excel
                    </a>
                    <a href="{{ route('boms.print_pdf', ['search' => request('search')]) }}" target="_blank" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-rose-200">
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
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">NO. BOM</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">MATERIAL (HASIL)</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">BQ</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">MATERIAL ASAL (KOMPONEN)</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">STATUS</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($boms as $bom)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-4 text-xs font-medium text-slate-400 text-center">{{ $loop->iteration + ($boms->firstItem() - 1) }}</td>
                        <td class="py-4 px-4 text-xs font-black text-blue-600 font-mono">{{ $bom->bom_number }}</td>
                        <td class="py-4 px-4">
                            <div class="text-[10px] font-mono text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded inline-block mb-1">{{ $bom->material->kode ?? '' }}</div>
                            <div class="text-xs font-bold text-slate-800">{{ $bom->material->nama ?? '-' }}</div>
                        </td>
                        <td class="py-4 px-4 text-xs font-black text-slate-700 text-center">{{ (float) $bom->base_quantity }}</td>
                        <td class="py-4 px-4">
                            <div class="space-y-1.5">
                            @foreach($bom->items as $bi)
                                <div class="flex items-center gap-2 {{ !$loop->first ? 'pt-1.5 border-t border-slate-100' : '' }}">
                                    <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">{{ $bi->material?->kode }}</span>
                                    <span class="text-xs font-medium text-slate-700">{{ $bi->material?->nama }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 ml-auto bg-slate-100 px-2 py-0.5 rounded-full">{{ (float) $bi->quantity }} {{ $bi->unit }}</span>
                                </div>
                            @endforeach
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($bom->status === 'active')
                            <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider">Aktif</span>
                            @else
                            <span class="bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider">Nonaktif</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('boms.show', $bom->id) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors" title="Detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                <a href="{{ route('boms.edit', $bom->id) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-colors" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </a>
                                <form method="POST" action="{{ route('boms.destroy', $bom->id) }}" onsubmit="return confirm('Hapus BOM ini?')" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500 font-medium">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                Belum ada data Bill of Materials.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($boms->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $boms->firstItem() ?? 0 }}-{{ $boms->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $boms->total() }}</span>
            </div>
            <div>
                {{ $boms->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>

</div>

{{-- MODAL: IMPORT EXCEL --}}
<div id="importModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9000] hidden items-center justify-center transition-opacity" style="opacity: 0;">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden transform scale-95 transition-transform" id="importModalContent">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Import Excel BOM
            </h3>
            <button onclick="closeModal('importModal')" class="text-slate-400 hover:text-rose-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
        </div>
        
        <form action="{{ route('boms.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500 mb-2">Upload template Excel yang telah diisi data BOM.</p>
                <a href="{{ route('boms.template') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download Template
                </a>
            </div>
            
            <div class="mb-6">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Pilih File Excel (.xlsx, .xls) <span class="text-rose-500">*</span></label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-600 hover:file:bg-rose-100 border border-slate-200 rounded-xl p-1 outline-none transition-colors focus:border-rose-400 focus:ring-1 focus:ring-rose-400">
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('importModal')" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-5 rounded-xl transition-all text-sm">Batal</button>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm shadow-md shadow-rose-200">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(id) {
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
    
    function closeModal(id) {
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

    // Close modals on clicking overlay
    window.onclick = function(event) {
        if (event.target.id === 'importModal') {
            closeModal('importModal');
        }
    }
</script>
@endpush
