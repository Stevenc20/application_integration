<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$lis = \App\Models\LembarInspeksi::with(['itemChecks:id,lembar_inspeksi_id,hasil_visual,hasil_dimensi'])->get();
foreach ($lis as $li) {
    if ($li->itemChecks->count() > 0) {
        echo "LI ID: {$li->id}\n";
        echo "ItemChecks Count: " . $li->itemChecks->count() . "\n";
        foreach ($li->itemChecks as $ic) {
            echo "  IC ID: {$ic->id}\n";
            echo "  Visual keys: " . implode(", ", array_keys($ic->hasil_visual ?: [])) . "\n";
        }
    }
}
