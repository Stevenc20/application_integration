<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\BreakTime;
use App\Models\Dandori;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use App\Services\ProductionMetricsService;
use App\Exports\LkhActualExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailyProduction(Request $request)
    {
        // 1. Line Filters (Flexible & Normalization)
        $lineNamesUnique = \App\Models\LineMaster::select('line_name')->distinct()->pluck('line_name');
        
        $selectedLineName = $request->get('line', 'Line A');
        if (str_contains($selectedLineName, '-')) {
            $selectedLineName = ucwords(str_replace('-', ' ', $selectedLineName));
        }

        // Normalize selectedLineName to match actual line_name in the database for active styling
        $normSelected = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $selectedLineName)));
        foreach ($lineNamesUnique as $lmName) {
            $lmNorm = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lmName)));
            if ($lmNorm === $normSelected) {
                $selectedLineName = $lmName;
                break;
            }
        }

        // 2. Date Filter
        $date = $request->get('date');
        if (!$date) {
            $hour = (int) now()->format('H');
            $date = ($hour < 7) ? now()->subDay()->toDateString() : now()->toDateString();
        } else {
            $date = \Carbon\Carbon::parse($date)->toDateString();
        }

        // 3. Shift Filter
        $selectedShift = $request->get('shift', 'Shift Pagi');

        // Find the latest revision shift name if applicable
        $latestShiftName = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', 'like', "{$selectedShift}%")
            ->orderByDesc('updated_at')
            ->value('shift_name') ?: $selectedShift;

        // 4. Query ProductionPlan for schedule
        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->visibleOnTimeline();

        if ($latestShiftName !== 'all') {
            $planQuery->where('shift_name', $latestShiftName);
        }

        // Normalisasi Line Filter
        if ($selectedLineName && strtoupper($selectedLineName) !== 'ALL') {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $selectedLineName)));
            $planQuery->whereRaw("
                REPLACE(
                    REPLACE(
                        UPPER(TRIM(press_name)),
                        'PRESS ',
                        ''
                    ),
                    'LINE ',
                    ''
                ) LIKE ?
            ", ["%{$normalized}%"]);
        }

        $plans = $planQuery->orderBy('press_name')->orderBy('row_no', 'asc')->get();

        // Validate and filter out invalid/orphan/duplicate breaks in memory using BreakTimelineValidator
        $plans = app(\App\Services\BreakTimelineValidator::class)->filterValidPlans($plans);

        // LKH mirrors production_plans (PPC master) — no separate timeline engine
        $plans = $plans->sortBy(fn ($p) => $p->row_no ?? PHP_INT_MAX)->values();

        // ── MERGE BREAK SPLITS: exclude children, load their data separately ──
        $childPlans = \App\Models\ProductionPlan::whereIn('parent_job_id', $plans->pluck('id')->filter())
            ->whereDate('plan_date', $date)
            ->where('shift_name', $latestShiftName)
            ->get()
            ->groupBy('parent_job_id');

        $plans = $plans->filter(fn ($p) => !$p->parent_job_id)->values();

        // 5. Map plan to JobMaster with DailyProduction and Downtimes
        $jobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();

        // Also include children's identifiers for loading their JobMasters
        foreach ($childPlans as $parentId => $children) {
            foreach ($children as $child) {
                $jn = trim($child->job_no ?? '');
                $jm = trim($child->job_master ?? '');
                $jobNumbers[] = $jn ? ($jn . '-' . $child->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $child->id);
            }
        }

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with(['dailyProduction', 'downtimes', 'qChecks'])
            ->get()
            ->keyBy('job_number');

        // Merge children's production data into parent's JobMaster
        foreach ($childPlans as $parentId => $children) {
            $parent = $plans->firstWhere('id', $parentId);
            if (!$parent) continue;
            $parentJm = $jobMasters->get(trim($parent->job_no ?? '') . '-' . $parent->id);
            if (!$parentJm) continue;
            foreach ($children as $child) {
                $childKey = trim($child->job_no ?? '') . '-' . $child->id;
                $childJm = $jobMasters->get($childKey);
                if (!$childJm || !$childJm->dailyProduction) continue;
                if (!$parentJm->dailyProduction) continue;
                $parentJm->dailyProduction->actual_ok     = ($parentJm->dailyProduction->actual_ok ?? 0) + ($childJm->dailyProduction->actual_ok ?? 0);
                $parentJm->dailyProduction->actual_repair = ($parentJm->dailyProduction->actual_repair ?? 0) + ($childJm->dailyProduction->actual_repair ?? 0);
                $parentJm->dailyProduction->actual_reject = ($parentJm->dailyProduction->actual_reject ?? 0) + ($childJm->dailyProduction->actual_reject ?? 0);
                // Merge downtimes too
                foreach ($childJm->downtimes ?? [] as $dt) {
                    $parentJm->downtimes->push($dt);
                }
            }
        }

        // Preload dandoris for this date grouped by job master id
        $dandoriByJob = Dandori::whereIn('next_job_id', $jobMasters->pluck('id'))
            ->whereDate('work_date', $date)
            ->get()
            ->groupBy('next_job_id');

        // Preload all breaks - Prioritize PPC planning break rows, fallback to BreakTime table
        $ppcBreaksQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'break');
        if ($latestShiftName !== 'all') {
            $ppcBreaksQuery->where('shift_name', $latestShiftName);
        }
        if ($selectedLineName && strtoupper($selectedLineName) !== 'ALL') {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $selectedLineName)));
            $ppcBreaksQuery->whereRaw("
                REPLACE(
                    REPLACE(
                        UPPER(TRIM(press_name)),
                        'PRESS ',
                        ''
                    ),
                    'LINE ',
                    ''
                ) LIKE ?
            ", ["%{$normalized}%"]);
        }
        $ppcBreaks = $ppcBreaksQuery->get();

        $allBreaks = collect();
        if ($ppcBreaks->isNotEmpty()) {
            foreach ($ppcBreaks as $pb) {
                if (!empty($pb->start_time) && !empty($pb->finish_time)) {
                    $allBreaks->push((object)[
                        'waktu_mulai' => $pb->start_time,
                        'waktu_selesai' => $pb->finish_time,
                        'shift' => $pb->shift_name
                    ]);
                }
            }
        }
        if ($allBreaks->isEmpty()) {
            $allBreaks = \App\Models\BreakTime::all();
        }

        $isShiftMalam = str_contains(strtolower($latestShiftName), 'malam') || str_contains(strtolower($latestShiftName), '2');

        // Unified Time Parser handling Shift Malam midnight crossover
        $parseTime = function($timeStr) use ($date, $isShiftMalam) {
            if (!$timeStr) return null;
            $timeStr = str_replace('.', ':', trim($timeStr));
            if (str_contains($timeStr, '-')) {
                return \Carbon\Carbon::parse($timeStr);
            }
            $dt = \Carbon\Carbon::parse($date . ' ' . $timeStr);
            if ($isShiftMalam) {
                $hour = (int)$dt->format('H');
                if ($hour < 12) {
                    $dt->addDay();
                }
            }
            return $dt;
        };

        // Shift starts (anchor for plan time parsing)
        $shiftStartStr = $isShiftMalam ? '21:00' : '07:40';
        $shiftStart = $parseTime($shiftStartStr);

        // Shift display boundaries for timeline visual
        $shiftDisplayStart = $parseTime($isShiftMalam ? '21:00' : '07:30');
        $shiftDisplayEnd = $parseTime($isShiftMalam ? '07:30' : '21:00');

        $planRunningTime = $shiftStart->copy();
        $actualRunningTime = $shiftStart->copy();

        $listOfCycleTimes = [];
        $totalActualStrokeFinished = 0;
        $totalActualTptMinutesFinished = 0.0;


        $totals = [
            'plan_qty' => 0,
            'actual_good' => 0,
            'actual_repair' => 0,
            'actual_reject' => 0,
            'total_stroke' => 0,
            'total_loading_time' => 0.0,
            'total_operating_time' => 0.0,
            'press_time' => 0.0,
            'total_dandori' => 0.0,
            'total_qcheck' => 0.0,
            'downtime_total' => 0.0,
            'total_report_tpt' => 0.0,
            'total_panel_record_ct' => 0.0,
            'total_break_time' => 0.0,
            'total_work_time' => 0.0,
            'downtime_prod' => 0.0,
            'downtime_dies' => 0.0,
            'downtime_mach' => 0.0,
            'downtime_matl' => 0.0,
            'downtime_log' => 0.0,
            'downtime_ubp' => 0.0,
            'tpt_plan' => 0.0,
            'tpt_act' => 0.0,
        ];

        $jobsData = [];
        $jobDisplayNo = 0;
        $kebMtlByPlanId = $this->buildKebMtlGroupTotals($plans);

        // Helper to calculate finish time taking breaks into account (High-Fidelity Industrial Cascade)
        $calculateFinishTime = function($startTime, $durationMinutes, $allBreaks, $isShiftMalam) use ($parseTime) {
            if (!$startTime) return null;
            
            $currentTimeMins = ($startTime->hour * 60) + $startTime->minute;
            if ($isShiftMalam && $startTime->hour < 7) {
                $currentTimeMins += 24 * 60;
            }
            
            $remainingWork = (int) round($durationMinutes);
            $jobCurrentMins = $currentTimeMins;
            
            // Build break windows in minutes
            $breakWindowsMins = [];
            foreach ($allBreaks as $br) {
                $brShiftMalam = str_contains(strtolower($br->shift ?? ''), 'malam') || str_contains(strtolower($br->shift ?? ''), '2');
                if ($brShiftMalam == $isShiftMalam) {
                    $bStart = $parseTime($br->waktu_mulai);
                    $bEnd = $parseTime($br->waktu_selesai);
                    if ($bStart && $bEnd) {
                        $bStartMin = ($bStart->hour * 60) + $bStart->minute;
                        if ($isShiftMalam && $bStart->hour < 7) $bStartMin += 24 * 60;
                        
                        $bEndMin = ($bEnd->hour * 60) + $bEnd->minute;
                        if ($isShiftMalam && $bEnd->hour < 7) $bEndMin += 24 * 60;
                        
                        $breakWindowsMins[] = [
                            'start' => $bStartMin,
                            'finish' => $bEndMin
                        ];
                    }
                }
            }
            
            // Sort breaks chronologically
            usort($breakWindowsMins, fn($a, $b) => $a['start'] <=> $b['start']);
            
            foreach ($breakWindowsMins as $b) {
                if ($jobCurrentMins < $b['start']) {
                    $availableBeforeBreak = $b['start'] - $jobCurrentMins;
                    if ($remainingWork <= $availableBeforeBreak) {
                        $jobCurrentMins += $remainingWork;
                        $remainingWork = 0;
                        break;
                    } else {
                        $remainingWork -= $availableBeforeBreak;
                        $jobCurrentMins = $b['finish'];
                    }
                } elseif ($jobCurrentMins >= $b['start'] && $jobCurrentMins < $b['finish']) {
                    // If start time falls inside the break, push start time to the end of the break
                    $jobCurrentMins = $b['finish'];
                }
            }
            
            if ($remainingWork > 0) {
                $jobCurrentMins += $remainingWork;
            }
            
            // Convert jobCurrentMins back to Carbon object
            $finishTime = $startTime->copy();
            $targetHour = (int) floor($jobCurrentMins / 60);
            $targetMinute = (int) ($jobCurrentMins % 60);
            
            if ($isShiftMalam && $targetHour >= 24) {
                $targetHour -= 24;
                if ($startTime->hour >= 12) {
                    $finishTime->addDay();
                }
            }
            
            $finishTime->setTime($targetHour, $targetMinute, 0);
            return $finishTime;
        };

        // ── MERGE CONSECUTIVE SAME-JOB PLANS INTO ONE ──
        $mergedPlans = collect();
        $buffer = null;
        foreach ($plans as $plan) {
            if (($plan->row_type ?? 'job') === 'break') {
                if ($buffer) { $mergedPlans->push($buffer); $buffer = null; }
                $mergedPlans->push($plan);
                continue;
            }
            $currentJobNo = trim($plan->job_no ?? '');
            if ($buffer && $currentJobNo === trim($buffer->job_no ?? '')) {
                $buffer->plan = ($buffer->plan ?? 0) + ($plan->plan ?? $plan->target_qty ?? 0);
                $buffer->target_qty = ($buffer->target_qty ?? 0) + ($plan->target_qty ?? 0);
                if ($plan->start_time && (!$buffer->start_time || $plan->start_time < $buffer->start_time))
                    $buffer->start_time = $plan->start_time;
                if ($plan->finish_time && (!$buffer->finish_time || $plan->finish_time > $buffer->finish_time))
                    $buffer->finish_time = $plan->finish_time;
                if ($plan->act_start && (!$buffer->act_start || $plan->act_start < $buffer->act_start))
                    $buffer->act_start = $plan->act_start;
                if ($plan->act_finish && (!$buffer->act_finish || $plan->act_finish > $buffer->act_finish))
                    $buffer->act_finish = $plan->act_finish;
            } else {
                if ($buffer) $mergedPlans->push($buffer);
                $buffer = clone $plan;
            }
        }
        if ($buffer) $mergedPlans->push($buffer);
        $plans = $mergedPlans;

        $jobDisplayNo = 0;
        $previousActFinish = null;

        foreach ($plans as $plan) {
            if (($plan->row_type ?? 'job') === 'break') {
                $planStart = $parseTime($plan->start_time);
                $planFinish = $parseTime($plan->finish_time);

                $breakDuration = 0.0;
                if ($planStart && $planFinish && $planFinish->gt($planStart)) {
                    $breakDuration = abs($planFinish->diffInMinutes($planStart));
                }

                $jobsData[] = [
                    'row_type' => 'break',
                    'break_label' => $this->resolveBreakLabel($plan),
                    'job_master' => $plan->job_master ?: $this->resolveBreakLabel($plan),
                    'job_no' => $plan->job_no ?? '',
                    'schedule_start' => $planStart,
                    'schedule_finish' => $planFinish,
                    'keterangan' => $plan->keterangan ?? '',
                ];

                if ($planFinish) {
                    // Advance plan running time to schedule finish of the break
                    if ($planRunningTime->lt($planFinish)) {
                        $planRunningTime = $planFinish->copy();
                    }
                    
                    // Advance actual running time ONLY if the preceding job finished after the break started
                    if ($planStart && $actualRunningTime->gt($planStart)) {
                        if ($actualRunningTime->lt($planFinish)) {
                            $actualRunningTime = $planFinish->copy();
                        }
                    }
                }

                $totals['total_break_time'] += $breakDuration;
                continue;
            }

            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);
            $jobData = $jobMasters->get($identifier);

            $actualQtySafe = $jobData && $jobData->dailyProduction ? ($jobData->dailyProduction->actual_ok ?? 0) : 0;
            $actualRepairSafe = $jobData && $jobData->dailyProduction ? ($jobData->dailyProduction->actual_repair ?? 0) : 0;
            $actualRejectSafe = $jobData && $jobData->dailyProduction ? ($jobData->dailyProduction->actual_reject ?? 0) : 0;
            $cycleTimeSafe = floatval($plan->ct_detik ?? 0.0);
            $planQtySafe = intval($plan->plan ?? $plan->target_qty ?? 0);

            $dandoriTime = 0.0;
            $downtimeTime = 0.0;
            $dtBreakdown = ['prod_t' => 0.0, 'dies_t' => 0.0, 'mach_t' => 0.0, 'mat_t' => 0.0, 'log_t' => 0.0, 'ubp_t' => 0.0];

            $qcheckTime = 0.0;
            $diesChangeTime = 0.0;
            $variantChangeTime = 0.0;

            if ($jobData) {
                $jobDandoris = $dandoriByJob->get($jobData->id);
                if ($jobDandoris) {
                    // Dies & Variant = all dandori time except 1st_check (the yellow bar in Input Harian)
                    $diesChangeTime = round($jobDandoris->where('jenis_dandori', '!=', '1st_check')->sum('duration_minutes'), 1);
                    $variantChangeTime = 0.0;
                }
                $downtimes = $jobData->downtimes;
                $breakdown = ProductionMetricsService::downtimeBreakdown($downtimes);
                $downtimeTime = $breakdown['total'];
                $confirmedDowntimes = $downtimes->filter(fn($dt) => !in_array(trim($dt->problem ?? ''), ['', '-']));
                $breakdownConfirmed = ProductionMetricsService::downtimeBreakdown($confirmedDowntimes);
                $dtBreakdown = [
                    'prod_t' => $breakdownConfirmed['production'],
                    'dies_t' => $breakdownConfirmed['dies'],
                    'mach_t' => $breakdownConfirmed['machine'],
                    'mat_t' => $breakdownConfirmed['material'],
                    'log_t' => $breakdownConfirmed['logistic'],
                    'ubp_t' => $breakdownConfirmed['ubp'],
                ];
                $qcheckTime = $jobData->total_qcheck_minutes;
                if ($jobDandoris) {
                    $qcheckTime += round($jobDandoris->where('jenis_dandori', '1st_check')->sum('duration_minutes'), 1);
                }
                $dandoriTime = $diesChangeTime + $variantChangeTime + $qcheckTime;
            }

            $jobDisplayNo++;

            // Schedule — exact copy from PPC production_plans
            $planStart = $parseTime($plan->start_time);
            $planFinish = $parseTime($plan->finish_time);

            // Retrieve actual times from ProductionPlan (PPC) act_start/act_finish, falling back to JobMaster
            $actStart = $plan->act_start ? $parseTime($plan->act_start) : (($jobData && $jobData->started_at) ? \Carbon\Carbon::parse($jobData->started_at) : null);
            
            $actFinish = null;
            if ($plan->act_finish) {
                $actFinish = $parseTime($plan->act_finish);
            } else {
                if ($jobData) {
                    if ($jobData->finished_at) {
                        $actFinish = \Carbon\Carbon::parse($jobData->finished_at);
                    } elseif (in_array(strtolower($jobData->status), ['complete', 'finished', 'closed'])) {
                        $actFinish = \Carbon\Carbon::parse($jobData->updated_at);
                    }
                }
            }

            // Filter downtimes to only those overlapping actual production window [actStart, actFinish]
            // Keep unfiltered copy for timeline-based press time calculation (needs all history entries for anchor/deadline)
            $timelineDowntimes = $downtimes ?? collect();
            if (isset($downtimes) && $actStart && $actFinish && $actFinish->gt($actStart)) {
                $downtimes = $downtimes->filter(function($dt) use ($actStart, $actFinish) {
                    $dtStart = $dt->start_time ? \Carbon\Carbon::parse($dt->start_time) : null;
                    $dtEnd = $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time) : null;
                    if (!$dtStart) return false;
                    if (!$dtEnd) return $dtStart->lt($actFinish);
                    return $dtStart->lt($actFinish) && $dtEnd->gte($actStart);
                });
                $dandoriTime = $diesChangeTime + $variantChangeTime + $qcheckTime;
                $breakdown = ProductionMetricsService::downtimeBreakdown($downtimes);
                $downtimeTime = $breakdown['total'];
                $confirmedDowntimes = $downtimes->filter(fn($dt) => !in_array(trim($dt->problem ?? ''), ['', '-']));
                $breakdownConfirmed = ProductionMetricsService::downtimeBreakdown($confirmedDowntimes);
                $dtBreakdown = [
                    'prod_t' => $breakdownConfirmed['production'],
                    'dies_t' => $breakdownConfirmed['dies'],
                    'mach_t' => $breakdownConfirmed['machine'],
                    'mat_t' => $breakdownConfirmed['material'],
                    'log_t' => $breakdownConfirmed['logistic'],
                    'ubp_t' => $breakdownConfirmed['ubp'],
                ];
            }

            // Plan Break overlapping calculation
            $planBreakDuration = 0.0;
            $breakBaseStart = $planStart;
            $breakBaseEnd = $planFinish;
            if ($breakBaseStart && $breakBaseEnd && $breakBaseEnd->gt($breakBaseStart)) {
                foreach ($allBreaks as $br) {
                    $brShiftMalam = str_contains(strtolower($br->shift ?? ''), 'malam') || str_contains(strtolower($br->shift ?? ''), '2');
                    if ($brShiftMalam == $isShiftMalam) {
                        $breakStart = $parseTime($br->waktu_mulai);
                        $breakEnd = $parseTime($br->waktu_selesai);
                        
                        if ($breakStart && $breakEnd) {
                            $overlapStart = $breakBaseStart->gt($breakStart) ? $breakBaseStart : $breakStart;
                            $overlapEnd = $breakBaseEnd->lt($breakEnd) ? $breakBaseEnd : $breakEnd;

                            if ($overlapEnd->gt($overlapStart)) {
                                $planBreakDuration += abs($overlapEnd->diffInSeconds($overlapStart)) / 60.0;
                            }
                        }
                    }
                }
            }
            $planBreakDuration = max(0.0, $planBreakDuration);

            // Plan TPT = PL Finish - PL Start (planned duration from PPC)
            $planTpt = 0.0;
            if ($planStart && $planFinish && $planFinish->gt($planStart)) {
                $planTpt = abs($planFinish->diffInMinutes($planStart));
            }
            $planGsph = ProductionMetricsService::planGsphStored($plan);
            $planCt = ProductionMetricsService::planCt($cycleTimeSafe, $planTpt, $planQtySafe);
            $effectivePlanCt = $cycleTimeSafe > 0 ? $cycleTimeSafe : $planCt;
            $storedProcessTime = (float) ($plan->process_time ?? 0);
            $engineProcessTime = ProductionMetricsService::calculateProcessTime($effectivePlanCt, $planQtySafe);
            $planProcessTime = $engineProcessTime > 0 ? $engineProcessTime : $storedProcessTime;

            if ($engineProcessTime > 0 && abs($storedProcessTime - $engineProcessTime) > 0.001) {
                if ($cycleTimeSafe <= 0 && $effectivePlanCt > 0) {
                    $plan->ct_detik = $effectivePlanCt;
                }
                $plan->process_time = $engineProcessTime;
                $plan->tpt = ProductionMetricsService::planTptMinutes($plan);
                $plan->gsph_item = ProductionMetricsService::gsph($planQtySafe, (float) $plan->tpt);
                $plan->save();

                $plan->refresh();
                $planTpt = 0.0;
                if ($planStart && $planFinish && $planFinish->gt($planStart)) {
                    $planTpt = abs($planFinish->diffInMinutes($planStart));
                }
                $planGsph = ProductionMetricsService::planGsphStored($plan);
                $planCt = ProductionMetricsService::planCt((float) ($plan->ct_detik ?? 0), $planTpt, $planQtySafe);
                $planProcessTime = (float) $plan->process_time;
            }

            if ($planCt > 0) {
                $listOfCycleTimes[] = $planCt;
            }

            // total_stroke = good + repair + reject
            $totalStroke = $actualQtySafe + $actualRepairSafe + $actualRejectSafe;

            $itemFinishActual = $calculateFinishTime($actualRunningTime, max($planTpt, 1), $allBreaks, $isShiftMalam);

            // Calculate Actual Break duration from real break downtime records (not scheduled)
            $jobBreakDuration = 0.0;
            if (isset($downtimes)) {
                $now = \Carbon\Carbon::now();
                foreach ($downtimes->filter(fn($dt) => str_contains(strtolower($dt->jenis_downtime ?? ''), 'break')) as $bdt) {
                    $secs = !empty($bdt->duration_seconds) ? (float)$bdt->duration_seconds
                        : ($bdt->finish_time
                            ? (float)abs(\Carbon\Carbon::parse($bdt->finish_time)->diffInSeconds(\Carbon\Carbon::parse($bdt->start_time)))
                            : ($bdt->start_time ? (float)abs($now->diffInSeconds(\Carbon\Carbon::parse($bdt->start_time))) : 0.0));
                    $jobBreakDuration += $secs / 60.0;
                }
            }
            $jobBreakDuration = max(0.0, round($jobBreakDuration, 2));

            // TPT = PressTime + Dandori + Downtime
            $pressTime = $this->calculateBlueBarPressTime(
                jobData: $jobData,
                actStart: $actStart,
                actFinish: $actFinish,
                planStart: $planStart,
                planFinish: $planFinish,
                downtimes: $timelineDowntimes,
                planCt: $planCt,
                totalStroke: $totalStroke,
            );

            $pressTime = max(0.0, $pressTime);

            // TPT = PressTime + Dandori + Downtime
            $actualTpt = ProductionMetricsService::actualTpt($pressTime, $dandoriTime, $downtimeTime);
            $actCt = ProductionMetricsService::actualCt($pressTime, $totalStroke);
            $actualGsph = ProductionMetricsService::gsph($totalStroke, $actualTpt);
            $ctGap = $actCt - $planCt;
            $balance = ProductionMetricsService::balance($planQtySafe, $actualQtySafe, $actualRepairSafe, $actualRejectSafe);
            $achievement = ProductionMetricsService::achievement($actualQtySafe, $planQtySafe);

            $itemFinishActual = $actFinish ?: $calculateFinishTime($actualRunningTime, $actualTpt, $allBreaks, $isShiftMalam);

            $reportTpt = $actualTpt;

            // Work time = total elapsed between start and end (TPT + break + any gap)
            $workTimeDuration = max(0.0, $actualTpt + $jobBreakDuration);
            $gapUnaccountedMinutes = 0.0;
            if ($actStart) {
                $elapsedEnd = $actFinish ?? \Carbon\Carbon::now();
                $totalElapsedMinutes = max(0, $elapsedEnd->diffInMinutes($actStart));
                $gapUnaccountedMinutes = max(0, $totalElapsedMinutes - $actualTpt - $jobBreakDuration);
                $workTimeDuration = max($workTimeDuration, $totalElapsedMinutes - $jobBreakDuration);
            }

            // OEE with Six Big Losses: Availability Loss (downtime + dandori), Performance Loss (idle + speed)
            $oeeResult = ProductionMetricsService::calculateOee(
                workTimeDuration: $workTimeDuration,
                breakDuration: $jobBreakDuration,
                downtime: $downtimeTime,
                dandori: $dandoriTime,
                pressTime: $pressTime,
                totalStroke: $totalStroke,
                actualGood: $actualQtySafe,
            );

            $loadingTime = $oeeResult['planned_production_time'];
            $operatingTime = $oeeResult['operating_time'];
            $availability = $oeeResult['availability'];
            $performance = $oeeResult['performance'];
            $quality = $oeeResult['quality'];
            $oee = $oeeResult['oee'];

            $passRate = $totalStroke > 0 ? ($actualQtySafe / $totalStroke * 100.0) : 0.0;
            $repairRate = $totalStroke > 0 ? ($actualRepairSafe / $totalStroke * 100.0) : 0.0;
            $rejectRate = $totalStroke > 0 ? ($actualRejectSafe / $totalStroke * 100.0) : 0.0;

            $numMachines = 1; 
            $jobPlanStroke = $planQtySafe * $numMachines;
            $jobActualStroke = $actualQtySafe * $numMachines;

            if ($itemFinishActual && $itemFinishActual->lt(now())) {
                $totalActualStrokeFinished += $totalStroke;
                $totalActualTptMinutesFinished += $actualTpt;
            }

            $hasOpenDandori = $jobData && $jobData->downtimes->contains(
                fn ($dt) => str_contains(strtolower($dt->jenis_downtime ?? ''), 'dandori') && empty($dt->finish_time)
            );
            $hasOpenDowntime = $jobData && $jobData->downtimes->contains(
                fn ($dt) => !str_contains(strtolower($dt->jenis_downtime ?? ''), 'dandori')
                    && !str_contains(strtolower($dt->jenis_downtime ?? ''), 'idle')
                    && !str_contains(strtolower($dt->jenis_downtime ?? ''), 'break')
                    && empty($dt->finish_time)
            );

            $qtyPltVal = (float) ($plan->qty_plt ?? 0);
            $kebMtlVal = $kebMtlByPlanId[$plan->id] ?? (int) $planQtySafe;
            $totalPltVal = $qtyPltVal > 0
                ? (int) max(1, round($planQtySafe / $qtyPltVal))
                : (int) round((float) ($plan->total_plt ?? 0));

            $jobsData[] = array_merge([
                'row_type' => 'job',
                'display_no' => $jobDisplayNo,
                'job_master' => $plan->job_master,
                'type_plt' => $plan->type_plt,
                'qty_plt' => $qtyPltVal,
                'keb_mtl' => $kebMtlVal,
                'total_plt' => $totalPltVal,
                'job_no' => $plan->job_no ?? $plan->job_master,
                'plan_qty' => $planQtySafe,
                'plan_ct' => $planCt,
                'act_ct' => $actCt,
                'ct_gap' => $ctGap,
                'achievement' => $achievement,
                'balance' => $balance,
                'actual_gsph' => $actualGsph,
                'actual_good' => $actualQtySafe,
                'actual_repair' => $actualRepairSafe,
                'actual_reject' => $actualRejectSafe,
                'total_pieces' => $totalStroke,
                'total_mesin' => (int) ($plan->total_mesin ?? 0),
                'process_time' => $planProcessTime,
                'reg_active' => (float) ($plan->reg_active ?? 0),
                'dct' => (float) ($plan->dct ?? 0),
                'mct' => (float) ($plan->mct ?? 0),
                'plan_dct' => (float) ($plan->plan_dct ?? 0),
                'schedule_start' => $planStart ?: $planRunningTime->copy(),
                'schedule_finish' => $planFinish ?: $planRunningTime->copy(),
                'actual_start' => $actStart,
                'actual_finish' => $actFinish,
                'keterangan' => $plan->keterangan ?? '',
                'a1' => (int) ($plan->a1 ?? 0),
                'a2' => (int) ($plan->a2 ?? 0),
                'a3' => (int) ($plan->a3 ?? 0),
                'a4' => (int) ($plan->a4 ?? 0),
                'plan_id' => $plan->id,
                'dt_menit' => (float) ($plan->dt_menit ?? 0) > 0 ? (float) $plan->dt_menit : $downtimeTime,
                'status' => $this->resolveJobStatus($plan, $jobData, $actualQtySafe, $planQtySafe, $hasOpenDandori, $hasOpenDowntime),
                'press_time' => $pressTime,
                'dandori_time' => $dandoriTime,
                'qcheck_time' => $qcheckTime,
                'dies_change_time' => $diesChangeTime,
                'variant_change_time' => $variantChangeTime,
                'dies_variant_time' => $diesChangeTime + $variantChangeTime,
                'report_tpt' => $reportTpt,
                'dt_breakdown' => $dtBreakdown,
                'dt_total' => $downtimeTime,
                'tpt_plan' => $planTpt,
                'tpt_act' => $actualTpt,
                'pass_rate' => $passRate,
                'repair_rate' => $repairRate,
                'reject_rate' => $rejectRate,
                'oee' => $oee,
                'plan_gsph' => $planGsph,
                'gsph' => $actualGsph,
                'break_time_duration' => $jobBreakDuration,
                'work_time_duration' => $workTimeDuration,
                'note' => $plan->keterangan ?? $plan->notes ?? '',
                'parent_job_id' => $plan->parent_job_id,
                'split_group' => $plan->split_group,
                'session_no' => $plan->session_no,
            ]);

            $planRunningTime = ($planFinish ?: $planRunningTime)->copy();
            $actualRunningTime = $actFinish ?: $itemFinishActual->copy();
            if ($actFinish) {
                $previousActFinish = $actFinish->copy();
            }

            $totals['plan_qty'] += $planQtySafe;
            $totals['actual_good'] += $actualQtySafe;
            $totals['actual_repair'] += $actualRepairSafe;
            $totals['actual_reject'] += $actualRejectSafe;
            $totals['total_stroke'] += $totalStroke;
            $totals['total_loading_time'] += $loadingTime;
            $totals['total_operating_time'] += $operatingTime;
            $totals['press_time'] += $pressTime;
            $totals['total_dandori'] += $dandoriTime;
            $totals['total_qcheck'] += $qcheckTime;
            $totals['total_report_tpt'] += $reportTpt;
            $totals['downtime_total'] += $downtimeTime;
            $totals['downtime_prod'] += $dtBreakdown['prod_t'];
            $totals['downtime_dies'] += $dtBreakdown['dies_t'];
            $totals['downtime_mach'] += $dtBreakdown['mach_t'];
            $totals['downtime_matl'] += $dtBreakdown['mat_t'];
            $totals['downtime_log'] += $dtBreakdown['log_t'];
            $totals['downtime_ubp'] += $dtBreakdown['ubp_t'];
            $totals['total_panel_record_ct'] += $planCt;
            $totals['total_break_time'] += $jobBreakDuration;
            $totals['total_work_time'] += $workTimeDuration;
            $totals['tpt_act'] += $actualTpt;
        }

        $totalAllActualPiecesFinal = $totals['actual_good'] + $totals['actual_repair'] + $totals['actual_reject'];
        if ($totalAllActualPiecesFinal > 0) {
            $totals['pass_rate'] = ($totals['actual_good'] / $totalAllActualPiecesFinal) * 100.0;
            $totals['repair_rate'] = ($totals['actual_repair'] / $totalAllActualPiecesFinal) * 100.0;
            $totals['reject_rate'] = ($totals['actual_reject'] / $totalAllActualPiecesFinal) * 100.0;
        } else {
            $totals['pass_rate'] = $totals['repair_rate'] = $totals['reject_rate'] = 0.0;
        }

        $jobRows = collect($jobsData)->where('row_type', 'job');
        $shiftPlanCt = $jobRows->avg('plan_ct') ?: 0;
        $shiftActCt = $totalAllActualPiecesFinal > 0 && $totals['tpt_act'] > 0
            ? ProductionMetricsService::actualCt($totals['tpt_act'], $totalAllActualPiecesFinal)
            : 0;
        $totals['tpt_plan'] = collect($jobsData)->where('row_type', 'job')->sum('tpt_plan');
        $shiftPlanGsph = ProductionMetricsService::shiftPlanGsph((int) $totals['plan_qty'], $totals['tpt_plan']);
        $shiftActGsph = ProductionMetricsService::gsph($totalAllActualPiecesFinal, $totals['tpt_act']);

        // Shift OEE components
        $shiftAvailability = $totals['total_loading_time'] > 0 ? ($totals['total_operating_time'] / $totals['total_loading_time']) : 0.0;
        $shiftPerformance = $totals['total_operating_time'] > 0 ? ($totals['press_time'] / $totals['total_operating_time']) : 0.0;
        $shiftQuality = $totals['total_stroke'] > 0 ? ($totals['actual_good'] / $totals['total_stroke']) : 0.0;

        $shiftAvailability = min(max($shiftAvailability, 0.0), 1.0);
        $shiftPerformance = min(max($shiftPerformance, 0.0), 1.0);
        $shiftQuality = min(max($shiftQuality, 0.0), 1.0);
        $shiftOee = $shiftAvailability * $shiftPerformance * $shiftQuality * 100.0;

        // Phase 1: STOP RECALCULATE FOOTER GSPH & PLAN
        // Retrieve master GSPH & PLAN from imported TOTAL FINISH row or other summary rows or MAX(gsph_item)
        $masterGsph = null;
        $masterPlan = null;
        $tfRow = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $latestShiftName)
            ->whereRaw("UPPER(TRIM(press_name)) = ?", [strtoupper(trim($selectedLineName))])
            ->where(function($q) {
                $q->where('row_type', 'total_finish')
                  ->orWhere('job_master', 'TOTAL FINISH');
            })
            ->where('gsph_item', '>', 0)
            ->first();
        if ($tfRow) {
            $masterGsph = $tfRow->gsph_item;
            $masterPlan = $tfRow->plan;
        } else {
            $gsphRow = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $latestShiftName)
                ->whereRaw("UPPER(TRIM(press_name)) = ?", [strtoupper(trim($selectedLineName))])
                ->where(function($q) {
                    $q->where('job_master', 'like', '%GSPH%')
                      ->orWhere('job_no', 'like', '%GSPH%');
                })
                ->where('gsph_item', '>', 0)
                ->first();
            if ($gsphRow) {
                $masterGsph = $gsphRow->gsph_item;
                $masterPlan = $gsphRow->plan;
            }
        }
        
        if (!$masterGsph) {
            $masterGsph = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $latestShiftName)
                ->whereRaw("UPPER(TRIM(press_name)) = ?", [strtoupper(trim($selectedLineName))])
                ->where('row_type', 'job')
                ->max('gsph_item') ?: 0;
        }

        if (!$masterPlan) {
            $masterPlan = $plans->where('row_type', 'job')->sum('plan');
        }

        // Apply master plan and GSPH
        $totals['plan_qty'] = $masterPlan;
        $totals['weighted_ct'] = $shiftActCt;
        $totals['weighted_gsph'] = $shiftActGsph;
        $totals['weighted_oee'] = $shiftOee;
        $totals['oee'] = $shiftOee;
        $totals['gsph'] = $shiftActGsph;
        $totals['plan_gsph'] = $masterGsph;

        $totals['avg_plan_ct'] = $shiftPlanCt;
        $firstPlan = $plans->first();
        $lastPlan = $plans->last();
        
        $firstJobPlan = $plans->first(fn ($p) => ($p->row_type ?? 'job') === 'job');
        $lastJobPlan = $plans->filter(fn ($p) => ($p->row_type ?? 'job') === 'job')->last();

        $totals['total_schedule_start'] = ($firstJobPlan && $firstJobPlan->start_time)
            ? $parseTime($firstJobPlan->start_time)
            : $shiftStart;
        $totals['total_schedule_finish'] = ($lastJobPlan && $lastJobPlan->finish_time)
            ? $parseTime($lastJobPlan->finish_time)
            : $planRunningTime;

        $summary = [
            'item_plan' => $plans->where('row_type', 'job')->count(),
            'item_act' => collect($jobsData)->filter(fn($j) => ($j['row_type'] ?? 'job') === 'job' && $j['actual_good'] > 0)->count(),
            'qty_plan' => $totals['plan_qty'],
            'qty_act' => $totals['actual_good'],
            'tpt_plan' => $totals['tpt_plan'],
            'tpt_act' => $totals['tpt_act'],
            'gsph_plan' => $masterGsph,
            'gsph_act' => $shiftActGsph,
            'pass_rate_plan' => 100.0,
            'pass_rate_act' => $totals['pass_rate'],
            'reject_rate_plan' => 2.0,
            'reject_rate_act' => $totals['reject_rate'],
            'repair_rate_plan' => 0.5,
            'repair_rate_act' => $totals['repair_rate'],
            'weighted_ct' => $shiftActCt,
            'weighted_oee' => $shiftOee,
            'availability' => $shiftAvailability * 100.0,
            'performance' => $shiftPerformance * 100.0,
            'quality' => $shiftQuality * 100.0,
        ];

        // Get operational hours (jam) from first plan record that has it
        $operationalHours = $plans->first(fn ($p) => !empty($p->jam))?->jam;

        // Fetch custom line assignment configuration
        $assignment = \App\Models\LineAssignment::with(['leaderUser', 'foremanUser', 'supervisorUser'])
            ->where('line_name', $selectedLineName)
            ->where('shift_name', $latestShiftName)
            ->first();

        // 1. Team Leader
        if ($assignment && $assignment->leaderUser) {
            $leaderName = $assignment->leaderUser->name;
        } else {
            $lineLower = strtolower($selectedLineName);
            $leaderRole = 'leader a';
            if (str_contains($lineLower, 'a')) {
                $leaderRole = 'leader a';
            } elseif (str_contains($lineLower, 'b')) {
                $leaderRole = 'leader b';
            } elseif (str_contains($lineLower, 'c')) {
                $leaderRole = 'leader c';
            } elseif (str_contains($lineLower, 'd')) {
                $leaderRole = 'leader d';
            }

            if (auth()->check() && strtolower(auth()->user()->role) === $leaderRole) {
                $leaderName = auth()->user()->name;
            } else {
                $leaderUser = \App\Models\User::where('role', $leaderRole)->first();
                $leaderName = $leaderUser ? $leaderUser->name : '';
            }
        }

        // 2. Foreman
        if ($assignment && $assignment->foremanUser) {
            $foremanName = $assignment->foremanUser->name;
        } else {
            if (auth()->check() && strtolower(auth()->user()->role) === 'foreman') {
                $foremanName = auth()->user()->name;
            } else {
                $foremanUser = \App\Models\User::where('role', 'foreman')->first();
                $foremanName = $foremanUser ? $foremanUser->name : '';
            }
        }

        // 3. Supervisor
        if ($assignment && $assignment->supervisorUser) {
            $supervisorName = $assignment->supervisorUser->name;
        } else {
            if (auth()->check() && strtolower(auth()->user()->role) === 'supervisor') {
                $supervisorName = auth()->user()->name;
            } else {
                $supervisorUser = \App\Models\User::where('role', 'supervisor')->first();
                $supervisorName = $supervisorUser ? $supervisorUser->name : '';
            }
        }

        $assignedNames = [
            'teamleader' => $leaderName,
            'foreman'    => $foremanName,
            'supervisor' => $supervisorName,
        ];

        $sigChain = ['teamleader', 'foreman', 'supervisor'];
        $signedRoles = \App\Models\Signature::whereIn('role', $sigChain)->where('work_date', $date)->pluck('role')->toArray();
        $signatureStatus = [];
        $prevSigned = true;
        foreach ($sigChain as $role) {
            $signed = in_array($role, $signedRoles);
            $signatureStatus[$role] = [
                'signed' => $signed,
                'available' => $prevSigned,
                'name' => $assignedNames[$role] ?? '',
            ];
            $prevSigned = $signed;
        }

        // Excel export
        if ($request->query('format') === 'excel') {
            $export = new LkhActualExport(
                jobsData: $jobsData,
                totals: $totals,
                summary: $summary,
                lineName: $selectedLineName,
                shiftName: $latestShiftName,
                date: $date,
                signatureStatus: $signatureStatus,
                shiftDisplayStart: $shiftDisplayStart,
                shiftDisplayEnd: $shiftDisplayEnd,
            );
            return $export->download();
        }

        return view('reports.daily_production', compact(
            'jobsData',
            'totals',
            'summary',
            'lineNamesUnique',
            'selectedLineName',
            'selectedShift',
            'date',
            'latestShiftName',
            'operationalHours',
            'shiftDisplayStart',
            'shiftDisplayEnd',
            'signatureStatus'
        ));
    }

    public function performance(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $carbonMonth = \Carbon\Carbon::parse($month . '-01');
        $startDate = $carbonMonth->copy()->startOfMonth();
        $endDate = $carbonMonth->copy()->endOfMonth();

        // Load all job plans for the month and map to JobMaster for downtime data
        $plans = ProductionPlan::whereDate('plan_date', '>=', $startDate)
            ->whereDate('plan_date', '<=', $endDate)
            ->visibleOnTimeline()
            ->where('row_type', 'job')
            ->get();

        // Build job number → plan mapping to load JobMaster with downtimes
        $jobNumbers = $plans->map(function ($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();
        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with('downtimes')
            ->get()
            ->keyBy('job_number');

        $grouped = $plans->groupBy(fn ($p) => $p->plan_date->toDateString() . '|' . ($p->shift_name ?? 'Shift Pagi'));

        $dailyRecords = [];
        $weeklyBuckets = [];

        foreach ($grouped as $key => $group) {
            [$dateStr, $shiftName] = explode('|', $key);

            $totalActualGood = 0;
            $totalActualRepair = 0;
            $totalActualReject = 0;
            $totalStroke = 0;
            $totalPressTime = 0.0;
            $totalWorkTime = 0.0;
            $totalBreakTime = 0.0;
            $totalDowntime = 0.0;
            $totalDandori = 0.0;

            foreach ($group as $plan) {
                $actualQtySafe = max((int) ($plan->ok ?? 0), 0);
                $repairQty = max((int) ($plan->repair ?? 0), 0);
                $rejectQty = max((int) ($plan->reject ?? 0), 0);
                $planQty = (int) ($plan->plan ?? $plan->target_qty ?? 0);
                $stroke = $actualQtySafe + $repairQty + $rejectQty;

                $totalActualGood += $actualQtySafe;
                $totalActualRepair += $repairQty;
                $totalActualReject += $rejectQty;
                $totalStroke += $stroke;

                $cycleTime = (float) ($plan->ct_detik ?? 0);

                $pressTime = ProductionMetricsService::processTimeMinutes($cycleTime, $stroke);
                $totalPressTime += $pressTime;

                // Load downtimes via JobMaster
                $jnL = trim($plan->job_no ?? '');
                $jmL = trim($plan->job_master ?? '');
                $jobNumber = $jnL ? ($jnL . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jmL) . '-' . $plan->id);
                $jobData = $jobMasters->get($jobNumber);
                $downtimes = $jobData?->downtimes ?? collect();

                $dandoriTime = ProductionMetricsService::dandoriMinutes($downtimes);
                $downtimeTime = collect(ProductionMetricsService::downtimeBreakdown($downtimes))['total'] ?? 0;
                $processTime = ProductionMetricsService::processTimeMinutes($cycleTime, $planQty);
                if ($processTime <= 0) {
                    $actStart = $plan->act_start ? \Carbon\Carbon::parse($plan->act_start) : null;
                    $actFinish = $plan->act_finish ? \Carbon\Carbon::parse($plan->act_finish) : null;
                    if ($actStart && $actFinish && $actFinish->gt($actStart)) {
                        $processTime = abs($actFinish->diffInSeconds($actStart)) / 60.0;
                    }
                }

                $actualTpt = ProductionMetricsService::actualTpt($processTime, $dandoriTime, $downtimeTime);
                $workTimeDuration = max(0.0, $actualTpt);
                $totalWorkTime += $workTimeDuration;
                $totalDowntime += $downtimeTime;
                $totalDandori += $dandoriTime;

                // Compute break overlap
                $breakDuration = 0.0;
                $breakBaseStart = $plan->act_start ? \Carbon\Carbon::parse($plan->act_start) : null;
                $breakBaseEnd = $plan->act_finish ? \Carbon\Carbon::parse($plan->act_finish) : null;
                if ($breakBaseStart && $breakBaseEnd && $breakBaseEnd->gt($breakBaseStart)) {
                    $breaks = \App\Models\MasterBreakTime::where('is_active', true)->get();
                    foreach ($breaks as $br) {
                        $brStart = \Carbon\Carbon::parse($br->waktu_mulai);
                        $brEnd = \Carbon\Carbon::parse($br->waktu_selesai);
                        $overlapStart = $breakBaseStart->gt($brStart) ? $breakBaseStart : $brStart;
                        $overlapEnd = $breakBaseEnd->lt($brEnd) ? $breakBaseEnd : $brEnd;
                        if ($overlapEnd->gt($overlapStart)) {
                            $breakDuration += abs($overlapEnd->diffInSeconds($overlapStart)) / 60.0;
                        }
                    }
                }
                $totalBreakTime += $breakDuration;
            }

            $oeeResult = ProductionMetricsService::calculateOee(
                workTimeDuration: $totalWorkTime,
                breakDuration: $totalBreakTime,
                downtime: $totalDowntime,
                dandori: $totalDandori,
                pressTime: $totalPressTime,
                totalStroke: $totalStroke,
                actualGood: $totalActualGood,
            );

            $weekNumber = (int) ceil(\Carbon\Carbon::parse($dateStr)->day / 7);
            $weeklyBuckets[$weekNumber][] = $oeeResult['oee'];

            $dailyRecords[] = [
                'date' => $dateStr,
                'shift' => $shiftName,
                'availability' => $oeeResult['availability'] * 100.0,
                'performance' => $oeeResult['performance'] * 100.0,
                'quality' => $oeeResult['quality'] * 100.0,
                'oee' => $oeeResult['oee'],
                'good' => $totalActualGood,
                'stroke' => $totalStroke,
            ];
        }

        $weeklyOee = [];
        for ($w = 1; $w <= 5; $w++) {
            $weeklyOee['Week ' . $w] = isset($weeklyBuckets[$w]) && count($weeklyBuckets[$w]) > 0
                ? round(array_sum($weeklyBuckets[$w]) / count($weeklyBuckets[$w]), 1)
                : 0;
        }

        $avgAvailability = count($dailyRecords) > 0
            ? round(array_sum(array_column($dailyRecords, 'availability')) / count($dailyRecords), 1) : 0;
        $avgPerformance = count($dailyRecords) > 0
            ? round(array_sum(array_column($dailyRecords, 'performance')) / count($dailyRecords), 1) : 0;
        $avgQuality = count($dailyRecords) > 0
            ? round(array_sum(array_column($dailyRecords, 'quality')) / count($dailyRecords), 1) : 0;
        $avgOee = count($dailyRecords) > 0
            ? round(array_sum(array_column($dailyRecords, 'oee')) / count($dailyRecords), 1) : 0;

        $lineNamesUnique = \App\Models\LineMaster::select('line_name')->distinct()->pluck('line_name');

        if ($request->ajax()) {
            return response()->json([
                'daily' => $dailyRecords,
                'weekly' => $weeklyOee,
                'averages' => [
                    'availability' => $avgAvailability,
                    'performance' => $avgPerformance,
                    'quality' => $avgQuality,
                    'oee' => $avgOee,
                ],
                'month' => $month,
                'target_oee' => 80,
            ]);
        }

        // PDF export
        if ($request->query('format') === 'pdf') {
            $targetOee = 80;
            $pdf = Pdf::loadView('reports.performance_pdf', compact(
                'dailyRecords', 'weeklyOee', 'avgAvailability', 'avgPerformance', 'avgQuality', 'avgOee',
                'month', 'targetOee'
            ));
            $filename = 'OEE_Performance_' . str_replace('-', '_', $month) . '.pdf';
            return $pdf->download($filename);
        }

        return view('supervisor.reports.performance', compact(
            'dailyRecords', 'weeklyOee', 'avgAvailability', 'avgPerformance', 'avgQuality', 'avgOee',
            'month', 'lineNamesUnique'
        ));
    }

    /**
     * KEB. MTL = sum PLAN per consecutive material block (Excel parity).
     * Break rows split blocks; same type_plt/job family in sequence shares total.
     *
     * @return array<int, int> plan_id => keb_mtl
     */
    private function buildKebMtlGroupTotals($plans): array
    {
        $byPlanId = [];
        $currentKey = null;
        $blockSum = 0;
        $blockIds = [];

        $flush = function () use (&$byPlanId, &$blockSum, &$blockIds) {
            foreach ($blockIds as $id) {
                $byPlanId[$id] = $blockSum;
            }
            $blockIds = [];
            $blockSum = 0;
        };

        foreach ($plans as $plan) {
            if (($plan->row_type ?? '') === 'break') {
                $flush();
                $currentKey = null;
                continue;
            }
            if (($plan->row_type ?? 'job') !== 'job') {
                continue;
            }

            $key = $this->materialGroupKey($plan);
            if ($currentKey !== null && $key !== $currentKey) {
                $flush();
            }
            $currentKey = $key;
            $blockSum += (int) round((float) ($plan->plan ?? $plan->target_qty ?? 0));
            $blockIds[] = $plan->id;
        }
        $flush();

        return $byPlanId;
    }

    private function materialGroupKey(ProductionPlan $plan): string
    {
        $type = strtoupper(trim((string) ($plan->type_plt ?? '')));
        if ($type !== '' && $type !== '—' && $type !== '-') {
            return 'TYPE:' . $type;
        }

        $master = strtoupper(trim((string) ($plan->job_master ?? '')));
        if (preg_match('/^([A-Z]{1,3}[-\s]?\d{2,4})/', $master, $m)) {
            return 'MAT:' . preg_replace('/\s+/', '', $m[1]);
        }

        $jobNo = strtoupper(trim((string) ($plan->job_no ?? '')));
        if ($jobNo !== '') {
            return 'JOB:' . $jobNo;
        }

        return 'ROW:' . $plan->id;
    }

    private function resolvePlanGsph(ProductionPlan $plan, int $planQty, float $planTpt): int
    {
        $fromExcel = (float) ($plan->gsph_item ?? 0);
        if ($fromExcel > 0) {
            return (int) round($fromExcel);
        }

        return ProductionMetricsService::gsph($planQty, $planTpt);
    }

    private function resolveBreakLabel(ProductionPlan $plan): string
    {
        if (!empty($plan->job_master) && !in_array($plan->job_master, ['0', '—', '', 'None'], true)) {
            return strtoupper(trim($plan->job_master));
        }

        $dct = (int) ($plan->dct ?? 0);
        if ($dct >= 45) {
            $isFriday = str_contains(strtoupper($plan->hari ?? ''), 'JUMAT');

            return $isFriday ? 'ISTIRAHAT JUMAT' : 'ISTIRAHAT SIANG';
        }
        if ($dct === 15) {
            return 'CINGKORAK / BREAKTIME';
        }
        if ($dct === 10) {
            return 'CLEANING';
        }

        return strtoupper(trim($plan->keterangan ?: 'ISTIRAHAT'));
    }

    public function downtimeRecap(Request $request, $planId)
    {
        $plan = ProductionPlan::findOrFail($planId);
        $jobNumber = trim($plan->job_no ?? '');
        $jobData = null;
        $downtimes = collect();

        if ($jobNumber) {
            $jobData = JobMaster::where('job_number', 'like', $jobNumber . '%')->with('downtimes')->first();
            $downtimes = $jobData?->downtimes ?? collect();
        }

        $classified = [];
        $totalMinutes = 0;
        foreach ($downtimes as $dt) {
            $type = $dt->jenis_downtime ?? 'Uncategorized';
            $dur = 0.0;
            if (!empty($dt->duration_seconds)) {
                $dur = round((float) $dt->duration_seconds / 60.0, 2);
            } elseif ($dt->start_time && $dt->finish_time) {
                $dur = round(abs(\Carbon\Carbon::parse($dt->finish_time)->diffInSeconds(\Carbon\Carbon::parse($dt->start_time))) / 60.0, 2);
            }
            if (!isset($classified[$type])) {
                $classified[$type] = ['count' => 0, 'total_minutes' => 0.0];
            }
            $classified[$type]['count']++;
            $classified[$type]['total_minutes'] += $dur;
            $totalMinutes += $dur;
        }
        $totalCount = $downtimes->count();

        return view('supervisor.reports.downtime_recap', compact(
            'plan', 'jobData', 'downtimes', 'classified', 'totalMinutes', 'totalCount'
        ));
    }

    public function downtimeRecapJson($planId)
    {
        $plan = ProductionPlan::findOrFail($planId);
        $jobNumber = trim($plan->job_no ?? '');
        $downtimes = collect();

        if ($jobNumber) {
            $jobData = JobMaster::where('job_number', 'like', $jobNumber . '%')->with('downtimes')->first();
            $downtimes = $jobData?->downtimes ?? collect();
        }

        $classified = [];
        $totalMinutes = 0;
        foreach ($downtimes as $dt) {
            $type = $dt->jenis_downtime ?? 'Uncategorized';
            $dur = 0.0;
            if (!empty($dt->duration_seconds)) {
                $dur = round((float) $dt->duration_seconds / 60.0, 2);
            } elseif ($dt->start_time && $dt->finish_time) {
                $dur = round(abs(\Carbon\Carbon::parse($dt->finish_time)->diffInSeconds(\Carbon\Carbon::parse($dt->start_time))) / 60.0, 2);
            }
            if (!isset($classified[$type])) {
                $classified[$type] = ['count' => 0, 'total_minutes' => 0.0];
            }
            $classified[$type]['count']++;
            $classified[$type]['total_minutes'] += $dur;
            $totalMinutes += $dur;
        }
        $totalCount = $downtimes->count();

        $detailRows = [];
        foreach ($downtimes as $dt) {
            $dur = 0;
            if (!empty($dt->duration_seconds)) {
                $dur = round((float) $dt->duration_seconds / 60.0, 2);
            } elseif ($dt->start_time && $dt->finish_time) {
                $dur = round(abs(\Carbon\Carbon::parse($dt->finish_time)->diffInSeconds(\Carbon\Carbon::parse($dt->start_time))) / 60.0, 2);
            }
            $detailRows[] = [
                'jenis'       => $dt->jenis_downtime ?? '-',
                'problem'     => $dt->problem ?? '-',
                'penyebab'    => $dt->penyebab ?? '-',
                'action'      => $dt->action ?? '-',
                'pic'         => $dt->pic ?? '-',
                'start_time'  => $dt->start_time ? \Carbon\Carbon::parse($dt->start_time)->format('H:i') : '-',
                'finish_time' => $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->format('H:i') : '-',
                'durasi'      => number_format($dur, 2),
            ];
        }

        return response()->json([
            'classified'   => $classified,
            'totalCount'   => $totalCount,
            'totalMinutes' => $totalMinutes,
            'downtimes'     => $detailRows,
            'plan' => [
                'job_no' => $plan->job_no ?? $plan->job_master,
                'plan_date' => $plan->plan_date->format('d M Y'),
            ],
        ]);
    }

    public function handworkRecap(Request $request, $planId)
    {
        $plan = ProductionPlan::findOrFail($planId);
        $jobNumber = trim($plan->job_no ?? '');

        $repairRejectLogs = collect();
        $totalRepair = 0;
        $totalReject = 0;

        if ($jobNumber) {
            $jobData = JobMaster::where('job_number', 'like', $jobNumber . '%')->first();
            if ($jobData) {
                $repairRejectLogs = \App\Models\RepairRejectLog::where('job_master_id', $jobData->id)
                    ->with('creator')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $totalRepair = (int) $repairRejectLogs->where('type', 'repair')->sum('qty_a');
                $totalReject = (int) $repairRejectLogs->where('type', 'reject')->sum('qty_a');
            }
        }

        return view('supervisor.reports.handwork_recap', compact(
            'plan', 'repairRejectLogs', 'totalRepair', 'totalReject'
        ));
    }

    private function resolveJobStatus(
        ProductionPlan $plan,
        ?JobMaster $jobData,
        int $actualOk,
        int $planQty,
        bool $hasOpenDandori,
        bool $hasOpenDowntime
    ): string {
        if ($hasOpenDandori) {
            return 'DANDORI';
        }
        if ($hasOpenDowntime) {
            return 'DOWNTIME';
        }

        $raw = strtolower($jobData->status ?? $plan->status ?? 'pending');
        if (in_array($raw, ['running', 'in_progress', 'active', 'started'], true)) {
            return 'RUNNING';
        }
        if (in_array($raw, ['complete', 'completed', 'finished', 'closed', 'done'], true)) {
            return 'DONE';
        }
        if ($planQty > 0 && $actualOk >= $planQty) {
            return 'DONE';
        }

        return 'PENDING';
    }

    private function calculateBlueBarPressTime(
        $jobData, $actStart, $actFinish, $planStart, $planFinish, $downtimes, $planCt, $totalStroke
    ) {
        // Use JobMaster's timestamps when available — matches what the JS production engine uses
        $jS = $jobData && $jobData->started_at
            ? ($jobData->started_at instanceof Carbon ? $jobData->started_at : Carbon::parse($jobData->started_at))
            : $actStart;
        $jF = $jobData && $jobData->finished_at
            ? ($jobData->finished_at instanceof Carbon ? $jobData->finished_at : Carbon::parse($jobData->finished_at))
            : $actFinish;

        if (!$jS || !$jF || !$jF->gt($jS)) {
            return max(0.0, ProductionMetricsService::processTimeMinutes($planCt, $totalStroke));
        }

        $history = [];
        foreach ($downtimes as $dt) {
            if ($dt->start_time && $dt->finish_time) {
                $dtStart = $dt->start_time instanceof Carbon ? $dt->start_time : Carbon::parse($dt->start_time);
                $dtEnd = $dt->finish_time instanceof Carbon ? $dt->finish_time : Carbon::parse($dt->finish_time);
                if ($dtStart && $dtEnd) {
                    $history[] = [
                        'start' => $dtStart,
                        'end' => $dtEnd,
                        'type' => strtolower(trim($dt->jenis_downtime ?? '')),
                    ];
                }
            }
        }
        usort($history, fn($a, $b) => $a['start']->timestamp - $b['start']->timestamp);

        $hasDandori = false;
        $firstDandoriTime = null;
        foreach ($history as $h) {
            if ($h['type'] === 'dandori') {
                $hasDandori = true;
                if (!$firstDandoriTime || $h['start']->lt($firstDandoriTime)) {
                    $firstDandoriTime = $h['start'];
                }
            }
        }

        $actualStartMs = $jS;

        $effectiveActualStart = $actualStartMs
            ?: ($hasDandori ? $firstDandoriTime : null)
            ?: $jS;

        $candidates = [];
        if ($actualStartMs) $candidates[] = $actualStartMs;
        if ($hasDandori && $firstDandoriTime) $candidates[] = $firstDandoriTime;
        foreach ($history as $h) {
            $candidates[] = $h['start'];
        }

        $anchor = !empty($candidates) ? (clone $candidates[0]) : $jS;
        foreach ($candidates as $c) {
            if ($c->lt($anchor)) $anchor = (clone $c);
        }

        $firstProdHistory = null;
        foreach ($history as $h) {
            if ($h['type'] !== 'dandori' && $h['type'] !== 'setup') {
                $firstProdHistory = $h['start'];
                break;
            }
        }
        $firstAnyHistory = !empty($history) ? $history[0]['start'] : null;
        $effectiveProductionStart = $firstProdHistory ?: $actualStartMs ?: $firstAnyHistory ?: $effectiveActualStart;
        if (!$effectiveProductionStart) {
            $effectiveProductionStart = $effectiveActualStart ?: $anchor;
        }

        // Planned duration: prefer JobMaster plan_start/plan_end (matches JS engine in Input Harian),
        // fall back to ProductionPlan start_time/finish_time, then to actual times jS/jF.
        $pS = $jS; $pF = $jF;
        if ($jobData && $jobData->plan_start && $jobData->plan_end) {
            $pps = $jobData->plan_start instanceof Carbon ? $jobData->plan_start : Carbon::parse($jobData->plan_start);
            $ppf = $jobData->plan_end instanceof Carbon ? $jobData->plan_end : Carbon::parse($jobData->plan_end);
            $pS = $pps; $pF = $ppf;
        } elseif ($planStart && $planFinish) {
            $pS = $planStart; $pF = $planFinish;
        }
        $plannedDurationSeconds = max(abs($pF->diffInSeconds($pS)), 1);
        $relativeDeadline = (clone $effectiveProductionStart)->addSeconds($plannedDurationSeconds);

        $earliestTimestamps = [];
        if ($effectiveActualStart) $earliestTimestamps[] = $effectiveActualStart;
        if ($hasDandori && $firstDandoriTime) $earliestTimestamps[] = $firstDandoriTime;
        foreach ($history as $h) {
            $earliestTimestamps[] = $h['start'];
        }

        $earliestActivity = !empty($earliestTimestamps) ? (clone $earliestTimestamps[0]) : $jS;
        foreach ($earliestTimestamps as $c) {
            if ($c->lt($earliestActivity)) $earliestActivity = (clone $c);
        }

        $lastPos = clone $earliestActivity;
        $totalBlueSeconds = 0;

        $appendBlue = function ($start, $end) use ($relativeDeadline, &$totalBlueSeconds) {
            if ($end->lte($start)) return;
            if ($end->gt($relativeDeadline) && $start->lt($relativeDeadline)) {
                $totalBlueSeconds += abs($relativeDeadline->diffInSeconds($start));
            } elseif ($start->gte($relativeDeadline)) {
                return;
            } else {
                $totalBlueSeconds += abs($end->diffInSeconds($start));
            }
        };

        foreach ($history as $h) {
            $start = $h['start'];
            $end = $h['end'];

            if ($start->gt($lastPos)) {
                $isInitialDandoriGap = $hasDandori && $firstDandoriTime
                    && $start->gt($firstDandoriTime)
                    && (!$effectiveActualStart || $start->lte($effectiveActualStart));

                if ($isInitialDandoriGap) {
                    $lastPos = clone $start;
                } elseif ($effectiveActualStart && $start->gt($effectiveActualStart)) {
                    $segStart = $lastPos->gt($effectiveActualStart) ? $lastPos : $effectiveActualStart;
                    $appendBlue($segStart, $start);
                    $lastPos = clone $start;
                }
            }

            if ($end->gt($lastPos)) {
                $lastPos = clone $end;
            }
        }

        if ($jF->gt($lastPos)) {
            $segStart = $lastPos->gt($effectiveActualStart) ? $lastPos : $effectiveActualStart;
            $appendBlue($segStart, $jF);
        }

        return $totalBlueSeconds / 60.0;
    }
}
