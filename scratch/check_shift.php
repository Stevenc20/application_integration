<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = \App\Models\ProductionPlan::latest()->take(10)->get(['id', 'shift_name', 'press_name', 'plan_date']);

foreach($rows as $r) {
    echo "ID: {$r->id} | Shift: [{$r->shift_name}] | Press: [{$r->press_name}] | Date: {$r->plan_date}\n";
}
