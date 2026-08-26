<?php
$f = 'app/Http/Controllers/Operational/InputHarianController.php';
$c = file_get_contents($f);
$c = str_replace("'break time', 'manual break'", "'break time'", $c);
$c = str_replace("'dandori', 'idle time', 'idle', 'break time', 'manual break'", "'dandori', 'idle time', 'idle', 'break time'", $c);
file_put_contents($f, $c);
echo "Reverted InputHarianController\n";
