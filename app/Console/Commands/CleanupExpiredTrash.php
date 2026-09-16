<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionDataTrash;
use App\Models\User;
use App\Notifications\DataTrashNotification;

class CleanupExpiredTrash extends Command
{
    protected $signature = 'production:cleanup-expired
                            {--dry-run : Only count records, do not delete}';

    protected $description = 'Permanently delete expired trash records';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $expired = ProductionDataTrash::expired()->get();

        if ($expired->isEmpty()) {
            $this->info('No expired trash records found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expired->count()} expired records to delete permanently.");
        $grouped = $expired->groupBy('original_table');

        foreach ($grouped as $table => $items) {
            $this->line("  {$table}: {$items->count()} records");
        }

        if ($dryRun) {
            $this->warn('DRY RUN — No records were deleted.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $trash) {
            $trash->update(['deleted_at' => now()]);
            $trash->delete();
            $count++;
        }

        $this->info("Done. {$count} expired records permanently deleted.");

        if ($count > 0) {
            $superadmins = User::where('role', 'superadmin')->where('is_active', 1)->get();
            foreach ($superadmins as $user) {
                $user->notify(new DataTrashNotification('deleted', $count, 'Data expired telah dibersihkan dari Recycle Bin.'));
            }
        }

        $logFile = storage_path('logs/cleanup-expired.log');
        $logLine = '[' . now() . "] Cleanup expired: {$count} records permanently deleted";
        file_put_contents($logFile, $logLine . PHP_EOL, FILE_APPEND);

        return Command::SUCCESS;
    }
}
