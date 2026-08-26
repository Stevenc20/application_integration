<?php
// File: test_api_match.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// KITA GUNAKAN TANGGAL HARI INI AGAR TIDAK KENA REDIRECT KADALUWARSA DI WEB
$date = date('Y-m-d');
$shift = 'Shift Pagi';
$line = 'LINE A';

echo "TANGGAL : $date\n";
echo "SHIFT   : $shift\n";
echo "LINE    : $line\n\n";

// 2. Eksekusi Input Harian Controller DULU, biar kita tau dia redirect ke shift/date apa
$reqWeb = Illuminate\Http\Request::create('/operational/input-harian', 'GET', [
    'date' => $date, 'shift' => $shift, 'line' => $line
]);
auth()->loginUsingId(1);

$controller = app(\App\Http\Controllers\Operational\InputHarianController::class);
$view = $controller->index($reqWeb);

$finalDate = $date;
$finalShift = $shift;

if ($view instanceof \Illuminate\Http\RedirectResponse) {
    $parsedUrl = parse_url($view->getTargetUrl());
    parse_str($parsedUrl['query'] ?? '', $queryParams);
    
    $finalDate = $queryParams['date'] ?? $date;
    $finalShift = $queryParams['shift'] ?? $shift;
    
    $reqWeb = Illuminate\Http\Request::create('/operational/input-harian', 'GET', array_merge([
        'date' => $date, 'line' => $line
    ], $queryParams));
    
    echo "(Mengikuti internal redirect Controller ke Date: $finalDate, Shift: $finalShift)\n\n";
    $view = $controller->index($reqWeb);
}

// 1. Eksekusi API secara internal menggunakan Final Date & Shift hasil evaluasi Web
$reqApi = Illuminate\Http\Request::create('/api/v1/ppc/item-check', 'GET', [
    'date' => $finalDate, 'shift' => $finalShift, 'line' => $line
]);
$reqApi->headers->set('Authorization', 'Bearer qa-super-secret-token');
$resApi = $kernel->handle($reqApi);
$rawApiContent = $resApi->getContent();
$apiData = json_decode($rawApiContent, true)['data'] ?? [];

$webJobs = $view->getData()['jobs'] ?? [];

echo "INPUT HARIAN (Web)\n";
$webMapped = [];
foreach ($webJobs as $job) {
    if ($job->row_type === 'break') continue;
    
    $jn = trim($job->job_no ?? '');
    $jm = trim($job->job_master ?? '');
    $target = (int) ($job->plan ?? $job->target_qty ?? 0);
    
    $jobNumber = $jn ? ($jn . '-' . $job->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $job->id);
    $jmRecord = \App\Models\JobMaster::where('job_number', $jobNumber)
                ->with(['dailyProduction' => function ($q) use ($finalDate) { $q->where('work_date', $finalDate); }])
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
