<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;
use App\Http\Controllers\ScheduleStampingController;

$date = '05 MEI 2026';
$shift = 'Shift Pagi';
$press = 'PRESS A';

// Delete the PERSIAPAN SORE break
ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('job_no', 'PERSIAPAN SORE')
    ->delete();

echo "Deleted PERSIAPAN SORE. Triggering recalculation...\n";

$controller = new ScheduleStampingController();
$reflection = new ReflectionClass($controller);

// Ensure breaks are updated (will update Istirahat Sore to 18:00)
$ensureBreaks = $reflection->getMethod('ensureStandardBreaks');
$ensureBreaks->setAccessible(true);
$ensureBreaks->invokeArgs($controller, [$date, $shift, $press]);

// Cascade
$firstItem = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->orderBy('id')
    ->first();

if ($firstItem) {
    $method = $reflection->getMethod('recalculateAndCascade');
    $method->setAccessible(true);
    $method->invokeArgs($controller, [$firstItem]);
}

echo "Done. Total Plan still: " . ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('row_type', 'job')
    ->sum('plan') . "\n";
