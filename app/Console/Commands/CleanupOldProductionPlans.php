<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use Carbon\Carbon;

class CleanupOldProductionPlans extends Command
{
    protected $signature = 'ppc:cleanup-old-plans
                            {--dry-run : Only count records, do not delete}
                            {--days=1 : Delete plans older than N days}';

    protected $description = 'Delete old ProductionPlan and related JobMaster data for past production dates';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($days)->startOfDay();
        $cutoffDate = $cutoff->format('Y-m-d');

        $this->info("Delete plans before: {$cutoffDate} (older than {$days} day(s))");
        if ($dryRun) {
            $this->warn('─── DRY RUN MODE ───');
        }
        $this->newLine();

        $planCount = ProductionPlan::whereDate('plan_date', '<', $cutoffDate)->count();
        $this->line("  ProductionPlans to delete: {$planCount}");

        $jobCount = JobMaster::whereNull('job_number')->orWhere('job_number', 'like', 'AUTO-%')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->count();
        $this->line("  Pending JobMasters to delete: {$jobCount}");

        if ($dryRun) {
            $this->newLine();
            $this->warn("DRY RUN — No records deleted.");
            return Command::SUCCESS;
        }

        // Delete ProductionPlan records for old dates
        $deletedPlans = ProductionPlan::whereDate('plan_date', '<', $cutoffDate)->delete();
        $this->info("✓ {$deletedPlans} ProductionPlan records deleted.");

        // Delete orphaned pending JobMaster records
        $deletedJobs = JobMaster::whereNull('job_number')->orWhere('job_number', 'like', 'AUTO-%')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->delete();
        $this->info("✓ {$deletedJobs} pending JobMaster records deleted.");

        $this->newLine();
        $this->info("Done. Cleanup completed for dates before {$cutoffDate}.");

        $logLine = '[' . now() . "] ppc:cleanup-old-plans: {$deletedPlans} plans, {$deletedJobs} jobs deleted (cutoff: {$cutoffDate})";
        file_put_contents(storage_path('logs/ppc-cleanup.log'), $logLine . PHP_EOL, FILE_APPEND);

        return Command::SUCCESS;
    }
}
