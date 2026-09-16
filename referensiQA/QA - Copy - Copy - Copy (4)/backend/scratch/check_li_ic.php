<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$lis = \App\Models\LembarInspeksi::with(['itemChecks'])->get();
$count = 0;
foreach ($lis as $li) {
    if ($li->itemChecks->count() > 0) {
        $count++;
    }
}
echo "Total LIs with ItemChecks: $count\n";
