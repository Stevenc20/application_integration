@if($plans->isNotEmpty())
{{-- COMPACT STATS ROW --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    <div class="pa-stat-card stat-total">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Produksi</span>
        <p class="text-2xl font-black text-slate-800 mt-1">{{ number_format($stats->total_qty) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Pcs diproduksi</p>
    </div>
    <div class="pa-stat-card stat-ok">
        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">OK</span>
        <p class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats->total_ok) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Produk baik</p>
    </div>
    <div class="pa-stat-card stat-repair">
        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Repair</span>
        <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats->total_repair) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Produk repair</p>
    </div>
    <div class="pa-stat-card stat-reject">
        <span class="text-[10px] font-bold text-red-600 uppercase tracking-widest">Reject</span>
        <p class="text-2xl font-black text-red-600 mt-1">{{ number_format($stats->total_reject) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Produk reject</p>
    </div>
    <div class="pa-stat-card stat-achievement">
        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Achievement</span>
        <p class="text-2xl font-black mt-1 {{ $achievement >= 80 ? 'text-emerald-600' : ($achievement >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $achievement }}%</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Target {{ number_format(max($stats->total_target, $totalPlanTarget)) }} pcs</p>
    </div>
</div>

{{-- OVERALL TIME BREAKDOWN --}}
@php
    $tRepair = $downtimeAgg->repair_sec ?? 0;
    $tDandori = $downtimeAgg->dandori_sec ?? 0;
    $tTryout = $downtimeAgg->tryout_sec ?? 0;
    $tBreak = $downtimeAgg->break_sec ?? 0;
    $tProd = $stats->total_runtime ?? 0;
    $tTimeTotal = max($tRepair + $tDandori + $tTryout + $tBreak + $tProd, 1);

    $segments = [
        ['label'=>'Downtime', 'color'=>'bg-red-600', 'sec'=>$tRepair],
        ['label'=>'Dandori', 'color'=>'bg-amber-500', 'sec'=>$tDandori],
        ['label'=>'Try Out', 'color'=>'bg-orange-500', 'sec'=>$tTryout],
        ['label'=>'Break Time', 'color'=>'bg-indigo-500', 'sec'=>$tBreak],
        ['label'=>'Production', 'color'=>'bg-blue-500', 'sec'=>$tProd],
    ];
@endphp
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-5">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Overall Time Breakdown</h3>
        <span class="text-[11px] font-black text-slate-500 tabular-nums">Total {{ gmdate('H:i', $tTimeTotal) }}</span>
    </div>
    <div class="h-6 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden flex">
        @foreach($segments as $seg)
            @php $pct = ($seg['sec'] / $tTimeTotal) * 100; @endphp
            @if($pct > 0)
            <div class="h-full {{ $seg['color'] }} transition-all duration-500" style="width: {{ $pct }}%"></div>
            @endif
        @endforeach
    </div>
    <div class="flex flex-wrap gap-x-5 mt-2.5 text-[10px] text-slate-500 font-medium">
        @foreach($segments as $seg)
            @if($seg['sec'] > 0)
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full {{ $seg['color'] }}"></span>{{ $seg['label'] }} {{ gmdate('H:i', $seg['sec']) }}</span>
            @endif
        @endforeach
    </div>
</div>

{{-- PER-JOB CARDS --}}
<div class="space-y-3">
    @foreach($plans as $plan)
    @php
        $job = $plan->job_data;
        $dp = $job?->dailyProduction;
        $isBreak = $plan->row_type === 'break';

        $actualOk = $dp ? (int)$dp->actual_ok : 0;
        $actualRepair = $dp ? (int)$dp->actual_repair : 0;
        $actualReject = $dp ? (int)$dp->actual_reject : 0;
        $actualQty = $dp ? (int)$dp->actual_qty : 0;
        $sessionSeconds = 0;
        if ($job) {
            foreach ($job->productionSessions as $ps) {
                $psSec = (int)$ps->total_seconds;
                if ($ps->status === 'running' && $ps->start_time) {
                    $psStart = $ps->start_time instanceof \Carbon\Carbon
                        ? $ps->start_time
                        : \Carbon\Carbon::parse($ps->start_time);
                    $psSec += max(0, (int)round(abs(now()->floatDiffInSeconds($psStart))));
                }
                $sessionSeconds += $psSec;
            }
        }
        $jobDowntime = $job ? (int)$job->downtimes->sum('duration_seconds') : 0;
        $runtime = max(0, $sessionSeconds - $jobDowntime);
        $target = ($dp && (int)$dp->target_qty > 0) 
            ? (int)$dp->target_qty 
            : (($job && (int)$job->target_qty > 0) 
                ? (int)$job->target_qty 
                : (int)($plan->plan ?? 0));

        $jRepair = $job ? $job->downtimes->where('jenis_downtime','repair')->sum('duration_seconds') : 0;
        $jTryOut = $job ? $job->downtimes->whereIn('jenis_downtime', ['try out','tryout'])->sum('duration_seconds') : 0;
        $jBreakTime = $job ? $job->downtimes->whereIn('jenis_downtime', ['break time','break'])->sum('duration_seconds') : 0;
        $jProd = $runtime;
        $jFirstCheck = 0;
        if ($job && $job->relationLoaded('dandoris')) {
            foreach ($job->dandoris->where('jenis_dandori', '1st_check') as $fc) {
                if ($fc->start_time) {
                    $s = \Carbon\Carbon::parse($fc->start_time);
                    $e = $fc->finish_time ? \Carbon\Carbon::parse($fc->finish_time) : now();
                    $jFirstCheck += $s->diffInSeconds($e);
                }
            }
        }
        $jDandoriSec = 0;
        if ($job && $job->relationLoaded('dandoriSessions')) {
            foreach ($job->dandoriSessions as $ds) {
                if ($ds->start_time && $ds->finish_time) {
                    $jDandoriSec += \Carbon\Carbon::parse($ds->start_time)->diffInSeconds(\Carbon\Carbon::parse($ds->finish_time));
                }
            }
        }
        $jTotal = max($jRepair + $jProd, 1);
        $jRepairPct = ($jRepair / $jTotal) * 100;
        $jProdPct = ($jProd / $jTotal) * 100;

        $actualTotal = $actualOk + $actualRepair + $actualReject;
        $denom = max($target, $actualTotal, 1);
        $achievedPct = $target > 0 ? round(($actualQty / $target) * 100) : 0;
        $okPct = ($actualOk / $denom) * 100;
        $repairPct = ($actualRepair / $denom) * 100;
        $rejectPct = ($actualReject / $denom) * 100;

        $badgeClass = match(strtolower($job->status ?? 'pending')) {
            'running' => 'bg-emerald-500',
            'pending' => 'bg-amber-400',
            'complete','finished' => 'bg-blue-500',
            default => 'bg-slate-300'
        };
        $shiftLabel = $dp->shift ?? $plan->shift_name ?? '-';
        $pressLabel = str_replace('PRESS ', '', $plan->press_name ?? '');
    @endphp

    @if($isBreak)
    {{-- Break row --}}
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 flex items-center gap-3">
        <div class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></div>
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">{{ $plan->job_master ?? 'BREAK' }}</span>
        @if($plan->start_time && $plan->finish_time)
        <span class="text-[10px] text-indigo-400 font-mono">{{ substr($plan->start_time, 0, 5) }} - {{ substr($plan->finish_time, 0, 5) }}</span>
        @endif
    </div>
    @else
    @php
        $isCompleted = $job && in_array(strtolower($job->status ?? ''), ['complete', 'finished']);
    @endphp
    <div class="job-card p-5">
        {{-- TOP ROW: item spec + quantities + status --}}
        <div class="grid grid-cols-12 gap-4 mb-4">
            {{-- ITEM SPEC (col 1-4) --}}
            <div class="col-span-12 md:col-span-4">
                <div class="flex flex-col gap-1">
                    <span class="font-black text-slate-800 text-sm tracking-tight leading-none">{{ $plan->job_no ?: 'NO JOB NO' }}</span>
                    <span class="text-[10px] font-medium text-slate-400 leading-tight">{{ $plan->job_master ?: 'NO MASTER NAME' }}</span>
                    <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-md bg-slate-800 text-white text-[8px] font-black tracking-wide uppercase">{{ $plan->press_name ?? 'N/A' }}</span>
                        <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-600 text-[8px] font-bold tracking-wide uppercase border border-blue-100">POS {{ $plan->row_no }}</span>
                        @if($job)
                        <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded-md text-white {{ $badgeClass }} shadow-sm">{{ $job->status }}</span>
                        @else
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 text-[8px] font-bold uppercase">Planned</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- QUANTITIES (col 5-8) --}}
            <div class="col-span-12 md:col-span-4">
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-[10px] max-w-[220px] mx-auto">
                    <div class="flex flex-col border-l-2 border-slate-300 pl-2.5">
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[8px]">Target</span>
                        <span class="font-black text-slate-800 text-xs">{{ number_format($target) }}</span>
                    </div>
                    <div class="flex flex-col border-l-2 border-emerald-500 pl-2.5">
                        <span class="text-emerald-600 font-bold uppercase tracking-wider text-[8px]">OK</span>
                        <span class="font-black text-emerald-600 text-sm">{{ number_format($actualOk) }}</span>
                    </div>
                    <div class="flex flex-col border-l-2 border-amber-500 pl-2.5">
                        <span class="text-amber-600 font-bold uppercase tracking-wider text-[8px]">Repair</span>
                        <span class="font-black text-amber-600 text-sm">{{ number_format($actualRepair) }}</span>
                    </div>
                    <div class="flex flex-col border-l-2 border-red-500 pl-2.5">
                        <span class="text-red-600 font-bold uppercase tracking-wider text-[8px]">Reject</span>
                        <span class="font-black text-red-600 text-sm">{{ number_format($actualReject) }}</span>
                    </div>
                    <div class="col-span-2 mt-1 pt-2 border-t border-slate-100 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-slate-400 font-bold uppercase tracking-wider text-[7px]">Achievement</span>
                            <div class="flex items-center gap-1">
                                <span class="font-black text-slate-800 text-[10px] tabular-nums">{{ number_format($actualQty) }}</span>
                                <span class="text-slate-300 font-bold">/</span>
                                <span class="font-black text-slate-400 text-[10px] tabular-nums">{{ number_format($target) }}</span>
                            </div>
                        </div>
                        <div class="px-2.5 py-1 rounded-lg min-w-[48px] flex items-center justify-center {{ $achievedPct >= 100 ? 'bg-emerald-600' : 'bg-slate-800' }} text-white shadow-sm">
                            <span class="text-[10px] font-black leading-none tabular-nums">{{ $achievedPct }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STATUS & RUNTIME (col 9-12) --}}
            <div class="col-span-12 md:col-span-4 flex flex-col items-center justify-center">
                @if($isCompleted)
                <div class="w-full py-3 px-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span class="text-[11px] font-black uppercase tracking-widest text-emerald-600">Completed</span>
                </div>
                <span class="text-[9px] font-bold text-slate-400 uppercase mt-2.5">Total Runtime</span>
                <span class="text-base font-black text-slate-800 tabular-nums">{{ floor($runtime/60).'m '.($runtime%60).'s' }}</span>
                @elseif($job)
                <span class="text-[9px] font-bold text-slate-400 uppercase">Runtime</span>
                <span class="text-base font-black text-slate-800 tabular-nums">{{ floor($runtime/60).'m '.($runtime%60).'s' }}</span>
                @else
                <span class="text-[9px] font-bold text-slate-300 uppercase">Belum mulai</span>
                @endif
                @if($job)
                <a href="{{ route('analytics.production.job', array_filter(['id' => $job->id, 'date_from' => $dateFrom ?? null, 'date_to' => $dateTo ?? null, 'line' => $line ?? null, 'shift' => $shift ?? null, 'status' => $status ?? null])) }}" class="mt-2.5 px-4 py-1.5 rounded-lg bg-blue-600 text-white text-[9px] font-black hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                    Detail &rarr;
                </a>
                @endif
            </div>
        </div>

        {{-- TIMELINES: two bars --}}
        <div class="space-y-4">
            {{-- PROGRESS BAR (like Input Harian) --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Production Progress (OK / Repair / Reject)</span>
                    <span class="text-[9px] font-black {{ $achievedPct >= 100 ? 'text-emerald-600' : 'text-blue-600' }}">{{ $achievedPct }}%</span>
                </div>
                <div class="relative h-4 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden flex">
                    @if($okPct > 0)<div class="h-full bg-emerald-500 transition-all duration-500" style="width: {{ $okPct }}%"></div>@endif
                    @if($repairPct > 0)<div class="h-full bg-amber-500 transition-all duration-500" style="width: {{ $repairPct }}%"></div>@endif
                    @if($rejectPct > 0)<div class="h-full bg-rose-500 transition-all duration-500" style="width: {{ $rejectPct }}%"></div>@endif
                </div>
            </div>

            {{-- ACTUAL SEGMENTED EXECUTION BAR (positioned segments like Input Harian) --}}
            @php
                $segments = [];
                $earliest = null;
                $latest = null;
                // 1. Production sessions (with overtime split)
                if ($job) {
                    foreach ($job->productionSessions as $ps) {
                        $s = $ps->start_time ? \Carbon\Carbon::parse($ps->start_time) : null;
                        $e = $ps->status === 'running' 
                            ? ($job->finished_at ? \Carbon\Carbon::parse($job->finished_at) : now()) 
                            : ($ps->finish_time ? \Carbon\Carbon::parse($ps->finish_time) : ($s && ($ps->total_seconds ?? 0) > 0 ? $s->copy()->addSeconds((int) $ps->total_seconds) : ($job->finished_at ? \Carbon\Carbon::parse($job->finished_at) : now())));
                        if ($s) {
                            // Build deadline RELATIVE to actual start (matches Input Harian JS)
                            $sessionDeadline = null;
                            if ($plan->start_time && $plan->finish_time) {
                                $pStart = str_contains($plan->start_time, '-')
                                    ? \Carbon\Carbon::parse($plan->start_time)
                                    : \Carbon\Carbon::parse($s->format('Y-m-d').' '.$plan->start_time);
                                $pFinish = str_contains($plan->finish_time, '-')
                                    ? \Carbon\Carbon::parse($plan->finish_time)
                                    : \Carbon\Carbon::parse($s->format('Y-m-d').' '.$plan->finish_time);
                                $durationSec = (int) max(abs($pFinish->diffInSeconds($pStart)), 1);
                                $sessionDeadline = $s->copy()->addSeconds($durationSec);
                            } elseif ($job && $job->plan_start && $job->plan_end) {
                                try {
                                    $jobPlanStart = \Carbon\Carbon::parse($job->plan_start);
                                    $jobPlanEnd = \Carbon\Carbon::parse($job->plan_end);
                                    $durationSec = (int) max(abs($jobPlanEnd->diffInSeconds($jobPlanStart)), 1);
                                    $sessionDeadline = $s->copy()->addSeconds($durationSec);
                                } catch (\Exception $e) {}
                            }

                            if ($sessionDeadline && $e > $sessionDeadline && $s < $sessionDeadline) {
                                $segments[] = ['type'=>'production','label'=>'Production','color'=>'bg-blue-600','start'=>$s,'end'=>$sessionDeadline];
                                $segments[] = ['type'=>'overtime','label'=>'Overtime','color'=>'bg-red-600','start'=>$sessionDeadline,'end'=>$e];
                            } else {
                                $segments[] = ['type'=>'production','label'=>'Production','color'=>'bg-blue-600','start'=>$s,'end'=>$e ?? $s];
                            }
                            if (!$earliest || $s < $earliest) $earliest = $s;
                            if ($e && (!$latest || $e > $latest)) $latest = $e;
                        }
                    }
                }

                // 2. Dandori sessions
                if ($job) {
                    foreach ($job->dandoriSessions as $ds) {
                        $s = $ds->start_time ? \Carbon\Carbon::parse($ds->start_time) : null;
                        $e = $ds->finish_time ? \Carbon\Carbon::parse($ds->finish_time) : ($s ? $s->copy()->addMinutes(5) : null);
                        if ($s) {
                            $segments[] = ['type'=>'dandori','label'=>'Dandori','color'=>'bg-amber-400','start'=>$s,'end'=>$e ?? $s];
                            if (!$earliest || $s < $earliest) $earliest = $s;
                            if ($e && (!$latest || $e > $latest)) $latest = $e;
                        }
                    }
                }

                // 2b. 1st Check (from dandoris table, purple)
                if ($job && $job->relationLoaded('dandoris')) {
                    foreach ($job->dandoris->where('jenis_dandori', '1st_check') as $fc) {
                        $s = $fc->start_time ? \Carbon\Carbon::parse($fc->start_time) : null;
                        $e = $fc->finish_time ? \Carbon\Carbon::parse($fc->finish_time) : ($s ? $s->copy()->addMinutes(5) : null);
                        if ($s) {
                            $segments[] = ['type'=>'1st_check','label'=>'1st Check','color'=>'bg-purple-500','start'=>$s,'end'=>$e ?? $s];
                            if (!$earliest || $s < $earliest) $earliest = $s;
                            if ($e && (!$latest || $e > $latest)) $latest = $e;
                        }
                    }
                }

                // 3. Downtimes
                if ($job) {
                    foreach ($job->downtimes as $dt) {
                        $s = $dt->start_time ? \Carbon\Carbon::parse($dt->start_time) : null;
                        $dur = (int)($dt->duration_seconds ?? 0);
                        $e = $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time) : ($s && $dur > 0 ? $s->copy()->addSeconds($dur) : null);
                        if ($s && $dur > 0) {
                            $dtType = strtolower($dt->jenis_downtime ?? '');
                            $isDandori = $dtType === 'dandori';
                            $isTryOut = in_array($dtType, ['try out','tryout']);
                            $isBreak = in_array($dtType, ['break time','break']);
                            $segmentColor = $isDandori ? 'bg-amber-400' : ($isTryOut ? 'bg-orange-500' : ($isBreak ? 'bg-indigo-500' : 'bg-red-600'));
                            if ($isTryOut) $jTryOut += $dur;
                            if ($isBreak) $jBreakTime += $dur;
                            $segments[] = [
                                'type' => $isDandori ? 'dandori' : ($isTryOut ? 'tryout' : ($isBreak ? 'break' : 'downtime')),
                                'label' => $isDandori ? 'Dandori' : ($isTryOut ? 'Try Out' : ($isBreak ? 'Break Time' : ($dt->jenis_downtime ?? 'Downtime'))),
                                'color' => $segmentColor,
                                'start' => $s,
                                'end' => $e ?? $s,
                                'detail' => $dt->problem ?? ''
                            ];
                            if (!$earliest || $s < $earliest) $earliest = $s;
                            if ($e && (!$latest || $e > $latest)) $latest = $e;
                        }
                    }
                }

                // 4. Fallback: if no events, use runtime to estimate
                if (!$earliest && $jProd > 0) {
                    $earliest = \Carbon\Carbon::parse($dateFrom.' 07:00:00');
                    $latest = $earliest->copy()->addSeconds((int)($jProd + $jRepair));
                }
                if (!$earliest) $earliest = \Carbon\Carbon::now()->subHour();
                if (!$latest) $latest = $earliest->copy()->addHour();

                // Sort segments by start time
                usort($segments, function($a, $b) {
                    return $a['start']->timestamp <=> $b['start']->timestamp;
                });

                $totalDur = $latest && $earliest ? max(1, $latest->timestamp - $earliest->timestamp) : 1;
                $segIdx = 0;
            @endphp
            @php
                // Generate tick marks
                $ticks = [];
                if ($earliest && $latest && $totalDur > 1) {
                    $durationHours = $earliest->diffInHours($latest);
                    if ($durationHours <= 2) {
                        $intervalMinutes = 15;
                    } elseif ($durationHours <= 6) {
                        $intervalMinutes = 30;
                    } elseif ($durationHours <= 12) {
                        $intervalMinutes = 60;
                    } else {
                        $intervalMinutes = 120;
                    }
                    
                    $currTick = $earliest->copy()->startOfHour();
                    if ($currTick < $earliest) {
                        $currTick->addHours(1);
                    }
                    while ($currTick <= $latest) {
                        $leftTick = (($currTick->timestamp - $earliest->timestamp) / $totalDur) * 100;
                        if ($leftTick >= 0 && $leftTick <= 100) {
                            $ticks[] = [
                                'time' => $currTick->format('H:i'),
                                'left' => $leftTick
                            ];
                        }
                        $currTick->addMinutes($intervalMinutes);
                    }
                    $jOvertime = 0;
                    foreach ($segments as $seg) {
                        if ($seg['type'] === 'overtime') {
                            $jOvertime += $seg['end']->timestamp - $seg['start']->timestamp;
                        }
                    }
                    $jProdAdjusted = max(0, $jProd - $jOvertime);
                } else {
                    $jOvertime = 0;
                    $jProdAdjusted = $jProd;
                }
            @endphp
            <div class="space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest">Actual Segmented Execution</span>
                    </div>
                    
                    @if($job)
                    {{-- ZOOM CONTROLS --}}
                    <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5 border border-slate-200 scale-90 select-none">
                        <button type="button" onclick="zoomTimeline('{{ $plan->id }}', -0.5)" class="w-5 h-5 flex items-center justify-center rounded bg-white border border-slate-200 shadow-sm text-slate-500 hover:bg-slate-50 text-[10px] font-black">&minus;</button>
                        <span id="zoom-val-{{ $plan->id }}" class="text-[8px] font-bold text-slate-600 px-1.5 font-mono">1.0x</span>
                        <button type="button" onclick="zoomTimeline('{{ $plan->id }}', 0.5)" class="w-5 h-5 flex items-center justify-center rounded bg-white border border-slate-200 shadow-sm text-slate-500 hover:bg-slate-50 text-[10px] font-black">+</button>
                        <button type="button" onclick="resetZoomTimeline('{{ $plan->id }}')" class="px-1.5 py-0.5 rounded text-[8px] font-bold text-slate-400 hover:text-slate-600 uppercase">Reset</button>
                    </div>
                    @endif
                    
                    <span class="text-[9px] font-black text-slate-400">{{ $jProd > 0 ? 'Runtime '.gmdate('H:i', $jProd) : 'Belum mulai' }}</span>
                </div>
                
                <div class="relative w-full overflow-x-auto select-none py-1 scrollbar-thin scrollbar-thumb-slate-300" id="scroll-container-{{ $plan->id }}">
                    <div class="relative flex flex-col gap-1.5 transition-all duration-200" style="width: 100%; min-width: 100%;" id="aseg-container-{{ $plan->id }}">
                        {{-- Timeline Bar --}}
                        <div class="relative h-10 bg-slate-900 rounded-xl border-2 border-slate-800 shadow-2xl overflow-hidden">
                            <div class="absolute inset-0 rounded-lg" id="aseg-{{ $plan->id }}">
                                @foreach($segments as $seg)
                                @php
                                    $left = max(0, (($seg['start']->timestamp - $earliest->timestamp) / $totalDur) * 100);
                                    $widthP = max(0.1, (($seg['end']->timestamp - $seg['start']->timestamp) / $totalDur) * 100);
                                    $segIdx++;
                                    $ttId = 'tt-aseg-'.$plan->id.'-'.$segIdx;
                                    $from = $seg['start']->format('H:i:s');
                                    $to = $seg['end']->format('H:i:s');
                                    $duration = $seg['start']->diffAsCarbonInterval($seg['end'])->forHumans(['short'=>true,'parts'=>2]);
                                    if (!$duration || $duration === '0 seconds') $duration = round($seg['end']->timestamp - $seg['start']->timestamp).'s';
                                @endphp
                                <div class="absolute h-full {{ $seg['color'] }} cursor-pointer border-r border-white/20 hover:brightness-110 transition-all"
                                     style="left: {{ $left }}%; width: {{ $widthP }}%;"
                                     onmouseover="showTimelineTooltip(event, '{{ $seg['label'] }}', '{{ $from }}', '{{ $to }}', '{{ $duration }}', '{{ $seg['detail'] ?? '' }}')"
                                     onmouseout="hideTimelineTooltip()"
                                     title="{{ $seg['label'] }}: {{ $from }} - {{ $to }} ({{ $duration }})">
                                </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- Time Ticks --}}
                        <div class="relative h-4 w-full">
                            @foreach($ticks as $tick)
                                <div class="absolute flex flex-col items-center -translate-x-1/2" style="left: {{ $tick['left'] }}%">
                                    <div class="w-[1px] h-1 bg-slate-300"></div>
                                    <span class="text-[8px] font-black text-slate-400 mt-0.5 font-mono leading-none">{{ $tick['time'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-[9px] text-slate-400">
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>Prod {{ gmdate('H:i', $jProdAdjusted) }}</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>Downtime {{ gmdate('H:i', $jRepair) }}</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Dandori</span>
                    @if($jFirstCheck > 0)<span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>1st Check {{ gmdate('H:i', $jFirstCheck) }}</span>@endif
                    @if($jTryOut > 0)<span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>Try Out {{ gmdate('H:i', $jTryOut) }}</span>@endif
                    @if($jBreakTime > 0)<span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>Break Time {{ gmdate('H:i', $jBreakTime) }}</span>@endif
                    @if($jOvertime > 0)<span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-600 ring-1 ring-red-300"></span>Overtime {{ gmdate('H:i', $jOvertime) }}</span>@endif
                </div>

                {{-- DONUT + OUTSIDE SHIFT + HISTORY LINKS --}}
                @php
                $jTotalTime = max($jProd + $jRepair + $jDandoriSec + $jFirstCheck + $jTryOut + $jBreakTime, 1);
                $donutColors = ['#2563eb','#dc2626','#f59e0b','#a855f7','#f97316','#6366f1'];
                $donutSecs  = [$jProd, $jRepair, $jDandoriSec, $jFirstCheck, $jTryOut, $jBreakTime];
                $donutLabels= ['Prod','Downtime','Dandori','1st Check','Try Out','Break'];
                $gradParts = []; $acc = 0;
                for ($di=0; $di<6; $di++) {
                    $pct = ($donutSecs[$di] / $jTotalTime) * 100;
                    if ($pct > 0) {
                        $gradParts[] = $donutColors[$di].' '.$acc.'% '.($acc+$pct).'%';
                        $acc += $pct;
                    }
                }
                $gradient = implode(', ', $gradParts);
                @endphp
                <div class="flex items-center gap-4 pt-2 border-t border-slate-100 mt-2">
                    <div class="flex items-center gap-2.5">
                        <div class="relative w-10 h-10 shrink-0">
                            <div class="absolute inset-0 rounded-full" style="background: conic-gradient({{ $gradient }})"></div>
                            <div class="absolute inset-[3px] rounded-full bg-white"></div>
                        </div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider leading-tight">Time<br>Breakdown</span>
                    </div>
                    @if(($jOvertime ?? 0) > 0)
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-50 border border-red-200">
                        <span class="w-2 h-2 rounded-full bg-red-600 ring-1 ring-red-300 shrink-0"></span>
                        <span class="text-[9px] font-black text-red-700">Outside Shift <span class="font-mono">{{ gmdate('H:i', $jOvertime) }}</span></span>
                    </div>
                    @endif
                    @if($job)
                    <div class="flex items-center gap-1 ml-auto">
                        <a href="{{ route('operational.dandori', ['job_id' => $job->id]) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-amber-100 text-amber-700 hover:bg-amber-200 transition-all" title="History Dandori">Dandori</a>
                        <a href="{{ route('monitoring.history', ['type' => 'downtime', 'plan_id' => $plan->id]) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-red-100 text-red-700 hover:bg-red-200 transition-all" title="History Downtime">DT</a>
                        <a href="{{ route('monitoring.history', ['type' => 'downtime', 'plan_id' => $plan->id, 'jenis' => 'try+out']) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-orange-100 text-orange-700 hover:bg-orange-200 transition-all" title="History Try Out">Try Out</a>
                        <a href="{{ route('monitoring.history', ['type' => 'break', 'plan_id' => $plan->id]) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-all" title="History Break Time">Break</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>

{{-- DAILY TREND TABLE (collapsed) --}}
@if($dailyTrend->isNotEmpty() && $dailyTrend->count() > 1)
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mt-4">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-bold text-slate-700">Daily Trend</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-5 py-3">Tanggal</th>
                    <th class="text-right px-5 py-3">Qty</th>
                    <th class="text-right px-5 py-3">OK</th>
                    <th class="text-right px-5 py-3">Repair</th>
                    <th class="text-right px-5 py-3">Reject</th>
                    <th class="text-center px-5 py-3">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($dailyTrend as $day)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-700">{{ \Carbon\Carbon::parse($day->work_date)->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right font-bold text-slate-800">{{ number_format($day->qty) }}</td>
                    <td class="px-5 py-3 text-right text-emerald-600 font-semibold">{{ number_format($day->ok) }}</td>
                    <td class="px-5 py-3 text-right text-amber-600 font-semibold">{{ number_format($day->repair) }}</td>
                    <td class="px-5 py-3 text-right text-red-600 font-semibold">{{ number_format($day->reject) }}</td>
                    @php
                        $dayTotal = $day->ok + $day->repair + $day->reject;
                        $dayDenom = max($day->target_qty, $dayTotal, 1);
                        $dayOkPct = ($day->ok / $dayDenom) * 100;
                        $dayRepairPct = ($day->repair / $dayDenom) * 100;
                        $dayRejectPct = ($day->reject / $dayDenom) * 100;
                        $dayOverall = round(($dayTotal / $dayDenom) * 100);
                    @endphp
                    <td class="px-5 py-3 min-w-[140px]">
                        @if($day->target_qty > 0)
                        <div class="flex flex-col gap-1">
                            <div class="timeline-bar h-4">
                                <div class="bg-emerald-500" style="width: {{ $dayOkPct }}%"></div>
                                <div class="bg-amber-500" style="width: {{ $dayRepairPct }}%"></div>
                                <div class="bg-rose-500" style="width: {{ $dayRejectPct }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span>{{ number_format($day->ok) }} + {{ number_format($day->repair) }} + {{ number_format($day->reject) }} / {{ number_format($day->target_qty) }}</span>
                                <span class="font-bold {{ $dayOverall >= 100 ? 'text-emerald-600' : ($dayOverall >= 70 ? 'text-blue-600' : ($dayOverall >= 50 ? 'text-amber-600' : 'text-red-600')) }}">{{ $dayOverall }}%</span>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-300">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@else
{{-- NO DATA --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <div class="text-center py-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        </div>
        <p class="text-slate-600 text-sm font-bold">Tidak ada jadwal PPC untuk periode ini</p>
        <p class="text-[11px] text-slate-400 mt-1">Coba ubah filter tanggal, line, atau shift.</p>
    </div>
</div>
@endif
