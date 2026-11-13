<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Services\DashboardRealtimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = (clone $startDate)->endOfMonth();

        // 7 kategori aktual dari Input Harian
        $validCategories = ['mesin', 'dies', 'material', 'logistic', 'try out', 'produksi', 'others'];

        $types = Downtime::whereNotNull('jenis_downtime')
            ->whereNotNull('start_time')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->whereNotIn(DB::raw('LOWER(TRIM(jenis_downtime))'), ['dandori', 'idle', 'idle time', 'break time', 'break_time'])
            ->selectRaw("
                LOWER(TRIM(jenis_downtime)) as category_clean,
                COALESCE(SUM(duration_seconds), 0) as total_seconds,
                COUNT(*) as count
            ")
            ->groupBy(DB::raw('LOWER(TRIM(jenis_downtime))'))
            ->orderByDesc('total_seconds')
            ->get()
            ->filter(function ($r) use ($validCategories) {
                return in_array($r->category_clean, $validCategories, true);
            })
            ->values();

        $totalSeconds = (float) $types->sum('total_seconds');
        $totalMinutes = round($totalSeconds / 60, 1);
        $totalEvents = (int) $types->sum('count');

        $labels = [];
        $data = [];
        $cumulative = [];
        $percentages = [];
        $counts = [];
        $topCategories = [];
        $items = [];
        $runningSumSeconds = 0;

        foreach ($types as $item) {
            $seconds = (float) $item->total_seconds;
            $minutes = round($seconds / 60, 1);
            $prevCumPct = $totalSeconds > 0 ? round(($runningSumSeconds / $totalSeconds) * 100, 1) : 0.0;
            $runningSumSeconds += $seconds;

            $pct = $totalSeconds > 0 ? round(($seconds / $totalSeconds) * 100, 1) : 0.0;
            $cumPct = $totalSeconds > 0 ? round(($runningSumSeconds / $totalSeconds) * 100, 1) : 0.0;
            if ($cumPct > 100) $cumPct = 100.0;

            // Format label menjadi Title Case konsisten dengan Input Harian
            $label = ucwords($item->category_clean);

            // Kategori Pareto 80/20: kategori yang menyumbang hingga batas kumulatif 80% (Vital Few)
            $inPareto80 = ($prevCumPct < 80.0 || empty($topCategories));
            if ($inPareto80) {
                $topCategories[] = $label;
            }

            $labels[] = $label;
            $data[] = $minutes;
            $percentages[] = $pct;
            $cumulative[] = $cumPct;
            $counts[] = (int) $item->count;

            $items[] = [
                'category' => $label,
                'minutes' => $minutes,
                'percentage' => $pct,
                'cumulative' => $cumPct,
                'count' => (int) $item->count,
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

    public function gsph(Request $request)
    {
        $date = $request->input('date');
        $shift = (int) $request->input('shift', 1);

        // Default: pakai tanggal schedule terakhir yang punya job, bukan hardcode hari ini
        if (! $date) {
            $latestPlanDate = ProductionPlan::activePpcRevision()->where('row_type', 'job')->max('plan_date');
            $date = $latestPlanDate
                ? Carbon::parse($latestPlanDate)->toDateString()
                : Carbon::now()->toDateString();
        }

        $lines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name')->toArray();

        $dashService = app(DashboardRealtimeService::class);

        $labels = [];
        $plan = [];
        $actual = [];

        foreach ($lines as $lineName) {
            $metrics = $dashService->getLineMetrics($lineName, $date, $shift);
            $gsphEntry = collect($metrics['kpi'])->firstWhere('desc', 'GSPH');

            $labels[] = $lineName;
            $plan[] = $gsphEntry ? (float) $gsphEntry['plan'] : 0;
            $actual[] = $gsphEntry ? (float) $gsphEntry['actual'] : 0;
        }

        return response()->json(compact('labels', 'plan', 'actual') + ['date' => $date]);
    }
}
