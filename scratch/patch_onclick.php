<?php
$f = 'resources/views/operational/input_harian.blade.php';
$c = file_get_contents($f);

$s = 'onclick="openDowntimeReport({{ $activeJob->id }}, @json($unfilledDowntimes->first()))"';
$r = "onclick=\"openDowntimeReport({{ \$activeJob->id }}, {{ e(json_encode(\$unfilledDowntimes->first())) }})\"";

if (strpos($c, $s) !== false) {
    $c = str_replace($s, $r, $c);
    file_put_contents($f, $c);
    echo "Fixed onclick JSON.\n";
} else {
    echo "String not found!\n";
}
