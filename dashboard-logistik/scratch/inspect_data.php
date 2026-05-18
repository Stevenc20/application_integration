<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;

$date = '2026-05-05';
$shift = 'Selasa Pagi';
$press = 'PRESS A';

$items = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->orderBy('id')
    ->get();

foreach ($items as $item) {
    echo sprintf("ID: %d | Type: %s | Job: %s | Start: %s | Finish: %s | TPT: %s | Plan: %s\n",
        $item->id, $item->row_type, $item->job_no, $item->start_time, $item->finish_time, $item->tpt, $item->plan);
}
