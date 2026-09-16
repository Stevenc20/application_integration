<?php

namespace App\Services;

use App\Models\ProductionPlan;
use Illuminate\Support\Collection;

/**
 * Single source of truth for manufacturing KPI formulas (PPC → Input Harian → LKH).
 */
class ProductionMetricsService
{
    public static function calculateProcessTime($ct, $plan): int
    {
        if (!$ct || !$plan) {
            return 0;
        }

        return (int) ceil(((float) $ct * (int) $plan) / 60.0);
    }

    public static function processTimeMinutes(float $ctSeconds, int $qty): float
    {
        return self::calculateProcessTime($ctSeconds, $qty);
    }

    public static function planCt(float $ctDetik, float $planTpt, int $planQty): float
    {
        if ($ctDetik > 0) {
            return $ctDetik;
        }

        if ($planQty > 0 && $planTpt > 0) {
            return (round($planTpt) * 60.0) / $planQty;
        }

        return 0.0;
    }

    /**
     * Actual TPT = Process + Dandori + Downtime only (break/idle/work excluded).
     */
    public static function actualTpt(float $processTime, float $dandori, float $downtime): float
    {
        return max(0.0, $processTime + $dandori + $downtime);
    }

    /**
     * Plan TPT from PPC columns (process + reg_active + dct), dynamically calculated to prevent stale values.
     */
    public static function planTptMinutes(ProductionPlan $plan): float
    {
        $process = self::processTimeMinutes((float) ($plan->ct_detik ?? 0), (int) ($plan->plan ?? $plan->target_qty ?? 0));
        return max(0.0, $process + (float) ($plan->dct ?? 0));
    }

    /**
     * Plan GSPH — render ONLY stored PPC value (no recalculation).
     */
    public static function planGsphStored(ProductionPlan $plan): int
    {
        return (int) ($plan->gsph_item ?? 0);
    }

    /**
     * Plan TPT — render ONLY stored PPC column.
     */
    public static function planTptStored(ProductionPlan $plan): float
    {
        return max(0.0, (float) ($plan->tpt ?? 0));
    }

    /** @deprecated Use planGsphStored — kept for internal regenerate when Excel row has no GSPH */
    public static function planGsphFromPlan(ProductionPlan $plan): int
    {
        $qty = (int) ($plan->plan ?? $plan->target_qty ?? 0);
        return self::gsph($qty, self::planTptMinutes($plan));
    }

    /**
     * Shift/footer GSPH = TOTAL PLAN / (TOTAL TPT / 60).
     */
    public static function shiftPlanGsph(int $totalPlanQty, float $totalPlanTptMinutes): int
    {
        return self::gsph($totalPlanQty, $totalPlanTptMinutes);
    }

    public static function actualCt(float $tptMinutes, int $actualQty): float
    {
        if ($actualQty <= 0 || $tptMinutes <= 0) {
            return 0.0;
        }

        return ($tptMinutes * 60.0) / $actualQty;
    }

    public static function gsph(int $qty, float $tptMinutes): int
    {
        if ($qty <= 0 || $tptMinutes <= 0) {
            return 0;
        }

        return (int) round($qty / ($tptMinutes / 60.0));
    }

    public static function balance(int $plan, int $ok, int $repair, int $reject): int
    {
        return $plan - $ok - $repair - $reject;
    }

    public static function achievement(int $ok, int $plan): float
    {
        if ($plan <= 0) {
            return 0.0;
        }

        return ($ok / $plan) * 100.0;
    }

    /**
     * Sum downtime minutes by category (excludes dandori, idle, break, quality).
     *
     * @return array{machine: float, material: float, logistic: float, production: float, ubp: float, total: float}
     */
    public static function downtimeBreakdown(Collection $downtimes): array
    {
        $breakdown = [
            'machine' => 0.0,
            'material' => 0.0,
            'logistic' => 0.0,
            'production' => 0.0,
            'dies' => 0.0,
            'ubp' => 0.0,
            'total' => 0.0,
        ];

        $now = \Carbon\Carbon::now();

        foreach ($downtimes as $dt) {
            $type = strtoupper($dt->jenis_downtime ?? '');
            if (self::isExcludedDowntimeType($type)) {
                continue;
            }

            $dur = self::downtimeDurationSeconds($dt, $now) / 60.0;
            $breakdown['total'] += $dur;

            if (str_contains($type, 'DIES')) {
                $breakdown['dies'] += $dur;
            } elseif (str_contains($type, 'MACHINE') || str_contains($type, 'MACH') || str_contains($type, 'MESIN')) {
                $breakdown['machine'] += $dur;
            } elseif (str_contains($type, 'MATERIAL') || str_contains($type, 'MAT')) {
                $breakdown['material'] += $dur;
            } elseif (str_contains($type, 'LOGISTIC') || str_contains($type, 'LOG')) {
                $breakdown['logistic'] += $dur;
            } elseif (str_contains($type, 'UBP')) {
                $breakdown['ubp'] += $dur;
            } elseif (str_contains($type, 'PRODUCTION') || str_contains($type, 'PROD')) {
                $breakdown['production'] += $dur;
            } else {
                $breakdown['production'] += $dur;
            }
        }

        return $breakdown;
    }

    public static function dandoriMinutes(Collection $downtimes): float
    {
        $total = 0.0;
        $now = \Carbon\Carbon::now();

        foreach ($downtimes->filter(fn ($dt) => str_contains(strtolower($dt->jenis_downtime ?? ''), 'dandori')) as $dt) {
            $total += self::downtimeDurationSeconds($dt, $now);
        }

        return max(0.0, $total / 60.0);
    }

    /**
     * Calculate duration_seconds for a downtime record, handling open/running records.
     */
    private static function downtimeDurationSeconds($dt, \Carbon\Carbon $now): float
    {
        if (!empty($dt->duration_seconds)) {
            return (float) $dt->duration_seconds;
        }

        if ($dt->finish_time) {
            return (float) abs(\Carbon\Carbon::parse($dt->finish_time)->diffInSeconds(\Carbon\Carbon::parse($dt->start_time)));
        }

        // Open/running downtime: calculate from start_time to now
        if ($dt->start_time) {
            return (float) abs($now->diffInSeconds(\Carbon\Carbon::parse($dt->start_time)));
        }

        return 0.0;
    }

    /**
     * Calculate OEE with Six Big Losses breakdown.
     *
     * Availability Loss = Downtime (breakdown) + Dandori (setup/changeover)
     * Performance Loss = Idle (idling/minor stops) + Speed Loss (operatingTime - pressTime)
     * Quality Loss     = Reject + Repair
     *
     * @param  float  $workTimeDuration  Total elapsed time (process + dandori + downtime + idle)
     * @param  float  $breakDuration     Break time deducted from planned production time
     * @param  float  $downtime          Unplanned downtime minutes (breakdown)
     * @param  float  $dandori           Planned setup/changeover minutes
     * @param  float  $pressTime         Ideal press time = (totalPieces × idealCycleTime) / 60
     * @param  int    $totalStroke       Total pieces produced (good + repair + reject)
     * @param  int    $actualGood        Good quality pieces
     * @return array{availability: float, performance: float, quality: float, oee: float, planned_production_time: float, operating_time: float, availability_loss: float}
     */
    public static function calculateOee(
        float $workTimeDuration,
        float $breakDuration,
        float $downtime,
        float $dandori,
        float $pressTime,
        int $totalStroke,
        int $actualGood
    ): array {
        $plannedProductionTime = max($workTimeDuration - $breakDuration, 0.0);

        $availabilityLoss = $downtime + $dandori;
        $operatingTime = max($plannedProductionTime - $availabilityLoss, 0.0);

        $availability = $plannedProductionTime > 0 ? (float) ($operatingTime / $plannedProductionTime) : 0.0;
        $performance = $operatingTime > 0 ? (float) ($pressTime / $operatingTime) : 0.0;
        $quality = $totalStroke > 0 ? (float) ($actualGood / $totalStroke) : 0.0;

        $availability = min(max($availability, 0.0), 1.0);
        $performance = min(max($performance, 0.0), 1.0);
        $quality = min(max($quality, 0.0), 1.0);

        $oee = $availability * $performance * $quality * 100.0;

        return [
            'availability' => $availability,
            'performance' => $performance,
            'quality' => $quality,
            'oee' => $oee,
            'planned_production_time' => $plannedProductionTime,
            'operating_time' => $operatingTime,
            'availability_loss' => $availabilityLoss,
        ];
    }

    public static function isExcludedDowntimeType(string $type): bool
    {
        return str_contains($type, 'DANDORI')
            || str_contains($type, 'IDLE')
            || str_contains($type, 'BREAK')
            || str_contains($type, 'QCHECK')
            || str_contains($type, 'Q CHECK')
            || str_contains($type, 'QUALITY');
    }
}
