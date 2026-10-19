<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CutOffService
{
    /**
     * Process cut-off for a given date and shift.
     * Finds production plans where ok < plan (unfinished items)
     * and creates pending RecoveryItems for them.
     *
     * @param string $date
     * @param string $shiftName
     * @param string|null $pressName Optional press name to scope cut-off per line/press
     */
    public function processCutOff(string $date, string $shiftName, ?string $pressName = null): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'total_unfinished' => 0];

        DB::transaction(function () use ($date, $shiftName, $pressName, &$stats) {
            $stats['carried'] = 0;
            $stats['cancelled'] = 0;

            // Normalize press filter if provided
            $normalizedPress = $pressName ? strtoupper(trim(str_replace(['Press ', 'PRESS ', 'Line ', 'LINE '], '', $pressName))) : null;

            // --- Phase 1: Handle existing 'continue' items ---
            $continueItemsQuery = RecoveryItem::where('status', 'continue')
                ->whereDate('source_date', $date)
                ->where('source_shift', $shiftName);

            if ($normalizedPress !== null) {
                $continueItemsQuery->whereRaw("
                    REPLACE(
                        REPLACE(
                            UPPER(TRIM(press_name)),
                            'PRESS ',
                            ''
                        ),
                        'LINE ',
                        ''
                    ) = ?
                ", [$normalizedPress]);
            }

            $continueItems = $continueItemsQuery->get();

            $nextShiftName = $shiftName === 'Shift Pagi' ? 'Shift Malam' : 'Shift Pagi';
            $nextDate = $shiftName === 'Shift Pagi' ? $date : \Carbon\Carbon::parse($date)->addDay()->toDateString();

            foreach ($continueItems as $item) {
                $linkedPlan = $item->production_plan_id ? \App\Models\ProductionPlan::find($item->production_plan_id) : null;
                $actualQty = $linkedPlan ? (float)($linkedPlan->ok ?? 0) : 0;
                $planQty = $linkedPlan ? (float)($linkedPlan->plan ?? 0) : (float)$item->plan_qty;

                if ($actualQty >= $planQty) {
                    $item->update(['status' => 'completed']);
                    $stats['cancelled']++;
                    continue;
                }

                $recoveryQty = max(0, $planQty - $actualQty);

                $item->update([
                    'status' => 'scheduled',
                    'source_date' => $nextDate,
                    'source_shift' => $nextShiftName,
                    'recovery_qty' => $recoveryQty
                ]);

                \App\Models\ProductionPlan::create([
                    'line_master_id' => $linkedPlan ? $linkedPlan->line_master_id : 1,
                    'plan_date' => $nextDate,
                    'shift_name' => $nextShiftName,
                    'press_name' => $item->press_name,
                    'row_type' => 'job',
                    'job_no' => $item->job_no,
                    'job_master' => $item->job_master ?? $item->job_name,
                    'plan' => $recoveryQty,
                    'ct_detik' => $item->ct_detik,
                    'dct' => $item->dct,
                    'total_mesin' => $item->total_mesin,
                    'source_type' => 'recovery',
                    'recovery_id' => $item->id,
                    'row_no' => 9999, // push to end
                ]);

                $stats['carried']++;
            }

            // Exclude ALL active statuses covered by active_production_plan_id virtual column
            $existingPlanIds = RecoveryItem::whereIn('status', ['waiting_approval', 'continue', 'approved', 'scheduled', 'in_production'])
                ->pluck('production_plan_id')
                ->filter()
                ->toArray();

            $unfinishedPlansQuery = ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', 'like', "{$shiftName}%")
                ->where('row_type', 'job')
                ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                ->whereNotIn('id', $existingPlanIds)
                ->where(function ($q) {
                    $q->whereRaw('COALESCE(ok, 0) < COALESCE(plan, 0)')
                      ->orWhereNull('ok');
                });

            if ($normalizedPress !== null) {
                $unfinishedPlansQuery->whereRaw("
                    REPLACE(
                        REPLACE(
                            UPPER(TRIM(press_name)),
                            'PRESS ',
                            ''
                        ),
                        'LINE ',
                        ''
                    ) = ?
                ", [$normalizedPress]);
            }

            $unfinishedPlans = $unfinishedPlansQuery->get();

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

                // Use updateOrCreate with production_plan_id to guarantee idempotency
                // and strictly avoid Duplicate entry on active_production_plan_id
                $itemAttributes = [
                    'recovery_schedule_id' => $schedule->id,
                    'job_no'               => trim($plan->job_no ?? ''),
                    'job_master'           => $plan->job_master ?? trim($plan->job_no ?? ''),
                    'press_name'           => $plan->press_name,
                    'plan_qty'             => $planQty,
                    'ok'                   => $actualQty,
                    'repair'               => (float)($plan->repair ?? 0),
                    'reject'               => (float)($plan->reject ?? 0),
                    'ct_detik'             => $ctDetik,
                    'dct'                  => $dct,
                    'reg_active'           => (float)($plan->reg_active ?? 0),
                    'total_mesin'          => (int)($plan->total_mesin ?? 1),
                    'status'               => 'waiting_approval',
                    'original_date'        => $date,
                    'original_shift_name'  => $shiftName,
                    'source_date'          => $date,
                    'source_shift'         => $shiftName,
                    'actual_qty'           => $actualQty,
                    'recovery_qty'         => $recoveryQty,
                    'duration_minutes'     => $durationMinutes,
                    'queued_at'            => now(),
                ];

                $existingItem = RecoveryItem::where('production_plan_id', $plan->id)
                    ->whereIn('status', ['waiting_approval', 'continue', 'approved', 'scheduled', 'in_production'])
                    ->first();

                if ($existingItem) {
                    $existingItem->update($itemAttributes);
                } else {
                    RecoveryItem::updateOrCreate(
                        [
                            'recovery_schedule_id' => $schedule->id,
                            'production_plan_id'   => $plan->id,
                        ],
                        $itemAttributes
                    );
                    $stats['created']++;
                }
            }
        });

        $scopeMsg = $pressName ? " for press {$pressName}" : "";
        Log::info("CutOff processed for {$date} {$shiftName}{$scopeMsg}: {$stats['created']} recovery items created, {$stats['skipped']} skipped, {$stats['total_unfinished']} unfinished plans");

        return $stats;
    }


}
