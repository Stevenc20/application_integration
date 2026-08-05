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
     */
    public function processCutOff(string $date, string $shiftName): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'total_unfinished' => 0, 'carried' => 0, 'cancelled' => 0];

        DB::transaction(function () use ($date, $shiftName, &$stats) {
            $unfinishedPlans = ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $shiftName)
                ->where('row_type', 'job')
                ->where(function ($q) {
                    $q->whereRaw('COALESCE(ok, 0) < COALESCE(plan, 0)')
                      ->orWhereNull('ok');
                })
                ->whereNotExists(function ($sub) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('recovery_items')
                        ->whereColumn('recovery_items.production_plan_id', 'production_plans.id')
                        ->whereIn('recovery_items.status', ['waiting_approval', 'continue', 'approved', 'scheduled', 'in_production']);
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

        // Carry forward items flagged "dilanjut pindah jam" (status 'continue')
        // to the next shift automatically — no leader approval needed.
        DB::transaction(function () use ($date, $shiftName, &$stats) {
            $continueItems = RecoveryItem::where('status', 'continue')
                ->whereDate('source_date', $date)
                ->where('source_shift', $shiftName)
                ->get();

            foreach ($continueItems as $item) {
                $plan = $item->productionPlan;
                $actualQty = (float)($plan?->ok ?? 0);
                $planQty = (float)($plan?->plan ?? $item->plan_qty ?? 0);
                $remaining = max(0, $planQty - $actualQty);

                if ($remaining <= 0) {
                    $item->update(['status' => 'completed']);
                    $stats['cancelled']++;
                    continue;
                }

                $target = $this->nextShiftTarget($date, $shiftName);

                $item->update([
                    'recovery_qty' => $remaining,
                    'status'       => 'scheduled',
                    'source_date'  => $target['date'],
                    'source_shift' => $target['shift'],
                ]);

                $this->injectIntoPlan($item, $target['date'], $target['shift']);
                $stats['carried']++;
            }
        });

        Log::info("CutOff processed for {$date} {$shiftName}: {$stats['created']} recovery items created, {$stats['skipped']} skipped, {$stats['carried']} carried, {$stats['cancelled']} cancelled, {$stats['total_unfinished']} unfinished plans");

        return $stats;
    }

    /**
     * Determine the target date/shift for carried-forward items.
     */
    private function nextShiftTarget(string $date, string $shiftName): array
    {
        if (str_contains(strtoupper($shiftName), 'MALAM')) {
            return [
                'date'  => \Carbon\Carbon::parse($date)->addDay()->toDateString(),
                'shift' => 'Shift Pagi',
            ];
        }

        return [
            'date'  => $date,
            'shift' => 'Shift Malam',
        ];
    }

    /**
     * Inject a carried-forward recovery item into the target shift's plan
     * (mirrors PPC approveItems but without waiting for approval).
     */
    private function injectIntoPlan(RecoveryItem $item, string $targetDate, string $targetShift): void
    {
        $press = $item->press_name;

        $existingPlan = ProductionPlan::whereDate('plan_date', $targetDate)
            ->where('press_name', $press)
            ->first();
        $lineMasterId = $existingPlan ? $existingPlan->line_master_id : 1;

        $hari = \Carbon\Carbon::parse($targetDate)->locale('id')->isoFormat('dddd');
        $processTime = (int) ceil((($item->ct_detik ?? 0) * $item->recovery_qty) / 60.0);

        ProductionPlan::create([
            'plan_date'      => $targetDate,
            'shift_name'     => $targetShift,
            'press_name'     => $press,
            'line_master_id' => $lineMasterId,
            'hari'           => $hari,
            'row_type'       => 'job',
            'row_no'         => 0,
            'job_no'         => $item->job_no,
            'job_master'     => $item->job_master,
            'plan'           => $item->recovery_qty,
            'original_plan'  => $item->plan_qty,
            'remaining_plan' => $item->recovery_qty,
            'ct_detik'       => $item->ct_detik,
            'dct'            => $item->dct,
            'total_mesin'    => (int) ($item->total_mesin ?? 1),
            'process_time'   => $processTime,
            'recovery_id'    => $item->id,
            'source_type'    => 'recovery',
            'status'         => 'pending',
        ]);

        try {
            $timelineGenerator = app(\App\Services\TimelineGenerationService::class);
            $timelineGenerator->regenerateSection($targetDate, $targetShift, $press);
        } catch (\Throwable $e) {
            Log::warning('Regeneration after carry-forward failed: ' . $e->getMessage());
        }
    }

}
