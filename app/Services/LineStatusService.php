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
        
        // Use logical work_date: if before 07:30, it belongs to yesterday's night shift
        $now = now();
        $today = ($now->format('H:i') < '07:30') ? $now->copy()->subDay()->toDateString() : $now->toDateString();
        $activeLines = LineMaster::where('status', 'active')
            ->select('line_name')->distinct()->pluck('line_name');

        if ($activeLines->isEmpty()) {
            return [];
        }

        // 1. All running/paused jobs grouped by line (validating session/plan relevance)
        $allRunningJobsRaw = JobMaster::whereIn('line', $activeLines)
            ->whereIn('status', ['running', 'paused'])
            ->get();

        $validRunningJobs = $allRunningJobsRaw->filter(function ($job) use ($today, $shiftText) {
            $hasSession = ProductionSession::where('job_master_id', $job->id)
                ->whereDate('work_date', $today)
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(status)'), ['running', 'paused'])
                ->exists();
            if ($hasSession) return true;

            $hasLog = ProductionLog::where('job_master_id', $job->id)
                ->whereDate('created_at', $today)
                ->exists();
            if ($hasLog) return true;

            $hasDowntime = Downtime::where('job_master_id', $job->id)
                ->whereNull('finish_time')
                ->whereDate('start_time', $today)
                ->exists();
            if ($hasDowntime) return true;

            // If no active session/log/downtime today, verify if it belongs to today's PPC plan
            $planExists = \App\Models\ProductionPlan::whereDate('plan_date', $today)
                ->where('shift_name', 'like', $shiftText . '%')
                ->where('row_type', 'job')
                ->where(function ($q) use ($job) {
                    $jobNoPart = explode('-', $job->job_number)[0];
                    $q->where('job_no', 'like', $jobNoPart . '%')
                      ->orWhere('job_master', $job->job_name);
                })->exists();

            return $planExists;
        });

        $allRunningJobs = $validRunningJobs->groupBy('line');
        $allJobIds = $validRunningJobs->pluck('id');

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

        // 6. Active 1st checks (directly from dandoris table)
        // Ignoring Job status to ensure 1st check always appears if active
        $linesWithFirstCheck = Dandori::whereNull('finish_time')
            ->where('work_date', $today)
            ->where(function($q) {
                $q->where('jenis_dandori', '1st_check')->orWhere('jenis_dandori', '1st check');
            })
            ->pluck('line')
            ->toArray();

        // 6b. Active SETUP (from downtimes table with jenis_downtime = 'dandori')
        $activeSetupJobIds = [];
        if ($allJobIds->isNotEmpty()) {
            $activeSetupJobIds = Downtime::whereIn('job_master_id', $allJobIds)
                ->whereNull('finish_time')
                ->whereDate('start_time', $today)
                ->where('jenis_downtime', 'dandori')
                ->pluck('job_master_id')
                ->toArray();
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

            // 5. SETUP (dandori downtime active)
            if (array_intersect($lineJobIds, $activeSetupJobIds)) {
                $statuses[$line] = ['label' => 'SETUP', 'color' => 'amber', 'pulse' => true];
                continue;
            }

            // 6. PRODUCTION — Active running session on this line
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
