<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;
use App\Http\Controllers\ScheduleStampingController;

$date = '05 MEI 2026';
$shift = 'Shift Pagi (Rev)';
$press = 'PRESS A';

$firstItem = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->orderBy('id')
    ->first();

if ($firstItem) {
    echo "Recalculating for $date, $shift, $press...\n";
    $controller = new ScheduleStampingController();
    
    $reflection = new ReflectionClass($controller);
    
    $ensureBreaks = $reflection->getMethod('ensureStandardBreaks');
    $ensureBreaks->setAccessible(true);
    $ensureBreaks->invokeArgs($controller, [$date, $shift, $press]);

    $method = $reflection->getMethod('recalculateAndCascade');
    $method->setAccessible(true);
    $method->invokeArgs($controller, [$firstItem]);
    
    echo "Done.\n";
    
    $items = ScheduleStamping::where('upload_date', $date)
        ->where('shift_name', $shift)
        ->where('press_name', $press)
        ->orderByRaw('start_time IS NULL, start_time ASC')
        ->orderBy('id')
        ->get();

    foreach ($items as $item) {
        echo sprintf("ID: %d | Type: %s | JobNo: %s | Start: %s | Finish: %s | TPT: %s\n",
            $item->id, $item->row_type, $item->job_no, $item->start_time, $item->finish_time, $item->tpt);
    }
} else {
    echo "No items found.\n";
}
