@php
    $status = $job->job_data->status ?? 'pending';
    $actualOk = $job->job_data->dailyProduction?->actual_ok ?? 0;
    $actualRepair = $job->job_data->dailyProduction?->actual_repair ?? 0;
    $actualReject = $job->job_data->dailyProduction?->actual_reject ?? 0;
    $actualQty = $job->job_data->dailyProduction?->actual_qty ?? 0;
    $efficiency = $job->job_data->dailyProduction?->efficiency ?? 0;
    $runtime = $job->job_data->dailyProduction?->runtime_seconds ?? 0;
    $status = strtolower($job->job_data->status ?? 'pending');
    $isCompleted = in_array($status, ['complete', 'finished']);
    $jobId = $job->job_data->id ?? 0;
    
    $firstPending = isset($pendingJobs) ? $pendingJobs->first() : null;
    $isFirstPending = $firstPending && $firstPending->id === $jobId;
@endphp

@if($job->row_type === 'break')
<tr id="row-{{ $job->id }}" class="bg-slate-800/5 hover:bg-slate-800/10 transition-colors border-l-4 border-slate-400">
    {{-- 1. ITEM SPEC --}}
    <td class="px-6 py-5 align-middle">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex flex-col gap-0.5">
                <span class="font-black text-slate-700 text-sm tracking-tight leading-none uppercase">{{ $job->job_master ?: 'ISTIRAHAT' }}</span>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">JADWAL ISTIRAHAT PPC</span>
            </div>
        </div>
    </td>

    {{-- 2. QUANTITIES --}}
    <td class="px-6 py-5 align-middle text-center">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200/50 text-[10px] font-black text-slate-500 uppercase tracking-widest">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
            WAKTU ISTIRAHAT
        </span>
    </td>

    {{-- 3. STATUS & ACTION --}}
    <td class="px-6 py-5 align-middle text-center">
        <div class="flex flex-col items-center gap-1">
            <span class="px-3 py-1 rounded-lg text-[9px] font-black tracking-widest text-slate-600 bg-slate-200 shadow-sm uppercase">
                BREAK TIME
            </span>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">System Idle</span>
        </div>
    </td>

    {{-- 4. TIMELINES --}}
    <td class="px-6 py-5 align-middle">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black text-slate-500">Jadwal:</span>
                <span class="text-sm font-black text-slate-700 bg-white border border-slate-200 px-3 py-1 rounded-xl shadow-sm font-mono tracking-tight">{{ $job->start_time }} - {{ $job->finish_time }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-slate-400 font-bold text-[10px] uppercase">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Auto Resume
            </div>
        </div>
    </td>
</tr>
@else
<tr id="row-{{ $job->id }}" data-job-number="{{ $job->job_no }}" class="hover:bg-slate-50 transition-colors">
    {{-- 1. ITEM SPEC --}}
    <td class="px-6 py-5 align-top">
        <div class="flex flex-col gap-1">
            <span class="font-black text-slate-800 text-sm tracking-tight leading-none">{{ $job->job_no ?: 'NO JOB NO' }}</span>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase leading-tight">{{ $job->job_master ?: 'NO MASTER NAME' }}</span>
                @if(!empty($job->session_no))
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-black bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm shrink-0" title="Split Session {{ $job->session_no }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        SES {{ chr(64 + (int)$job->session_no) }}
                    </span>
                @endif
            </div>
            <div class="mt-2 flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-[8px] font-black tracking-tighter uppercase">LINE: {{ $job->press_name ?? 'N/A' }}</span>
                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-500 text-[8px] font-black tracking-tighter uppercase">POS: {{ $job->row_no }}</span>
            </div>
        </div>
    </td>

    {{-- 2. QUANTITIES --}}
    <td class="px-6 py-5 align-top">
        @if($isCompleted)
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-[10px] max-w-[180px] mx-auto">
            <div class="flex flex-col border-l-2 border-slate-200 pl-2">
                <span class="text-slate-400 font-bold uppercase tracking-tighter text-[8px]">Plan Target</span>
                <span class="font-black text-slate-800 text-xs">{{ number_format($job->plan) }}</span>
            </div>
            <div class="flex flex-col border-l-2 border-emerald-500 pl-2">
                <span class="text-emerald-500 font-bold uppercase tracking-tighter text-[8px]">Final OK</span>
                <span class="font-black text-emerald-600 text-sm">{{ number_format($actualOk) }}</span>
            </div>
            <div class="flex flex-col border-l-2 border-orange-500 pl-2">
                <span class="text-orange-500 font-bold uppercase tracking-tighter text-[8px]">Final Repair</span>
                <span class="font-black text-orange-600 text-sm">{{ number_format($actualRepair) }}</span>
            </div>
            <div class="flex flex-col border-l-2 border-red-500 pl-2">
                <span class="text-red-500 font-bold uppercase tracking-tighter text-[8px]">Final Reject</span>
                <span class="font-black text-red-600 text-sm">{{ number_format($actualReject) }}</span>
            </div>

            {{-- ACHIEVEMENT SUMMARY --}}
            <div class="col-span-2 mt-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-slate-400 font-bold uppercase tracking-tighter text-[7px]">Achievement Status</span>
                    <div class="flex items-center gap-1">
                        <span class="font-black text-slate-800 text-[10px]">{{ number_format($actualQty) }}</span>
                        <span class="text-slate-300 font-bold">/</span>
                        <span class="font-black text-slate-400 text-[10px]">{{ number_format($job->plan) }}</span>
                    </div>
                </div>
                <div class="px-2 py-1 rounded-lg bg-emerald-600 text-white flex flex-col items-center justify-center min-w-[45px]">
                    <span class="text-[9px] font-black leading-none">{{ number_format($efficiency, 1) }}%</span>
                </div>
            </div>
        </div>
        @else
        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-[10px] max-w-[180px] mx-auto">
            <div class="flex flex-col border-l-2 border-slate-200 pl-2">
                <span class="text-slate-400 font-bold uppercase tracking-tighter text-[8px]">Plan Target</span>
                <span class="font-black text-slate-800 text-xs">{{ number_format($job->plan) }}</span>
            </div>
            <div class="flex flex-col border-l-2 border-blue-500 pl-2">
                <span class="text-blue-500 font-bold uppercase tracking-tighter text-[8px]">Actual OK</span>
                @if($status == 'running' && !($isLocked ?? false))
                    <div class="flex flex-col gap-1.5">
                        <input type="number" id="actual-{{ $jobId }}" onchange="manualInput(this.id, {{ $jobId }})" value="{{ $actualOk }}" placeholder="0" class="w-full bg-transparent border-b border-blue-100 font-black text-blue-600 outline-none text-xs text-center transition-all">
                        <button onclick="stepInput('actual-{{ $jobId }}', {{ $job->qty_plt ?? 0 }}, {{ $jobId }})" class="w-full py-1.5 rounded bg-emerald-500 text-white text-[8px] font-black hover:bg-emerald-600 transition-all">PLT ({{ $job->qty_plt ?? 0 }})</button>
                    </div>
                @else
                    <span class="font-black text-slate-400 text-xs">{{ number_format($actualOk) }}</span>
                @endif
            </div>
            <div class="flex flex-col border-l-2 border-orange-500 pl-2">
                <span class="text-orange-500 font-bold uppercase tracking-tighter text-[8px]">Repair</span>
                @if($status == 'running' && !($isLocked ?? false))
                    <input type="number" id="repair-{{ $jobId }}" onchange="manualInput(this.id, {{ $jobId }})" value="{{ $actualRepair }}" placeholder="0" class="w-full bg-transparent border-b border-orange-100 font-black text-orange-600 outline-none text-xs text-center transition-all">
                @else
                    <span class="font-black text-slate-400 text-xs">{{ number_format($actualRepair) }}</span>
                @endif
            </div>
            <div class="flex flex-col border-l-2 border-red-500 pl-2">
                <span class="text-red-500 font-bold uppercase tracking-tighter text-[8px]">Reject</span>
                @if($status == 'running' && !($isLocked ?? false))
                    <input type="number" id="reject-{{ $jobId }}" onchange="manualInput(this.id, {{ $jobId }})" value="{{ $actualReject }}" placeholder="0" class="w-full bg-transparent border-b border-red-100 font-black text-red-600 outline-none text-xs text-center transition-all">
                @else
                    <span class="font-black text-slate-400 text-xs">{{ number_format($actualReject) }}</span>
                @endif
            </div>

            {{-- ACHIEVEMENT SUMMARY --}}
            <div class="col-span-2 mt-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-slate-400 font-bold uppercase tracking-tighter text-[7px]">Achievement Status</span>
                    <div class="flex items-center gap-1">
                        <span id="row-actual-{{ $jobId }}" class="font-black text-slate-800 text-[10px]">{{ number_format($actualQty) }}</span>
                        <span class="text-slate-300 font-bold">/</span>
                        <span class="font-black text-slate-400 text-[10px]">{{ number_format($job->plan) }}</span>
                    </div>
                </div>
                <div class="px-2 py-1 rounded-lg bg-slate-900 text-white flex flex-col items-center justify-center min-w-[45px]">
                    <span id="row-efficiency-{{ $jobId }}" class="text-[9px] font-black leading-none">{{ number_format($efficiency, 1) }}%</span>
                </div>
            </div>
        </div>
        @if($status == 'running' && !($isLocked ?? false))
            <button onclick="saveJob({{ $jobId }})" class="mt-3 w-full py-2 rounded-lg bg-slate-900 text-white text-[9px] font-black hover:bg-black transition-all shadow-md active:scale-95">INPUT DATA</button>
        @endif
        @endif
    </td>

    {{-- 3. STATUS & ACTION --}}
    <td class="px-6 py-5 align-top text-center">
        @if($isCompleted)
            <div class="flex flex-col items-center">
                <div class="w-full py-4 px-6 rounded-2xl bg-emerald-50 border border-emerald-100 flex flex-col items-center justify-center gap-1 shadow-sm">
                    <div class="flex items-center gap-2 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-[11px] font-black uppercase tracking-widest">COMPLETED</span>
                    </div>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ $job->job_data->updated_at->format('H:i d/m/y') }}</span>
                </div>
                <span class="text-[10px] font-black text-slate-400 uppercase mt-4">Total Runtime</span>
                <span class="text-lg font-black text-slate-800 tabular-nums">
                    {{ floor($runtime/60).'m '.($runtime%60).'s' }}
                </span>
            </div>
        @else
            <div id="timer-{{ $jobId }}" class="font-mono text-2xl font-black text-slate-800 tracking-tighter tabular-nums mb-1">00:00:00</div>
            <div class="flex flex-col items-center gap-1.5">
                <span id="badge-{{ $jobId }}" class="px-3 py-1 rounded-lg text-[9px] font-black tracking-widest text-white shadow-lg
                    @if($status=='running') bg-green-500 shadow-green-100
                    @else bg-blue-600 shadow-blue-50 @endif uppercase mb-2">
                    {{ $status }}
                </span>
                
                @if($jobId > 0 && !$isCompleted)
                <div class="flex flex-col gap-2 w-full max-w-[140px]">
                    @if($status == 'running')
                        <!-- Running job controls are handled at active job board -->
                    @else
                        <span class="px-2 py-1 rounded text-[8px] font-bold bg-slate-100 text-slate-400 border border-slate-200/50 uppercase tracking-widest text-center">Menunggu Giliran</span>
                    @endif
                </div>
                @elseif($jobId <= 0)
                <div class="text-[8px] text-red-500 font-bold uppercase italic tracking-tighter">Sync error: No JobData</div>
                @endif

                <span class="text-[9px] font-black text-slate-300 uppercase italic tracking-widest mt-2">
                    SAVED: <span id="runtime-{{ $jobId }}" class="text-slate-500 tabular-nums">{{ floor($runtime/60).'m '.($runtime%60).'s' }}</span>
                </span>
            </div>
        @endif
    </td>

    {{-- 4. TIMELINES --}}
    <td class="px-6 py-5 align-top min-w-[550px]">
        <div class="space-y-5">
            <!-- TARGET -->
            <div class="relative">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Production Progress (OK / Repair / Reject)</span>
                    <span class="text-[9px] font-black text-slate-400 bg-slate-100 px-2 py-0.5 rounded tabular-nums border border-slate-200">{{ $job->start_time }} - {{ $job->finish_time }}</span>
                </div>
                <div class="relative h-3 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden cursor-pointer flex"
                     onmouseenter="window.showTargetTooltip(event, {{ $jobId }})"
                     onmouseleave="window.hideTimelineTooltip()">
                    <div id="row-bar-ok-{{ $jobId }}" class="h-full bg-emerald-500 transition-all duration-500" style="width: 0%"></div>
                    <div id="row-bar-repair-{{ $jobId }}" class="h-full bg-amber-500 transition-all duration-500" style="width: 0%"></div>
                    <div id="row-bar-reject-{{ $jobId }}" class="h-full bg-rose-500 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <!-- ACTUAL -->
            <div class="relative">
                <div class="flex justify-between items-center mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest">Actual Segmented Execution (Live Tracking)</span>
                        @if(!empty($job->act_start) || !empty($job->act_finish))
                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[8px] font-black tracking-tighter uppercase border border-blue-100">
                                PPC Actual: {{ $job->act_start ?: '--:--' }} - {{ $job->act_finish ?: '--:--' }}
                            </span>
                        @endif
                    </div>
                    <span id="pct-{{ $jobId }}" class="text-[9px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded shadow-sm border border-blue-100 tabular-nums">0%</span>
                </div>
                <div class="relative h-9 w-full mb-6 group hover:z-[60]">
                    <div class="absolute inset-0 bg-slate-900 rounded-xl border-2 border-slate-800 shadow-2xl overflow-hidden flex items-center">
                        <div id="actual-segments-{{ $jobId }}" class="absolute inset-0 rounded-lg">
                            <!-- SEGMENTS RENDERED VIA JS -->
                        </div>
                    </div>
                    <div id="actual-labels-{{ $jobId }}" class="absolute inset-0 pointer-events-none z-50">
                        <!-- TOOLTIPS & CALLOUTS RENDERED VIA JS -->
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>
@endif
