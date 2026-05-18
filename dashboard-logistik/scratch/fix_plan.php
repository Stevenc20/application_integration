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

// Find row 11 for this section
$item = ScheduleStamping::where('upload_date', $date)
    ->where('shift_name', $shift)
    ->where('press_name', $press)
    ->where('row_no', 11)
    ->first();

if ($item) {
    echo "Updating Row 11 Plan from " . $item->plan . " to 345...\n";
    $item->plan = 345;
    $item->save();
    
    // Now trigger a full cascade
    echo "Recalculating...\n";
    $controller = new ScheduleStampingController();
    $reflection = new ReflectionClass($controller);
    
    // Ensure breaks are updated first
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
    
    echo "Done. New sum: " . ScheduleStamping::where('upload_date', $date)
        ->where('shift_name', $shift)
        ->where('press_name', $press)
        ->where('row_type', 'job')
        ->sum('plan') . "\n";
} else {
    echo "Row 11 not found.\n";
}
