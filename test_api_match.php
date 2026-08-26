<?php
// File: test_api_match.php
// Jalankan dengan: docker compose exec app php test_api_match.php

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

// 2. Eksekusi Input Harian Controller secara internal (mensimulasikan request web)
$reqWeb = Illuminate\Http\Request::create('/operational/input-harian', 'GET', [
    'date' => $date, 'shift' => $shift, 'line' => $line
]);
// Bypass auth untuk testing controller internal
auth()->loginUsingId(1); // pastikan ada user id 1 (atau bypass)

// Kita bisa langsung memanggil method-nya untuk mendapatkan raw datanya sebelum jadi HTML
$controller = app(\App\Http\Controllers\Operational\InputHarianController::class);
// Untuk mendapatkan raw datanya, kita ekstrak langsung array yang akan di-pass ke view
// Berhubung index() me-return view(), kita bisa menangkap reflection / view data.
$view = $controller->index($reqWeb);
$webJobs = $view->getData()['jobs'] ?? [];

echo "INPUT HARIAN\n";
$webMapped = [];
foreach ($webJobs as $job) {
    $jn = trim($job->job_no ?? '');
    $jm = trim($job->job_master ?? '');
    $target = (int) ($job->plan ?? $job->target_qty ?? 0);
    
    // Identifikasi aktual persis seperti Input Harian Blade
    $actualOk = 0;
    if ($job->job_number && isset($view->getData()['jobMasters'])) {
         // ini asumsi, tapi kita tembak langsung dari model relasi yang di-load
    }
    
    // Karena logic view lumayan panjang, kita query JobMaster nya langsung dari job_number
    $jobNumber = $jn ? ($jn . '-' . $job->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $job->id);
    $jmRecord = \App\Models\JobMaster::where('job_number', $jobNumber)
                ->with(['dailyProduction' => function ($q) use ($date) { $q->where('work_date', $date); }])
                ->first();
                
    $actualOk = $jmRecord ? (int)($jmRecord->dailyProduction->actual_ok ?? 0) : 0;
    $start = substr($job->start_time ?? '', 0, 5);
    $finish = substr($job->finish_time ?? '', 0, 5);
    
    $rowStr = "$jm | Plan $target | Actual $actualOk | Start $start | Finish $finish";
    echo $rowStr . "\n";
    $webMapped[] = $rowStr;
}

echo "\nAPI\n";
$apiMapped = [];
foreach ($apiData as $item) {
    $rowStr = $item['job_master'] . " | Plan " . $item['plan_qty'] . " | Actual " . $item['actual_ok'] . " | Start " . $item['plan_start'] . " | Finish " . $item['plan_finish'];
    echo $rowStr . "\n";
    $apiMapped[] = $rowStr;
}

echo "\nSTATUS: " . ($webMapped === $apiMapped ? "MATCH" : "MISMATCH") . "\n";
