<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobMaster;
use App\Models\DailyProduction;
use App\Models\ProductionSession;
use App\Models\Downtime;
use App\Models\ProductionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CloseCompletedT4023 extends Command
{
    protected $signature = 'production:close-t4023';
    protected $description = 'Close and complete phantom/stuck T4023 running job whose target is already achieved';

    public function handle(): int
    {
        $this->info("=== CLOSING STUCK T4023 RUNNING SESSIONS ===");

        $runningJobs = JobMaster::where('line', 'like', '%A%')
            ->where(function ($q) {
                $q->where('job_number', 'like', '%T4023%')
                  ->orWhere('job_name', 'like', '%T4023%');
            })
            ->whereIn(DB::raw('LOWER(status)'), ['running', 'paused'])
            ->get();

        if ($runningJobs->isEmpty()) {
            $this->info("No running or paused T4023 found on Line A.");
            return 0;
        }

        $now = Carbon::now();

        foreach ($runningJobs as $job) {
            $this->info("Closing Job ID {$job->id} ({$job->job_number})...");

            // Close downtimes
            Downtime::where('job_master_id', $job->id)->whereNull('finish_time')->update([
                'finish_time' => $now,
            ]);

            // Close production sessions
            ProductionSession::where('job_master_id', $job->id)->whereIn('status', ['running', 'paused'])->update([
                'status' => 'finished',
                'finish_time' => $now,
            ]);

            // Mark job as complete
            $job->update([
                'status' => 'complete',
                'finished_at' => $now,
            ]);

            // Sync plan status
            $parts = explode('-', $job->job_number);
            $planId = end($parts);
            if (is_numeric($planId)) {
                ProductionPlan::where('id', $planId)->update(['status' => 'complete']);
            }

            $this->info("✓ Job {$job->job_number} successfully closed and marked complete.");
        }

        \App\Services\DashboardRealtimeService::flushCache();
        \App\Services\DashboardRealtimeService::signalUpdate('Press A');
        \App\Services\DashboardRealtimeService::signalUpdate('Line A');

        $this->info("Realtime cache flushed and signal sent.");

        return 0;
    }
}
