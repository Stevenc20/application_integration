@extends('layouts.supervisor')

@section('title', 'Audit Trail Produksi')

@section('content')
<div class="space-y-6">
    {{-- PAGE HEADER --}}
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 flex items-center justify-center text-white shadow-xl shadow-slate-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tighter">Audit Trail Produksi</h1>
                <p class="text-sm text-slate-500 font-medium">Detail input per item — {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none">
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all">Filter</button>
            </form>
            <div class="flex items-center gap-3 text-sm font-bold">
                <div class="px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-500">{{ $items->count() }} item</div>
                <div class="px-3 py-1.5 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-600">OK: {{ number_format($totalOk) }}</div>
                <div class="px-3 py-1.5 bg-orange-50 rounded-xl border border-orange-200 text-orange-600">R: {{ number_format($totalRepair) }}</div>
                <div class="px-3 py-1.5 bg-red-50 rounded-xl border border-red-200 text-red-600">X: {{ number_format($totalReject) }}</div>
            </div>
        </div>
    </div>

    {{-- ITEM LIST --}}
    @forelse($items as $item)
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
        <div class="p-6 flex flex-col md:flex-row md:items-center gap-4">
            {{-- LINE ICON --}}
            <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-sm shrink-0 shadow-lg">
                {{ strtoupper(substr($item['line'] ?? '?', 0, 2)) }}
            </div>

            {{-- ITEM INFO --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg font-black text-slate-800 tracking-tight">{{ $item['job_number'] }}</span>
                    <span class="text-slate-300 mx-1">|</span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $item['line'] }}</span>
                </div>
                <p class="text-sm font-bold text-slate-600 truncate">{{ $item['job_name'] }}</p>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-[9px] font-bold text-slate-500 uppercase">Shift {{ $item['shift'] }}</span>
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-[9px] font-bold text-slate-500">Target: {{ number_format($item['target_qty']) }}</span>
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-[9px] font-bold text-slate-500">{{ $item['work_date'] }}</span>
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-[9px] font-bold text-blue-500">{{ $item['entry_count'] }} input</span>
                    @if($item['last_input'])
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-[9px] font-bold text-slate-400">Terakhir: {{ $item['last_input']->format('H:i') }}</span>
                    @endif
                </div>
            </div>

            {{-- QTY SUMMARY --}}
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <p class="text-[9px] font-black text-emerald-500 uppercase">OK</p>
                    <p class="text-xl font-black text-emerald-600 tabular-nums leading-none">{{ number_format($item['actual_ok']) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] font-black text-orange-500 uppercase">Repair</p>
                    <p class="text-xl font-black text-orange-600 tabular-nums leading-none">{{ number_format($item['actual_repair']) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] font-black text-red-500 uppercase">Reject</p>
                    <p class="text-xl font-black text-red-600 tabular-nums leading-none">{{ number_format($item['actual_reject']) }}</p>
                </div>
            </div>

            {{-- DETAIL BUTTON --}}
            <a href="{{ route('operational.job.logs.detail', $item['id']) }}" class="inline-flex items-center gap-1.5 px-5 py-3 rounded-xl bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Detail
            </a>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-20 text-center">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <p class="text-slate-400 font-bold text-sm">Belum ada data produksi untuk tanggal {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</p>
        </div>
    </div>
    @endforelse
</div>
@endsection
