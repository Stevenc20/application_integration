@extends('layouts.app')

@section('title', 'Summary Kanban Material (SKM)')

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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Summary Kanban Material (SKM)</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">{{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="relative flex gap-3 flex-wrap">
            <a href="{{ route('summary_kanban.demands.template') }}" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-4 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                Download Template
            </a>
            <a href="{{ route('summary_kanban.create') }}" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-xl border border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Buat SKM Manual
            </a>
        </div>
    </div>

    {{-- Stats Widgets --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center flex flex-col items-center justify-center">
            <div class="text-3xl font-black text-slate-800 leading-none mb-2">{{ $stats['total'] }}</div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total SKM</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center flex flex-col items-center justify-center">
            <div class="text-3xl font-black text-slate-400 leading-none mb-2">{{ $stats['draft'] }}</div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Draft</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center flex flex-col items-center justify-center">
            <div class="text-3xl font-black text-blue-500 leading-none mb-2">{{ $stats['sent'] }}</div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dikirim</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center flex flex-col items-center justify-center">
            <div class="text-3xl font-black text-amber-500 leading-none mb-2">{{ $stats['partial_received'] }}</div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sebagian</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center flex flex-col items-center justify-center">
            <div class="text-3xl font-black text-emerald-500 leading-none mb-2">{{ $stats['completed'] }}</div>
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selesai</div>
        </div>
        <div class="bg-white rounded-2xl border shadow-sm p-5 text-center flex flex-col items-center justify-center {{ $stats['pending'] > 0 ? 'border-red-300 bg-red-50' : 'border-slate-200' }}">
            <div class="text-3xl font-black leading-none mb-2 {{ $stats['pending'] > 0 ? 'text-red-500' : 'text-slate-300' }}">{{ $stats['pending'] }}</div>
            <div class="text-[10px] font-black uppercase tracking-widest {{ $stats['pending'] > 0 ? 'text-red-500' : 'text-slate-400' }}">Perlu Order</div>
        </div>
    </div>

    {{-- Alert / Status Banner --}}
    @if($stats['pending'] > 0)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <div class="text-sm font-black text-red-700">{{ $stats['pending'] }} material SKM stoknya di bawah minimum!</div>
            <div class="text-xs font-semibold text-red-500 mt-1">Buat dokumen SKM sekarang untuk memesan material yang dibutuhkan.</div>
        </div>
        <a href="{{ route('summary_kanban.create') }}" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-sm whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat SKM Sekarang
        </a>
    </div>
    @else
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-sm font-bold text-emerald-700">Semua stok material SKM mencukupi. Tidak ada item yang perlu dipesan.</div>
        <a href="{{ route('summary_kanban.create') }}" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-sm whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat SKM Manual
        </a>
    </div>
    @endif

    {{-- Riwayat SKM --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                Riwayat SKM
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800 border-y border-slate-700">
                        <th class="py-3 px-4 text-[10px] font-black text-white uppercase tracking-widest whitespace-nowrap">Nomor SKM</th>
                        <th class="py-3 px-4 text-[10px] font-black text-white uppercase tracking-widest whitespace-nowrap">Tanggal</th>
                        <th class="py-3 px-4 text-[10px] font-black text-white uppercase tracking-widest whitespace-nowrap text-right">Jml Item</th>
                        <th class="py-3 px-4 text-[10px] font-black text-white uppercase tracking-widest whitespace-nowrap text-center">Status</th>
                        <th class="py-3 px-4 text-[10px] font-black text-white uppercase tracking-widest whitespace-nowrap">Dibuat oleh</th>
                        <th class="py-3 px-4 text-[10px] font-black text-white uppercase tracking-widest whitespace-nowrap text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs font-black text-blue-600 font-mono">{{ $order->skm_number }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $order->order_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-700 text-right">{{ $order->items_count }}</td>
                        <td class="py-3 px-4 text-center">
                            @php
                                $statusClasses = match($order->status) {
                                    'draft'            => 'bg-slate-100 text-slate-600 border-slate-200',
                                    'sent'             => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'partial_received' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'completed'        => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'cancelled'        => 'bg-red-100 text-red-700 border-red-200',
                                    default            => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border {{ $statusClasses }}">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $order->createdBy->name ?? '-' }}</td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('summary_kanban.show', $order) }}" class="text-blue-600 hover:text-blue-700 font-bold text-xs hover:underline">Detail</a>
                                @if($order->status === 'draft')
                                <form method="POST" action="{{ route('summary_kanban.destroy', $order) }}"
                                      onsubmit="return confirm('Hapus SKM {{ $order->skm_number }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-600 font-bold text-xs hover:underline bg-none border-none cursor-pointer">Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500 font-medium">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                Belum ada dokumen SKM.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $orders->total() }}</span> SKM
            </div>
            <div>
                {{ $orders->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>

    {{-- Preview Item Perlu Dipesan --}}
    @if(!empty($pending))
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                Preview Item Perlu Dipesan
            </h3>
            <div class="text-[11px] font-medium text-slate-400 mt-1">
                Kalkulasi kanban beredar: LT 3 hari + SS 2 hari + Proses 1 hari = 6 hari &times; kanban/hari.
                Klik "Buat SKM Sekarang" untuk memprosesnya.
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-amber-50 border-y border-amber-100">
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap">Material</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap">Vendor</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Stok Saat Ini</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Total Kanban</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Stok (kanban)</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Outstanding</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Qty/Kartu</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Saran Kartu</th>
                        <th class="py-3 px-4 text-[10px] font-black text-amber-800 uppercase tracking-widest whitespace-nowrap text-right">Total Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($pending as $p)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <span class="block text-xs font-black text-slate-800 font-mono">{{ $p['material']->code }}</span>
                            <span class="block text-[11px] text-slate-500">{{ $p['material']->name }}</span>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-500">{{ $p['material']->vendor->name ?? '-' }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-red-500 text-right">{{ number_format($p['current_stock'], 0) }}</td>
                        <td class="py-3 px-4 text-xs font-black text-slate-800 text-right">{{ $p['total_kanban'] }}</td>
                        <td class="py-3 px-4 text-xs text-slate-500 text-right">{{ $p['stock_kanban'] }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-amber-600 text-right">{{ $p['outstanding_kanban'] }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-700 text-right">{{ number_format($p['kanban_qty'], 0) }}</td>
                        <td class="py-3 px-4 text-xs font-black text-slate-800 text-right">{{ $p['num_cards_suggest'] }}</td>
                        <td class="py-3 px-4 text-xs font-black text-slate-800 text-right">{{ number_format($p['order_qty_suggest'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Data Demand FP Bulan Berjalan --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <h3 class="font-black text-slate-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Data Demand FP Bulan Berjalan
            </h3>
            <div class="flex gap-2">
                <a href="{{ route('summary_kanban.demands.template') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    Download Template
                </a>
                @if($demands->isNotEmpty())
                <form method="POST" action="{{ route('summary_kanban.demands.clear') }}"
                      onsubmit="return confirm('Hapus semua demand aktif?')">
                    @csrf @method('DELETE')
                    <button class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2 px-3 rounded-xl transition-all flex items-center gap-1.5 text-xs border border-red-200 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Hapus Semua
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div class="px-6 py-3 text-[11px] font-medium text-slate-400 border-b border-slate-50">
            Demand digunakan untuk menghitung total kanban beredar. Import sekali per bulan — data akan tetap aktif sampai diganti import baru.
            @if($demands->isNotEmpty())
            <span class="text-blue-600 font-semibold ml-1">
                Periode aktif: {{ $demands->first()->period ?? '-' }} ({{ $demands->count() }} material FP/WIP)
            </span>
            @endif
        </div>

        {{-- Demand Import Form --}}
        <div class="p-6 border-b border-slate-100">
            <form method="POST" action="{{ route('summary_kanban.demands.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="text-xs font-bold text-slate-600 mb-2">Upload File Excel (Demand Bulan Ini)</div>
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <input type="file" name="file" accept=".xlsx,.xls" required
                           class="flex-1 w-full text-sm border border-slate-200 rounded-xl px-3 py-2 text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-red-50 file:text-red-600 hover:file:bg-red-100 transition-colors">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-sm whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        Import &amp; Ganti Demand
                    </button>
                </div>
            </form>
        </div>

        {{-- Demand Table --}}
        @if($demands->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Material FP/WIP</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Demand (pcs)</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Hari Kerja</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Periode</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($demands as $d)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <span class="text-xs font-black text-slate-800 font-mono">{{ $d->material->code ?? '-' }}</span>
                            <span class="text-xs text-slate-500 ml-1">{{ $d->material->name ?? '-' }}</span>
                        </td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-700 text-right">{{ number_format($d->demand_qty, 0) }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-right">{{ $d->working_days }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $d->period ?? '-' }}</td>
                        <td class="py-3 px-4 text-xs text-slate-400">{{ $d->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="mx-6 my-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs font-semibold text-amber-700 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            Belum ada demand aktif. Kanban dihitung berdasarkan min_stock sebagai fallback sampai demand diimport.
        </div>
        @endif
    </div>

</div>
@endsection
