<?php
$f = 'resources/views/operational/components/active-job-board.blade.php';
$c = file_get_contents($f);

$s = <<< 'PHP'
            $isOnBreak = $activeDowntime && strtolower($activeDowntime->jenis_downtime) === 'break time' && strtoupper(trim($activeDowntime->pic ?? '')) === 'AUTO BREAK';
PHP;
$r = <<< 'PHP'
            $isOnBreak = $activeDowntime && strtolower($activeDowntime->jenis_downtime) === 'break time' && strtoupper(trim($activeDowntime->source ?? '')) === 'AUTO';
PHP;
$c = str_replace($s, $r, $c);

file_put_contents($f, $c);
echo "Patched active-job-board.blade.php\n";
