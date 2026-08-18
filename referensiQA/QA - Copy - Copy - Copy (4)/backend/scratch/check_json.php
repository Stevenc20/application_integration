<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$li = \App\Models\LembarInspeksi::with(['itemChecks:id,lembar_inspeksi_id,hasil_visual,hasil_dimensi'])->find(161);
echo json_encode($li);
