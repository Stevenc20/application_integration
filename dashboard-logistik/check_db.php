<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = App\Models\ScheduleStamping::orderBy('id')->get();
foreach ($items as $item) {
    if (str_contains($item->job_no, 'AES-024') || $item->row_type == 'break') {
        echo "ID: {$item->id}, Job: {$item->job_no}, Start: {$item->start_time}, Finish: {$item->finish_time}, TPT: {$item->tpt}, Row Type: {$item->row_type}\n";
    }
}
?>
