<?php
$qpr = App\Models\Qpr::with('actions')->where('no_qpr', '01/QG/IPPI/07/2026')->first();
if (!$qpr) {
    echo "QPR Not found\n";
    exit;
}

$kasieQaSigned = collect($qpr->approval_signatures)->contains(function($s) { 
    return ($s['role'] ?? '') === 'Kasie QA' && !empty($s['signature']); 
});

echo json_encode([
    'kasieQaSigned' => $kasieQaSigned,
    'correction_items' => $qpr->correction_items,
    'dampak_items' => $qpr->dampak_items,
    'actions' => $qpr->actions ? $qpr->actions->toArray() : null
], JSON_PRETTY_PRINT);
