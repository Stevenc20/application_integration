<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$qprs = \App\Models\Qpr::whereNotNull('inspeksi_id')->get();
$count = 0;
foreach ($qprs as $qpr) {
    $itemCheck = \App\Models\ItemCheck::find($qpr->inspeksi_id);
    if ($itemCheck) {
        $qpr->proses_repair = $itemCheck->getProsesRepairString();
        $qpr->area_problems = $itemCheck->getAreaProblemsArray();
        $qpr->save();
        $count++;
    }
}
echo "Synced {$count} QPRs successfully.\n";
