<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: move old production data to recycle bin daily at 2 AM
Schedule::command('production:cleanup')
    ->dailyAt('02:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/cleanup-scheduler.log'));

// Schedule: permanently delete expired trash daily at 3 AM
Schedule::command('production:cleanup-expired')
    ->dailyAt('03:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/cleanup-expired-scheduler.log'));

// Schedule: send warning notification every Sunday at 8 AM
Schedule::command('production:cleanup-notify')
    ->weeklyOn(0, '08:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/cleanup-notify-scheduler.log'));

// PPC schedule cleanup is intentionally NOT scheduled automatically.
// Deleting past ProductionPlan rows caused historical schedules to disappear
// after an import on the following day. Run `ppc:cleanup-old-plans --dry-run`
// manually and only delete after an explicit retention decision.

// Schedule: auto-break pause/resume every minute
Schedule::command('break:auto')
    ->everyMinute()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/auto-break-scheduler.log'));
