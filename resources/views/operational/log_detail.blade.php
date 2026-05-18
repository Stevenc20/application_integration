@extends('layouts.supervisor')

@section('title', 'Detail Rekam Jejak - ' . ($job->job_number ?? 'Unknown'))

@section('content')
<div class="space-y-6">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
        <div class="flex items-center gap-4">
            <a href="{{ route('operational.input_harian') }}" class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest">Job Master</span>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tighter">{{ $job->job_number ?? '-' }}</h1>
                </div>
                <p class="text-sm text-slate-500 font-medium">{{ $job->job_name ?? 'N/A' }} <span class="mx-2 text-slate-300">|</span> Line: {{ $job->line_name ?? '-' }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-6 pr-4">
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Total Achievement</p>
                <p class="text-3xl font-black text-slate-800 tracking-tighter leading-none">
                    {{ number_format($job->dailyProduction?->efficiency ?? 0, 1) }}<span class="text-sm text-slate-400 ml-0.5">%</span>
                </p>
            </div>
            <div class="w-px h-10 bg-slate-200"></div>
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Actual OK</p>
                <p class="text-3xl font-black text-blue-600 tracking-tighter leading-none">
                    {{ number_format($job->dailyProduction?->actual_qty ?? 0) }}<span class="text-xs text-slate-400 ml-1">PCS</span>
                </p>
            </div>
        </div>
    </div>

    {{-- MAIN LOG TABLE --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="font-black text-slate-800 uppercase tracking-widest text-sm">Rekam Jejak Produksi Lengkap</h2>
                    <p class="text-xs text-slate-500 font-medium">Audit trail setiap aktivitas input data</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 uppercase mr-2">Status:</span>
                <span class="px-3 py-1 rounded-full bg-green-500 text-white text-[10px] font-black tracking-widest uppercase shadow-lg shadow-green-900/20">Active Session</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu Input</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty OK (Pcs)</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty Repair</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty Reject</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Operator PIC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 transition-all group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="font-black text-slate-800 text-sm tracking-tight leading-none mb-1">{{ $log->created_at->format('H:i:s') }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $log->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="px-3 py-1 rounded-lg bg-blue-50 text-blue-600 font-black text-xs shadow-sm">+{{ $log->ok_qty }}</span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="text-orange-500 font-bold text-xs">{{ $log->repair_qty }}</span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="text-red-500 font-bold text-xs">{{ $log->reject_qty }}</span>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex flex-col items-end">
                                <span class="font-black text-slate-700 text-xs uppercase tracking-tight">System User</span>
                                <span class="text-[9px] text-slate-400 font-bold italic">Manual Input via Dashboard</span>
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
                                <p class="text-slate-400 font-bold text-sm">Belum ada rekam jejak untuk item ini hari ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div class="px-8 py-6 border-t border-gray-100 bg-slate-50/50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
