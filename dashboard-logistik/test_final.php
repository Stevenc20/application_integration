<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\ScheduleStampingController();
$item = App\Models\ScheduleStamping::find(650);
if ($item) {
    echo "Before: Start {$item->start_time}, Finish {$item->finish_time}\n";
    // We can't call private method directly, but we can trigger it via updateInline or just test the logic
}

// Manually testing the calculateFinishWithBreaks logic via a reflection if needed, 
// or just trust the test_logic.php which I already ran.

function timeToMinutes($timeStr) {
    if (!$timeStr || $timeStr === '-') return 0;
    $parts = explode(':', $timeStr);
    if (count($parts) < 2) return 0;
    return (int)$parts[0] * 60 + (int)$parts[1];
}

function minutesToTime($mins) {
    $h = floor($mins / 60) % 24;
    $m = $mins % 60;
    return sprintf('%02d:%02d', $h, $m);
}

function calculateFinishWithBreaks($startTime, $duration) {
    $currentMins = timeToMinutes($startTime);
    $finishMins = $currentMins + $duration;
    
    $fixedBreaks = [
        ['start' => '12:00', 'finish' => '12:40'],
        ['start' => '15:15', 'finish' => '15:30'],
        ['start' => '18:00', 'finish' => '18:30'],
    ];

    foreach ($fixedBreaks as $b) {
        $bStart = timeToMinutes($b['start']);
        $bEnd   = timeToMinutes($b['finish']);
        $bDuration = $bEnd - $bStart;

        if ($bDuration <= 0) continue;

        if ($currentMins < $bStart && $finishMins > $bStart) {
            $finishMins += $bDuration;
        }
    }

    return minutesToTime($finishMins);
}

echo "Testing 11:35 + 52 mins: " . calculateFinishWithBreaks("11:35", 52) . "\n";
?>
