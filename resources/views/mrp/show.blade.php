@extends('layouts.app')

@section('title', 'Hasil MRP Run')

@section('content')
<div class="space-y-6">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl">account_tree</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">MRP - Material Requirements Planning</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Detail Hasil Eksekusi MRP Run</p>
            </div>
        </div>
        <div class="relative flex items-center gap-2 flex-wrap">
            <a href="{{ route('mrp.excel', $mrpRun->id) }}" class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm inline-flex items-center gap-1.5">
                <span class="material-icons text-base">download</span> Export Excel
            </a>
            <a href="{{ route('mrp.pdf', $mrpRun->id) }}" target="_blank" class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm inline-flex items-center gap-1.5">
                <span class="material-icons text-base">picture_as_pdf</span> Print PDF
            </a>
            <a href="{{ route('mrp.index') }}" class="bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl px-4 py-2.5 transition-all text-sm backdrop-blur-sm">Kembali</a>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6">
        <div class="flex justify-between items-start flex-wrap gap-4">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">MRP Run</div>
                <div class="text-xl font-black text-blue-800">
                    {{ $mrpRun->created_at ? $mrpRun->created_at->format('d M Y H:i') : '-' }} WIB
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Dijalankan oleh: <strong>{{ $mrpRun->runBy->name ?? '-' }}</strong>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="text-sm text-blue-800">Total Material</div>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ $mrpRun->results->count() }}</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="text-sm text-red-800">Perlu Pengadaan (PO)</div>
                <div class="text-2xl font-black text-red-600 mt-1">{{ $mrpRun->results->where('recommendation_type', 'purchase')->count() }}</div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <div class="text-sm text-amber-800">Perlu Produksi</div>
                <div class="text-2xl font-black text-amber-600 mt-1">{{ $mrpRun->results->where('recommendation_type', 'production')->count() }}</div>
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6">
        <div class="text-base font-black text-slate-700">Detail Hasil MRP</div>
        <div class="text-xs text-slate-400 mb-2 mt-1">
            Formula: <strong>Gross</strong> = BOM explosion multi-level (FP &rarr; WIP &rarr; RM) &nbsp;|&nbsp;
            <strong>Net</strong> = Gross - Stok Tersedia - Sisa PO &nbsp;|&nbsp;
            <strong>+Safety 20%</strong> &nbsp;|&nbsp;
            <strong>Order</strong> = round-up ke Qty/Case
        </div>
        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 p-2.5 rounded-lg mb-5">
            * Stok Tersedia = Stok RM aktual + Stok FP/WIP dikonversi ke RM via BOM (stok FP &divide; base qty &times; qty komponen)
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-red-700 text-white">
                        <th class="py-3 px-4 font-bold whitespace-nowrap">Material</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Gross Req.</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Sisa PO</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Net Req.</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Safety 20%</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Total + Safety</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Qty/Case</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Rekomendasi Order</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-right">Stok Tersedia*</th>
                        <th class="py-3 px-4 font-bold whitespace-nowrap text-center">Rekomendasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($mrpRun->results->sortBy(fn($r) => $r->recommendation_type === 'purchase' ? 0 : 1) as $result)
                    @php
                        $withSafety = (float)$result->net_requirement + (float)$result->safety_stock_qty;
                        $isPurchase = $result->recommendation_type === 'purchase';
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors {{ $isPurchase ? 'bg-red-50/30' : '' }}">
                        <td class="py-3 px-4">
                            <div class="font-bold font-mono text-blue-800">{{ $result->material->kode ?? '' }}</div>
                            <div class="font-medium text-slate-700">{{ $result->material->nama ?? '-' }}</div>
                            <div class="text-slate-400 text-[10px]">{{ $result->material->uom ?? '' }}</div>
                        </td>
                        <td class="py-3 px-4 text-right">{{ number_format($result->gross_requirement, 3) }}</td>
                        <td class="py-3 px-4 text-right text-emerald-700">
                            {{ (float)$result->open_po_qty > 0 ? number_format($result->open_po_qty, 3) : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold">{{ number_format($result->net_requirement, 3) }}</td>
                        <td class="py-3 px-4 text-right text-amber-600">+{{ number_format($result->safety_stock_qty, 3) }}</td>
                        <td class="py-3 px-4 text-right font-semibold text-blue-800">{{ number_format($withSafety, 3) }}</td>
                        <td class="py-3 px-4 text-right text-slate-500">
                            {{ (float)$result->qty_per_case > 0 ? number_format($result->qty_per_case, 3) : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-black text-lg text-slate-800">
                            {{ number_format($result->recommended_quantity, 3) }}
                        </td>
                        <td class="py-3 px-4 text-right font-semibold {{ (float)$result->current_stock < (float)$result->gross_requirement ? 'text-red-500' : 'text-emerald-600' }}">
                            {{ number_format($result->current_stock, 3) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($isPurchase)
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">Buat PO</span>
                            @else
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">Produksi</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-slate-400 italic">Tidak ada hasil MRP.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
