<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductionPlan;

$date = '2026-05-11'; // Assuming this is the date the user is testing
$plans = ProductionPlan::whereDate('plan_date', $date)->get();

echo "Total rows found: " . $plans->count() . "\n";
foreach ($plans as $p) {
    $combined = $p->job_master . " | " . $p->job_no . " | " . $p->keterangan;
    if (preg_match('/(CINGKORAK|FINISH|FNISH|BREAKTI|ISTIRAHAT)/i', $combined)) {
        echo "ID: {$p->id} | Shift: {$p->shift_name} | RowType: {$p->row_type} | Content: {$combined}\n";
    }
}
