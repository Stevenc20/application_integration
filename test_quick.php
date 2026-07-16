<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->login(\App\Models\User::first());
$request = \Illuminate\Http\Request::create('/operational/input-harian?line=PRESS+D&date=2026-07-15', 'GET');
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle($request);

$controller = app(\App\Http\Controllers\Operational\InputHarianController::class);
$response = $controller->index($request);
$viewData = $response->getData();
$html = view('operational.input_harian', (array) $viewData)->render();

// Check _breakSchedule
if (preg_match('/window\._breakSchedule\s*=\s*(\[.*?\]);/s', $html, $m)) {
    echo "=== _breakSchedule ===" . PHP_EOL;
    $schedule = json_decode($m[1], true);
    foreach ($schedule as $s) {
        echo "  {$s['label']} {$s['start']}-{$s['end']} [{$s['startMin']}-{$s['endMin']}]" . PHP_EOL;
    }
    $nowMin = (int)now()->format('H') * 60 + (int)now()->format('i');
    echo "Now: " . now()->format('H:i') . " ($nowMin min)" . PHP_EOL;
    foreach ($schedule as $s) {
        if ($nowMin >= $s['startMin'] && $nowMin < $s['endMin']) {
            echo "*** ACTIVE: {$s['label']} ***" . PHP_EOL;
        }
    }
}

// Check ProductionConfig
if (preg_match('/currentStatus:\s*[\'"]([^\'"]*)[\'"]/', $html, $m2)) {
    echo PHP_EOL . "currentStatus: {$m2[1]}" . PHP_EOL;
}
if (preg_match('/currentActiveId:\s*(\d+)/', $html, $m3)) {
    echo "currentActiveId: {$m3[1]}" . PHP_EOL;
}
if (preg_match('/isLocked:\s*(true|false)/', $html, $m4)) {
    echo "isLocked: {$m4[1]}" . PHP_EOL;
}

// Check jobMasterData status for active job
if (preg_match('/"id":\s*(\d+),\s*\n\s*status:\s*[\'"]([^\'"]*)[\'"]/', $html, $m5)) {
    echo "jobMasterData status: {$m5[2]}" . PHP_EOL;
}

// Check which JS file is loaded
if (preg_match('/production-engine[^"]*\.js/', $html, $m6)) {
    echo PHP_EOL . "JS file: {$m6[0]}" . PHP_EOL;
}
