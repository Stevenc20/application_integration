<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('name', 'Sapriadi')->first();
echo "Sapriadi role: '{$user->role}'\n";
echo "Sapriadi dept: '{$user->department}'\n";
