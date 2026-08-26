<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\ProductionService::class);

echo "Test 1: " . $service->calculateEfficiency(250, 250) . "\n";
echo "Test 2: " . $service->calculateEfficiency(125, 250) . "\n";
echo "Test 3: " . $service->calculateEfficiency(76, 100) . "\n";
echo "Test 4: " . $service->calculateEfficiency(400, 400) . "\n";
echo "Test 5: " . $service->calculateEfficiency(0, 250) . "\n";
echo "Test 7: " . $service->calculateEfficiency(100, 0) . "\n";
echo "Test 8: " . $service->calculateEfficiency(100, null) . "\n";
