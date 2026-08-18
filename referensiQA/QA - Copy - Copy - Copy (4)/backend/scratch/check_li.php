<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$qpr = App\Models\Qpr::find(5);
$li = $qpr->inspeksi;
echo "LI ID: " . $li->id . "\n";
echo "LI qg_name: " . $li->qg_name . "\n";
echo "LI qc_name: " . $li->qc_name . "\n";
echo "LI assigned_operator_id: " . $li->assigned_operator_id . "\n";
echo "LI creator: " . $li->creator?->name . "\n";
echo "QPR approval_signatures: " . $qpr->approval_signatures . "\n";
