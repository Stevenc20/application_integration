@extends('layouts.app')

@section('title', 'Production Order')

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
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Production Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Daftar perintah produksi untuk pengolahan material</p>
            </div>
        </div>
        
        <div class="relative flex gap-3 flex-wrap">
            <a href="{{ route('production_orders.print_all', ['search' => request('search'), 'status' => request('status')]) }}" target="_blank" class="bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white font-bold py-2.5 px-4 rounded-xl transition-all flex items-center gap-2 text-sm ring-1 ring-white/30 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                Print PDF
            </a>
            <a href="{{ route('production_orders.create') }}" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-xl border border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Buat Production Order
            </a>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <form action="{{ route('production_orders.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[240px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No. Order / material..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400">
                </div>
                
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px]">
                <span class="text-slate-400 text-sm font-bold">-</span>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px]">
                
                <select name="status" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ in_array(request('status'), ['draft', 'created']) ? 'selected' : '' }}>Created / Draft</option>
                    <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Released</option>
                    <option value="in_progress" {{ in_array(request('status'), ['in_progress', 'goods_issued']) ? 'selected' : '' }}>In Progress / Goods Issued</option>
                    <option value="completed" {{ in_array(request('status'), ['completed', 'confirmed']) ? 'selected' : '' }}>Completed / Confirmed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                @if(request('search') || request('start_date') || request('end_date') || request('status'))
                    <a href="{{ route('production_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                @endif
            </form>
        </div>

        <form method="POST" action="{{ route('production_orders.bulk_release') }}" id="bulkForm" onsubmit="return confirm('Release semua Production Order yang dipilih?')">
            @csrf
            
            {{-- Bulk Action Bar --}}
            <div id="bulkBar" class="hidden px-6 py-3 bg-sky-50 border-b border-sky-100 flex items-center gap-4 transition-all">
                <span class="font-black text-sky-700 text-sm flex items-center gap-1.5" id="bulkCount">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    0 dipilih
                </span>
                <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-1.5 px-4 rounded-lg transition-all text-xs shadow-sm shadow-sky-200">Release Semua yang Dipilih</button>
                <button type="button" onclick="clearSelection()" class="text-slate-500 hover:text-slate-700 font-bold text-xs transition-colors">Batal Pilih</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-slate-50 border-y border-slate-200">
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-12 text-center">
                                <input type="checkbox" id="checkAll" class="rounded border-slate-300 text-rose-600 shadow-sm focus:ring-rose-500 cursor-pointer" title="Pilih semua Created">
                            </th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">NO. ORDER</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">MATERIAL</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">QTY PLAN</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">QTY PROD</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">TGL MULAI</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">TGL SELESAI</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">STATUS</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-4 text-center">
                                @if($order->status === 'created')
                                <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="row-check rounded border-slate-300 text-rose-600 shadow-sm focus:ring-rose-500 cursor-pointer">
                                @endif
                            </td>
                            <td class="py-4 px-4 text-xs font-black text-blue-600 font-mono">{{ $order->order_number }}</td>
                            <td class="py-4 px-4">
                                <div class="text-[10px] font-mono text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded inline-block mb-1">{{ $order->material->kode ?? '-' }}</div>
                                <div class="text-xs font-bold text-slate-800">{{ $order->material->nama ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-4 text-xs font-black text-slate-700 text-center">{{ number_format($order->quantity_planned, 3, ',', '.') }}</td>
                            <td class="py-4 px-4 text-xs font-black text-emerald-600 text-center">{{ number_format($order->quantity_produced, 3, ',', '.') }}</td>
                            <td class="py-4 px-4 text-xs font-medium text-slate-600 text-center">{{ $order->planned_start_date ? $order->planned_start_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-4 px-4 text-xs font-medium text-slate-600 text-center">{{ $order->planned_end_date ? $order->planned_end_date->format('d/m/Y') : '-' }}</td>
                            <td class="py-4 px-4 text-center">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-600 border border-slate-200',
                                        'created' => 'bg-slate-100 text-slate-600 border border-slate-200',
                                        'released' => 'bg-sky-100 text-sky-700 border border-sky-200',
                                        'in_progress' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                        'goods_issued' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                        'confirmed' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                        'completed' => 'bg-emerald-100 text-emerald-700 border border-emerald-500',
                                        'cancelled' => 'bg-rose-100 text-rose-700 border border-rose-200'
                                    ];
                                    $style = $statusColors[strtolower($order->status)] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $style }}">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('production_orders.show', $order->id) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors" title="Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </a>
                                    
                                    @if($order->status === 'created')
                                    <div class="h-4 w-[1px] bg-slate-200 mx-0.5"></div>
                                    <a href="{{ route('production_orders.edit', $order->id) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-colors" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </a>
                                    
                                    <button type="submit" formaction="{{ route('production_orders.destroy', $order->id) }}" formmethod="POST" onclick="return confirm('Hapus Production Order {{ $order->order_number }}?')" class="p-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-lg transition-colors" title="Hapus">
                                        @method('DELETE')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                    @endif
                                    
                                    <div class="h-4 w-[1px] bg-slate-200 mx-0.5"></div>
                                    <a target="_blank" href="{{ route('production_orders.print', $order->id) }}" class="p-1.5 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition-colors" title="Print">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500 font-medium">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                    Belum ada data Production Order.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $orders->total() }}</span> data
            </div>
            <div>
                {{ $orders->appends(request()->query())->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    const checkAll  = document.getElementById('checkAll');
    const bulkBar   = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');

    function updateBar() {
        const checked = document.querySelectorAll('.row-check:checked');
        if (checked.length > 0) {
            bulkBar.classList.remove('hidden');
            bulkBar.classList.add('flex');
            bulkCount.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                ${checked.length} dipilih
            `;
        } else {
            bulkBar.classList.add('hidden');
            bulkBar.classList.remove('flex');
            if (checkAll) checkAll.checked = false;
        }
    }

    function clearSelection() {
        document.querySelectorAll('.row-check:checked').forEach(c => c.checked = false);
        if (checkAll) checkAll.checked = false;
        updateBar();
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
            updateBar();
        });
    }

    document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', updateBar));
</script>
@endpush
@endsection
