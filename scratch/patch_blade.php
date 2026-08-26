<?php
$f = 'resources/views/operational/input_harian.blade.php';
$c = file_get_contents($f);

$c = str_replace(
    'dtType: "{{ $rdt->jenis_downtime }}",',
    "dtType: \"{{ \$rdt->jenis_downtime }}\",\n                    source: \"{{ \$rdt->source }}\",",
    $c
);

$c = str_replace(
    "'type' => \$dt->jenis_downtime,",
    "'type' => \$dt->jenis_downtime,\n                'source' => \$dt->source,",
    $c
);

file_put_contents($f, $c);
echo "Patched input_harian.blade.php\n";
