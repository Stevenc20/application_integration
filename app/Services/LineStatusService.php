<?php

namespace App\Services;

use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionSession;

class LineStatusService
{
    public static function getStatuses(int $shift = 1, ?string $date = null): array
    {
        $h = (int) now()->format('H');
        $m = (int) now()->format('i');
        $timeInMin = $h * 60 + $m;
        $activeWorkDate = $date ?: (($timeInMin < 450) ? now()->subDay()->toDateString() : now()->toDateString());
        $today = now()->toDateString();

        $activeLines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name');

        if ($activeLines->isEmpty()) {
            return [];
        }

        // 1. All running/paused jobs grouped by normalized line key
        // Only consider jobs that belong to active plans on the current work date
        $allRunningJobsRaw = JobMaster::whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(status)'), ['running', 'paused'])
            ->get();

        $validRunningJobs = collect();
        foreach ($allRunningJobsRaw as $job) {
            $plan = $job->production_plan;
            $planDateStr = $plan && $plan->plan_date
                ? ($plan->plan_date instanceof \Carbon\Carbon ? $plan->plan_date->toDateString() : substr((string) $plan->plan_date, 0, 10))
                : null;

            // Accept job if its plan matches activeWorkDate or today, or if it has an active session/daily on activeWorkDate
            if ($plan && in_array($planDateStr, [$activeWorkDate, $today], true)) {
                $validRunningJobs->push($job);

                continue;
            }

            if (! $plan) {
                $hasTodayActivity = \App\Models\DailyProduction::where('job_master_id', $job->id)
                    ->whereIn('work_date', [$activeWorkDate, $today])
                    ->exists();
                if ($hasTodayActivity) {
                    $validRunningJobs->push($job);
                }
            }
        }

        $jobsByNormalizedLine = $validRunningJobs->groupBy(fn ($job) => self::normalizeLineKey($job->line));
        $allJobIds = $validRunningJobs->pluck('id');

        // 2. Active downtimes (machine/process issues) across all jobs
        $activeDowntimeJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeDowntimeJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(COALESCE(jenis_downtime, "")))'), [
                    'dandori', 'try out', 'tryout', 'break time', 'break', 'istirahat', '1st check', '1st_check', 'qcheck', 'q check',
                ])
                ->pluck('job_master_id')
                ->toArray();
        }

        // 3. Active break time downtimes across all jobs
        $activeBreakJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeBreakJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(COALESCE(jenis_downtime, "")))'), ['break time', 'break', 'istirahat'])
                ->pluck('job_master_id')
                ->toArray();
        }

        // 4. Active try out downtimes across all jobs
        $activeTryoutJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeTryoutJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(COALESCE(jenis_downtime, "")))'), ['try out', 'tryout'])
                ->pluck('job_master_id')
                ->toArray();
        }

        // 5. Active production sessions across all jobs
        $activeSessionJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeSessionJobIds = ProductionSession::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->where('status', 'running')
                ->pluck('job_master_id')
                ->toArray();
        }

        // 6. Active dandoris & QCheck across all running jobs (split 1st check vs setup)
        $firstCheckJobIds = [];
        $setupJobIds = [];
        $linesWithFirstCheck = [];
        $linesWithSetup = [];
        if ($allJobIds->isNotEmpty()) {
            $allDandoris = Dandori::whereIn('next_job_id', $allJobIds->toArray())
                ->whereNull('finish_time')
                ->get();

            foreach ($allDandoris as $d) {
                $jenis = strtolower(trim($d->jenis_dandori ?? ''));
                $act = strtoupper(trim($d->activity ?? ''));
                $normLine = self::normalizeLineKey($d->line);
                if ($jenis === '1st_check' || $jenis === '1st check' || $act === '1ST CHECK') {
                    if ($d->next_job_id) {
                        $firstCheckJobIds[] = $d->next_job_id;
                    }
                    if ($normLine !== '') {
                        $linesWithFirstCheck[] = $normLine;
                    }
                } else {
                    if ($d->next_job_id) {
                        $setupJobIds[] = $d->next_job_id;
                    }
                    if ($normLine !== '') {
                        $linesWithSetup[] = $normLine;
                    }
                }
            }

            $openDandoriDowntimes = Downtime::whereIn('job_master_id', $allJobIds->toArray())
                ->whereNull('finish_time')
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(COALESCE(jenis_downtime, "")))'), ['dandori', '1st check', '1st_check', 'qcheck', 'q check'])
                ->get();

            foreach ($openDandoriDowntimes as $ddt) {
                $dtType = strtolower(trim($ddt->jenis_downtime ?? ''));
                if (in_array($dtType, ['1st check', '1st_check', 'qcheck', 'q check'], true)) {
                    $firstCheckJobIds[] = $ddt->job_master_id;
                } else {
                    $setupJobIds[] = $ddt->job_master_id;
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('q_checks')) {
                $openQChecks = \App\Models\QCheck::whereIn('job_master_id', $allJobIds->toArray())
                    ->whereNotNull('start_time')
                    ->whereNull('finish_time')
                    ->pluck('job_master_id')
                    ->toArray();
                $firstCheckJobIds = array_merge($firstCheckJobIds, $openQChecks);
            }
        }

        $statuses = [];
        foreach ($activeLines as $line) {
            $normLine = self::normalizeLineKey($line);
            $lineJobs = $jobsByNormalizedLine->get($normLine, collect());
            $lineJobIds = $lineJobs->pluck('id')->toArray();

            // 1. DOWNTIME (machine/process issue)
            if (array_intersect($lineJobIds, $activeDowntimeJobIds)) {
                $statuses[$line] = ['label' => 'DOWNTIME', 'color' => 'red', 'pulse' => true];

                continue;
            }

            // 2. BREAKTIME
            if (array_intersect($lineJobIds, $activeBreakJobIds)) {
                $statuses[$line] = ['label' => 'BREAKTIME', 'color' => 'yellow', 'pulse' => true];

                continue;
            }

            // 3. TRYOUT
            if (array_intersect($lineJobIds, $activeTryoutJobIds)) {
                $statuses[$line] = ['label' => 'TRYOUT', 'color' => 'blue', 'pulse' => true];

                continue;
            }

            // 4. 1ST CHECK
            if (array_intersect($lineJobIds, $firstCheckJobIds) || in_array($normLine, $linesWithFirstCheck, true)) {
                $statuses[$line] = ['label' => '1ST CHECK', 'color' => 'purple', 'pulse' => true];

                continue;
            }

            // 5. SETUP (non-1st-check dandori)
            if (array_intersect($lineJobIds, $setupJobIds) || in_array($normLine, $linesWithSetup, true)) {
                $statuses[$line] = ['label' => 'SETUP', 'color' => 'amber', 'pulse' => true];

                continue;
            }

            // 6. PRODUCTION (running session, or running job with started_at and no open pause/downtime)
            $hasRunningJob = $lineJobs->contains(fn ($j) => strtolower((string) $j->status) === 'running' && ! empty($j->started_at));
            if (array_intersect($lineJobIds, $activeSessionJobIds) || $hasRunningJob) {
                $statuses[$line] = ['label' => 'PRODUCTION', 'color' => 'green', 'pulse' => true];

                continue;
            }

            // 7. Running/paused job exists but no activity
            if (! empty($lineJobIds)) {
                $statuses[$line] = ['label' => 'NOT RUNNING', 'color' => 'gray', 'pulse' => false];

                continue;
            }

            // 8. No job at all
            $statuses[$line] = ['label' => 'NOT RUNNING', 'color' => 'gray', 'pulse' => false];
        }

        return $statuses;
    }

    public static function normalizeLineKey(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/^(PRESS|LINE)\s+/', '', $normalized);

        return preg_replace('/[\s\-_.]+/', '', $normalized);
    }
}
