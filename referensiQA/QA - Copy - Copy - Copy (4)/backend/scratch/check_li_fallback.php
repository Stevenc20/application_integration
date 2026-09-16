<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$li = \App\Models\LembarInspeksi::orderBy('id', 'desc')->first();
echo "appearance6_results:\n";
print_r($li->appearance6_results);
echo "dimensi1_results:\n";
print_r($li->dimensi1_results);
