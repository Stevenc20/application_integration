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
                <p class="text-sm text-slate-500 font-medium">Semua input data produksi & repair/reject — {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none">
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all">Filter</button>
            </form>
            <div class="flex items-center gap-3 text-sm font-bold">
                <div class="px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-500">{{ $logs->total() }} entries</div>
                <div class="px-3 py-1.5 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-600">OK: {{ number_format($totalOk) }}</div>
                <div class="px-3 py-1.5 bg-orange-50 rounded-xl border border-orange-200 text-orange-600">R: {{ number_format($totalRepair) }}</div>
                <div class="px-3 py-1.5 bg-red-50 rounded-xl border border-red-200 text-red-600">X: {{ number_format($totalReject) }}</div>
            </div>
        </div>
    </div>

    {{-- LOG TABLE --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest w-10">#</th>
                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Detail Item Produksi</th>
                        <th class="px-4 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty Input</th>
                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    @php
                        $hasRepair = ($log['repair_qty'] ?? 0) > 0;
                        $hasReject = ($log['reject_qty'] ?? 0) > 0;
                        $hasOk = ($log['ok_qty'] ?? 0) > 0;
                        
                        if ($log['source'] === 'repair') {
                            $typeBadge = '<span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 text-[9px] font-black uppercase">R</span>';
                            $typeColor = 'border-l-4 border-l-orange-400';
                        } elseif ($log['source'] === 'reject') {
                            $typeBadge = '<span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700 text-[9px] font-black uppercase">X</span>';
                            $typeColor = 'border-l-4 border-l-red-400';
                        } else {
                            $typeBadge = '<span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[9px] font-black uppercase">OK</span>';
                            $typeColor = 'border-l-4 border-l-blue-400';
                        }
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-all {{ $typeColor }}">
                        <td class="px-4 py-4 text-[10px] font-bold text-slate-400">{{ ($logs->currentPage() - 1) * $logs->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <p class="font-black text-slate-800 text-sm tabular-nums">{{ $log['created_at']->format('H:i:s') }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase">{{ $log['created_at']->format('d M Y') }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-black text-[10px] shrink-0 mt-0.5">
                                    {{ strtoupper(substr($log['line'] ?? '?', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <a href="{{ route('operational.job.logs.detail', $log['job_master_id']) }}" class="text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-wider">{{ $log['job_number'] }}</a>
                                        <span class="text-[9px] font-bold text-slate-400">|</span>
                                        <span class="text-[9px] font-black text-slate-500 uppercase">{{ $log['line'] }}</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 truncate max-w-[350px]" title="{{ $log['job_name'] }}">{{ $log['job_name'] }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[8px] font-bold text-slate-500 uppercase">Shift {{ $log['shift'] ?? '-' }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[8px] font-bold text-slate-500">Tgt: {{ number_format($log['target_qty'] ?? 0) }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[8px] font-bold text-slate-500">{{ $log['work_date'] ?? '-' }}</span>
                                    </div>
                                    @if($log['defect_name'] && $log['defect_name'] !== '-')
                                        <p class="text-[10px] font-bold text-orange-500 mt-0.5">Defect: {{ $log['defect_name'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($hasOk)
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 font-black text-xs tabular-nums">+{{ $log['ok_qty'] }}</span>
                                @endif
                                @if($hasRepair)
                                    <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-600 font-black text-xs tabular-nums">{{ $log['repair_qty'] }}R</span>
                                @endif
                                @if($hasReject)
                                    <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 font-black text-xs tabular-nums">{{ $log['reject_qty'] }}X</span>
                                @endif
                                @if(!$hasOk && !$hasRepair && !$hasReject)
                                    <span class="text-slate-300">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-col gap-1">
                                {!! $typeBadge !!}
                                <a href="{{ route('operational.job.logs.detail', $log['job_master_id']) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 text-white text-[9px] font-black uppercase tracking-wider hover:bg-blue-700 transition-all shadow-sm mt-0.5 w-fit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Detail
                                </a>
                                @if($log['operator'])
                                    <span class="text-[9px] font-bold text-slate-500">{{ $log['operator'] }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-slate-400 font-bold text-sm">Belum ada data produksi untuk tanggal {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div class="px-8 py-6 border-t border-gray-100 bg-slate-50/50 flex items-center justify-between">
            <p class="text-xs text-slate-400 font-bold">Menampilkan {{ $logs->firstItem() }} - {{ $logs->lastItem() }} dari {{ $logs->total() }} entries</p>
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
