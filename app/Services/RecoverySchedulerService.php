<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Models\MasterBreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverySchedulerService
{
    /**
     * Schedule approved recovery items into production_plans for a given date/shift/press.
     * Priority 1: Approved recovery items (oldest first)
     * Priority 2: New PPC items (by row_no)
     * Items that don't fit remain in recovery pool (status 'approved').
     *
     * Returns stats about what was scheduled vs what remained.
     */
    public function scheduleForShift(string $date, string $shiftName, string $pressName, ?int $lineMasterId = null): array
    {
        $stats = [
            'recovery_scheduled' => 0,
            'recovery_remaining' => 0,
            'recovery_total'     => 0,
            'new_scheduled'      => 0,
            'new_remaining'      => 0,
            'new_total'          => 0,
        ];

        // 1. Determine shift boundaries & capacity
        $shiftConfig = $this->getShiftConfig($shiftName);
        $shiftStartMins = $shiftConfig['start'];
        $shiftEndMins = $shiftConfig['end'];

        // Handle night shift crossing midnight
        if ($shiftEndMins <= $shiftStartMins) {
            $shiftEndMins += 1440;
        }

        $breakMinutes = $this->getBreakMinutes($date, $shiftName);
        $capacityMinutes = ($shiftEndMins - $shiftStartMins) - $breakMinutes;

        if ($capacityMinutes <= 0) {
            Log::warning("No capacity for {$pressName} on {$date} {$shiftName}");
            return $stats;
        }

        // 2. Get approved recovery items for this press (oldest first — FIFO)
        $recoveryItems = RecoveryItem::approved()
            ->where('press_name', $pressName)
            ->whereNotExists(function ($q) use ($date, $pressName) {
                $q->selectRaw(1)
                  ->from('production_plans')
                  ->whereColumn('production_plans.recovery_id', 'recovery_items.id')
                  ->whereDate('production_plans.plan_date', $date)
                  ->where('production_plans.press_name', $pressName);
            })
            ->orderBy('source_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $stats['recovery_total'] = $recoveryItems->count();
        $remaining = $capacityMinutes;

        // 3. Priority 1: Insert approved recovery items
        foreach ($recoveryItems as $item) {
            $itemMinutes = $this->calculateItemMinutes($item);
            if ($itemMinutes <= $remaining) {
                $remaining -= $itemMinutes;
                $stats['recovery_scheduled']++;
            } else {
                $stats['recovery_remaining']++;
            }
        }

        // 4. Priority 2: Get new PPC items not yet scheduled
        $newPlans = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('press_name', $pressName)
            ->where('row_type', 'job')
            ->whereNull('recovery_id')
            ->whereNull('start_time')
            ->whereNull('finish_time')
            ->orderBy('row_no', 'asc')
            ->get();

        $stats['new_total'] = $newPlans->count();

        foreach ($newPlans as $plan) {
            $ctDetik = (float)($plan->ct_detik ?? 0);
            $dct = (float)($plan->dct ?? 0);
            $planQty = (float)($plan->plan ?? 0);

            $processTime = $ctDetik > 0 ? (int)ceil(($ctDetik * $planQty) / 60.0) : 0;
            $itemMinutes = $processTime + $dct;

            if ($itemMinutes <= $remaining) {
                $remaining -= $itemMinutes;
                $stats['new_scheduled']++;
            } else {
                $stats['new_remaining']++;
            }
        }

        $stats['capacity_minutes'] = $capacityMinutes;
        $stats['used_minutes'] = $capacityMinutes - $remaining;

        Log::info("Scheduler for {$pressName} on {$date} {$shiftName}: capacity={$capacityMinutes}min, recovery scheduled={$stats['recovery_scheduled']}/{$stats['recovery_total']}, new scheduled={$stats['new_scheduled']}/{$stats['new_total']}");

        return $stats;
    }

    /**
     * Calculate how many minutes this recovery item needs.
     */
    private function calculateItemMinutes($item): float
    {
        $ctDetik = (float)($item->ct_detik ?? 0);
        $dct = (float)($item->dct ?? 0);
        $qty = $item->recovery_qty > 0
            ? (float)$item->recovery_qty
            : (float)($item->plan_qty ?? 0);

        if ($ctDetik <= 0) return 0;

        $processTime = (int)ceil(($ctDetik * $qty) / 60.0);
        return $processTime + $dct;
    }

    /**
     * Get shift start and end in minutes from midnight.
     */
    private function getShiftConfig(string $shiftName): array
    {
        $config = config("shift.{$shiftName}");
        if ($config) {
            return [
                'start' => MasterBreakTime::timeToMinutes($config['start']),
                'end'   => MasterBreakTime::timeToMinutes($config['end']),
            ];
        }

        // Fallback for unrecognized shift names
        if (str_contains(strtolower($shiftName), 'malam')) {
            return ['start' => 21 * 60, 'end' => 7 * 60 + 30];
        }
        return ['start' => 7 * 60 + 30, 'end' => 21 * 60];
    }

    /**
     * Get total break minutes for a date/shift.
     * Uses the same break resolution engine as TimelineGenerationService.
     */
    private function getBreakMinutes(string $date, string $shiftName): int
    {
        try {
            $engine = app(TimelineGenerationService::class);
            $breaks = $engine->resolveBreakWindows($date, $shiftName);

            $total = 0;
            foreach ($breaks as $b) {
                $start = MasterBreakTime::timeToMinutes($b['start']);
                $end = MasterBreakTime::timeToMinutes($b['finish']);
                $total += ($end - $start);
            }

            return $total;
        } catch (\Throwable $e) {
            return 0;
        }
    }

}
