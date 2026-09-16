<?php

namespace App\Console\Commands;

use App\Models\NetworkAccessLog;
use App\Models\NetworkLatency;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanNetworkLogs extends Command
{
    protected $signature = 'network:clean-logs {--days=30 : Delete logs older than N days}';
    protected $description = 'Clean old network access logs and latency data';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $deletedLogs = NetworkAccessLog::where('created_at', '<', $cutoff)->delete();
        $deletedLatency = NetworkLatency::where('measured_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deletedLogs} access logs and {$deletedLatency} latency records older than {$days} days");
        return Command::SUCCESS;
    }
}
