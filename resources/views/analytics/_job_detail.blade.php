@extends('layouts.supervisor')

@section('title', 'Job Detail - ' . ($job->job_number ?? ''))

@section('scripts')
<script>
    // Timeline zoom functions
    window.zoomLevels = window.zoomLevels || {};
    function zoomTimeline(id, delta) {
        if (!window.zoomLevels[id]) window.zoomLevels[id] = 1.0;
        let newZoom = window.zoomLevels[id] + delta;
        if (newZoom < 1.0) newZoom = 1.0;
        if (newZoom > 10.0) newZoom = 10.0;
        window.zoomLevels[id] = newZoom;
        
        const container = document.getElementById('aseg-container-' + id);
        const labelVal = document.getElementById('zoom-val-' + id);
        if (container) {
            container.style.width = (newZoom * 100) + '%';
            container.style.minWidth = (newZoom * 100) + '%';
        }
        if (labelVal) {
            labelVal.innerText = newZoom.toFixed(1) + 'x';
        }
    }
    function resetZoomTimeline(id) {
        window.zoomLevels[id] = 1.0;
        const container = document.getElementById('aseg-container-' + id);
        const labelVal = document.getElementById('zoom-val-' + id);
        if (container) {
            container.style.width = '100%';
            container.style.minWidth = '100%';
        }
        if (labelVal) {
            labelVal.innerText = '1.0x';
        }
    }

    function showTimelineTooltip(e, label, from, to, duration, detail) {
        try {
            let el = document.getElementById('timeline-tooltip');
            if (!el) {
                el = document.createElement('div');
                el.id = 'timeline-tooltip';
                el.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;background:#1e293b;color:white;border-radius:10px;padding:8px 12px;font-size:11px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.3);border:1px solid #334155;max-width:260px;';
                document.body.appendChild(el);
            }
            const lc = (label||'').toLowerCase();
            const lColor = lc.includes('dandori') ? '#fbbf24' : lc.includes('production') ? '#60a5fa' : lc.includes('1st') ? '#a855f7' : lc.includes('try') ? '#f97316' : lc.includes('break') ? '#6366f1' : '#f87171';
            el.innerHTML = '<div style="font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;color:' + lColor + ';">' + (label||'') + '</div>'
                + '<div style="display:flex;gap:12px;font-size:10px;color:#94a3b8;">'
                + '<span>' + (from||'') + ' → ' + (to||'') + '</span>'
                + '<span style="color:white;font-weight:700;">' + (duration||'') + '</span>'
                + '</div>'
                + (detail ? '<div style="font-size:9px;color:#64748b;margin-top:3px;border-top:1px solid #334155;padding-top:3px;">' + detail + '</div>' : '');
            el.style.display = 'block';
            let x = e.clientX + 12;
            let y = e.clientY - 10;
            if (x + 270 > window.innerWidth) x = e.clientX - 270;
            if (y < 0) y = e.clientY + 20;
            el.style.left = x + 'px';
            el.style.top = y + 'px';
        } catch(err) {
            console.warn('Tooltip error:', err);
        }
    }
    function hideTimelineTooltip() {
        let el = document.getElementById('timeline-tooltip');
        if (el) el.style.display = 'none';
    }
</script>
@endsection

@section('content')
<style>
    .pa-page { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .detail-timeline-bar {
        height: 12px;
        border-radius: 999px;
        overflow: hidden;
        display: flex;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    .detail-timeline-bar > div {
        height: 100%;
        transition: width 0.5s cubic-bezier(.4,0,.2,1);
    }
    .section-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.2s ease;
    }
    .section-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    .section-card .section-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: #fafbfc;
    }
    .session-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        flex-shrink: 0;
    }
    .session-line {
        width: 2px;
        background: #e2e8f0;
        flex-shrink: 0;
    }
    .detail-stat {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 18px;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    .detail-stat:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transform: translateY(-1px);
    }
    .detail-stat::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }
    .detail-stat.s-ok::before { background: linear-gradient(90deg, #059669, #10b981); }
    .detail-stat.s-repair::before { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .detail-stat.s-reject::before { background: linear-gradient(90deg, #dc2626, #f87171); }
    .detail-stat.s-target::before { background: linear-gradient(90deg, #1e293b, #475569); }
</style>

<div class="pa-page space-y-6">
    {{-- Back button & header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('analytics.production', ['tab' => 'overview'] + request()->except('tab', 'id')) }}" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-500 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                &larr; Kembali
            </a>
            <div>
                <h1 class="text-lg font-black text-slate-800 tracking-tight">{{ $job->job_number ?? 'JOB#'.$job->id }}</h1>
                <p class="text-xs text-slate-400 font-medium">{{ $job->job_name }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-[10px] font-black uppercase tracking-wide shadow-sm">{{ $job->line ?? '-' }}</span>
            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wide text-white shadow-sm
                {{ strtolower($job->status ?? 'pending') === 'running' ? 'bg-emerald-500' : (strtolower($job->status ?? 'pending') === 'complete' ? 'bg-blue-500' : 'bg-amber-400') }}">
                {{ $job->status ?? 'PENDING' }}
            </span>
        </div>
    </div>

    @php
        $dp = $job->dailyProduction;
        $actualOk = $dp ? (int)$dp->actual_ok : 0;
        $actualRepair = $dp ? (int)$dp->actual_repair : 0;
        $actualReject = $dp ? (int)$dp->actual_reject : 0;
        $actualQty = $dp ? (int)$dp->actual_qty : 0;
        $runtime = $dp ? (int)$dp->runtime_seconds : 0;
        $target = ($dp && (int)$dp->target_qty > 0) 
            ? (int)$dp->target_qty 
            : (($job && (int)$job->target_qty > 0) 
                ? (int)$job->target_qty 
                : 0);
        $downtimeSec = $dp ? (int)$dp->downtime_seconds : 0;

        $jRepair = $job->downtimes->where('jenis_downtime','repair')->sum('duration_seconds');
        $jProd = $runtime;
        $jFirstCheck = 0;
        if (isset($dandoris)) {
            foreach ($dandoris as $fc) {
                if ($fc->start_time) {
                    $s = \Carbon\Carbon::parse($fc->start_time);
                    $e = $fc->finish_time ? \Carbon\Carbon::parse($fc->finish_time) : now();
                    $jFirstCheck += $s->diffInSeconds($e);
                }
            }
        }
        $jTryOut = $job->downtimes->whereIn('jenis_downtime', ['try out','tryout'])->sum('duration_seconds');
        $jBreakTime = $job->downtimes->whereIn('jenis_downtime', ['break time','break'])->sum('duration_seconds');
        $jDandoriSec = 0;
        foreach ($dandoriSessions as $ds) {
            if ($ds->start_time && $ds->finish_time) {
                $jDandoriSec += \Carbon\Carbon::parse($ds->start_time)->diffInSeconds(\Carbon\Carbon::parse($ds->finish_time));
            }
        }
        $jTotal = max($jRepair + $jProd, 1);
        $jRepairPct = ($jRepair / $jTotal) * 100;
        $jProdPct = ($jProd / $jTotal) * 100;

        $actualTotal = $actualOk + $actualRepair + $actualReject;
        $denom = max($target, $actualTotal, 1);
        $okPct = ($actualOk / $denom) * 100;
        $repairPct = ($actualRepair / $denom) * 100;
        $rejectPct = ($actualReject / $denom) * 100;
        $achievedPct = $target > 0 ? round(($actualQty / $target) * 100) : 0;
    @endphp

    {{-- TARGET ACHIEVEMENT --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Target Achievement</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-black {{ $achievedPct >= 100 ? 'text-emerald-600' : ($achievedPct >= 80 ? 'text-blue-600' : 'text-red-600') }}">{{ $achievedPct }}%</span>
                <span class="text-sm text-slate-400 font-medium">dari target {{ number_format($target) }} pcs</span>
            </div>
            @if($target > 0)
            <div class="flex items-center gap-2 mt-1.5">
                @php $diff = $actualQty - $target; @endphp
                @if($diff >= 0)
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-100">&#10003; Tercapai ({{ number_format($diff) }} pcs di atas target)</span>
                @else
                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2.5 py-0.5 rounded-md border border-red-100">&#10007; Belum Tercapai ({{ number_format(abs($diff)) }} pcs kurang)</span>
                @endif
            </div>
            @endif
        </div>
        <div class="text-right">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Produksi</span>
            <p class="text-2xl font-black text-slate-800 tabular-nums">{{ number_format($actualQty) }}</p>
            <span class="text-[10px] text-slate-500 font-medium">OK {{ number_format($actualOk) }} | Repair {{ number_format($actualRepair) }} | Reject {{ number_format($actualReject) }}</span>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="detail-stat s-ok">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">OK</span>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($actualOk) }}</p>
        </div>
        <div class="detail-stat s-repair">
            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">Repair</span>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($actualRepair) }}</p>
        </div>
        <div class="detail-stat s-reject">
            <span class="text-[10px] font-bold text-red-600 uppercase tracking-widest">Reject</span>
            <p class="text-2xl font-black text-red-600 mt-1">{{ number_format($actualReject) }}</p>
        </div>
        <div class="detail-stat s-target">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Target</span>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ number_format($target) }}</p>
        </div>
    </div>

    {{-- Progress & Timeline Bars --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Production Progress --}}
        <div class="section-card p-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Production Progress</h3>
            <div class="detail-timeline-bar h-5 mb-2">
                @if($okPct > 0)<div class="bg-emerald-500" style="width: {{ $okPct }}%"></div>@endif
                @if($repairPct > 0)<div class="bg-amber-500" style="width: {{ $repairPct }}%"></div>@endif
                @if($rejectPct > 0)<div class="bg-rose-500" style="width: {{ $rejectPct }}%"></div>@endif
            </div>
            <div class="flex items-center justify-between text-[10px]">
                <span class="text-slate-500">{{ number_format($actualQty) }} / {{ number_format($target) }} pcs</span>
                <span class="font-black {{ $achievedPct >= 100 ? 'text-emerald-600' : 'text-blue-600' }}">{{ $achievedPct }}%</span>
            </div>
        </div>

        {{-- Segmented Timeline Bar (same as overview) --}}
        <div class="section-card p-4">
            @php
                $segments = [];
                $earliest = null;
                $latest = null;

                $thePlan = $job->productionPlan;

                // Production sessions — only for runtime calc + earliest/latest, NOT visual segments.
                // Visual production bars are rebuilt as gap-fill below.
                foreach ($sessions as $ps) {
                    $s = $ps->start_time ? \Carbon\Carbon::parse($ps->start_time) : null;
                    $e = $ps->status === 'running'
                        ? ($job->finished_at ? \Carbon\Carbon::parse($job->finished_at) : now())
                        : ($ps->finish_time ? \Carbon\Carbon::parse($ps->finish_time) : ($s && ($ps->total_seconds ?? 0) > 0 ? $s->copy()->addSeconds((int) $ps->total_seconds) : ($job->finished_at ? \Carbon\Carbon::parse($job->finished_at) : now())));
                    if ($s) {
                        if (!$earliest || $s < $earliest) $earliest = $s;
                        if ($e && (!$latest || $e > $latest)) $latest = $e;
                    }
                }

                foreach ($dandoriSessions as $ds) {
                    $s = $ds->start_time ? \Carbon\Carbon::parse($ds->start_time) : null;
                    $e = $ds->finish_time ? \Carbon\Carbon::parse($ds->finish_time) : ($s ? $s->copy()->addMinutes(5) : null);
                    if ($s) {
                        $segments[] = ['type'=>'dandori','label'=>'Dandori','color'=>'bg-amber-400','start'=>$s,'end'=>$e ?? $s];
                        if (!$earliest || $s < $earliest) $earliest = $s;
                        if ($e && (!$latest || $e > $latest)) $latest = $e;
                    }
                }

                if (isset($dandoris)) {
                    foreach ($dandoris as $fc) {
                        $s = $fc->start_time ? \Carbon\Carbon::parse($fc->start_time) : null;
                        $e = $fc->finish_time ? \Carbon\Carbon::parse($fc->finish_time) : ($s ? $s->copy()->addMinutes(5) : null);
                        if ($s) {
                            $segments[] = ['type'=>'1st_check','label'=>'1st Check','color'=>'bg-purple-500','start'=>$s,'end'=>$e ?? $s];
                            if (!$earliest || $s < $earliest) $earliest = $s;
                            if ($e && (!$latest || $e > $latest)) $latest = $e;
                        }
                    }
                }

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

                if (!$earliest && $jProd > 0) {
                    $earliest = now()->subSeconds((int)($jProd + $jRepair));
                }
                if (!$earliest) $earliest = now()->subHour();
                if (!$latest) $latest = $earliest->copy()->addHour();

                usort($segments, function($a, $b) {
                    return $a['start']->timestamp <=> $b['start']->timestamp;
                });

                // Fill gaps between non-production segments with Production bars
                if (count($segments) > 0) {
                    $nonProd = array_values(array_filter($segments, fn($s) => $s['type'] !== 'production' && $s['type'] !== 'overtime'));
                    if (count($nonProd) > 0) {
                        usort($nonProd, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);
                        $gapStart = $nonProd[0]['start']->copy();
                        $prodSegs = [];
                        $prodDeadline = null;
                        if ($thePlan && $thePlan->start_time && $thePlan->finish_time) {
                            $pS = str_contains($thePlan->start_time, '-') ? \Carbon\Carbon::parse($thePlan->start_time) : \Carbon\Carbon::parse($gapStart->format('Y-m-d').' '.$thePlan->start_time);
                            $pF = str_contains($thePlan->finish_time, '-') ? \Carbon\Carbon::parse($thePlan->finish_time) : \Carbon\Carbon::parse($gapStart->format('Y-m-d').' '.$thePlan->finish_time);
                            $prodDeadline = $gapStart->copy()->addSeconds((int) max(abs($pF->diffInSeconds($pS)), 1));
                        } elseif ($job->plan_start && $job->plan_end) {
                            try { $prodDeadline = $gapStart->copy()->addSeconds((int) max(abs(\Carbon\Carbon::parse($job->plan_end)->diffInSeconds(\Carbon\Carbon::parse($job->plan_start))), 1)); } catch (\Exception $e) {}
                        }
                        foreach ($nonProd as $seg) {
                            $segStart = $seg['start'];
                            if ($segStart->timestamp > $gapStart->timestamp) {
                                if ($prodDeadline && $segStart->timestamp > $prodDeadline->timestamp && $gapStart->timestamp < $prodDeadline->timestamp) {
                                    $prodSegs[] = ['type'=>'production','label'=>'Production','color'=>'bg-blue-600','start'=>$gapStart->copy(),'end'=>$prodDeadline->copy()];
                                    $prodSegs[] = ['type'=>'overtime','label'=>'Overtime','color'=>'bg-red-600','start'=>$prodDeadline->copy(),'end'=>$segStart->copy()];
                                } else {
                                    $prodSegs[] = ['type'=>'production','label'=>'Production','color'=>'bg-blue-600','start'=>$gapStart->copy(),'end'=>$segStart->copy()];
                                }
                            }
                            $segEnd = $seg['end'] ?? $seg['start'];
                            if ($segEnd->timestamp > $gapStart->timestamp) $gapStart = $segEnd->copy();
                        }
                        $segments = array_merge($segments, $prodSegs);
                        usort($segments, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);
                    }
                }

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
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Actual Segmented Timeline</h3>
                
                {{-- ZOOM CONTROLS --}}
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-0.5 border border-slate-200 scale-90 select-none">
                    <button type="button" onclick="zoomTimeline('{{ $job->id }}', -0.5)" class="w-5 h-5 flex items-center justify-center rounded bg-white border border-slate-200 shadow-sm text-slate-500 hover:bg-slate-50 text-[10px] font-black">&minus;</button>
                    <span id="zoom-val-{{ $job->id }}" class="text-[8px] font-bold text-slate-600 px-1.5 font-mono">1.0x</span>
                    <button type="button" onclick="zoomTimeline('{{ $job->id }}', 0.5)" class="w-5 h-5 flex items-center justify-center rounded bg-white border border-slate-200 shadow-sm text-slate-500 hover:bg-slate-50 text-[10px] font-black">+</button>
                    <button type="button" onclick="resetZoomTimeline('{{ $job->id }}')" class="px-1.5 py-0.5 rounded text-[8px] font-bold text-slate-400 hover:text-slate-600 uppercase">Reset</button>
                </div>
            </div>
            
            <div class="relative w-full overflow-x-auto select-none py-1 scrollbar-thin scrollbar-thumb-slate-300" id="scroll-container-{{ $job->id }}">
                <div class="relative flex flex-col gap-1.5 transition-all duration-200" style="width: 100%; min-width: 100%;" id="aseg-container-{{ $job->id }}">
                    {{-- Timeline Bar --}}
                    <div class="relative h-10 bg-slate-900 rounded-xl border-2 border-slate-800 shadow-2xl overflow-hidden">
                        <div class="absolute inset-0 rounded-lg">
                            @foreach($segments as $seg)
                            @php
                                $left = max(0, (($seg['start']->timestamp - $earliest->timestamp) / $totalDur) * 100);
                                $widthP = max(0.1, (($seg['end']->timestamp - $seg['start']->timestamp) / $totalDur) * 100);
                                $segIdx++;
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
            
            <div class="flex flex-wrap gap-x-4 text-[10px] text-slate-500 mt-1">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-600"></span>Prod {{ gmdate('H:i', $jProdAdjusted) }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-600"></span>Down {{ gmdate('H:i', $jRepair) }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Dandori</span>
                @if($jFirstCheck > 0)<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span>1st Check {{ gmdate('H:i', $jFirstCheck) }}</span>@endif
                @if($jTryOut > 0)<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-500"></span>Try Out {{ gmdate('H:i', $jTryOut) }}</span>@endif
                @if($jBreakTime > 0)<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-500"></span>Break {{ gmdate('H:i', $jBreakTime) }}</span>@endif
                @if($jOvertime > 0)<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-600 ring-1 ring-red-300"></span>Overtime {{ gmdate('H:i', $jOvertime) }}</span>@endif
            </div>

            {{-- DONUT + OUTSIDE SHIFT + HISTORY LINKS --}}
            @php
            $jTotalTime = max($jProd + $jRepair + $jDandoriSec + $jFirstCheck + $jTryOut + $jBreakTime, 1);
            $donutSecs  = [$jProd, $jRepair, $jDandoriSec, $jFirstCheck, $jTryOut, $jBreakTime];
            $donutColors = ['#2563eb','#dc2626','#f59e0b','#a855f7','#f97316','#6366f1'];
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
            <div class="flex items-center gap-4 pt-3 mt-3 border-t border-slate-100">
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
                <div class="flex items-center gap-1 ml-auto">
                    <a href="{{ route('operational.dandori', ['job_id' => $job->id]) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-amber-100 text-amber-700 hover:bg-amber-200 transition-all" title="History Dandori">Dandori</a>
                    <a href="{{ route('monitoring.history', ['type' => 'downtime', 'plan_id' => $job->productionPlan?->id]) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-red-100 text-red-700 hover:bg-red-200 transition-all" title="History Downtime">DT</a>
                    <a href="{{ route('monitoring.history', ['type' => 'downtime', 'plan_id' => $job->productionPlan?->id, 'jenis' => 'try+out']) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-orange-100 text-orange-700 hover:bg-orange-200 transition-all" title="History Try Out">Try Out</a>
                    <a href="{{ route('monitoring.history', ['type' => 'break', 'plan_id' => $job->productionPlan?->id]) }}" class="px-2 py-1 rounded-md text-[8px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-all" title="History Break Time">Break</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Production Sessions Timeline --}}
    @if($sessions->isNotEmpty())
    <div class="section-card">
        <div class="section-header flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700">Production Sessions</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $sessions->count() }} sessions</span>
        </div>
        <div class="p-5">
            <div class="space-y-0">
                @foreach($sessions as $s)
                @php
                    $sStatus = strtolower($s->status ?? 'pending');
                    $dotColor = $sStatus === 'running' ? 'bg-emerald-500 border-emerald-300' : ($sStatus === 'finished' ? 'bg-blue-500 border-blue-300' : ($sStatus === 'paused' ? 'bg-amber-500 border-amber-300' : 'bg-slate-400 border-slate-300'));
                @endphp
                <div class="flex gap-4 pb-4 last:pb-0">
                    <div class="flex flex-col items-center gap-1">
                        <div class="session-dot {{ $dotColor }}"></div>
                        @if(!$loop->last)<div class="session-line flex-1 min-h-[20px]"></div>@endif
                    </div>
                    <div class="flex-1 pb-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 uppercase">{{ $s->status }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $s->work_date ? \Carbon\Carbon::parse($s->work_date)->format('d M') : '-' }}</span>
                        </div>
                        <div class="flex gap-4 mt-1 text-[10px] text-slate-500">
                            @if($s->start_time)<span>Start: {{ \Carbon\Carbon::parse($s->start_time)->format('H:i:s') }}</span>@endif
                            @if($s->finish_time)<span>Finish: {{ \Carbon\Carbon::parse($s->finish_time)->format('H:i:s') }}</span>@endif
                            @if($s->total_seconds > 0)<span class="font-bold text-slate-600">Durasi: {{ gmdate('H:i:s', $s->total_seconds) }}</span>@endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Production Logs --}}
    @if($job->productionLogs->isNotEmpty())
    <div class="section-card">
        <div class="section-header flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700">Production Logs</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $job->productionLogs->count() }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Waktu</th>
                        <th class="text-right px-5 py-3">OK</th>
                        <th class="text-right px-5 py-3">Repair</th>
                        <th class="text-right px-5 py-3">Reject</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($job->productionLogs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $log->created_at ? $log->created_at->format('d M H:i:s') : '-' }}</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-semibold">{{ number_format($log->ok_qty ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-amber-600 font-semibold">{{ number_format($log->repair_qty ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-red-600 font-semibold">{{ number_format($log->reject_qty ?? 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Downtime Events --}}
    @if($job->downtimes->isNotEmpty())
    <div class="section-card">
        <div class="section-header flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700">Downtime Events</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $job->downtimes->count() }} events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Waktu Mulai</th>
                        <th class="text-left px-5 py-3">Jenis</th>
                        <th class="text-left px-5 py-3">Problem</th>
                        <th class="text-left px-5 py-3">Penyebab</th>
                        <th class="text-left px-5 py-3">PIC</th>
                        <th class="text-right px-5 py-3">Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($job->downtimes as $dt)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $dt->start_time ? \Carbon\Carbon::parse($dt->start_time)->format('d M H:i') : '-' }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ strtolower($dt->jenis_downtime ?? '') === 'repair' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ strtoupper($dt->jenis_downtime ?? 'DOWN') }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600 text-xs max-w-[200px] truncate">{{ $dt->problem ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-500 text-xs max-w-[150px] truncate">{{ $dt->penyebab ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $dt->pic ?? '-' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-700 text-xs">{{ $dt->duration_seconds ? gmdate('H:i:s', $dt->duration_seconds) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Repair & Reject Detail --}}
    @if($job->repairRejects->isNotEmpty())
    <div class="section-card">
        <div class="section-header flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700">Repair &amp; Reject Details</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $job->repairRejects->count() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Tipe</th>
                        <th class="text-left px-5 py-3">Defect</th>
                        <th class="text-left px-5 py-3">Kategori</th>
                        <th class="text-right px-5 py-3">Qty</th>
                        <th class="text-left px-5 py-3">Root Cause</th>
                        <th class="text-left px-5 py-3">Countermeasure</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($job->repairRejects as $rr)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rr->type === 'repair' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">
                                {{ strtoupper($rr->type ?? 'REJECT') }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs font-medium text-slate-700">{{ $rr->defect_name ?? '-' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $rr->repair_category ?? '-' }}</td>
                        <td class="px-5 py-3 text-right text-xs font-bold text-slate-700">{{ number_format($rr->qty_a ?? 0) }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500 max-w-[200px] truncate">{{ $rr->root_cause ?? '-' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500 max-w-[200px] truncate">{{ $rr->countermeasure ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Dandori Sessions --}}
    @if($dandoriSessions->isNotEmpty())
    <div class="section-card">
        <div class="section-header flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700">Dandori / Changeover</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $dandoriSessions->count() }} sessions</span>
        </div>
        <div class="p-5 space-y-4">
            @foreach($dandoriSessions as $ds)
            <div class="border border-slate-100 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-700">Session #{{ $ds->id }}</span>
                    <span class="text-[10px] font-bold text-slate-400">{{ $ds->total_minutes ? round($ds->total_minutes, 1).' min' : '-' }}</span>
                </div>
                <div class="flex gap-4 text-[10px] text-slate-500 mb-2">
                    @if($ds->start_time)<span>Mulai: {{ \Carbon\Carbon::parse($ds->start_time)->format('d M H:i') }}</span>@endif
                    @if($ds->finish_time)<span>Selesai: {{ \Carbon\Carbon::parse($ds->finish_time)->format('d M H:i') }}</span>@endif
                    <span>Status: <span class="font-bold {{ strtolower($ds->status ?? '') === 'completed' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $ds->status ?? 'ONGOING' }}</span></span>
                </div>
                @if($ds->groups->isNotEmpty())
                <div class="ml-4 space-y-2">
                    @foreach($ds->groups as $g)
                    <div class="border-l-2 border-slate-200 pl-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-600">{{ $g->group_name ?? 'Group #'.$g->id }}</span>
                            <span class="text-[9px] text-slate-400">{{ $g->total_minutes ? round($g->total_minutes, 1).' min' : '-' }}</span>
                        </div>
                        @if($g->details->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($g->details as $d)
                            <span class="px-2 py-0.5 rounded text-[8px] font-bold {{ strtolower($d->status ?? '') === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                {{ $d->activity_name ?? 'Step #'.$d->id }}
                                @if($d->duration_minutes > 0)<span class="ml-1 text-slate-400">({{ round($d->duration_minutes, 1) }}m)</span>@endif
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Jika tidak ada data sama sekali --}}
    @if($job->productionLogs->isEmpty() && $job->downtimes->isEmpty() && $job->repairRejects->isEmpty() && $sessions->isEmpty() && $dandoriSessions->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <p class="text-slate-500 font-bold">Belum ada data detail untuk job ini.</p>
        <p class="text-xs text-slate-400 mt-1">Data akan muncul setelah operator melakukan input produksi.</p>
    </div>
    @endif
</div>
@endsection