<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\JobMaster;
use App\Models\LineMaster;
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

        $types = Downtime::whereNotNull('jenis_downtime')
            ->whereNotNull('start_time')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->selectRaw("
                jenis_downtime,
                COALESCE(SUM(duration_seconds), 0) as total_seconds,
                COUNT(*) as count
            ")
            ->groupBy('jenis_downtime')
            ->orderByDesc('total_seconds')
            ->get();

        $labels = $types->pluck('jenis_downtime');
        $totalSeconds = $types->sum('total_seconds');
        $data = $types->map(fn($r) => round($r->total_seconds / 60, 1));
        $percentages = $types->map(fn($r) => $totalSeconds > 0 ? round(($r->total_seconds / $totalSeconds) * 100, 1) : 0);
        $counts = $types->pluck('count');

        $colors = ['#3b82f6', '#ef4444', '#eab308', '#22c55e', '#a855f7', '#f97316', '#06b6d4'];

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                ],
            ],
            'percentages' => $percentages,
            'counts' => $counts,
            'totalMinutes' => round($totalSeconds / 60, 1),
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
        $date = $request->input('date', Carbon::now()->toDateString());
        $shift = (int) $request->input('shift', 1);

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

        return response()->json(compact('labels', 'plan', 'actual'));
    }
}
