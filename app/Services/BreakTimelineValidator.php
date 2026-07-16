<?php

namespace App\Services;

use App\Models\ProductionPlan;
use Illuminate\Support\Collection;

class BreakTimelineValidator
{
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

        // SORT JOBS
        $sortedJobs = $jobs->sortBy(function ($job) {
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