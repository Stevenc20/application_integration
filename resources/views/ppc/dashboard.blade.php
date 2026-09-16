@extends('layouts.ppc')

@section('title', 'PPC Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Hero Header --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">PPC DASHBOARD</h1>
                    <p class="text-rose-200 text-sm font-semibold flex items-center gap-2 mt-1">
                        <span class="inline-block w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        Production Planning & Control &bull; {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">User</p>
                    <p class="text-white font-bold text-sm">{{ auth()->user()->name ?? 'PPC' }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">Waktu</p>
                    <p id="liveClock" class="text-white font-mono font-bold text-sm">--:--:--</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recovery Alert Banner --}}
    @if($recoveryAlert)
    <a href="{{ route('ppc.planning.production_plan') }}" class="block group">
        <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl px-6 py-5 shadow-lg shadow-amber-200/50 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 800 200" fill="none"><circle cx="750" cy="100" r="120" fill="white"/><circle cx="50" cy="50" r="80" fill="white"/></svg>
            </div>
            <div class="relative flex items-center gap-5">
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center ring-1 ring-white/30 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white font-black text-sm tracking-tight">
                        <span class="bg-white/20 rounded-lg px-2 py-0.5 text-white mr-2">{{ $recoveryAlert['total'] }}</span>
                        Item Recovery Menunggu Persetujuan
                    </p>
                    <p class="text-amber-100 text-xs font-semibold mt-1">
                        Dari {{ implode(', ', $recoveryAlert['presses']) }} 
                        &bull; Klik untuk langsung ke Production Plan
                    </p>
                </div>
                <div class="flex items-center gap-2 text-white/80 group-hover:text-white transition-colors shrink-0">
                    <span class="text-xs font-black uppercase tracking-wider">Lihat</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>
    </a>
    @endif

    {{-- Summary Statistics --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Plans Today --}}
        <div class="group bg-white rounded-2xl border border-rose-100 p-5 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg shadow-rose-200/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest bg-rose-50 px-2.5 py-1 rounded-full">Hari Ini</span>
            </div>
            <p class="text-3xl font-black text-slate-800 tracking-tight" id="statTotalPlans">{{ $totalPlans }}</p>
            <p class="text-xs font-bold text-slate-400 mt-1">Total Rencana Produksi</p>
        </div>

        {{-- Card 2: Running --}}
        <div class="group bg-white rounded-2xl border border-emerald-100 p-5 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-200/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
            </div>
            <p class="text-3xl font-black text-emerald-600 tracking-tight" id="statRunning">{{ $running }}</p>
            <p class="text-xs font-bold text-slate-400 mt-1">Sedang Berjalan</p>
        </div>

        {{-- Card 3: Completed --}}
        <div class="group bg-white rounded-2xl border border-sky-100 p-5 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-sky-200/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[10px] font-black text-sky-400 uppercase tracking-widest bg-sky-50 px-2.5 py-1 rounded-full">Selesai</span>
            </div>
            <p class="text-3xl font-black text-sky-600 tracking-tight" id="statCompleted">{{ $completed }}</p>
            <p class="text-xs font-bold text-slate-400 mt-1">Sudah Selesai</p>
        </div>

        {{-- Card 4: Pending --}}
        <div class="group bg-white rounded-2xl border border-amber-100 p-5 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-200/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest bg-amber-50 px-2.5 py-1 rounded-full">Pending</span>
            </div>
            <p class="text-3xl font-black text-amber-600 tracking-tight" id="statPending">{{ $pending }}</p>
            <p class="text-xs font-bold text-slate-400 mt-1">Menunggu Approval</p>
        </div>
    </div>

    {{-- Recovery Summary Widget --}}
    <div class="bg-white rounded-2xl border border-amber-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-700 tracking-tight">RECOVERY SUMMARY</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Qty: {{ number_format($recoverySummary['total_qty']) }} pcs</p>
                </div>
            </div>
            <a href="{{ route('ppc.planning.recovery.index') }}"
               class="text-[11px] font-black text-amber-600 hover:text-amber-700 flex items-center gap-1 transition-colors">
                LIHAT SEMUA
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-5 gap-3">
            <div class="bg-amber-50 rounded-xl px-3 py-2.5 text-center">
                <p class="text-lg font-black text-amber-700">{{ $recoverySummary['pending'] }}</p>
                <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Pending</p>
            </div>
            <div class="bg-sky-50 rounded-xl px-3 py-2.5 text-center">
                <p class="text-lg font-black text-sky-700">{{ $recoverySummary['approved'] }}</p>
                <p class="text-[10px] font-bold text-sky-500 uppercase tracking-wider">Approved</p>
            </div>
            <div class="bg-emerald-50 rounded-xl px-3 py-2.5 text-center">
                <p class="text-lg font-black text-emerald-700">{{ $recoverySummary['scheduled'] }}</p>
                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Scheduled</p>
            </div>
            <div class="bg-slate-50 rounded-xl px-3 py-2.5 text-center">
                <p class="text-lg font-black text-slate-700">{{ $recoverySummary['completed'] }}</p>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Completed</p>
            </div>
            <div class="bg-rose-50 rounded-xl px-3 py-2.5 text-center">
                <p class="text-sm font-black text-rose-700 truncate" title="{{ number_format($recoverySummary['total_qty']) }}">{{ number_format($recoverySummary['total_qty'], 0) }}</p>
                <p class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">Total Qty</p>
            </div>
        </div>
        @if($recoverySummary['by_press']->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($recoverySummary['by_press'] as $rp)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-[10px] font-bold">
                {{ $rp->press_name }}
                <span class="px-1.5 py-0.5 bg-amber-200 rounded-full text-[9px]">{{ $rp->total }} item ({{ number_format($rp->qty) }} pcs)</span>
            </span>
            @endforeach
        </div>
        @endif
    </div>

    @include('components.grafik-gsph')

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Quick Actions Panel --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-rose-50 to-red-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-rose-600 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-700 tracking-tight">Aksi Cepat</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Quick Actions</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('ppc.planning.production_plan') }}" class="flex items-center gap-4 p-4 rounded-xl hover:bg-rose-50 transition-all group border border-transparent hover:border-rose-100">
                        <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition-colors">Production Plan</p>
                            <p class="text-[11px] text-slate-400 font-medium">Kelola jadwal produksi harian</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300 group-hover:text-rose-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <a href="{{ route('master.job') }}" class="flex items-center gap-4 p-4 rounded-xl hover:bg-rose-50 transition-all group border border-transparent hover:border-rose-100">
                        <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition-colors">Job Master</p>
                            <p class="text-[11px] text-slate-400 font-medium">Database master pekerjaan</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300 group-hover:text-rose-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <a href="{{ route('monitoring.line') }}" class="flex items-center gap-4 p-4 rounded-xl hover:bg-rose-50 transition-all group border border-transparent hover:border-rose-100">
                        <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition-colors">Line Monitoring</p>
                            <p class="text-[11px] text-slate-400 font-medium">Pantau status line produksi</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300 group-hover:text-rose-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <a href="{{ route('supervisor.reports.daily_production') }}" class="flex items-center gap-4 p-4 rounded-xl hover:bg-rose-50 transition-all group border border-transparent hover:border-rose-100">
                        <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center group-hover:bg-rose-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition-colors">Daily Report</p>
                            <p class="text-[11px] text-slate-400 font-medium">Laporan produksi harian</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300 group-hover:text-rose-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
            </div>

            {{-- System Info --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h3 class="text-sm font-black text-slate-700 tracking-tight">Informasi Sistem</h3>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Role</span>
                        <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black uppercase">PPC</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status</span>
                        <span class="flex items-center gap-1.5 text-xs font-bold text-emerald-600">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> Online
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Versi</span>
                        <span class="text-xs font-bold text-slate-600">v2.0.0</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Production Overview Panel --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden h-full">
                <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-rose-50/30">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-700 tracking-tight">Overview Produksi</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Per Press - Hari Ini</p>
                            </div>
                        </div>
                        <a href="{{ route('ppc.planning.production_plan') }}" class="text-[11px] font-black text-rose-600 hover:text-rose-700 flex items-center gap-1 transition-colors">
                            LIHAT SEMUA
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>
                </div>

                <div class="p-6">
                    {{-- Press Cards Grid --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        @foreach(['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D'] as $idx => $press)
                        @php
                            $colors = [
                                ['from-rose-500', 'to-red-600', 'bg-rose-50', 'text-rose-700', 'border-rose-200', 'shadow-rose-100'],
                                ['from-red-500', 'to-rose-600', 'bg-red-50', 'text-red-700', 'border-red-200', 'shadow-red-100'],
                                ['from-rose-600', 'to-red-700', 'bg-rose-50', 'text-rose-800', 'border-rose-200', 'shadow-rose-100'],
                                ['from-red-600', 'to-rose-700', 'bg-red-50', 'text-red-800', 'border-red-200', 'shadow-red-100'],
                            ];
                            $c = $colors[$idx];
                        @endphp
                        <a href="{{ route('ppc.planning.production_plan', ['press' => $press]) }}" class="group block p-5 rounded-2xl border {{ $c[4] }} {{ $c[2] }} hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-9 h-9 bg-gradient-to-br {{ $c[0] }} {{ $c[1] }} rounded-xl flex items-center justify-center shadow-md {{ $c[5] }}">
                                    <span class="text-white text-[11px] font-black">{{ chr(65 + $idx) }}</span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-300 group-hover:text-rose-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                            <h4 class="text-sm font-black {{ $c[3] }} tracking-tight">{{ $press }}</h4>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Klik untuk lihat jadwal</p>
                        </a>
                        @endforeach
                    </div>

                    {{-- Info Banner --}}
                    <div class="bg-gradient-to-r from-rose-50 to-red-50 rounded-2xl p-5 border border-rose-100">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-rose-800">Modul PPC Aktif</h4>
                                <p class="text-xs text-rose-600/80 mt-1 leading-relaxed">Dashboard ini terhubung langsung ke modul <strong>Production Planning</strong>. Gunakan menu <strong>Planning → Production Plan</strong> di sidebar untuk mengelola jadwal produksi, import data Excel, dan memonitor progres secara real-time.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live Clock
    function updateClock() {
        const el = document.getElementById('liveClock');
        if (el) {
            el.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Counter Animation
    const animateValue = (id, start, end, duration) => {
        const obj = document.getElementById(id);
        if (!obj) return;
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };

    // Trigger animations with PHP values
    animateValue("statTotalPlans", 0, {{ $totalPlans }}, 1500);
    animateValue("statRunning", 0, {{ $running }}, 1500);
    animateValue("statCompleted", 0, {{ $completed }}, 1500);
    animateValue("statPending", 0, {{ $pending }}, 1500);
});
</script>
@endpush
@endsection
