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

// Row 14 (ID 1214) and Row 15 (ID 1215)
$r14 = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('row_no', 14)
    ->first();

$r15 = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('row_no', 15)
    ->first();

if ($r14 && $r15 && $r14->job_no === $r15->job_no) {
    echo "Rebalancing Plan for " . $r14->job_no . " to align with break at 18:00...\n";
    echo "Original: Row 14 Plan " . $r14->plan . ", Row 15 Plan " . $r15->plan . "\n";
    
    // To finish at 18:00 starting from 16:51, we need 69 mins TPT.
    // TPT = (Plan * CT / 60) + DCT
    // 69 = (Plan * 15 / 60) + 30
    // 39 = Plan * 0.25
    // Plan = 39 / 0.25 = 156.
    
    $totalPlan = $r14->plan + $r15->plan; // 100 + 350 = 450
    
    $r14->plan = 156;
    $r15->plan = $totalPlan - 156; // 294
    
    $r14->save();
    $r15->save();
    
    echo "New: Row 14 Plan " . $r14->plan . ", Row 15 Plan " . $r15->plan . "\n";
    
    // Trigger recalculation
    $controller = new ScheduleStampingController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('recalculateAndCascade');
    $method->setAccessible(true);
    $method->invokeArgs($controller, [$r14]);
    
    echo "Recalculation done.\n";
} else {
    echo "Rows not found or job_no mismatch.\n";
}
