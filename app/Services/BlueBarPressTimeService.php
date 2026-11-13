<?php

namespace App\Services;

use App\Models\JobMaster;
use Carbon\Carbon;

class BlueBarPressTimeService
{
    /**
     * Menghitung durasi bar biru produksi (dalam satuan menit) dari history aktivitas job aktual
     * persis seperti yang dihitung dan ditampilkan pada engine Input Harian & ReportController.
     *
     * @param  iterable<int, array{start: Carbon|string, end: Carbon|string, type: string}>  $history
     */
    public static function calculate(
        ?JobMaster $jobData,
        ?Carbon $actStart,
        ?Carbon $actFinish,
        ?Carbon $planStart,
        ?Carbon $planFinish,
        iterable $history,
        float $planCt = 0.0,
        int $totalStroke = 0
    ): float {
        $jS = $jobData && $jobData->started_at
            ? ($jobData->started_at instanceof Carbon ? $jobData->started_at : Carbon::parse($jobData->started_at))
            : $actStart;
        $jF = $jobData && $jobData->finished_at
            ? ($jobData->finished_at instanceof Carbon ? $jobData->finished_at : Carbon::parse($jobData->finished_at))
            : $actFinish;

        if (! $jS || ! $jF || ! $jF->gt($jS)) {
            return max(0.0, ProductionMetricsService::processTimeMinutes($planCt, $totalStroke));
        }

        $normalized = [];
        foreach ($history as $entry) {
            $start = $entry['start'] ?? ($entry['start_time'] ?? null);
            $end = $entry['end'] ?? ($entry['finish_time'] ?? null);
            if (! $start || ! $end) {
                continue;
            }

            $start = $start instanceof Carbon ? $start : Carbon::parse($start);
            $end = $end instanceof Carbon ? $end : Carbon::parse($end);
            if (! $end->gt($start)) {
                continue;
            }

            $normalized[] = [
                'start' => $start,
                'end' => $end,
                'type' => strtolower(trim((string) ($entry['type'] ?? ($entry['jenis_downtime'] ?? '')))),
            ];
        }
        usort($normalized, fn ($a, $b) => $a['start']->timestamp - $b['start']->timestamp);

        $hasDandori = false;
        $firstDandoriTime = null;
        foreach ($normalized as $h) {
            if ($h['type'] === 'dandori') {
                $hasDandori = true;
                if (! $firstDandoriTime || $h['start']->lt($firstDandoriTime)) {
                    $firstDandoriTime = $h['start'];
                }
            }
        }

        $actualStartMs = $jS;
        $effectiveActualStart = $actualStartMs
            ?: ($hasDandori ? $firstDandoriTime : null)
            ?: $jS;

        $candidates = [];
        if ($actualStartMs) {
            $candidates[] = $actualStartMs;
        }
        if ($hasDandori && $firstDandoriTime) {
            $candidates[] = $firstDandoriTime;
        }
        foreach ($normalized as $h) {
            $candidates[] = $h['start'];
        }

        $anchor = ! empty($candidates) ? (clone $candidates[0]) : $jS;
        foreach ($candidates as $c) {
            if ($c->lt($anchor)) {
                $anchor = (clone $c);
            }
        }

        $firstProdHistory = null;
        foreach ($normalized as $h) {
            if (! in_array($h['type'], ['dandori', 'setup', 'break time', 'break'], true)) {
                $firstProdHistory = $h['start'];
                break;
            }
        }
        $firstAnyHistory = ! empty($normalized) ? $normalized[0]['start'] : null;
        $effectiveProductionStart = $firstProdHistory ?: $actualStartMs ?: $firstAnyHistory ?: $effectiveActualStart;
        if (! $effectiveProductionStart) {
            $effectiveProductionStart = $effectiveActualStart ?: $anchor;
        }

        $pS = $jS;
        $pF = $jF;
        if ($jobData && $jobData->plan_start && $jobData->plan_end) {
            $pps = $jobData->plan_start instanceof Carbon ? $jobData->plan_start : Carbon::parse($jobData->plan_start);
            $ppf = $jobData->plan_end instanceof Carbon ? $jobData->plan_end : Carbon::parse($jobData->plan_end);
            $pS = $pps;
            $pF = $ppf;
        } elseif ($planStart && $planFinish) {
            $pS = $planStart;
            $pF = $planFinish;
        }
        $plannedDurationSeconds = max(abs($pF->diffInSeconds($pS)), 1);
        $relativeDeadline = (clone $effectiveProductionStart)->addSeconds($plannedDurationSeconds);

        $earliestTimestamps = [];
        if ($effectiveActualStart) {
            $earliestTimestamps[] = $effectiveActualStart;
        }
        if ($hasDandori && $firstDandoriTime) {
            $earliestTimestamps[] = $firstDandoriTime;
        }
        foreach ($normalized as $h) {
            $earliestTimestamps[] = $h['start'];
        }

        $earliestActivity = ! empty($earliestTimestamps) ? (clone $earliestTimestamps[0]) : $jS;
        foreach ($earliestTimestamps as $c) {
            if ($c->lt($earliestActivity)) {
                $earliestActivity = (clone $c);
            }
        }

        $lastPos = clone $earliestActivity;
        $totalBlueSeconds = 0;

        $appendBlue = function ($start, $end) use ($relativeDeadline, &$totalBlueSeconds) {
            if ($end->lte($start)) {
                return;
            }
            if ($end->gt($relativeDeadline) && $start->lt($relativeDeadline)) {
                $totalBlueSeconds += abs($relativeDeadline->diffInSeconds($start));
            } elseif ($start->gte($relativeDeadline)) {
                return;
            } else {
                $totalBlueSeconds += abs($end->diffInSeconds($start));
            }
        };

        foreach ($normalized as $h) {
            $start = $h['start'];
            $end = $h['end'];

            if ($start->gt($lastPos)) {
                $isInitialDandoriGap = $hasDandori && $firstDandoriTime
                    && $start->gt($firstDandoriTime)
                    && (! $effectiveActualStart || $start->lte($effectiveActualStart));

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

        return max(0.0, $totalBlueSeconds / 60.0);
    }
}
