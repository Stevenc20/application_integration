<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\RepairRejectLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataMiningService
{
    /**
     * Calculate Trend analysis (Efficiency, Reject Rate, or Downtime).
     */
    public function calculateTrend(string $metric, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();

        if ($metric === 'downtime') {
            $rawDaily = Downtime::select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('COALESCE(SUM(duration_seconds), 0) / 60.0 as value')
            )
            ->whereDate('start_time', '>=', $startDate)
            ->whereNotIn('jenis_downtime', ['dandori', 'idle', 'idle time', 'break time'])
            ->groupBy(DB::raw('DATE(start_time)'))
            ->orderBy('date', 'asc')
            ->get();
        } elseif ($metric === 'reject') {
            $rawDaily = DailyProduction::select(
                'work_date as date',
                DB::raw('CASE WHEN SUM(actual_ok + actual_repair + actual_reject) > 0 THEN (SUM(actual_reject) * 100.0 / SUM(actual_ok + actual_repair + actual_reject)) ELSE 0 END as value')
            )
            ->whereDate('work_date', '>=', $startDate)
            ->groupBy('work_date')
            ->orderBy('date', 'asc')
            ->get();
        } else {
            // Efficiency
            $rawDaily = DailyProduction::select(
                'work_date as date',
                DB::raw('COALESCE(AVG(NULLIF(efficiency, 0)), 0) as value')
            )
            ->whereDate('work_date', '>=', $startDate)
            ->groupBy('work_date')
            ->orderBy('date', 'asc')
            ->get();
        }

        if ($rawDaily->isEmpty()) {
            return [
                'summary' => [
                    'current' => 0,
                    'average' => 0,
                    'trend_direction' => 'stable',
                    'trend_change_pct' => 0,
                    'data_points' => 0,
                ],
                'daily' => [],
            ];
        }

        $dailyData = [];
        $values = [];
        $count = $rawDaily->count();

        foreach ($rawDaily as $idx => $row) {
            $val = round((float) $row->value, 1);
            $values[] = $val;

            // Calculate 7-day Simple Moving Average (SMA-7)
            $slice = array_slice($values, max(0, $idx - 6), min(7, $idx + 1));
            $sma7 = count($slice) > 0 ? round(array_sum($slice) / count($slice), 1) : $val;

            $item = ['date' => $row->date, 'sma_7' => $sma7];
            if ($metric === 'efficiency') {
                $item['efficiency'] = $val;
            } elseif ($metric === 'reject') {
                $item['reject_rate'] = $val;
            } else {
                $item['total_minutes'] = $val;
            }

            $dailyData[] = $item;
        }

        $avg = count($values) > 0 ? round(array_sum($values) / count($values), 1) : 0;
        $current = end($values);
        $first = reset($values);

        $changePct = ($first > 0) ? round((($current - $first) / $first) * 100, 1) : 0;
        $dir = 'stable';
        if ($changePct > 2) {
            $dir = 'up';
        } elseif ($changePct < -2) {
            $dir = 'down';
        }

        return [
            'summary' => [
                'current' => $current,
                'average' => $avg,
                'trend_direction' => $dir,
                'trend_change_pct' => $changePct,
                'data_points' => $count,
            ],
            'daily' => $dailyData,
        ];
    }

    /**
     * Detect Anomalies using Z-Score statistical analysis.
     */
    public function detectAnomalies(int $days = 30, float $threshold = 2.0): array
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();

        // 1. Efficiency Anomalies
        $effData = DailyProduction::with('jobMaster')
            ->whereDate('work_date', '>=', $startDate)
            ->where('efficiency', '>', 0)
            ->get();

        $effAnomalies = $this->calculateZScoreAnomalies(
            $effData,
            'efficiency',
            'efficiency',
            $threshold,
            fn($r) => [
                'date' => $r->work_date,
                'line' => $r->line ?? $r->jobMaster?->line ?? '-',
                'shift' => $r->shift ?? '-',
                'metric' => 'efficiency',
                'value' => round((float) $r->efficiency, 1) . '%',
                'detail' => "Target: {$r->target_qty} Pcs | Actual: {$r->actual_ok} Pcs",
            ]
        );

        // 2. Reject Anomalies
        $rejectData = DailyProduction::with('jobMaster')
            ->whereDate('work_date', '>=', $startDate)
            ->where('actual_reject', '>', 0)
            ->get();

        $rejectAnomalies = $this->calculateZScoreAnomalies(
            $rejectData,
            'actual_reject',
            'reject',
            $threshold,
            fn($r) => [
                'date' => $r->work_date,
                'line' => $r->line ?? $r->jobMaster?->line ?? '-',
                'shift' => $r->shift ?? '-',
                'metric' => 'reject',
                'value' => (int) $r->actual_reject . ' Pcs',
                'detail' => "Reject tinggi pada job {$r->jobMaster?->job_name}",
            ]
        );

        // 3. Downtime Anomalies
        $dtData = Downtime::with('jobMaster')
            ->whereDate('start_time', '>=', $startDate)
            ->whereNotIn('jenis_downtime', ['dandori', 'idle', 'idle time', 'break time'])
            ->get();

        $dtAnomalies = $this->calculateZScoreAnomalies(
            $dtData,
            function($dt) { return ($dt->duration_seconds ?? 0) / 60.0; },
            'downtime',
            $threshold,
            fn($dt) => [
                'date' => Carbon::parse($dt->start_time)->toDateString(),
                'line' => $dt->jobMaster?->line ?? '-',
                'shift' => '-',
                'metric' => 'downtime',
                'value' => round(($dt->duration_seconds ?? 0) / 60.0, 1) . ' Min',
                'detail' => "Hambatan {$dt->jenis_downtime}: " . ($dt->problem ?: 'Unspecified'),
            ]
        );

        return [
            'anomalies' => [
                'efficiency' => $effAnomalies,
                'reject'     => $rejectAnomalies,
                'downtime'   => $dtAnomalies,
            ],
        ];
    }

    /**
     * Calculate Pareto 80/20 analysis (Downtime or Defect).
     */
    public function calculatePareto(string $type, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();

        if ($type === 'defect') {
            $rows = DB::table('repair_reject_logs')
                ->select(
                    DB::raw('COALESCE(defect_name, area_problem, "Lainnya") as category'),
                    DB::raw('SUM(qty_a + qty_b) as total')
                )
                ->whereDate('created_at', '>=', $startDate)
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();
        } else {
            // Downtime Pareto
            $rows = DB::table('downtimes')
                ->select(
                    DB::raw('COALESCE(NULLIF(jenis_downtime, ""), "Lainnya") as category'),
                    DB::raw('ROUND(SUM(duration_seconds) / 60.0, 1) as total')
                )
                ->whereDate('start_time', '>=', $startDate)
                ->whereNotIn('jenis_downtime', ['dandori', 'idle', 'idle time', 'break time'])
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();
        }

        $grandTotal = $rows->sum('total');
        if ($grandTotal <= 0) {
            return [
                'labels' => [],
                'data' => [],
                'cumulative' => [],
                'top_categories' => [],
            ];
        }

        $labels = [];
        $data = [];
        $cumulative = [];
        $topCategories = [];
        $runningSum = 0;

        foreach ($rows as $row) {
            $val = (float) $row->total;
            if ($val <= 0) continue;

            $runningSum += $val;
            $cumPct = round(($runningSum / $grandTotal) * 100, 1);

            $labels[] = strtoupper((string) $row->category);
            $data[] = $val;
            $cumulative[] = $cumPct;

            if ($cumPct <= 85 || count($topCategories) === 0) {
                $topCategories[] = strtoupper((string) $row->category);
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'cumulative' => $cumulative,
            'top_categories' => $topCategories,
        ];
    }

    /**
     * Get summary aggregate for Data Mining dashboard.
     */
    public function getSummary(int $days = 30, float $threshold = 2.0): array
    {
        $anomalies = $this->detectAnomalies($days, $threshold);
        $effTrend = $this->calculateTrend('efficiency', $days);
        $rejTrend = $this->calculateTrend('reject', $days);
        $dtTrend  = $this->calculateTrend('downtime', $days);
        $dtPareto = $this->calculatePareto('downtime', $days);
        $defPareto = $this->calculatePareto('defect', $days);

        $totalAnomalies = count($anomalies['anomalies']['efficiency'])
            + count($anomalies['anomalies']['reject'])
            + count($anomalies['anomalies']['downtime']);

        return [
            'total_anomalies' => $totalAnomalies,
            'anomalies' => $anomalies['anomalies'],
            'efficiency' => $effTrend,
            'reject' => $rejTrend,
            'downtime' => $dtTrend,
            'pareto' => [
                'downtime' => $dtPareto,
                'defect' => $defPareto,
            ],
        ];
    }

    /**
     * Helper to compute Z-Score anomalies for a dataset.
     */
    private function calculateZScoreAnomalies($collection, $valKey, string $metricName, float $threshold, callable $formatter): array
    {
        if ($collection->isEmpty()) return [];

        $values = $collection->map(function($item) use ($valKey) {
            return is_callable($valKey) ? $valKey($item) : (float) ($item->{$valKey} ?? 0);
        })->toArray();

        $count = count($values);
        if ($count < 3) return [];

        $mean = array_sum($values) / $count;
        $variance = array_reduce($values, function($acc, $val) use ($mean) {
            return $acc + pow($val - $mean, 2);
        }, 0.0) / $count;

        $stdDev = sqrt($variance);
        if ($stdDev <= 0) return [];

        $anomalies = [];
        foreach ($collection as $item) {
            $val = is_callable($valKey) ? $valKey($item) : (float) ($item->{$valKey} ?? 0);
            $zScore = ($val - $mean) / $stdDev;

            if (abs($zScore) >= $threshold) {
                $formatted = $formatter($item);
                $formatted['z_score'] = round($zScore, 2);
                $formatted['status'] = $zScore < 0 ? 'anomali_negatif' : 'anomali_positif';
                $anomalies[] = $formatted;
            }
        }

        return array_slice($anomalies, 0, 20);
    }
}
