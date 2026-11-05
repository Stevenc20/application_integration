<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\DandoriSession;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductionTimelineService
{
    /**
     * Standardize activity types across the entire application.
     */
    public static function normalizeActivityType(?string $rawType): string
    {
        $t = strtolower(trim($rawType ?? ''));

        if (in_array($t, ['1st_check', '1st check', 'first_check', 'firstcheck', 'qcheck', 'q check', 'quality'])) {
            return '1st_check';
        }

        if (in_array($t, ['dandori', 'setup', 'changeover', 'persiapan'])) {
            return 'dandori';
        }

        if (in_array($t, ['try out', 'tryout', 'try_out', 'trial'])) {
            return 'tryout';
        }

        if (in_array($t, ['break time', 'break', 'breaktime', 'istirahat', 'ishoma', 'cingkorak'])) {
            return 'break';
        }

        if (in_array($t, ['production', 'produksi', 'run', 'running'])) {
            return 'production';
        }

        if (in_array($t, ['overtime', 'lembur'])) {
            return 'overtime';
        }

        return 'downtime';
    }

    /**
     * Map normalized activity type to standard visual color.
     */
    public static function getActivityColor(string $normalizedType): string
    {
        return match ($normalizedType) {
            'production' => 'bg-blue-600',
            'overtime'   => 'bg-red-600',
            'dandori'    => 'bg-amber-400',
            '1st_check'  => 'bg-purple-500',
            'tryout'     => 'bg-orange-500',
            'break'      => 'bg-slate-500',
            default      => 'bg-red-500', // downtime / repair
        };
    }

    /**
     * Map normalized activity type to standard label.
     */
    public static function getActivityLabel(string $normalizedType, ?string $rawLabel = null): string
    {
        return match ($normalizedType) {
            'production' => 'Production',
            'overtime'   => 'Overtime',
            'dandori'    => 'Dandori',
            '1st_check'  => '1st Check',
            'tryout'     => 'Try Out',
            'break'      => 'Break Time',
            default      => ($rawLabel && !in_array(strtolower($rawLabel), ['downtime', 'other'])) ? 'Downtime ' . $rawLabel : 'Downtime',
        };
    }

    /**
     * Build standard, non-overlapping chronological timeline segments for a JobMaster / ProductionPlan.
     *
     * @param JobMaster|null $job
     * @param ProductionPlan|null $plan
     * @param string|null $referenceDate (Y-m-d)
     * @param Carbon|null $currentTime
     * @return array Array of segments: [type, label, color, start (Carbon), end (Carbon), duration_sec, detail, link, is_incomplete]
     */
    public function buildJobTimeline(?JobMaster $job, ?ProductionPlan $plan = null, ?string $referenceDate = null, ?Carbon $currentTime = null): array
    {
        $now = $currentTime ? $currentTime->copy() : now();
        $workDate = $referenceDate ?: ($plan?->plan_date ? Carbon::parse($plan->plan_date)->format('Y-m-d') : ($job?->started_at ? Carbon::parse($job->started_at)->format('Y-m-d') : $now->format('Y-m-d')));

        if (!$job && !$plan) {
            return [];
        }

        $isComplete = $job && in_array(strtolower($job->status ?? ''), ['complete', 'completed', 'finished', 'closed', 'done']);
        $isPending = !$job || in_array(strtolower($job->status ?? ''), ['pending', 'queued', 'unstarted']);
        $isRunning = $job && strtolower($job->status ?? '') === 'running';
        $rawEvents = [];

        // 1. HARVEST DANDORI SESSIONS (from DandoriSession model if loaded or exists)
        if ($job && $job->relationLoaded('dandoriSessions')) {
            foreach ($job->dandoriSessions as $ds) {
                if (!$ds->start_time) continue;
                $s = Carbon::parse($ds->start_time);
                $fallbackEnd = $ds->total_minutes > 0 ? $s->copy()->addSeconds((int) round($ds->total_minutes * 60)) : ($isRunning ? $now->copy() : ($isComplete && $job->finished_at ? Carbon::parse($job->finished_at) : $s->copy()));
                $e = $ds->finish_time ? Carbon::parse($ds->finish_time) : $fallbackEnd;
                if (!$e || $e < $s) $e = $s->copy();

                $rawEvents[] = [
                    'source' => 'dandori_session',
                    'id' => 'ds_' . $ds->id,
                    'type' => 'dandori',
                    'start' => $s,
                    'end' => $e,
                    'detail' => 'Changeover / Setup Session',
                    'link' => null,
                ];
            }
        }

        // 2. HARVEST DANDORIS TABLE (App\Models\Dandori)
        if ($job && $job->relationLoaded('dandoris')) {
            foreach ($job->dandoris as $dRec) {
                if (!$dRec->start_time) continue;
                $s = Carbon::parse($dRec->start_time);
                $durMin = (float) ($dRec->duration_minutes ?? 0);
                $typeNorm = self::normalizeActivityType($dRec->jenis_dandori ?: $dRec->activity);
                if (!in_array($typeNorm, ['1st_check', 'dandori'])) {
                    $typeNorm = 'dandori';
                }

                $fallbackEnd = $durMin > 0 ? $s->copy()->addSeconds((int) round($durMin * 60)) : ($isRunning ? $now->copy() : ($isComplete && $job->finished_at ? Carbon::parse($job->finished_at) : $s->copy()));
                $e = $dRec->finish_time ? Carbon::parse($dRec->finish_time) : $fallbackEnd;
                if (!$e || $e < $s) $e = $s->copy();

                $rawEvents[] = [
                    'source' => 'dandoris',
                    'id' => 'd_' . $dRec->id,
                    'type' => $typeNorm,
                    'start' => $s,
                    'end' => $e,
                    'detail' => $dRec->activity ?: ($typeNorm === '1st_check' ? '1st Check Quality' : 'Dandori Setup'),
                    'link' => null,
                ];
            }
        }

        // 3. HARVEST DOWNTIMES TABLE (Downtime: tryout, break, repair, downtime, dandori)
        if ($job && $job->relationLoaded('downtimes')) {
            foreach ($job->downtimes as $dt) {
                if (!$dt->start_time) continue;
                $s = Carbon::parse($dt->start_time);
                $durSec = (int) ($dt->duration_seconds ?? 0);
                $typeNorm = self::normalizeActivityType($dt->jenis_downtime);

                $fallbackEnd = $durSec > 0 ? $s->copy()->addSeconds($durSec) : ($isRunning ? $now->copy() : ($isComplete && $job->finished_at ? Carbon::parse($job->finished_at) : $s->copy()));
                $e = $dt->finish_time ? Carbon::parse($dt->finish_time) : $fallbackEnd;
                if (!$e || $e < $s) $e = $s->copy();

                $rawEvents[] = [
                    'source' => 'downtimes',
                    'id' => 'dt_' . $dt->id,
                    'type' => $typeNorm,
                    'start' => $s,
                    'end' => $e,
                    'detail' => $dt->problem ?: ($dt->penyebab ?: self::getActivityLabel($typeNorm, $dt->jenis_downtime)),
                    'link' => null,
                ];
            }
        }

        // 4. SMART RECONCILIATION & DEDUPLICATION
        $nonProdEvents = $this->reconcileEvents($rawEvents);

        // 5. DETERMINE BOUNDARIES FOR PRODUCTION FILLING
        $actualOk = $job?->dailyProduction ? (int) $job->dailyProduction->actual_ok : 0;
        $actualRepair = $job?->dailyProduction ? (int) $job->dailyProduction->actual_repair : 0;
        $actualReject = $job?->dailyProduction ? (int) $job->dailyProduction->actual_reject : 0;
        $actualTotal = $actualOk + $actualRepair + $actualReject;

        $hasOutputOrActivity = ($actualTotal > 0) || ($job && $job->started_at) || ($job && in_array(strtolower($job->status ?? ''), ['running', 'complete', 'completed', 'finished', 'paused']));

        // Filter out empty zero-duration uncompleted events for non-running jobs
        $nonProdEvents = array_values(array_filter($nonProdEvents, function ($ev) use ($isRunning) {
            if ($ev['end'] <= $ev['start'] && !$isRunning) {
                return false;
            }
            return true;
        }));

        // If no events and no activity, show planned schedule block
        if (empty($nonProdEvents) && !$hasOutputOrActivity) {
            $schedStart = $plan?->start_time ? Carbon::parse($workDate . ' ' . substr($plan->start_time, 0, 5)) : Carbon::parse($workDate . ' 07:30:00');
            $schedEnd = $plan?->finish_time ? Carbon::parse($workDate . ' ' . substr($plan->finish_time, 0, 5)) : $schedStart->copy()->addMinutes(60);
            if ($schedEnd <= $schedStart) $schedEnd = $schedStart->copy()->addMinutes(60);

            return [
                [
                    'type' => 'planned',
                    'label' => 'Planned Schedule',
                    'color' => 'bg-slate-700/60 border border-dashed border-slate-500/50',
                    'start' => $schedStart,
                    'end' => $schedEnd,
                    'duration_sec' => (int) $schedEnd->diffInSeconds($schedStart),
                    'detail' => 'Scheduled: ' . $schedStart->format('H:i') . ' - ' . $schedEnd->format('H:i'),
                    'link' => null,
                    'is_incomplete' => false,
                ]
            ];
        }

        // Determine earliest start
        $earliestEvent = !empty($nonProdEvents) ? $nonProdEvents[0]['start'] : null;
        $jobStartedAt = $job?->started_at ? Carbon::parse($job->started_at) : null;

        if ($earliestEvent && $jobStartedAt) {
            $timelineStart = $earliestEvent < $jobStartedAt ? $earliestEvent->copy() : $jobStartedAt->copy();
        } elseif ($jobStartedAt) {
            $timelineStart = $jobStartedAt->copy();
        } elseif ($earliestEvent) {
            $timelineStart = $earliestEvent->copy();
        } else {
            $timelineStart = $plan?->start_time ? Carbon::parse($workDate . ' ' . substr($plan->start_time, 0, 5)) : Carbon::parse($workDate . ' 07:30:00');
        }

        // Determine latest end
        $latestEventEnd = null;
        foreach ($nonProdEvents as $ev) {
            if (!$latestEventEnd || $ev['end'] > $latestEventEnd) {
                $latestEventEnd = $ev['end']->copy();
            }
        }

        $jobFinishedAt = $job?->finished_at ? Carbon::parse($job->finished_at) : null;
        if ($isComplete) {
            $timelineEnd = $jobFinishedAt ?: ($latestEventEnd ?: $now->copy());
            if ($latestEventEnd && $latestEventEnd > $timelineEnd) {
                $timelineEnd = $latestEventEnd->copy();
            }
        } elseif ($isPending && !$hasOutputOrActivity) {
            // Pending job with non-prod events (e.g. historical dandori):
            // Timeline bounds to latest event end or planned finish, NOT current time ($now)
            $schedEnd = $plan?->finish_time ? Carbon::parse($workDate . ' ' . substr($plan->finish_time, 0, 5)) : null;
            $timelineEnd = $latestEventEnd ?: ($schedEnd ?: $timelineStart->copy()->addMinutes(60));
            if ($schedEnd && $schedEnd > $timelineEnd) {
                $timelineEnd = $schedEnd->copy();
            }
        } else {
            $timelineEnd = $latestEventEnd && $latestEventEnd > $now ? $latestEventEnd->copy() : $now->copy();
        }

        if ($timelineEnd <= $timelineStart) {
            $timelineEnd = $timelineStart->copy()->addSeconds(max(300, (int) ($job?->dailyProduction?->runtime_seconds ?? 300)));
        }

        // Calculate Relative Deadline for Overtime
        $prodDeadline = null;
        if ($plan && $plan->start_time && $plan->finish_time) {
            $pStart = str_contains($plan->start_time, '-') ? Carbon::parse($plan->start_time) : Carbon::parse($timelineStart->format('Y-m-d') . ' ' . $plan->start_time);
            $pFinish = str_contains($plan->finish_time, '-') ? Carbon::parse($plan->finish_time) : Carbon::parse($timelineStart->format('Y-m-d') . ' ' . $plan->finish_time);
            $durationSec = (int) max(abs($pFinish->diffInSeconds($pStart)), 1);
            $prodDeadline = $timelineStart->copy()->addSeconds($durationSec);
        }

        // 6. ASSEMBLE ORDERED TIMELINE WITH CHRONOLOGICAL GAP FILLING AND MULTI-LANE CONCURRENCY
        $segments = [];
        $lastPos = $timelineStart->copy();

        $appendProd = function (Carbon $s, Carbon $e) use (&$segments, $prodDeadline) {
            if ($e <= $s) return;
            $dur = (int) abs($e->diffInSeconds($s));
            if ($dur <= 0) return;

            if ($prodDeadline && $e > $prodDeadline && $s < $prodDeadline) {
                $pDur = (int) abs($prodDeadline->diffInSeconds($s));
                $oDur = (int) abs($e->diffInSeconds($prodDeadline));
                if ($pDur > 0) {
                    $segments[] = [
                        'type' => 'production',
                        'label' => 'Production',
                        'color' => self::getActivityColor('production'),
                        'start' => $s->copy(),
                        'end' => $prodDeadline->copy(),
                        'duration_sec' => $pDur,
                        'detail' => 'Actual Production Time',
                        'link' => null,
                        'is_incomplete' => false,
                        'lane' => 0,
                    ];
                }
                if ($oDur > 0) {
                    $segments[] = [
                        'type' => 'overtime',
                        'label' => 'Overtime',
                        'color' => self::getActivityColor('overtime'),
                        'start' => $prodDeadline->copy(),
                        'end' => $e->copy(),
                        'duration_sec' => $oDur,
                        'detail' => 'Outside Scheduled Shift',
                        'link' => null,
                        'is_incomplete' => false,
                        'lane' => 0,
                    ];
                }
            } elseif ($prodDeadline && $s >= $prodDeadline) {
                $segments[] = [
                    'type' => 'overtime',
                    'label' => 'Overtime',
                    'color' => self::getActivityColor('overtime'),
                    'start' => $s->copy(),
                    'end' => $e->copy(),
                    'duration_sec' => $dur,
                    'detail' => 'Outside Scheduled Shift',
                    'link' => null,
                    'is_incomplete' => false,
                    'lane' => 0,
                ];
            } else {
                $segments[] = [
                    'type' => 'production',
                    'label' => 'Production',
                    'color' => self::getActivityColor('production'),
                    'start' => $s->copy(),
                    'end' => $e->copy(),
                    'duration_sec' => $dur,
                    'detail' => 'Actual Production Time',
                    'link' => null,
                    'is_incomplete' => false,
                    'lane' => 0,
                ];
            }
        };

        foreach ($nonProdEvents as $ev) {
            $evStart = $ev['start'];
            $evEnd = $ev['end'];
            $evDur = (int) abs($evEnd->diffInSeconds($evStart));
            if ($evDur <= 0) continue;

            // Fill production gap up to event start if start is ahead of lastPos
            if ($evStart > $lastPos) {
                if ($jobStartedAt && $lastPos < $jobStartedAt && $evStart > $jobStartedAt) {
                    $appendProd($jobStartedAt, $evStart);
                } elseif ($jobStartedAt && $lastPos >= $jobStartedAt) {
                    $appendProd($lastPos, $evStart);
                } elseif (!$jobStartedAt && $hasOutputOrActivity) {
                    $appendProd($lastPos, $evStart);
                }
                $lastPos = $evStart->copy();
            }

            // Assign lane using interval-graph coloring so that concurrent events
            // each get a distinct visible lane (0, 1, 2, ...) without colliding
            $usedLanes = [];
            foreach ($segments as $prevSeg) {
                if ($prevSeg['type'] === 'planned') continue;
                $oStart = max($prevSeg['start']->timestamp, $evStart->timestamp);
                $oEnd = min($prevSeg['end']->timestamp, $evEnd->timestamp);
                if ($oEnd > $oStart) {
                    $usedLanes[] = $prevSeg['lane'] ?? 0;
                }
            }
            $lane = 0;
            while (in_array($lane, $usedLanes)) {
                $lane++;
            }

            $segments[] = [
                'type' => $ev['type'],
                'label' => self::getActivityLabel($ev['type'], $ev['detail'] ?? null),
                'color' => self::getActivityColor($ev['type']),
                'start' => $evStart->copy(),
                'end' => $evEnd->copy(),
                'duration_sec' => $evDur,
                'detail' => $ev['detail'] ?? '',
                'link' => $ev['link'] ?? null,
                'is_incomplete' => false,
                'lane' => $lane,
            ];

            // Only advance the sequential baseline if this event extends beyond lastPos
            if ($evEnd > $lastPos) {
                $lastPos = $evEnd->copy();
            }
        }

        // Trailing gap after last event until timelineEnd
        if ($timelineEnd > $lastPos) {
            if ($hasOutputOrActivity) {
                if ($jobStartedAt && $lastPos < $jobStartedAt) {
                    $appendProd($jobStartedAt, $timelineEnd);
                } else {
                    $appendProd($lastPos, $timelineEnd);
                }
            } elseif ($isPending) {
                // For pending jobs with non-prod events, fill remainder as planned schedule
                $segments[] = [
                    'type' => 'planned',
                    'label' => 'Planned Schedule',
                    'color' => 'bg-slate-700/60 border border-dashed border-slate-500/50',
                    'start' => $lastPos->copy(),
                    'end' => $timelineEnd->copy(),
                    'duration_sec' => (int) $timelineEnd->diffInSeconds($lastPos),
                    'detail' => 'Unstarted Schedule',
                    'link' => null,
                    'is_incomplete' => false,
                    'lane' => 0,
                ];
            }
        }

        // Post-process: Split segments at all concurrency boundaries (active start & end points)
        // so that before overlap an event occupies 100% height, during overlap it occupies 50% (or 1/N),
        // and after overlap the remaining event returns to 100% height.
        return $this->splitSegmentsByConcurrency($segments);
    }

    /**
     * Slice segments into contiguous sub-slices at every transition point where
     * the number of concurrent events changes.
     */
    protected function splitSegmentsByConcurrency(array $segments): array
    {
        if (empty($segments)) {
            return [];
        }

        // Filter active segments (exclude planned)
        $activeSegs = [];
        $plannedSegs = [];
        foreach ($segments as $seg) {
            if ($seg['type'] === 'planned') {
                $plannedSegs[] = $seg;
            } else {
                $activeSegs[] = $seg;
            }
        }

        if (empty($activeSegs)) {
            return $segments;
        }

        // Collect all distinct boundary timestamps
        $points = [];
        foreach ($activeSegs as $seg) {
            $points[] = $seg['start']->timestamp;
            $points[] = $seg['end']->timestamp;
        }
        $points = array_values(array_unique($points));
        sort($points);

        $result = [];

        // For each adjacent pair [t0, t1], find all active segments that cover this slice
        $numPoints = count($points);
        for ($k = 0; $k < $numPoints - 1; $k++) {
            $t0 = $points[$k];
            $t1 = $points[$k + 1];
            if ($t1 <= $t0) continue;

            $covering = [];
            foreach ($activeSegs as $seg) {
                if ($seg['start']->timestamp <= $t0 && $seg['end']->timestamp >= $t1) {
                    $covering[] = $seg;
                }
            }

            if (empty($covering)) continue;

            $totalLanesInSlice = count($covering);
            // Sort covering segments deterministically by type or original start timestamp
            usort($covering, function ($a, $b) {
                // Keep lane 0 consistent (e.g. Dandori in lane 0, 1st Check in lane 1)
                $priority = fn($t) => match ($t) {
                    'production' => 1,
                    'overtime'   => 2,
                    'dandori'    => 3,
                    '1st_check'  => 4,
                    'tryout'     => 5,
                    'break'      => 6,
                    default      => 7,
                };
                $pA = $priority($a['type']);
                $pB = $priority($b['type']);
                if ($pA !== $pB) {
                    return $pA <=> $pB;
                }
                return $a['start']->timestamp <=> $b['start']->timestamp;
            });

            foreach ($covering as $laneIdx => $parentSeg) {
                $sliceStart = Carbon::createFromTimestamp($t0, $parentSeg['start']->timezone);
                $sliceEnd = Carbon::createFromTimestamp($t1, $parentSeg['end']->timezone);
                $sliceDur = $t1 - $t0;

                $slice = $parentSeg;
                $slice['start'] = $sliceStart;
                $slice['end'] = $sliceEnd;
                $slice['duration_sec'] = $sliceDur;
                $slice['original_start'] = $parentSeg['original_start'] ?? $parentSeg['start'];
                $slice['original_end'] = $parentSeg['original_end'] ?? $parentSeg['end'];
                $slice['original_duration_sec'] = $parentSeg['original_duration_sec'] ?? $parentSeg['duration_sec'];
                $slice['lane'] = $laneIdx;
                $slice['total_lanes'] = $totalLanesInSlice;

                $result[] = $slice;
            }
        }

        // Re-append planned segments
        foreach ($plannedSegs as $pSeg) {
            $pSeg['lane'] = 0;
            $pSeg['total_lanes'] = 1;
            $result[] = $pSeg;
        }

        // Sort result chronologically
        usort($result, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp ?: $a['lane'] <=> $b['lane']);

        return $result;
    }

    /**
     * Reconcile multiple event streams (e.g. Dandori in downtimes vs dandoris table)
     * without deleting distinct physical activities.
     */
    protected function reconcileEvents(array $rawEvents): array
    {
        if (empty($rawEvents)) {
            return [];
        }

        // Sort by start timestamp ascending
        usort($rawEvents, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        $reconciled = [];
        foreach ($rawEvents as $ev) {
            $isDuplicate = false;
            foreach ($reconciled as $existing) {
                // Only consider duplicate if same normalized activity type AND overlapping intervals
                if ($existing['type'] === $ev['type']) {
                    $overlapStart = max($existing['start']->timestamp, $ev['start']->timestamp);
                    $overlapEnd = min($existing['end']->timestamp, $ev['end']->timestamp);
                    $overlap = $overlapEnd - $overlapStart;
                    if ($overlap > 0) {
                        $minDur = min(
                            abs($existing['end']->timestamp - $existing['start']->timestamp),
                            abs($ev['end']->timestamp - $ev['start']->timestamp)
                        );
                        // If overlap covers >= 75% of the shorter event, treat as duplicate sync record
                        if ($minDur > 0 && ($overlap / $minDur) >= 0.75) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                }
            }

            if (!$isDuplicate) {
                $reconciled[] = $ev;
            }
        }

        // Final sort
        usort($reconciled, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);
        return $reconciled;
    }
}
