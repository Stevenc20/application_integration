<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$qpr = App\Models\Qpr::where('no_qpr', '01/QG/IPPI/07/2026')->first();
if (!$qpr) { echo "QPR not found\n"; exit; }

$sigs = $qpr->approval_signatures;
if (!is_array($sigs)) $sigs = json_decode($sigs, true) ?? [];

$changed = false;
foreach ($sigs as &$sig) {
    // Kosongkan signature jika QPR masih Pending Approval
    if (($sig['position'] ?? '') === 'foreman') {
        $sig['signature'] = null;
        $sig['signed_at'] = null;
        $sig['nama'] = null;
        $changed = true;
    }
}
unset($sig);

if ($changed) {
    $qpr->approval_signatures = $sigs;
    $qpr->save();
    echo "Foreman signature slot cleared for QPR 01.\n";
} else {
    echo "No changes made.\n";
}
