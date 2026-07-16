@extends('layouts.app')

@section('title', 'MRP - Material Requirements Planning')

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

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">MRP - Material Requirements Planning</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Sistem Perencanaan Kebutuhan Material Terpadu</p>
            </div>
        </div>
    </div>

    <!-- CARD 1: DEMAND ORDER CUSTOMER -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-black text-lg text-slate-800">Demand Order Customer</h3>
                <p class="text-sm font-medium text-slate-500 mt-1">Import file Excel berisi daftar order FP/WIP dari customer. MRP akan mengeksplosi secara multi-level ke bahan baku (RM) via BOM.</p>
            </div>
            @if($demands->isNotEmpty())
            <form method="POST" action="{{ route('mrp.demands.clear') }}" onsubmit="return confirm('Hapus semua demand aktif?')">
                @csrf @method('DELETE')
                <button type="submit" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2 px-4 rounded-xl transition-all text-xs border border-rose-200 whitespace-nowrap">
                    Hapus Semua ({{ $demands->count() }})
                </button>
            </form>
            @endif
        </div>

        <div class="p-6 bg-slate-50/50">
            <div class="bg-sky-50 border border-sky-100 rounded-xl p-5 flex flex-col lg:flex-row gap-6 justify-between items-start lg:items-center">
                <div class="flex-1 w-full">
                    <span class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-3">Upload File Excel Demand (.xlsx / .xls)</span>
                    <form method="POST" action="{{ route('mrp.demands.import') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3 w-full">
                        @csrf
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required class="flex-1 text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-sky-100 file:text-sky-700 hover:file:bg-sky-200 border border-sky-200 bg-white rounded-xl p-1 outline-none transition-colors">
                        <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm shadow-md shadow-sky-200 whitespace-nowrap">Import</button>
                    </form>
                    <div class="text-[11px] font-medium text-sky-700 mt-3 bg-sky-100/50 p-2.5 rounded-lg border border-sky-100">
                        <strong>Format:</strong> Kolom A = Kode Material FP/WIP | Kolom B = Qty Order | Kolom C = Customer (opsional) | Kolom D = Notes (opsional). Baris 1 = header (dilewati otomatis).
                    </div>
                </div>
                <div class="lg:text-right border-t lg:border-t-0 lg:border-l border-sky-200 pt-5 lg:pt-0 lg:pl-6 w-full lg:w-auto">
                    <span class="block text-xs font-bold text-slate-500 mb-2">Belum punya template?</span>
                    <a href="{{ route('mrp.demands.template') }}" class="inline-flex items-center gap-1.5 bg-white border border-sky-200 text-sky-600 hover:bg-sky-50 font-bold py-2 px-4 rounded-xl transition-all text-xs shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Unduh Template
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-slate-100">
            @if($demands->isEmpty())
                <div class="py-12 text-center text-slate-500 font-medium">
                    <div class="flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Belum ada demand. Upload file Excel di atas.
                    </div>
                </div>
            @else
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-12 text-center">#</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">KODE</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">NAMA MATERIAL</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">TIPE</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">QTY ORDER</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">CUSTOMER</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">CATATAN</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($demands as $i => $d)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 text-xs font-medium text-slate-400 text-center">{{ $i + 1 }}</td>
                            <td class="py-3 px-4 text-xs font-black text-blue-600 font-mono">{{ $d->material->kode ?? '' }}</td>
                            <td class="py-3 px-4 text-xs font-bold text-slate-800">{{ $d->material->nama ?? '-' }}</td>
                            <td class="py-3 px-4 text-center">
                                @if(strtolower($d->material->tipe) == 'fp')
                                    <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-wider">FP</span>
                                @else
                                    <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-wider">WIP</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs font-black text-emerald-600 text-right">{{ number_format($d->order_quantity, 3) }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $d->customer_name ?? '-' }}</td>
                            <td class="py-3 px-4 text-[11px] font-medium text-slate-500 max-w-[200px] truncate">{{ $d->notes ?? '-' }}</td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('mrp.demands.destroy', $d->id) }}" onsubmit="return confirm('Hapus demand ini?')" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- CARD 2: JALANKAN MRP -->
    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl border border-indigo-100 shadow-sm overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6 p-6 md:p-8">
        <div class="flex-1">
            <h3 class="font-black text-xl text-indigo-900 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Jalankan MRP
            </h3>
            <p class="text-sm font-medium text-indigo-700 mt-2">
                <strong>Formula:</strong> Gross = BOM explosion multi-level (FP &rarr; WIP &rarr; RM) &rarr; Net = Gross - Stok - Sisa PO (approved/partial) &rarr; +Safety 20% &rarr; Order = round-up ke Qty/Case.
            </p>
        </div>
        <form method="POST" action="{{ route('mrp.run') }}" onsubmit="return confirm('Jalankan MRP Run sekarang dengan {{ $demands->count() }} demand?')">
            @csrf
            <button type="submit" class="{{ $demands->isEmpty() ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200' }} font-black py-3 px-8 rounded-xl transition-all text-sm whitespace-nowrap flex items-center gap-2" @disabled($demands->isEmpty())>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Jalankan MRP {{ $demands->isNotEmpty() ? '('.$demands->count().' item)' : '' }}
            </button>
        </form>
    </div>

    <!-- CARD 3: RIWAYAT MRP RUN -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-black text-lg text-slate-800">Riwayat MRP Run</h3>
                <p class="text-sm font-medium text-slate-500 mt-1">Daftar eksekusi MRP planning yang telah dilakukan sebelumnya.</p>
            </div>
            <a href="{{ route('mrp.export-pdf') }}" target="_blank" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-xs border border-rose-200 shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                Print PDF Riwayat
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[25%]">TANGGAL RUN</th>
                        <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[25%] text-right">JUMLAH HASIL</th>
                        <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[30%]">DIJALANKAN OLEH</th>
                        <th class="py-3 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[20%] text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($runs as $run)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-6 text-sm font-bold text-slate-700 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            {{ $run->created_at ? $run->created_at->format('d M Y H:i') : '-' }} WIB
                        </td>
                        <td class="py-4 px-6 text-sm font-black text-emerald-600 text-right">{{ $run->results ? $run->results->count() : 0 }} material</td>
                        <td class="py-4 px-6 text-sm font-medium text-slate-600">{{ $run->runBy->name ?? '-' }}</td>
                        <td class="py-4 px-6 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('mrp.show', $run->id) }}" class="bg-white border border-slate-200 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 text-slate-600 font-bold py-1.5 px-3 rounded-lg transition-all text-xs flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Lihat Hasil
                                </a>
                                <form method="POST" action="{{ route('mrp.destroy', $run->id) }}" onsubmit="return confirm('Hapus MRP Run ini? Semua data hasil akan dihapus.')" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-white border border-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 text-slate-600 font-bold py-1.5 px-3 rounded-lg transition-all text-xs flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-500 font-medium">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Belum ada riwayat MRP Run.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($runs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $runs->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $runs->firstItem() ?? 0 }}-{{ $runs->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $runs->total() }}</span> riwayat
            </div>
            <div>
                {{ $runs->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
