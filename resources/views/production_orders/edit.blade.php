@extends('layouts.app')

@section('title', 'Edit Production Order')

@section('content')
<div class="space-y-6">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl">precision_manufacturing</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Edit Production Order</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">{{ $productionOrder->order_number }}</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm">
        <ul class="text-sm font-semibold m-0 ps-5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden max-w-xl">
        <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-800">Edit Production Order: {{ $productionOrder->order_number }}</div>
        <div class="p-6">
            <form action="{{ route('production_orders.update', $productionOrder->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Material *</label>
                    <select name="material_id" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full" required>
                        @foreach($materials as $m)
                        <option value="{{ $m->id }}" {{ old('material_id', $productionOrder->material_id) == $m->id ? 'selected' : '' }}>{{ $m->kode }} - {{ $m->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">BOM *</label>
                    <select name="bom_id" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full" required>
                        @foreach($boms as $bom)
                        <option value="{{ $bom->id }}" {{ old('bom_id', $productionOrder->bom_id) == $bom->id ? 'selected' : '' }}>{{ $bom->bom_number }} ({{ $bom->material->nama ?? '' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Qty Planned *</label>
                    <input type="number" name="quantity_planned" value="{{ old('quantity_planned', $productionOrder->quantity_planned) }}" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full" min="0.001" step="0.001" required>
                </div>

                <div class="flex gap-4 flex-wrap">
                    <div class="flex-1 min-w-[200px] flex flex-col gap-1.5 mb-4">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Tgl Mulai Rencana</label>
                        <input type="date" name="planned_start_date" value="{{ old('planned_start_date', $productionOrder->planned_start_date ? $productionOrder->planned_start_date->format('Y-m-d') : '') }}" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full">
                    </div>
                    <div class="flex-1 min-w-[200px] flex flex-col gap-1.5 mb-4">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Tgl Selesai Rencana</label>
                        <input type="date" name="planned_end_date" value="{{ old('planned_end_date', $productionOrder->planned_end_date ? $productionOrder->planned_end_date->format('Y-m-d') : '') }}" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full">
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-wider">Catatan</label>
                    <textarea name="notes" rows="2" class="border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 w-full resize-y min-h-[60px]">{{ old('notes', $productionOrder->notes) }}</textarea>
                </div>

                <div class="flex gap-3 mt-5">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg px-6 py-2.5 transition-all text-sm">Perbarui</button>
                    <a href="{{ route('production_orders.show', $productionOrder->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg px-6 py-2.5 transition-all text-sm inline-flex items-center">Batal</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
