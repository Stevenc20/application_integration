<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$li = \App\Models\LembarInspeksi::with('itemChecks')->first();
echo implode(", ", array_keys($li->toArray()));
