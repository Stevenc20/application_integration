<?php
require 'vendor/autoload.php';
use Maatwebsite\Excel\Facades\Excel;

// We need to bootstrap Laravel to use the facades
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::capture());

$file = $argv[1] ?? null;
if (!$file || !file_exists($file)) {
    die("File not found\n");
}

echo "Testing file: $file\n";
$start = microtime(true);

try {
    $data = Excel::toArray(new \App\Imports\ProductionPlanImport(), $file);
    $end = microtime(true);
    echo "Success! Time taken: " . ($end - $start) . " seconds\n";
    echo "Total sheets read: " . count($data) . "\n";
    foreach ($data as $name => $rows) {
        echo "Sheet [$name]: " . count($rows) . " rows\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
