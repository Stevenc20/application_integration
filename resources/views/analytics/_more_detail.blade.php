@if($dailyRecords->isNotEmpty())
{{-- STATS CARDS --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="pa-stat-card stat-total">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Produksi</span>
        <p class="text-2xl font-black text-slate-800 mt-1">{{ number_format($stats->total_qty) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">{{ $stats->total_lines }} line · {{ $stats->total_jobs }} jobs</p>
    </div>
    <div class="pa-stat-card stat-ok">
        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">OK</span>
        <p class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats->total_ok) }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Repair {{ number_format($stats->total_repair) }} · Reject {{ number_format($stats->total_reject) }}</p>
    </div>
    <div class="pa-stat-card stat-achievement">
        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Achievement</span>
        <p class="text-2xl font-black mt-1 {{ $achievement >= 80 ? 'text-emerald-600' : ($achievement >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $achievement }}%</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Target {{ number_format($stats->total_target) }} pcs</p>
    </div>
    <div class="pa-stat-card">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Runtime</span>
        <p class="text-2xl font-black text-slate-800 mt-1">{{ $stats->total_runtime ? gmdate('H:i', $stats->total_runtime) : '00:00' }}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">Downtime {{ $stats->total_downtime ? gmdate('H:i', $stats->total_downtime) : '00:00' }}</p>
    </div>
</div>

{{-- TIME BREAKDOWN --}}
@php
    $tRepair = $downtimeAgg->repair_sec ?? 0;
    $tDandori = $downtimeAgg->dandori_sec ?? 0;
    $tTryout = $downtimeAgg->tryout_sec ?? 0;
    $tBreak = $downtimeAgg->break_sec ?? 0;
    $tProd = $stats->total_runtime ?? 0;
    $tTimeTotal = max($tRepair + $tDandori + $tTryout + $tBreak + $tProd, 1);
    $segments = [
        ['label'=>'Repair', 'color'=>'bg-red-600', 'sec'=>$tRepair],
        ['label'=>'Dandori', 'color'=>'bg-amber-500', 'sec'=>$tDandori],
        ['label'=>'Try Out', 'color'=>'bg-orange-500', 'sec'=>$tTryout],
        ['label'=>'Break', 'color'=>'bg-indigo-500', 'sec'=>$tBreak],
        ['label'=>'Production', 'color'=>'bg-blue-500', 'sec'=>$tProd],
    ];
@endphp
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Time Breakdown</h3>
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

{{-- DAILY PRODUCTION TABLE --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Daily Production Records</h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $dailyRecords->count() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Line</th>
                    <th class="text-left px-4 py-3">Shift</th>
                    <th class="text-right px-4 py-3">Target</th>
                    <th class="text-right px-4 py-3">OK</th>
                    <th class="text-right px-4 py-3">Repair</th>
                    <th class="text-right px-4 py-3">Reject</th>
                    <th class="text-right px-4 py-3">Runtime</th>
                    <th class="text-right px-4 py-3">Achievement</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($dailyRecords as $r)
                @php
                    $targetQty = $r->target_qty ?: ($r->jobMaster?->target_qty ?? 0);
                    $ach = $targetQty > 0 ? round(($r->actual_ok / $targetQty) * 100, 1) : 0;
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-700">{{ \Carbon\Carbon::parse($r->work_date)->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->line ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ ($r->shift ?? '') === 'Shift Pagi' ? 'bg-blue-100 text-blue-600' : 'bg-indigo-100 text-indigo-600' }}">{{ $r->shift ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-600">{{ number_format($targetQty) }}</td>
                    <td class="px-4 py-3 text-right text-emerald-600 font-semibold">{{ number_format($r->actual_ok ?? 0) }}</td>
                    <td class="px-4 py-3 text-right text-amber-600 font-semibold">{{ number_format($r->actual_repair ?? 0) }}</td>
                    <td class="px-4 py-3 text-right text-red-600 font-semibold">{{ number_format($r->actual_reject ?? 0) }}</td>
                    <td class="px-4 py-3 text-right font-mono text-slate-600">{{ $r->runtime_seconds ? gmdate('H:i', $r->runtime_seconds) : '-' }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $ach >= 80 ? 'text-emerald-600' : ($ach >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $ach }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- DOWNTIME TABLE --}}
@if($allDowntimes->isNotEmpty())
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Downtime Events</h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $allDowntimes->count() }} events</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-4 py-3">Start</th>
                    <th class="text-left px-4 py-3">Jenis</th>
                    <th class="text-left px-4 py-3">Problem</th>
                    <th class="text-left px-4 py-3">Penyebab</th>
                    <th class="text-left px-4 py-3">PIC</th>
                    <th class="text-right px-4 py-3">Duration</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($allDowntimes as $d)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ \Carbon\Carbon::parse($d->start_time)->format('d M H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold
                            {{ strtolower($d->jenis_downtime ?? '') === 'repair' ? 'bg-red-100 text-red-600' : '' }}
                            {{ in_array(strtolower($d->jenis_downtime ?? ''), ['dandori']) ? 'bg-amber-100 text-amber-600' : '' }}
                            {{ in_array(strtolower($d->jenis_downtime ?? ''), ['try out','tryout']) ? 'bg-orange-100 text-orange-600' : '' }}
                            {{ in_array(strtolower($d->jenis_downtime ?? ''), ['break time','break']) ? 'bg-indigo-100 text-indigo-600' : '' }}
                        ">{{ $d->jenis_downtime }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 max-w-[200px] truncate">{{ $d->problem ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-500 max-w-[200px] truncate">{{ $d->penyebab ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $d->pic ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-slate-600 font-semibold">{{ $d->duration_seconds ? gmdate('H:i', $d->duration_seconds) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- PRODUCTION LOGS --}}
@if($allLogs->isNotEmpty())
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Production Logs</h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $allLogs->count() }} logs</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-4 py-3">Time</th>
                    <th class="text-right px-4 py-3">OK</th>
                    <th class="text-right px-4 py-3">Repair</th>
                    <th class="text-right px-4 py-3">Reject</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($allLogs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d M H:i') : '-' }}</td>
                    <td class="px-4 py-3 text-right text-emerald-600 font-semibold">{{ number_format($log->ok_qty ?? 0) }}</td>
                    <td class="px-4 py-3 text-right text-amber-600 font-semibold">{{ number_format($log->repair_qty ?? 0) }}</td>
                    <td class="px-4 py-3 text-right text-red-600 font-semibold">{{ number_format($log->reject_qty ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- REPAIR / REJECT LOGS --}}
@if($allRepairRejects->isNotEmpty())
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Repair & Reject Details</h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $allRepairRejects->count() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-4 py-3">Time</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Defect</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-right px-4 py-3">Qty A</th>
                    <th class="text-right px-4 py-3">Qty B</th>
                    <th class="text-left px-4 py-3">Root Cause</th>
                    <th class="text-left px-4 py-3">Countermeasure</th>
                    <th class="text-left px-4 py-3">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($allRepairRejects as $rr)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $rr->created_at ? \Carbon\Carbon::parse($rr->created_at)->format('d M H:i') : '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $rr->type === 'repair' ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600' }}">
                            {{ ucfirst($rr->type ?? '') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-700 font-medium">{{ $rr->defect_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $rr->repair_category ?? '-' }}</td>
                    <td class="px-4 py-3 text-right text-slate-600">{{ number_format($rr->qty_a ?? 0) }}</td>
                    <td class="px-4 py-3 text-right text-slate-600">{{ number_format($rr->qty_b ?? 0) }}</td>
                    <td class="px-4 py-3 text-slate-500 max-w-[150px] truncate">{{ $rr->root_cause ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-500 max-w-[150px] truncate">{{ $rr->countermeasure ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $rr->creator?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- DANDORI / CHANGEOVER --}}
@if($allDandoris->isNotEmpty())
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Dandori / Changeover</h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $allDandoris->count() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-4 py-3">Time</th>
                    <th class="text-left px-4 py-3">Line</th>
                    <th class="text-left px-4 py-3">Shift</th>
                    <th class="text-left px-4 py-3">Activity</th>
                    <th class="text-left px-4 py-3">Jenis</th>
                    <th class="text-right px-4 py-3">Duration</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($allDandoris as $dn)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $dn->created_at ? \Carbon\Carbon::parse($dn->created_at)->format('d M H:i') : '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $dn->line ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $dn->shift ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-700 font-medium">{{ $dn->activity ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-600">{{ $dn->jenis_dandori ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-slate-600 font-semibold">{{ isset($dn->duration_minutes) ? $dn->duration_minutes . ' min' : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@else
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
    <p class="text-slate-500 font-bold">Tidak ada data untuk periode ini.</p>
    <p class="text-xs text-slate-400 mt-1">Coba ubah rentang tanggal, line, atau shift yang dipilih.</p>
</div>
@endif
