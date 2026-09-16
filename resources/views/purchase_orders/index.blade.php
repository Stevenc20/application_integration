@extends('layouts.app')

@section('title', 'Purchase Order')

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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Purchase Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Kelola pesanan pembelian barang ke vendor, pelacakan pengiriman, status PO, dan rincian kuantitas item</p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                <form method="GET" action="{{ route('purchase_orders.index') }}" class="flex items-center gap-2 w-full lg:w-auto flex-wrap" id="filterForm">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[240px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="No. PO / Nama Vendor..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400">
                    </div>

                    <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal order" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px]">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai tanggal order" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px]">

                    <select name="vendor_id" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[160px]" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Semua Vendor</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ (string) request('vendor_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->nama }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[140px]" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ strtolower(request('status')) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="approved" {{ strtolower(request('status')) === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="partially_received" {{ (strtolower(request('status')) === 'partially_received' || strtolower(request('status')) === 'partial_received' || strtolower(request('status')) === 'partially received') ? 'selected' : '' }}>Partial Received</option>
                        <option value="received" {{ strtolower(request('status')) === 'received' ? 'selected' : '' }}>Received</option>
                        <option value="cancelled" {{ strtolower(request('status')) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>

                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                    @if(request('search') || request('date_from') || request('date_to') || request('vendor_id') || request('status'))
                    <a href="{{ route('purchase_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                    @endif
                </form>

                <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto">
                    <a href="{{ route('purchase_orders.export', request()->query()) }}" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Excel
                    </a>
                    <a href="{{ route('purchase_orders.print_pdf', request()->query()) }}" target="_blank" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-rose-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        PDF
                    </a>
                    <a href="{{ route('purchase_orders.create') }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-1.5 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Buat PO
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">No. PO</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Vendor</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Tgl Order</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Est. Terima</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Catatan</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Status</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($pos as $po)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs font-mono font-bold" style="color: var(--navy-dark);">{{ $po->po_number }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $po->vendor->nama ?? '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $po->order_date ? $po->order_date->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 text-xs text-slate-400">{{ Str::limit($po->notes ?? '-', 30) }}</td>
                        <td class="py-3 px-4 text-xs">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'approved' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'received' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    'partially_received' => 'bg-amber-100 text-amber-700 border-amber-200',
                                ];
                                $statusClass = $statusColors[$po->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border {{ $statusClass }}">
                                {{ ucwords(str_replace('_', ' ', $po->status)) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-center">
                            <a href="{{ route('purchase_orders.show', $po->id) }}" class="bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 px-2 py-1 rounded text-[10px] font-bold transition-colors">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-10 text-slate-300">
                            <span class="material-icons text-5xl block mb-2">sentiment_dissatisfied</span>
                            <span class="text-sm font-medium">Belum ada Purchase Order.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100">
            {{ $pos->links() }}
        </div>
    </div>
</div>
@endsection
