<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::where('job_no', 'like', '%ISTIRAHAT%')
    ->orWhere('job_no', 'like', '%CINGKORAK%')
    ->orWhere('job_no', 'like', '%BREAKTIME%')
    ->orWhere('job_no', 'like', '%TOTAL%')
    ->get(['id', 'job_no', 'row_type', 'plan_date']);

foreach($rows as $r) {
    echo "ID: {$r->id} | No: {$r->job_no} | Type: {$r->row_type} | Date: {$r->plan_date}\n";
}
