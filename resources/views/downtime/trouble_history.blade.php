@extends('layouts.supervisor')
@section('title', 'Downtime & Trouble History')

@section('content')
<div class="space-y-6">
    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Downtime & Trouble History</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi seluruh hambatan produksi secara global</p>
        </div>
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2.5 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700">{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</span>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="p-5">
            <form method="GET" action="{{ route('downtime.history') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5 ml-1">Pilih Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition bg-white">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5 ml-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job, Problem, PIC..."
                            class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition bg-white">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition-all shadow-md">Filter Data</button>
                    <a href="{{ route('downtime.history') }}" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 font-bold text-sm transition-all flex items-center justify-center">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- MAIN DATA TABLE --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Job Information</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Jenis</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Problem / Penyebab</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tindakan (Action)</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">PIC</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Waktu & Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($downtimes as $dt)
                        @php
                            $isMissing = !$dt->problem || trim($dt->problem) === '-' || str_contains($dt->problem, '(Shortcut)');
                            $startTime = \Carbon\Carbon::parse($dt->start_time);
                            $finishTime = $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time) : null;
                            $dur = $dt->duration_seconds;
                            $durStr = $dur >= 60 ? floor($dur / 60) . 'm ' . ($dur % 60) . 's' : $dur . 's';
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors {{ $isMissing ? 'bg-red-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-wider">{{ $dt->jobMaster->job_number }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 mt-0.5">{{ $dt->jobMaster->line }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest 
                                    @if(strtolower($dt->jenis_downtime) == 'produksi') bg-blue-100 text-blue-700 
                                    @elseif(strtolower($dt->jenis_downtime) == 'mesin') bg-red-100 text-red-700
                                    @elseif(strtolower($dt->jenis_downtime) == 'dies') bg-orange-100 text-orange-700
                                    @else bg-slate-100 text-slate-600 @endif">
                                    {{ $dt->jenis_downtime }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold {{ $isMissing ? 'text-red-600' : 'text-slate-800' }}">
                                            {{ $dt->problem ?? '-' }}
                                        </span>
                                        @if($isMissing)
                                            <span class="bg-red-100 text-red-600 text-[8px] px-1.5 py-0.5 rounded-full font-black animate-pulse">BELUM LENGKAP</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-0.5">{{ $dt->penyebab ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-slate-600 line-clamp-2 max-w-xs">{{ $dt->action ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">
                                        {{ substr($dt->pic ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-xs font-medium text-slate-700">{{ $dt->pic ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-[11px] font-mono font-bold text-slate-700">{{ $startTime->format('H:i') }} - {{ $finishTime ? $finishTime->format('H:i') : '--:--' }}</span>
                                    <span class="text-[10px] font-black text-primary-red mt-1 bg-red-50 px-2 py-0.5 rounded-md">{{ $durStr }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-slate-800 font-bold">Tidak Ada Data Downtime</h3>
                                    <p class="text-slate-500 text-sm mt-1">Coba ubah filter tanggal atau kata kunci pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- PAGINATION --}}
        @if($downtimes->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $downtimes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
