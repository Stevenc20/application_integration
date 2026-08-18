<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dt = App\Models\Downtime::whereNull('finish_time')->latest('id')->first();
echo json_encode($dt, JSON_PRETTY_PRINT) . "\n";
if ($dt) {
    echo "DURATION: " . abs(Carbon\Carbon::now()->diffInSeconds(Carbon\Carbon::parse($dt->start_time))) . "\n";
}
