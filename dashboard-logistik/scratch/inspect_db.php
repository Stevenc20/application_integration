<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;

$allDates = ScheduleStamping::select('upload_date')->distinct()->pluck('upload_date');
echo "Dates: " . $allDates->implode(', ') . "\n";

$first = ScheduleStamping::first();
if ($first) {
    echo "Sample Shift: " . $first->shift_name . "\n";
    echo "Sample Press: " . $first->press_name . "\n";
}
