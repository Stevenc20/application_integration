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
    public function regenerateSection(string $date, string $shiftName, ?string $pressName = null, bool $forceMaster = false): array
    {
        // Normalize date to Y-m-d format (handles "2026-05-08 00:00:00" from DB queries)
        $date = \Carbon\Carbon::parse($date)->format('Y-m-d');

        \Log::info('[REGEN START]', [
            'date' => $date,
            'shift' => $shiftName,
            'press' => $pressName,
        ]);

        // Pre-cleanup: reset orphaned recovery plans that have no valid RecoveryItem
        // so they are included as PPC items instead of being permanently filtered out
        $orphanBase = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('source_type', 'recovery');

        if ($pressName) {
            $orphanBase->where('press_name', $pressName);
        }

        $orphanBase->whereNull('recovery_id')
            ->update(['source_type' => 'ppc']);

        $orphanBase2 = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('source_type', 'recovery')
            ->whereNotNull('recovery_id')
            ->whereDoesntHave('recoveryItem', function ($q) {
                $q->whereIn('status', ['approved', 'scheduled', 'in_production', 'completed']);
            });

        if ($pressName) {
            $orphanBase2->where('press_name', $pressName);
        }

        $orphanBase2->update(['source_type' => 'ppc', 'recovery_id' => null]);

        // 2. Clean slate — remove previous computed data
        $previousChildren = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->whereNotNull('parent_job_id');
        if ($pressName) {
            $this->applyPressFilter($previousChildren, $pressName);
        }
        $previousChildren->delete();

        $parents = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->whereNotNull('original_plan')
            ->where(function ($q) {
                $q->whereNull('source_type')
                  ->orWhere('source_type', '!=', 'recovery');
            });
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

        $previousBreaks = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'break');
        if ($pressName) {
            $this->applyPressFilter($previousBreaks, $pressName);
        }
        $previousBreaks->delete();

        // Hanya reset timing recovery items (start/finish dihapus)
        $resetRecovery = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'job')
            ->where('source_type', 'recovery');
        if ($pressName) {
            $this->applyPressFilter($resetRecovery, $pressName);
        }
        $resetRecovery->update([
            'start_time'   => null,
            'finish_time'  => null,
            'process_time' => null,
            'tpt'          => null,
        ]);

        // Reset perhitungan PPC items (timing dari import dipertahankan)
        $resetPpcCalc = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('row_type', 'job')
            ->where(function ($q) {
                $q->whereNull('source_type')
                  ->orWhere('source_type', '!=', 'recovery');
            });
        if ($pressName) {
            $this->applyPressFilter($resetPpcCalc, $pressName);
        }
        $resetPpcCalc->update([
            'process_time' => null,
            'tpt'          => null,
        ]);

        // 3. Build merged queue: Recovery items first, then PPC items
        $baseConditions = function ($q) use ($date, $shiftName, $pressName) {
            $q->whereDate('plan_date', $date)
              ->where('shift_name', $shiftName)
              ->where('row_type', 'job');
            if ($pressName) {
                $this->applyPressFilter($q, $pressName);
            }
        };

        $excludeTotals = function ($q) {
            $q->whereNotIn('job_no', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'
            ])
            ->whereNotIn('job_master', [
                'TOTAL FINISH', 'TOTAL FNISH', 'FINISH', 'PLAN', 'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL',
                'JOB MASTER'
            ])
            ->whereNotNull('job_no')
            ->where('job_no', '!=', '');
        };

        // 3a. Recovery items (source_type='recovery' with valid RecoveryItem status)
        $recoveryQuery = ProductionPlan::query();
        $baseConditions($recoveryQuery);
        $excludeTotals($recoveryQuery);
        $recoveryQuery->where('source_type', 'recovery')
            ->whereHas('recoveryItem', function ($q) {
                $q->whereIn('status', ['approved', 'scheduled', 'in_production', 'completed']);
            });
        $recoveryPlans = $recoveryQuery->get();
        $recoveryJobNos = $recoveryPlans->pluck('job_no')->unique()->toArray();

        // 3b. PPC items (source_type IS NULL or != 'recovery'), excluding job_no already in recovery
        $ppcQuery = ProductionPlan::query();
        $baseConditions($ppcQuery);
        $excludeTotals($ppcQuery);
        $ppcQuery->where(function ($q) {
            $q->whereNull('source_type')
              ->orWhere('source_type', '!=', 'recovery');
        });
        if (!empty($recoveryJobNos)) {
            $ppcQuery->whereNotIn('job_no', $recoveryJobNos);
        }
        $ppcPlans = $ppcQuery->get();

        // 3c. Merge & sort by row_no — respects user's drag-drop order
        // Items with null row_no (Fase 13 orphan cleanup) sort at the end
        $plans = $recoveryPlans->concat($ppcPlans)
            ->sortBy(function ($p) {
                return (int)($p->row_no ?? 999999);
            })
            ->values();

        if ($plans->isEmpty()) {
            \Log::info('[REGEN] Aborted — no job plans found', ['date' => $date, 'shift' => $shiftName, 'press' => $pressName]);
            return ['updated' => 0, 'overflow' => []];
        }

        \Log::info('[REGEN] Plans loaded', ['date' => $date, 'shift' => $shiftName, 'press' => $pressName, 'count' => $plans->count()]);

        $firstPlan = $plans->first();
        $resolvedPressName = $pressName ?: ($firstPlan->press_name ?? 'PRESS A');
        $hari = $firstPlan->hari;

        // Resolve line_master_id dari pressName parameter, bukan dari firstPlan
        // Karena firstPlan bisa jadi recovery item dengan line_master_id yang salah (fallback 1)
        $lineMasterId = null;
        $pressMeta = null;
        if ($pressName && Schema::hasTable('line_masters')) {
            $cleanPress = strtoupper(str_replace([' ', '-', 'LINE'], '', $pressName));
            $lm = DB::table('line_masters')
                ->whereRaw('REPLACE(REPLACE(UPPER(line_name), " ", ""), "-", "") LIKE ?', ['%' . $cleanPress . '%'])
                ->orWhereRaw('REPLACE(REPLACE(UPPER(line_code), " ", ""), "-", "") LIKE ?', ['%' . $cleanPress . '%'])
                ->first(['id', 'production_start', 'production_end']);
            if ($lm) {
                $lineMasterId = $lm->id;
                $pressMeta = $lm;
            }
        }
        // Fallback: jika pressName tidak cocok, pakai line_master_id dari firstPlan
        if (!$lineMasterId) {
            $lineMasterId = $firstPlan->line_master_id;
        }
        if (!$lineMasterId && Schema::hasTable('line_masters')) {
            $lineMasterId = DB::table('line_masters')->first()->id ?? 1;
        }
        if (!$lineMasterId) {
            $lineMasterId = 1;
        }
        // Jika pressMeta belum di-set, baca dari DB berdasarkan lineMasterId
        if (!$pressMeta) {
            $pressMeta = DB::table('line_masters')
                ->where('id', $lineMasterId)
                ->first(['production_start', 'production_end']);
        }

        \Log::info('[REGEN] Press config resolved', [
            'lineMasterId' => $lineMasterId,
            'pressName' => $pressName,
            'resolvedPressName' => $resolvedPressName,
            'production_start' => $pressMeta->production_start ?? 'null',
            'production_end' => $pressMeta->production_end ?? 'null',
        ]);

        $breakWindows = $this->resolveBreakWindows($date, $shiftName, $hari);
        \Log::info('[REGEN] Break windows resolved', ['date' => $date, 'shift' => $shiftName, 'press' => $pressName, 'count' => count($breakWindows)]);
        
        // Sort breaks chronologically by start time
        usort($breakWindows, fn ($a, $b) => MasterBreakTime::timeToMinutes($a['start']) <=> MasterBreakTime::timeToMinutes($b['start']));

        $isShiftMalam = $this->isShiftMalam($shiftName);
        $shiftStart = $this->shiftAnchorTime($date, $shiftName, $isShiftMalam);
        $currentTimeMins = MasterBreakTime::timeToMinutes($shiftStart->format('H:i'));

        $shiftEnd = $this->shiftEndTime($date, $shiftName, $isShiftMalam);
        $shiftEndMins = MasterBreakTime::timeToMinutes(
            $pressMeta->production_end ?? $shiftEnd->format('H:i')
        );
        if ($isShiftMalam) {
            $shiftEndMins += 1440;
        }

        // PPC anchor: earliest start_time dari data import (untuk Press D dkk)
        // Kalo press punya production_start di line_masters, pakai itu
        // Kalo tidak, cari start_time PPC terkecil dari DB
        $ppcAnchorMins = null;
        if ($pressMeta && $pressMeta->production_start) {
            $ppcAnchorMins = MasterBreakTime::timeToMinutes($pressMeta->production_start);
        } else {
            $importedStart = ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $shiftName)
                ->where('press_name', $resolvedPressName)
                ->whereNull('source_type')
                ->whereNotNull('start_time')
                ->where('row_type', 'job')
                ->orderBy('start_time')
                ->value('start_time');
            if ($importedStart) {
                $ppcAnchorMins = MasterBreakTime::timeToMinutes($importedStart);
            }
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
            
            \Log::info("=========== ITERATION ===========\n" .
                "Index : {$i}\n" .
                "Job : {$itemPlan->job_no}\n" .
                "source_type : " . ($itemPlan->source_type ?? 'ppc') . "\n" .
                "db_start : " . ($itemPlan->start_time ?? 'NULL') . "\n" .
                "db_finish : " . ($itemPlan->finish_time ?? 'NULL') . "\n" .
                "db_process_time : " . ($itemPlan->process_time ?? 'NULL') . "\n" .
                "db_tpt : " . ($itemPlan->tpt ?? 'NULL') . "\n" .
                "cursor_before : " . MasterBreakTime::minutesToTime($currentTimeMins) . "\n" .
                "================================");
            
            // A. RE-SCAN: Insert uninserted breaks that have been passed by the cursor.
            // Skip breaks that have fully ended before or at current time (e.g., ISTIRAHAT SIANG
            // ends at 12:45 but cursor starts at 12:45+)
            $reScan = true;
            while ($reScan) {
                $reScan = false;
                foreach ($breakWindows as $idx => $b) {
                    if (in_array($idx, $insertedBreaks)) {
                        continue;
                    }
                    $bStartMins = $b['_normStart'] ?? MasterBreakTime::timeToMinutes($b['start']);
                    $bEndMins = $b['_normEnd'] ?? MasterBreakTime::timeToMinutes($b['finish']);
                    
                    // Break yang udah full lewat — skip render
                    if ($bEndMins <= $currentTimeMins) {
                        $insertedBreaks[] = $idx;
                        continue;
                    }

                    if ($bStartMins <= $currentTimeMins) {
                        $outputSequence[] = [
                            'type' => 'break',
                            'label' => $b['label'],
                            'start' => $b['start'],
                            'finish' => $b['finish'],
                            'type_break' => $b['type'],
                        ];
                        $insertedBreaks[] = $idx;
                        $currentTimeMins = max($currentTimeMins, $bEndMins);
                        $reScan = true;
                        break; // restart scan after cursor moved
                    }
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
                $tpt = $processTime + ($planQty > 0 ? $dct : 0);
            }
            
            $startTimeMins = $currentTimeMins;
            $adjustedFinishMins = $startTimeMins + $tpt;

            // Jangan mulai item setelah batas shift
            if ($startTimeMins >= $shiftEndMins) {
                $cutoffPlans[] = $itemPlan;
                $i++;
                continue;
            }
            // Jika item tidak muat penuh dalam shift → full overflow (no partial)
            if ($adjustedFinishMins >= $shiftEndMins) {
                $cutoffPlans[] = $itemPlan;
                $i++;
                continue;
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
            $outputSequence[] = [
                'type' => 'break',
                'label' => $b['label'],
                'start' => $b['start'],
                'finish' => $b['finish'],
                'type_break' => $b['type'],
            ];
            $insertedBreaks[] = $idx;
            $currentTimeMins = $bEndMins;
            continue 2;
        }

        // =====================================
        // BERAPA PCS YANG BISA DIPROSES SEBELUM BREAK
        // =====================================

        $actualProcessTime = $tpt - $dct;

        if ($actualProcessTime > 0) {
            $timeRatio = $availableProcessMinute / $actualProcessTime;
            $timeRatio = max(0, min(1, $timeRatio));
            $processableQty = floor(($planQty * $timeRatio) + 0.00001);
        } else {
            $timeRatio = 0;
            $processableQty = 0;
        }

        // JIKA TIDAK CUKUP WAKTU UNTUK 1 PCS, defer seluruh item ke setelah break
        if ($processableQty <= 0) {
            $outputSequence[] = [
                'type' => 'break',
                'label' => $b['label'],
                'start' => $b['start'],
                'finish' => $b['finish'],
                'type_break' => $b['type'],
            ];
            $insertedBreaks[] = $idx;
            $currentTimeMins = $bEndMins;
            continue 2;
        }

        // SAFETY
        $processableQty =
            max(1, min($planQty, $processableQty));

        // =====================================
        // SESSION A — sebelum break
        // =====================================

        $sessionAProcessTime =
            round($actualProcessTime * ($processableQty / max(1, $planQty)), 1);

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

        \Log::info('[REGEN] Job split (Session A)', [
            'job' => $itemPlan->job_no,
            'start' => MasterBreakTime::minutesToTime($startTimeMins),
            'finish' => $b['start'],
            'cursor_after' => $b['finish'],
        ]);

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
        // SESSION B — clone sisa item untuk setelah break
        // =====================================

        $remainingQty = $planQty - $processableQty;

        if ($remainingQty > 0) {
            $clone = $itemPlan->replicate();
            $clone->id = null;
            $clone->parent_job_id = $itemPlan->id;
            $clone->plan = $remainingQty;
            $clone->start_time = $b['finish'];

            $sessionBProcessTime = max(0, $processTime - $sessionAProcessTime);
            $sessionBTpt = ceil($sessionBProcessTime + $dct);
            $clone->process_time = $sessionBProcessTime;
            $clone->tpt = $sessionBTpt;

            \Log::info("SESSION B CREATED\n" .
                "Old Job : {$itemPlan->job_no}\n" .
                "Remaining Qty : {$remainingQty}\n" .
                "Inserted Index : {$i}\n" .
                "Current Index : {$i}");

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

            // C. Zero-qty items advance cursor by 0 minutes (no work done).
            // The re-scan in section A already handles any breaks that start at cursor.

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

            // Advance cursor to finish time (continuous, no gaps except from breaks)
            $currentTimeMins = $adjustedFinishMins;
            $i++;

            \Log::info('[REGEN] Job normal', [
                'job' => $itemPlan->job_no,
                'source_type' => $itemPlan->source_type ?? 'ppc',
                'start' => $jobStartStr,
                'finish' => $jobFinishStr,
                'cursor_after' => MasterBreakTime::minutesToTime($currentTimeMins),
            ]);
        }

        // Post-loop: Insert any remaining break windows at their correct chronological position
        foreach ($breakWindows as $idx => $b) {
            if (in_array($idx, $insertedBreaks)) {
                continue;
            }
            $bStart = $b['start'];
            $bStartMins = $b['_normStart'] ?? MasterBreakTime::timeToMinutes($bStart);
            $inserted = false;
            for ($pos = 0; $pos < count($outputSequence); $pos++) {
                $item = $outputSequence[$pos];
                if ($item['type'] === 'break') {
                    continue;
                }
                $itemFinish = $item['finish'];
                if ($itemFinish && MasterBreakTime::timeToMinutes($itemFinish) > $bStartMins) {
                    array_splice($outputSequence, $pos, 0, [[
                        'type' => 'break',
                        'label' => $b['label'],
                        'start' => $b['start'],
                        'finish' => $b['finish'],
                        'type_break' => $b['type'],
                    ]]);
                    $inserted = true;
                    $insertedBreaks[] = $idx;
                    break;
                }
            }
            if (!$inserted) {
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

        $jobCount = count(array_filter($outputSequence, fn ($o) => $o['type'] === 'job'));
        $breakCount = count(array_filter($outputSequence, fn ($o) => $o['type'] === 'break'));
        \Log::info('[REGEN] Output sequence built', ['date' => $date, 'shift' => $shiftName, 'press' => $pressName, 'total' => count($outputSequence), 'jobs' => $jobCount, 'breaks' => $breakCount]);

        // Sequence is already in processing order (Excel row order), which is the desired
        // user-facing order.  Breaks are inserted inline when they overlap, so no re-sort needed.
        // The old usort() by start time was removed because it scrambled the Excel row order.


        // 2c. Cutoff items (start >= shiftEnd) → null timestamps & collect overflow for alert
        $overflow = [];
        if (!empty($cutoffPlans)) {
            foreach ($cutoffPlans as $cp) {
                $cp->start_time = null;
                $cp->finish_time = null;
                $cp->save();

                $planQty = (float)($cp->plan ?? 0);
                $okQty = (float)($cp->ok ?? 0);
                $repairQty = (float)($cp->repair ?? 0);
                $rejectQty = (float)($cp->reject ?? 0);
                $balance = max(0, $planQty - $okQty - $repairQty - $rejectQty);
                $recoveryQty = max(0, $planQty - $okQty);
                $ct = (float)($cp->ct_detik ?? 0);
                $durationMinutes = $ct > 0 ? (int)ceil(($ct * $recoveryQty) / 60.0) : 0;

                $overflow[] = [
                    'plan_id'         => $cp->id,
                    'job_no'          => $cp->job_no,
                    'job_master'      => $cp->job_master,
                    'plan'            => $planQty,
                    'ok'              => $okQty,
                    'repair'          => $repairQty,
                    'reject'          => $rejectQty,
                    'balance'         => $balance,
                    'recovery_qty'    => $recoveryQty,
                    'duration_minutes' => $durationMinutes,
                    'position'        => $cp->row_no,
                ];
            }
        }

        \Log::info('[REGEN] Persisting output sequence', ['date' => $date, 'shift' => $shiftName, 'press' => $pressName, 'rows' => count($outputSequence)]);

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

                    // Recovery rows: DELETE old placeholder + INSERT fresh (new ID, clean state)
                    // PPC rows: UPDATE existing record (preserve original ID)
                    $saveType = ($plan->source_type === 'recovery' && $plan->id)
                        ? 'DELETE+INSERT'
                        : ($plan->id ? 'UPDATE' : 'INSERT');
                    $logData = [
                        'job_no' => $plan->job_no ?? $item['label'],
                        'source_type' => $plan->source_type ?? 'ppc',
                        'row_type' => $item['type'],
                        'save_type' => $saveType,
                        'start' => $item['start'],
                        'finish' => $item['finish'],
                        'row_no' => $rowNo - 1,
                        'recovery_id' => $plan->recovery_id,
                    ];
                    if ($plan->source_type === 'recovery' && $plan->id) {
                        $logData['old_id'] = $plan->id;
                        $oldId = $plan->id;
                        $plan->id = null;
                        $plan->exists = false;
                        ProductionPlan::where('id', $oldId)->where('source_type', 'recovery')->delete();
                    }
                    $plan->save();
                    if ($plan->source_type === 'recovery') {
                        $logData['new_id'] = $plan->id;
                    }
                    \Log::info('[REGEN TRACE PERSIST]', $logData);
                }
                $updated++;
            }

        });

        // 2d. Recovery items that couldn't be scheduled: revert to waiting_approval queue
        // OR reset orphaned plans (source_type='recovery' but RecoveryItem already reverted)
        // so they re-enter the timeline as PPC items on next regeneration
        $unusedRecovery = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('press_name', $resolvedPressName)
            ->where('source_type', 'recovery')
            ->whereNull('start_time')
            ->get();

        foreach ($unusedRecovery as $ur) {
            if ($ur->recovery_id) {
                $affected = RecoveryItem::where('id', $ur->recovery_id)
                    ->whereIn('status', ['approved', 'scheduled'])
                    ->update(['status' => 'waiting_approval']);

                if ($affected > 0) {
                    \Log::info('[REGEN UNUSED RECOVERY] Reverted to waiting_approval', [
                        'plan_id' => $ur->id,
                        'job_no' => $ur->job_no,
                        'recovery_item_id' => $ur->recovery_id,
                        'row_no' => $ur->row_no,
                    ]);
                }

                if ($affected === 0) {
                    $ur->update(['source_type' => 'ppc', 'recovery_id' => null]);
                }
            } else {
                // No recovery_id — reset to PPC to avoid orphan
                $ur->update(['source_type' => 'ppc', 'recovery_id' => null]);
            }
        }

        // 2e. Cleanup PPC originals that match recovery job_no — set row_no to NULL
        // so they don't create duplicate row_no conflicts in the view sort order
        $recoveryJobNos = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('press_name', $resolvedPressName)
            ->where('source_type', 'recovery')
            ->whereNotNull('recovery_id')
            ->pluck('job_no')
            ->unique()
            ->toArray();

        if (!empty($recoveryJobNos)) {
            ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $shiftName)
                ->where('press_name', $resolvedPressName)
                ->where(function ($q) {
                    $q->whereNull('source_type')->orWhere('source_type', '!=', 'recovery');
                })
                ->whereIn('job_no', $recoveryJobNos)
                ->whereNull('start_time')
                ->update(['row_no' => null]);
        }

        \Log::info('[REGEN END]');
        \Log::info('[REGEN] Complete', ['date' => $date, 'shift' => $shiftName, 'press' => $pressName, 'updated' => $updated, 'overflow_count' => count($overflow)]);

        return ['updated' => $updated, 'overflow' => $overflow];
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

        \Log::info('[TRACE BREAK DB]', [
            'date' => $date,
            'shiftName' => $shiftName,
            'hari_raw' => $hari,
            'dayKey' => $dayKey,
        ]);

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

        // Fallback to legacy fixed breaks when no DB entries match this shift
        if (empty($windows)) {
            $legacy = $this->legacyFixedBreaks($dayKey);
            \Log::info("resolveBreakWindows: DB empty for {$date} {$shiftName} ({$dayKey}), using legacy: " . count($legacy) . " breaks");
            return $legacy;
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
        $query->where(function ($q) use ($pressName) {
            // (a) Direct fuzzy match on raw press_name
            $q->whereRaw('UPPER(TRIM(press_name)) LIKE ?', ['%' . strtoupper(trim($pressName)) . '%']);

            // (b) Fallback via line_master_id — resolves mismatched formats
            //     e.g. filter 'PA' should match DB row with press_name 'PRESS A'
            //     when both share the same line_master_id via LineMaster.
            $lmIds = \App\Models\LineMaster::where(function ($lm) use ($pressName) {
                $lm->where('line_code', 'like', '%' . $pressName . '%')
                   ->orWhere('line_name', 'like', '%' . $pressName . '%');
            })->pluck('id');
            if ($lmIds->isNotEmpty()) {
                $q->orWhereIn('line_master_id', $lmIds->toArray());
            }
        });
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

    private function normalizeDayName(string $day): string
    {
        $normalized = strtolower(trim($day));
        $normalized = preg_replace('/\s+(pagi|malam|shift\s*(pagi|malam)?)$/i', '', $normalized);
        $enToId = [
            'monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu',
            'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu',
        ];
        if (isset($enToId[$normalized])) {
            return $enToId[$normalized];
        }
        if (in_array($normalized, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu', 'semua'], true)) {
            return $normalized;
        }
        return $normalized;
    }

    private function resolveDayKey(string $date, ?string $hari): string
    {
        if ($hari) {
            return $this->normalizeDayName($hari);
        }

        $carbonDay = strtolower(Carbon::parse($date)->locale('id')->isoFormat('dddd'));
        $resolved = $this->normalizeDayName($carbonDay);

        return $resolved;
    }

    private function isShiftMalam(string $shiftName): bool
    {
        $s = strtolower($shiftName);

        return str_contains($s, 'malam') || str_contains($s, '2');
    }

    private function shiftAnchorTime(string $date, string $shiftName, bool $isShiftMalam): Carbon
    {
        $config = config("shift.{$shiftName}", $isShiftMalam
            ? ['start' => '21:30', 'end' => '07:30']
            : ['start' => '07:30', 'end' => '21:30']);
        return $this->parsePlanTime($date, $config['start'], $isShiftMalam);
    }

    private function shiftEndTime(string $date, string $shiftName, bool $isShiftMalam): Carbon
    {
        $config = config("shift.{$shiftName}", $isShiftMalam
            ? ['start' => '21:30', 'end' => '07:30']
            : ['start' => '07:30', 'end' => '21:30']);
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

    /**
     * Validate a section's timeline for consistency.
     * Returns an array of error messages (empty = valid).
     */
    public function validateTimeline(string $date, string $shiftName, string $pressName): array
    {
        $errors = [];

        $plans = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shiftName)
            ->where('press_name', $pressName)
            ->whereIn('row_type', ['job', 'break'])
            ->orderBy('row_no')
            ->get();

        if ($plans->isEmpty()) {
            return [];
        }

        $prevFinish = null;
        $recoveryIds = [];
        $totalPlanQtyBefore = 0;
        $totalPlanQtyAfter = 0;
        $jobCount = 0;

        foreach ($plans as $plan) {
            if ($plan->row_type === 'job') {
                $jobCount++;

                // Track total plan qty (excluding split children)
                if (!$plan->parent_job_id) {
                    $totalPlanQtyAfter += (float)($plan->plan ?? 0);
                }

                // No time overlap
                if ($plan->start_time && $prevFinish !== null) {
                    $startMins = MasterBreakTime::timeToMinutes(substr($plan->start_time, 0, 5));
                    if ($startMins < $prevFinish) {
                        $errors[] = "Time overlap at row {$plan->row_no}: start {$plan->start_time} < previous finish " . MasterBreakTime::minutesToTime($prevFinish);
                    }
                }

                // No duplicate recovery
                if ($plan->recovery_id) {
                    if (in_array($plan->recovery_id, $recoveryIds)) {
                        $errors[] = "Duplicate recovery_id {$plan->recovery_id} at row {$plan->row_no}";
                    }
                    $recoveryIds[] = $plan->recovery_id;

                    // No locked recovery in timeline
                    $recoveryItem = RecoveryItem::find($plan->recovery_id);
                    if ($recoveryItem && $recoveryItem->status === 'in_production') {
                        $errors[] = "Locked recovery item {$plan->recovery_id} (IN_PRODUCTION) appears in timeline at row {$plan->row_no}";
                    }
                }

                // Finish time within shift
                if ($plan->start_time && $plan->finish_time) {
                    $finishMins = MasterBreakTime::timeToMinutes(substr($plan->finish_time, 0, 5));
                    $shiftEndTime = $this->shiftEndTime($date, $shiftName, $this->isShiftMalam($shiftName));
                    $shiftEndMins = MasterBreakTime::timeToMinutes($shiftEndTime->format('H:i'));
                    if ($this->isShiftMalam($shiftName)) {
                        $shiftEndMins += 1440;
                    }
                    if ($finishMins > $shiftEndMins) {
                        $errors[] = "Finish time {$plan->finish_time} exceeds shift end at row {$plan->row_no}";
                    }

                    $prevFinish = $finishMins;
                }

                // Track finish for non-overlap check
                if ($plan->start_time && $plan->finish_time) {
                    $prevFinish = MasterBreakTime::timeToMinutes(substr($plan->finish_time, 0, 5));
                }
            } elseif ($plan->row_type === 'break') {
                // Break start must be before finish
                if ($plan->start_time && $plan->finish_time) {
                    $bStart = MasterBreakTime::timeToMinutes(substr($plan->start_time, 0, 5));
                    $bFinish = MasterBreakTime::timeToMinutes(substr($plan->finish_time, 0, 5));
                    if ($bStart >= $bFinish) {
                        $errors[] = "Break '{$plan->job_no}' at row {$plan->row_no} has invalid times: start {$plan->start_time} >= finish {$plan->finish_time}";
                    }
                }
            }
        }

        // Total qty match (only if we have a reference point — skipped for now)
        return $errors;
    }
}


