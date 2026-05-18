<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::where('row_type', 'break')
    ->latest()
    ->take(5)
    ->get(['id', 'job_no', 'job_master', 'keterangan', 'row_no']);

foreach($rows as $r) {
    echo "ID: {$r->id} | No: [{$r->row_no}] | JobNo: [{$r->job_no}] | Master: [{$r->job_master}] | Ket: [{$r->keterangan}]\n";
}
