<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$breaks = \App\Models\MasterBreakTime::all();
foreach($breaks as $b) {
    echo "{$b->hari} | {$b->shift} | {$b->waktu_mulai} - {$b->waktu_selesai} | {$b->label}\n";
}
