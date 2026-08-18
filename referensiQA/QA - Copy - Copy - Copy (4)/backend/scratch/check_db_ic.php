<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ic = \App\Models\ItemCheck::find(1);
echo "Visual:\n";
print_r($ic->hasil_visual);
echo "\nDimensi:\n";
print_r($ic->hasil_dimensi);
