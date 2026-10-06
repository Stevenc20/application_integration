<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LKH {{ $date }} - {{ $selectedLineName }} - {{ $latestShiftName }}</title>
    <style>
        @page {
            margin: 8mm 8mm 6mm 8mm;
            size: a4 landscape;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 6.5pt;
            color: #1e293b;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .header-box {
            text-align: center;
            padding-bottom: 5pt;
            border-bottom: 2pt solid #991b1b;
            margin-bottom: 6pt;
        }
        .header-box h2 {
            font-size: 13pt;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.5pt;
        }
        .header-box h1 {
            font-size: 10.5pt;
            font-weight: 800;
            color: #991b1b;
            margin: 2pt 0 1pt 0;
            text-transform: uppercase;
            letter-spacing: 0.8pt;
        }
        .header-box p {
            font-size: 5.5pt;
            color: #64748b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            font-weight: 600;
        }
        .info-bar {
            width: 100%;
            margin-bottom: 6pt;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 0.5pt solid #cbd5e1;
            border-radius: 3pt;
        }
        .info-bar td {
            padding: 3pt 6pt;
            font-size: 6.5pt;
            border: none;
        }
        .info-bar .lbl {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            width: 50pt;
        }
        .info-bar .val {
            font-weight: 800;
            color: #0f172a;
        }
        .section-header {
            font-size: 7.5pt;
            font-weight: 800;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            padding: 3pt 0 2pt 0;
            border-bottom: 1.2pt solid #991b1b;
            margin-bottom: 4pt;
            margin-top: 6pt;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }
        table.data-table th {
            background-color: #991b1b;
            color: #ffffff;
            font-weight: 700;
            font-size: 5pt;
            padding: 2.5pt 1.5pt;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.15pt;
            border: 0.4pt solid #7a1414;
        }
        table.data-table td {
            padding: 2pt 1.5pt;
            border: 0.4pt solid #cbd5e1;
            font-size: 5.2pt;
            text-align: center;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        table.data-table tfoot td {
            background-color: #fee2e2;
            font-weight: 800;
            border-top: 1.2pt solid #991b1b;
            color: #7f1d1d;
        }
        table.data-table .break-row td {
            background-color: #fffbeb !important;
            color: #92400e;
            font-weight: 700;
        }
        .l { text-align: left !important; padding-left: 3pt !important; }
        .r { text-align: right !important; padding-right: 3pt !important; }
        .c { text-align: center !important; }
        .b { font-weight: 700; }
        .bb { font-weight: 800; }
        .green { color: #15803d; font-weight: 700; }
        .amber { color: #b45309; }
        .red { color: #dc2626; }
        .red-dark { color: #991b1b; font-weight: 800; }
        .grp-border { border-right: 1pt solid #7a1414 !important; }
        .col-border { border-right: 0.8pt solid #94a3b8 !important; }
        .page-break { page-break-before: always; }
        .no-break { page-break-inside: avoid; }

        /* Summary & Signatures Grid */
        .bottom-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8pt;
        }
        .bottom-grid td {
            vertical-align: top;
            padding: 0;
            border: none;
        }
        table.summ-table {
            width: 98%;
            border-collapse: collapse;
        }
        table.summ-table th {
            background-color: #991b1b;
            color: #ffffff;
            font-weight: 800;
            font-size: 5.5pt;
            padding: 3pt 3pt;
            text-transform: uppercase;
            border: 0.4pt solid #7a1414;
        }
        table.summ-table td {
            padding: 2.5pt 3pt;
            border: 0.4pt solid #cbd5e1;
            font-size: 5.5pt;
        }
        table.summ-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Signatures Card */
        .sig-container {
            width: 96%;
            margin-left: auto;
            border: 0.5pt solid #cbd5e1;
            border-radius: 3pt;
            background-color: #ffffff;
            padding: 4pt 6pt;
        }
        .sig-title {
            font-size: 6.5pt;
            font-weight: 800;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            border-bottom: 1pt solid #991b1b;
            padding-bottom: 2pt;
            margin-bottom: 5pt;
            text-align: center;
        }
        table.sig-grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.sig-grid td {
            text-align: center;
            padding: 2pt 4pt;
            vertical-align: top;
            border: none;
            width: 33.33%;
        }
        table.sig-grid .sig-role {
            font-weight: 700;
            color: #475569;
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
        }
        table.sig-grid .sig-box {
            height: 32pt;
            line-height: 32pt;
            font-size: 6pt;
            font-style: italic;
            color: #cbd5e1;
        }
        table.sig-grid .sig-name {
            border-top: 0.8pt solid #334155;
            padding-top: 2pt;
            font-weight: 800;
            color: #0f172a;
            font-size: 6.2pt;
        }
        table.sig-grid .sig-status {
            font-size: 5pt;
            margin-top: 1.5pt;
            font-weight: 700;
            letter-spacing: 0.3pt;
        }

        .footer {
            text-align: center;
            font-size: 5pt;
            color: #94a3af;
            padding-top: 4pt;
            border-top: 0.5pt solid #e2e8f0;
            margin-top: 6pt;
        }
    </style>
</head>
<body>

    {{-- ========================================================================= --}}
    {{-- HALAMAN 1: PRODUCTION SCHEDULE (PPC) + SUMMARY ACHIEVEMENT + PENGESAHAN   --}}
    {{-- ========================================================================= --}}

    {{-- HEADER --}}
    <div class="header-box">
        <h2>PT INTI PANTJA PRESS INDUSTRI</h2>
        <h1>LAPORAN KERJA HARIAN (LKH) STAMPING</h1>
        <p>PRODUCTION CONTROL &amp; REALTIME EXECUTION SYSTEM</p>
    </div>

    {{-- INFO METADATA --}}
    <table class="info-bar">
        <tr>
            <td class="lbl">Line</td>
            <td class="val">{{ $selectedLineName }}</td>
            <td class="lbl">Shift</td>
            <td class="val">{{ $latestShiftName }}</td>
            <td class="lbl">Tanggal</td>
            <td class="val">{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</td>
            <td class="lbl">Jam Shift</td>
            <td class="val">
                {{ $shiftDisplayStart ? $shiftDisplayStart->format('H:i') : '07:40' }} — 
                {{ $shiftDisplayEnd ? $shiftDisplayEnd->format('H:i') : '16:00' }}
            </td>
        </tr>
    </table>

    {{-- 1. PRODUCTION SCHEDULE --}}
    <div class="section-header">1. Production Schedule — PPC Master Timeline</div>
    <table class="data-table">
        <thead>
            <tr>
                <th colspan="7" class="grp-border">Schedule Plan</th>
                <th colspan="3" class="grp-border">Uchi Dandori</th>
                <th>Total Uchi</th>
                <th>TPT</th>
                <th>Break</th>
                <th>Work Time</th>
                <th>GSPH</th>
            </tr>
            <tr>
                <th style="width:16pt">No</th>
                <th style="width:140pt" class="l">Job Master</th>
                <th style="width:36pt">Plan Qty</th>
                <th style="width:28pt">M/C</th>
                <th style="width:30pt">CT (s)</th>
                <th style="width:32pt">Start</th>
                <th style="width:32pt" class="grp-border">Finish</th>
                <th style="width:35pt">Dies &amp; Var</th>
                <th style="width:35pt">1st-Q</th>
                <th style="width:35pt" class="grp-border">Dan (m)</th>
                <th style="width:35pt">Uchi (m)</th>
                <th style="width:35pt">TPT (m)</th>
                <th style="width:35pt">Break (m)</th>
                <th style="width:35pt">Work (m)</th>
                <th style="width:35pt">GSPH</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $rowNo = 0; 
                $schTptPlan = 0; 
                $schBreak = 0; 
                $schWork = 0; 
                $schPlan = 0; 
                $schDan = 0; 
            @endphp
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
                        $gsphVal = intval($job['plan_gsph'] ?? $job['gsph'] ?? 0);
                        
                        $schPlan += $planQty; 
                        $schDan += $dandori; 
                        $schTptPlan += $tptPlan;
                        $schBreak += $breakTime; 
                        $schWork += $workTime;
                    }
                @endphp
                @if ($isBreak)
                <tr class="break-row">
                    <td>-</td>
                    <td colspan="4" class="l">{{ $job['break_label'] ?? $job['job_master'] ?? 'ISTIRAHAT' }}</td>
                    <td>{{ $ss ? $ss->format('H:i') : '-' }}</td>
                    <td class="grp-border">{{ $sf ? $sf->format('H:i') : '-' }}</td>
                    <td colspan="8">{{ $bd }} MINS</td>
                </tr>
                @else
                <tr>
                    <td>{{ $job['display_no'] ?? $rowNo }}</td>
                    <td class="l b">{{ $job['job_master'] ?? '-' }}</td>
                    <td class="r">{{ number_format($planQty,0) }}</td>
                    <td>{{ $job['total_mesin'] ?? 1 }}</td>
                    <td>{{ number_format($job['plan_ct'] ?? 0, 1) }}</td>
                    <td>{{ $sStart }}</td>
                    <td class="grp-border">{{ $sFin }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($job['dies_variant_time'] ?? 0) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($job['qcheck_time'] ?? 0) }}</td>
                    <td class="grp-border">{{ \App\Support\ProductionFormat::minutes($dandori) }}</td>
                    <td>{{ number_format($uchi,0) }}</td>
                    <td class="b">{{ \App\Support\ProductionFormat::minutes($tptPlan) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($breakTime) }}</td>
                    <td class="b">{{ \App\Support\ProductionFormat::minutes($workTime) }}</td>
                    <td class="b red-dark">{{ number_format($gsphVal,0) }}</td>
                </tr>
                @endif
            @empty
                <tr><td colspan="15" style="color:#94a3af;padding:6pt;">Tidak ada jadwal produksi</td></tr>
            @endforelse
        </tbody>
        @php
            $schedJobs = collect($jobsData)->where('row_type', 'job');
            $totDvt = $schedJobs->sum('dies_variant_time');
            $totQc = $schedJobs->sum('qcheck_time');
            $totProc = (int)ceil($schedJobs->sum('process_time'));
            $totGsph = $schTptPlan > 0 ? round($schPlan / ($schTptPlan / 60)) : 0;
        @endphp
        <tfoot>
            <tr>
                <td></td>
                <td class="l bb">TOTAL SHIFT</td>
                <td class="r bb">{{ number_format($schPlan,0) }}</td>
                <td></td><td></td><td></td>
                <td class="grp-border"></td>
                <td>{{ \App\Support\ProductionFormat::minutes($totDvt) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($totQc) }}</td>
                <td class="grp-border">{{ \App\Support\ProductionFormat::minutes($schDan) }}</td>
                <td>{{ number_format($totProc, 0) }}</td>
                <td class="bb">{{ \App\Support\ProductionFormat::minutes($schTptPlan) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($schBreak) }}</td>
                <td class="bb">{{ \App\Support\ProductionFormat::minutes($schWork) }}</td>
                <td class="bb red-dark">{{ number_format($totGsph,0) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- LOWER GRID: SUMMARY ACHIEVEMENT (LEFT 58%) & PENGESAHAN (RIGHT 42%) --}}
    <table class="bottom-grid no-break">
        <tr>
            {{-- SUMMARY ACHIEVEMENT --}}
            <td style="width: 58%;">
                <div class="section-header" style="margin-top:0;">3. Summary Achievement — Shift Overview</div>
                @php
                    $sumItemPlan = $summary['item_plan'] ?? 0;
                    $sumItemAct  = $summary['item_act'] ?? 0;
                    $sumQtyPlan  = $summary['qty_plan'] ?? 0;
                    $sumQtyAct   = $summary['qty_act'] ?? 0;
                    $sumTptPlan  = $summary['tpt_plan'] ?? 0;
                    $sumTptAct   = $summary['tpt_act'] ?? 0;
                    $sumGsphPlan = $summary['gsph_plan'] ?? 0;
                    $sumGsphAct  = $summary['gsph_act'] ?? 0;
                    $sumPassPlan = $summary['pass_rate_plan'] ?? 100;
                    $sumPassAct  = $summary['pass_rate_act'] ?? 0;
                    $sumRejPlan  = $summary['reject_rate_plan'] ?? 2;
                    $sumRejAct   = $summary['reject_rate_act'] ?? 0;
                    $sumRepPlan  = $summary['repair_rate_plan'] ?? 0.5;
                    $sumRepAct   = $summary['repair_rate_act'] ?? 0;
                    $sumOee      = $summary['weighted_oee'] ?? 0;
                    $achievePct  = $sumQtyPlan > 0 ? ($sumQtyAct / $sumQtyPlan) * 100 : 0;

                    $pctColor = fn($v) => $v >= 100 ? 'green' : ($v >= 80 ? 'amber' : 'red');
                    $invColor = fn($v) => $v <= 80 ? 'green' : ($v <= 100 ? 'amber' : 'red');
                @endphp
                <table class="summ-table">
                    <thead>
                        <tr>
                            <th class="l" style="width:42%;">KPI Parameter</th>
                            <th style="width:20%;">Plan</th>
                            <th style="width:20%;">Actual</th>
                            <th style="width:18%;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['label' => 'ITEM PROCESS', 'plan' => "$sumItemPlan Items", 'act' => "$sumItemAct Items", 'pct' => $sumItemPlan > 0 ? ($sumItemAct / $sumItemPlan) * 100 : 0],
                            ['label' => 'QTY PROCESS (PCS)', 'plan' => number_format($sumQtyPlan,0).' Pcs', 'act' => number_format($sumQtyAct,0).' Pcs', 'pct' => $sumQtyPlan > 0 ? ($sumQtyAct / $sumQtyPlan) * 100 : 0],
                            ['label' => 'TPT PROCESS (MIN)', 'plan' => \App\Support\ProductionFormat::minutes($sumTptPlan).' Min', 'act' => \App\Support\ProductionFormat::minutes($sumTptAct).' Min', 'pct' => $sumTptPlan > 0 ? ($sumTptAct / $sumTptPlan) * 100 : 0],
                            ['label' => 'GSPH', 'plan' => number_format($sumGsphPlan,0).' Pcs/Hr', 'act' => number_format($sumGsphAct,0).' Pcs/Hr', 'pct' => $sumGsphPlan > 0 ? ($sumGsphAct / $sumGsphPlan) * 100 : 0],
                            ['label' => 'PASS RATE (%)', 'plan' => number_format($sumPassPlan,1).'%', 'act' => number_format($sumPassAct,1).'%', 'pct' => $sumPassPlan > 0 ? ($sumPassAct / $sumPassPlan) * 100 : 0],
                            ['label' => 'REJECT RATE (%)', 'plan' => number_format($sumRejPlan,1).'%', 'act' => number_format($sumRejAct,1).'%', 'pct' => $sumRejPlan > 0 ? ($sumRejAct / $sumRejPlan) * 100 : 0],
                            ['label' => 'REPAIR RATE (%)', 'plan' => number_format($sumRepPlan,1).'%', 'act' => number_format($sumRepAct,1).'%', 'pct' => $sumRepPlan > 0 ? ($sumRepAct / $sumRepPlan) * 100 : 0],
                        ] as $idx => $r)
                        @php
                            $isInv = in_array($idx, [5, 6]);
                            $cls = $isInv ? $invColor($r['pct']) : $pctColor($r['pct']);
                        @endphp
                        <tr>
                            <td class="l b">{{ $r['label'] }}</td>
                            <td class="c">{{ $r['plan'] }}</td>
                            <td class="c bb red-dark">{{ $r['act'] }}</td>
                            <td class="c b {{ $cls }}">{{ number_format($r['pct'],1) }}%</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td class="l b">OEE (%)</td>
                            <td class="c">100.0%</td>
                            <td class="c bb red-dark">{{ number_format($sumOee,1) }}%</td>
                            <td class="c b {{ $sumOee >= 85 ? 'green' : ($sumOee >= 65 ? 'amber' : 'red') }}">{{ number_format($sumOee,1) }}%</td>
                        </tr>
                        <tr style="background-color:#fee2e2;font-weight:800;">
                            <td class="l bb" style="color:#991b1b;">TOTAL ACHIEVEMENT</td>
                            <td class="c">{{ number_format($sumQtyPlan,0) }}</td>
                            <td class="c bb red-dark">{{ number_format($sumQtyAct,0) }}</td>
                            <td class="c bb {{ $pctColor($achievePct) }}">{{ number_format($achievePct,1) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            {{-- PENGESAHAN (SIGNATURES) --}}
            <td style="width: 42%;">
                <div class="sig-container">
                    <div class="sig-title">Verifikasi &amp; Pengesahan Dokumen</div>
                    <table class="sig-grid">
                        <tr>
                            @foreach ([
                                ['role' => 'Team Leader', 'key' => 'teamleader'],
                                ['role' => 'Foreman', 'key' => 'foreman'],
                                ['role' => 'Supervisor', 'key' => 'supervisor'],
                            ] as $s)
                            @php 
                                $st = $signatureStatus[$s['key']] ?? ['signed' => false, 'available' => false, 'name' => ''];
                                $isSigned = !empty($st['signed']);
                                $personName = !empty($st['name']) ? $st['name'] : '______________';
                            @endphp
                            <td>
                                <div class="sig-role">{{ $s['role'] }}</div>
                                <div class="sig-box">
                                    @if($isSigned)
                                        <span style="color:#15803d;font-weight:800;font-size:7pt;">✓ VERIFIED</span>
                                    @else
                                        <span>(Belum TTD)</span>
                                    @endif
                                </div>
                                <div class="sig-name">{{ $personName }}</div>
                                <div class="sig-status" style="{{ $isSigned ? 'color:#15803d;' : 'color:#94a3af;' }}">
                                    {{ $isSigned ? 'ELECTRONIC SIGNED' : ($st['available'] ? 'MENUNGGU TTD' : 'TERKUNCI') }}
                                </div>
                            </td>
                            @endforeach
                        </tr>
                    </table>
                    <p style="font-size:4.8pt;color:#64748b;margin:6pt 0 0 0;text-align:center;font-style:italic;">
                        Tanda tangan elektronik sah sesuai Sistem Verifikasi Digital LKH PT Inti Pantja Press Industri.
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Halaman 1 dari 2 &bull; Dokumen Resmi Laporan Kerja Harian (LKH) IPPI &bull; Digenerate pada {{ now()->format('d/m/Y H:i:s') }}
    </div>

    {{-- ========================================================================= --}}
    {{-- HALAMAN 2: ACTUAL LAPANGAN (REALTIME EXECUTION — FULL COLUMNS)            --}}
    {{-- ========================================================================= --}}
    <div class="page-break"></div>

    {{-- MINI HEADER HALAMAN 2 --}}
    <div style="padding-bottom:3pt;border-bottom:1.5pt solid #991b1b;margin-bottom:5pt;display:table;width:100%;">
        <div style="display:table-cell;vertical-align:middle;text-align:left;">
            <span style="font-size:9.5pt;font-weight:900;color:#0f172a;">PT INTI PANTJA PRESS INDUSTRI</span>
            <span style="font-size:8.5pt;font-weight:800;color:#991b1b;margin-left:6pt;">— ACTUAL LAPANGAN</span>
        </div>
        <div style="display:table-cell;vertical-align:middle;text-align:right;font-size:6.5pt;font-weight:800;color:#475569;">
            Line: <span style="color:#0f172a;">{{ $selectedLineName }}</span> &bull; 
            Shift: <span style="color:#0f172a;">{{ $latestShiftName }}</span> &bull; 
            Tgl: <span style="color:#0f172a;">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
        </div>
    </div>

    <div class="section-header" style="margin-top:2pt;">2. Actual Lapangan — Realtime Execution Details</div>
    <table class="data-table">
        <thead>
            <tr>
                <th colspan="8" class="grp-border">Schedule &amp; Output Actual</th>
                <th colspan="4" class="grp-border">Execution Time</th>
                <th colspan="2" class="grp-border">Cycle Time</th>
                <th class="grp-border">Press</th>
                <th colspan="3" class="grp-border">Uchi Dandori</th>
                <th colspan="7" class="grp-border">Down Time (Menit)</th>
                <th colspan="2" class="grp-border">TPT (Min)</th>
                <th colspan="2" class="grp-border">Break</th>
                <th class="grp-border">Work</th>
                <th colspan="3" class="grp-border">Quality Rate</th>
                <th class="grp-border">OEE</th>
                <th>GSPH</th>
            </tr>
            <tr>
                <th style="width:14pt">No</th>
                <th style="width:75pt" class="l">Job Master</th>
                <th style="width:25pt">Plan</th>
                <th style="width:25pt">Act</th>
                <th style="width:24pt">Good</th>
                <th style="width:20pt">Rep</th>
                <th style="width:20pt">Rej</th>
                <th style="width:25pt" class="grp-border">Stroke</th>
                
                <th style="width:24pt">PL S</th>
                <th style="width:24pt">PL F</th>
                <th style="width:24pt">Act S</th>
                <th style="width:24pt" class="grp-border">Act F</th>
                
                <th style="width:22pt">Rec</th>
                <th style="width:22pt" class="grp-border">LKH</th>
                
                <th style="width:24pt" class="grp-border">Menit</th>
                
                <th style="width:22pt">Dies</th>
                <th style="width:20pt">1stQ</th>
                <th style="width:24pt" class="grp-border">Dan</th>
                
                <th style="width:20pt">Dies</th>
                <th style="width:20pt">M/C</th>
                <th style="width:20pt">Mat</th>
                <th style="width:20pt">Log</th>
                <th style="width:20pt">Prod</th>
                <th style="width:20pt">Oth</th>
                <th style="width:24pt" class="grp-border">Total</th>
                
                <th style="width:23pt">Plan</th>
                <th style="width:23pt" class="grp-border">Act</th>
                
                <th style="width:20pt">Typ</th>
                <th style="width:20pt" class="grp-border">Min</th>
                
                <th style="width:24pt" class="grp-border">Work</th>
                
                <th style="width:24pt">Pass%</th>
                <th style="width:22pt">Rep%</th>
                <th style="width:22pt" class="grp-border">Rej%</th>
                
                <th style="width:26pt" class="grp-border">OEE%</th>
                <th style="width:28pt">Pcs/Hr</th>
            </tr>
        </thead>
        <tbody>
            @php
                $actIdx = 0;
                $aPlan = 0; $aAct = 0; $aGood = 0; $aRep = 0; $aRej = 0; $aStroke = 0;
                $aProc = 0; $aDan = 0; $aQc = 0; 
                $aDtDies = 0; $aDtM = 0; $aDtMat = 0; $aDtLog = 0; $aDtProd = 0; $aDtOth = 0; $aDtTot = 0;
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
                        $actIdx++;
                        $planQ = intval($job['plan_qty'] ?? 0);
                        $actGood = intval($job['actual_good'] ?? 0);
                        $actRep = intval($job['actual_repair'] ?? 0);
                        $actRej = intval($job['actual_reject'] ?? 0);
                        $totalS = $actGood + $actRep + $actRej;
                        
                        $ps = $job['schedule_start'] ?? null;
                        $pf = $job['schedule_finish'] ?? null;
                        $as = $job['actual_start'] ?? null;
                        $af = $job['actual_finish'] ?? null;
                        
                        $ctRec = $job['plan_ct'] ?? 0;
                        $ctAct = $job['act_ct'] ?? 0;
                        $procAct = floatval($job['press_time'] ?? $job['process_time'] ?? 0);
                        $dctAct = (float)($job['dandori_time'] ?? 0);
                        
                        $dtBd = $job['dt_breakdown'] ?? [];
                        $dtDies = (float)($dtBd['dies_t'] ?? 0);
                        $dtMach = (float)($dtBd['mach_t'] ?? 0);
                        $dtMatl = (float)($dtBd['mat_t'] ?? 0);
                        $dtLog  = (float)($dtBd['log_t'] ?? 0);
                        $dtProd = (float)($dtBd['prod_t'] ?? 0);
                        $dtOth  = (float)($dtBd['others_t'] ?? 0);
                        $dtTot  = (float)($job['dt_total'] ?? ($dtDies + $dtMach + $dtMatl + $dtLog + $dtProd + $dtOth));
                        
                        $tptPlan = (float)($job['tpt_plan'] ?? 0);
                        $tptActual = (float)($job['tpt_act'] ?? 0);
                        $breakTime = (float)($job['break_time_duration'] ?? 0);
                        $workTime = max(0, $tptActual + $breakTime);
                        
                        $pasRate = $totalS > 0 ? ($actGood / $totalS) * 100 : 0;
                        $repRate = $totalS > 0 ? ($actRep / $totalS) * 100 : 0;
                        $rejRate = $totalS > 0 ? ($actRej / $totalS) * 100 : 0;
                        $oeeVal = $job['oee'] ?? 0;
                        $gsphActual = intval($job['gsph'] ?? 0);

                        $aPlan += $planQ; 
                        $aGood += $actGood; 
                        $aRep += $actRep; 
                        $aRej += $actRej;
                        $aStroke += $totalS; 
                        $aProc += $procAct;
                        $aDan += $dctAct; 
                        $aQc += intval($job['qcheck_time'] ?? 0); 
                        $aDtDies += $dtDies;
                        $aDtM += $dtMach;
                        $aDtMat += $dtMatl; 
                        $aDtLog += $dtLog; 
                        $aDtProd += $dtProd; 
                        $aDtOth += $dtOth; 
                        $aDtTot += $dtTot;
                        $aTptP += $tptPlan; 
                        $aTptA += $tptActual;
                        $aBreak += $breakTime; 
                        $aWork += $workTime;

                        $pasClass = $pasRate >= 98 ? 'green' : ($pasRate >= 90 ? 'amber' : 'red');
                        $repClass = $repRate <= 1 ? 'green' : ($repRate <= 3 ? 'amber' : 'red');
                        $rejClass = $rejRate <= 2 ? 'green' : ($rejRate <= 5 ? 'amber' : 'red');
                        $oeeClass = $oeeVal >= 85 ? 'green' : ($oeeVal >= 65 ? 'amber' : 'red');
                    }
                @endphp
                @if ($isBreak)
                <tr class="break-row">
                    <td>-</td>
                    <td colspan="7" class="l">{{ $job['break_label'] ?? $job['job_master'] ?? 'ISTIRAHAT' }}</td>
                    <td>{{ $ss ? $ss->format('H:i') : '-' }}</td>
                    <td>{{ $sf ? $sf->format('H:i') : '-' }}</td>
                    <td>-</td>
                    <td class="grp-border">-</td>
                    <td>-</td><td class="grp-border">-</td>
                    <td class="grp-border">-</td>
                    <td>-</td><td>-</td><td class="grp-border">-</td>
                    <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td class="grp-border">-</td>
                    <td>-</td><td class="grp-border">-</td>
                    <td>BREAK</td>
                    <td class="grp-border">{{ $bd }}m</td>
                    <td class="grp-border">-</td>
                    <td>-</td><td>-</td><td class="grp-border">-</td>
                    <td class="grp-border">-</td>
                    <td>-</td>
                </tr>
                @else
                <tr>
                    <td>{{ $job['display_no'] ?? $actIdx }}</td>
                    <td class="l b">{{ $job['job_master'] ?? '-' }}</td>
                    <td class="r">{{ number_format($planQ,0) }}</td>
                    <td class="r b">{{ number_format($totalS,0) }}</td>
                    <td class="r b green">{{ number_format($actGood,0) }}</td>
                    <td class="r amber">{{ number_format($actRep,0) }}</td>
                    <td class="r red">{{ number_format($actRej,0) }}</td>
                    <td class="r b grp-border">{{ number_format($totalS,0) }}</td>
                    
                    <td>{{ $ps ? $ps->format('H:i') : '-' }}</td>
                    <td>{{ $pf ? $pf->format('H:i') : '-' }}</td>
                    <td class="b green">{{ $as ? $as->format('H:i') : '-' }}</td>
                    <td class="b green grp-border">{{ $af ? $af->format('H:i') : '-' }}</td>
                    
                    <td>{{ number_format($ctRec,1) }}</td>
                    <td class="grp-border">{{ number_format($ctAct,1) }}</td>
                    
                    <td class="grp-border">{{ \App\Support\ProductionFormat::minutes($procAct) }}</td>
                    
                    <td>{{ \App\Support\ProductionFormat::minutes($job['dies_variant_time'] ?? 0) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($job['qcheck_time'] ?? 0) }}</td>
                    <td class="grp-border">{{ \App\Support\ProductionFormat::minutes($dctAct) }}</td>
                    
                    <td>{{ \App\Support\ProductionFormat::minutes($dtDies) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtMach) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtMatl) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtLog) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtProd) }}</td>
                    <td>{{ \App\Support\ProductionFormat::minutes($dtOth) }}</td>
                    <td class="b grp-border">{{ \App\Support\ProductionFormat::minutes($dtTot) }}</td>
                    
                    <td>{{ \App\Support\ProductionFormat::minutes($tptPlan) }}</td>
                    <td class="b grp-border">{{ \App\Support\ProductionFormat::minutes($tptActual) }}</td>
                    
                    <td>{{ $breakTime > 0 ? 'BRK' : '-' }}</td>
                    <td class="grp-border">{{ \App\Support\ProductionFormat::minutes($breakTime) }}</td>
                    
                    <td class="b grp-border">{{ \App\Support\ProductionFormat::minutes($workTime) }}</td>
                    
                    <td class="{{ $pasClass }} b">{{ number_format($pasRate,1) }}</td>
                    <td class="{{ $repClass }}">{{ number_format($repRate,1) }}</td>
                    <td class="{{ $rejClass }} grp-border">{{ number_format($rejRate,1) }}</td>
                    
                    <td class="{{ $oeeClass }} b grp-border">{{ number_format($oeeVal,1) }}</td>
                    <td class="b red-dark">{{ number_format($gsphActual,0) }}</td>
                </tr>
                @endif
            @empty
                <tr><td colspan="35" style="color:#94a3af;padding:6pt;">Tidak ada catatan produksi actual</td></tr>
            @endforelse
        </tbody>
        @php
            $totPassR = $aStroke > 0 ? ($aGood / $aStroke) * 100 : 0;
            $totRepR = $aStroke > 0 ? ($aRep / $aStroke) * 100 : 0;
            $totRejR = $aStroke > 0 ? ($aRej / $aStroke) * 100 : 0;
            $wOee = $totals['weighted_oee'] ?? 0;
            $wGsph = $totals['weighted_gsph'] ?? 0;
            $actJobs = collect($jobsData)->where('row_type', 'job');
            $tDvt = $actJobs->sum('dies_variant_time');
        @endphp
        <tfoot>
            <tr>
                <td></td>
                <td class="l bb">TOTAL SHIFT</td>
                <td class="r bb">{{ number_format($aPlan,0) }}</td>
                <td class="r bb">{{ number_format($aStroke,0) }}</td>
                <td class="r bb green">{{ number_format($aGood,0) }}</td>
                <td class="r">{{ number_format($aRep,0) }}</td>
                <td class="r">{{ number_format($aRej,0) }}</td>
                <td class="r bb grp-border">{{ number_format($aStroke,0) }}</td>
                
                <td></td><td></td><td></td><td class="grp-border"></td>
                <td></td><td class="grp-border"></td>
                
                <td class="grp-border">{{ (int)ceil($aProc) }}</td>
                
                <td>{{ \App\Support\ProductionFormat::minutes($tDvt) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aQc) }}</td>
                <td class="grp-border">{{ \App\Support\ProductionFormat::minutes($aDan) }}</td>
                
                <td>{{ \App\Support\ProductionFormat::minutes($aDtDies) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtM) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtMat) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtLog) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtProd) }}</td>
                <td>{{ \App\Support\ProductionFormat::minutes($aDtOth) }}</td>
                <td class="bb grp-border">{{ \App\Support\ProductionFormat::minutes($aDtTot) }}</td>
                
                <td>{{ \App\Support\ProductionFormat::minutes($aTptP) }}</td>
                <td class="bb grp-border">{{ \App\Support\ProductionFormat::minutes($aTptA) }}</td>
                
                <td></td><td class="grp-border">{{ \App\Support\ProductionFormat::minutes($aBreak) }}</td>
                
                <td class="bb grp-border">{{ \App\Support\ProductionFormat::minutes($aWork) }}</td>
                
                <td class="b green">{{ number_format($totPassR,1) }}</td>
                <td>{{ number_format($totRepR,1) }}</td>
                <td class="grp-border">{{ number_format($totRejR,1) }}</td>
                
                <td class="b {{ $wOee >= 85 ? 'green' : ($wOee >= 65 ? 'amber' : 'red') }} grp-border">{{ number_format($wOee,1) }}</td>
                <td class="bb red-dark">{{ number_format($wGsph,0) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Halaman 2 dari 2 &bull; Dokumen Resmi Laporan Kerja Harian (LKH) IPPI &bull; Digenerate pada {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>
