<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::latest('id')
    ->take(50)
    ->get(['id', 'plan_date', 'job_no', 'row_type', 'shift_name', 'press_name']);

echo "Latest 50 rows in DB:\n";
foreach($rows as $r) {
    echo "ID: {$r->id} | Date: [{$r->plan_date}] | Shift: [{$r->shift_name}] | Press: [{$r->press_name}] | Job: [{$r->job_no}]\n";
}
