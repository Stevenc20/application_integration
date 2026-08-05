<?php

namespace App\Services;

use App\Models\ProductionPlan;
use Illuminate\Support\Collection;

class BreakTimelineValidator
{
    /**
     * Drop break rows that fall outside the active job span of their press+shift,
     * so a break never shows up when the schedule doesn't actually reach it.
     * Preserves the original ordering of the collection.
     *
     * @param Collection<ProductionPlan> $plans
     * @return Collection<ProductionPlan>
     */
    public function filterBreakRows(Collection $plans): Collection
    {
        $keptIds = $plans
            ->groupBy(function ($p) {
                return (string) ($p->press_name ?? '') . '|' . (string) ($p->shift_name ?? '');
            })
            ->flatMap(function (Collection $group) {
                return $this->filterOverlappingBreaks(
                    $this->filterValidPlans($group)
                );
            })
            ->pluck('id')
            ->flip();

        return $plans->filter(function ($p) use ($keptIds) {
            return $keptIds->has($p->id);
        })->values();
    }

    /**
     * Filter valid jobs & breaks.
     *
     * @param Collection<ProductionPlan> $plans
     * @return Collection<ProductionPlan>
     */
    public function filterValidPlans(Collection $plans): Collection
    {
        // ONLY JOBS
        $jobs = $plans->filter(function ($p) {
            return ($p->row_type ?? 'job') === 'job';
        });

        if ($jobs->isEmpty()) {
            return collect();
        }

        // Only timed jobs define the active span (null start_time = pending/overflow item)
        $jobsWithTime = $jobs->filter(function ($job) {
            return !empty($job->start_time);
        });

        if ($jobsWithTime->isEmpty()) {
            // No reachable schedule — keep non-break rows but drop breaks
            return $plans->filter(function ($plan) {
                return ($plan->row_type ?? 'job') !== 'break';
            })->values();
        }

        // SORT JOBS
        $sortedJobs = $jobsWithTime->sortBy(function ($job) {
            return $this->timeToMinutes($job->start_time);
        });

        $firstJob = $sortedJobs->first();
        $lastJob = $sortedJobs->last();

        $firstJobStartMin =
            $this->timeToMinutes($firstJob->start_time);

        $lastJobFinishMin =
            $this->finishToMinutes($lastJob->start_time, $lastJob->finish_time);

        $seenBreaks = [];

        return $plans->filter(function ($plan) use (
            &$seenBreaks,
            $firstJobStartMin,
            $lastJobFinishMin
        ) {

            // KEEP NON BREAK
            if (($plan->row_type ?? 'job') !== 'break') {
                return true;
            }

            // INVALID TIME
            if (
                empty($plan->start_time) ||
                empty($plan->finish_time)
            ) {
                return false;
            }

            $breakStart =
                $this->timeToMinutes($plan->start_time);

            $breakFinish =
                $this->timeToMinutes($plan->finish_time);

            // OUTSIDE ACTIVE JOB RANGE
            if (
                $breakStart < $firstJobStartMin ||
                $breakStart > $lastJobFinishMin
            ) {
                return false;
            }

            // DUPLICATE BREAK
            $uniqueKey =
                $plan->start_time . '-' .
                $plan->finish_time . '-' .
                strtoupper(trim(
                    $plan->job_no ??
                    $plan->job_master ??
                    'ISTIRAHAT'
                ));

            if (in_array($uniqueKey, $seenBreaks, true)) {
                return false;
            }

            $seenBreaks[] = $uniqueKey;

            return true;

        })->values();
    }

    /**
     * Drop breaks that overlap a running job. Real schedules carve breaks OUT
     * of a job (e.g. Y-1561 11:34–12:56 with ISTIRAHAT SIANG 12:00–12:40 inside,
     * then Y-1561 resumes 12:40–12:56), so overlap alone is NOT corruption.
     * A break is only dropped when it overlaps a job AND no job resumes at the
     * break's finish time — the signature of corrupt rows (e.g. an ISTIRAHAT
     * SORE whose time cells were left at morning values). Jobs are never dropped.
     *
     * @param Collection<ProductionPlan> $plans
     * @return Collection<ProductionPlan>
     */
    public function filterOverlappingBreaks(Collection $plans): Collection
    {
        $jobRanges = [];
        $jobStarts = [];

        foreach ($plans as $p) {
            if (($p->row_type ?? 'job') !== 'job') {
                continue;
            }
            if (empty($p->start_time) || empty($p->finish_time)) {
                continue;
            }

            $jobRanges[] = [
                'start'  => $this->timeToMinutes($p->start_time),
                'finish' => $this->finishToMinutes($p->start_time, $p->finish_time),
            ];
            $jobStarts[] = $this->normalizeTime($p->start_time);
        }

        if (empty($jobRanges)) {
            return $plans;
        }

        return $plans->filter(function ($plan) use ($jobRanges, $jobStarts) {
            if (($plan->row_type ?? 'job') !== 'break') {
                return true;
            }
            if (empty($plan->start_time) || empty($plan->finish_time)) {
                return true;
            }

            $breakStart  = $this->timeToMinutes($plan->start_time);
            $breakFinish = $this->timeToMinutes($plan->finish_time);

            foreach ($jobRanges as $range) {
                // Strict overlap: the break cuts into a running job's window
                if ($breakStart < $range['finish'] && $breakFinish > $range['start']) {
                    // Carve-out: a job resumes exactly when the break ends → legit
                    if (in_array($this->normalizeTime($plan->finish_time), $jobStarts, true)) {
                        return true;
                    }

                    return false;
                }
            }

            return true;
        })->values();
    }

    private function normalizeTime(?string $time): string
    {
        return str_replace('.', ':', trim((string) $time));
    }

    /**
     * Convert time to minutes with consistent midnight-crossover handling.
     * All times within a batch are normalized against a reference hour.
     * If a time follows a midnight-crossover pattern (hours < 7 after hours >= 12),
     * times before 7 get +24h to stay after the crossover.
     */
    private function timeToMinutes(?string $timeStr, bool $add24IfBefore7 = true): int
    {
        if (!$timeStr) {
            return 0;
        }

        $timeStr = str_replace('.', ':', trim($timeStr));
        $parts = explode(':', $timeStr);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        if ($add24IfBefore7 && $hours < 7) {
            $hours += 24;
        }

        return ($hours * 60) + $minutes;
    }

    /**
     * Normalize finish time consistently with its start time.
     * If the start had +24h (hour<7) but finish hour>=7 (crossed 07:00 boundary),
     * add +24h to finish too so finish > start.
     */
    private function finishToMinutes(?string $startTime, ?string $finishTime): int
    {
        $startMin = $this->timeToMinutes($startTime, true);
        $finishMin = $this->timeToMinutes($finishTime, true);

        // If start had +24h but finish didn't (hour>=7), add +24h to keep consistency
        if ($startMin > $finishMin) {
            $finishMin += 24 * 60;
        }

        return $finishMin;
    }
}