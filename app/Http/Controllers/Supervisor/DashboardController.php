<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\MasterBreakTime;
use App\Models\ProductionPlan;
use App\Models\ProductionTarget;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\DailyProduction;
use App\Models\ProductionLog;
use App\Services\DashboardRealtimeService;
use App\Services\DashboardDetailService;
use App\Services\LineStatusService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    protected DashboardRealtimeService $dashService;

    public function __construct(DashboardRealtimeService $dashService)
    {
        $this->dashService = $dashService;
    }

    public function index()
    {
        $selectedLine = request('line');
        $linesQuery = LineMaster::where('status', 'active')->select('line_name')->distinct();
        
        if ($selectedLine) {
            $linesQuery->where('line_name', $selectedLine);
        }
        
        $lines = $linesQuery->pluck('line_name');
        
        $view = 'supervisor.dashboard';
        if (request()->routeIs('supervisor.quality.dashboard') || request()->routeIs('quality.dashboard'))  $view = 'supervisor.quality.dashboard';
        
        return view($view, compact('selectedLine', 'lines'));
    }

    public function monitor()
    {
        $lines = LineMaster::where('status', 'active')->select('line_name')->distinct()->pluck('line_name');
        return view('supervisor.monitor', compact('lines'));
    }

    public function getApiData()
    {
        $date  = request('date', now()->toDateString());
        $shift = (int) request('shift', 1);
        $selectedLine = request('line');

        $lines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name')->toArray();

        if ($selectedLine && in_array($selectedLine, $lines)) {
            $lines = [$selectedLine];
        }

        $lineKpi = [];
        $lineMeta = [];
        $detailData = [
            'QTY'      => ['type' => 'production'],
            'TOTAL_DT' => ['type' => 'dt_summary'],
            'MACH_T'   => ['type' => 'dt_detail'],
            'DIES_T'   => ['type' => 'dt_detail'],
            'MAT_T'    => ['type' => 'dt_detail'],
            'LOG_T'    => ['type' => 'dt_detail'],
            'PROD_T'   => ['type' => 'runtime'],
            'REPAIR'   => ['type' => 'quality'],
            'REJECT'   => ['type' => 'quality'],
        ];

        foreach ($lines as $lineName) {
            $metrics = $this->dashService->getLineMetrics($lineName, $date, $shift);

            $lineKpi[$lineName] = $metrics['kpi'];
            $lineMeta[$lineName] = $metrics['meta'] ?? ['job' => '-', 'stroke' => '0'];

            foreach ($detailData as $key => $template) {
                $detailData[$key][$lineName] = $metrics['detailData'][$key][$lineName] ?? ['rows' => [], 'total' => '0'];
            }
        }

        return response()->json([
            'line_kpi'    => $lineKpi,
            'line_meta'   => $lineMeta,
            'detail_data' => $detailData,
        ]);
    }

    public function getDetailData()
    {
        $date  = request('date', now()->toDateString());
        $shift = (int) request('shift', 1);
        $selectedLine = request('line');

        $detailService = app(DashboardDetailService::class);
        $result = [];

        $lines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name')->toArray();

        if ($selectedLine && in_array($selectedLine, $lines)) {
            $lines = [$selectedLine];
        }

        foreach ($lines as $lineName) {
            $result[$lineName] = $detailService->getLineDetail($lineName, $date, $shift);
        }

        return response()->json(['detail' => $result]);
    }

    public function stream(): StreamedResponse
    {
        $date  = request('date', now()->toDateString());
        $shift = (int) request('shift', 1);
        $selectedLine = request('line');

        $response = new StreamedResponse(function () use ($date, $shift, $selectedLine) {
            ob_implicit_flush(true);

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            $maxLoops = 5;

            $lines = LineMaster::where('status', 'active')
                ->select('line_name')->distinct()->pluck('line_name')->toArray();

            if ($selectedLine && in_array($selectedLine, $lines)) {
                $lines = [$selectedLine];
            }

            for ($i = 0; $i < $maxLoops; $i++) {
                if (connection_aborted()) break;

                $hasUpdate = false;

                foreach ($lines as $lineName) {
                    if (DashboardRealtimeService::hasUpdate($lineName)) {
                        $hasUpdate = true;
                        DashboardRealtimeService::consumeUpdate($lineName);
                    }
                }

                if ($hasUpdate) {
                    $lineKpi = [];
                    $lineMeta = [];
                    $detailData = [
                        'QTY'      => ['type' => 'production'],
                        'TOTAL_DT' => ['type' => 'dt_summary'],
                        'MACH_T'   => ['type' => 'dt_detail'],
                        'DIES_T'   => ['type' => 'dt_detail'],
                        'MAT_T'    => ['type' => 'dt_detail'],
                        'LOG_T'    => ['type' => 'dt_detail'],
                        'PROD_T'   => ['type' => 'runtime'],
                        'REPAIR'   => ['type' => 'quality'],
                        'REJECT'   => ['type' => 'quality'],
                    ];

                    foreach ($lines as $lineName) {
                        $metrics = $this->dashService->getLineMetrics($lineName, $date, $shift);
                        $lineKpi[$lineName] = $metrics['kpi'];
                        $lineMeta[$lineName] = $metrics['meta'] ?? ['job' => '-', 'stroke' => '0'];
                        foreach ($detailData as $key => $template) {
                            $detailData[$key][$lineName] = $metrics['detailData'][$key][$lineName] ?? ['rows' => [], 'total' => '0'];
                        }
                    }

                    echo "data: " . json_encode([
                        'line_kpi'    => $lineKpi,
                        'line_meta'   => $lineMeta,
                        'detail_data' => $detailData,
                    ]) . "\n\n";
                } else {
                    echo ": keepalive\n\n";
                }

                if (ob_get_level() > 0) ob_flush();
                flush();

                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    private function computeOverviewData(string $dateFrom, string $dateTo, string $selectedLine, int $shift): array
    {
        $shiftText = $shift === 1 ? 'Shift Pagi' : 'Shift Malam';

        $query = DailyProduction::query();

        $query->where('work_date', '>=', $dateFrom)
              ->where('work_date', '<=', $dateTo);

        if ($selectedLine !== 'all') {
            $query->where('line', 'LIKE', '%' . $selectedLine);
        }

        $query->where(function ($q) use ($shiftText) {
            $q->where('shift', $shiftText)->orWhere('shift', '')->orWhereNull('shift');
        });

        $totals = (clone $query)->selectRaw('COALESCE(SUM(actual_ok),0) as total_ok, COALESCE(SUM(actual_repair),0) as total_repair, COALESCE(SUM(actual_reject),0) as total_reject')->first();
        $totalOk = (int) $totals->total_ok;
        $totalRepair = (int) $totals->total_repair;
        $totalReject = (int) $totals->total_reject;
        $totalProduction = $totalOk + $totalRepair + $totalReject;

        $okPercent = $totalProduction > 0 ? round(($totalOk / $totalProduction) * 100, 1) : 0;
        $rejectRate = $totalProduction > 0 ? round(($totalReject / $totalProduction) * 100, 2) : 0;

        $targetQuery = ProductionTarget::query();
        $targetQuery->whereDate('target_date', $dateFrom);
        if ($selectedLine !== 'all') {
            $targetQuery->where('process_type', 'LIKE', '%' . $selectedLine);
        }
        $targetQuery->where('shift', $shiftText);
        $targetQty = $targetQuery->sum('target_qty');

        if ($targetQty <= 0) {
            $prodPlanQuery = ProductionPlan::where('plan_date', $dateFrom)
                ->where('shift_name', 'like', $shiftText . '%')
                ->where('row_type', 'job');
            if ($selectedLine !== 'all') {
                $prodPlanQuery->where('press_name', 'LIKE', '%' . $selectedLine);
            }
            $targetQty = (int) $prodPlanQuery->sum('plan');
        }

        if ($targetQty <= 0) {
            $jmQuery = JobMaster::whereHas('dailyProduction', function ($q) use ($dateFrom, $shiftText, $selectedLine) {
                $q->where('work_date', $dateFrom)
                  ->where(function ($sq) use ($shiftText) {
                      $sq->where('shift', $shiftText)->orWhere('shift', '')->orWhereNull('shift');
                  });
                if ($selectedLine !== 'all') {
                    $q->where('line', 'LIKE', '%' . $selectedLine);
                }
            });
            $targetQty = (int) $jmQuery->sum('target_qty');
        }

        $achievementPercent = $targetQty > 0 ? round(($totalOk / $targetQty) * 100, 1) : 0;
        $gap = $targetQty > 0 ? max(0, $targetQty - $totalOk) : 0;

        if ($achievementPercent >= 100) {
            $performanceColor = 'bg-green-600';
        } elseif ($achievementPercent >= 80) {
            $performanceColor = 'bg-yellow-500';
        } elseif ($targetQty > 0) {
            $performanceColor = 'bg-red-600';
        } else {
            $performanceColor = 'bg-gray-400';
        }

        // Chart data
        $chartLabels = [];
        $actualProduction = [];
        $expectedProduction = [];

        $now = Carbon::now();
        $chartDate = Carbon::parse($dateFrom);
        $chartEndDate = Carbon::parse($dateTo);

        if ($shift === 1) {
            $chartStart = $chartDate->copy()->setTime(7, 30);
            $chartEnd   = $chartDate->copy()->setTime(21, 0);
        } else {
            $shiftEndDate = $shift === 2 && $now->format('H:i') >= '21:00' ? Carbon::parse($dateTo)->addDay() : $chartEndDate;
            $chartStart = $chartDate->copy()->setTime(21, 0);
            $chartEnd   = $shiftEndDate->copy()->setTime(7, 30);
        }

        $current = $chartStart->copy();
        while ($current <= $chartEnd) {
            $chartLabels[] = $current->format('H:i');
            $current->addHour();
        }

        $logQuery = ProductionLog::whereBetween('created_at', [$chartStart, $chartEnd]);
        if ($selectedLine !== 'all') {
            $logQuery->whereHas('jobMaster', function ($q) use ($selectedLine) {
                $q->where('line', 'LIKE', '%' . $selectedLine);
            });
        }
        $chartData = $logQuery->selectRaw("HOUR(created_at) as local_hour, SUM(ok_qty) as total_ok")
            ->groupBy('local_hour')
            ->pluck('total_ok', 'local_hour')
            ->toArray();

        $cumulative = 0;
        foreach ($chartLabels as $label) {
            $h = (int)substr($label, 0, 2);
            $cumulative += ($chartData[$h] ?? 0);
            $actualProduction[] = $cumulative;
        }

        if ($targetQty > 0) {
            $step = $targetQty / (count($chartLabels) > 1 ? count($chartLabels) - 1 : 1);
            for ($i = 0; $i < count($chartLabels); $i++) {
                $expectedProduction[] = round($step * $i);
            }
        } else {
            foreach($chartLabels as $l) $expectedProduction[] = 0;
        }

        $latestProductions = $query->with('jobMaster')->latest()->paginate(10);

        return compact(
            'totalOk', 'totalRepair', 'totalReject', 'totalProduction',
            'okPercent', 'rejectRate',
            'targetQty', 'achievementPercent', 'gap', 'performanceColor',
            'chartLabels', 'actualProduction', 'expectedProduction',
            'latestProductions',
        );
    }

    public function overviewData()
    {
        $dateFrom = request('date_from') ?? now()->toDateString();
        $dateTo   = request('date_to') ?? now()->toDateString();
        $selectedLine = request('line', 'all');
        if (empty($selectedLine)) $selectedLine = 'all';
        $shift = (int) (request('shift', '1') ?: '1');

        $data = $this->computeOverviewData($dateFrom, $dateTo, $selectedLine, $shift);

        $progress = ($data['targetQty'] ?? 0) > 0 ? min(round((($data['totalOk'] ?? 0) / $data['targetQty']) * 100, 1), 100) : 0;
        $progColor = $progress >= 80 ? 'bg-green-500' : ($progress >= 50 ? 'bg-amber-500' : 'bg-red-500');
        if (($data['targetQty'] ?? 0) <= 0) $progColor = 'bg-gray-300';

        // Render recent logs HTML
        $logsHtml = view('supervisor.partials.overview_logs', ['latestProductions' => $data['latestProductions'], 'shift' => $shift])->render();

        $noData = ($data['totalOk'] ?? 0) <= 0 && ($data['targetQty'] ?? 0) <= 0;

        return response()->json([
            'kpi' => [
                'target' => $data['targetQty'] ?? 0,
                'achievement' => $data['achievementPercent'] ?? 0,
                'gap' => $data['gap'] ?? 0,
                'ok_qty' => $data['totalOk'] ?? 0,
                'reject_qty' => $data['totalReject'] ?? 0,
                'ok_pct' => $data['okPercent'] ?? 0,
                'reject_rate' => $data['rejectRate'] ?? 0,
                'target_label' => 'Target: ' . (($data['targetQty'] ?? 0) > 0 ? number_format($data['targetQty']) : '0'),
                'actual_label' => 'Actual: ' . number_format($data['totalOk'] ?? 0),
                'progress' => $progress,
                'prog_color' => $progColor,
                'achievement_color' => ($data['achievementPercent'] ?? 0) >= 80 ? 'text-green-600' : 'text-red-600',
                'no_data' => $noData,
            ],
            'chart' => [
                'labels' => $data['chartLabels'] ?? [],
                'expected' => $data['expectedProduction'] ?? [],
                'actual' => $data['actualProduction'] ?? [],
            ],
            'logs_html' => $logsHtml,
            'logs_pagination' => $data['latestProductions']->links()->toHtml(),
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
        FILTERS
        ====================================
        */

        $selectedLine = request('line', 'all');
        if (empty($selectedLine)) $selectedLine = 'all';

        $selectedShift = request('shift', '1');
        if (empty($selectedShift)) $selectedShift = '1';

        $now = Carbon::now();
        $time = $now->format('H:i');

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();

        $shift = (int) $selectedShift;
        $isOvertime = false;
        $shiftStart = null;
        $shiftEnd = null;

        if ($shift === 1) {
            $shiftStart = $today->copy()->setTime(7, 30);
            $shiftEnd   = $today->copy()->setTime(21, 0);
            $isOvertime = $time >= '16:15' && $time < '21:00';
        } else {
            if ($time >= '21:00') {
                $shiftStart = $today->copy()->setTime(21, 0);
                $shiftEnd   = $tomorrow->copy()->setTime(7, 30);
            } else {
                $shiftStart = $yesterday->copy()->setTime(21, 0);
                $shiftEnd   = $today->copy()->setTime(7, 30);
            }
            $isOvertime = $time >= '04:30' && $time < '07:30';
        }


        /*
        ====================================
        BUILD OVERVIEW DATA
        ====================================
        */

        $data = $this->computeOverviewData($dateFrom, $dateTo, $selectedLine, $shift);

        extract($data);


        /*
        ====================================
        BREAK SCHEDULE
        ====================================
        */

        $dayName = $now->format('l');
        $dayMap = [
            'Monday'=>'senin','Tuesday'=>'selasa','Wednesday'=>'rabu',
            'Thursday'=>'kamis','Friday'=>'jumat','Saturday'=>'sabtu','Sunday'=>'minggu'
        ];
        $dayDb = $dayMap[$dayName] ?? strtolower($dayName);
        $shiftDb = $shift === 1 ? 'Shift Pagi' : 'Shift Malam';

        $masterBreaks = MasterBreakTime::where('is_active', true)
            ->where(function ($q) use ($dayDb) {
                $q->where('hari', $dayDb)->orWhere('hari', 'semua');
            })
            ->where(function ($q) use ($shiftDb) {
                $q->where('shift', $shiftDb)->orWhereNull('shift');
            })
            ->orderBy('sort_order')
            ->get();

        $breakSchedule = [];
        $isBreak = false;
        $currentBreak = null;
        $nowMinutes = $now->hour * 60 + $now->minute;

        foreach ($masterBreaks as $b) {
            $startStr = substr($b->waktu_mulai, 0, 5);
            $endStr   = substr($b->waktu_selesai, 0, 5);
            $startMin = MasterBreakTime::timeToMinutes($startStr);
            $endMin   = MasterBreakTime::timeToMinutes($endStr);

            $entry = [
                'label'    => $b->label,
                'type'     => $b->type,
                'start'    => $startStr,
                'end'      => $endStr,
                'startMin' => $startMin,
                'endMin'   => $endMin,
            ];
            $breakSchedule[] = $entry;

            if ($nowMinutes >= $startMin && $nowMinutes < $endMin) {
                $isBreak = true;
                $currentBreak = $entry;
            }
        }

        $timeToNextBreak = null;
        if (!$isBreak) {
            foreach ($breakSchedule as $b) {
                if ($nowMinutes < $b['startMin']) {
                    $timeToNextBreak = $b['start'] . ' - ' . $b['end'] . ' (' . $b['label'] . ')';
                    break;
                }
            }
        }

        $lineStatuses = LineStatusService::getStatuses($shift);

        $openAbnormality = 0;
        $activeDowntime = 0;

        $serverNow = now()->format('Y-m-d H:i:s');
        $shiftStartFull = $shiftStart->format('Y-m-d H:i:s');
        $shiftEndFull = $shiftEnd->format('Y-m-d H:i:s');

        return view('supervisor.overview', compact(
            'dateFrom', 'dateTo', 'selectedLine', 'selectedShift',
            'totalOk', 'totalRepair', 'totalReject', 'rejectRate',
            'okPercent',
            'targetQty', 'achievementPercent', 'gap', 'performanceColor',
            'openAbnormality', 'activeDowntime',
            'lineStatuses',
            'chartLabels', 'actualProduction', 'expectedProduction',
            'latestProductions',
            'shift', 'isOvertime', 'shiftStart', 'shiftEnd',
            'isBreak', 'currentBreak', 'breakSchedule', 'timeToNextBreak',
            'serverNow', 'shiftStartFull', 'shiftEndFull',
        ));
    }

    public function allProductionLogs()
    {
        $dateFrom = request('date_from') ?? now()->toDateString();
        $selectedLine = request('line', 'all');
        $shift = (int) (request('shift', '1') ?: '1');
        $shiftText = $shift === 1 ? 'Shift Pagi' : 'Shift Malam';

        $logs = DailyProduction::with('jobMaster')
            ->where('work_date', $dateFrom)
            ->where(function ($q) use ($shiftText) {
                $q->where('shift', $shiftText)->orWhere('shift', '')->orWhereNull('shift');
            });

        if ($selectedLine !== 'all') {
            $logs->where('line', 'LIKE', '%' . $selectedLine);
        }

        $logs = $logs->latest()->paginate(15);

        return response()->json($logs);
    }

    public function overviewLineStatus()
    {
        $dateFrom = request('date_from') ?? now()->toDateString();
        $time = now()->format('H:i');
        $shift = ($time >= '07:30' && $time < '21:00') ? 1 : 2;

        $statuses = LineStatusService::getStatuses($shift);

        return response()->json(['line_statuses' => $statuses]);
    }

    public function lineStatusSingle($line)
    {
        $time = now()->format('H:i');
        $shift = ($time >= '07:30' && $time < '21:00') ? 1 : 2;
        $statuses = LineStatusService::getStatuses($shift);
        $status = $statuses[$line] ?? ['label' => 'NOT RUNNING', 'color' => 'gray', 'pulse' => false];

        return response()->json(['line' => $line, 'status' => $status]);
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