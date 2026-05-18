<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ProductionProcess;
use App\Models\ProductionTarget;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $selectedLine = request('line');
        $linesQuery = \App\Models\LineMaster::where('status', 'active')->select('line_name')->distinct();
        
        if ($selectedLine) {
            $linesQuery->where('line_name', $selectedLine);
        }
        
        $lines = $linesQuery->pluck('line_name');
        
        $view = 'supervisor.dashboard';
        if (request()->routeIs('supervisor.downtime.dashboard')) $view = 'supervisor.downtime.dashboard';
        if (request()->routeIs('supervisor.quality.dashboard'))  $view = 'supervisor.quality.dashboard';
        
        return view($view, compact('selectedLine', 'lines'));
    }

    public function getApiData()
    {
        $date  = request('date', now()->toDateString());
        $shift = request('shift', 1);
        $selectedLine = request('line');

        // Fetch lines dynamically from database
        $lines = \App\Models\LineMaster::where('status', 'active')->select('line_name')->distinct()->pluck('line_name')->toArray();

        // If a specific line is selected, only process that line
        if ($selectedLine && in_array($selectedLine, $lines)) {
            $lines = [$selectedLine];
        }

        // 1. Get all jobs for the selected date and shift
        $jobs = \App\Models\JobMaster::whereDate('created_at', $date)
            ->where('status', '!=', 'pending')
            ->with(['productionLogs', 'downtimes'])
            ->get();
        $lineKpi = [];
        $detailData = [
            'TOTAL_DT' => ['type' => 'dt_summary'],
            'MACH_T'   => ['type' => 'dt_detail'],
            'DIES_T'   => ['type' => 'dt_detail'],
            'MAT_T'    => ['type' => 'dt_detail'],
            'LOG_T'    => ['type' => 'dt_detail'],
            'PROD_T'   => ['type' => 'dt_detail'],
            'REPAIR'   => ['type' => 'quality'],
            'REJECT'   => ['type' => 'quality'],
        ];

        foreach ($lines as $lineName) {
            $lineJobs = $jobs->filter(fn($j) => strtoupper($j->line) === strtoupper($lineName));
            
            $ok      = 0;
            $repair  = 0;
            $reject  = 0;
            $dtTotal = 0;
            $dtMach  = 0;
            $dtDies  = 0;
            $dtMat   = 0;
            $dtLog   = 0;
            $prodT   = 0;

            $dtRows     = [];
            $machRows   = [];
            $diesRows   = [];
            $matRows    = [];
            $logRows    = [];
            $repairRows = [];
            $rejectRows = [];

            foreach ($lineJobs as $job) {
                // Production Qty
                $ok     += $job->productionLogs->sum('ok_qty');
                $repair += $job->productionLogs->sum('repair_qty');
                $reject += $job->productionLogs->sum('reject_qty');

                // Downtimes
                foreach ($job->downtimes as $dt) {
                    $dur = round($dt->duration_seconds / 60, 1);
                    $dtTotal += $dur;

                    $row = [
                        'no'     => count($dtRows) + 1,
                        'jenis'  => $dt->jenis_downtime,
                        'item'   => $dt->problem ?? '-',
                        'problem'=> $dt->problem ?? '-',
                        'penyebab'=> $dt->penyebab ?? '-',
                        'action' => $dt->action ?? '-',
                        'durasi' => $dur
                    ];

                    $dtRows[] = $row;

                    $type = strtoupper($dt->jenis_downtime);
                    if (str_contains($type, 'MACHINE')) { $dtMach += $dur; $machRows[] = $row; }
                    elseif (str_contains($type, 'DIES'))    { $dtDies += $dur; $diesRows[] = $row; }
                    elseif (str_contains($type, 'MATERIAL')){ $dtMat  += $dur; $matRows[]  = $row; }
                    elseif (str_contains($type, 'LOGISTIC')){ $dtLog  += $dur; $logRows[]  = $row; }
                }

                // Repair/Reject Detail
                foreach($job->productionLogs->where('repair_qty', '>', 0) as $log) {
                    $repairRows[] = ['no' => count($repairRows)+1, 'item' => $job->job_name, 'problem' => 'Defect', 'qty' => $log->repair_qty];
                }
                foreach($job->productionLogs->where('reject_qty', '>', 0) as $log) {
                    $rejectRows[] = ['no' => count($rejectRows)+1, 'item' => $job->job_name, 'problem' => 'Reject', 'qty' => $log->reject_qty];
                }
            }

            // GSPH Calculation (Dummy logic for now: assume 8 hours if ok > 0)
            $gsph = $ok > 0 ? round($ok / 8, 1) : 0;

            $lineKpi[$lineName] = [
                ['desc'=>'QTY',      'plan'=>'450',           'actual'=>(string)$ok,         'actualLink'=>true, 'current'=>'-'              ],
                ['desc'=>'GSPH',     'plan'=>'85.0',          'actual'=>(string)$gsph,       'current'=>(string)($gsph*0.9)                  ],
                ['desc'=>'PROD_T',   'plan'=>'480 m',         'actual'=>'460 m',             'current'=>'230 m', 'popup'=>true               ],
                ['desc'=>'TOTAL_DT', 'plan'=>'0 m',           'actual'=>$dtTotal.' m',       'current'=>round($dtTotal/2,1).' m', 'popup'=>true, 'danger'=>$dtTotal > 0],
                ['desc'=>'MACH_T',   'plan'=>'0 m',           'actual'=>$dtMach.' m',        'current'=>round($dtMach/2,1).' m', 'popup'=>true   ],
                ['desc'=>'DIES_T',   'plan'=>'0 m',           'actual'=>$dtDies.' m',        'current'=>round($dtDies/2,1).' m', 'popup'=>true   ],
                ['desc'=>'MAT_T',    'plan'=>'0 m',           'actual'=>$dtMat.' m',         'current'=>round($dtMat/2,1).' m',  'popup'=>true   ],
                ['desc'=>'LOG_T',    'plan'=>'0 m',           'actual'=>$dtLog.' m',         'current'=>round($dtLog/2,1).' m',  'popup'=>true   ],
                ['desc'=>'IDLE_T',   'plan'=>'0 m',           'actual'=>'0 m',               'current'=>'-'                               ],
                ['desc'=>'OVERTIME', 'plan'=>'0 m',           'actual'=>'0 m',               'current'=>'-'                               ],
                ['desc'=>'REPAIR',   'plan'=>'5 pcs',         'actual'=>$repair.' pcs',      'actualPct'=>($ok>0?round(($repair/$ok)*100,1):0).'%', 'current'=>'-', 'popup'=>true ],
                ['desc'=>'REJECT',   'plan'=>'2 pcs',         'actual'=>$reject.' pcs',      'actualPct'=>($ok>0?round(($reject/$ok)*100,1):0).'%', 'current'=>'-', 'popup'=>true ],
            ];

            $detailData['TOTAL_DT'][$lineName] = ['rows' => $dtRows, 'total' => (string)$dtTotal];
            $detailData['MACH_T'][$lineName]   = ['rows' => $machRows, 'total' => (string)$dtMach];
            $detailData['DIES_T'][$lineName]   = ['rows' => $diesRows, 'total' => (string)$dtDies];
            $detailData['MAT_T'][$lineName]    = ['rows' => $matRows, 'total' => (string)$dtMat];
            $detailData['LOG_T'][$lineName]    = ['rows' => $logRows, 'total' => (string)$dtLog];
            $detailData['PROD_T'][$lineName]   = ['rows' => [], 'total' => '460']; // Placeholder rows for now
            $detailData['REPAIR'][$lineName]   = ['rows' => $repairRows, 'total' => (string)$repair];
            $detailData['REJECT'][$lineName]   = ['rows' => $rejectRows, 'total' => (string)$reject];
        }

        return response()->json([
            'line_kpi'    => $lineKpi,
            'detail_data' => $detailData
        ]);
    }

    public function overview()
    {

        /*
        ====================================
        DEFAULT DATE
        ====================================
        */

        $dateFrom = request('date_from') ?? now()->toDateString();
        $dateTo   = request('date_to') ?? now()->toDateString();


        /*
        ====================================
        BASE QUERY
        ====================================
        */

        $query = ProductionProcess::with('job');

        $query->whereDate('created_at', '>=', $dateFrom)
              ->whereDate('created_at', '<=', $dateTo);

        if (request('process_type')) {
            $query->where('process_type', request('process_type'));
        }

        if (request('shift')) {
            $query->where('shift', request('shift'));
        }

        if (request('order')) {
            $query->where('production_order_number', 'like', '%' . request('order') . '%');
        }


        /*
        ====================================
        PRODUCTION TOTAL
        ====================================
        */

        $totalOk = (clone $query)->sum('qty_ok');
        $totalRepair = (clone $query)->sum('qty_repair');
        $totalReject = (clone $query)->sum('qty_reject');

        $totalProduction = $totalOk + $totalRepair + $totalReject;

        $okPercent = $totalProduction > 0 ? round(($totalOk / $totalProduction) * 100, 1) : 0;
        $repairPercent = $totalProduction > 0 ? round(($totalRepair / $totalProduction) * 100, 1) : 0;
        $rejectPercent = $totalProduction > 0 ? round(($totalReject / $totalProduction) * 100, 1) : 0;


        /*
        ====================================
        REJECT RATE
        ====================================
        */

        $rejectRate = $totalProduction > 0
            ? round(($totalReject / $totalProduction) * 100, 2)
            : 0;


        /*
        ====================================
        TARGET QUERY
        ====================================
        */

        $targetQuery = ProductionTarget::query();

        $targetQuery->whereDate('target_date', $dateFrom);

        if (request('process_type')) {
            $targetQuery->where('process_type', request('process_type'));
        }

        if (request('shift')) {
            $targetQuery->where('shift', request('shift'));
        }

        $targetQty = $targetQuery->sum('target_qty');


        /*
        ====================================
        ACHIEVEMENT
        ====================================
        */

        if ($targetQty > 0) {

            $achievementPercent = round(($totalOk / $targetQty) * 100, 1);

            $gap = $targetQty - $totalOk;

            if ($achievementPercent >= 100) {
                $performanceColor = 'bg-green-600';
            } elseif ($achievementPercent >= 80) {
                $performanceColor = 'bg-yellow-500';
            } else {
                $performanceColor = 'bg-red-600';
            }

        } else {

            $achievementPercent = 0;
            $gap = 0;
            $performanceColor = 'bg-gray-400';

        }

        /*
        ====================================
        TIME BASE (FIX REDUNDANSI)
        ====================================
        */

        $now = Carbon::now();
        $time = $now->format('H:i');

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();

        // DEFAULT
        $minutesPassed = 0;
        $remainingMinutes = 0;
        $shift = null;
        $isOvertime = false;

        /*
        ====================================
        SHIFT LOGIC
        ====================================
        */

        if ($time >= '07:30' && $time < '16:15') {
            $shift = 1;
            $shiftStart = $today->copy()->setTime(7,30);
            $shiftEnd   = $today->copy()->setTime(16,15);
        }

        elseif ($time >= '16:15' && $time < '21:00') {
            $shift = 1;
            $isOvertime = true;
            $shiftStart = $today->copy()->setTime(7,30);
            $shiftEnd   = $today->copy()->setTime(21,0);
        }

        elseif ($time >= '21:00' || $time < '04:30') {
            $shift = 2;

            if ($time >= '21:00') {
                $shiftStart = $today->copy()->setTime(21,0);
                $shiftEnd   = $tomorrow->copy()->setTime(4,30);
            } else {
                $shiftStart = $yesterday->copy()->setTime(21,0);
                $shiftEnd   = $today->copy()->setTime(4,30);
            }
        }

        elseif ($time >= '04:30' && $time < '07:30') {
            $shift = 2;
            $isOvertime = true;
            $shiftStart = $yesterday->copy()->setTime(21,0);
            $shiftEnd   = $today->copy()->setTime(7,30);
        }

        /*
====================================
REALTIME TIME CALC (SYNC WITH JS)
====================================
*/

if (isset($shiftStart) && isset($shiftEnd)) {

    // total shift duration
    $shiftDurationMinutes = $shiftStart->diffInMinutes($shiftEnd);

    // realtime minutes passed
    $minutesPassed = $shiftStart->diffInMinutes($now);

    // clamp biar ga minus / over
    if ($now < $shiftStart) {
        $minutesPassed = 0;
    }

    if ($now > $shiftEnd) {
        $minutesPassed = $shiftDurationMinutes;
    }

    // remaining realtime
    $remainingMinutes = max($shiftDurationMinutes - $minutesPassed, 0);
}

        /*
        ====================================
        BREAK LOGIC
        ====================================
        */

        $day = $now->format('l');

        $breaks = []; 
        $isBreak = false;

        $currentBreakStart = null;
        $currentBreakEnd = null;

        if (in_array($day, ['Monday','Tuesday','Wednesday','Thursday'])) {

            $breaks[] = [
                'start' => $today->copy()->setTime(12,0),
                'end'   => $today->copy()->setTime(12,40),
            ];

            $breaks[] = [
                'start' => $today->copy()->setTime(15,15),
                'end'   => $today->copy()->setTime(15,30),
            ];
        }

        elseif ($day == 'Friday') {

            $breaks[] = [
                'start' => $today->copy()->setTime(11,45),
                'end'   => $today->copy()->setTime(12,45),
            ];
        }

        foreach ($breaks as $b) {

            if (
                $now->greaterThanOrEqualTo($b['start']) &&
                $now->lessThan($b['end'])
            ) {
                $isBreak = true;
                $currentBreakStart = $b['start'];
                $currentBreakEnd = $b['end'];
                break;
            }
        }

        /*
        ====================================
        EFFECTIVE TIME
        ====================================
        */

        $breakMinutesPassed = 0;
        $futureBreakMinutes = 0;

        $effectiveMinutesPassed = max($minutesPassed - $breakMinutesPassed, 0);
        $effectiveRemainingMinutes = max($remainingMinutes - $futureBreakMinutes, 0);

        if ($effectiveRemainingMinutes < 0) {
            $effectiveRemainingMinutes = 0;
        }

        foreach ($breaks as $b) {

            if ($now > $b['end']) {
                $breakMinutesPassed += $b['start']->diffInMinutes($b['end']);
            }

            elseif ($now >= $b['start'] && $now < $b['end']) {
                $breakMinutesPassed += $b['start']->diffInMinutes($now);
            }

            elseif ($now < $b['start']) {
                $futureBreakMinutes += $b['start']->diffInMinutes($b['end']);
            }
        }

        $effectiveMinutesPassed = max($minutesPassed - $breakMinutesPassed, 0);
        $effectiveRemainingMinutes = max($remainingMinutes - $futureBreakMinutes, 0);

        // HOURS
        $hoursPassed = round($effectiveMinutesPassed / 60, 2);
        $remainingHours = round($effectiveRemainingMinutes / 60, 2);

        /*
        ====================================
        SPEED CALCULATION
        ====================================
        */

        $currentSpeed = ($effectiveMinutesPassed > 0)
            ? round(($totalOk / $effectiveMinutesPassed) * 60, 1)
            : 0;

        $requiredSpeed = ($effectiveRemainingMinutes > 0 && $gap > 0)
            ? round(($gap / $effectiveRemainingMinutes) * 60, 1)
            : 0;

        /*
        ====================================
        MACHINE STATUS
        ====================================
        */

        $openAbnormality = 0;
        $activeDowntime = 0;

        /*
        ====================================
        PRODUCTION TREND CHART (IMPROVED)
        ====================================
        */
        $chartLabels = [];
        $actualProduction = [];
        $expectedProduction = [];

        // Generate full shift timeline
        $current = $shiftStart->copy();
        while ($current <= $shiftEnd) {
            $chartLabels[] = $current->format('H:i');
            $current->addHour();
        }

        // Fetch data and map to local timezone
        $chartData = ProductionProcess::whereBetween('created_at', [$shiftStart, $shiftEnd])
            ->selectRaw("HOUR(CONVERT_TZ(created_at, '+00:00', '+07:00')) as local_hour, SUM(qty_ok) as total_ok")
            ->groupBy('local_hour')
            ->pluck('total_ok', 'local_hour')
            ->toArray();

        $cumulative = 0;
        foreach ($chartLabels as $label) {
            $h = (int)substr($label, 0, 2);
            $cumulative += ($chartData[$h] ?? 0);
            $actualProduction[] = $cumulative;
        }

        // Expected line based on target
        if ($targetQty > 0) {
            $step = $targetQty / (count($chartLabels) > 1 ? count($chartLabels) - 1 : 1);
            for ($i = 0; $i < count($chartLabels); $i++) {
                $expectedProduction[] = round($step * $i);
            }
        } else {
            // Placeholder expected line if target is 0
            foreach($chartLabels as $l) $expectedProduction[] = 0;
        }

        /*
        ====================================
        RECENT PRODUCTION (TIDAK DIUBAH)
        ====================================
        */

        $latestProductions = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        /*
        ====================================
        STATUS
        ====================================
        */

        $status = 'waiting';

        if (($requiredSpeed ?? 0) > 0) {
            $status = $currentSpeed >= $requiredSpeed ? 'on_track' : 'behind';
        }

        // realtime shift egine
        

        /*
        ====================================
        RETURN
        ====================================
        */

        return view('supervisor.overview', [

            'totalOk' => $totalOk,
            'totalRepair' => $totalRepair,
            'totalReject' => $totalReject,
            'rejectRate' => $rejectRate,
            'okPercent' => $okPercent,
            'repairPercent' => $repairPercent, 
            'rejectPercent' => $rejectPercent,

            'targetQty' => $targetQty,
            'achievementPercent' => $achievementPercent,
            'gap' => $gap,
            'performanceColor' => $performanceColor,

            'remainingHours' => $remainingHours,
            'currentSpeed' => $currentSpeed,
            'requiredSpeed' => $requiredSpeed,
            
            'openAbnormality' => $openAbnormality,
            'activeDowntime' => $activeDowntime,

            'chartLabels' => $chartLabels,
            'actualProduction' => $actualProduction,
            'expectedProduction' => $expectedProduction,

            'latestProductions' => $latestProductions,
            'status' => $status,
            
            'shift' => $shift,
            'isOvertime' => $isOvertime,
            'shiftStart' => $shiftStart->format('H:i'),
            'shiftEnd' => $shiftEnd->format('H:i'),

            'isBreak' => $isBreak,
            'breakStart' => $currentBreakStart?->format('H:i'),
            'breakEnd' => $currentBreakEnd?->format('H:i'),
            'serverNow' => now()->format('Y-m-d H:i:s'),
            'shiftStartFull' => $shiftStart->format('Y-m-d H:i:s'),
            'shiftEndFull' => $shiftEnd->format('Y-m-d H:i:s'),

        ]);
    }

    public function troubleHistory()
    {
        $date = request('date', now()->toDateString());
        $search = request('search');

        $query = \App\Models\Downtime::with('jobMaster')
            ->whereDate('created_at', $date);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('problem', 'like', "%{$search}%")
                  ->orWhere('penyebab', 'like', "%{$search}%")
                  ->orWhere('pic', 'like', "%{$search}%")
                  ->orWhereHas('jobMaster', function($jq) use ($search) {
                      $jq->where('job_number', 'like', "%{$search}%");
                  });
            });
        }

        $downtimes = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('downtime.trouble_history', compact('downtimes', 'date', 'search'));
    }
}