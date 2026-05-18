<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ProductionPlan;

echo "Total records: " . ProductionPlan::count() . "\n";
echo "Unique dates: " . json_encode(ProductionPlan::distinct()->pluck('plan_date')->toArray()) . "\n";
echo "Sample data:\n";
print_r(ProductionPlan::latest()->take(3)->get()->toArray());
