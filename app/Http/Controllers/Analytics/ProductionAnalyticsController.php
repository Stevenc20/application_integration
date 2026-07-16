<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\Downtime;
use App\Models\ProductionLog;
use App\Models\ProductionSession;
use App\Models\DandoriSession;
use App\Models\ProductionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductionAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'overview');

        // Persist filter params in session so they survive Detail → Kembali navigation
        $filterKeys = ['date_from', 'date_to', 'line', 'shift', 'status'];
        $sessionKey = 'analytics_production_filters';

        if ($request->get('reset') === '1') {
            session()->forget($sessionKey);
        }

        $hasFilter = $request->hasAny($filterKeys);

        if ($hasFilter) {
            foreach ($filterKeys as $k) {
                if ($request->has($k)) {
                    session([$sessionKey.'.'.$k => $request->get($k)]);
                }
            }
        }

        $saved = session($sessionKey, []);
        $dateFrom = $request->get('date_from', $saved['date_from'] ?? Carbon::now()->format('Y-m-d'));
        $dateTo = $request->get('date_to', $saved['date_to'] ?? Carbon::now()->format('Y-m-d'));
        $line = $request->get('line', $saved['line'] ?? null);
        $shift = $request->get('shift', $saved['shift'] ?? $this->detectShift());
        $status = $request->get('status', $saved['status'] ?? null);

        $lines = JobMaster::distinct()->pluck('line')->filter()->values();

        $data = match ($tab) {
            'overview' => $this->overviewData($dateFrom, $dateTo, $line, $shift, $status),
            'timeline' => $this->timelineData($dateFrom, $dateTo, $line),
            'history' => $this->historyData($request, $dateFrom, $dateTo, $line, $shift),
            'lifecycle' => $this->lifecycleData(),
            'more_detail' => $this->moreDetailData($dateFrom, $dateTo, $line, $shift),
            default => $this->overviewData($dateFrom, $dateTo, $line, $shift, $status),
        };

        $data['jobList'] = $data['plans'] ?? collect();
        return view('analytics.index', compact('tab', 'dateFrom', 'dateTo', 'line', 'shift', 'status', 'lines') + $data);
    }

    private function detectShift()
    {
        $hour = (int) now()->format('H');
        return ($hour >= 7 && $hour < 19) ? 'Shift Pagi' : 'Shift Malam';
    }

    private function overviewData($dateFrom, $dateTo, $line, $shift, $status)
    {
        // 1. Aggregate stats from DailyProduction (for summary row)
        $dpQuery = DailyProduction::whereBetween('work_date', [$dateFrom, $dateTo]);
        if ($line) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $dpQuery->whereRaw("REPLACE(REPLACE(UPPER(TRIM(`line`)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"]);
        }
        if ($shift) $dpQuery->where('shift', 'like', '%'.$shift.'%');

        $stats = (clone $dpQuery)->selectRaw('
            COALESCE(SUM(actual_ok + actual_repair + actual_reject), 0) as total_qty,
            COALESCE(SUM(actual_ok), 0) as total_ok,
            COALESCE(SUM(actual_repair), 0) as total_repair,
            COALESCE(SUM(actual_reject), 0) as total_reject,
            COALESCE(SUM(runtime_seconds), 0) as total_runtime,
            COALESCE(SUM(downtime_seconds), 0) as total_downtime,
            COALESCE(SUM(target_qty), 0) as total_target
        ')->first();

        $dailyTrend = (clone $dpQuery)
            ->selectRaw('work_date, COALESCE(SUM(actual_ok), 0) as ok, COALESCE(SUM(actual_repair), 0) as repair, COALESCE(SUM(actual_reject), 0) as reject, COALESCE(SUM(actual_ok + actual_repair + actual_reject), 0) as qty, COALESCE(SUM(target_qty), 0) as target_qty')
            ->groupBy('work_date')
            ->orderBy('work_date')
            ->get();

        $planTarget = ProductionPlan::whereBetween('plan_date', [$dateFrom, $dateTo])
            ->whereIn('row_type', ['job', 'break'])
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);
        if ($line) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $planTarget->whereRaw("REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"]);
        }
        if ($shift) $planTarget->where('shift_name', 'like', '%'.$shift.'%');
        $totalPlanTarget = $planTarget->sum('plan');

        $achievementTarget = max($stats->total_target, $totalPlanTarget);
        $achievement = $achievementTarget > 0 ? round(($stats->total_ok / $achievementTarget) * 100, 1) : 0;

        $dpJobIds = (clone $dpQuery)->distinct()->pluck('job_master_id')->filter();
        $downtimeAgg = $dpJobIds->isNotEmpty()
            ? Downtime::whereIn('job_master_id', $dpJobIds)
                ->whereBetween('start_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN jenis_downtime IN ('idle','idle time') THEN duration_seconds ELSE 0 END), 0) as idle_sec,
                    COALESCE(SUM(CASE WHEN jenis_downtime='repair' THEN duration_seconds ELSE 0 END), 0) as repair_sec,
                    COALESCE(SUM(CASE WHEN jenis_downtime='dandori' THEN duration_seconds ELSE 0 END), 0) as dandori_sec,
                    COALESCE(SUM(CASE WHEN jenis_downtime IN ('try out','tryout') THEN duration_seconds ELSE 0 END), 0) as tryout_sec,
                    COALESCE(SUM(CASE WHEN jenis_downtime IN ('break time','break') THEN duration_seconds ELSE 0 END), 0) as break_sec
                ")->first()
            : (object) ['idle_sec' => 0, 'repair_sec' => 0, 'dandori_sec' => 0, 'tryout_sec' => 0, 'break_sec' => 0];

        // 2. Get PPC plans as the primary job list (like Input Harian)
        $plans = ProductionPlan::whereBetween('plan_date', [$dateFrom, $dateTo])
            ->whereIn('row_type', ['job', 'break'])
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);
        if ($line) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $plans->whereRaw("REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"]);
        }
        if ($shift) $plans->where('shift_name', 'like', '%'.$shift.'%');
        $plans = $plans->orderBy('row_no')->get();

        // 3. Build job_number identifiers and fetch JobMasters
        $jobNumbers = $plans->map(function ($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . Str::slug($jm) . '-' . $p->id);
        })->toArray();

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with([
                'dailyProduction' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('work_date', [$dateFrom, $dateTo]);
                },
                'downtimes' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('start_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
                },
                'productionLogs' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])->orderBy('created_at', 'desc')->take(5);
                },
                'productionSessions' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereDate('work_date', '>=', $dateFrom)->whereDate('work_date', '<=', $dateTo)->orderBy('start_time');
                },
                'dandoriSessions' => function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('start_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])->orderBy('start_time');
                },
                'dandoris' => function ($q) use ($dateFrom, $dateTo) {
                    $q->where('jenis_dandori', '1st_check')
                      ->whereBetween('start_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
                },
            ])
            ->get()
            ->keyBy('job_number');

        // 4. Attach job_data to each plan (like Input Harian does)
        $plans->each(function ($plan) use ($jobMasters) {
            $jobNo = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $key = $jobNo ? ($jobNo . '-' . $plan->id) : ('AUTO-' . Str::slug($jm) . '-' . $plan->id);
            $plan->job_data = $jobMasters->get($key);
        });

        // 4b. Recalculate total_runtime from actual session data (matches Input Harian timeline)
        $calculatedRuntime = 0;
        foreach ($plans as $plan) {
            $job = $plan->job_data;
            if (!$job) continue;
            $jobSessionSec = 0;
            foreach ($job->productionSessions as $ps) {
                $jobSessionSec += (int)$ps->total_seconds;
            }
            $jobDowntimeSec = (int)$job->downtimes->sum('duration_seconds');
            $calculatedRuntime += max(0, $jobSessionSec - $jobDowntimeSec);
        }
        $stats->total_runtime = $calculatedRuntime;

        // 5. Filter by status if needed
        if ($status) {
            $plans = $plans->filter(function ($plan) use ($status) {
                $j = $plan->job_data;
                return $j && strtolower($j->status) === strtolower($status);
            })->values();
        }

        // 6. Sort by row_no to match PPC schedule order (like Input Harian)
        $plans = $plans->sortBy(fn ($p) => (int) ($p->row_no ?? 0))->values();

        return compact('stats', 'dailyTrend', 'achievement', 'downtimeAgg', 'plans', 'totalPlanTarget');
    }

    private function timelineData($dateFrom, $dateTo, $line)
    {
        $logsQuery = ProductionLog::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('jobMaster');

        $downtimesQuery = Downtime::whereBetween('start_time', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('jobMaster');

        if ($line) {
            $logsQuery->whereHas('jobMaster', fn($q) => $q->where('line', $line));
            $downtimesQuery->whereHas('jobMaster', fn($q) => $q->where('line', $line));
        }

        $logs = $logsQuery->orderBy('created_at')->get();
        $downtimes = $downtimesQuery->orderBy('start_time')->get();

        return compact('logs', 'downtimes');
    }

    private function historyData(Request $request, $dateFrom, $dateTo, $line, $shift)
    {
        $query = DailyProduction::with(['jobMaster.downtimes' => function ($q) use ($dateFrom, $dateTo) {
                $q->select('job_master_id', 'jenis_downtime', 'duration_seconds', 'start_time')
                  ->whereBetween('start_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
            }])->whereBetween('work_date', [$dateFrom, $dateTo]);

        if ($line) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $query->whereRaw("REPLACE(REPLACE(UPPER(TRIM(`line`)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"]);
        }
        if ($shift) $query->where('shift', 'like', '%'.$shift.'%');

        $records = $query->orderBy('work_date', 'desc')->orderBy('line')->paginate(20)->withQueryString();

        return compact('records');
    }

    private function moreDetailData($dateFrom, $dateTo, $line, $shift)
    {
        $dpQuery = DailyProduction::whereBetween('work_date', [$dateFrom, $dateTo]);
        if ($line) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));
            $dpQuery->whereRaw("REPLACE(REPLACE(UPPER(TRIM(`line`)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"]);
        }
        if ($shift) $dpQuery->where('shift', 'like', '%'.$shift.'%');

        $stats = (clone $dpQuery)->selectRaw('
            COALESCE(SUM(actual_ok + actual_repair + actual_reject), 0) as total_qty,
            COALESCE(SUM(actual_ok), 0) as total_ok,
            COALESCE(SUM(actual_repair), 0) as total_repair,
            COALESCE(SUM(actual_reject), 0) as total_reject,
            COALESCE(SUM(runtime_seconds), 0) as total_runtime,
            COALESCE(SUM(downtime_seconds), 0) as total_downtime,
            COALESCE(SUM(target_qty), 0) as total_target,
            COUNT(DISTINCT job_master_id) as total_jobs,
            COUNT(DISTINCT CONCAT(work_date, line)) as total_lines
        ')->first();

        $achievement = $stats->total_target > 0 ? round(($stats->total_ok / $stats->total_target) * 100, 1) : 0;

        $dailyRecords = (clone $dpQuery)
            ->orderBy('work_date', 'desc')
            ->orderBy('line')
            ->orderBy('shift')
            ->get();

        $jobIds = (clone $dpQuery)->distinct()->pluck('job_master_id')->filter();

        $allDowntimes = collect();
        $allLogs = collect();
        $allRepairRejects = collect();
        $allDandoris = collect();

        if ($jobIds->isNotEmpty()) {
            $allDowntimes = Downtime::whereIn('job_master_id', $jobIds)
                ->whereBetween('start_time', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->orderBy('start_time')
                ->get();

            $allLogs = ProductionLog::whereIn('job_master_id', $jobIds)
                ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->orderBy('created_at')
                ->get();

            $allRepairRejects = \App\Models\RepairRejectLog::whereIn('job_master_id', $jobIds)
                ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->with('creator')
                ->orderBy('created_at')
                ->get();

            $allDandoris = \App\Models\Dandori::whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->when($line, fn($q) => $q->where('line', $line))
                ->orderBy('created_at')
                ->get();
        }

        $downtimeAgg = (object) [
            'idle_sec' => $allDowntimes->whereIn('jenis_downtime', ['idle','idle time'])->sum('duration_seconds'),
            'repair_sec' => $allDowntimes->where('jenis_downtime','repair')->sum('duration_seconds'),
            'dandori_sec' => $allDowntimes->where('jenis_downtime','dandori')->sum('duration_seconds'),
            'tryout_sec' => $allDowntimes->whereIn('jenis_downtime', ['try out','tryout'])->sum('duration_seconds'),
            'break_sec' => $allDowntimes->whereIn('jenis_downtime', ['break time','break'])->sum('duration_seconds'),
        ];

        return compact(
            'stats', 'achievement', 'dailyRecords', 'allDowntimes',
            'allLogs', 'allRepairRejects', 'allDandoris', 'downtimeAgg'
        );
    }

    private function lifecycleData()
    {
        $base = function ($q) {
            return $q->withSum('productionLogs as total_ok', 'ok_qty')
                     ->withSum('productionLogs as total_repair', 'repair_qty')
                     ->withSum('productionLogs as total_reject', 'reject_qty');
        };

        $active = $base(JobMaster::whereIn('status', ['running', 'idle', 'pending']))->orderBy('created_at', 'desc')->get();
        $completed = $base(JobMaster::where('status', 'complete')->where('created_at', '>=', Carbon::now()->subMonths(3)))->orderBy('created_at', 'desc')->get();
        $archived = $base(JobMaster::where('status', 'complete')->where('created_at', '<', Carbon::now()->subMonths(3)))->orderBy('created_at', 'desc')->paginate(20);

        return compact('active', 'completed', 'archived');
    }

    public function jobDetail($id)
    {
        $job = JobMaster::with([
            'dailyProduction',
            'downtimes' => fn($q) => $q->orderBy('start_time'),
            'productionLogs' => fn($q) => $q->orderBy('created_at', 'desc')->take(100),
            'repairRejects.images',
            'repairRejects.creator',
        ])->findOrFail($id);

        $sessions = ProductionSession::where('job_master_id', $id)
            ->orderBy('start_time')
            ->get();

        $dandoriSessions = DandoriSession::where('job_master_id', $id)
            ->with('groups.details')
            ->orderBy('start_time')
            ->get();

        $dandoris = \App\Models\Dandori::where('next_job_id', $id)
            ->where('jenis_dandori', '1st_check')
            ->orderBy('start_time')
            ->get();

        if (request()->wantsJson()) {
            return response()->json(compact('job', 'sessions', 'dandoriSessions', 'dandoris'));
        }

        return view('analytics._job_detail', compact('job', 'sessions', 'dandoriSessions', 'dandoris'));
    }
}
