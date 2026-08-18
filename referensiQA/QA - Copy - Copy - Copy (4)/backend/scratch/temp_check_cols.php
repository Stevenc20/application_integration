<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = Schema::getColumnListing('lembar_inspeksis');
echo "Total columns: " . count($cols) . "\n";
foreach ($cols as $c) {
    if (strpos($c, 'dimensi') !== false || strpos($c, 'appearance') !== false) {
        echo $c . "\n";
    }
}
