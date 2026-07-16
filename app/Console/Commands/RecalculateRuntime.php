<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionSession;
use App\Models\DailyProduction;
use Carbon\Carbon;

class RecalculateRuntime extends Command
{
    protected $signature = 'runtime:recalculate
                            {--dry-run : Only show what would change, do not update}';

    protected $description = 'Recalculate runtime_seconds for completed sessions using actual timeline';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $sessions = ProductionSession::whereIn('status', ['finished', 'completed'])
            ->whereNotNull('start_time')
            ->whereNotNull('finish_time')
            ->get();

        $this->info("Found {$sessions->count()} completed sessions");
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($sessions->count());
        $bar->start();

        foreach ($sessions as $session) {
            try {
                $startTime = $session->start_time instanceof Carbon
                    ? $session->start_time
                    : Carbon::parse($session->start_time);

                $finishTime = $session->finish_time instanceof Carbon
                    ? $session->finish_time
                    : Carbon::parse($session->finish_time);

                $wallClock = (int)round(abs($finishTime->floatDiffInSeconds($startTime)));
                $current = (int)$session->total_seconds;
                $diff = abs($wallClock - $current);

                if ($current === 0) {
                    // Bug: total_seconds was never saved — set to wall clock
                    $recalculated = $wallClock;
                } elseif ($diff <= 2) {
                    // Pure rounding fix (floor → round): adjust by ±1s
                    $recalculated = $wallClock;
                } else {
                    // Pause/resume cycles or data issue — keep original
                    $recalculated = $current;
                }

                if ($recalculated === (int)$session->total_seconds) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    $session->update(['total_seconds' => $recalculated]);

                    DailyProduction::where('job_master_id', $session->job_master_id)
                        ->where('work_date', $session->work_date)
                        ->update(['runtime_seconds' => $recalculated]);
                }

                $updated++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("  Error session #{$session->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->warn("DRY RUN — Sessions that would change: {$updated}");
            $this->info("Already correct: {$skipped}");
        } else {
            $this->info("Updated: {$updated} sessions");
            $this->info("Already correct: {$skipped}");
        }

        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }

        return Command::SUCCESS;
    }
}
