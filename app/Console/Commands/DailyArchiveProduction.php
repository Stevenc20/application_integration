<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionSession;
use App\Models\Downtime;
use App\Models\DandoriSession;
use App\Models\JobMaster;
use Carbon\Carbon;

class DailyArchiveProduction extends Command
{
    protected $signature = 'production:daily-archive
                            {--date= : Force specific date (Y-m-d, default: yesterday)}';

    protected $description = 'Auto-complete stuck sessions, fix stuck downtimes from previous day(s)';

    public function handle(): int
    {
        $cutoffDate = $this->option('date')
            ? Carbon::parse($this->option('date')->toDateString())
            : Carbon::yesterday();

        $now = Carbon::now();
        $cutoff = $cutoffDate->copy()->startOfDay();
        $logFile = storage_path('logs/daily-archive.log');
        $stats = ['sessions' => 0, 'downtimes' => 0, 'dandori' => 0, 'jobs' => 0];

        $this->info("Archive cutoff: {$cutoff->toDateString()}");
        $this->newLine();

        // 1. Force-complete stuck production sessions (running/paused from before cutoff)
        $stuckSessions = ProductionSession::where('work_date', '<', $cutoff->toDateString())
            ->whereIn('status', ['running', 'paused'])
            ->get();

        if ($stuckSessions->count() > 0) {
            foreach ($stuckSessions as $session) {
                $session->update([
                    'status' => 'complete',
                    'finish_time' => $now->format('H:i:s'),
                ]);
                $stats['sessions']++;
            }
            $this->info("  Sessions: {$stats['sessions']} force-complete");
        } else {
            $this->line("  Sessions: 0 stuck");
        }

        // 2. Fix stuck downtimes (end_time null, start_time before cutoff)
        $stuckDowntimes = Downtime::whereNull('finish_time')
            ->where('start_time', '<', $cutoff->toDateTimeString())
            ->get();

        if ($stuckDowntimes->count() > 0) {
            foreach ($stuckDowntimes as $dt) {
                $start = Carbon::parse($dt->start_time);
                $duration = $start->diffInSeconds($now);
                $dt->update([
                    'finish_time' => $now->format('Y-m-d H:i:s'),
                    'duration_seconds' => $duration,
                ]);
                $stats['downtimes']++;
            }
            $this->info("  Downtimes: {$stats['downtimes']} fixed");
        } else {
            $this->line("  Downtimes: 0 stuck");
        }

        // 3. Force-complete stuck dandori sessions
        $stuckDandori = DandoriSession::where('created_at', '<', $cutoff->toDateTimeString())
            ->whereIn('status', ['running', 'active'])
            ->get();

        if ($stuckDandori->count() > 0) {
            foreach ($stuckDandori as $dandori) {
                $dandori->update([
                    'status' => 'complete',
                    'finish_time' => $now->format('H:i:s'),
                ]);
                $stats['dandori']++;
            }
            $this->info("  Dandori: {$stats['dandori']} force-complete");
        } else {
            $this->line("  Dandori: 0 stuck");
        }

        // 4. Reset ALL stale job_masters (running/paused/complete from before cutoff) to pending
        $stuckJobs = JobMaster::whereIn(DB::raw('LOWER(status)'), ['running', 'paused', 'complete', 'finished'])
            ->whereNotIn('id', function ($q) use ($cutoff) {
                $q->select('job_master_id')
                    ->from('production_sessions')
                    ->whereDate('work_date', '>=', $cutoff->toDateString());
            })
            ->get();

        if ($stuckJobs->count() > 0) {
            foreach ($stuckJobs as $job) {
                $job->update([
                    'status' => 'pending',
                    'started_at' => null,
                    'finished_at' => null,
                ]);
                $stats['jobs']++;
            }
            $this->info("  Jobs: {$stats['jobs']} reset to pending");
        } else {
            $this->line("  Jobs: 0 stuck");
        }

        $total = array_sum($stats);
        $this->newLine();

        if ($total > 0) {
            $this->info("Total fixed: {$total}");
            $logLine = "[{$now}] daily-archive: {$total} records fixed (sessions={$stats['sessions']}, downtimes={$stats['downtimes']}, dandori={$stats['dandori']}, jobs={$stats['jobs']}, cutoff={$cutoff->toDateString()})";
            file_put_contents($logFile, $logLine . PHP_EOL, FILE_APPEND);
        } else {
            $this->line("Nothing to fix.");
        }

        return Command::SUCCESS;
    }
}
