<?php

namespace App\Services;

use App\Models\MasterBreakTime;
use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single PPC timeline generator — breaktime parameters → production_plans.
 * LKH / Input Harian / Actual Process only READ this output.
 */
class TimelineGenerationService
{
    public function regenerateSection(string $date, string $shiftName, ?string $pressName = null, bool $forceMaster = false): int
    {
        // Normalize date to Y-m-d format (handles "2026-05-08 00:00:00" from DB queries)
        $date = \Carbon\Carbon::parse($date)->format('Y-m-d');

        // 1. Fetch initial plans to extract context (like line_master_id, hari)
        $query = ProductionPlan::query()
            ->whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'job')
            ->whereNotIn('job_no', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ])
            ->whereNotIn('job_master', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ]);

        if ($pressName) {
            $this->applyPressFilter($query, $pressName);
        }

        $plans = $query->orderBy('row_no')->get();
        if ($plans->isEmpty()) {
            return 0;
        }

        $firstPlan = $plans->first();
        $lineMasterId = $firstPlan->line_master_id;
        if (!$lineMasterId && Schema::hasTable('line_masters')) {
            $lineMasterId = DB::table('line_masters')->first()->id ?? 1;
        }
        if (!$lineMasterId) {
            $lineMasterId = 1;
        }
        // Use the plan's actual press_name so breaks stay under the same press_name as jobs,
        // preventing phantom press_names (e.g. 'Line A') from clobbering real ones (e.g. 'PRESS A').
        $resolvedPressName = $firstPlan->press_name ?? ($pressName ?: 'PRESS A');
        $hari = $firstPlan->hari;

        // Clean up previous splits to ensure deterministic regeneration
        $previousChildren = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->whereNotNull('parent_job_id');
        if ($pressName) {
            $this->applyPressFilter($previousChildren, $pressName);
        }
        $previousChildren->delete();

        // Restore parents to original plan values
        $parents = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->whereNotNull('original_plan');
        if ($pressName) {
            $this->applyPressFilter($parents, $pressName);
        }
        foreach ($parents->get() as $parent) {
            $parent->plan = $parent->original_plan;
            $parent->original_plan = null;
            $parent->remaining_plan = null;
            $parent->split_group = null;
            $parent->session_no = null;
            $parent->parent_job_id = null;
            $parent->start_time = null;
            $parent->finish_time = null;
            $parent->process_time = null;
            $parent->tpt = null;
            $parent->gsph_item = null;
            $parent->total_plt = null;
            $parent->plan_dct = null;
            $parent->tpt_total = null;
            $parent->a1 = null;
            $parent->a2 = null;
            $parent->a3 = null;
            $parent->a4 = null;
            $parent->save();
        }

        // Delete all old break rows to avoid duplicates or out of order entries
        $previousBreaks = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'break');
        if ($pressName) {
            $this->applyPressFilter($previousBreaks, $pressName);
        }
        $previousBreaks->delete();

        // Reset timing fields on all job rows so anchor recalculates from shift start time
        $resetTiming = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'job');
        if ($pressName) {
            $this->applyPressFilter($resetTiming, $pressName);
        }
        $resetTiming->update(['start_time' => null, 'finish_time' => null]);

        // 2a. Insert approved recovery items into production_plans (source_type='recovery')
        $this->insertApprovedRecoveryForSection($date, $shiftName, $resolvedPressName, $lineMasterId, $hari);

        // 2b. Re-fetch all clean job plans for processing
        $query = ProductionPlan::query()
            ->whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'job')
            ->whereNotIn('job_no', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ])
            ->whereNotIn('job_master', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ]);

        if ($pressName) {
            $this->applyPressFilter($query, $pressName);
        }

        $plans = $query->orderBy('row_no')->get();
        if ($plans->isEmpty()) {
            return 0;
        }

        $hari = $plans->first()->hari;
        $breakWindows = $this->resolveBreakWindows($date, $shiftName, $hari);
        
        // Sort breaks chronologically by start time
        usort($breakWindows, fn ($a, $b) => MasterBreakTime::timeToMinutes($a['start']) <=> MasterBreakTime::timeToMinutes($b['start']));

        $isShiftMalam = $this->isShiftMalam($shiftName);
        $shiftStart = $this->shiftAnchorTime($date, $shiftName, $isShiftMalam);
        $currentTimeMins = MasterBreakTime::timeToMinutes($shiftStart->format('H:i'));

        $shiftEnd = $this->shiftEndTime($date, $shiftName, $isShiftMalam);
        $shiftEndMins = MasterBreakTime::timeToMinutes($shiftEnd->format('H:i'));
        if ($isShiftMalam) {
            $shiftEndMins += 1440;
        }

        // Normalize break times for night shift: breaks that fall after midnight
        // (00:00-04:45) are actually on the NEXT calendar day, so add +1440 min
        // for correct comparison against cursor times (21:00+).
        if ($isShiftMalam) {
            $anchorMins = $currentTimeMins; // 21:00 = 1260
            foreach ($breakWindows as &$b) {
                $rawStart = MasterBreakTime::timeToMinutes($b['start']);
                if ($rawStart < $anchorMins) {
                    $b['_normStart'] = $rawStart + 1440;
                    $b['_normEnd'] = MasterBreakTime::timeToMinutes($b['finish']) + 1440;
                }
            }
            unset($b);
        }

        $outputSequence = [];
        $insertedBreaks = [];
        $cutoffPlans = [];
        $plansArray = $plans->all();
        $i = 0;
        $loopGuard = 0;

        while ($i < count($plansArray)) {
            $loopGuard++;
            if ($loopGuard > 200) {
                break;
            }

            $itemPlan = $plansArray[$i];
            
            // A. Check if there are any uninserted break windows that start BEFORE or AT the current timeline cursor
            foreach ($breakWindows as $idx => $b) {
                if (in_array($idx, $insertedBreaks)) {
                    continue;
                }
                $bStartMins = $b['_normStart'] ?? MasterBreakTime::timeToMinutes($b['start']);
                $bEndMins = $b['_normEnd'] ?? MasterBreakTime::timeToMinutes($b['finish']);
                
                if ($bStartMins <= $currentTimeMins) {
                    // Push the break row to output sequence at the exact current time!
                    $outputSequence[] = [
                        'type' => 'break',
                        'label' => $b['label'],
                        'start' => $b['start'],
                        'finish' => $b['finish'],
                        'type_break' => $b['type'],
                    ];
                    $insertedBreaks[] = $idx;
                    
                    // Advance cursor to end of break
                    $currentTimeMins = max($currentTimeMins, $bEndMins);
                }
            }

            // B. Process current job normally
            $ct = (float)($itemPlan->ct_detik ?? 0);
            $planQty = (int)($itemPlan->plan ?? 0);
            $dct = (float)($itemPlan->dct ?? 0);

            // Use stored process_time/tpt from DB when available,
            // fall back to calculation from ct, plan, dct
            $processTime = (int)($itemPlan->process_time ?? 0);
            if ($processTime <= 0) {
                $processTime = (int)ceil(($ct * $planQty) / 60.0);
            }
            $tpt = (float)($itemPlan->tpt ?? 0);
            if ($tpt <= 0) {
                $tpt = $processTime + $dct;
            }

            // Jika plan qty 0, jangan buang waktu untuk setup (dct)
            if ($planQty <= 0) {
                $tpt = 0;
            }
            
            $startTimeMins = $currentTimeMins;
            $adjustedFinishMins = $startTimeMins + $tpt;

            // Jangan mulai item setelah batas shift
            if ($startTimeMins >= $shiftEndMins) {
                $cutoffPlans[] = $itemPlan;
                $i++;
                continue;
            }
            // Cap finish di batas shift (21:00 Pagi / 07:30 Malam)
            $wasCapped = false;
            if ($adjustedFinishMins > $shiftEndMins) {
                $adjustedFinishMins = $shiftEndMins;
                $wasCapped = true;
            }
            
           // =====================================
// BREAK OVERLAP CHECK
// =====================================

foreach ($breakWindows as $idx => $b) {

    if (in_array($idx, $insertedBreaks)) {
        continue;
    }

    $bStartMins =
        $b['_normStart'] ?? MasterBreakTime::timeToMinutes($b['start']);

    $bEndMins =
        $b['_normEnd'] ?? MasterBreakTime::timeToMinutes($b['finish']);

    // BREAK OVERLAP
    if (
        $bStartMins > $startTimeMins
        &&
        $bStartMins < $adjustedFinishMins
    ) {

        // =====================================
        // SISA MENIT SEBELUM BREAK
        // =====================================

        $remainingMinuteBeforeBreak =
            $bStartMins - $startTimeMins;

        // =====================================
        // AVAILABLE PROCESS MINUTE
        // =====================================

        $availableProcessMinute =
            max(0, $remainingMinuteBeforeBreak - $dct);

        // =====================================
        // JIKA TIDAK ADA WAKTU SEBELUM BREAK
        // defer seluruh item ke setelah break
        // =====================================

        if ($availableProcessMinute <= 0) {
            $currentTimeMins = $bEndMins;
            continue 2;
        }

        // =====================================
        // BERAPA PCS YANG BISA DIPROSES
        // =====================================

        // SKIP jika CT = 0 (tidak bisa dihitung)
        if ($ct <= 0) {
            continue;
        }

        $processableQty =
            floor(
                ($availableProcessMinute * 60)
                / $ct
            );

        // JIKA TIDAK CUKUP WAKTU UNTUK 1 PCS, defer ke setelah break
        if ($processableQty <= 0) {
            $currentTimeMins = $bEndMins;
            continue 2;
        }

        // SAFETY
        $processableQty =
            max(1, min($planQty, $processableQty));

        // =====================================
        // SESSION A
        // =====================================

        $sessionAProcessTime =
            round(
                ($ct * $processableQty) / 60,
                1
            );

        $sessionATpt =
            ceil($sessionAProcessTime + $dct);

        // UPDATE ITEM ASLI
        $itemPlan->original_plan =
            $planQty;

        $itemPlan->plan =
            $processableQty;

        $itemPlan->process_time =
            $sessionAProcessTime;

        $itemPlan->tpt =
            $sessionATpt;

        $itemPlan->start_time =
            MasterBreakTime::minutesToTime(
                $startTimeMins
            );

        $itemPlan->finish_time =
            $b['start'];

        $itemPlan->save();

        // Add Session A to output sequence — stops exactly at break start
        $outputSequence[] = [
            'type' => 'job',
            'plan' => $itemPlan,
            'start' => MasterBreakTime::minutesToTime($startTimeMins),
            'finish' => $b['start'],
            'metrics' => [
                'process_time' => $sessionAProcessTime,
                'tpt' => $sessionATpt,
                'gsph' => ProductionMetricsService::gsph($processableQty, $sessionATpt),
                'total_plt' => $itemPlan->qty_plt > 0 ? (int)ceil($processableQty / $itemPlan->qty_plt) : 0,
                'plan_dct' => $dct,
                'tpt_total' => $sessionATpt * $itemPlan->total_mesin,
                'a1' => $sessionATpt,
                'a2' => $sessionATpt,
                'a3' => $sessionATpt,
                'a4' => $sessionATpt,
            ],
        ];

        // =====================================
        // INSERT BREAK ROW
        // =====================================

        $outputSequence[] = [
            'type' => 'break',
            'label' => $b['label'],
            'start' => $b['start'],
            'finish' => $b['finish'],
            'type_break' => $b['type'],
        ];

        $insertedBreaks[] = $idx;

        // =====================================
        // SESSION B — replace current array item
        // so the main loop continues processing
        // the clone (which may hit more breaks)
        // =====================================

        $remainingQty = $planQty - $processableQty;

        if ($remainingQty > 0) {
            $clone = $itemPlan->replicate();
            $clone->id = null;
            $clone->parent_job_id = $itemPlan->id;
            $clone->plan = $remainingQty;
            $clone->start_time = $b['finish'];

            $sessionBProcessTime = round(($ct * $remainingQty) / 60, 1);
            $sessionBTpt = ceil($sessionBProcessTime + $dct);
            $clone->process_time = $sessionBProcessTime;
            $clone->tpt = $sessionBTpt;

            // Replace current plan with clone so the normal loop
            // processes it from after the break
            $plansArray[$i] = $clone;
            $currentTimeMins = $bEndMins;
        } else {
            $currentTimeMins = $bEndMins;
            $i++;
        }

        continue 2;
    }
}

            // Jika plan qty 0, tempatkan setelah break terdekat
            if ($planQty <= 0) {
                foreach ($breakWindows as $idx => $b) {
                    if (in_array($idx, $insertedBreaks)) continue;
                    $bEndMins = $b['_normEnd'] ?? MasterBreakTime::timeToMinutes($b['finish']);
                    if ($currentTimeMins < $bEndMins) {
                        $outputSequence[] = [
                            'type' => 'break',
                            'label' => $b['label'],
                            'start' => $b['start'],
                            'finish' => $b['finish'],
                            'type_break' => $b['type'],
                        ];
                        $insertedBreaks[] = $idx;
                        $currentTimeMins = max($currentTimeMins, $bEndMins);
                    }
                    break;
                }
                $startTimeMins = $currentTimeMins;
                $adjustedFinishMins = $startTimeMins;
            }

            // Calculate metrics for the job
            $metrics = [
                'process_time' => $processTime,
                'tpt' => $tpt,
                'gsph' => ProductionMetricsService::gsph($planQty, $tpt),
                'total_plt' => $itemPlan->qty_plt > 0 ? (int)ceil($planQty / $itemPlan->qty_plt) : 0,
                'plan_dct' => $dct,
                'tpt_total' => $tpt * $itemPlan->total_mesin,
                'a1' => $tpt,
                'a2' => $tpt,
                'a3' => $tpt,
                'a4' => $tpt,
            ];

            $jobStartStr = MasterBreakTime::minutesToTime($startTimeMins);
            $jobFinishStr = MasterBreakTime::minutesToTime($adjustedFinishMins);

            $outputSequence[] = [
                'type' => 'job',
                'plan' => $itemPlan,
                'start' => $jobStartStr,
                'finish' => $jobFinishStr,
                'metrics' => $metrics,
            ];

            // Advance cursor to the shifted finish time (min +1 to avoid stalls)
            $currentTimeMins = max($startTimeMins + 1, $adjustedFinishMins);
            $i++;
        }

        // Post-loop: Insert any remaining break windows
        foreach ($breakWindows as $idx => $b) {
            if (!in_array($idx, $insertedBreaks)) {
                $outputSequence[] = [
                    'type' => 'break',
                    'label' => $b['label'],
                    'start' => $b['start'],
                    'finish' => $b['finish'],
                    'type_break' => $b['type'],
                ];
                $insertedBreaks[] = $idx;
            }
        }

        // Sequence is already in processing order (Excel row order), which is the desired
        // user-facing order.  Breaks are inserted inline when they overlap, so no re-sort needed.
        // The old usort() by start time was removed because it scrambled the Excel row order.

        // 2b. Extend job finish to the next break if there is a gap.
        // This ensures items that finish before a break still visually
        // "stop at the break boundary" (user confirmed "semua item sebelum break").
        for ($i = 0; $i < count($outputSequence) - 1; $i++) {
            if ($outputSequence[$i]['type'] === 'job' && $outputSequence[$i + 1]['type'] === 'break') {
                $jobFinish = MasterBreakTime::timeToMinutes($outputSequence[$i]['finish']);
                $breakStart = MasterBreakTime::timeToMinutes($outputSequence[$i + 1]['start']);
                if ($jobFinish < $breakStart) {
                    $outputSequence[$i]['finish'] = $outputSequence[$i + 1]['start'];
                }
            }
        }

        // 2c. Maximise remaining time until shift boundary.
        // (21:00 for pagi, 07:30 for malam).
        // Strategy:
        //   A. If last job finish < shiftEnd → calculate additional pcs from CT
        //   B. If that fails → find approved recovery item to fill the gap
        //   C. If last job finish == shiftEnd but was CAPPED (tpt > available mins) → add more pcs
        //   D. Visual extension fallback
        $lastJobIdx = null;
        for ($i = count($outputSequence) - 1; $i >= 0; $i--) {
            if ($outputSequence[$i]['type'] === 'job') {
                $lastJobIdx = $i;
                break;
            }
        }

        if ($lastJobIdx === null) {
            // No jobs to extend — nothing to do
        } else {
            $lastJob = &$outputSequence[$lastJobIdx];
            $lastFinish = MasterBreakTime::timeToMinutes($lastJob['finish']);
            $lastStart = MasterBreakTime::timeToMinutes($lastJob['start']);
            $lastPlanModel = $lastJob['plan'];
            $lastCt = (float)($lastPlanModel->ct_detik ?? 0);
            $lastDct = (float)($lastPlanModel->dct ?? 0);
            $lastTpt = (float)($lastJob['metrics']['tpt'] ?? 0);

            // Berapa menit yg tersedia dari start job terakhir sampai shift end?
            $availableFromStart = $shiftEndMins - $lastStart;

            // Apakah item terakhir di-cap? (finish == shiftEnd tapi tpt > waktu yg tersedia)
            $wasCapped = ($lastFinish >= $shiftEndMins && $lastTpt > $availableFromStart);

            // Berapa menit yg "terbuang" karena cap / gap?
            $unusedMins = 0;
            if ($wasCapped) {
                // Waktu dari finish skrg (shiftEnd) sampai seharusnya finish (start + tpt)
                $naturalFinish = $lastStart + $lastTpt;
                $unusedMins = ($naturalFinish > $shiftEndMins) ? ($naturalFinish - $shiftEndMins) : 0;
            } else if ($lastFinish < $shiftEndMins) {
                $unusedMins = $shiftEndMins - $lastFinish;
            }

            // --- Attempt A: extend plan qty ---
            $didFillWithPcs = false;
            if ($unusedMins > 0 && $lastCt > 0) {
                // Hitung MAXIMUM pcs yg muat dari start item sampai shiftEnd
                // (dikurangi dct karena setup time tetap).
                // Idempoten: kalo dipanggil berulang hasilnya tetap sama.
                $availableProcessMins = ($shiftEndMins - $lastStart) - $lastDct;
                if ($availableProcessMins > 0) {
                    $maxPcs = (int)floor(($availableProcessMins * 60) / $lastCt);
                    $currentPlan = (float)$lastPlanModel->plan;
                    if ($maxPcs > $currentPlan) {
                        $lastPlanModel->plan = $maxPcs;
                        $newProcessTime = (int)ceil(($lastCt * $maxPcs) / 60.0);
                        $newTpt = $newProcessTime + $lastDct;
                        $lastJob['metrics']['process_time'] = $newProcessTime;
                        $lastJob['metrics']['tpt'] = $newTpt;
                        $lastJob['finish'] = MasterBreakTime::minutesToTime($shiftEndMins);
                        $didFillWithPcs = true;
                    }
                }
            }

            // --- Visual extension fallback ---
            $actualLastIdx = null;
            for ($i = count($outputSequence) - 1; $i >= 0; $i--) {
                if ($outputSequence[$i]['type'] === 'job') {
                    $actualLastIdx = $i;
                    break;
                }
            }
            if ($actualLastIdx !== null) {
                $actualLastFinish = MasterBreakTime::timeToMinutes($outputSequence[$actualLastIdx]['finish']);
                if ($actualLastFinish < $shiftEndMins) {
                    $outputSequence[$actualLastIdx]['finish'] = MasterBreakTime::minutesToTime($shiftEndMins);
                }
            }
            unset($lastJob);
        }

        // 2d. Cutoff items (start >= shiftEnd) → null timestamps & create recovery items
        if (!empty($cutoffPlans)) {
            $schedule = \App\Models\RecoverySchedule::firstOrCreate(
                [
                    'plan_date'  => $date,
                    'shift_name' => $shiftName,
                    'press_name' => $resolvedPressName,
                ],
                [
                    'status' => 'waiting_approval',
                ]
            );
            foreach ($cutoffPlans as $cp) {
                $cp->start_time = null;
                $cp->finish_time = null;
                $cp->save();
                $cpJobNo = trim($cp->job_no ?? '');
                $cpPlan = (float)($cp->plan ?? 0);
                if ($cpJobNo === '' || $cpPlan <= 0) {
                    continue;
                }
                $actualQty = (float)($cp->ok ?? 0);
                $recoveryQty = max(0, $cpPlan - $actualQty);
                $ctDetik = (float)($cp->ct_detik ?? 0);
                $dct = (float)($cp->dct ?? 0);
                $durationMinutes = $ctDetik > 0
                    ? (int)ceil(($ctDetik * $recoveryQty) / 60.0) + $dct
                    : 0;

                \App\Models\RecoveryItem::firstOrCreate(
                    [
                        'recovery_schedule_id' => $schedule->id,
                        'job_no'               => $cpJobNo,
                        'press_name'           => $resolvedPressName,
                    ],
                    [
                        'production_plan_id'  => $cp->id,
                        'job_master'          => $cp->job_master ?? $cpJobNo,
                        'plan_qty'            => $cpPlan,
                        'ok'                  => $actualQty,
                        'repair'              => (float)($cp->repair ?? 0),
                        'reject'              => (float)($cp->reject ?? 0),
                        'ct_detik'            => $ctDetik,
                        'dct'                 => $dct,
                        'reg_active'          => (float)($cp->reg_active ?? 0),
                        'total_mesin'         => (int)($cp->total_mesin ?? 1),
                        'status'              => 'waiting_approval',
                        'original_date'       => $date,
                        'original_shift_name' => $shiftName,
                        'source_date'         => $date,
                        'source_shift'        => $shiftName,
                        'actual_qty'          => $actualQty,
                        'recovery_qty'        => $recoveryQty,
                        'duration_minutes'    => $durationMinutes,
                        'queued_at'           => now(),
                    ]
                );
            }
        }

        // 2e. Revert recovery items that didn't fit (start_time is null)
        $unusedRecovery = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('press_name', $resolvedPressName)
            ->where('source_type', 'recovery')
            ->whereNull('start_time')
            ->get();

        foreach ($unusedRecovery as $ur) {
            if ($ur->recovery_id) {
                RecoveryItem::where('id', $ur->recovery_id)
                    ->where('status', 'scheduled')
                    ->update(['status' => 'approved']);
            }
            $ur->delete();
        }

        // 3. Database transaction persist
        $updated = 0;
        DB::transaction(function () use ($outputSequence, $date, $shiftName, $resolvedPressName, $lineMasterId, $hari, &$updated) {
            $rowNo = 1;
            foreach ($outputSequence as $item) {
                if ($item['type'] === 'break') {
                    $duration = MasterBreakTime::timeToMinutes($item['finish']) - MasterBreakTime::timeToMinutes($item['start']);
                    
                    ProductionPlan::create([
                        'plan_date' => $date,
                        'shift_name' => $shiftName,
                        'press_name' => $resolvedPressName,
                        'line_master_id' => $lineMasterId,
                        'hari' => $hari,
                        'row_no' => $rowNo++,
                        'row_type' => 'break',
                        'job_no' => $item['label'],
                        'job_master' => $item['label'],
                        'start_time' => $item['start'],
                        'finish_time' => $item['finish'],
                        'dct' => $duration,
                        'tpt' => $duration,
                        'plan_dct' => $duration,
                        'a1' => $duration,
                        'keterangan' => $item['type_break'] === 'cinkorak' ? 'CINKORAK' : 'ISTIRAHAT',
                    ]);
                } else {
                    $plan = $item['plan'];
                    $metrics = $item['metrics'];

                    $plan->row_type = 'job';
                    $plan->row_no = $rowNo++;
                    $plan->process_time = $metrics['process_time'];
                    $plan->tpt = $metrics['tpt'];
                    $plan->gsph_item = $metrics['gsph'];
                    $plan->start_time = $item['start'];
                    $plan->finish_time = $item['finish'];
                    $plan->total_plt = $metrics['total_plt'];
                    $plan->plan_dct = $metrics['plan_dct'];
                    $plan->tpt_total = $metrics['tpt_total'];
                    $plan->a1 = $metrics['a1'];
                    $plan->a2 = $metrics['a2'];
                    $plan->a3 = $metrics['a3'];
                    $plan->a4 = $metrics['a4'];
                    $plan->p1 = $metrics['a1'] > 0;
                    $plan->p2 = $metrics['a2'] > 0;
                    $plan->p3 = $metrics['a3'] > 0;
                    $plan->p4 = $metrics['a4'] > 0;
                    
                    // Explicitly preserve split state fields
                    $plan->parent_job_id = $item['plan']->parent_job_id;
                    $plan->split_group = $item['plan']->split_group;
                    $plan->session_no = $item['plan']->session_no;
                    $plan->original_plan = $item['plan']->original_plan;
                    $plan->remaining_plan = $item['plan']->remaining_plan;
                    $plan->save();
                }
                $updated++;
            }

        });

        return $updated;
    }

    private function insertBreakRow(
        string $date, string $shiftName, string $pressName, int $lineMasterId, ?string $hari,
        array $b, int $rowNo
    ): void {
        $label = $b['label'] ?? ($b['type'] === 'cinkorak' ? 'CINGKORAK' : 'ISTIRAHAT');
        $duration = MasterBreakTime::timeToMinutes($b['finish']) - MasterBreakTime::timeToMinutes($b['start']);

        ProductionPlan::create([
            'plan_date' => $date,
            'shift_name' => $shiftName,
            'press_name' => $pressName,
            'line_master_id' => $lineMasterId,
            'hari' => $hari,
            'row_no' => $rowNo,
            'row_type' => 'break',
            'job_no' => $label,
            'job_master' => $label,
            'start_time' => $b['start'],
            'finish_time' => $b['finish'],
            'dct' => $duration,
            'tpt' => $duration,
            'plan_dct' => $duration,
            'a1' => $duration,
            'keterangan' => $b['label'] ?? ($b['type'] === 'cinkorak' ? 'CINKORAK' : 'ISTIRAHAT'),
        ]);
    }

    public function ensureBreaksExist(string $date, string $shiftName, string $pressName, int $lineMasterId, ?string $hari = null): void
    {
        $breakWindows = $this->resolveBreakWindows($date, $shiftName, $hari);
        if (empty($breakWindows)) {
            return;
        }

        foreach ($breakWindows as $b) {
            $label = strtoupper(trim($b['label']));
            
            // Check if there is already a break row with this label or start time
            $existsQuery = ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $shiftName)
                ->where('row_type', 'break')
                ->where(function ($q) use ($label, $b) {
                    $q->where('job_no', $label)
                      ->orWhere('job_master', $label)
                      ->orWhere('start_time', $b['start']);
                });
            $this->applyPressFilter($existsQuery, $pressName);
            $exists = $existsQuery->exists();

            if (!$exists) {
                // Determine a good row_no for this break based on its start time
                $jobsQuery = ProductionPlan::whereDate('plan_date', $date)
                    ->where('shift_name', $shiftName)
                    ->where('row_type', 'job');
                $this->applyPressFilter($jobsQuery, $pressName);
                $jobs = $jobsQuery->get();

                $rowNo = $this->breakRowNoAfter($jobs, $b['start']);
                
                $this->insertBreakRow($date, $shiftName, $pressName, $lineMasterId, $hari, $b, $rowNo);
            }
        }
    }

    /**
     * Load approved recovery items for this press and insert them into production_plans
     * as source_type='recovery' entries. Items that don't fit capacity will be reverted
     * by revertUnusedRecoveryItems() after the main processing loop.
     */
    private function insertApprovedRecoveryForSection(string $date, string $shiftName, string $pressName, int $lineMasterId, ?string $hari): void
    {
        $approvedRecovery = RecoveryItem::approved()
            ->where('press_name', $pressName)
            ->orderBy('source_date', 'asc')
            ->orderBy('source_shift', 'asc')
            ->orderBy('original_row_no', 'asc')
            ->get();

        if ($approvedRecovery->isEmpty()) {
            return;
        }

        $existingRecoveryNo = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('press_name', $pressName)
            ->where('source_type', 'recovery')
            ->max('row_no') ?? 0;

        $rowNo = $existingRecoveryNo > 0 ? $existingRecoveryNo + 1 : 1;

        foreach ($approvedRecovery as $ri) {
            $planQty = $ri->recovery_qty > 0 ? (float)$ri->recovery_qty : (float)($ri->plan_qty ?? 0);
            $ctDetik = (float)($ri->ct_detik ?? 0);
            $dct = (float)($ri->dct ?? 0);
            $processTime = $ctDetik > 0 ? (int)ceil(($ctDetik * $planQty) / 60.0) : 0;

            ProductionPlan::create([
                'line_master_id' => $lineMasterId,
                'plan_date'      => $date,
                'shift_name'     => $shiftName,
                'press_name'     => $pressName,
                'hari'           => $hari,
                'row_no'         => $rowNo++,
                'row_type'       => 'job',
                'job_master'     => $ri->job_master,
                'job_no'         => $ri->job_no,
                'plan'           => $planQty,
                'ok'             => (float)($ri->ok ?? 0),
                'repair'         => (float)($ri->repair ?? 0),
                'reject'         => (float)($ri->reject ?? 0),
                'ct_detik'       => $ctDetik,
                'dct'            => $dct,
                'reg_active'     => (float)($ri->reg_active ?? 0),
                'total_mesin'    => (int)($ri->total_mesin ?? 1),
                'process_time'   => $processTime,
                'recovery_id'    => $ri->id,
                'source_type'    => 'recovery',
                'status'         => 'pending',
            ]);

            $ri->update(['status' => 'scheduled']);
        }
    }

    private function breakWindowsFromCaptured(\Illuminate\Support\Collection $captured): array
    {
        if ($captured->isEmpty()) {
            return [];
        }

        $windows = [];
        foreach ($captured as $b) {
            $startStr = substr((string) ($b->start_time ?? ''), 0, 5);
            $finishStr = substr((string) ($b->finish_time ?? ''), 0, 5);

            // Strict validation: skip garbage data (00:00, invalid format, finish <= start)
            if (!preg_match('/^\d{2}:\d{2}$/', $startStr) || !preg_match('/^\d{2}:\d{2}$/', $finishStr)) {
                continue;
            }
            if ($startStr === '00:00' && $finishStr === '00:00') {
                continue;
            }
            if (MasterBreakTime::timeToMinutes($startStr) >= MasterBreakTime::timeToMinutes($finishStr)) {
                continue;
            }

            $label = strtoupper($b->job_no ?? $b->job_master ?? 'ISTIRAHAT');
            $isCinkorak = str_contains($label, 'CINGKORAK');

            $windows[] = [
                'start' => $startStr,
                'finish' => $finishStr,
                'type' => $isCinkorak ? 'cinkorak' : 'istirahat',
                'label' => $label,
            ];
        }

        return $windows;
    }

    public function resolveBreakWindows(string $date, string $shiftName, ?string $hari = null): array
    {
        $dayKey = $this->resolveDayKey($date, $hari);
        
        if (!Schema::hasTable('master_break_times')) {
            return $this->legacyFixedBreaks($dayKey);
        }

        $normalizedShift = $this->normalizeShiftKey($shiftName);

        $query = MasterBreakTime::where('is_active', true)
            ->where(function ($q) use ($dayKey) {
                $q->where('hari', $dayKey)
                  ->orWhere('hari', 'semua');
            });

        $query->where(function ($q) use ($normalizedShift) {
            $q->whereNull('shift')
              ->orWhere('shift', $normalizedShift)
              ->orWhere('shift', '')
              ->orWhere('shift', 'LIKE', '%' . $normalizedShift . '%');
        });

        $breaks = $query->orderBy('sort_order')
            ->orderBy('waktu_mulai')
            ->get();

        $windows = [];
        foreach ($breaks as $b) {
            $windows[] = [
                'start' => substr((string) $b->waktu_mulai, 0, 5),
                'finish' => substr((string) $b->waktu_selesai, 0, 5),
                'type' => $b->type === 'cinkorak' ? 'cinkorak' : 'istirahat',
                'label' => strtoupper($b->label),
            ];
        }

        return $windows;
    }

    public function regenerateAllSections(bool $forceMaster = false): void
    {
        $sections = ProductionPlan::select('plan_date', 'shift_name', 'press_name')
            ->whereNotNull('plan_date')
            ->whereNotNull('shift_name')
            ->distinct()
            ->get();

        foreach ($sections as $sec) {
            $this->regenerateSection(
                $sec->plan_date instanceof Carbon ? $sec->plan_date->toDateString() : (string)$sec->plan_date,
                (string)$sec->shift_name,
                $sec->press_name ? (string)$sec->press_name : null,
                $forceMaster
            );
        }
    }

    public function regenerateForPlan(ProductionPlan $plan, bool $forceMaster = false): void
    {
        $dateStr = $plan->plan_date instanceof Carbon ? $plan->plan_date->toDateString() : (string)$plan->plan_date;
        $this->regenerateSection($dateStr, $plan->shift_name, $plan->press_name, $forceMaster);
    }

    private function breakRowNoAfter(Collection $jobs, string $breakStart): int
    {
        $breakMins = MasterBreakTime::timeToMinutes($breakStart);
        $anchor = 0;

        foreach ($jobs as $job) {
            $start = substr((string) ($job->start_time ?? ''), 0, 5);
            if ($start && MasterBreakTime::timeToMinutes($start) <= $breakMins) {
                $anchor = (int) ($job->row_no ?? 0);
            }
        }

        return $anchor > 0 ? $anchor + 1 : 500;
    }

    /**
     * Insert a PPC Excel break row at the correct chronological position.
     * Called AFTER regenerateSection() so config breaks already exist.
     * This preserves the planner's original break positions (e.g. extra CINGKORAK at 10:00).
     */
    /**
     * @return array{process_time: float, work_minutes: float, tpt: float, gsph: int, total_plt: float, plan_dct: float, tpt_total: float, a1: int, a2: int, a3: int, a4: int}
     */
    private function calculateJobMetrics(ProductionPlan $plan, array $breakWindows): array
    {
        $planQty = (int) ($plan->plan ?? $plan->target_qty ?? 0);
        $qtyPlt = (float) ($plan->qty_plt ?? 0);
        $ct = (float) ($plan->ct_detik ?? 0);
        $dct = (float) ($plan->dct ?? 0);
        $regActive = (float) ($plan->reg_active ?? 0);
        $mesin = max(1, (int) ($plan->total_mesin ?? 1));

        $processTime = ProductionMetricsService::processTimeMinutes($ct, $planQty);
        $baseTpt = ProductionMetricsService::planTptMinutes($plan);
        $gsph = ProductionMetricsService::gsph($planQty, $baseTpt);

        $workMinutes = max(1, (int) round($processTime));
        $totalPlt = $qtyPlt > 0 ? ceil(($planQty / $qtyPlt) * 10) / 10 : (float) ($plan->total_plt ?? 0);
        $planDct = ($regActive + $dct) * $mesin;
        $tptTotal = $planQty * $mesin;
        $a1 = (int) ceil($baseTpt);

        return [
            'process_time' => $processTime,
            'work_minutes' => $workMinutes,
            'tpt' => $baseTpt,
            'gsph' => $gsph,
            'total_plt' => $totalPlt,
            'plan_dct' => $planDct,
            'tpt_total' => $tptTotal,
            'a1' => $a1,
            'a2' => $mesin >= 4 ? $a1 : 0,
            'a3' => $mesin >= 4 ? $a1 : 0,
            'a4' => $a1,
        ];
    }

    public function calculateFinishWithBreaks(string $startTime, int $durationMinutes, array $breakWindows): string
    {
        $current = MasterBreakTime::timeToMinutes($startTime);
        $remaining = max(0, $durationMinutes);

        usort($breakWindows, fn ($a, $b) => MasterBreakTime::timeToMinutes($a['start']) <=> MasterBreakTime::timeToMinutes($b['start']));

        while ($remaining > 0) {
            $finishWithout = $current + $remaining;
            $foundBreak = false;

            foreach ($breakWindows as $b) {
                $bStart = MasterBreakTime::timeToMinutes($b['start']);
                $bEnd = MasterBreakTime::timeToMinutes($b['finish']);

                if ($current >= $bStart && $current < $bEnd) {
                    $current = $bEnd;
                    $foundBreak = true;
                    break;
                }

                if ($current < $bStart && $finishWithout > $bStart) {
                    $workBefore = $bStart - $current;
                    $remaining -= $workBefore;
                    $current = $bEnd;
                    $foundBreak = true;
                    break;
                }
            }

            if (!$foundBreak) {
                $current += $remaining;
                $remaining = 0;
            }
        }

        return MasterBreakTime::minutesToTime($current);
    }

    /**
     * Estimate finish time for a production duration, considering break windows.
     * Returns the finish time string if within shift end, or null if it exceeds capacity.
     */
    public function simulateFinishTime(string $startTime, int $durationMinutes, array $breakWindows, string $shiftEndTime): ?string
    {
        $finish = $this->calculateFinishWithBreaks($startTime, $durationMinutes, $breakWindows);

        $finishMins = MasterBreakTime::timeToMinutes($finish);
        $shiftEndMins = MasterBreakTime::timeToMinutes($shiftEndTime);

        if ($finishMins <= $shiftEndMins) {
            return $finish;
        }

        return null;
    }

    public function pushIfInBreak(string $timeStr, array $breakWindows): string
    {
        $mins = MasterBreakTime::timeToMinutes($timeStr);

        foreach ($breakWindows as $b) {
            $bStart = MasterBreakTime::timeToMinutes($b['start']);
            $bEnd = MasterBreakTime::timeToMinutes($b['finish']);
            if ($mins >= $bStart && $mins < $bEnd) {
                return $b['finish'];
            }
        }

        return $timeStr;
    }

    private function matchMasterBreakForPlan(ProductionPlan $plan, array $breakWindows): ?array
    {
        $label = strtoupper($plan->job_no ?? $plan->job_master ?? '');
        foreach ($breakWindows as $b) {
            if (strtoupper($b['label'] ?? '') === $label) {
                return $b;
            }
            if (substr((string) $plan->start_time, 0, 5) === ($b['start'] ?? null)) {
                return $b;
            }
        }

        return null;
    }

    private function applyPressFilter($query, ?string $pressName): void
    {
        if (!$pressName) {
            return;
        }
        $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $pressName)));
        $query->whereRaw("
            REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
        ", ["%{$normalized}%"]);
    }

    private function legacyFixedBreaks(string $dayKey): array
    {
        $windows = [];
        if (in_array($dayKey, ['senin', 'selasa', 'rabu', 'kamis'], true)) {
            $windows[] = ['start' => '12:00', 'finish' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG'];
        }
        if ($dayKey === 'jumat') {
            $windows[] = ['start' => '11:45', 'finish' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT JUMAT'];
        }
        $windows[] = ['start' => '15:15', 'finish' => '15:30', 'type' => 'cinkorak', 'label' => 'CINGKORAK'];
        $windows[] = ['start' => '16:30', 'finish' => '16:45', 'type' => 'istirahat', 'label' => 'BREAKTIME'];
        $windows[] = ['start' => '18:00', 'finish' => '18:30', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SORE'];

        return $windows;
    }

    private function resolveDayKey(string $date, ?string $hari): string
    {
        if ($hari) {
            $normalized = strtolower(trim($hari));
            $map = [
                'monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu',
                'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu',
            ];
            if (isset($map[$normalized])) {
                return $map[$normalized];
            }
            if (in_array($normalized, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu', 'semua'], true)) {
                return $normalized;
            }
        }

        return strtolower(Carbon::parse($date)->locale('id')->isoFormat('dddd'));
    }

    private function isShiftMalam(string $shiftName): bool
    {
        $s = strtolower($shiftName);

        return str_contains($s, 'malam') || str_contains($s, '2');
    }

    private function shiftAnchorTime(string $date, string $shiftName, bool $isShiftMalam): Carbon
    {
        $config = config("shift.{$shiftName}", $isShiftMalam
            ? ['start' => '21:00', 'end' => '07:30']
            : ['start' => '07:30', 'end' => '21:00']);
        return $this->parsePlanTime($date, $config['start'], $isShiftMalam);
    }

    private function shiftEndTime(string $date, string $shiftName, bool $isShiftMalam): Carbon
    {
        $config = config("shift.{$shiftName}", $isShiftMalam
            ? ['start' => '21:00', 'end' => '07:30']
            : ['start' => '07:30', 'end' => '21:00']);
        return $this->parsePlanTime($date, $config['end'], $isShiftMalam);
    }

    private function parsePlanTime(string $date, ?string $timeStr, bool $isShiftMalam): ?Carbon
    {
        if (!$timeStr) {
            return null;
        }

        $timeStr = str_replace('.', ':', trim($timeStr));
        if (str_contains($timeStr, '-')) {
            return Carbon::parse($timeStr);
        }

        $dt = Carbon::parse($date . ' ' . $timeStr);
        if ($isShiftMalam && (int) $dt->format('H') < 12) {
            $dt->addDay();
        }

        return $dt;
    }

    private function normalizeTimeString(?string $time): string
    {
        if (!$time) {
            return '07:30';
        }

        return substr(str_replace('.', ':', trim($time)), 0, 5);
    }

    private function normalizeShiftKey(string $shiftName): string
    {
        $cleaned = preg_replace(['/\s+REV[-\s].*$/i', '/\s*\(.*?\)\s*/'], '', $shiftName);
        return trim($cleaned);
    }

    public function simulateProposedBreak(array $proposed): array
    {
        $date = $proposed['date'] ?? now()->toDateString();
        $shiftName = $proposed['shift'] ?? 'Shift Pagi';
        $hari = $proposed['hari'] ?? 'semua';
        $label = $proposed['label'] ?? 'PROPOSED';
        $start = $proposed['waktu_mulai'] ?? '12:00';
        $finish = $proposed['waktu_selesai'] ?? '12:45';
        $type = $proposed['type'] ?? 'istirahat';
        $isActive = filter_var($proposed['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $query = ProductionPlan::query()
            ->whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->whereIn('row_type', ['job', 'break'])
            ->whereNotIn('job_no', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ])
            ->whereNotIn('job_master', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ]);

        $plans = $query->orderBy('row_no')->get();
        if ($plans->isEmpty()) {
            return [];
        }

        $dayKey = $this->resolveDayKey($date, $plans->first()->hari);
        $breakWindows = $this->resolveBreakWindows($date, $shiftName, $dayKey);

        // Filter out existing proposed break to apply updated values
        $breakWindows = array_filter($breakWindows, function ($b) use ($label) {
            return strtoupper($b['label']) !== strtoupper($label);
        });

        if ($isActive) {
            $breakWindows[] = [
                'start' => substr($start, 0, 5),
                'finish' => substr($finish, 0, 5),
                'type' => $type === 'cinkorak' ? 'cinkorak' : 'istirahat',
                'label' => strtoupper($label),
            ];
        }

        usort($breakWindows, fn ($a, $b) => MasterBreakTime::timeToMinutes($a['start']) <=> MasterBreakTime::timeToMinutes($b['start']));

        $firstPlan = $plans->first();
        $startTimeStr = $firstPlan->start_time;
        if ($startTimeStr && preg_match('/^\d{2}[:\.]\d{2}/', trim($startTimeStr))) {
            $currentTimeMins = MasterBreakTime::timeToMinutes(substr(trim($startTimeStr), 0, 5));
        } else {
            $isShiftMalam = $this->isShiftMalam($shiftName);
            $shiftStart = $this->shiftAnchorTime($date, $shiftName, $isShiftMalam);
            $currentTimeMins = MasterBreakTime::timeToMinutes($shiftStart->format('H:i'));
        }

        $simulatedSequence = [];
        $processedBreaks = [];

        foreach ($plans as $itemPlan) {
            $isBreak = ($itemPlan->row_type === 'break');
            
            if ($isBreak) {
                $itemLabel = strtoupper(trim($itemPlan->job_no ?? $itemPlan->job_master ?? 'ISTIRAHAT'));
                $foundMaster = null;
                foreach ($breakWindows as $b) {
                    if (strtoupper($b['label']) === $itemLabel) {
                        $foundMaster = $b;
                        break;
                    }
                }
                
                $bStart = $foundMaster ? $foundMaster['start'] : $itemPlan->start_time;
                $bFinish = $foundMaster ? $foundMaster['finish'] : $itemPlan->finish_time;
                
                $bFinishMins = MasterBreakTime::timeToMinutes($bFinish);

                if ($foundMaster) {
                    $processedBreaks[] = $foundMaster['label'];
                }

                if ($currentTimeMins < $bFinishMins) {
                    $currentTimeMins = $bFinishMins;
                }
            } else {
                $metrics = $this->calculateJobMetrics($itemPlan, $breakWindows);
                $tptValue = $itemPlan->tpt;
                if (empty($tptValue) || (float)$tptValue <= 0) {
                    $tptValue = $metrics['tpt'];
                }
                $workMinutes = max(1, (int) round($tptValue ?? 0));

                $jobStartMins = $currentTimeMins;
                $jobStartStr = MasterBreakTime::minutesToTime($jobStartMins);

                $remainingWork = $workMinutes;
                $jobCurrentMins = $jobStartMins;

                foreach ($breakWindows as $b) {
                    $bStartMins = MasterBreakTime::timeToMinutes($b['start']);
                    $bEndMins = MasterBreakTime::timeToMinutes($b['finish']);

                    if ($jobCurrentMins < $bStartMins && !in_array($b['label'], $processedBreaks)) {
                        $availableBeforeBreak = $bStartMins - $jobCurrentMins;
                        if ($remainingWork <= $availableBeforeBreak) {
                            $jobCurrentMins += $remainingWork;
                            $remainingWork = 0;
                            break;
                        } else {
                            $remainingWork -= $availableBeforeBreak;
                            $jobCurrentMins = $bEndMins;
                        }
                    }
                }

                if ($remainingWork > 0) {
                    $jobCurrentMins += $remainingWork;
                }

                $jobFinishStr = MasterBreakTime::minutesToTime($jobCurrentMins);

                $simulatedSequence[] = [
                    'job_no' => $itemPlan->job_no,
                    'old_start' => substr((string) $itemPlan->start_time, 0, 5),
                    'old_finish' => substr((string) $itemPlan->finish_time, 0, 5),
                    'new_start' => $jobStartStr,
                    'new_finish' => $jobFinishStr,
                ];

                $currentTimeMins = $jobCurrentMins;
            }
        }

        $affected = [];
        foreach ($simulatedSequence as $s) {
            if ($s['old_start'] !== $s['new_start'] || $s['old_finish'] !== $s['new_finish']) {
                $affected[] = $s;
            }
        }

        return $affected;
    }
}
