<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::where('shift_name', 'like', '%REV%')
    ->where('row_type', 'break')
    ->latest()
    ->take(10)
    ->get(['id', 'job_no', 'job_master', 'keterangan', 'row_no', 'shift_name']);

echo "Data for REV shifts:\n";
foreach($rows as $r) {
    echo "Shift: {$r->shift_name} | ID: {$r->id} | No: [{$r->row_no}] | JobNo: [{$r->job_no}] | Master: [{$r->job_master}] | Ket: [{$r->keterangan}]\n";
}
