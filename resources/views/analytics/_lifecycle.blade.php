{{-- ACTIVE JOBS --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Active Jobs
        </h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $active->count() }} jobs</span>
    </div>
    @if($active->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-5 py-3">Job No</th>
                    <th class="text-left px-5 py-3">Nama</th>
                    <th class="text-left px-5 py-3">Line</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-center px-5 py-3">Progress</th>
                    <th class="text-left px-5 py-3">Mulai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($active as $job)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono font-bold text-slate-700">{{ $job->job_number ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $job->job_name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $job->line ?? '-' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $job->status === 'running' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $job->status === 'running' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                            {{ strtoupper($job->status) }}
                        </span>
                    </td>
                    @php
                        $aOk = $job->total_ok ?? 0;
                        $aRepair = $job->total_repair ?? 0;
                        $aReject = $job->total_reject ?? 0;
                        $aTarget = $job->target_qty ?? 0;
                        $aTotal = $aOk + $aRepair + $aReject;
                        $aDenom = max($aTarget, $aTotal, 1);
                        $aOkPct = ($aOk / $aDenom) * 100;
                        $aRepairPct = ($aRepair / $aDenom) * 100;
                        $aRejectPct = ($aReject / $aDenom) * 100;
                        $aOverall = round(($aTotal / $aDenom) * 100);
                    @endphp
                    <td class="px-5 py-3 min-w-[140px]">
                        @if($aTarget > 0)
                        <div class="flex flex-col gap-1">
                            <div class="h-4 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden flex">
                                <div class="h-full bg-emerald-500 transition-all" style="width: {{ $aOkPct }}%"></div>
                                <div class="h-full bg-amber-500 transition-all" style="width: {{ $aRepairPct }}%"></div>
                                <div class="h-full bg-rose-500 transition-all" style="width: {{ $aRejectPct }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span>{{ number_format($aOk) }} + {{ number_format($aRepair) }} + {{ number_format($aReject) }} / {{ number_format($aTarget) }}</span>
                                <span class="font-bold {{ $aOverall >= 100 ? 'text-emerald-600' : ($aOverall >= 70 ? 'text-blue-600' : ($aOverall >= 50 ? 'text-amber-600' : 'text-red-600')) }}">{{ $aOverall }}%</span>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $job->started_at ? \Carbon\Carbon::parse($job->started_at)->format('d M H:i') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8 text-slate-400 text-sm font-medium">Tidak ada job aktif.</div>
    @endif
</div>

{{-- COMPLETED (last 3 months) --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Completed (3 Bulan Terakhir)
        </h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $completed->count() }} jobs</span>
    </div>
    @if($completed->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-5 py-3">Job No</th>
                    <th class="text-left px-5 py-3">Nama</th>
                    <th class="text-left px-5 py-3">Line</th>
                    <th class="text-center px-5 py-3">Progress</th>
                    <th class="text-left px-5 py-3">Selesai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($completed as $job)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono font-bold text-slate-700">{{ $job->job_number ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $job->job_name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $job->line ?? '-' }}</td>
                    @php
                        $cOk = $job->total_ok ?? 0;
                        $cRepair = $job->total_repair ?? 0;
                        $cReject = $job->total_reject ?? 0;
                        $cTarget = $job->target_qty ?? 0;
                        $cTotal = $cOk + $cRepair + $cReject;
                        $cDenom = max($cTarget, $cTotal, 1);
                        $cOkPct = ($cOk / $cDenom) * 100;
                        $cRepairPct = ($cRepair / $cDenom) * 100;
                        $cRejectPct = ($cReject / $cDenom) * 100;
                        $cOverall = round(($cTotal / $cDenom) * 100);
                    @endphp
                    <td class="px-5 py-3 min-w-[140px]">
                        @if($cTarget > 0)
                        <div class="flex flex-col gap-1">
                            <div class="h-4 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden flex">
                                <div class="h-full bg-emerald-500 transition-all" style="width: {{ $cOkPct }}%"></div>
                                <div class="h-full bg-amber-500 transition-all" style="width: {{ $cRepairPct }}%"></div>
                                <div class="h-full bg-rose-500 transition-all" style="width: {{ $cRejectPct }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span>{{ number_format($cOk) }} + {{ number_format($cRepair) }} + {{ number_format($cReject) }} / {{ number_format($cTarget) }}</span>
                                <span class="font-bold {{ $cOverall >= 100 ? 'text-emerald-600' : ($cOverall >= 70 ? 'text-blue-600' : ($cOverall >= 50 ? 'text-amber-600' : 'text-red-600')) }}">{{ $cOverall }}%</span>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $job->finished_at ? \Carbon\Carbon::parse($job->finished_at)->format('d M Y H:i') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8 text-slate-400 text-sm font-medium">Belum ada job completed.</div>
    @endif
</div>

{{-- ARCHIVED --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Archive (> 3 Bulan)
        </h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $archived->total() }} jobs</span>
    </div>
    @if($archived->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-5 py-3">Job No</th>
                    <th class="text-left px-5 py-3">Nama</th>
                    <th class="text-left px-5 py-3">Line</th>
                    <th class="text-center px-5 py-3">Progress</th>
                    <th class="text-left px-5 py-3">Selesai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($archived as $job)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono font-bold text-slate-700">{{ $job->job_number ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $job->job_name ?? '-' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $job->line ?? '-' }}</td>
                    @php
                        $rOk = $job->total_ok ?? 0;
                        $rRepair = $job->total_repair ?? 0;
                        $rReject = $job->total_reject ?? 0;
                        $rTarget = $job->target_qty ?? 0;
                        $rTotal = $rOk + $rRepair + $rReject;
                        $rDenom = max($rTarget, $rTotal, 1);
                        $rOkPct = ($rOk / $rDenom) * 100;
                        $rRepairPct = ($rRepair / $rDenom) * 100;
                        $rRejectPct = ($rReject / $rDenom) * 100;
                        $rOverall = round(($rTotal / $rDenom) * 100);
                    @endphp
                    <td class="px-5 py-3 min-w-[140px]">
                        @if($rTarget > 0)
                        <div class="flex flex-col gap-1">
                            <div class="h-4 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden flex">
                                <div class="h-full bg-emerald-500 transition-all" style="width: {{ $rOkPct }}%"></div>
                                <div class="h-full bg-amber-500 transition-all" style="width: {{ $rRepairPct }}%"></div>
                                <div class="h-full bg-rose-500 transition-all" style="width: {{ $rRejectPct }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span>{{ number_format($rOk) }} + {{ number_format($rRepair) }} + {{ number_format($rReject) }} / {{ number_format($rTarget) }}</span>
                                <span class="font-bold {{ $rOverall >= 100 ? 'text-emerald-600' : ($rOverall >= 70 ? 'text-blue-600' : ($rOverall >= 50 ? 'text-amber-600' : 'text-red-600')) }}">{{ $rOverall }}%</span>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $job->finished_at ? \Carbon\Carbon::parse($job->finished_at)->format('d M Y H:i') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $archived->links() }}
    </div>
    @else
    <div class="text-center py-8 text-slate-400 text-sm font-medium">Belum ada archive.</div>
    @endif
</div>
