@extends('layouts.app')

@section('title', 'Rundown Stock')

@push('styles')
<style>
.top-scrollbar { width: 100%; overflow-x: auto; overflow-y: hidden; height: 18px; margin-bottom: 4px; }
.top-scrollbar-dummy { height: 1px; }
.top-scrollbar::-webkit-scrollbar { height: 12px; }
.top-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.top-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid #f1f5f9; }
.top-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.table-wrap { overflow-x: auto; }
.table-wrap::-webkit-scrollbar { height: 12px; width: 12px; }
.table-wrap::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid #f1f5f9; }
.table-wrap::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">Rundown Stock</h1>
                    <p class="text-rose-200 text-sm font-semibold mt-1">Detail inventory finish part &mdash; RUNDOWN STOCK FP</p>
                </div>
            </div>
            
            <div class="flex gap-3 overflow-x-auto pb-2 md:pb-0">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">Total Item</p>
                    <p class="text-white font-bold text-xl">{{ $total }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0 border-b-2 border-blue-400">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">Over Stock</p>
                    <p class="text-white font-bold text-xl">{{ $overStock }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0 border-b-2 border-amber-400">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">Limited</p>
                    <p class="text-white font-bold text-xl">{{ $limitedStock }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0 border-b-2 border-red-400">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">Kosong</p>
                    <p class="text-white font-bold text-xl">{{ $zeroStock }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(!$hasData)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center flex flex-col items-center justify-center">
        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
        </div>
        <h3 class="text-lg font-black text-slate-700 tracking-tight mb-2">Belum ada data</h3>
        <p class="text-sm text-slate-500 font-medium mb-6 max-w-md">Upload file Excel XLSM dari halaman Dashboard untuk melihat data Rundown Stock.</p>
        <a href="{{ route('stock.index') }}" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-md shadow-rose-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Ke Dashboard
        </a>
    </div>
    @else

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <form method="GET" action="{{ route('rundown.index') }}" id="filterForm">
            {{-- Customer Quick Filter --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3 flex-wrap">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mr-2">Customer:</span>
                <a href="{{ route('rundown.index', array_merge(request()->except(['customer','page']), ['customer'=>''])) }}" class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase transition-all duration-200 border {{ $filterCustomer === '' ? 'bg-rose-600 text-white border-rose-600 shadow-md shadow-rose-200' : 'bg-white text-slate-600 border-slate-200 hover:border-rose-300 hover:text-rose-600' }}">
                    Semua
                </a>
                @foreach($allCustomer as $c)
                <a href="{{ route('rundown.index', array_merge(request()->except(['customer','page']), ['customer'=>$c])) }}" class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase transition-all duration-200 border {{ $filterCustomer === $c ? 'bg-rose-600 text-white border-rose-600 shadow-md shadow-rose-200' : 'bg-white text-slate-600 border-slate-200 hover:border-rose-300 hover:text-rose-600' }}">
                    {{ $c }}
                </a>
                @endforeach
            </div>

            {{-- Toolbar Filters --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 flex-wrap bg-white">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 flex-1 min-w-[200px] max-w-sm focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Part Number..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400" onchange="document.getElementById('filterForm').submit()">
                </div>
                
                <select name="customer" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 transition-all" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Customer</option>
                    @foreach($allCustomer as $c)
                    <option value="{{ $c }}" {{ $filterCustomer === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>

                <select name="proses" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 transition-all" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Proses</option>
                    @foreach($allProses as $val => $label)
                    <option value="{{ $val }}" {{ $filterProses === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="type_of_part" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 transition-all" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Type Part</option>
                    @foreach($allTypeOfPart as $t)
                    <option value="{{ $t }}" {{ $filterType === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>

                <select name="stock_movement" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 transition-all" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Movement</option>
                    @foreach($allMovement as $m)
                    <option value="{{ $m }}" {{ $filterMovement === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>

                <select name="remarks" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 transition-all" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Status</option>
                    @foreach($allRemarks as $r)
                    <option value="{{ $r }}" {{ $filterRemarks === $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="sort" value="{{ $sortBy }}">
                <input type="hidden" name="dir" value="{{ $sortDir }}">
                
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2 text-sm shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter
                </button>
                <a href="{{ route('rundown.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm">Reset</a>
                
                <div class="ml-auto text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                    Menampilkan <span class="text-rose-600">{{ $total }}</span> item
                </div>
            </div>
        </form>

        {{-- Table Content --}}
        <div class="top-scrollbar px-6 mt-4" id="topScrollbarRundown">
            <div class="top-scrollbar-dummy" id="topScrollbarDummyRundown"></div>
        </div>

        <div class="table-wrap px-6 pb-6" id="tableWrapRundown">
            @if($items->isEmpty())
            <div class="py-12 text-center border-t border-slate-100 mt-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <p class="text-slate-500 font-medium text-sm">Tidak ada data yang cocok dengan filter pencarian.</p>
            </div>
            @else
            <table class="w-full text-left border-collapse min-w-[1400px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap rounded-tl-xl w-12 text-center">#</th>
                        
                        @if($filterType === 'Raw Material')
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Kode</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Nama Material</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tipe</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Lokasi</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Qty Stok</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Stok di Vendor</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">UoM</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Min. Stok</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap rounded-tr-xl text-center">Status</th>
                        @else
                            @php
                                $headers = [
                                    ['col' => 'job_no', 'label' => 'Job No', 'align' => 'left'],
                                    ['col' => null, 'label' => 'Part Number', 'align' => 'left'],
                                    ['col' => 'customer', 'label' => 'Customer', 'align' => 'left'],
                                    ['col' => null, 'label' => 'Proses', 'align' => 'center'],
                                    ['col' => null, 'label' => 'Source', 'align' => 'left'],
                                    ['col' => null, 'label' => 'Type Part', 'align' => 'left'],
                                    ['col' => 'stock_movement', 'label' => 'Movement', 'align' => 'center'],
                                    ['col' => null, 'label' => 'Pcs/Day', 'align' => 'right'],
                                    ['col' => 'stock_fg', 'label' => 'Stock FG', 'align' => 'right'],
                                    ['col' => 'strength', 'label' => 'Strength(Day)', 'align' => 'left'],
                                    ['col' => null, 'label' => 'Stock SAP', 'align' => 'right'],
                                    ['col' => null, 'label' => 'Diff', 'align' => 'right'],
                                    ['col' => null, 'label' => 'Accuracy', 'align' => 'right'],
                                    ['col' => null, 'label' => 'Status', 'align' => 'center'],
                                    ['col' => null, 'label' => 'Min Stock', 'align' => 'right'],
                                    ['col' => null, 'label' => 'Max Stock', 'align' => 'right'],
                                    ['col' => null, 'label' => 'Shortage', 'align' => 'right', 'rounded' => true]
                                ];
                            @endphp
                            @foreach($headers as $h)
                                <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-{{ $h['align'] }} {{ isset($h['rounded']) ? 'rounded-tr-xl' : '' }}">
                                    @if($h['col'])
                                        @php $nDir = ($sortBy === $h['col'] && $sortDir === 'asc') ? 'desc' : 'asc'; @endphp
                                        <a href="{{ request()->fullUrlWithQuery(['sort'=>$h['col'],'dir'=>$nDir]) }}" class="hover:text-rose-600 inline-flex items-center gap-1 transition-colors {{ $h['align'] === 'right' ? 'flex-row-reverse' : '' }}">
                                            {{ $h['label'] }}
                                            @if($sortBy === $h['col'])
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $sortDir==='asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" /></svg>
                                            @endif
                                        </a>
                                    @else
                                        {{ $h['label'] }}
                                    @endif
                                </th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($items as $i => $item)
                    @php
                    $rowNum = ($items->currentPage() - 1) * $items->perPage() + $i + 1;
                    
                    if ($filterType === 'Raw Material') {
                        $mCode = strtoupper(trim($item->job_no));
                        $mat = isset($materialsData) ? ($materialsData[$mCode] ?? null) : null;
                        $locName = '-'; $qtyVendor = 0; $uom = '-';
                        if ($mat) {
                            $uom = $mat->uom ?? 'PCS';
                            $stock = $mat->stocks->first();
                            if ($stock) {
                                $locName = $stock->storageLocation->nama ?? '-';
                                $qtyVendor = $stock->qty_vendor ?? 0;
                            }
                        }
                    } else {
                        $strength = (float) $item->strength;
                        if ($strength <= 0) { $sc = 'red'; $bp = 0; }
                        elseif ($strength < 1) { $sc = 'red'; $bp = max(5, min(100, $strength * 20)); }
                        elseif ($strength < 2) { $sc = 'amber'; $bp = min(100, $strength * 20); }
                        elseif ($strength < 5) { $sc = 'emerald'; $bp = min(100, $strength * 12); }
                        else { $sc = 'blue'; $bp = 100; }

                        $pl = strtolower($item->proses ?? '');
                        if (str_contains($pl, 'stamp')) $prosesCls = 'bg-yellow-100 text-yellow-800';
                        elseif (str_contains($pl, 'subcont')) $prosesCls = 'bg-purple-100 text-purple-800';
                        elseif (str_contains($pl, 'raw')) $prosesCls = 'bg-red-100 text-red-800';
                        else $prosesCls = 'bg-sky-100 text-sky-800';

                        $mv = strtolower($item->stock_movement ?? '');
                        if (str_contains($mv, 'fast')) $movCls = 'bg-green-100 text-green-800 border border-green-200';
                        elseif (str_contains($mv, 'medium')) $movCls = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                        else $movCls = 'bg-red-100 text-red-800 border border-red-200';
                    }

                    $rem = strtolower(trim($item->remarks ?? ''));
                    if ($rem === 'ok' || $rem === 'normal') { $remCls = 'bg-emerald-100 text-emerald-800 border border-emerald-200'; $qtyColor = 'text-emerald-600'; }
                    elseif ($rem === 'minim' || $rem === 'rendah') { $remCls = 'bg-amber-100 text-amber-800 border border-amber-200'; $qtyColor = 'text-amber-600'; }
                    elseif ($rem === 'over') { $remCls = 'bg-blue-100 text-blue-800 border border-blue-200'; $qtyColor = 'text-blue-600'; }
                    elseif ($rem === 'run out') { $remCls = 'bg-purple-100 text-purple-800 border border-purple-200'; $qtyColor = 'text-purple-600'; }
                    elseif ($rem === 'kosong') { $remCls = 'bg-red-100 text-red-800 border border-red-200'; $qtyColor = 'text-red-600'; }
                    else { $remCls = 'bg-slate-100 text-slate-600 border border-slate-200'; $qtyColor = 'text-slate-600'; }

                    @endphp
                    
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="py-3 px-4 text-xs font-medium text-slate-400 text-center">{{ $rowNum }}</td>
                        
                        @if($filterType === 'Raw Material')
                            <td class="py-3 px-4 text-xs font-black text-slate-800">{{ $item->job_no }}</td>
                            <td class="py-3 px-4 text-xs font-bold text-slate-600">{{ $item->part_number }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-500">RM</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-500">{{ $locName }}</td>
                            <td class="py-3 px-4 text-xs font-black text-right {{ $qtyColor }}">{{ number_format($item->stock_fg, 0) }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-500 text-center">{{ $qtyVendor > 0 ? number_format($qtyVendor, 0) : '—' }}</td>
                            <td class="py-3 px-4 text-[10px] font-bold text-slate-400 text-center uppercase">{{ $uom }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-400 text-right">{{ number_format($item->min_stock, 0) }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $remCls }}">{{ $item->remarks ?: '-' }}</span>
                            </td>
                        @else
                            <td class="py-3 px-4 text-xs font-black text-slate-800">{{ $item->job_no }}</td>
                            <td class="py-3 px-4 text-xs font-bold text-slate-600 max-w-[150px] truncate" title="{{ $item->part_number }}">{{ $item->part_number }}</td>
                            <td class="py-3 px-4 text-[10px] font-black text-slate-500">{{ $item->customer }}</td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded text-[9px] font-black uppercase tracking-wider {{ $prosesCls }}">{{ $item->proses }}</span>
                            </td>
                            <td class="py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase">{{ $item->source }}</td>
                            <td class="py-3 px-4 text-[10px] font-semibold text-slate-500">{{ $item->type_of_part }}</td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded text-[9px] font-black uppercase tracking-wider {{ $movCls }}">{{ $item->stock_movement }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-400 text-right">{{ number_format($item->pcs_day, 0) }}</td>
                            <td class="py-3 px-4 text-sm font-black text-right text-slate-700">{{ number_format($item->stock_fg, 0) }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden min-w-[50px]">
                                        <div class="h-full rounded-full bg-{{ $sc }}-500" style="width: {{ $bp }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-{{ $sc }}-600 min-w-[30px] text-right">{{ number_format($strength, 1) }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-500 text-right">{{ number_format($item->stock_sap, 0) }}</td>
                            <td class="py-3 px-4 text-xs font-bold text-right {{ $item->stock_diff < 0 ? 'text-red-500' : 'text-emerald-500' }}">{{ number_format($item->stock_diff, 0) }}</td>
                            <td class="py-3 px-4 text-[10px] font-bold text-slate-500 text-right">{{ number_format((float)$item->accuracy * 100, 1) }}%</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $remCls }}">{{ $item->remarks ?: '-' }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-400 text-right">{{ number_format($item->min_stock, 0) }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-400 text-right">{{ number_format($item->max_stock, 0) }}</td>
                            <td class="py-3 px-4 text-xs font-bold text-right {{ $item->stock_shortage < 0 ? 'text-red-500' : 'text-slate-600' }}">{{ number_format($item->stock_shortage, 0) }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Pagination --}}
        @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $items->firstItem() }}-{{ $items->lastItem() }}</span> dari <span class="font-black text-slate-700">{{ $items->total() }}</span>
            </div>
            
            <div class="flex items-center gap-1.5">
                @if($items->onFirstPage())
                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </span>
                @else
                <a href="{{ $items->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
                @endif

                @php
                $pStart = max(1, $items->currentPage() - 2);
                $pEnd   = min($items->lastPage(), $items->currentPage() + 2);
                @endphp

                @if($pStart > 1)
                <a href="{{ $items->url(1) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">1</a>
                @if($pStart > 2)
                <span class="px-1 text-slate-400">...</span>
                @endif
                @endif

                @for($p = $pStart; $p <= $pEnd; $p++)
                @if($p === $items->currentPage())
                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-600 text-xs font-bold text-white shadow-md shadow-rose-200 border border-rose-600">{{ $p }}</span>
                @else
                <a href="{{ $items->url($p) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">{{ $p }}</a>
                @endif
                @endfor

                @if($pEnd < $items->lastPage())
                @if($pEnd < $items->lastPage() - 1)
                <span class="px-1 text-slate-400">...</span>
                @endif
                <a href="{{ $items->url($items->lastPage()) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">{{ $items->lastPage() }}</a>
                @endif

                @if($items->hasMorePages())
                <a href="{{ $items->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
                @else
                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </span>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync Top Scrollbar
    const tableWrap = document.getElementById('tableWrapRundown');
    const topScrollbar = document.getElementById('topScrollbarRundown');
    const topScrollbarDummy = document.getElementById('topScrollbarDummyRundown');
    if(tableWrap && topScrollbar && topScrollbarDummy) {
        const table = tableWrap.querySelector('table');
        if(table) {
            const updateDummyWidth = () => {
                topScrollbarDummy.style.width = table.offsetWidth + 'px';
            };
            updateDummyWidth();
            topScrollbar.addEventListener('scroll', function() {
                tableWrap.scrollLeft = topScrollbar.scrollLeft;
            });
            tableWrap.addEventListener('scroll', function() {
                topScrollbar.scrollLeft = tableWrap.scrollLeft;
            });
            window.addEventListener('resize', updateDummyWidth);
        } else {
            topScrollbar.style.display = 'none';
        }
    }
});
</script>
@endpush