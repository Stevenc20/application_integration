<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;

$date = '05 MEI 2026';
$shift = 'Shift Pagi';
$press = 'PRESS A';

$items = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->orderByRaw('start_time IS NULL, start_time ASC')
    ->orderBy('id', 'asc')
    ->get();

foreach ($items as $item) {
    echo sprintf("ID: %d | Type: %s | JobNo: %s | Start: %s | Finish: %s | TPT: %s\n",
        $item->id, $item->row_type, $item->job_no, $item->start_time, $item->finish_time, $item->tpt);
}
