<?php
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

        if ($currentMins <= $bStart && $finishMins > $bStart) {
            $finishMins += $bDuration;
        }
    }

    return minutesToTime($finishMins);
}

echo "11:35 + 52 mins = " . calculateFinishWithBreaks("11:35", 52) . "\n";
echo "11:35 + 25 mins = " . calculateFinishWithBreaks("11:35", 25) . "\n";
?>
