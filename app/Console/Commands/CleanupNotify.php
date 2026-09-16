<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\DataCleanupWarning;
use Carbon\Carbon;

class CleanupNotify extends Command
{
    protected $signature = 'production:cleanup-notify
                            {--force : Send even if already notified this month}';

    protected $description = 'Send warning notification 1 week before data cleanup';

    public function handle(): int
    {
        $months = (int) env('DATA_RETENTION_MONTHS', 6);
        $cutoff = Carbon::now()->subMonths($months)->startOfDay();
        $executionDate = $cutoff->copy()->addMonths($months)->format('d F Y');

        $logFile = storage_path('logs/cleanup-notify.log');
        $lastNotify = null;
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lastLine = !empty($lines) ? trim(end($lines)) : '';
            if ($lastLine && preg_match('/^\[(.*?)\]/', $lastLine, $m)) {
                $lastNotify = $m[1];
            }
        }

        if ($lastNotify && !$this->option('force')) {
            $lastDate = Carbon::parse($lastNotify);
            if ($lastDate->diffInDays(now()) < 20) {
                $this->info("Already notified on {$lastDate->format('Y-m-d')}. Skipping.");
                return Command::SUCCESS;
            }
        }

        $roles = ['admin', 'supervisor', 'manager', 'kadiv', 'direktur', 'presdir'];
        $users = User::whereIn('role', $roles)->where('is_active', 1)->get();

        if ($users->isEmpty()) {
            $this->warn('No active users found with roles: ' . implode(', ', $roles));
            return Command::SUCCESS;
        }

        $this->info("Sending warning to {$users->count()} users...");

        $sent = 0;
        foreach ($users as $user) {
            try {
                $user->notify(new DataCleanupWarning($months));
                $sent++;
            } catch (\Exception $e) {
                $this->warn("Failed to notify {$user->name}: {$e->getMessage()}");
            }
        }

        $logLine = '[' . now() . "] Notified {$sent} users about {$months}-month retention policy";
        file_put_contents($logFile, $logLine . PHP_EOL, FILE_APPEND);

        $this->info("Done. {$sent} users notified.");
        return Command::SUCCESS;
    }
}
