<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::whereDate('plan_date', '2026-05-08')
    ->orderBy('id', 'asc')
    ->get(['id', 'job_no', 'job_master', 'row_type', 'row_no', 'shift_name']);

echo "All rows for 2026-05-08:\n";
foreach($rows as $r) {
    echo "ID: {$r->id} | Shift: {$r->shift_name} | Type: {$r->row_type} | No: [{$r->row_no}] | Job: [{$r->job_no}] | Master: [{$r->job_master}]\n";
}
