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

// Schedule: cleanup old production plans daily at 00:30
Schedule::command('ppc:cleanup-old-plans')
    ->dailyAt('00:30')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/ppc-cleanup-scheduler.log'));

// Schedule: process shift cut-off (Pagi) daily at 21:00
Schedule::command('ppc:process-cutoff')
    ->dailyAt('21:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/ppc-cutoff-scheduler.log'));

// Schedule: process shift cut-off (Press C extended) daily at 22:00
Schedule::command('ppc:process-cutoff')
    ->dailyAt('22:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/ppc-cutoff-scheduler.log'));

// Schedule: process shift cut-off (Malam) daily at 07:30
Schedule::command('ppc:process-cutoff')
    ->dailyAt('07:30')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/ppc-cutoff-scheduler.log'));

// Schedule: auto-break pause/resume every minute
Schedule::command('break:auto')
    ->everyMinute()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/auto-break-scheduler.log'));
