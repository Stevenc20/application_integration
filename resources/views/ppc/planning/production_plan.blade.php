@extends('layouts.ppc')
@section('title', 'Production Planning')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-gradient-to-r from-red-900 via-red-800 to-rose-700 px-6 py-5 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mx-auto max-w-screen-2xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 backdrop-blur rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">PRODUCTION PLANNING</h1>
                    <p class="text-slate-300 text-[10px] font-semibold flex flex-wrap items-center gap-2 mt-0.5">
                        <span class="inline-block w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                        {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }} &bull; {{ $currentPress }} &bull; 
                        <span class="text-white font-black px-1.5 py-0.5 bg-white/10 rounded">{{ $totalJobs }} ITEMS</span>
                        <span class="mx-1 text-white/30">|</span>
                        <span class="text-emerald-400 font-bold uppercase tracking-tighter">Last Import: {{ $activeFilters['last_import'] }}</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button onclick="PlanningEngine.openImportModal()" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    IMPORT EXCEL
                </button>

                <div class="w-px h-6 bg-white/20"></div>

                <form id="filterForm" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                           class="bg-white/10 border-white/20 text-white text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-white/30 placeholder-slate-300 transition-all">
                    <select name="status" onchange="this.form.submit()"
                            class="bg-white/10 border-white/20 text-white text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-white/30 transition-all">
                        <option value="" class="text-slate-800">SEMUA STATUS</option>
                        <option value="pending" class="text-slate-800" {{ request('status') === 'pending' ? 'selected' : '' }}>PENDING</option>
                        <option value="approved" class="text-slate-800" {{ request('status') === 'approved' ? 'selected' : '' }}>APPROVED</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Press & Shift Selector --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 mx-auto max-w-screen-2xl">
            <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl ring-1 ring-white/20">
                @foreach(['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D', 'ALL'] as $p)
                <a href="{{ route('ppc.planning.production_plan', ['press' => $p, 'date' => $date, 'shift' => $currentShift, 'status' => request('status')]) }}" 
                   class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ ($currentPress ?? 'PRESS A') === $p ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                    {{ $p }}
                </a>
                @endforeach
            </div>

            <div class="w-px h-6 bg-white/20"></div>

            <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl ring-1 ring-white/20">
                {{-- 1. All Shifts Button --}}
                <a href="{{ route('ppc.planning.production_plan', ['press' => $currentPress, 'date' => $date, 'shift' => 'ALL']) }}" 
                   class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ $currentShift === 'ALL' ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                    SEMUA SHIFT
                </a>

                {{-- 2. Dynamic Shifts (Pagi, Malam, and Revisions) --}}
                @php
                    // Sort shifts: standard Pagi/Malam first, then others
                    $sortedShifts = collect($availableShifts)->unique()->sort(function($a, $b) {
                        if (str_contains(strtoupper($a), 'PAGI')) return -1;
                        if (str_contains(strtoupper($b), 'PAGI')) return 1;
                        return strcmp($a, $b);
                    });
                @endphp

                @foreach($sortedShifts as $s)
                    @if(!empty($s))
                    <a href="{{ route('ppc.planning.production_plan', ['press' => $currentPress, 'date' => $date, 'shift' => $s, 'status' => request('status')]) }}" 
                       class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ $currentShift === $s ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                        {{ strtoupper($s) }}
                    </a>
                    @endif
                @endforeach

                {{-- 3. Fallback if no data imported yet --}}
                @if(count($availableShifts) == 0)
                    @foreach(['Pagi', 'Malam'] as $s)
                    <a href="{{ route('ppc.planning.production_plan', ['press' => $currentPress, 'date' => $date, 'shift' => $s]) }}" 
                       class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ $currentShift === $s ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                        SHIFT {{ strtoupper($s) }}
                    </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>


    {{-- Main Content Table --}}
    <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white custom-scrollbar shadow-sm">
        <table class="w-full table-auto border-collapse text-left">
            <thead>
                <tr class="bg-slate-900 whitespace-nowrap">
                    {{-- Group: Identity --}}
                    <th class="px-3 py-4 text-[9px] font-black text-slate-500 uppercase tracking-widest text-center border-r border-slate-700 min-w-[50px]">#</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[250px]">Job Master</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Type</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Qty/Plt</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Tot. Plt</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[180px]">Job No.</th>
                    {{-- Group: Output Monitoring --}}
                    <th class="px-3 py-4 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Plan</th>
                    <th class="px-3 py-4 text-[9px] font-black text-emerald-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">OK</th>
                    <th class="px-3 py-4 text-[9px] font-black text-yellow-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Repair</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Reject</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Balance</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Mesin</th>
                    {{-- Group: Time Metrics --}}
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">CT"</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Proc.Time</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Reg Act</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">DCT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">MCT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Plan DCT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">TPT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[100px]">GSPH</th>
                    {{-- Group: Schedule --}}
                    <th class="px-3 py-4 text-[9px] font-black text-violet-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Start</th>
                    <th class="px-3 py-4 text-[9px] font-black text-violet-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Finish</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[250px]">Keterangan</th>
                    {{-- Group: Reject --}}
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-1</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-2</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-3</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-4</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">DT</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center min-w-[120px]">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php
                    // Calculate starting number based on pagination
                    $jobNo = ($plans->currentPage() - 1) * $plans->perPage() + 1;
                @endphp
                @forelse($plans as $plan)
                    @php 
                        $jobMaster = trim($plan->job_master ?? '');
                        $combined = strtoupper(($plan->job_no ?? '') . ' ' . $jobMaster . ' ' . ($plan->keterangan ?? ''));
                        
                        // 1. SKIP GHOST ROWS (UI Shield)
                        if (empty($jobMaster) || in_array($jobMaster, ['—', '0'])) {
                            continue;
                        }

                        $isBreak = ($plan->row_type === 'break') || 
                                   ($plan->row_no === '—' || empty($plan->row_no)) ||
                                   str_contains($combined, 'ISTIRAHAT') || 
                                   str_contains($combined, 'CINGKORAK') || 
                                   str_contains($combined, 'BREAK') ||
                                   str_contains($combined, 'JUMAT') ||
                                   str_contains($combined, 'SORE') ||
                                   str_contains($combined, 'MALAM');
                    @endphp
                            @if($isBreak && $plan->row_type !== 'total_finish')
                                @php
                                    $breakDesc = 'OPERATIONAL BREAK';
                                    $start = $plan->start_time;
                                    $finish = $plan->finish_time;
                                    $dct = (int) $plan->dct;

                                    if ($dct >= 45) {
                                        $isFriday = str_contains(strtoupper($plan->hari ?? ''), 'JUMAT');
                                        $breakDesc = $isFriday ? 'ISTIRAHAT JUMAT' : 'ISTIRAHAT SIANG';
                                    } elseif ($dct == 15) {
                                        $breakDesc = ($start >= '15:00' && $finish <= '16:00') ? 'BREAKTIME' : 'CINGKORAK';
                                    } elseif ($dct == 30) {
                                        $breakDesc = 'ISTIRAHAT SORE';
                                    } elseif ($dct == 10) {
                                        $breakDesc = 'CLEANING / 5S';
                                    }

                                    if (!empty($plan->job_master) && !in_array($plan->job_master, ['0', '—', '', 'None'])) {
                                        $breakDesc = $plan->job_master;
                                    }

                                    // Helper for solid break row cells
                                    $breakCell = 'bg-amber-50/40 border-r border-amber-100/50';

                                    // 2. SMART BREAK CHECK: Only show if there's a production item after this
                                    $nextPlan = $plans[$loop->index + 1] ?? null;
                                    $showBreak = $nextPlan && !empty(trim($nextPlan->job_master ?? '')) && !in_array($nextPlan->job_master, ['0', '—']);
                                @endphp

                                @if(!$showBreak) @continue @endif

                                <tr class="border-l-4 border-l-amber-400 border-y border-amber-200/50 transition-none">
                                    {{-- 1: No --}}
                                    <td class="px-3 py-3 text-center text-[10px] font-bold text-amber-600/50 {{ $breakCell }}">—</td>

                                    {{-- 2-20: Label Istirahat (19 Columns) --}}
                                    <td colspan="19" class="px-6 py-3 {{ $breakCell }}">
                                        <div class="flex items-center gap-6">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse border-2 border-white shadow-sm"></span>
                                                <span class="text-[10px] font-black text-amber-900 uppercase tracking-widest">{{ $breakDesc }}</span>
                                            </div>

                                            {{-- THE CLEAN BADGE (Simplified) --}}
                                            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/50 border border-amber-200 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-[10px] font-black text-amber-700 uppercase tracking-tighter">{{ (int)$plan->dct ?: '0' }} MINS</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 21: Start Time (Amber Pill Style) --}}
                                    <td class="px-2 py-3 text-center bg-amber-50/40 border-r border-amber-100/50">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/40 border border-amber-200/50 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-[10px] font-black text-amber-700 tracking-tight">{{ $plan->start_time ?: '—' }}</span>
                                        </div>
                                    </td>

                                    {{-- 22: Finish Time (Amber Pill Style) --}}
                                    <td class="px-2 py-3 text-center bg-amber-50/40 border-r border-amber-100/50">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/40 border border-amber-200/50 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-[10px] font-black text-amber-700 tracking-tight">{{ $plan->finish_time ?: '—' }}</span>
                                        </div>
                                    </td>

                                    {{-- 23: Keterangan --}}
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>

                                    {{-- 24-27: A1-A4 --}}
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>

                                    {{-- 28: DT (Aligned & Styled) --}}
                                    <td class="px-3 py-3 text-center bg-amber-50/40 border-r border-amber-100/50">
                                        <span class="font-black text-amber-800 text-[11px]">{{ (int)$plan->dct ?: '0' }}</span>
                                    </td>

                                    {{-- 29: Status --}}
                                    <td class="bg-amber-50/40"></td>
                                </tr>
                                @continue
                            @elseif($plan->row_type === 'total_finish')
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->dct ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-black text-amber-700 border-r border-slate-100">{{ $plan->tpt ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->process_time ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->reg_active ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->mct ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->gsph_item ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-mono text-xs font-black text-indigo-700 border-r border-slate-100 bg-indigo-50/30">{{ $plan->start_time }}</td>
                                    <td class="px-3 py-3 text-center font-mono text-xs font-black text-indigo-700 border-r border-slate-100 bg-indigo-50/30">{{ $plan->finish_time }}</td>
                                    <td colspan="6" class="px-4 py-3 border-r border-slate-100 text-slate-400 text-[10px] text-center italic">{{ $plan->keterangan ?: '—' }}</td>
                                    <td class="px-4 py-3 text-center border-l border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->dt_menit ?: '—' }}</td>
                                </tr>
                            @else
                                <tr class="hover:bg-blue-50/30 transition-colors border-b border-slate-100 group">
                                    <td class="px-3 py-3 text-center text-[11px] font-bold text-slate-400 border-r border-slate-100">{{ $jobNo++ }}</td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        <input type="text" value="{{ $plan->job_master }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'job_master', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-slate-800 p-0">
                                    </td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-indigo-100 text-indigo-700 uppercase tracking-tighter">{{ $plan->type_plt }}</span></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" value="{{ $plan->qty_plt }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'qty_plt', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">{{ round($plan->total_plt) }}</td>
                                    <td class="px-4 py-3 border-r border-slate-100"><input type="text" value="{{ $plan->job_no }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'job_no', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-medium text-slate-600 p-0"></td>
                                    {{-- Group: Output Monitoring (Interactive) --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-amber-50/20"><input type="number" value="{{ $plan->plan }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'plan', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-amber-600 text-center p-0"></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-emerald-50/20">
                                        <span class="text-sm font-black text-emerald-600 tracking-tight">{{ number_format($plan->ok ?? 0) }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-yellow-50/20">
                                        <span class="text-sm font-black text-yellow-600 tracking-tight">{{ number_format($plan->repair ?? 0) }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-rose-50/20">
                                        <span class="text-sm font-black text-rose-600 tracking-tight">{{ number_format($plan->reject ?? 0) }}</span>
                                    </td>
                                    
                                    {{-- Calculation: Balance (Plan - OK - Reject) --}}
                                    @php 
                                        $balance = ($plan->plan ?? 0) - ($plan->ok ?? 0) - ($plan->reject ?? 0);
                                        $balanceColor = $balance > 0 ? 'text-slate-600' : ($balance < 0 ? 'text-indigo-600' : 'text-slate-300');
                                    @endphp
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-black text-sm {{ $balanceColor }}">{{ number_format($balance) }}</td>

                                    {{-- 9: Mesin --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" value="{{ $plan->total_mesin }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'total_mesin', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    
                                    {{-- 10: CT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" step="0.1" value="{{ $plan->ct_detik }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'ct_detik', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    
                                    {{-- 11: Process Time --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">{{ number_format($plan->process_time, 1) }}</td>
                                    
                                    {{-- 12: Reg Active --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" value="{{ $plan->reg_active }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'reg_active', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    
                                    {{-- 13: DCT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">{{ $plan->dct ?: '0' }}</td>
                                    
                                    {{-- 14: MCT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">{{ $plan->mct ?: '0' }}</td>
                                    
                                    {{-- 15: Plan DCT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-amber-600 text-sm bg-amber-50/30">{{ $plan->plan_dct ?: '0' }}</td>
                                    
                                    {{-- 16: TPT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-black text-amber-700 text-sm">{{ $plan->tpt ?: '0' }}</td>
                                    
                                    {{-- 17: GSPH --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">{{ number_format($plan->gsph_item) }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600">{{ $plan->start_time }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600">{{ $plan->finish_time }}</td>
                                    <td class="px-4 py-3 border-r border-slate-100"><input type="text" value="{{ $plan->keterangan }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'keterangan', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-xs font-medium text-slate-500 p-0"></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a1 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a2 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a3 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a4 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->dt_menit ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        @php
                                            $totalOutput = ($plan->ok ?? 0) + ($plan->repair ?? 0) + ($plan->reject ?? 0);
                                            $planQty = $plan->plan ?? 0;
                                            
                                            if ($totalOutput == 0) {
                                                $statusLabel = 'PENDING';
                                                $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
                                                $dotColor = 'bg-amber-500';
                                            } elseif ($totalOutput < $planQty) {
                                                $statusLabel = 'RUNNING';
                                                $statusColor = 'bg-blue-100 text-blue-700 border-blue-200';
                                                $dotColor = 'bg-blue-500 animate-pulse';
                                            } else {
                                                $statusLabel = 'DONE';
                                                $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                                $dotColor = 'bg-emerald-500';
                                            }
                                        @endphp
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border {{ $statusColor }} shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                            <span class="text-[9px] font-black tracking-widest">{{ $statusLabel }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="26" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <button onclick="PlanningEngine.openImportModal()" class="mt-6 px-6 py-2.5 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-700 transition-all">
                                            Mulai Import
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- IN-TABLE TOTAL FINISH ROW --}}
                        @if($totalFinishRow)
                        <tr class="bg-emerald-50 border-t-2 border-emerald-200">
                            {{-- 1: No --}}
                            <td class="px-3 py-4 text-center text-emerald-600 font-black">—</td>
                            
                            {{-- 2-6: Label TOTAL FINISH --}}
                            <td colspan="5" class="px-4 py-4 font-black text-emerald-700 text-sm uppercase tracking-wider text-center border-r border-emerald-100">TOTAL FINISH</td>
                            
                            {{-- 7: Total Plan --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 bg-amber-50/30">{{ number_format($totalFinishRow->plan) }}</td>
                            
                            {{-- 8: Total OK --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 bg-emerald-50/30">{{ number_format($totalFinishRow->ok) }}</td>

                            {{-- 9: Total Repair --}}
                            <td class="px-3 py-4 text-center font-black text-yellow-700 border-r border-emerald-100 bg-yellow-50/30">{{ number_format($totalFinishRow->repair) }}</td>

                            {{-- 10: Total Reject --}}
                            <td class="px-3 py-4 text-center font-black text-rose-700 border-r border-emerald-100 bg-rose-50/30">{{ number_format($totalFinishRow->reject) }}</td>

                            {{-- 11: Total Balance --}}
                            @php 
                                $totalBalance = ($totalFinishRow->plan ?? 0) - ($totalFinishRow->ok ?? 0);
                            @endphp
                            <td class="px-3 py-4 text-center font-black text-slate-700 border-r border-emerald-100 bg-slate-50">{{ number_format($totalBalance) }}</td>

                            {{-- 12: Total Mesin --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->total_mesin ?: '—' }}</td>

                            {{-- 10: Average CT --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-100/50">{{ number_format($totalFinishRow->ct_detik, 1) }}</td>

                            {{-- 11: Total Proc Time --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ number_format($totalFinishRow->process_time, 1) }}</td>
                            
                            {{-- 12: Total Reg Act --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->reg_active ?: '0' }}</td>
                            
                            {{-- 13: Total DCT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->dct ?: '0' }}</td>
                            
                            {{-- 14: Total MCT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->mct ?: '0' }}</td>
                            
                            {{-- 15: Total Plan DCT --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-50">{{ number_format($totalFinishRow->plan_dct) }}</td>
                            
                            {{-- 16: Total TPT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ number_format($totalFinishRow->tpt) }}</td>
                            
                            {{-- 17: Total GSPH --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-50">{{ number_format($totalFinishRow->gsph_item) }}</td>
                            
                            {{-- 18-19: Start/Finish --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-[10px]">{{ $totalFinishRow->start_time }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-[10px]">{{ $totalFinishRow->finish_time }}</td>
                            
                            {{-- 20: Keterangan --}}
                            <td class="px-4 py-4 border-r border-emerald-100 min-w-[260px]"></td>
                            
                            {{-- 20-23: A1-A4 --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a1 ?: '0' }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a2 ?: '0' }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a3 ?: '0' }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a4 ?: '0' }}</td>
                            
                            {{-- 24: DT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-50">{{ $totalFinishRow->dt_menit ?: '0' }}</td>
                            
                            {{-- 26: Status --}}
                            <td class="px-3 py-4 text-center">
                                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase">Summary</span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
    </div>
        
    {{-- Pagination Links --}}
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-3xl">
        {{ $plans->links() }}
    </div>

            {{-- Summary Cards — Reorganized into PLAN vs ACTUAL with REPAIR --}}
            @if($totalFinishRow)
            @php 
                $totalReject = ($totalFinishRow->a1 ?? 0) + ($totalFinishRow->a2 ?? 0) + ($totalFinishRow->a3 ?? 0) + ($totalFinishRow->a4 ?? 0);
                $totalBalance = ($totalFinishRow->plan ?? 0) - ($totalFinishRow->ok ?? 0);
                $targetGsph = ($totalFinishRow->tpt ?? 0) > 0 ? ($totalFinishRow->plan / ($totalFinishRow->tpt / 60)) : 0;
                $achievement = ($totalFinishRow->plan ?? 0) > 0 ? (($totalFinishRow->ok ?? 0) / $totalFinishRow->plan) * 100 : 0;
                $actualGsph = $totalFinishRow->gsph_item ?? 0;
                $totalRepair = $totalFinishRow->repair ?? 0;
            @endphp

            <div class="mt-8 px-8 py-10 bg-slate-50/50 border-t border-slate-100">
                <div class="flex flex-col xl:flex-row gap-10 items-start">
                    
                    {{-- SECTION 1: PLAN OVERVIEW --}}
                    <div class="flex-none xl:w-[350px] space-y-5 min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-6 bg-amber-500 rounded-full shadow-[0_0_8px_rgba(245,158,11,0.4)]"></div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Plan Overview</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            {{-- Total Plan --}}
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 group-hover:text-amber-500 transition-colors">Total Plan</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-slate-800">{{ number_format($totalFinishRow->plan) }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Pcs</span>
                                </div>
                            </div>

                            {{-- Total Balance --}}
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 group-hover:text-slate-600 transition-colors">Total Balance</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-slate-600">{{ number_format($totalBalance) }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Pcs</span>
                                </div>
                            </div>

                            {{-- Target GSPH --}}
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-4 border-b-amber-500 min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 group-hover:text-amber-600 transition-colors">Target GSPH</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-amber-600">{{ number_format($targetGsph, 1) }}</span>
                                    <span class="text-[10px] font-bold text-amber-400 uppercase">U/H</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Vertical Separator for Desktop --}}
                    <div class="hidden xl:block w-px bg-slate-200 self-stretch my-2"></div>

                    {{-- SECTION 2: ACTUAL PRODUCTION --}}
                    <div class="flex-1 space-y-6 min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-6 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.4)]"></div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Actual Production</h3>
                        </div>

                        <div class="flex flex-col gap-4">
                            {{-- Row 1: Small KPIs (OK, REPAIR, REJECT) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-2 border-b-emerald-500 min-w-0">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5 group-hover:text-emerald-500 transition-colors">Total OK</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-black text-emerald-600">{{ number_format($totalFinishRow->ok) }}</span>
                                        <span class="text-[9px] font-bold text-emerald-400 uppercase">Pcs</span>
                                    </div>
                                </div>

                                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-2 border-b-indigo-400 min-w-0">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5 group-hover:text-indigo-500 transition-colors">Total Repair</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-black text-indigo-600">{{ number_format($totalRepair) }}</span>
                                        <span class="text-[9px] font-bold text-indigo-400 uppercase">Pcs</span>
                                    </div>
                                </div>

                                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-2 border-b-rose-500 min-w-0">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5 group-hover:text-rose-500 transition-colors">Total Reject</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-black text-rose-600">{{ number_format($totalReject) }}</span>
                                        <span class="text-[9px] font-bold text-rose-400 uppercase">Pcs</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 2: Hero KPIs (ACHIEVEMENT, GSPH) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all group border-b-4 border-b-sky-500 min-w-0">
                                    <div class="flex justify-between items-start mb-3">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] group-hover:text-sky-500 transition-colors">Overall Achievement</p>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex items-baseline gap-2">
                                        @php $achColor = $achievement >= 100 ? 'text-indigo-600' : ($achievement >= 90 ? 'text-emerald-600' : 'text-amber-600'); @endphp
                                        <span class="text-4xl font-black {{ $achColor }} tracking-tight">{{ number_format($achievement, 1) }}%</span>
                                    </div>
                                    <div class="mt-4 w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full {{ str_replace('text', 'bg', $achColor) }} transition-all duration-1000" style="width: {{ min(100, $achievement) }}%"></div>
                                    </div>
                                </div>

                                <div class="bg-slate-900 p-6 rounded-3xl shadow-xl shadow-slate-200 group relative overflow-hidden min-w-0">
                                    <div class="absolute top-0 right-0 p-4 opacity-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <p class="text-[10px] font-black text-indigo-300 uppercase tracking-[0.2em] mb-3">Actual GSPH Rate</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl font-black text-white tracking-tight">{{ number_format($actualGsph) }}</span>
                                        <span class="text-xs font-bold text-indigo-400 uppercase">Unit/Hour</span>
                                    </div>
                                    <p class="text-[9px] font-bold text-indigo-300/50 mt-4 italic">Real-time production throughput</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

</div>

@push('modals')
{{-- MODAL AREA (Teleported outside all wrappers to prevent layout shifting) --}}
<div id="importModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="PlanningEngine.closeImportModal()"></div>
    <div class="relative w-full max-w-md p-6 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-8 py-8">
                <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">IMPORT JADWAL</h3>
                <p class="text-slate-500 text-sm mt-1">Upload file Excel (.xlsx, .xlsm) untuk sinkronisasi jadwal produksi.</p>

                <form id="importForm" action="/ppc/planning/production-plan/import" method="POST" enctype="multipart/form-data" class="mt-8">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilih File Excel</label>
                            <input type="file" name="excel_file" required accept=".xlsx,.xls,.xlsm"
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all border border-slate-200 rounded-xl p-1">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Tanggal Rencana</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="PlanningEngine.closeImportModal()" class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                            BATAL
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-black shadow-lg shadow-indigo-100 transition-all">
                            IMPORT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

<style>
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Status Picker Dropdown */
    .status-dropdown { animation: statusFadeIn 0.15s ease; transform-origin: top right; }
    @keyframes statusFadeIn {
        from { opacity: 0; transform: scale(0.92) translateY(-4px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
</style>

<script>
window.StatusPicker = {
    toggle: function(btn) {
        const picker = btn.closest('.status-picker');
        const dropdown = picker.querySelector('.status-dropdown');
        const isOpen = !dropdown.classList.contains('hidden');
        // Close all other open dropdowns first
        document.querySelectorAll('.status-dropdown').forEach(d => d.classList.add('hidden'));
        if (!isOpen) dropdown.classList.remove('hidden');
    },

    select: function(btn, status, bgColor, textColor, dotColor) {
        const picker = btn.closest('.status-picker');
        const planId = picker.dataset.planId;
        const badge = picker.querySelector('.status-badge');
        const dot = badge.querySelector('span.rounded-full');
        const label = badge.childNodes[2]; // text node between dot and chevron

        // Update badge style
        badge.style.backgroundColor = bgColor;
        badge.style.color = textColor;
        badge.style.ringColor = dotColor;
        dot.style.backgroundColor = dotColor;

        // Update label text
        const labels = { pending: 'Pending', approved: 'Approved', completed: 'Done' };
        badge.querySelector('span.rounded-full').nextSibling.textContent = ' ' + labels[status] + ' ';

        // Close dropdown
        picker.querySelector('.status-dropdown').classList.add('hidden');
        picker.dataset.current = status;

        // Save via API
        PlanningEngine.updateInline(planId, 'status', status);
    }
};

// Close on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.status-picker')) {
        document.querySelectorAll('.status-dropdown').forEach(d => d.classList.add('hidden'));
    }
});
</script>

@push('scripts')
@vite(['resources/js/ppc/planning.js'])
<script>
    document.addEventListener('DOMContentLoaded', function() {
        PlanningEngine.init({
            csrfToken: '{{ csrf_token() }}',
            inlineUrl: '{{ route('ppc.planning.production_plan.inline') }}',
            indexUrl: '{{ route('ppc.planning.production_plan') }}',
            currentPress: '{{ $currentPress }}'
        });
    });
</script>
@endpush
@endsection
@push('scripts')
<script>
    // 1. Auto Refresh setiap 5 menit (300.000 ms)
    setInterval(() => {
        console.log('Syncing data with server...');
        // Hanya auto refresh jika user TIDAK sedang membuka modal import
        if (!$('#importModal').is(':visible')) {
            location.reload();
        }
    }, 300000);

    // 2. Planning Engine Initialization
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Production Planning Dashboard Active | Date: {{ $activeFilters['date'] }}');
    });
</script>
@endpush
