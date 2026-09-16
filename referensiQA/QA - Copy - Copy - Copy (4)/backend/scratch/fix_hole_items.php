<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function fixHoleInModel($modelClass) {
    $records = $modelClass::all();
    $migrated = 0;

    foreach ($records as $record) {
        $hasHole = false;
        $holeData = null;
        $holeIdx = -1;

        // Find which dimension has HOLE
        for ($i = 1; $i <= 7; $i++) {
            $item = $record->{"dimensi{$i}_item"} ?? '';
            $label = $record->{"dimensi{$i}"} ?? '';
            
            if (stripos($item, 'HOLE') !== false || stripos($label, 'HOLE') !== false) {
                $hasHole = true;
                $holeData = [
                    'item' => $item,
                    'label' => $label,
                    'method' => $record->{"dimensi{$i}_method"} ?? ''
                ];
                $holeIdx = $i;
                break;
            }
        }

        if ($hasHole) {
            // Move to appearance6 if appearance6 doesn't already have it
            $app6 = $record->appearance6 ?? '';
            if (empty($app6) || stripos($app6, 'HOLE') === false) {
                $record->appearance6 = trim($app6 . ', ' . ($holeData['item'] ?: $holeData['label']), ', ');
            }

            // Shift dimensions up to remove the HOLE dimension
            for ($j = $holeIdx; $j < 7; $j++) {
                $record->{"dimensi{$j}"} = $record->{"dimensi".($j+1)};
                $record->{"dimensi{$j}_item"} = $record->{"dimensi".($j+1)."_item"};
                $record->{"dimensi{$j}_method"} = $record->{"dimensi".($j+1)."_method"};
                
                // Only shift if columns exist (for LembarInspeksi)
                if (array_key_exists("dimensi{$j}_nominal", $record->getAttributes())) {
                    $record->{"dimensi{$j}_nominal"} = $record->{"dimensi".($j+1)."_nominal"} ?? null;
                    $record->{"dimensi{$j}_plus"} = $record->{"dimensi".($j+1)."_plus"} ?? null;
                    $record->{"dimensi{$j}_minus"} = $record->{"dimensi".($j+1)."_minus"} ?? null;
                }
            }

            // Clear dimension 7
            $record->dimensi7 = null;
            $record->dimensi7_item = null;
            $record->dimensi7_method = null;
            if (array_key_exists("dimensi7_nominal", $record->getAttributes())) {
                $record->dimensi7_nominal = null;
                $record->dimensi7_plus = null;
                $record->dimensi7_minus = null;
            }

            $record->save();
            $migrated++;
        }
    }
    return $migrated;
}

$liMigrated = fixHoleInModel(App\Models\LembarInspeksi::class);
$templateMigrated = fixHoleInModel(App\Models\LiTemplate::class);

echo "Migrated LembarInspeksi: $liMigrated\n";
echo "Migrated LiTemplate: $templateMigrated\n";
