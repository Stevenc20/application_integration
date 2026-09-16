<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LKH {{ $date }} - {{ $selectedLineName }}</title>
    <style>
        @page { margin: 12mm 10mm; size: landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; color: #1f2937; line-height: 1.3; margin: 0; padding: 0; }
        .header { text-align: center; padding-bottom: 8pt; border-bottom: 2px solid #991B1B; margin-bottom: 10pt; }
        .header h1 { font-size: 11pt; font-weight: 800; color: #991B1B; margin: 0 0 2pt 0; text-transform: uppercase; letter-spacing: 0.5pt; }
        .header h2 { font-size: 14pt; font-weight: 900; color: #1f2937; margin: 0 0 1pt 0; }
        .header p { font-size: 6pt; color: #6b7280; margin: 0; text-transform: uppercase; letter-spacing: 0.3pt; }
        .info-table { width: 100%; margin-bottom: 8pt; }
        .info-table td { padding: 2pt 6pt; font-size: 6.5pt; }
        .info-table .lbl { font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.2pt; width: 50pt; }
        .info-table .val { font-weight: 800; color: #1f2937; }
        .section-title { font-size: 7.5pt; font-weight: 800; color: #991B1B; text-transform: uppercase; letter-spacing: 0.3pt; padding: 4pt 0 2pt 0; border-bottom: 1.5px solid #991B1B; margin-bottom: 4pt; margin-top: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
        table th { background-color: #991B1B; color: #ffffff; font-weight: 700; font-size: 5.5pt; padding: 2.5pt 2pt; text-align: center; text-transform: uppercase; letter-spacing: 0.2pt; border: 0.4pt solid #7a1414; }
        table td { padding: 2pt 2pt; border: 0.4pt solid #d1d5db; font-size: 5.5pt; text-align: center; vertical-align: middle; }
        table tr:nth-child(even) td { background-color: #fafafa; }
        table tfoot td { background-color: #ffe4e6; font-weight: 800; border-top: 1.5px solid #991B1B; }
        table .break-row td { background-color: #fffbeb !important; }
        .l { text-align: center; }
        .r { text-align: center; }
        .b { font-weight: 700; }
        .bb { font-weight: 800; }
        .green { color: #059669; }
        .amber { color: #d97706; }
        .red { color: #dc2626; }
        .red-dark { color: #991B1B; }
        .footer { text-align: center; font-size: 5.5pt; color: #9ca3af; padding-top: 6pt; border-top: 1px solid #e5e7eb; margin-top: 12pt; }
        .sig-section { margin-top: 14pt; }
        .sig-table { width: 80%; margin: 0 auto; }
        .sig-table td { text-align: center; padding: 4pt 10pt; vertical-align: top; border: none; font-size: 6.5pt; }
        .sig-table .lbl { font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.2pt; }
        .sig-table .line { border-top: 1px solid #374151; margin-top: 28pt; padding-top: 3pt; font-weight: 800; color: #1f2937; }
        .sig-signed { color: #059669; font-weight: 700; }
        .sig-unsigned { color: #9ca3af; }
        .group-border { border-right: 1px solid #7a1414 !important; }
        .no-break { page-break-inside: avoid; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h2>INTI PANTJA PRESS INDUSTRI</h2>
        <h1>LAPORAN KERJA HARIAN STAMPING</h1>
        <p>PRODUCTION SECTION — SISTEM TERINTEGRASI</p>
    </div>

    {{-- INFO --}}
    <table class="info-table">
        <tr>
            <td class="lbl">Line</td>
            <td class="val">{{ $selectedLineName }}</td>
            <td class="lbl">Shift</td>
            <td class="val">{{ $latestShiftName }}</td>
            <td class="lbl">Tanggal</td>
            <td class="val">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
        </tr>
    </table>

    {{-- ==================== TABLE 1: SCHEDULE ==================== --}}
    <div class="section-title">1. Production Schedule — PPC Master Timeline</div>
    <table>
        <thead>
            <tr>
                <th colspan="7" style="border-right:1px solid #7a1414;">Schedule</th>
                <th colspan="4" style="border-right:1px solid #7a1414;">Uchi Dandori</th>
                <th>Uchi</th><th>TPT</th><th>Break</th><th>Work</th><th>GSPH</th>
            </tr>
            <tr>
                <th style="width:18pt">No</th>
                <th style="width:120pt" class="l">Job No</th>
                <th style="width:40pt">Plan</th>
                <th style="width:30pt">M/C</th>
                <th style="width:35pt">CT</th>
                <th style="width:35pt">Start</th>
                <th style="width:35pt;border-right:1px solid #7a1414;">Finish</th>
                <th style="width:35pt">Dies</th>
                <th style="width:35pt">Var</th>
                <th style="width:35pt">1stQ</th>
                <th style="width:35pt;border-right:1px solid #7a1414;">Dan</th>
                <th style="width:35pt">Uchi</th>
                <th style="width:35pt">TPT</th>
                <th style="width:35pt">Break</th>
                <th style="width:35pt">Work</th>
                <th style="width:35pt">GSPH</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNo = 0; $schTptPlan = 0; $schBreak = 0; $schWork = 0; $schPlan = 0; $schDan = 0; @endphp
            @forelse($jobsData as $job)
                @php
                    $isBreak = ($job['row_type'] ?? 'job') === 'break';
                    if ($isBreak) {
                        $ss = $job['schedule_start'] ?? null;
                        $sf = $job['schedule_finish'] ?? null;
                        $bd = ($ss && $sf) ? abs($sf->diffInMinutes($ss)) : 0;
                    } else {
                        $rowNo++;
                        $ss = $job['schedule_start'] ?? null;
                        $sf = $job['schedule_finish'] ?? null;
                        $sStart = $ss ? $ss->format('H:i') : '-';
                        $sFin = $sf ? $sf->format('H:i') : '-';
                        $planQty = intval($job['plan_qty'] ?? 0);
                        $dandori = (float)($job['dandori_time'] ?? 0);
                        $procTime = floatval($job['process_time'] ?? 0);
                        $uchi = (int) ceil($procTime);
                        $tptPlan = (float)($job['tpt_plan'] ?? 0);
                        $breakTime = (float)($job['break_time_duration'] ?? 0);
                        $workTime = max(0, ($tptPlan + $breakTime));
                        $gsphVal = intval($job['gsph'] ?? 0);
                        $schPlan += $planQty; $schDan += $dandori; $schTptPlan += $tptPlan;
                        $schBreak += $breakTime; $schWork += $workTime;
                    }
                @endphp
                @if ($isBreak)
                <tr class="break-row">
                    <td>-</td>
                    <td colspan="4" class="l" style="font-weight:700;color:#92400e;">{{ $job['break_label'] ?? 'Istirahat' }}</td>
                    <td>{{ $ss ? $ss->format('H:i') : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ $sf ? $sf->format('H:i') : '-' }}</td>
                    <td colspan="9" style="color:#92400e;font-weight:700;">{{ $bd }} MINS</td>
                </tr>
                @else
                <tr>
                    <td>{{ $rowNo }}</td>
                    <td class="l b">{{ $job['job_master'] ?? '-' }}</td>
                    <td class="r">{{ number_format($planQty,0) }}</td>
                    <td>{{ $job['total_mesin'] ?? '-' }}</td>
                    <td>{{ number_format($job['plan_ct'] ?? 0,1) }}</td>
                    <td>{{ $sStart }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ $sFin }}</td>
                    <td>{{ number_format($job['dies_change_time'] ?? 0,1) }}</td><td>{{ number_format($job['variant_change_time'] ?? 0,1) }}</td><td>{{ number_format($job['qcheck_time'] ?? 0,0) }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($dandori) }}</td>
                    <td>{{ number_format($uchi,0) }}</td>
                    <td class="b">{{ \App\Support\ProductionFormat::minutes($tptPlan) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($breakTime) }}</td>
                    <td class="b">{{ \App\Support\ProductionFormat::minutes($workTime) }}</td>
                    <td class="b red-dark">{{ number_format($gsphVal,0) }}</td>
                </tr>
                @endif
            @empty
                <tr><td colspan="16" style="color:#9ca3af;padding:8pt;">Tidak ada jadwal produksi</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td class="l bb">TOTAL SHIFT</td>
                <td class="r">{{ number_format($schPlan,0) }}</td>
                <td></td><td></td><td></td>
                <td style="border-right:1px solid #7a1414;"></td>
                <td>{{ number_format(collect($jobsData)->where('row_type','job')->sum('dies_change_time'),1) }}</td><td>{{ number_format(collect($jobsData)->where('row_type','job')->sum('variant_change_time'),1) }}</td><td>{{ number_format(collect($jobsData)->where('row_type','job')->sum('qcheck_time'),0) }}</td>
                <td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($schDan) }}</td>
                <td>{{ number_format($schPlan > 0 ? (int)ceil(collect($jobsData)->where('row_type','job')->sum('process_time')) : 0,0) }}</td>
                <td class="bb">{{ \App\Support\ProductionFormat::minutes($schTptPlan) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($schBreak) }}</td>
                <td class="bb">{{ \App\Support\ProductionFormat::minutes($schWork) }}</td>
                <td class="bb">{{ $schTptPlan > 0 ? round($schPlan / ($schTptPlan / 60), 1) : 0 }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ==================== TABLE 2: ACTUAL LAPANGAN ==================== --}}
    <div class="section-title" style="page-break-before:always;">2. Actual Lapangan — Realtime Execution</div>
    <table>
        <thead>
            <tr>
                <th colspan="12" style="border-right:1px solid #7a1414;">Schedule</th>
                <th colspan="2" style="border-right:1px solid #7a1414;">CT</th>
                <th style="border-right:1px solid #7a1414;">Press</th>
                <th colspan="4" style="border-right:1px solid #7a1414;">Uchi Dandori</th>
                <th colspan="5" style="border-right:1px solid #7a1414;">Down Time</th>
                <th colspan="2" style="border-right:1px solid #7a1414;">TPT</th>
                <th colspan="2" style="border-right:1px solid #7a1414;">Break</th>
                <th style="border-right:1px solid #7a1414;">Work</th>
                <th colspan="3" style="border-right:1px solid #7a1414;">Quality</th>
                <th style="border-right:1px solid #7a1414;">OEE</th>
                <th>GSPH</th>
            </tr>
            <tr>
                <th>No</th><th class="l">Job No</th><th>Plan</th><th>Act</th>
                <th>Good</th><th>Rep</th><th>Rej</th><th>Str</th>
                <th style="border-right:1px solid #7a1414;">PL S</th>
                <th style="border-right:1px solid #7a1414;">PL F</th>
                <th style="border-right:1px solid #7a1414;">Act S</th>
                <th style="border-right:1px solid #7a1414;">Act F</th>
                <th>Rec</th><th style="border-right:1px solid #7a1414;">LKH</th>
                <th style="border-right:1px solid #7a1414;">Menit</th>
                <th>Dies</th><th>Var</th><th>1stQ</th>
                <th style="border-right:1px solid #7a1414;">Dan</th>
                <th>M/C</th><th>Mat</th><th>Log</th><th>Prod</th>
                <th style="border-right:1px solid #7a1414;">Total</th>
                <th>Plan</th><th style="border-right:1px solid #7a1414;">Act</th>
                <th>Typ</th><th style="border-right:1px solid #7a1414;">Min</th>
                <th style="border-right:1px solid #7a1414;">Menit</th>
                <th>Pass%</th><th>Rep%</th>
                <th style="border-right:1px solid #7a1414;">Rej%</th>
                <th style="border-right:1px solid #7a1414;">%</th>
                <th>Pcs/Hr</th>
            </tr>
        </thead>
        <tbody>
            @php
                $actNo = 0;
                $aPlan = 0; $aGood = 0; $aRep = 0; $aRej = 0; $aStroke = 0;
                $aDan = 0; $aQc = 0; $aDtM = 0; $aDtMat = 0; $aDtLog = 0; $aDtProd = 0; $aDtTot = 0;
                $aTptP = 0; $aTptA = 0; $aBreak = 0; $aWork = 0;
            @endphp
            @forelse($jobsData as $job)
                @php
                    $isBreak = ($job['row_type'] ?? 'job') === 'break';
                    if ($isBreak) {
                        $ss = $job['schedule_start'] ?? null;
                        $sf = $job['schedule_finish'] ?? null;
                        $bd = ($ss && $sf) ? abs($sf->diffInMinutes($ss)) : 0;
                    } else {
                        $actNo++;
                        $actGood = intval($job['actual_good'] ?? 0);
                        $actRep = intval($job['actual_repair'] ?? 0);
                        $actRej = intval($job['actual_reject'] ?? 0);
                        $totalS = $actGood + $actRep + $actRej;
                        $planQ = intval($job['plan_qty'] ?? 0);
                        $ps = $job['schedule_start'] ?? null;
                        $pf = $job['schedule_finish'] ?? null;
                        $as = $job['actual_start'] ?? null;
                        $af = $job['actual_finish'] ?? null;
                        $ctRec = $job['plan_ct'] ?? 0;
                        $ctAct = $job['act_ct'] ?? 0;
                        $procAct = floatval($job['press_time'] ?? $job['process_time'] ?? 0);
                        $dctAct = (float)($job['dandori_time'] ?? 0);
                        $dtBd = $job['dt_breakdown'] ?? [];
                        $dtMach = (float)($dtBd['mach_t'] ?? 0);
                        $dtMatl = (float)($dtBd['mat_t'] ?? 0);
                        $dtLog  = (float)($dtBd['log_t'] ?? 0);
                        $dtProd = (float)($dtBd['prod_t'] ?? 0);
                        $dtTot  = $dtMach + $dtMatl + $dtLog + $dtProd;
                        $tptPlan = (float)($job['tpt_plan'] ?? 0);
                        $tptActual = (float)($job['tpt_act'] ?? 0);
                        $breakTime = (float)($job['break_time_duration'] ?? 0);
                        $workTime = max(0, $tptActual + $breakTime);
                        $pasRate = $totalS > 0 ? ($actGood / $totalS) * 100 : 0;
                        $repRate = $totalS > 0 ? ($actRep / $totalS) * 100 : 0;
                        $rejRate = $totalS > 0 ? ($actRej / $totalS) * 100 : 0;
                        $oeeVal = $job['oee'] ?? 0;
                        $gsphActual = intval($job['gsph'] ?? 0);

                        $aPlan += $planQ; $aGood += $actGood; $aRep += $actRep; $aRej += $actRej;
                        $aStroke += $totalS; $aDan += $dctAct; $aQc += intval($job['qcheck_time'] ?? 0); $aDtM += $dtMach;
                        $aDtMat += $dtMatl; $aDtLog += $dtLog; $aDtProd += $dtProd; $aDtTot += $dtTot;
                        $aTptP += $tptPlan; $aTptA += $tptActual;
                        $aBreak += $breakTime; $aWork += $workTime;

                        $pasClass = $pasRate >= 98 ? 'green' : ($pasRate >= 90 ? 'amber' : 'red');
                        $repClass = $repRate <= 1 ? 'green' : ($repRate <= 3 ? 'amber' : 'red');
                        $rejClass = $rejRate <= 2 ? 'green' : ($rejRate <= 5 ? 'amber' : 'red');
                        $oeeClass = $oeeVal >= 85 ? 'green' : ($oeeVal >= 65 ? 'amber' : 'red');
                    }
                @endphp
                @if ($isBreak)
                <tr class="break-row">
                    <td>-</td>
                    <td colspan="7" class="l" style="font-weight:700;color:#92400e;">{{ $job['break_label'] ?? 'Istirahat' }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ $ss ? $ss->format('H:i') : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ $sf ? $sf->format('H:i') : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;">-</td>
                    <td style="border-right:1px solid #7a1414;">-</td>
                    <td>-</td><td style="border-right:1px solid #7a1414;">-</td>
                    <td style="border-right:1px solid #7a1414;">-</td>
                    <td>-</td><td>-</td><td>-</td><td style="border-right:1px solid #7a1414;">-</td>
                    <td>-</td><td>-</td><td>-</td><td>-</td><td style="border-right:1px solid #7a1414;">-</td>
                    <td>-</td><td style="border-right:1px solid #7a1414;">-</td>
                    <td>-</td><td style="border-right:1px solid #7a1414;">-</td>
                    <td style="border-right:1px solid #7a1414;">-</td>
                    <td>-</td><td>-</td><td style="border-right:1px solid #7a1414;">-</td>
                    <td style="border-right:1px solid #7a1414;">{{ $bd }}min</td>
                    <td>-</td>
                </tr>
                @else
                <tr>
                    <td>{{ $actNo }}</td>
                    <td class="l b">{{ $job['job_master'] ?? '-' }}</td>
                    <td class="r">{{ number_format($planQ,0) }}</td>
                    <td class="r b">{{ number_format($totalS,0) }}</td>
                    <td class="r b green">{{ number_format($actGood,0) }}</td>
                    <td class="r amber">{{ number_format($actRep,0) }}</td>
                    <td class="r red">{{ number_format($actRej,0) }}</td>
                    <td class="r b">{{ number_format($totalS,0) }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ $ps ? $ps->format('H:i') : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ $pf ? $pf->format('H:i') : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;" class="b green">{{ $as ? $as->format('H:i') : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;" class="b green">{{ $af ? $af->format('H:i') : '-' }}</td>
                    <td>{{ number_format($ctRec,1) }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ number_format($ctAct,1) }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($procAct) }}</td>
                    <td>{{ number_format($job['dies_change_time'] ?? 0,1) }}</td><td>{{ number_format($job['variant_change_time'] ?? 0,1) }}</td><td>{{ number_format($job['qcheck_time'] ?? 0,0) }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($dctAct) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtMach) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtMatl) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtLog) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtProd) }}</td>
                    <td class="b" style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($dtTot) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($tptPlan) }}</td>
                    <td class="b" style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($tptActual) }}</td>
                    <td>{{ $breakTime > 0 ? 'BREAK' : '-' }}</td>
                    <td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($breakTime) }}</td>
                    <td class="b" style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($workTime) }}</td>
                    <td class="{{ $pasClass }} b">{{ number_format($pasRate,1) }}</td>
                    <td class="{{ $repClass }}">{{ number_format($repRate,1) }}</td>
                    <td class="{{ $rejClass }}" style="border-right:1px solid #7a1414;">{{ number_format($rejRate,1) }}</td>
                    <td class="{{ $oeeClass }} b" style="border-right:1px solid #7a1414;">{{ number_format($oeeVal,1) }}</td>
                    <td class="b red-dark">{{ number_format($gsphActual,0) }}</td>
                </tr>
                @endif
            @empty
                <tr><td colspan="34" style="color:#9ca3af;padding:8pt;">Tidak ada jadwal produksi</td></tr>
            @endforelse
        </tbody>
        @php
            $totStroke = $aGood + $aRep + $aRej;
            $totPassR = $totStroke > 0 ? ($aGood / $totStroke) * 100 : 0;
            $totRepR = $totStroke > 0 ? ($aRep / $totStroke) * 100 : 0;
            $totRejR = $totStroke > 0 ? ($aRej / $totStroke) * 100 : 0;
            $wOee = $totals['weighted_oee'] ?? 0;
            $wGsph = $totals['weighted_gsph'] ?? 0;
        @endphp
        <tfoot>
            <tr>
                <td></td>
                <td class="l bb">TOTAL SHIFT</td>
                <td class="r">{{ number_format($aPlan,0) }}</td>
                <td class="r bb">{{ number_format($aStroke,0) }}</td>
                <td class="r bb green">{{ number_format($aGood,0) }}</td>
                <td class="r">{{ number_format($aRep,0) }}</td>
                <td class="r">{{ number_format($aRej,0) }}</td>
                <td class="r bb">{{ number_format($aStroke,0) }}</td>
                <td style="border-right:1px solid #7a1414;"></td>
                <td style="border-right:1px solid #7a1414;"></td>
                <td style="border-right:1px solid #7a1414;"></td>
                <td style="border-right:1px solid #7a1414;"></td>
                <td></td><td style="border-right:1px solid #7a1414;"></td>
                <td style="border-right:1px solid #7a1414;"></td>
                <td>{{ number_format(collect($jobsData)->where('row_type','job')->sum('dies_change_time'),1) }}</td><td>{{ number_format(collect($jobsData)->where('row_type','job')->sum('variant_change_time'),1) }}</td><td>{{ number_format($aQc,0) }}</td>
                <td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($aDan) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtM) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtMat) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtLog) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtProd) }}</td>
                <td class="bb" style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($aDtTot) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aTptP) }}</td>
                <td class="bb" style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($aTptA) }}</td>
                <td></td><td style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($aBreak) }}</td>
                <td class="bb" style="border-right:1px solid #7a1414;">{{ \App\Support\ProductionFormat::minutes($aWork) }}</td>
                <td class="b green">{{ number_format($totPassR,1) }}</td>
                <td>{{ number_format($totRepR,1) }}</td>
                <td style="border-right:1px solid #7a1414;">{{ number_format($totRejR,1) }}</td>
                <td class="b {{ $wOee >= 85 ? 'green' : ($wOee >= 65 ? 'amber' : 'red') }}" style="border-right:1px solid #7a1414;">{{ number_format($wOee,1) }}</td>
                <td class="bb red-dark">{{ number_format($wGsph,0) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ==================== TABLE 3: SUMMARY ==================== --}}
    <div class="section-title" style="page-break-before:always;">3. Summary Achievement — Shift Performance Overview</div>
    @php
        $sumItemPlan = $summary['item_plan'] ?? 0;
        $sumItemAct = $summary['item_act'] ?? 0;
        $sumQtyPlan = $summary['qty_plan'] ?? 0;
        $sumQtyAct = $summary['qty_act'] ?? 0;
        $sumTptPlan = $summary['tpt_plan'] ?? 0;
        $sumTptAct = $summary['tpt_act'] ?? 0;
        $sumGsphPlan = $summary['gsph_plan'] ?? 0;
        $sumGsphAct = $summary['gsph_act'] ?? 0;
        $sumPassPlan = $summary['pass_rate_plan'] ?? 100;
        $sumPassAct = $summary['pass_rate_act'] ?? 0;
        $sumRejPlan = $summary['reject_rate_plan'] ?? 2;
        $sumRejAct = $summary['reject_rate_act'] ?? 0;
        $sumRepPlan = $summary['repair_rate_plan'] ?? 0.5;
        $sumRepAct = $summary['repair_rate_act'] ?? 0;
        $sumOee = $summary['weighted_oee'] ?? 0;
        $achievementPct = $sumQtyPlan > 0 ? ($sumQtyAct / $sumQtyPlan) * 100 : 0;

        $pctClass = fn($v) => $v >= 100 ? 'green' : ($v >= 80 ? 'amber' : 'red');
        $invClass = fn($v) => $v <= 80 ? 'green' : ($v <= 100 ? 'amber' : 'red');
    @endphp
    <table style="width:70%;">
        <thead>
            <tr>
                <th class="l" style="width:45%;">KPI Parameter</th>
                <th style="width:18%;">Plan</th>
                <th style="width:18%;">Actual</th>
                <th style="width:19%;">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['label' => 'ITEM PROCESS', 'plan' => "$sumItemPlan Items", 'act' => "$sumItemAct Items", 'pct' => $sumItemPlan > 0 ? ($sumItemAct / $sumItemPlan) * 100 : 0],
                ['label' => 'QTY PROCESS (PCS)', 'plan' => number_format($sumQtyPlan,0).' Pcs', 'act' => number_format($sumQtyAct,0).' Pcs', 'pct' => $sumQtyPlan > 0 ? ($sumQtyAct / $sumQtyPlan) * 100 : 0],
                ['label' => 'TPT PROCESS (MIN)', 'plan' => \App\Support\ProductionFormat::minutes($sumTptPlan).' Min', 'act' => \App\Support\ProductionFormat::minutes($sumTptAct).' Min', 'pct' => $sumTptPlan > 0 ? ($sumTptAct / $sumTptPlan) * 100 : 0],
                ['label' => 'GSPH', 'plan' => number_format($sumGsphPlan,0).' Pcs/Hour', 'act' => number_format($sumGsphAct,0).' Pcs/Hour', 'pct' => $sumGsphPlan > 0 ? ($sumGsphAct / $sumGsphPlan) * 100 : 0],
                ['label' => 'PASS RATE (%)', 'plan' => number_format($sumPassPlan,1).'%', 'act' => number_format($sumPassAct,1).'%', 'pct' => $sumPassPlan > 0 ? ($sumPassAct / $sumPassPlan) * 100 : 0],
                ['label' => 'REJECT RATE (%)', 'plan' => number_format($sumRejPlan,1).'%', 'act' => number_format($sumRejAct,1).'%', 'pct' => $sumRejPlan > 0 ? ($sumRejAct / $sumRejPlan) * 100 : 0],
                ['label' => 'REPAIR RATE (%)', 'plan' => number_format($sumRepPlan,1).'%', 'act' => number_format($sumRepAct,1).'%', 'pct' => $sumRepPlan > 0 ? ($sumRepAct / $sumRepPlan) * 100 : 0],
            ] as $k => $row)
            @php
                $isInv = in_array($k, [5, 6]); // reject & repair use inverted class
                $cls = $isInv ? $invClass($row['pct']) : $pctClass($row['pct']);
            @endphp
            <tr>
                <td class="l b">{{ $row['label'] }}</td>
                <td>{{ $row['plan'] }}</td>
                <td class="bb red-dark">{{ $row['act'] }}</td>
                <td class="b {{ $cls }}">{{ number_format($row['pct'],1) }}%</td>
            </tr>
            @endforeach
            <tr>
                <td class="l b">OEE (%)</td>
                <td>100.0%</td>
                <td class="bb red-dark">{{ number_format($sumOee,1) }}%</td>
                <td class="b @php $oeeC = $sumOee >= 85 ? 'green' : ($sumOee >= 65 ? 'amber' : 'red') @endphp {{ $oeeC }}">{{ number_format($sumOee,1) }}%</td>
            </tr>
            <tr style="background-color:#f3f4f6;font-size:8pt;font-weight:800;">
                <td class="l bb">ACHIEVEMENT</td>
                <td>{{ number_format($sumQtyPlan,0) }}</td>
                <td class="bb red-dark">{{ number_format($sumQtyAct,0) }}</td>
                <td class="bb {{ $pctClass($achievementPct) }}">{{ number_format($achievementPct,1) }}%</td>
            </tr>
        </tbody>
    </table>

    {{-- SIGNATURES --}}
    <div class="sig-section no-break">
        <div class="section-title">Pengesahan</div>
        <table class="sig-table">
            <tr>
                @foreach ([
                    ['role' => 'Team Leader', 'key' => 'teamleader'],
                    ['role' => 'Foreman', 'key' => 'foreman'],
                    ['role' => 'Supervisor', 'key' => 'supervisor'],
                ] as $sig)
                @php $state = $signatureStatus[$sig['key']] ?? ['signed' => false, 'available' => false]; @endphp
                <td>
                    <div class="lbl">{{ $sig['role'] }}</div>
                    <div class="line">{{ !empty($state['name']) ? $state['name'] : '______________' }}</div>
                    <div style="font-size:5.5pt;margin-top:2pt;{{ $state['signed'] ? 'color:#059669;font-weight:700;' : 'color:#9ca3af;' }}">
                        {{ $state['signed'] ? '✓ SIGNED' : ($state['available'] ? '___' : 'LOCKED') }}
                    </div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Dokumen ini digenerate otomatis oleh Sistem Informasi Terintegrasi IPPI &bull;
        {{ now()->format('d/m/Y H:i') }} &bull;
        {{ $selectedLineName }} &bull; {{ $latestShiftName }}
    </div>

</body>
</html>
