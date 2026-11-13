<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Services\DashboardRealtimeService;
use App\Services\ProductionMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikController extends Controller
{
    public function quality(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::today()->subDays(6)->toDateString());
        $dateTo = $request->input('date_to', Carbon::today()->toDateString());
        $line = $request->input('line');

        $query = DailyProduction::whereBetween('work_date', [$dateFrom, $dateTo]);
        if ($line) {
            $query->where('line', $line);
        }

        $daily = $query->selectRaw("
            work_date,
            COALESCE(SUM(actual_ok), 0) as total_ok,
            COALESCE(SUM(actual_repair), 0) as total_repair,
            COALESCE(SUM(actual_reject), 0) as total_reject,
            COALESCE(SUM(actual_ok + actual_repair + actual_reject), 0) as total_qty
        ")->groupBy('work_date')->orderBy('work_date')->get();

        $labels = $daily->pluck('work_date')->map(fn($d) => Carbon::parse($d)->format('d M'));
        $okRates = $daily->map(fn($r) => $r->total_qty > 0 ? round(($r->total_ok / $r->total_qty) * 100, 1) : 0);
        $repairRates = $daily->map(fn($r) => $r->total_qty > 0 ? round(($r->total_repair / $r->total_qty) * 100, 1) : 0);
        $rejectRates = $daily->map(fn($r) => $r->total_qty > 0 ? round(($r->total_reject / $r->total_qty) * 100, 1) : 0);

        $totals = [
            'ok' => $daily->sum('total_ok'),
            'repair' => $daily->sum('total_repair'),
            'reject' => $daily->sum('total_reject'),
            'total' => $daily->sum('total_qty'),
        ];

        // Per-line summary
        $lineQuery = DailyProduction::whereBetween('work_date', [$dateFrom, $dateTo])
            ->selectRaw("
                COALESCE(line, 'Unknown') as line_name,
                COALESCE(SUM(actual_ok), 0) as total_ok,
                COALESCE(SUM(actual_repair), 0) as total_repair,
                COALESCE(SUM(actual_reject), 0) as total_reject,
                COALESCE(SUM(actual_ok + actual_repair + actual_reject), 0) as total_qty
            ")->groupBy('line');
        if ($line) {
            $lineQuery->where('line', $line);
        }
        $lineSummary = $lineQuery->get()->map(fn($r) => [
            'line' => $r->line_name,
            'ok' => (int) $r->total_ok,
            'repair' => (int) $r->total_repair,
            'reject' => (int) $r->total_reject,
            'total' => (int) $r->total_qty,
            'ok_rate' => $r->total_qty > 0 ? round(($r->total_ok / $r->total_qty) * 100, 1) : 0,
            'reject_rate' => $r->total_qty > 0 ? round(($r->total_reject / $r->total_qty) * 100, 1) : 0,
        ]);

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                ['label' => 'OK Rate (%)', 'data' => $okRates, 'backgroundColor' => '#22c55e', 'borderColor' => '#16a34a'],
                ['label' => 'Repair Rate (%)', 'data' => $repairRates, 'backgroundColor' => '#eab308', 'borderColor' => '#ca8a04'],
                ['label' => 'Reject Rate (%)', 'data' => $rejectRates, 'backgroundColor' => '#ef4444', 'borderColor' => '#dc2626'],
            ],
            'totals' => $totals,
            'lineSummary' => $lineSummary,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function downtimeItem(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $limit = (int) $request->input('limit', 10);

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = (clone $startDate)->endOfMonth();

        $items = Downtime::whereNotNull('problem')
            ->whereNotNull('start_time')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->selectRaw("
                problem,
                COALESCE(SUM(duration_seconds), 0) as total_seconds,
                COUNT(*) as count
            ")
            ->groupBy('problem')
            ->orderByDesc('total_seconds')
            ->limit($limit)
            ->get();

        $labels = $items->pluck('problem');
        $data = $items->map(fn($r) => round($r->total_seconds / 60, 1));
        $counts = $items->pluck('count');

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Durasi (Menit)',
                    'data' => $data,
                    'backgroundColor' => '#881337',
                    'borderRadius' => 4,
                ],
            ],
            'counts' => $counts,
            'month' => $month,
        ]);
    }

    public function downtimeType(Request $request)
    {
        try {
            $month = $request->input('month') ?: Carbon::now()->format('Y-m');
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d 23:59:59');
        } catch (\Throwable $e) {
            $month = Carbon::now()->format('Y-m');
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d 23:59:59');
        }

        // Canonical mapping to 7 valid operational categories from Input Harian
        $categoryMap = [
            'mesin' => 'Mesin',
            'dies' => 'Dies',
            'material' => 'Material',
            'logistic' => 'Logistic',
            'logistik' => 'Logistic',
            'try out' => 'Try Out',
            'tryout' => 'Try Out',
            'trial' => 'Try Out',
            'produksi' => 'Produksi',
            'others' => 'Others',
            'other' => 'Others',
            'lainnya' => 'Others',
        ];

        $excludedActivities = [
            'dandori',
            'idle',
            'idle time',
            'break time',
            'break_time',
            'break',
            'istirahat',
            'pray',
            'sholat',
        ];

        // Query raw downtime records in the period with safe timestamp & duration fallbacks
        $rawRecords = Downtime::whereNotNull('jenis_downtime')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('COALESCE(start_time, created_at)'), [$startDate, $endDate]);
            })
            ->whereNotIn(DB::raw('LOWER(TRIM(jenis_downtime))'), $excludedActivities)
            ->select('id', 'jenis_downtime', 'duration_seconds', 'start_time', 'finish_time', 'created_at')
            ->get();

        // Aggregate by canonical categories
        $grouped = [];
        foreach ($rawRecords as $rec) {
            $rawType = strtolower(trim((string) $rec->jenis_downtime));
            if (!isset($categoryMap[$rawType])) {
                $canonical = 'Others';
            } else {
                $canonical = $categoryMap[$rawType];
            }

            // Compute duration in seconds (handle open/uncalculated downtime safely)
            $calcSeconds = (float) ($rec->duration_seconds ?? 0);
            if ($calcSeconds <= 0) {
                $st = $rec->start_time ?: $rec->created_at;
                $fn = $rec->finish_time ?: Carbon::now();
                if ($st) {
                    $calcSeconds = (float) max(0, abs(Carbon::parse($fn)->diffInSeconds(Carbon::parse($st))));
                }
            }

            if (!isset($grouped[$canonical])) {
                $grouped[$canonical] = [
                    'category' => $canonical,
                    'total_seconds' => 0.0,
                    'count' => 0,
                ];
            }

            $grouped[$canonical]['total_seconds'] += $calcSeconds;
            $grouped[$canonical]['count'] += 1;
        }

        // Sort descending by total duration (standard Pareto requirement)
        uasort($grouped, function ($a, $b) {
            if ($b['total_seconds'] === $a['total_seconds']) {
                return $b['count'] <=> $a['count'];
            }
            return $b['total_seconds'] <=> $a['total_seconds'];
        });

        $totalSeconds = array_sum(array_column($grouped, 'total_seconds'));
        $totalMinutes = round($totalSeconds / 60, 1);
        $totalEvents = array_sum(array_column($grouped, 'count'));

        $labels = [];
        $data = [];
        $cumulative = [];
        $percentages = [];
        $counts = [];
        $topCategories = [];
        $items = [];
        $runningSumSeconds = 0;

        foreach ($grouped as $item) {
            $seconds = (float) $item['total_seconds'];
            $minutes = round($seconds / 60, 1);
            $prevCumPct = $totalSeconds > 0 ? round(($runningSumSeconds / $totalSeconds) * 100, 1) : 0.0;
            $runningSumSeconds += $seconds;

            $pct = $totalSeconds > 0 ? round(($seconds / $totalSeconds) * 100, 1) : 0.0;
            $cumPct = $totalSeconds > 0 ? round(($runningSumSeconds / $totalSeconds) * 100, 1) : 0.0;
            if ($cumPct > 100) $cumPct = 100.0;

            $label = $item['category'];

            // Kategori Pareto 80/20: kategori yang menyumbang hingga batas kumulatif 80% (Vital Few)
            $inPareto80 = ($prevCumPct < 80.0 || empty($topCategories));
            if ($inPareto80) {
                $topCategories[] = $label;
            }

            $labels[] = $label;
            $data[] = $minutes;
            $percentages[] = $pct;
            $cumulative[] = $cumPct;
            $counts[] = (int) $item['count'];

            $items[] = [
                'category' => $label,
                'minutes' => $minutes,
                'percentage' => $pct,
                'cumulative' => $cumPct,
                'count' => (int) $item['count'],
                'in_pareto_80' => $inPareto80,
            ];
        }

        // Kategori dominan (terbesar)
        $topCategory = !empty($labels) ? $labels[0] : '-';

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Durasi (Menit)',
                    'data' => $data,
                    'backgroundColor' => array_map(fn($p) => $p >= 20 ? '#dc2626' : ($p >= 10 ? '#f59e0b' : '#2563eb'), $percentages),
                    'borderRadius' => 6,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Kumulatif (%)',
                    'data' => $cumulative,
                    'type' => 'line',
                    'borderColor' => '#1e293b',
                    'backgroundColor' => '#1e293b',
                    'borderWidth' => 2,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => '#1e293b',
                    'tension' => 0.25,
                    'yAxisID' => 'y1',
                ],
            ],
            'items' => $items,
            'data' => $data,
            'cumulative' => $cumulative,
            'percentages' => $percentages,
            'counts' => $counts,
            'top_categories' => $topCategories,
            'topCategory' => $topCategory,
            'totalMinutes' => $totalMinutes,
            'totalEvents' => $totalEvents,
            'month' => $month,
        ]);
    }

    public function downtimeMachine(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $limit = (int) $request->input('limit', 10);

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = (clone $startDate)->endOfMonth();

        $machines = MachineLog::whereBetween('downtime_start', [$startDate, $endDate])
            ->selectRaw("
                machine_id,
                COALESCE(SUM(TIMESTAMPDIFF(SECOND, downtime_start, COALESCE(downtime_end, NOW()))), 0) as total_seconds,
                COUNT(*) as count
            ")
            ->groupBy('machine_id')
            ->orderByDesc('total_seconds')
            ->limit($limit)
            ->get()
            ->load('machine');

        $labels = $machines->map(fn($r) => $r->machine?->name ?? 'Unknown #' . $r->machine_id);
        $data = $machines->map(fn($r) => round($r->total_seconds / 60, 1));
        $counts = $machines->pluck('count');
        $lines = $machines->map(fn($r) => $r->machine?->line ?? '-');

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Durasi (Menit)',
                    'data' => $data,
                    'backgroundColor' => '#be123c',
                    'borderRadius' => 4,
                ],
            ],
            'counts' => $counts,
            'lines' => $lines,
            'month' => $month,
        ]);
    }

    public function dandoriMonitoring(Request $request)
    {
        try {
            $month = $request->input('month') ?: Carbon::now()->format('Y-m');
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->format('Y-m-d');
        } catch (\Throwable $e) {
            $month = Carbon::now()->format('Y-m');
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $query = Dandori::with(['nextJob', 'creator'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('COALESCE(work_date, DATE(start_time))'), [$startDate, $endDate]);
            });

        if ($line = $request->input('line')) {
            $query->where('line', $line);
        }

        if ($shift = $request->input('shift')) {
            $query->where('shift', 'like', "%{$shift}%");
        }

        $records = $query->orderBy('start_time', 'asc')->get();

        $totalMinutes = 0.0;
        $dandoriMinutes = 0.0;
        $dandoriCount = 0;
        $firstCheckMinutes = 0.0;
        $firstCheckCount = 0;

        $dailyStats = [];
        $lineStats = [];
        $items = [];

        // Pre-fill days of month for trend
        $startDay = Carbon::parse($startDate);
        $endDay = Carbon::parse($endDate);
        for ($d = $startDay->copy(); $d->lte($endDay); $d->addDay()) {
            $dayKey = $d->format('Y-m-d');
            $dailyStats[$dayKey] = [
                'date' => $dayKey,
                'dandori' => 0.0,
                'first_check' => 0.0,
                'total' => 0.0,
            ];
        }

        foreach ($records as $r) {
            $dur = (float) ($r->duration_minutes ?? 0);
            if ($dur <= 0 && $r->start_time) {
                $fn = $r->finish_time ?: Carbon::now();
                $dur = round(abs(Carbon::parse($fn)->diffInSeconds(Carbon::parse($r->start_time))) / 60, 2);
            }

            $isFirstCheck = strtolower((string) $r->jenis_dandori) === '1st_check' 
                || strtoupper((string) $r->activity) === '1ST CHECK';

            $totalMinutes += $dur;
            if ($isFirstCheck) {
                $firstCheckMinutes += $dur;
                $firstCheckCount++;
            } else {
                $dandoriMinutes += $dur;
                $dandoriCount++;
            }

            // Daily trend grouping
            $recDate = $r->work_date ? Carbon::parse($r->work_date)->format('Y-m-d') : ($r->start_time ? Carbon::parse($r->start_time)->format('Y-m-d') : null);
            if ($recDate && isset($dailyStats[$recDate])) {
                if ($isFirstCheck) {
                    $dailyStats[$recDate]['first_check'] += $dur;
                } else {
                    $dailyStats[$recDate]['dandori'] += $dur;
                }
                $dailyStats[$recDate]['total'] += $dur;
            }

            // Line grouping
            $recLine = trim((string) $r->line) ?: 'Unknown Line';
            if (!isset($lineStats[$recLine])) {
                $lineStats[$recLine] = [
                    'line' => $recLine,
                    'total_minutes' => 0.0,
                    'dandori_minutes' => 0.0,
                    'first_check_minutes' => 0.0,
                    'count' => 0,
                ];
            }
            $lineStats[$recLine]['total_minutes'] += $dur;
            if ($isFirstCheck) {
                $lineStats[$recLine]['first_check_minutes'] += $dur;
            } else {
                $lineStats[$recLine]['dandori_minutes'] += $dur;
            }
            $lineStats[$recLine]['count']++;

            $items[] = [
                'id' => $r->id,
                'date' => $recDate ?: '-',
                'line' => $r->line ?: '-',
                'shift' => $r->shift ?: '-',
                'job_number' => $r->nextJob?->job_number ?? '-',
                'job_name' => $r->nextJob?->job_name ?? '-',
                'start_time' => $r->start_time ? Carbon::parse($r->start_time)->format('H:i') : '-',
                'finish_time' => $r->finish_time ? Carbon::parse($r->finish_time)->format('H:i') : 'Berjalan',
                'activity' => $isFirstCheck ? '1st Check' : 'Dandori / Dies Change',
                'jenis_dandori' => $isFirstCheck ? '1st_check' : 'dandori',
                'is_first_check' => $isFirstCheck,
                'duration_minutes' => round($dur, 1),
                'pic' => $r->creator?->name ?? 'Operator',
            ];
        }

        // Sort items latest first for table view
        $items = array_reverse($items);

        $totalEvents = count($records);
        $avgMinutes = $totalEvents > 0 ? round($totalMinutes / $totalEvents, 1) : 0.0;

        // Daily trend data format for Chart.js
        $trendLabels = array_keys($dailyStats);
        $trendDandori = array_map(fn($d) => round($d['dandori'], 1), array_values($dailyStats));
        $trendFirstCheck = array_map(fn($d) => round($d['first_check'], 1), array_values($dailyStats));

        // Line breakdown sorted descending
        uasort($lineStats, fn($a, $b) => $b['total_minutes'] <=> $a['total_minutes']);

        return response()->json([
            'month' => $month,
            'summary' => [
                'totalMinutes' => round($totalMinutes, 1),
                'totalEvents' => $totalEvents,
                'avgMinutes' => $avgMinutes,
                'dandoriMinutes' => round($dandoriMinutes, 1),
                'dandoriCount' => $dandoriCount,
                'firstCheckMinutes' => round($firstCheckMinutes, 1),
                'firstCheckCount' => $firstCheckCount,
            ],
            'trend' => [
                'labels' => array_map(fn($dt) => Carbon::parse($dt)->format('d M'), $trendLabels),
                'dandori' => $trendDandori,
                'first_check' => $trendFirstCheck,
            ],
            'composition' => [
                'labels' => ['Ganti Dies / Setup', '1st Check'],
                'data' => [round($dandoriMinutes, 1), round($firstCheckMinutes, 1)],
                'counts' => [$dandoriCount, $firstCheckCount],
            ],
            'lineBreakdown' => array_values($lineStats),
            'items' => $items,
        ]);
    }

    public function gsphMonitoring(Request $request)
    {
        $date = $request->input('date');
        $shift = (int) $request->input('shift', 1);
        if ($shift <= 0) $shift = 1;

        if (!$date) {
            $latestPlanDate = ProductionPlan::activePpcRevision()->where('row_type', 'job')->max('plan_date');
            $date = $latestPlanDate ? Carbon::parse($latestPlanDate)->toDateString() : Carbon::now()->toDateString();
        }

        $allLines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name')->toArray();

        if ($filterLine = $request->input('line')) {
            $lines = array_values(array_filter($allLines, fn($l) => strcasecmp($l, $filterLine) === 0 || str_contains(strtolower($l), strtolower($filterLine))));
            if (empty($lines)) {
                $lines = [$filterLine];
            }
        } else {
            $lines = $allLines;
        }

        $dashService = app(DashboardRealtimeService::class);

        $lineMetricsList = [];
        $jobRows = [];

        $totalPlanGsphSum = 0;
        $totalActGsphSum = 0;
        $activeLineCount = 0;

        foreach ($lines as $lineName) {
            $metrics = $dashService->getLineMetrics($lineName, $date, $shift);
            $kpiGsph = collect($metrics['kpi'])->firstWhere('desc', 'GSPH');
            $kpiQty = collect($metrics['kpi'])->firstWhere('desc', 'QTY');

            $pGsph = $kpiGsph ? (float) ($kpiGsph['plan'] ?? 0) : 0.0;
            $aGsph = $kpiGsph ? (float) ($kpiGsph['actual'] ?? 0) : 0.0;
            $cGsph = $kpiGsph ? ($kpiGsph['current'] ?? '-') : '-';

            $pQty = $kpiQty ? (int) ($kpiQty['plan'] ?? 0) : 0;
            $aQty = $kpiQty ? (int) ($kpiQty['actual'] ?? 0) : 0;

            $achievePct = $pGsph > 0 ? round(($aGsph / $pGsph) * 100, 1) : 0.0;

            if ($pGsph > 0 || $aGsph > 0 || $pQty > 0 || $aQty > 0) {
                $totalPlanGsphSum += $pGsph;
                $totalActGsphSum += $aGsph;
                $activeLineCount++;
            }

            $lineMetricsList[] = [
                'line' => $lineName,
                'plan_gsph' => (int) round($pGsph),
                'actual_gsph' => (int) round($aGsph),
                'current_gsph' => $cGsph,
                'plan_qty' => $pQty,
                'actual_qty' => $aQty,
                'achievement_pct' => $achievePct,
                'status' => $achievePct >= 100 ? 'Target Tercapai' : ($achievePct >= 85 ? 'Mendekati' : ($pGsph > 0 ? 'Under Plan' : 'Standby')),
            ];

            // Detail per job for this line
            $isMorning = ($shift === 1);
            $plans = $dashService->getPlansForDateAndShift($date, $isMorning)
                ->filter(fn($p) => $dashService->pressMatches($p, $lineName))
                ->values();

            foreach ($plans as $p) {
                if ($p->isChildSlice()) continue;
                $pQtyItem = (int) ($p->plan ?? 0);
                $pGsphItem = (int) round((float) ($p->gsph_item ?? 0));

                // Find associated job and daily production
                $job = JobMaster::where('job_number', 'like', "%{$p->id}%")->first();
                $dp = $job ? DailyProduction::where('job_master_id', $job->id)->whereDate('work_date', $date)->first() : null;

                $actQty = (int) ($dp?->actual_ok ?? 0);
                
                // Actual TPT calculation consistent with Input Harian
                $pressTime = $dp ? ((float) $dp->runtime_seconds / 60.0) : 0.0;
                $dandoriTime = 0.0;
                $downtimeTime = 0.0;
                if ($job) {
                    $dandoris = Dandori::where('next_job_id', $job->id)
                        ->where(function($q) use ($date) {
                            $q->whereDate('work_date', $date)
                              ->orWhereDate('start_time', $date);
                        })->get();
                    $dandoriTime = (float) $dandoris->sum('duration_minutes');

                    $dts = Downtime::where('job_master_id', $job->id)
                        ->whereBetween('start_time', [Carbon::parse($date)->startOfDay(), Carbon::parse($date)->endOfDay()])
                        ->whereNotIn('jenis_downtime', ['break time', 'break', 'dandori', 'idle', 'idle time'])
                        ->get();
                    $downtimeTime = round((float) $dts->sum('duration_seconds') / 60.0, 1);
                }

                $actTpt = ProductionMetricsService::actualTpt($pressTime, $dandoriTime, $downtimeTime);
                $actGsph = ProductionMetricsService::gsph($actQty, $actTpt);

                $itemAchieve = $pGsphItem > 0 ? round(($actGsph / $pGsphItem) * 100, 1) : 0.0;

                $jobRows[] = [
                    'line' => $lineName,
                    'job_number' => $p->job_no ?: '-',
                    'job_name' => $p->job_master ?: ($p->job_no ?: '-'),
                    'target_qty' => $pQtyItem,
                    'actual_qty' => $actQty,
                    'press_time' => round($pressTime, 1),
                    'dandori_time' => round($dandoriTime, 1),
                    'downtime_time' => round($downtimeTime, 1),
                    'actual_tpt' => round($actTpt, 1),
                    'plan_gsph' => $pGsphItem,
                    'actual_gsph' => $actGsph,
                    'achievement_pct' => $itemAchieve,
                    'status' => $itemAchieve >= 100 ? 'Tercapai' : ($itemAchieve >= 85 ? 'Mendekati' : ($pGsphItem > 0 ? 'Under' : '-')),
                ];
            }
        }

        $avgPlanGsph = $activeLineCount > 0 ? (int) round($totalPlanGsphSum / $activeLineCount) : 0;
        $avgActualGsph = $activeLineCount > 0 ? (int) round($totalActGsphSum / $activeLineCount) : 0;
        $overallAchieve = $avgPlanGsph > 0 ? round(($avgActualGsph / $avgPlanGsph) * 100, 1) : 0.0;

        // Determine top and bottom lines
        $validLinePerf = array_filter($lineMetricsList, fn($l) => $l['plan_gsph'] > 0 || $l['actual_gsph'] > 0);
        usort($validLinePerf, fn($a, $b) => $b['actual_gsph'] <=> $a['actual_gsph']);
        $topLine = !empty($validLinePerf) ? $validLinePerf[0]['line'] . ' (' . $validLinePerf[0]['actual_gsph'] . ')' : '-';
        $bottomLine = !empty($validLinePerf) ? end($validLinePerf)['line'] . ' (' . end($validLinePerf)['actual_gsph'] . ')' : '-';

        return response()->json([
            'date' => $date,
            'shift' => $shift,
            'summary' => [
                'avgActualGsph' => $avgActualGsph,
                'avgPlanGsph' => $avgPlanGsph,
                'overallAchievement' => $overallAchieve,
                'topLine' => $topLine,
                'bottomLine' => $bottomLine,
                'activeLines' => $activeLineCount,
            ],
            'chart' => [
                'labels' => array_column($lineMetricsList, 'line'),
                'plan' => array_column($lineMetricsList, 'plan_gsph'),
                'actual' => array_column($lineMetricsList, 'actual_gsph'),
                'achievement' => array_column($lineMetricsList, 'achievement_pct'),
            ],
            'lines' => $lineMetricsList,
            'jobs' => $jobRows,
        ]);
    }

    public function gsph(Request $request)
    {
        return $this->gsphMonitoring($request);
    }
}
