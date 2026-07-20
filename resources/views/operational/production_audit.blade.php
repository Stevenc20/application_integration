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
                <p class="text-sm text-slate-500 font-medium">Rekapitulasi riwayat pengerjaan item dan log audit harian</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200 text-slate-600 text-sm font-bold flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                {{ $jobs->total() }} Total Jobs Recorded
            </div>
        </div>
    </div>

    {{-- AUDIT LIST --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Item / Job Info</th>
                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu Mulai</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Target</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Actual OK</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Achievement</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($jobs as $job)
                    <tr class="hover:bg-slate-50 transition-all group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-black text-xs group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    {{ substr($job->line ?? '?', 0, 6) }}
                                </div>
                                <div>
                                    <p class="font-black text-slate-800 text-sm tracking-tight leading-none mb-1">{{ $job->job_number }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">{{ $job->job_name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="font-black text-slate-700 text-xs">{{ $job->started_at ? \Carbon\Carbon::parse($job->started_at)->format('H:i:s') : '-' }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase">{{ $job->started_at ? \Carbon\Carbon::parse($job->started_at)->format('d M Y') : '-' }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span class="font-black text-slate-400 text-sm">{{ number_format($job->capacity) }}</span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span class="font-black text-blue-600 text-sm">{{ number_format($job->dailyProduction?->actual_qty ?? 0) }}</span>
                        </td>
                        <td class="px-8 py-6 text-center">
                            @php
                                $eff = $job->dailyProduction?->efficiency ?? 0;
                                $colorClass = $eff >= 100 ? 'bg-green-500' : ($eff >= 80 ? 'bg-blue-500' : 'bg-amber-500');
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-12 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $colorClass }}" style="width: {{ min(100, $eff) }}%"></div>
                                </div>
                                <span class="text-[10px] font-black {{ str_replace('bg-', 'text-', $colorClass) }}">{{ number_format($eff, 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <a href="{{ route('operational.job.logs.detail', $job->id) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-900 hover:text-white transition-all text-xs font-black uppercase tracking-widest group/btn">
                                Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-slate-400 font-bold text-sm">Belum ada data pengerjaan job hari ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($jobs->hasPages())
        <div class="px-8 py-6 border-t border-slate-50 bg-slate-50/30">
            {{ $jobs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
