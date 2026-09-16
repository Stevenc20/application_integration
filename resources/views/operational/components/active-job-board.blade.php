@if($activeJob && strtolower($activeJob->status) == 'running')
    @if($isLocked ?? false)
    {{-- LOCKED SUMMARY CARD --}}
    <div id="active-job-card" class="bg-slate-900 rounded-3xl p-6 shadow-2xl border-2 border-slate-800/50 text-white relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1/2 h-0.5 bg-gradient-to-r from-red-500 via-red-500 to-transparent"></div>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-500/20 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-black text-red-400 uppercase tracking-widest">Shift Terkunci — Read Only</h2>
                <p class="text-xs text-slate-400 font-bold mt-1">Data produksi untuk shift ini sudah difinalisasi.</p>
            </div>
            <div class="ml-auto flex items-center gap-6">
                <div class="text-right">
                    <span class="text-[10px] text-slate-500 font-black uppercase tracking-wider">OK</span>
                    <p class="text-2xl font-black text-emerald-400 tabular-nums">{{ $activeJob->dailyProduction->actual_ok ?? 0 }}</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-500 font-black uppercase tracking-wider">Repair</span>
                    <p class="text-2xl font-black text-orange-400 tabular-nums">{{ $activeJob->dailyProduction->actual_repair ?? 0 }}</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-500 font-black uppercase tracking-wider">Reject</span>
                    <p class="text-2xl font-black text-red-400 tabular-nums">{{ $activeJob->dailyProduction->actual_reject ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- REALTIME PRODUCTION CONTROL BOARD --}}
    <div id="active-job-card" class="bg-slate-900 rounded-3xl p-6 shadow-2xl border-2 border-slate-800 text-white relative overflow-hidden">
        <!-- Visual Accents -->
        <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-blue-600/5 to-transparent"></div>
        <div class="absolute top-0 left-0 w-1/2 h-0.5 bg-gradient-to-r from-blue-500 via-red-500 to-transparent"></div>
        
        @php 
            $isDandori = $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first();
            $openFirstCheck = $activeJob->dandoris->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check' && !$d->finish_time)->first();
            $firstDandori = $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first();
            $trueSessionStart = $firstDandori ? $firstDandori->start_time : ($activeJob->started_at ?? null);

            $prodPlan = $activeJob->production_plan;
            $schedStart = $prodPlan ? $prodPlan->start_time : ($activeJob->plan_start ? \Carbon\Carbon::parse($activeJob->plan_start)->format('H:i') : '07:40');
            $schedFinish = $prodPlan ? $prodPlan->finish_time : ($activeJob->plan_end ? \Carbon\Carbon::parse($activeJob->plan_end)->format('H:i') : '10:40');
            $actStartVal = $prodPlan && $prodPlan->act_start ? $prodPlan->act_start : ($activeJob->started_at ? \Carbon\Carbon::parse($activeJob->started_at)->format('H:i') : null);
            $actFinishVal = $prodPlan && $prodPlan->act_finish ? $prodPlan->act_finish : ($activeJob->finished_at ? \Carbon\Carbon::parse($activeJob->finished_at)->format('H:i') : null);

            $activeDowntime = $activeJob->downtimes->whereNull('finish_time')->first();
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 relative z-10 items-stretch">
            
            {{-- ROW 1: Progress Timeline (Left, 9 cols) & Running Session (Right, 3 cols) --}}
            
            <!-- Progress Timeline (Left Area) -->
            <div class="lg:col-span-9">
                <div class="p-4 bg-slate-800/30 border border-slate-700 rounded-2xl min-h-[140px] flex flex-col justify-between h-full">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] sm:text-xs text-slate-500 font-black uppercase tracking-widest leading-none">Production Progress (OK / Repair / Reject)</span>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-xs sm:text-sm font-black font-mono text-white leading-none">{{ $schedStart }}</span>
                                <span class="text-slate-600 font-bold text-[10px] sm:text-xs leading-none">→</span>
                                <span class="text-xs sm:text-sm font-black font-mono text-white leading-none">{{ $schedFinish }}</span>
                            </div>
                        </div>
                        <div class="text-right flex items-center gap-2.5">
                            <div class="flex flex-col">
                                <span class="text-[10px] sm:text-xs text-slate-500 font-black uppercase tracking-widest leading-none">Actual Progress :</span>
                            </div>
                            <p id="timeline-time-label" class="text-base sm:text-lg font-black font-mono text-blue-500 leading-none bg-blue-500/10 px-2 py-1 rounded-lg">0%</p>
                        </div>
                    </div>

                    <!-- TARGET & ACTUAL PROGRESS BARS (ENLARGED LIKE OLD SYSTEM) -->
                    <div class="space-y-2.5 my-2">
                        <!-- PLANNED BAR -->
                        <div class="h-4 w-full bg-slate-800 rounded-full border border-slate-700/50 overflow-hidden relative cursor-pointer flex"
                             onmouseenter="window.showActiveTargetTooltip(event)"
                             onmouseleave="window.hideTimelineTooltip()">
                            <div id="timeline-bar-ok" class="h-full bg-emerald-500 transition-all duration-300" style="width: 0%"></div>
                            <div id="timeline-bar-repair" class="h-full bg-amber-500 transition-all duration-300" style="width: 0%"></div>
                            <div id="timeline-bar-reject" class="h-full bg-rose-500 transition-all duration-300" style="width: 0%"></div>
                        </div>
                        
                        <!-- ACTUAL TIMES MARKERS ABOVE ACTUAL PROGRESS BAR -->
                        <div class="flex items-center justify-between text-[11px] sm:text-xs font-black uppercase tracking-wider mt-3 mb-1">
                            <span id="execution-started-at" class="text-blue-400">
                                Started: {{ $actStartVal ?: '--:--' }}
                            </span>
                            <span id="timeline-current-time" class="text-slate-400 font-mono">
                                End: {{ $actFinishVal ?: '--:--' }}
                            </span>
                        </div>

                        <!-- DYNAMIC SEGMENTS CONTAINER BAR (ENLARGED) -->
                        <div class="relative h-10 w-full group">
                            <div class="absolute inset-0 bg-slate-900 rounded-xl border border-slate-700 shadow-inner overflow-hidden">
                                <div id="timeline-actual-container" class="absolute inset-0 rounded-xl">
                                    <!-- SEGMENTS RENDERED VIA JS -->
                                </div>
                            </div>
                            
                            <div id="timeline-actual-labels" class="absolute inset-0 pointer-events-none z-50"></div>

                            <!-- Current Time Marker Line -->
                            @php
                                $ppcStart = $prodPlan?->start_time ? \Carbon\Carbon::parse($date . ' ' . $prodPlan->start_time) : null;
                                $ppcEnd = $prodPlan?->finish_time ? \Carbon\Carbon::parse($date . ' ' . $prodPlan->finish_time) : null;
                                $planStart = $ppcStart ?? \Carbon\Carbon::parse($activeJob->plan_start ?? now()->startOfDay()->addHours(7)->addMinutes(40));
                                $planEnd = $ppcEnd ?? \Carbon\Carbon::parse($activeJob->plan_end ?? now()->startOfDay()->addHours(10)->addMinutes(40));
                                $totalDur = max(1, $planEnd->diffInSeconds($planStart));
                                $elapsed = max(0, now()->diffInSeconds($planStart, false));
                                $markerPerc = min(100, max(0, ($elapsed / $totalDur) * 100));
                            @endphp
                            <div id="timeline-marker" class="absolute top-0 h-full w-[2px] bg-yellow-400 shadow-[0_0_8px_rgba(250,204,21,1)] z-40 pointer-events-none" style="left: {{ $markerPerc }}%">
                                <div class="absolute -top-0.5 -left-0.5 w-1.5 h-1.5 bg-yellow-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend & Target/Capacity details -->
                    <div class="flex items-center justify-between text-[10px] sm:text-xs text-slate-500 font-bold uppercase pt-2 border-t border-slate-700/30">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Target PPC: {{ $activeJob->target_qty ?? 0 }} Pcs</span>
                            <span class="text-slate-600">|</span>
                            <span class="text-slate-400">Qty/Plt: {{ $activeJob->capacity ?? 0 }} Pcs</span>
                            <span class="text-slate-600">|</span>
                            <span class="text-slate-400">Cycle: {{ $activeJob->cycle_time ?? 0 }}s</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-amber-400"></div><span>Dandori</span></div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-purple-500"></div><span>1st Check</span></div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-blue-500"></div><span>Prod</span></div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-red-500"></div><span>Down</span></div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-orange-500"></div><span>TryOut</span></div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-slate-400"></div><span>Break</span></div>
                        </div>
                    </div>
                </div>
            </div>            <!-- Running Session Card (Right Area) -->
            <div class="lg:col-span-3">
                @php
                    $statusLabel = 'PRODUKSI';
                    $statusBg = 'bg-emerald-500/10 border-emerald-500/20';
                    $statusText = 'text-emerald-400';
                    $statusPulseColor = 'bg-emerald-500';

                    if ($activeDowntime) {
                        $dtType = strtolower($activeDowntime->jenis_downtime);
                        if ($dtType === 'dandori' && $openFirstCheck) {
                            $statusLabel = '1ST CHECK';
                            $statusBg = 'bg-purple-500/10 border-purple-500/20';
                            $statusText = 'text-purple-400';
                            $statusPulseColor = 'bg-purple-500';
                        } elseif ($dtType === 'dandori') {
                            $statusLabel = 'DANDORI';
                            $statusBg = 'bg-amber-500/10 border-amber-500/20';
                            $statusText = 'text-amber-400';
                            $statusPulseColor = 'bg-amber-500';
                        } elseif ($dtType === 'break time') {
                            $statusLabel = 'BREAK';
                            $statusBg = 'bg-slate-500/10 border-slate-500/20';
                            $statusText = 'text-slate-400';
                            $statusPulseColor = 'bg-slate-500';
                        } elseif ($dtType === 'try out') {
                            $statusLabel = 'TRY OUT';
                            $statusBg = 'bg-orange-500/10 border-orange-500/20';
                            $statusText = 'text-orange-400';
                            $statusPulseColor = 'bg-orange-500';
                        } else {
                            $statusLabel = 'DOWNTIME';
                            $statusBg = 'bg-rose-500/10 border-rose-500/20';
                            $statusText = 'text-rose-400';
                            $statusPulseColor = 'bg-rose-500';
                        }
                    } elseif ($openFirstCheck) {
                        $statusLabel = '1ST CHECK';
                        $statusBg = 'bg-purple-500/10 border-purple-500/20';
                        $statusText = 'text-purple-400';
                        $statusPulseColor = 'bg-purple-500';
                    } elseif (!$activeJob->started_at) {
                        $statusLabel = 'PENDING';
                        $statusBg = 'bg-slate-800/80 border-slate-700';
                        $statusText = 'text-slate-500';
                        $statusPulseColor = 'bg-slate-600';
                    }
                @endphp
                <div class="p-5 bg-slate-800/50 border border-slate-700 rounded-3xl h-full flex flex-col justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-900/40 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] sm:text-xs font-black text-blue-400 uppercase tracking-widest block leading-none">Running Session</span>
                                <h2 class="text-lg sm:text-xl font-black text-white tracking-tighter truncate leading-none mt-1.5">
                                    {{ strpos($activeJob->job_number, '-') !== false ? substr($activeJob->job_number, 0, strrpos($activeJob->job_number, '-')) : $activeJob->job_number }}
                                </h2>
                            </div>
                        </div>

                        <!-- Realtime Status Alert Label -->
                        <div id="realtime-status-container" class="px-4 py-3 mt-3.5 rounded-2xl border {{ $statusBg }} flex items-center justify-between transition-all duration-300">
                            <div class="flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                    <span id="realtime-status-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $statusPulseColor }} opacity-75"></span>
                                    <span id="realtime-status-dot" class="relative inline-flex rounded-full h-2 w-2 {{ $statusPulseColor }}"></span>
                                </span>
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-wider">Status:</span>
                            </div>
                            <span id="realtime-status-text" class="text-sm font-black {{ $statusText }} uppercase tracking-widest">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                    @php
                        $nextJob = null;
                        if (isset($activeJob) && isset($pendingJobs)) {
                            $foundActive = false;
                            foreach ($pendingJobs as $pj) {
                                if ($pj->id == $activeJob->id) {
                                    $foundActive = true;
                                    continue;
                                }
                                if ($foundActive && strtolower($pj->status) === 'pending') {
                                    $nextJob = $pj;
                                    break;
                                }
                            }
                            if (!$nextJob) {
                                foreach ($pendingJobs as $pj) {
                                    if ($pj->id != $activeJob->id && strtolower($pj->status) === 'pending') {
                                        $nextJob = $pj;
                                        break;
                                    }
                                }
                            }
                        }
                    @endphp

                    <div class="space-y-1">
                        <p class="text-[9px] sm:text-xs text-slate-500 font-black uppercase tracking-wider leading-none">Next Item</p>
                        <p class="text-xs sm:text-sm font-black truncate leading-tight text-slate-200" title="{{ $nextJob ? $nextJob->job_name : 'No pending items' }}">
                            @if($nextJob)
                                {{ strpos($nextJob->job_number, '-') !== false ? substr($nextJob->job_number, 0, strrpos($nextJob->job_number, '-')) : $nextJob->job_number }}
                            @else
                                <span class="text-slate-500 italic">None</span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="flex items-center justify-between border-t border-slate-700/50 pt-3 text-[9px] sm:text-xs mt-1">
                        <div>
                            <span class="text-slate-500 font-black uppercase leading-none block">Line</span>
                            <span class="text-xs sm:text-sm font-black text-blue-400 leading-none block mt-1.5">{{ $activeJob->line }}</span>
                        </div>
                        <div class="w-[1px] h-6 bg-slate-700/80"></div>
                        <div class="text-right">
                            <span class="text-slate-500 font-black uppercase leading-none block">Work Date</span>
                            <span class="text-xs sm:text-sm font-black text-slate-300 leading-none block mt-1.5">{{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>


            {{-- BREAK OVERLAY (hidden by default, shown during auto-break) --}}
            <div id="break-overlay" class="lg:col-span-12 hidden">
                <div class="flex flex-col items-center justify-center py-16 rounded-3xl bg-slate-800/30 border-2 border-slate-500/30">
                    <div class="w-20 h-20 rounded-full bg-slate-500/20 flex items-center justify-center mb-6 border border-slate-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p id="break-overlay-label" class="text-3xl sm:text-4xl font-black text-slate-300 uppercase tracking-widest mb-2">BREAK TIME</p>
                    <p class="text-sm font-black text-slate-500 uppercase tracking-widest">Istirahat — Timer dijeda</p>
                    <p id="break-overlay-timer" class="text-5xl sm:text-6xl font-black text-white mt-6 tabular-nums">00:00:00</p>
                    <p class="text-xs text-slate-600 mt-4 font-bold">Produksi akan otomatis dilanjutkan setelah istirahat selesai</p>
                </div>
            </div>

            {{-- ROW 2: Performance Console (Left, 9 cols) & Operator Console (Right, 3 cols) --}}
            <div id="active-work-area" class="lg:col-span-12">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Performance Console (Left Area) -->
            <div class="lg:col-span-9">
                <!-- Quick Entry & Performance Console (min-h-[220px]) -->
                @if($activeJob->started_at && !$isDandori)
                <div class="p-5 bg-blue-600/5 border border-blue-500/20 rounded-3xl min-h-[220px] flex flex-col gap-4 h-full">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-blue-500/10 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs sm:text-sm font-black text-blue-400 uppercase tracking-widest">Performance & Entry Console</span>
                            <span class="text-[9px] sm:text-[10px] font-black text-blue-500 uppercase tracking-wider bg-blue-500/10 px-2 py-0.5 rounded">Live Sync</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                            <span class="text-[9px] sm:text-[10px] text-blue-500 font-bold uppercase tracking-wider">Realtime Ingestion</span>
                        </div>
                    </div>
                    
                    <!-- Integrated Performance Stats (Achievement & Lost Time side-by-side) -->
                    <div class="grid grid-cols-2 gap-4 border-b border-slate-700/50 pb-4">
                        <!-- Achievement (Left Stat) -->
                        <div class="bg-slate-800/40 border border-slate-700/50 p-4 rounded-2xl flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] sm:text-xs text-slate-500 font-black uppercase tracking-wider">Achievement</span>
                                @php
                                    $totalActual = ($activeJob->dailyProduction->actual_ok ?? 0) + ($activeJob->dailyProduction->actual_repair ?? 0) + ($activeJob->dailyProduction->actual_reject ?? 0);
                                    $achievementPct = $activeJob->target_qty > 0 ? round(($totalActual / $activeJob->target_qty) * 100) : 0;
                                @endphp
                                <div class="flex items-baseline gap-2 mt-2 leading-none">
                                    <span id="active-achievement-display" class="text-2xl sm:text-3xl font-black tracking-tighter tabular-nums {{ $achievementPct >= 100 ? 'text-green-400' : 'text-yellow-400' }}">
                                        {{ $achievementPct }}%
                                    </span>
                                    <span id="active-achievement-pcs" class="text-[10px] sm:text-xs font-bold text-slate-400 tracking-tight tabular-nums">
                                        ({{ $totalActual }} / {{ $activeJob->target_qty ?? 0 }} PCS)
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end justify-between h-full">
                                <div class="w-2 h-2 rounded-full {{ $achievementPct >= 100 ? 'bg-green-400 animate-pulse' : 'bg-yellow-400' }}"></div>
                                <span class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase mt-2">Efficiency</span>
                            </div>
                        </div>

                        <!-- Lost Time (Right Stat) -->
                        <div class="bg-slate-800/40 border border-slate-700/50 p-4 rounded-2xl flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] sm:text-xs text-slate-500 font-black uppercase tracking-wider">Lost Time</span>
                                @php
                                    $lostSeconds = $activeJob->downtimes
                                        ->filter(fn($dt) => !in_array(strtolower(trim($dt->jenis_downtime ?? '')), ['dandori', 'break', 'break time']))
                                        ->sum('duration_seconds');
                                    $lostMins = intdiv($lostSeconds, 60);
                                    $lostSecs = $lostSeconds % 60;
                                @endphp
                                <span id="active-lost-time-display" class="text-2xl sm:text-3xl font-black tracking-tighter text-red-500 mt-2 leading-none tabular-nums">
                                    @if($lostMins > 0)
                                        {{ $lostMins }}<span class="text-xs sm:text-sm font-black text-slate-500">m </span>
                                    @endif
                                    {{ $lostSecs }}<span class="text-xs sm:text-sm font-black text-slate-500">s</span>
                                </span>
                            </div>
                            <div class="flex flex-col items-end justify-between h-full">
                                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                <span class="text-[9px] sm:text-[10px] text-slate-500 font-bold uppercase mt-2">Downtime</span>
                            </div>
                        </div>
                    </div>

                    <!-- Production Counter Columns (OK, Repair, Reject) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- OK Counter -->
                        <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50 flex flex-col justify-between">
                            <div class="flex justify-between items-center border-b border-slate-700/30 pb-2">
                                <span class="text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-wider">OK</span>
                                <span class="text-2xl sm:text-3xl font-black text-white leading-none tabular-nums animate-pulse" id="active-actual-display">{{ $activeJob->dailyProduction->actual_ok ?? 0 }}</span>
                            </div>
                            <input type="hidden" id="active-actual-{{ $activeJob->id }}" value="0">
                            <div class="flex flex-col gap-2 mt-3">
                                <div class="flex gap-2">
                                    <button onclick="stepInput('active-actual-{{ $activeJob->id }}', {{ $activeJob->capacity ?? 0 }}, {{ $activeJob->id }})" class="flex-1 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm transition-all active:scale-95 shadow-lg shadow-emerald-900/40 border-b-2 border-emerald-700 active:border-b-0 flex items-center justify-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                        PLT ({{ $activeJob->capacity ?? 0 }})
                                    </button>
                                    <button onclick="stepInput('active-actual-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-3 rounded-2xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 text-emerald-400 font-black text-sm transition-all active:scale-95 flex items-center justify-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                        +1
                                    </button>
                                    <button onclick="stepInput('active-actual-{{ $activeJob->id }}', -1, {{ $activeJob->id }})" class="flex-1 py-3 rounded-2xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 font-black text-sm transition-all active:scale-95 flex items-center justify-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M5 11h14v2H5z"/></svg>
                                        -1
                                    </button>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <input type="number" id="manual-ok-{{ $activeJob->id }}" placeholder="0" class="min-w-0 flex-1 bg-slate-700/50 border border-slate-600/50 rounded-xl px-2 py-2 sm:px-3 sm:py-2.5 text-sm text-white font-bold outline-none focus:border-blue-500 transition" onkeydown="if(event.key==='Enter'){manualStep('active-actual-{{ $activeJob->id }}','manual-ok-{{ $activeJob->id }}',{{ $activeJob->id }})}">
                                    <button onclick="manualStep('active-actual-{{ $activeJob->id }}','manual-ok-{{ $activeJob->id }}',{{ $activeJob->id }})" class="px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs transition-all active:scale-95">OK</button>
                                </div>
                            </div>
                        </div>

                        <!-- REPAIR Counter -->
                        <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50 flex flex-col justify-between">
                            <div class="flex justify-between items-center border-b border-slate-700/30 pb-2">
                                <span class="text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-wider">Repair</span>
                                <span class="text-2xl sm:text-3xl font-black text-orange-400 leading-none tabular-nums" id="active-repair-display">{{ $activeJob->dailyProduction->actual_repair ?? 0 }}</span>
                            </div>
                            <div class="flex flex-col gap-2 mt-3">
                                <div class="flex gap-2">
                                    <button onclick="stepInput('active-repair-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-3 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-400 font-black text-sm hover:bg-orange-500 hover:text-white transition-all active:scale-95 flex items-center justify-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                        +1
                                    </button>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <input type="number" id="manual-repair-{{ $activeJob->id }}" placeholder="0" class="min-w-0 flex-1 bg-slate-700/50 border border-slate-600/50 rounded-xl px-2 py-2 sm:px-3 sm:py-2.5 text-sm text-white font-bold outline-none focus:border-orange-500 transition" onkeydown="if(event.key==='Enter'){manualStep('active-repair-{{ $activeJob->id }}','manual-repair-{{ $activeJob->id }}',{{ $activeJob->id }})}">
                                    <button onclick="manualStep('active-repair-{{ $activeJob->id }}','manual-repair-{{ $activeJob->id }}',{{ $activeJob->id }})" class="px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl bg-orange-500 hover:bg-orange-400 text-white font-black text-xs transition-all active:scale-95">Go</button>
                                </div>
                            </div>
                        </div>

                        <!-- REJECT Counter -->
                        <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50 flex flex-col justify-between">
                            <div class="flex justify-between items-center border-b border-slate-700/30 pb-2">
                                <span class="text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-wider">Reject</span>
                                <span class="text-2xl sm:text-3xl font-black text-red-500 leading-none tabular-nums" id="active-reject-display">{{ $activeJob->dailyProduction->actual_reject ?? 0 }}</span>
                            </div>
                            <div class="flex flex-col gap-2 mt-3">
                                <div class="flex gap-2">
                                    <button onclick="stepInput('active-reject-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-3 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 font-black text-sm hover:bg-red-500 hover:text-white transition-all active:scale-95 flex items-center justify-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                        +1
                                    </button>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <input type="number" id="manual-reject-{{ $activeJob->id }}" placeholder="0" class="min-w-0 flex-1 bg-slate-700/50 border border-slate-600/50 rounded-xl px-2 py-2 sm:px-3 sm:py-2.5 text-sm text-white font-bold outline-none focus:border-red-500 transition" onkeydown="if(event.key==='Enter'){manualStep('active-reject-{{ $activeJob->id }}','manual-reject-{{ $activeJob->id }}',{{ $activeJob->id }})}">
                                    <button onclick="manualStep('active-reject-{{ $activeJob->id }}','manual-reject-{{ $activeJob->id }}',{{ $activeJob->id }})" class="px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl bg-red-500 hover:bg-red-400 text-white font-black text-xs transition-all active:scale-95">Go</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <!-- Dandori State Placeholder -->
                <div class="p-5 bg-slate-800/10 border border-slate-800/30 rounded-3xl h-full flex items-center justify-center min-h-[220px]">
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center mx-auto mb-3 text-amber-500 border border-amber-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                        <p class="text-sm font-black text-amber-500 uppercase tracking-widest">Dandori Setup Active</p>
                        <p class="text-xs sm:text-sm text-slate-500 font-bold mt-1.5 max-w-sm mx-auto leading-relaxed">Selesaikan persiapan (Dandori) untuk mengaktifkan Performance & Entry Console produksi.</p>
                    </div>
                </div>
                @endif
                
                {{-- Hidden inputs for active job (repair/reject fallback) --}}
                <div class="hidden">
                    <input type="number" id="active-repair-{{ $activeJob->id }}" value="">
                    <input type="number" id="active-reject-{{ $activeJob->id }}" value="">
                </div>
            </div>

            <!-- Operator Console (Right Area) -->
            <div class="lg:col-span-3">
                    <div class="h-full flex flex-col justify-between gap-4">
                    @php
                        $activeDowntime = $activeJob->downtimes->whereNull('finish_time')->first();
                        $dtType = $activeDowntime ? strtolower($activeDowntime->jenis_downtime) : '';
                        $alertBg = 'bg-red-500/10 border-red-500/30';
                        $alertText = 'text-red-500';
                        $alertTitle = 'DOWNTIME';
                        $alertDesc = 'DOWNTIME';
                        
                        if ($dtType === 'dandori' && $openFirstCheck) {
                            $alertBg = 'bg-purple-500/10 border-purple-500/30';
                            $alertText = 'text-purple-400';
                            $alertTitle = '1st Check';
                            $alertDesc = 'PROSES FIRST CHECK (SETTING DIES)';
                        } elseif ($dtType === 'dandori') {
                            $alertBg = 'bg-amber-500/10 border-amber-500/30';
                            $alertText = 'text-amber-500';
                            $alertTitle = 'Dandori (Persiapan)';
                            $alertDesc = 'PROSES DANDORI & SETTING DIES';
                        } elseif ($dtType === 'break time') {
                            $alertBg = 'bg-indigo-500/10 border-indigo-500/30';
                            $alertText = 'text-indigo-400';
                            $alertTitle = 'Break Time';
                            $alertDesc = 'WAKTU ISTIRAHAT OPERATOR';
                        } elseif ($dtType === 'try out') {
                            $alertBg = 'bg-orange-500/10 border-orange-500/30';
                            $alertText = 'text-orange-400';
                            $alertTitle = 'Try Out';
                            $alertDesc = 'TRY OUT MESIN / DIES';
                        } elseif ($activeDowntime) {
                            $alertTitle = 'DOWNTIME';
                            if (!empty($activeDowntime->problem) && !in_array($activeDowntime->problem, ['-', 'MENUNGGU PROSES MULAI (IDLE TIME)'])) {
                                $alertDesc = strtoupper($activeDowntime->problem);
                            } else {
                                $alertDesc = 'DOWNTIME';
                            }
                        }
                    @endphp

                    <div id="active-downtime-alert-box" class="{{ $activeDowntime ? '' : 'hidden' }} {{ $alertBg }} border-2 rounded-2xl p-4 text-center shadow-lg transition-all duration-300 flex-shrink-0">
                        <p id="active-downtime-title" class="text-xs sm:text-sm font-black {{ $alertText }} uppercase tracking-widest mb-1">{{ $alertTitle }}</p>
                        <p id="active-downtime-timer-{{ $activeJob->id }}" class="text-3xl font-black text-white tracking-tighter tabular-nums leading-none">00:00:00</p>
                    </div>

                    <!-- Operator Console Card (min-h-[320px]) -->
                    <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700 shadow-lg shadow-slate-950/20 min-h-[320px] flex flex-col h-full">
                        <div>
                            <div class="flex items-center justify-between border-b border-slate-700/50 pb-2">
                                <span class="text-xs sm:text-sm font-black text-slate-400 uppercase tracking-widest">Operator Console</span>
                                <span class="text-xs sm:text-sm font-black text-slate-500 uppercase">Actions</span>
                            </div>

                            <div id="control-board-actions" class="mt-4">
                                @if(!$activeJob->started_at && !$isDandori)
                                    <button onclick="jsStartDandori({{ $activeJob->id }})" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-sm shadow-xl shadow-emerald-900/40 transition-all flex items-center justify-center gap-2 group border-b-4 border-emerald-700 active:border-0 active:translate-y-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                        MULAI DANDORI (PERSIAPAN)
                                    </button>
                                @elseif($isDandori)
                                    <div class="grid grid-cols-2 gap-2">
                                        <button onclick="jsStopDandori({{ $activeJob->id }})" class="w-full py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-sm shadow-xl shadow-amber-900/40 transition-all flex items-center justify-center gap-2 group border-b-4 border-amber-700 active:border-0 active:translate-y-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                                            STOP &amp; LANJUT PRODUKSI
                                        </button>
                                        @if($openFirstCheck)
                                            <button onclick="jsStopFirstCheck({{ $activeJob->id }})" class="w-full py-3 rounded-xl bg-purple-500 hover:bg-purple-600 text-white font-black text-sm shadow-xl shadow-purple-900/40 transition-all flex items-center justify-center gap-2 group border-b-4 border-purple-700 active:border-0 active:translate-y-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                                                STOP 1ST CHECK
                                            </button>
                                        @else
                                            <button onclick="jsToggleFirstCheck({{ $activeJob->id }})" class="w-full py-3 rounded-xl bg-purple-500 hover:bg-purple-600 text-white font-black text-sm shadow-xl shadow-purple-900/40 transition-all flex items-center justify-center gap-2 group border-b-4 border-purple-700 active:border-0 active:translate-y-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 11.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm0-9c-2.76 0-5 2.24-5 5h2c0-1.66 1.34-3 3-3s3 1.34 3 3c0 2-3 2.5-3 4.5h2c0-1.5 2-2 2-4.5 0-2.76-2.24-5-5-5z"/></svg>
                                                1ST CHECK
                                            </button>
                                        @endif
                                        <button id="dandori-dt-btn-{{ $activeJob->id }}" onclick="handleDandoriDowntime({{ $activeJob->id }})" class="col-span-2 w-full py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 font-black text-sm uppercase tracking-wider flex items-center justify-center gap-2 hover:bg-red-500 hover:text-white transition-all shadow-md active:scale-95">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            DOWNTIME
                                        </button>
                                    </div>
                                @else
                                    <div class="flex flex-col gap-3">
                                        <button onclick="openDowntimeReport({{ $activeJob->id }}, null)" class="w-full py-3 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 hover:bg-slate-700 hover:text-white shadow-md active:scale-95">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            Cek List Downtime
                                        </button>
                                        <button onclick="finishJob({{ $activeJob->id }}, '{{ $activeJob->job_number }}', '{{ addslashes($activeJob->job_name) }}')" class="w-full py-3 rounded-xl bg-red-600/10 border border-red-500/50 text-red-400 font-black text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 group hover:bg-red-600 hover:text-white shadow-md active:scale-95">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                            SELESAIKAN JOB 
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(!$isDandori && $activeJob->started_at)
                        <div class="space-y-2 mt-3 pt-3 border-t border-slate-700/50">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" id="downtime-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'downtime', 'downtime')" class="dt-btn w-full py-2.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-[10px] font-black uppercase text-center hover:bg-red-500 hover:text-white transition-all shadow-md active:scale-95">Downtime</button>
                                <button type="button" id="tryout-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'tryout', 'try out')" class="to-btn w-full py-2.5 rounded-xl bg-orange-500/10 border border-orange-500/30 text-orange-400 text-[10px] font-black uppercase text-center hover:bg-orange-500 hover:text-white transition-all shadow-md active:scale-95">Try Out</button>
                                <button type="button" id="break-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'break', 'break time')" class="break-btn col-span-2 w-full py-2.5 rounded-xl bg-slate-500/10 border border-slate-500/30 text-slate-400 text-[10px] font-black uppercase text-center hover:bg-slate-500 hover:text-white transition-all shadow-md active:scale-95">Break</button>
                                <a href="{{ route('operational.dandori', ['job_id' => $activeJob->id, 'line' => $activeJob->line, 'shift' => 'Shift 1']) }}" class="col-span-2 w-full py-2.5 rounded-xl bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[10px] font-black uppercase text-center hover:bg-blue-500 hover:text-white transition-all shadow-md active:scale-95 flex items-center justify-center">Dandori Mod</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            </div> {{-- end inner grid 9+3 --}}
            
            {{-- REPAIR & REJECT INCIDENT LIST (per active job, loaded inline) --}}
            @if($activeJob->started_at)
            <div class="lg:col-span-12 mt-4 pt-4 border-t border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-orange-500/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Catatan Repair & Reject — Job Ini</h3>
                        <span id="rr-count-badge-{{ $activeJob->id }}" class="hidden text-[9px] font-black px-2 py-0.5 rounded-full bg-orange-500/20 text-orange-400 border border-orange-500/30">0</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('operational.repair_reject.index', ['job_id' => $activeJob->id, 'line' => $activeJob->line]) }}" class="text-[9px] font-black text-orange-400 hover:text-orange-300 uppercase tracking-widest flex items-center gap-1 group">
                            Lihat History R&R
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                        <span class="text-slate-800">|</span>
                        <button onclick="loadRRList({{ $activeJob->id }})" class="text-[9px] font-black text-slate-400 hover:text-slate-300 uppercase tracking-widest flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </button>
                    </div>
                </div>
                <div id="rr-list-container-{{ $activeJob->id }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-2">
                    <div class="col-span-full text-center py-4 text-xs text-slate-600 font-bold">Loading...</div>
                </div>
            </div>
            @endif

            </div> {{-- end #active-work-area --}}

            {{-- REKAM JEJAK --}}
            @if(isset($productionLogs) && $productionLogs->isNotEmpty() && isset($activeJob))
            <div class="lg:col-span-12 mt-8 pt-8 border-t border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded bg-slate-700 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Rekam Jejak Produksi</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('operational.job.logs.detail', $activeJob->id) }}" class="text-[9px] font-black text-blue-500 hover:text-blue-600 uppercase tracking-widest flex items-center gap-1 group">
                            Lihat Semua History
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>
                </div>
                <div id="rekam-jejak-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    @foreach($productionLogs as $log)
                    <div class="bg-slate-800/30 border border-slate-700/50 p-3 rounded-xl flex items-center justify-between group hover:border-blue-500/50 transition-all">
                        <div>
                            <p class="text-[8px] font-black text-slate-500 uppercase leading-none mb-1">{{ $log->created_at->format('H:i:s') }}</p>
                            <p class="text-xs font-black text-white leading-none">OK: {{ $log->ok_qty }} <span class="text-slate-500">|</span> <span class="text-red-400">X: {{ $log->reject_qty }}</span></p>
                        </div>
                        <div class="w-2 h-2 rounded-full bg-blue-500/20 group-hover:bg-blue-500 transition-colors"></div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
@else
    {{-- STANDBY CARD --}}
    @php
        $firstJob = $pendingJobs->first();
    @endphp
    <div class="bg-slate-900 rounded-3xl p-6 md:p-12 shadow-2xl border-4 border-slate-800 text-white flex flex-col items-center justify-center text-center relative overflow-hidden min-h-[300px]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(59,130,246,0.05)_0%,transparent_70%)]"></div>
        <div class="relative z-10 flex flex-col items-center">
            <div class="w-20 h-20 rounded-3xl bg-slate-800 flex items-center justify-center mb-6 shadow-xl border border-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-2xl font-black text-slate-400 uppercase tracking-widest mb-2">Ready to Start</h2>
            <p class="text-slate-500 text-sm max-w-md italic font-medium mb-8">Mulai persiapan (Dandori) untuk item pertama sesuai urutan jadwal PPC di bawah.</p>
            
            <div class="w-full max-w-2xl">
                <div class="flex items-center gap-2 mb-3 ml-1">
                    <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Item Pertama dalam Antrean (Jadwal PPC)</label>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch gap-3 bg-slate-800/40 p-3 rounded-[2rem] border border-slate-700/50">
                    <div class="flex-1 relative" id="custom-select-container">
                        <input type="hidden" id="standby-job-select" value="{{ $firstJob ? $firstJob->id : '' }}">
                        <button type="button" onclick="toggleCustomSelect()" id="custom-select-trigger" class="w-full h-14 bg-slate-900/90 border border-slate-700 rounded-2xl px-6 flex items-center justify-between text-sm text-white hover:border-blue-500/50 transition-all outline-none shadow-inner group/select font-bold">
                            <span id="custom-select-label">
                                @if($firstJob)
                                    [{{ $firstJob->job_number }}] {{ $firstJob->job_name }}
                                @else
                                    -- Antrean Kosong --
                                @endif
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500 group-hover/select:text-blue-400 transition-transform duration-300" id="custom-select-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div id="custom-select-menu" class="hidden absolute bottom-full mb-3 left-0 right-0 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden z-[110] max-h-60 overflow-y-auto">
                            <div class="px-4 py-3 border-b border-slate-800 bg-slate-950 flex items-center justify-between">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Urutan Antrean Jadwal PPC</span>
                                <span class="text-[8px] font-black text-emerald-400 px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">Sesuai Urutan</span>
                            </div>
                            <div class="p-2 space-y-1">
                                @forelse($pendingJobs as $index => $pj)
                                <div class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800/30 transition-all group/item cursor-pointer" onclick="selectCustomItem({{ $pj->id }}, '[{{ $pj->job_number }}] {{ addslashes($pj->job_name) }}')">
                                    <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-400 group-hover/item:text-white transition-all">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0 text-left">
                                        <div class="flex items-center justify-between">
                                            <p class="text-[10px] font-black text-slate-500 uppercase leading-none mb-1 group-hover/item:text-slate-400">{{ $pj->job_number }}</p>
                                            @if($index === 0)
                                                <span class="text-[8px] font-black text-blue-400 uppercase">Item Pertama (Aktif)</span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-bold text-slate-300 truncate">{{ $pj->job_name }}</p>
                                    </div>
                                </div>
                                @empty
                                <div class="px-4 py-8 text-center">
                                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Antrean Kosong</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <button onclick="enqueueJob(document.getElementById('standby-job-select').value)" class="h-14 px-4 md:px-8 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-[10px] md:text-xs shadow-xl shadow-emerald-900/20 transition-all uppercase tracking-widest flex items-center justify-center gap-3 group/btn w-full md:w-auto md:min-w-[200px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                        <span>MULAI PROSES SEKARANG</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
