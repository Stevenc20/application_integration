@extends('layouts.app')

@section('title', 'Stock Overview')

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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Stock Overview</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Ringkasan ketersediaan pasokan material, minimum limit pengawasan stok, dan mutasi terpadu</p>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        {{-- Toolbar --}}
        <div class="px-6 py-4 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="font-black text-slate-800 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                    Stock Overview
                </div>
                <a href="{{ route('stock_overviews.export', ['search' => $search, 'location_id' => $locationId, 'status' => $status, 'min_stock' => $minStockOnly ? 1 : null]) }}" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-1.5 text-xs shadow-sm whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Export Excel
                </a>
            </div>

            {{-- Filters --}}
            <form action="{{ route('stock_overviews.index') }}" method="GET" class="flex flex-wrap items-center gap-2 mt-4">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[250px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Kode/nama material..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400">
                </div>

                <select name="location_id" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[140px]">
                    <option value="Semua Lokasi">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                            {{ $loc->nama }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[130px]">
                    <option value="Semua Status">Semua Status</option>
                    <option value="Normal" {{ $status == 'Normal' ? 'selected' : '' }}>Normal</option>
                    <option value="Rendah" {{ $status == 'Rendah' ? 'selected' : '' }}>Rendah</option>
                    <option value="Habis" {{ $status == 'Habis' ? 'selected' : '' }}>Habis</option>
                </select>

                <label class="flex items-center gap-1.5 text-xs font-bold text-slate-600 cursor-pointer select-none whitespace-nowrap">
                    <input type="checkbox" name="min_stock" value="1" {{ $minStockOnly ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer">
                    Stok Minim
                </label>

                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center gap-1">
                    Cari
                </button>

                @if($search || ($locationId && $locationId !== 'Semua Lokasi') || ($status && $status !== 'Semua Status') || $minStockOnly)
                    <a href="{{ route('stock_overviews.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                @endif

                <button type="button" onclick="openMutationModal('mutationsModal')" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Riwayat Mutasi
                </button>
            </form>
        </div>

        {{-- Stock Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Kode</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Nama Material</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tipe</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Lokasi</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Qty Stok</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Stok di Vendor</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">UoM</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Min. Stok</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($stocks as $stock)
                    @php
                        $qty = $stock->qty;
                        $min = $stock->material->min_stok ?? 0;

                        if ($qty <= 0) {
                            $class = 'habis';
                            $label = 'Habis';
                        } elseif ($qty <= $min) {
                            $class = 'rendah';
                            $label = 'Rendah';
                        } else {
                            $class = 'normal';
                            $label = 'Normal';
                        }

                        $statusColors = [
                            'habis'  => ['badge' => 'bg-red-100 text-red-700 border-red-200', 'text' => 'text-red-500'],
                            'rendah' => ['badge' => 'bg-amber-100 text-amber-700 border-amber-200', 'text' => 'text-amber-500'],
                            'normal' => ['badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'text' => 'text-emerald-500'],
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <span class="text-xs font-black text-blue-600 font-mono">{{ $stock->material->kode ?? '-' }}</span>
                        </td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-800">{{ $stock->material->nama ?? '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-500">{{ $stock->material->tipe ?? '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-500">{{ $stock->storageLocation->nama ?? '-' }}</td>
                        <td class="py-3 px-4 text-right">
                            <span class="text-sm font-black {{ $statusColors[$class]['text'] }}">
                                {{ number_format($qty, 0) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center text-xs text-slate-300">
                            {{ $stock->qty_vendor > 0 ? number_format($stock->qty_vendor, 0) : '—' }}
                        </td>
                        <td class="py-3 px-4 text-center text-xs font-bold text-slate-600">
                            {{ $stock->material->uom ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-right text-xs font-bold text-slate-700">
                            {{ number_format($min, 0) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border {{ $statusColors[$class]['badge'] }}">
                                {{ $label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-500 font-medium">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                Tidak ada data stok material yang cocok.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($stocks->total() > 0 && $stocks->lastPage() > 1)
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $stocks->firstItem() ?? 0 }}-{{ $stocks->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $stocks->total() }}</span> Material Stock
            </div>
            <div>
                {{ $stocks->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@push('modals')
<div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center" id="mutationsModal">
    <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col m-4">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <h3 class="font-black text-slate-800">Riwayat Mutasi Pasokan (GR & GI)</h3>
        </div>

        <div class="overflow-y-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 sticky top-0 z-10">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tanggal</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tipe</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Dokumen</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Lokasi</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Kode</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Nama Material</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($mutations as $m)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs text-slate-600">{{ \Carbon\Carbon::parse($m['tanggal'])->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-xs font-black" style="color: {{ $m['color'] }}">{{ $m['tipe'] }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-700 font-mono">{{ $m['dokumen'] }}</td>
                        <td class="py-3 px-4 text-xs text-slate-600">{{ $m['lokasi'] }}</td>
                        <td class="py-3 px-4 text-xs font-mono text-slate-700">{{ $m['kode'] }}</td>
                        <td class="py-3 px-4 text-xs text-slate-600">{{ $m['nama'] }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-right" style="color: {{ $m['color'] }}">{{ $m['qty'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500 font-medium">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Belum ada riwayat mutasi transaksi posting.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 shrink-0">
            <button type="button" onclick="closeMutationModal('mutationsModal')" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 rounded-xl transition-all text-sm">Tutup</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    function openMutationModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }

    function closeMutationModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('mutationsModal');
        if (event.target === modal) {
            closeMutationModal('mutationsModal');
        }
    }
</script>
@endpush
