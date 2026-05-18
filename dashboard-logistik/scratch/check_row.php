<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleStamping;

$id = 1214; // GT-5154 from verify_times
$item = ScheduleStamping::find($id);
if ($item) {
    echo json_encode($item->toArray(), JSON_PRETTY_PRINT) . "\n";
}
