<?php
$qpr = App\Models\Qpr::where('no_qpr', '01/QG/IPPI/06/2026')->first();
echo "QPR created_by: " . $qpr->created_by . "\n";
$sigs = json_decode($qpr->approval_signatures, true);
echo "Sigs 0 nama: " . ($sigs[0]['nama'] ?? 'none') . "\n";
