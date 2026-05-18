<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRODUCTION PLAN BY DATE ===\n";
$plans = App\Models\ProductionPlan::select('plan_date', \DB::raw('count(*) as count'))
    ->groupBy('plan_date')
    ->get();

if ($plans->isEmpty()) {
    echo "No production plans in database.\n";
} else {
    foreach ($plans as $p) {
        echo "Date: {$p->plan_date->toDateString()} | Count: {$p->count}\n";
    }
}
