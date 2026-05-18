<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;

$date = '05 MEI 2026';
$shift = 'Shift Pagi';
$press = 'PRESS A';

$sum = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('row_type', 'job')
    ->sum('plan');

echo "Total Plan in DB for $shift: " . $sum . "\n";

$items = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('row_type', 'job')
    ->orderBy('row_no')
    ->get(['row_no', 'job_no', 'plan']);

foreach ($items as $item) {
    echo "Row " . $item->row_no . ": " . $item->job_no . " -> Plan " . $item->plan . "\n";
}
