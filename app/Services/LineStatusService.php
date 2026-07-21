<?php

namespace App\Services;

use App\Models\LineMaster;
use App\Models\JobMaster;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\ProductionSession;
use App\Models\ProductionLog;

class LineStatusService
{
    public static function getStatuses(int $shift = 1): array
    {
        $shiftText = $shift === 1 ? 'Shift Pagi' : 'Shift Malam';
        $today = now()->toDateString();
        $activeLines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name');

        if ($activeLines->isEmpty()) {
            return [];
        }

        // 1. All running/paused jobs grouped by line
        $allRunningJobs = JobMaster::whereIn('line', $activeLines)
            ->whereIn('status', ['running', 'paused'])
            ->get()
            ->groupBy('line');

        $allJobIds = $allRunningJobs->flatten()->pluck('id');

        // 2. Active downtimes (machine/process issues) across all jobs
        $activeDowntimeJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeDowntimeJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereDate('start_time', $today)
                ->whereNotIn('jenis_downtime', ['dandori', 'try out', 'tryout', 'break time'])
                ->pluck('job_master_id')
                ->toArray();
        }

        // 3. Active break time downtimes across all jobs
        $activeBreakJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeBreakJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereDate('start_time', $today)
                ->where('jenis_downtime', 'break time')
                ->pluck('job_master_id')
                ->toArray();
        }

        // 4. Active try out downtimes across all jobs
        $activeTryoutJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeTryoutJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereDate('start_time', $today)
                ->whereIn('jenis_downtime', ['try out', 'tryout'])
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

        // 6. Active dandoris across all running jobs (split 1st check vs setup)
        $linesWithFirstCheck = [];
        $linesWithSetup = [];
        $allDandoris = collect();
        if ($allJobIds->isNotEmpty()) {
            $allDandoris = Dandori::whereIn('next_job_id', $allJobIds->toArray())
                ->whereNull('finish_time')
                ->where('work_date', $today)
                ->get();
        }

        foreach ($allDandoris as $d) {
            if (strtolower($d->jenis_dandori ?? '') === '1st_check' || strtolower($d->jenis_dandori ?? '') === '1st check') {
                $linesWithFirstCheck[] = $d->line;
            } else {
                $linesWithSetup[] = $d->line;
            }
        }

        // 7. Jobs with actual production activity (ProductionLog saved today)
        //    This determines if a line is truly producing vs still in dandori/setup phase
        $jobIdsWithProductionLog = [];
        if ($allJobIds->isNotEmpty()) {
            $jobIdsWithProductionLog = ProductionLog::whereIn('job_master_id', $allJobIds)
                ->whereDate('created_at', $today)
                ->pluck('job_master_id')
                ->toArray();
        }
        $linesWithProductionLog = [];
        foreach ($allRunningJobs as $line => $jobs) {
            $lineJobIdsArr = $jobs->pluck('id')->toArray();
            if (array_intersect($lineJobIdsArr, $jobIdsWithProductionLog)) {
                $linesWithProductionLog[] = $line;
            }
        }

        $statuses = [];
        foreach ($activeLines as $line) {
            $lineJobIds = $allRunningJobs->get($line, collect())->pluck('id')->toArray();

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
            if (in_array($line, $linesWithFirstCheck)) {
                $statuses[$line] = ['label' => '1ST CHECK', 'color' => 'purple', 'pulse' => true];
                continue;
            }

            // 5. PRODUCTION — If running session exists AND operator has saved qty today,
            //    the line is in production even if dandori record wasn't properly closed
            if (array_intersect($lineJobIds, $activeSessionJobIds) && in_array($line, $linesWithProductionLog)) {
                $statuses[$line] = ['label' => 'PRODUCTION', 'color' => 'green', 'pulse' => true];
                continue;
            }

            // 6. SETUP (non-1st-check dandori, no production activity yet)
            if (in_array($line, $linesWithSetup)) {
                $statuses[$line] = ['label' => 'SETUP', 'color' => 'amber', 'pulse' => true];
                continue;
            }

            // 7. PRODUCTION (running session without dandori — started via startJob directly)
            if (array_intersect($lineJobIds, $activeSessionJobIds)) {
                $statuses[$line] = ['label' => 'PRODUCTION', 'color' => 'green', 'pulse' => true];
                continue;
            }

            // 8. Running/paused job exists but no activity
            if (!empty($lineJobIds)) {
                $statuses[$line] = ['label' => 'NOT RUNNING', 'color' => 'gray', 'pulse' => false];
                continue;
            }

            // 9. No job at all
            $statuses[$line] = ['label' => 'NOT RUNNING', 'color' => 'gray', 'pulse' => false];
        }

        return $statuses;
    }
}
