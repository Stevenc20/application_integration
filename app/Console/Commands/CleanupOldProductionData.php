<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionDataTrash;
use App\Models\User;
use App\Notifications\DataTrashNotification;
use Carbon\Carbon;

class CleanupOldProductionData extends Command
{
    protected $signature = 'production:cleanup
                            {--dry-run : Only count records, do not move to trash}
                            {--months= : Override retention period in months}';

    protected $description = 'Move old production data to recycle bin';

    protected array $tables = [
        'repair_reject_images' => 'created_at',
        'production_sessions' => 'work_date',
        'production_logs' => 'created_at',
        'dandori_sessions' => 'created_at',
        'dandori_groups' => 'created_at',
        'dandori_details' => 'created_at',
        'machine_logs' => 'created_at',
    ];

    public function handle(): int
    {
        $months = (int)($this->option('months') ?: env('DATA_RETENTION_MONTHS', 6));
        $dryRun = $this->option('dry-run');
        $cutoff = Carbon::now()->subMonths($months)->startOfDay();
        $cutoffDate = $cutoff->format('Y-m-d');

        $this->info("Retention period: {$months} months");
        $this->info("Cutoff date: {$cutoffDate}");
        $this->info('Tables kept forever (skipped): daily_productions, production_plans, job_masters, downtimes, hambatan_jalur, repair_reject_logs, dandoris');
        if ($dryRun) {
            $this->warn('─── DRY RUN MODE ───');
            $this->newLine();
        }
        $this->newLine();

        $totalTrashed = 0;

        foreach ($this->tables as $table => $column) {
            $count = $this->countOldRecords($table, $column, $cutoff);
            $this->line(sprintf(
                '  %-25s %s %s',
                $table,
                $count > 0 ? "\033[33m{$count}\033[0m" : "\033[90m0\033[0m",
                $count > 0 ? 'records' : ''
            ));

            if ($count > 0 && !$dryRun) {
                $trashed = $this->moveToTrash($table, $column, $cutoff);
                $totalTrashed += $trashed;
                $this->info("    ✓ {$trashed} moved to recycle bin");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN — Total records that would be moved: {$totalTrashed}");
        } else {
            $this->info("Done. Total records moved to recycle bin: {$totalTrashed}");

            if ($totalTrashed > 0) {
                $superadmins = User::where('role', 'superadmin')->where('is_active', 1)->get();
                $detail = 'Tables: ' . implode(', ', array_keys($this->tables));
                foreach ($superadmins as $user) {
                    $user->notify(new DataTrashNotification('trashed', $totalTrashed, $detail));
                }
            }

            $logFile = storage_path('logs/cleanup.log');
            $logLine = '[' . now() . "] Cleanup: {$totalTrashed} records moved to trash (cutoff: {$cutoffDate})";
            file_put_contents($logFile, $logLine . PHP_EOL, FILE_APPEND);
        }

        return Command::SUCCESS;
    }

    private function countOldRecords(string $table, string $column, Carbon $cutoff): int
    {
        return DB::table($table)
            ->where($column, '<', $cutoff)
            ->count();
    }

    private function moveToTrash(string $table, string $column, Carbon $cutoff): int
    {
        $records = DB::table($table)
            ->where($column, '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($records as $record) {
            $recordArray = (array) $record;
            $id = $recordArray['id'] ?? null;
            if (!$id) continue;

            try {
                ProductionDataTrash::create([
                    'original_table' => $table,
                    'original_id' => $id,
                    'data' => $recordArray,
                    'trashed_at' => now(),
                    'trashed_by' => 'command:production:cleanup',
                    'expires_at' => now()->addDays(14),
                ]);
                DB::table($table)->where('id', $id)->delete();
                $count++;
            } catch (\Exception $e) {
                $this->warn("    Failed to trash {$table}#{$id}: {$e->getMessage()}");
            }
        }

        return $count;
    }
}
