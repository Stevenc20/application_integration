@if($activeJob && strtolower($activeJob->status) == 'running')
    {{-- REALTIME PRODUCTION CONTROL BOARD --}}
    <div class="bg-slate-900 rounded-3xl p-8 shadow-2xl border-4 border-slate-800 text-white relative overflow-hidden">
        <!-- Visual Accents -->
        <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-blue-600/5 to-transparent"></div>
        <div class="absolute top-0 left-0 w-1/2 h-1 bg-gradient-to-r from-blue-500 via-red-500 to-transparent"></div>
        
        @php 
            $isDandori = $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first();
            $firstDandori = $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first();
            $trueSessionStart = $firstDandori ? $firstDandori->start_time : ($activeJob->started_at ?? null);
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">
            <!-- Left Info -->
            <div class="lg:col-span-4 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-900/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Running Session</span>
                        <h2 class="text-2xl font-black tracking-tighter leading-none">{{ $activeJob->job_number }}</h2>
                    </div>
                </div>
                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <p class="text-xs text-slate-400 font-bold uppercase mb-1">Item Name</p>
                    <p class="text-lg font-bold truncate">{{ $activeJob->job_name }}</p>
                    <div class="mt-3 flex items-center gap-4">
                        <div>
                            <p class="text-[9px] text-slate-500 font-bold uppercase">Line</p>
                            <p class="text-sm font-black text-blue-400">{{ $activeJob->line }}</p>
                        </div>
                        <div class="w-[1px] h-6 bg-slate-700"></div>
                        <div>
                            <p class="text-[9px] text-slate-500 font-bold uppercase">Work Date</p>
                            <p class="text-sm font-black">{{ now()->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center Timeline & Progress -->
            <div class="lg:col-span-5 space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div class="flex flex-col">
                        <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">PPC Plan Schedule</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-black font-mono text-white">{{ $activeJob->plan_start ? \Carbon\Carbon::parse($activeJob->plan_start)->format('H:i') : '07:40' }}</span>
                            <span class="text-slate-600 font-bold">→</span>
                            <span class="text-xl font-black font-mono text-white">{{ $activeJob->plan_end ? \Carbon\Carbon::parse($activeJob->plan_end)->format('H:i') : '10:40' }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">Actual Progress</span>
                        <p id="timeline-time-label" class="text-3xl font-black font-mono text-blue-500 leading-none">0%</p>
                    </div>
                </div>

                <!-- DUAL BAR SYSTEM -->
                <div class="space-y-4">
                    <!-- 1. PLANNED BAR (STATIC) -->
                    <div class="space-y-1">
                        <div class="flex justify-between px-1">
                            <span class="text-[8px] font-bold text-slate-500 uppercase">Target: {{ $activeJob->capacity ?? 0 }} Items</span>
                            <span class="text-[8px] font-bold text-slate-500 uppercase">Cycle: {{ $activeJob->cycle_time ?? 0 }}s</span>
                        </div>
                        <div class="h-3 w-full bg-slate-800 rounded-full border border-slate-700/50 overflow-hidden relative">
                            <div class="h-full w-full bg-slate-700/20"></div>
                            <!-- Small markers for hourly targets? -->
                            <div class="absolute inset-0 flex justify-between px-4 opacity-20">
                                @for($i=0; $i<10; $i++) <div class="w-px h-full bg-white"></div> @endfor
                            </div>
                        </div>
                    </div>

                    <!-- 2. ACTUAL BAR (DYNAMIC) -->
                    @php
                        $planStart = \Carbon\Carbon::parse($activeJob->plan_start ?? now()->startOfDay()->addHours(7)->addMinutes(40));
                        $planEnd = \Carbon\Carbon::parse($activeJob->plan_end ?? now()->startOfDay()->addHours(10)->addMinutes(40));
                        $totalDur = max(1, $planEnd->diffInSeconds($planStart));
                    @endphp
                    <div class="space-y-1">
                        <div class="flex justify-between px-1 items-end">
                            <div class="flex flex-col">
                                <span class="text-[8px] font-black text-blue-400 uppercase italic">Execution Live</span>
                                <span id="execution-started-at" class="text-[10px] font-bold text-white">Started: {{ $activeJob->started_at ? \Carbon\Carbon::parse($activeJob->started_at)->format('H:i') : '--:--' }}</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-[7px] font-black text-slate-500 uppercase">Current Point</span>
                                <span id="timeline-current-time" class="text-[10px] font-black text-blue-400 font-mono bg-blue-500/10 px-2 py-0.5 rounded border border-blue-500/20 tabular-nums">End: --:--</span>
                            </div>
                        </div>
                        <div class="relative h-9 w-full mb-6 group">
                            <div class="absolute inset-0 bg-slate-900 rounded-xl border-2 border-slate-700 shadow-2xl overflow-hidden">
                                <div id="timeline-actual-container" class="absolute inset-0 rounded-lg">
                                    <!-- SEGMENTS RENDERED VIA JS -->
                                </div>
                            </div>
                            
                            <!-- LABELS LAYER (Callouts) - OUTSIDE OVERFLOW -->
                            <div id="timeline-actual-labels" class="absolute inset-0 pointer-events-none z-50">
                                <!-- TIME LABELS RENDERED VIA JS -->
                            </div>

                            <!-- Current Time Marker Line -->
                            @php
                                $planStart = \Carbon\Carbon::parse($activeJob->plan_start ?? now()->startOfDay()->addHours(7)->addMinutes(40));
                                $planEnd = \Carbon\Carbon::parse($activeJob->plan_end ?? now()->startOfDay()->addHours(10)->addMinutes(40));
                                $totalDur = max(1, $planEnd->diffInSeconds($planStart));
                                $elapsed = max(0, now()->diffInSeconds($planStart, false));
                                $markerPerc = min(100, max(0, ($elapsed / $totalDur) * 100));
                            @endphp
                            <div id="timeline-marker" class="absolute top-0 h-full w-[3px] bg-yellow-400 shadow-[0_0_15px_rgba(250,204,21,1)] z-40 pointer-events-none" style="left: {{ $markerPerc }}%">
                                <div class="absolute -top-1 -left-1 w-3 h-3 bg-yellow-400 rounded-full animate-ping"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legend Row -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded bg-amber-400"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase">Dandori</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded bg-blue-500"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase">Prod</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded bg-red-500"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase">Down</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded bg-orange-500"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase">TryOut</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded bg-indigo-500"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase">Break</span>
                    </div>
                </div>
            </div>

            <!-- Right Stats -->
            <div class="lg:col-span-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-2xl bg-slate-800/50 border border-slate-700 flex flex-col justify-center items-center text-center">
                        <p class="text-[8px] text-slate-500 font-bold uppercase">Achievement</p>
                        <p id="active-achievement-display" class="text-2xl font-black {{ ($activeJob->dailyProduction->efficiency ?? 0) >= 100 ? 'text-green-400' : 'text-yellow-400' }}">
                            {{ $activeJob->dailyProduction->efficiency ?? 0 }}%
                        </p>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-800/50 border border-slate-700 flex flex-col justify-center items-center text-center">
                        <p class="text-[8px] text-slate-500 font-bold uppercase">Lost Time</p>
                        @php $lostSeconds = $activeJob->downtimes->sum('duration_seconds'); @endphp
                        <p class="text-xl font-black text-red-500">{{ round($lostSeconds / 60) }}<span class="text-[10px] ml-0.5">MIN</span></p>
                    </div>
                </div>
                
                {{-- QUICK INPUT BOARD --}}
                @if(strtolower($activeJob->status) == 'running' && !$isDandori)
                <div class="p-4 rounded-2xl bg-blue-600/10 border border-blue-500/30 shadow-lg shadow-blue-900/20">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Quick Entry</span>
                        <div class="flex items-center gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                            <span class="text-[8px] font-bold text-blue-500 uppercase">Live Sync</span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- OK QTY -->
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-[8px] font-bold text-slate-400 uppercase">Actual OK</span>
                                <span class="text-[10px] font-black text-white tabular-nums" id="active-actual-display">0</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button onclick="stepInput('active-actual-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs shadow-lg shadow-blue-900/40 transition-all active:scale-95">+1</button>
                                <button onclick="stepInput('active-actual-{{ $activeJob->id }}', 5, {{ $activeJob->id }})" class="py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs shadow-lg shadow-indigo-900/40 transition-all active:scale-95">+5</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- REPAIR -->
                            <div>
                                <span class="text-[8px] font-bold text-slate-400 uppercase block mb-1">Repair</span>
                                <div class="flex gap-1">
                                    <button onclick="stepInput('active-repair-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-2 rounded-lg bg-orange-500/20 border border-orange-500/30 text-orange-400 font-black text-[10px] hover:bg-orange-500 hover:text-white transition-all">+1</button>
                                    <button onclick="stepInput('active-repair-{{ $activeJob->id }}', 5, {{ $activeJob->id }})" class="flex-1 py-2 rounded-lg bg-orange-500/40 text-white font-black text-[10px] hover:bg-orange-500 transition-all">+5</button>
                                </div>
                            </div>
                            <!-- REJECT -->
                            <div>
                                <span class="text-[8px] font-bold text-slate-400 uppercase block mb-1 text-right">Reject</span>
                                <div class="flex gap-1">
                                    <button onclick="stepInput('active-reject-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-2 rounded-lg bg-red-500/20 border border-red-500/30 text-red-400 font-black text-[10px] hover:bg-red-500 hover:text-white transition-all">+1</button>
                                    <button onclick="stepInput('active-reject-{{ $activeJob->id }}', 5, {{ $activeJob->id }})" class="flex-1 py-2 rounded-lg bg-red-500/40 text-white font-black text-[10px] hover:bg-red-500 transition-all">+5</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Hidden inputs for active job to ensure stepInput always has a target even if row is not in current pagination --}}
                <div class="hidden">
                    <input type="number" id="active-actual-{{ $activeJob->id }}" value="0">
                    <input type="number" id="active-repair-{{ $activeJob->id }}" value="0">
                    <input type="number" id="active-reject-{{ $activeJob->id }}" value="0">
                </div>
                @endif


                @if($isDandori)
                    <div class="bg-amber-500/10 border-2 border-amber-500/50 rounded-2xl p-4 text-center mb-4">
                        <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-1">Dandori Running</p>
                        <p id="dandori-timer-{{ $activeJob->id }}" class="text-3xl font-black text-white tracking-tighter">00:00:00</p>
                    </div>
                @endif

                <div id="control-board-actions">
                    @if(!$activeJob->started_at && !$isDandori)
                        <button onclick="jsStartDandori({{ $activeJob->id }})" class="w-full py-4 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-sm shadow-xl shadow-emerald-900/40 transition-all flex items-center justify-center gap-2 group border-b-4 border-emerald-700 active:border-0 active:translate-y-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                            MULAI DANDORI (PERSIAPAN)
                        </button>
                    @elseif($isDandori)
                        <button onclick="jsStopDandori({{ $activeJob->id }}, {{ $isDandori->id }})" class="w-full py-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-sm shadow-xl shadow-amber-900/40 transition-all flex items-center justify-center gap-2 group border-b-4 border-amber-700 active:border-0 active:translate-y-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                            STOP DANDORI & LANJUT PRODUKSI?
                        </button>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="openDowntimeReport({{ $activeJob->id }}, null)" class="flex-1 py-3.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 font-black text-[10px] uppercase transition-all flex items-center justify-center gap-2 hover:bg-slate-700 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                Cek List Downtime
                            </button>
                            <button onclick="finishJob({{ $activeJob->id }}, '{{ $activeJob->job_number }}', '{{ addslashes($activeJob->job_name) }}')" class="flex-1 py-3.5 rounded-xl bg-red-600/10 border border-red-500/50 text-red-500 font-black text-[10px] uppercase transition-all flex items-center justify-center gap-2 group hover:bg-red-600 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                SELESAIKAN JOB 
                            </button>
                        </div>
                    @endif
                </div>

                @if(!$isDandori && $activeJob->started_at)
                <div class="space-y-4 pt-2 border-t border-slate-800">
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button type="button" id="downtime-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'downtime', 'produksi')" class="dt-btn py-2 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-[9px] font-black uppercase hover:bg-red-500 hover:text-white transition-all">Downtime</button>
                        <button type="button" id="tryout-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'tryout', 'try out')" class="to-btn py-2 rounded-lg bg-orange-500/10 border border-orange-500/30 text-orange-400 text-[9px] font-black uppercase hover:bg-orange-500 hover:text-white transition-all">Try Out</button>
                        {{-- <button type="button" id="idle-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'idle', 'idle time')" class="idle-btn py-2 rounded-lg bg-slate-700/50 border border-slate-600 text-slate-300 text-[9px] font-black uppercase hover:bg-slate-600 hover:text-white transition-all">Idle</button> --}}
                        <button type="button" id="break-btn-{{ $activeJob->id }}" onclick="handleQuickDowntime({{ $activeJob->id }}, 'break', 'break time')" class="break-btn py-2 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-[9px] font-black uppercase hover:bg-indigo-500 hover:text-white transition-all">Break</button>
                        <a href="{{ route('operational.dandori', ['job_id' => $activeJob->id, 'line' => $activeJob->line, 'shift' => 'Shift 1']) }}" class="col-span-2 py-2 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-[9px] font-black uppercase text-center hover:bg-blue-500 hover:text-white transition-all">Dandori Module</a>
                    </div>
                </div>
                @endif
            </div>

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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
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
@else
    {{-- STANDBY CARD --}}
    <div class="bg-slate-900 rounded-3xl p-6 md:p-12 shadow-2xl border-4 border-slate-800 text-white flex flex-col items-center justify-center text-center relative overflow-hidden min-h-[300px]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(59,130,246,0.05)_0%,transparent_70%)]"></div>
        <div class="relative z-10 flex flex-col items-center">
            <div class="w-20 h-20 rounded-3xl bg-slate-800 flex items-center justify-center mb-6 shadow-xl border border-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-2xl font-black text-slate-400 uppercase tracking-widest mb-2">Ready to Start</h2>
            <p class="text-slate-500 text-sm max-w-md italic font-medium mb-8">Pilih item produksi dari antrean di bawah untuk memulai persiapan (Dandori).</p>
            
            <div class="w-full max-w-2xl">
                <div class="flex items-center gap-2 mb-3 ml-1">
                    <div class="w-1.5 h-4 bg-blue-600 rounded-full"></div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Select Item to Enqueue</label>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch gap-3 bg-slate-800/40 p-3 rounded-[2rem] border border-slate-700/50">
                    <div class="flex-1 relative" id="custom-select-container">
                        <input type="hidden" id="standby-job-select" value="">
                        <button type="button" onclick="toggleCustomSelect()" id="custom-select-trigger" class="w-full h-14 bg-slate-900/90 border border-slate-700 rounded-2xl px-6 flex items-center justify-between text-sm text-slate-400 hover:text-white hover:border-blue-500/50 transition-all outline-none shadow-inner group/select">
                            <span id="custom-select-label">-- Pilih Item dari Antrean --</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500 group-hover/select:text-blue-400 transition-transform duration-300" id="custom-select-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div id="custom-select-menu" class="hidden absolute bottom-full mb-3 left-0 right-0 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden z-[110] max-h-60 overflow-y-auto">
                            <div class="p-2 space-y-1">
                                @forelse($pendingJobs as $pj)
                                <div onclick="selectCustomItem('{{ $pj->id }}', '[{{ $pj->job_number }}] {{ $pj->job_name }}')" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-blue-600/20 hover:text-blue-400 cursor-pointer transition-all group/item">
                                    <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500 group-hover/item:bg-blue-600 group-hover/item:text-white transition-all">
                                        {{ substr($pj->line ?? '??', 0, 2) }}
                                    </div>
                                    <div class="flex-1 min-w-0 text-left">
                                        <p class="text-[10px] font-black text-slate-500 uppercase leading-none mb-1 group-hover/item:text-blue-300">{{ $pj->job_number }}</p>
                                        <p class="text-xs font-bold truncate">{{ $pj->job_name }}</p>
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
                    <button onclick="enqueueJob(document.getElementById('standby-job-select').value)" class="h-14 px-4 md:px-8 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-black text-[10px] md:text-xs shadow-xl shadow-blue-900/20 transition-all uppercase tracking-widest flex items-center justify-center gap-3 group/btn w-full md:w-auto md:min-w-[200px]">
                        <span>MASUKKAN ANTRIAN</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
