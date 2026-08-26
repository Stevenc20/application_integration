<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix Auto Breaks
$autoAffected = DB::table('downtimes')
    ->where('jenis_downtime', 'break time')
    ->where('pic', 'AUTO BREAK')
    ->whereNull('source')
    ->update(['source' => 'AUTO']);

// Fix Manual Breaks
$manualAffected = DB::table('downtimes')
    ->where('jenis_downtime', 'break time')
    ->where('pic', '!=', 'AUTO BREAK')
    ->whereNull('source')
    ->update(['source' => 'MANUAL']);

echo "Fixed DB records. Auto: $autoAffected, Manual: $manualAffected\n";
