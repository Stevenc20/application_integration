@extends('layouts.app')

@section('title', 'Detail BOM')

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
                <h1 class="text-2xl font-black text-white tracking-tight">Detail BOM: {{ $bom->bom_number }}</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Bill of Materials</p>
            </div>
        </div>
    </div>

    {{-- Card Info BOM --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 flex flex-col md:flex-row justify-between items-start gap-5">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nomor BOM</div>
            <div class="text-2xl font-black text-blue-500 mb-3 font-mono">{{ $bom->bom_number }}</div>
            <div class="text-sm text-slate-500 font-medium mb-3">
                <span class="font-mono font-bold bg-sky-50 text-sky-700 px-1.5 py-0.5 rounded text-xs mr-1.5">{{ $bom->material->kode ?? '' }}</span>
                <strong>{{ $bom->material->nama ?? '-' }}</strong>
            </div>
            <div class="text-xs font-bold text-slate-700">
                Qty Base: <strong>{{ fmt_qty($bom->base_quantity) }}</strong> {{ $bom->material->uom ?? 'PCS' }}
            </div>
            @if($bom->description)
            <div class="mt-2 text-xs text-slate-400">Catatan: {{ $bom->description }}</div>
            @endif
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="px-4 py-1.5 rounded-full text-xs font-bold {{ $bom->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                {{ $bom->status === 'active' ? 'Aktif' : 'Nonaktif' }}
            </span>
            <a href="{{ route('boms.edit', $bom->id) }}" class="bg-amber-400 hover:bg-amber-500 text-white font-bold rounded-lg px-5 py-2 transition-all text-sm">Edit</a>
            <a href="{{ route('boms.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg px-5 py-2 transition-all text-sm">Kembali</a>
        </div>
    </div>

    {{-- Card Komponen --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-700">Komponen BOM ({{ $bom->items->count() }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[50px]">#</th>
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Kode</th>
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Nama Material</th>
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tipe</th>
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Qty</th>
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">UoM</th>
                        <th class="py-3.5 px-5 text-xs font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($bom->items as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-5 text-xs text-slate-400">{{ $loop->iteration }}</td>
                        <td class="py-3.5 px-5 text-xs font-mono text-blue-500 font-bold">{{ $item->material->kode ?? '' }}</td>
                        <td class="py-3.5 px-5 text-xs font-bold text-slate-800">{{ $item->material->nama ?? '-' }}</td>
                        <td class="py-3.5 px-5 text-xs font-bold" style="color: {{ $item->material->tipe === 'RM' ? '#e11d48' : '#2563eb' }}">{{ $item->material->tipe ?? '-' }}</td>
                        <td class="py-3.5 px-5 text-xs font-black text-slate-800 text-right">{{ fmt_qty($item->quantity) }}</td>
                        <td class="py-3.5 px-5 text-xs text-slate-500">{{ $item->unit ?? ($item->material->uom ?? '-') }}</td>
                        <td class="py-3.5 px-5 text-xs text-slate-400">{{ $item->notes ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-medium">Tidak ada komponen di dalam BOM ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
