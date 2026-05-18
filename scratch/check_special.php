<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ProductionPlan;

$rows = ProductionPlan::where('job_no', 'like', '%Fnish%')
    ->orWhere('job_master', 'like', '%Fnish%')
    ->orWhere('job_no', 'like', '%ISTIRAHAT%')
    ->get();

echo "Found " . $rows->count() . " special rows.\n";
foreach ($rows as $r) {
    echo "ID: {$r->id} | Master: {$r->job_master} | No: {$r->job_no} | Date: {$r->plan_date}\n";
}
