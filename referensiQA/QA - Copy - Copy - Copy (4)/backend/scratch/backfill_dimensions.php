<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function parseDimensions($modelClass) {
    $records = $modelClass::all();
    $updated = 0;

    foreach ($records as $record) {
        $modified = false;

        for ($i = 1; $i <= 7; $i++) {
            $itemText = $record->{"dimensi{$i}_item"};
            if (!$itemText) continue;

            // Only parse if nominal is not already set
            if ($record->{"dimensi{$i}_nominal"} !== null) continue;

            $nominal = null;
            $plus = null;
            $minus = null;

            // Regex 1: Format ± (misal: Ø 10 mm ±0,1 atau 10±0.1)
            if (preg_match('/(?:Ø|R)?\s*(\d+[\.,]?\d*)\s*(?:mm)?\s*(?:±|v)\s*(\d+[\.,]?\d*)/iu', $itemText, $m)) {
                $nominal = str_replace(',', '.', $m[1]);
                $tol = str_replace(',', '.', $m[2]);
                $plus = $tol;
                $minus = $tol;
            } 
            // Regex 2: Format + / - (misal: Ø 10 mm +0.1/-0.2)
            elseif (preg_match('/(?:Ø|R)?\s*(\d+[\.,]?\d*)\s*(?:mm)?\s*\+\s*(\d+[\.,]?\d*)\s*\/\s*-\s*(\d+[\.,]?\d*)/iu', $itemText, $m)) {
                $nominal = str_replace(',', '.', $m[1]);
                $plus = str_replace(',', '.', $m[2]);
                $minus = str_replace(',', '.', $m[3]);
            }
            // Regex 3: Hanya nominal (misal: Ø 10 mm)
            elseif (preg_match('/(?:Ø|R)?\s*(\d+[\.,]?\d*)\s*(?:mm)/iu', $itemText, $m)) {
                $nominal = str_replace(',', '.', $m[1]);
                $plus = 0;
                $minus = 0;
            }

            if ($nominal !== null) {
                $record->{"dimensi{$i}_nominal"} = $nominal;
                $record->{"dimensi{$i}_plus"} = $plus;
                $record->{"dimensi{$i}_minus"} = $minus;
                $modified = true;
            }
        }

        if ($modified) {
            $record->save();
            $updated++;
        }
    }
    return $updated;
}

$liUpdated = parseDimensions(App\Models\LembarInspeksi::class);
$templateUpdated = parseDimensions(App\Models\LiTemplate::class);

echo "Parsed Dimensi for LembarInspeksi: $liUpdated\n";
echo "Parsed Dimensi for LiTemplate: $templateUpdated\n";
