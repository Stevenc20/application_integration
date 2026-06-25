<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CutOffService
{
    /**
     * Process cut-off for a given date and shift.
     * Finds production plans where ok < plan (unfinished items)
     * and creates pending RecoveryItems for them.
     */
    public function processCutOff(string $date, string $shiftName): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'total_unfinished' => 0];

        DB::transaction(function () use ($date, $shiftName, &$stats) {
            $unfinishedPlans = ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $shiftName)
                ->where('row_type', 'job')
                ->where(function ($q) {
                    $q->whereRaw('COALESCE(ok, 0) < COALESCE(plan, 0)')
                      ->orWhereNull('ok');
                })
                ->get();

            $stats['total_unfinished'] = $unfinishedPlans->count();

            foreach ($unfinishedPlans as $plan) {
                $actualQty = (float)($plan->ok ?? 0);
                $planQty = (float)($plan->plan ?? 0);
                $recoveryQty = max(0, $planQty - $actualQty);

                if ($recoveryQty <= 0) {
                    $stats['skipped']++;
                    continue;
                }

                $ctDetik = (float)($plan->ct_detik ?? 0);
                $dct = (float)($plan->dct ?? 0);
                $durationMinutes = $ctDetik > 0
                    ? (int)ceil(($ctDetik * $recoveryQty) / 60.0) + $dct
                    : 0;

                $schedule = RecoverySchedule::firstOrCreate(
                    [
                        'plan_date'  => $date,
                        'shift_name' => $shiftName,
                        'press_name' => $plan->press_name,
                    ],
                    [
                        'status' => 'waiting_approval',
                    ]
                );

                RecoveryItem::firstOrCreate(
                    [
                        'recovery_schedule_id' => $schedule->id,
                        'job_no'               => trim($plan->job_no ?? ''),
                        'press_name'           => $plan->press_name,
                    ],
                    [
                        'production_plan_id' => $plan->id,
                        'job_master'         => $plan->job_master ?? trim($plan->job_no ?? ''),
                        'plan_qty'           => $planQty,
                        'ok'                 => $actualQty,
                        'repair'             => (float)($plan->repair ?? 0),
                        'reject'             => (float)($plan->reject ?? 0),
                        'ct_detik'           => $ctDetik,
                        'dct'                => $dct,
                        'reg_active'         => (float)($plan->reg_active ?? 0),
                        'total_mesin'        => (int)($plan->total_mesin ?? 1),
                        'status'             => 'waiting_approval',
                        'original_date'      => $date,
                        'original_shift_name' => $shiftName,
                        'source_date'        => $date,
                        'source_shift'       => $shiftName,
                        'actual_qty'         => $actualQty,
                        'recovery_qty'       => $recoveryQty,
                        'duration_minutes'   => $durationMinutes,
                        'queued_at'          => now(),
                    ]
                );

                $stats['created']++;
            }
        });

        Log::info("CutOff processed for {$date} {$shiftName}: {$stats['created']} recovery items created, {$stats['skipped']} skipped, {$stats['total_unfinished']} unfinished plans");

        return $stats;
    }

    /**
     * Determine which shift should be cut off based on current time.
     * Reads shift end times from config/shift.php.
     */
    public function getShiftToCutOff(): ?array
    {
        $now = Carbon::now();
        $current = $now->format('H:i');

        $shifts = ['Shift Pagi', 'Shift Malam'];
        foreach ($shifts as $shiftName) {
            $config = config("shift.{$shiftName}");
            if (!$config) continue;

            $endTime = $config['end'];
            $endCarbon = Carbon::createFromTimeString($endTime);
            $windowEnd = $endCarbon->copy()->addMinutes(15)->format('H:i');

            if ($current >= $endTime && $current < $windowEnd) {
                $date = $shiftName === 'Shift Malam'
                    ? $now->copy()->subDay()->format('Y-m-d')
                    : $now->format('Y-m-d');
                return [
                    'date'  => $date,
                    'shift' => $shiftName,
                ];
            }
        }

        return null;
    }

    /**
     * Check if there are pending recovery items for a given press.
     */
    public function getPendingRecoveryForPress(string $pressName, ?string $date = null): int
    {
        $query = RecoveryItem::pending()->where('press_name', $pressName);

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereDate('source_date', $date)
                  ->orWhereDate('original_date', $date);
            });
        }

        return $query->count();
    }
}
