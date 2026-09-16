<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checks = \App\Models\ItemCheck::whereHas('schedule', function($q){
    $q->where('job_no', 'like', '%61627-BZ110%');
})->with('schedule')->get();

echo json_encode($checks->toArray());
