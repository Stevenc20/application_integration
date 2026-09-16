<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$date = '2026-08-18';
$plans = \App\Models\ProductionPlan::where('plan_date', $date)->get(['id', 'press_name', 'shift_name', 'row_type', 'remaining_plan', 'job_no']);

foreach ($plans as $p) {
    echo "id={$p->id} press={$p->press_name} shift={$p->shift_name} row_type={$p->row_type} remaining={$p->remaining_plan} job={$p->job_no}\n";
}
