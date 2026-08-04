@php
    $canEdit = $canEdit ?? false;
    $timeCell = $timeCell ?? fn($dt) => $dt ? (is_string($dt) ? $dt : $dt->format('H:i')) : '-';
    $lastJobFinish = null;
    foreach ($jobsData as $j) {
        if (($j['row_type'] ?? 'job') === 'job' && ($j['schedule_finish'] ?? null)) {
            $lastJobFinish = $j['schedule_finish'];
        }
    }

    $editTd = function ($planId, $field, $type, $value, $formatted, $tdClass) use ($canEdit) {
        $display = $formatted !== '' && $formatted !== null ? $formatted : '-';
        if (!$canEdit) {
            return '<td class="' . $tdClass . '">' . $display . '</td>';
        }
        return '<td class="' . $tdClass . ' lkh-editable-cell" data-plan-id="' . (int) $planId . '" data-field="' . $field . '" data-type="' . $type . '" data-value="' . e((string) $value) . '" title="Klik untuk edit"><span class="lkh-edit-display">' . $display . '</span><span class="lkh-edit-icon">✎</span></td>';
    };
@endphp

<tbody id="lkh-actual-tbody">
    @forelse ($jobsData as $job)
        @if (($job['row_type'] ?? 'job') === 'break')
        <tr class="break-row">
            <td class="text-center font-bold text-amber-600">—</td>
            <td colspan="7" class="text-center font-black text-amber-900 uppercase tracking-widest">
                <span class="inline-flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    {{ $job['break_label'] ?? $job['job_master'] ?? 'ISTIRAHAT' }}
                </span>
            </td>
            <td class="text-center">
                @if ($job['schedule_start'])
                <span class="time-box">
                    <span class="text-[11px] font-bold text-amber-800">{{ $job['schedule_start']->format('H:i') }}</span>
                </span>
                @endif
            </td>
            <td class="text-center">
                @if ($job['schedule_finish'])
                <span class="time-box">
                    <span class="text-[11px] font-bold text-amber-800">{{ $job['schedule_finish']->format('H:i') }}</span>
                </span>
                @endif
            </td>
            <td colspan="24" class="font-bold text-amber-700 text-center">
                @if ($job['schedule_start'] && $job['schedule_finish'])
                <span class="px-2 py-0.5 rounded-full bg-white border border-amber-200 text-[10px] font-bold text-amber-700">
                    {{ abs($job['schedule_finish']->diffInMinutes($job['schedule_start'])) }} MINS
                </span>
                @endif
            </td>
        </tr>
        @else
        @php
            $actGood = $job['actual_good'] ?? 0;
            $actRepair = $job['actual_repair'] ?? 0;
            $actReject = $job['actual_reject'] ?? 0;
            $totalStroke = $actGood + $actRepair + $actReject;
            $ctActual = $job['act_ct'] ?? 0;
            $ctRecord = $job['plan_ct'] ?? 0;
            $procAct = $job['press_time'] ?? $job['process_time'] ?? 0;
            $dctActual = $job['dandori_time'] ?? 0;
            $tptActual = $job['tpt_act'] ?? 0;
            $tptPlan = $job['tpt_plan'] ?? 0;
            $breakTime = $job['break_time_duration'] ?? 0;
            $workTime = max(0, $tptActual + $breakTime);
            $passRate = $totalStroke > 0 ? ($actGood / $totalStroke * 100) : 0;
            $repairRate = $totalStroke > 0 ? ($actRepair / $totalStroke * 100) : 0;
            $rejectRate = $totalStroke > 0 ? ($actReject / $totalStroke * 100) : 0;
            $oee = $job['oee'] ?? 0;
            $gsphActual = $job['gsph'] ?? 0;
            $planStart = $job['schedule_start'] ?? null;
            $planFinish = $job['schedule_finish'] ?? null;
            if ($planFinish && $lastJobFinish && $planFinish->eq($lastJobFinish)) {
                $planFinish = $shiftDisplayEnd;
            }
            $actStart = $job['actual_start'] ?? null;
            $actFinish = $job['actual_finish'] ?? null;
            $dtDies = $job['dt_breakdown']['dies_t'] ?? 0;
            $dtMach = $job['dt_breakdown']['mach_t'] ?? 0;
            $dtMatl = $job['dt_breakdown']['mat_t'] ?? 0;
            $dtLog = $job['dt_breakdown']['log_t'] ?? 0;
            $dtProd = $job['dt_breakdown']['prod_t'] ?? 0;
            $dtTotal = $job['dt_total'] ?? 0;
            $planId = $job['plan_id'] ?? 0;
        @endphp
        <tr>
            <td class="text-center font-bold text-gray-500">{{ $job['display_no'] ?? $loop->iteration }}</td>
            <td class="text-center font-semibold text-gray-800">{{ $job['job_master'] ?? '-' }}</td>
            <td class="cell-qty">{{ number_format($job['plan_qty'] ?? 0) }}</td>
            <td class="cell-qty font-bold">{{ number_format($totalStroke) }}</td>
            {!! $editTd($planId, 'actual_good', 'qty', $actGood, number_format($actGood), 'cell-qty text-emerald-700 font-bold') !!}
            {!! $editTd($planId, 'actual_repair', 'qty', $actRepair, number_format($actRepair), 'cell-qty text-amber-700') !!}
            {!! $editTd($planId, 'actual_reject', 'qty', $actReject, number_format($actReject), 'cell-qty text-red-600') !!}
            <td class="cell-qty font-bold">{{ number_format($totalStroke) }}</td>
            <td class="text-center cell-time">{{ $timeCell($planStart) }}</td>
            <td class="text-center cell-time">{{ $timeCell($planFinish) }}</td>
            {!! $editTd($planId, 'actual_start', 'time', $actStart ? $actStart->format('H:i') : '', $timeCell($actStart), 'text-center cell-time font-bold text-emerald-800') !!}
            {!! $editTd($planId, 'actual_finish', 'time', $actFinish ? $actFinish->format('H:i') : '', $timeCell($actFinish), 'text-center cell-time font-bold text-emerald-800') !!}
            <td class="text-center font-semibold">{{ number_format($ctRecord,1) }}</td>
            <td class="text-center font-semibold">{{ number_format($ctActual,1) }}</td>
            <td class="cell-qty">@fmtMin($procAct)</td>
            <td class="text-center font-semibold">@fmtMin($job['dies_variant_time'] ?? 0)</td>
            <td class="text-center">@fmtMin($job['qcheck_time'] ?? 0)</td>
            <td class="cell-qty">@fmtMin($dctActual)</td>
            <td class="cell-qty">@fmtMin($dtDies)</td>
            <td class="cell-qty">@fmtMin($dtMach)</td>
            <td class="cell-qty">@fmtMin($dtMatl)</td>
            <td class="cell-qty">@fmtMin($dtLog)</td>
            <td class="cell-qty">@fmtMin($dtProd)</td>
            <td class="cell-qty font-bold">
                @if ($planId && $dtTotal > 0)
                <a href="{{ route('monitoring.history', ['type' => 'downtime', 'plan_id' => $planId, 'date' => $date]) }}" class="text-blue-600 hover:underline" title="Lihat detail downtime">DT</a>
                @endif
                @fmtMin($dtTotal)
            </td>
            <td class="cell-qty">@fmtMin($tptPlan)</td>
            <td class="cell-qty font-bold">@fmtMin($tptActual)</td>
            <td class="text-center text-[10px] font-semibold text-gray-500">{{ $breakTime > 0 ? 'BREAK' : '-' }}</td>
            <td class="cell-qty">@fmtMin($breakTime)</td>
            <td class="cell-qty font-bold">@fmtMin($workTime)</td>
            <td class="cell-qty {{ $passRate >= 98 ? 'text-emerald-700' : 'text-amber-700' }}">
                @if ($planId && ($actRepair > 0 || $actReject > 0))
                <a href="{{ route('supervisor.handwork.index', ['plan_id' => $planId]) }}" class="text-blue-600 hover:underline" title="Lihat detail handwork">HW</a>
                @endif
                {{ number_format($passRate,1) }}
            </td>
            <td class="cell-qty {{ $repairRate <= 1 ? 'text-emerald-700' : 'text-amber-700' }}">{{ number_format($repairRate,1) }}</td>
            <td class="cell-qty {{ $rejectRate <= 2 ? 'text-emerald-700' : 'text-red-600' }}">{{ number_format($rejectRate,1) }}</td>
            <td class="cell-qty font-bold {{ $oee >= 85 ? 'text-emerald-700' : ($oee >= 65 ? 'text-amber-700' : 'text-red-600') }}">{{ number_format($oee,1) }}</td>
            <td class="cell-qty font-bold text-red-900">{{ number_format($gsphActual,0) }}</td>
        </tr>
        @endif
    @empty
        <tr><td colspan="34" class="text-center py-8 text-gray-500 font-bold">Tidak ada jadwal produksi</td></tr>
    @endforelse
</tbody>
@php
    $actRows = collect($jobsData)->where('row_type','job');
    $tActGood = $actRows->sum('actual_good');
    $tActRepair = $actRows->sum('actual_repair');
    $tActReject = $actRows->sum('actual_reject');
    $tActStroke = $tActGood + $tActRepair + $tActReject;
    $tActPlan = $actRows->sum('plan_qty');
    $tActProc = $actRows->sum('press_time');
    $tActDct = $actRows->sum('dandori_time');
    $tActQcheck = $actRows->sum('qcheck_time');
    $tActTptPlan = $actRows->sum('tpt_plan');
    $tActTpt = $actRows->sum('tpt_act');
    $tActBreak = $actRows->sum('break_time_duration');
    $tActWork = $tActTpt + $tActBreak;
    $tDtDies = $actRows->sum(fn($r) => $r['dt_breakdown']['dies_t'] ?? 0);
    $tDtMach = $actRows->sum(fn($r) => $r['dt_breakdown']['mach_t'] ?? 0);
    $tDtMatl = $actRows->sum(fn($r) => $r['dt_breakdown']['mat_t'] ?? 0);
    $tDtLog = $actRows->sum(fn($r) => $r['dt_breakdown']['log_t'] ?? 0);
    $tDtProd = $actRows->sum(fn($r) => $r['dt_breakdown']['prod_t'] ?? 0);
    $tDtTotal = $actRows->sum(fn($r) => $r['dt_total'] ?? 0);
    $tActPassRate = $tActStroke > 0 ? ($tActGood / $tActStroke * 100) : 0;
    $tActRepRate = $tActStroke > 0 ? ($tActRepair / $tActStroke * 100) : 0;
    $tActRejRate = $tActStroke > 0 ? ($tActReject / $tActStroke * 100) : 0;
    $tActOee = $totals['weighted_oee'] ?? 0;
    $tActGsph = $totals['weighted_gsph'] ?? 0;
@endphp
<tfoot id="lkh-actual-tfoot">
    <tr>
        <td></td>
        <td class="font-bold">TOTAL SHIFT</td>
        <td class="cell-qty font-bold">{{ number_format($tActPlan) }}</td>
        <td class="cell-qty font-bold">{{ number_format($tActStroke) }}</td>
        <td class="cell-qty font-bold text-emerald-800">{{ number_format($tActGood) }}</td>
        <td class="cell-qty font-bold text-amber-700">{{ number_format($tActRepair) }}</td>
        <td class="cell-qty font-bold text-red-600">{{ number_format($tActReject) }}</td>
        <td class="cell-qty font-bold">{{ number_format($tActStroke) }}</td>
        <td colspan="4"></td>
        <td></td>
        <td></td>
        <td class="cell-qty font-bold">{{ (int)ceil($tActProc) }}</td>
        <td class="cell-qty font-bold">@fmtMin($actRows->sum('dies_variant_time'))</td>
        <td class="cell-qty font-bold">@fmtMin($tActQcheck)</td>
        <td class="cell-qty font-bold">@fmtMin($tActDct)</td>
        <td class="cell-qty font-bold">@fmtMin($tDtDies)</td>
        <td class="cell-qty font-bold">@fmtMin($tDtMach)</td>
        <td class="cell-qty font-bold">@fmtMin($tDtMatl)</td>
        <td class="cell-qty font-bold">@fmtMin($tDtLog)</td>
        <td class="cell-qty font-bold">@fmtMin($tDtProd)</td>
        <td class="cell-qty font-bold">@fmtMin($tDtTotal)</td>
        <td class="cell-qty font-bold">@fmtMin($tActTptPlan)</td>
        <td class="cell-qty font-bold">@fmtMin($tActTpt)</td>
        <td></td>
        <td class="cell-qty font-bold">@fmtMin($tActBreak)</td>
        <td class="cell-qty font-bold">@fmtMin($tActWork)</td>
        <td class="cell-qty font-bold">{{ number_format($tActPassRate,1) }}</td>
        <td class="cell-qty font-bold">{{ number_format($tActRepRate,1) }}</td>
        <td class="cell-qty font-bold">{{ number_format($tActRejRate,1) }}</td>
        <td class="cell-qty font-bold {{ $tActOee >= 85 ? 'text-emerald-700' : ($tActOee >= 65 ? 'text-amber-700' : 'text-red-600') }}">{{ number_format($tActOee,1) }}</td>
        <td class="cell-qty font-bold text-red-900">{{ number_format($tActGsph,0) }}</td>
    </tr>
</tfoot>
