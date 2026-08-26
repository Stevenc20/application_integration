<?php
// File: test_api_match.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$date = '2026-08-25';
$shift = 'Shift Pagi';
$line = 'LINE A';

echo "TANGGAL : $date\n";
echo "SHIFT   : $shift\n";
echo "LINE    : $line\n\n";

// 1. Eksekusi API secara internal
$reqApi = Illuminate\Http\Request::create('/api/v1/ppc/item-check', 'GET', [
    'date' => $date, 'shift' => $shift, 'line' => $line
]);
$reqApi->headers->set('Authorization', 'Bearer qa-super-secret-token');
$resApi = $kernel->handle($reqApi);
$apiData = json_decode($resApi->getContent(), true)['data'] ?? [];

// 2. Eksekusi Input Harian Controller
$reqWeb = Illuminate\Http\Request::create('/operational/input-harian', 'GET', [
    'date' => $date, 'shift' => $shift, 'line' => $line
]);
auth()->loginUsingId(1);

$controller = app(\App\Http\Controllers\Operational\InputHarianController::class);
$view = $controller->index($reqWeb);

// Tangani FORCE REDIRECT jika shift memiliki revisi terbaru (misal: "Shift Pagi - Revisi 1")
if ($view instanceof \Illuminate\Http\RedirectResponse) {
    $parsedUrl = parse_url($view->getTargetUrl());
    parse_str($parsedUrl['query'] ?? '', $queryParams);
    $reqWeb = Illuminate\Http\Request::create('/operational/input-harian', 'GET', array_merge([
        'date' => $date, 'line' => $line
    ], $queryParams));
    
    echo "(Mengikuti internal redirect Controller ke: " . $reqWeb->query('shift') . ")\n\n";
    $view = $controller->index($reqWeb);
}

$webJobs = $view->getData()['jobs'] ?? [];

echo "INPUT HARIAN\n";
$webMapped = [];
foreach ($webJobs as $job) {
    $jn = trim($job->job_no ?? '');
    $jm = trim($job->job_master ?? '');
    $target = (int) ($job->plan ?? $job->target_qty ?? 0);
    
    $jobNumber = $jn ? ($jn . '-' . $job->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $job->id);
    $jmRecord = \App\Models\JobMaster::where('job_number', $jobNumber)
                ->with(['dailyProduction' => function ($q) use ($date) { $q->where('work_date', $date); }])
                ->first();
                
    $actualOk = $jmRecord ? (int)($jmRecord->dailyProduction->actual_ok ?? 0) : 0;
    $repair   = $jmRecord ? (int)($jmRecord->dailyProduction->actual_repair ?? $jmRecord->dailyProduction->repair_qty ?? 0) : 0;
    $reject   = $jmRecord ? (int)($jmRecord->dailyProduction->actual_reject ?? $jmRecord->dailyProduction->reject_qty ?? 0) : 0;
    
    $start = substr($job->start_time ?? '', 0, 5);
    $finish = substr($job->finish_time ?? '', 0, 5);
    
    $rowStr = "$jm | Plan $target | Actual $actualOk | Rep $repair | Rej $reject | Start $start | Finish $finish";
    echo $rowStr . "\n";
    $webMapped[] = $rowStr;
}

echo "\nAPI\n";
$apiMapped = [];
foreach ($apiData as $item) {
    $rowStr = $item['job_master'] . " | Plan " . $item['plan_qty'] . " | Actual " . $item['actual_ok'] . " | Rep " . $item['repair'] . " | Rej " . $item['reject'] . " | Start " . $item['plan_start'] . " | Finish " . $item['plan_finish'];
    echo $rowStr . "\n";
    $apiMapped[] = $rowStr;
}

echo "\nSTATUS: " . ($webMapped === $apiMapped ? "MATCH 100%" : "MISMATCH") . "\n";
