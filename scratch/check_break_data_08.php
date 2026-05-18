<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::whereDate('plan_date', '2026-05-08')
    ->where('row_type', 'break')
    ->get(['id', 'job_no', 'job_master', 'keterangan', 'row_no', 'shift_name']);

echo "Data for 2026-05-08:\n";
foreach($rows as $r) {
    echo "Shift: {$r->shift_name} | ID: {$r->id} | No: [{$r->row_no}] | JobNo: [{$r->job_no}] | Master: [{$r->job_master}] | Ket: [{$r->keterangan}]\n";
}
