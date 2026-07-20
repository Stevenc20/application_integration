@extends('layouts.supervisor')

@section('title', 'Detail Rekam Jejak - ' . ($job->job_number ?? 'Unknown'))

@section('content')
<div class="space-y-6">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex items-center gap-4">
            <a href="{{ url()->previous() }}" class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest">Job Master</span>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tighter">{{ $job->job_number ?? '-' }}</h1>
                </div>
                <p class="text-sm text-slate-500 font-medium">
                    {{ $job->job_name ?? 'N/A' }}
                    <span class="mx-2 text-slate-300">|</span> Line: <strong class="text-slate-700">{{ $job->line ?? $job->dailyProduction?->line ?? '-' }}</strong>
                    <span class="mx-2 text-slate-300">|</span> Shift: <strong class="text-slate-700">{{ $job->dailyProduction?->shift ?? '-' }}</strong>
                    <span class="mx-2 text-slate-300">|</span> Work Date: <strong class="text-slate-700">{{ $job->dailyProduction?->work_date ?? '-' }}</strong>
                    <span class="mx-2 text-slate-300">|</span> Target: <strong class="text-slate-700">{{ number_format($job->target_qty ?? $job->capacity ?? 0) }} Pcs</strong>
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-6 pr-4">
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Achievement</p>
                @php
                    $totalAct = ($job->dailyProduction?->actual_ok ?? 0) + ($job->dailyProduction?->actual_repair ?? 0) + ($job->dailyProduction?->actual_reject ?? 0);
                    $tgt = $job->target_qty ?? $job->capacity ?? 1;
                    $ach = $tgt > 0 ? round(($totalAct / $tgt) * 100, 1) : 0;
                @endphp
                <p class="text-3xl font-black tracking-tighter leading-none {{ $ach >= 100 ? 'text-emerald-600' : 'text-slate-800' }}">
                    {{ $ach }}<span class="text-sm text-slate-400 ml-0.5">%</span>
                </p>
            </div>
            <div class="w-px h-10 bg-slate-200"></div>
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Total Actual</p>
                <p class="text-3xl font-black text-blue-600 tracking-tighter leading-none">
                    {{ number_format($totalAct) }}<span class="text-xs text-slate-400 ml-1">PCS</span>
                </p>
            </div>
            <div class="w-px h-10 bg-slate-200"></div>
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <p class="text-[9px] text-emerald-500 font-black uppercase">OK</p>
                    <p class="text-lg font-black text-emerald-600">{{ number_format($job->dailyProduction?->actual_ok ?? 0) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-orange-500 font-black uppercase">Repair</p>
                    <p class="text-lg font-black text-orange-600">{{ number_format($job->dailyProduction?->actual_repair ?? 0) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-red-500 font-black uppercase">Reject</p>
                    <p class="text-lg font-black text-red-600">{{ number_format($job->dailyProduction?->actual_reject ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- LOG TABLE --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="font-black text-slate-800 uppercase tracking-widest text-sm">Detail Input Per Operator</h2>
                    <p class="text-xs text-slate-500 font-medium">{{ $logs->total() }} entries — semua input OK, Repair & Reject</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest w-10">#</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu Input</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tipe</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">OK</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Repair</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Reject</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Defect / Keterangan</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @php $cumulOk = 0; @endphp
                    @forelse($logs as $log)
                        @php $cumulOk += $log['ok_qty'] ?? 0; @endphp
                    <tr class="hover:bg-blue-50/30 transition-all group
                        @if(($log['source'] ?? '') === 'repair') border-l-4 border-l-orange-400
                        @elseif(($log['source'] ?? '') === 'reject') border-l-4 border-l-red-400
                        @else border-l-4 border-l-blue-400 @endif">
                        <td class="px-6 py-4 text-[10px] font-bold text-slate-400">{{ ($logs->currentPage() - 1) * $logs->perPage() + $loop->iteration }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="font-black text-slate-800 text-sm tabular-nums">{{ ($log['created_at'] ?? now())->format('H:i:s') }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase">{{ ($log['created_at'] ?? now())->format('d M Y') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @if(($log['source'] ?? '') === 'repair')
                                <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-[9px] font-black uppercase">Repair</span>
                            @elseif(($log['source'] ?? '') === 'reject')
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[9px] font-black uppercase">Reject</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[9px] font-black uppercase">Input OK</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(($log['ok_qty'] ?? 0) > 0)
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 font-black text-xs tabular-nums">+{{ $log['ok_qty'] }}</span>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(($log['repair_qty'] ?? 0) > 0)
                                <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-600 font-black text-xs tabular-nums">{{ $log['repair_qty'] }}</span>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(($log['reject_qty'] ?? 0) > 0)
                                <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-600 font-black text-xs tabular-nums">{{ $log['reject_qty'] }}</span>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($log['defect_name'] ?? null)
                                <p class="text-xs font-bold text-orange-600">{{ $log['defect_name'] }}</p>
                            @else
                                <span class="text-[10px] text-slate-400">Input produksi OK</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[9px] font-black text-slate-500">{{ strtoupper(substr($log['operator'] ?? 'S', 0, 1)) }}</div>
                                <span class="text-[10px] font-bold text-slate-600">{{ $log['operator'] ?? 'System' }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-slate-400 font-bold text-sm">Belum ada rekam jejak untuk item ini hari ini.</p>
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
