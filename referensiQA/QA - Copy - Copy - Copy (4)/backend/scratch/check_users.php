<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$totok = App\Models\User::where('name', 'like', '%totok%')->first();
$donny = App\Models\User::where('name', 'like', '%donny%')->first();
echo "Totok ID: " . ($totok->id ?? 'null') . "\n";
echo "Donny ID: " . ($donny->id ?? 'null') . "\n";

$qpr = App\Models\Qpr::find(5); // The one we just fixed
echo "QPR 5 created_by currently: " . $qpr->created_by . "\n";
