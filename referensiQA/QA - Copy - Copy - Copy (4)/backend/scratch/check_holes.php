<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = App\Models\LiTemplate::where('dimensi3', 'like', '%HOLE%')
    ->orWhere('dimensi4', 'like', '%HOLE%')
    ->orWhere('dimensi5', 'like', '%HOLE%')
    ->orWhere('dimensi6', 'like', '%HOLE%')
    ->orWhere('dimensi7', 'like', '%HOLE%')
    ->count();

echo "Count: $count\n";
