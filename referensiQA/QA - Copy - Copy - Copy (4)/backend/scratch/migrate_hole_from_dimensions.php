<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$models = [
    App\Models\ItemCheck::class,
    App\Models\LembarInspeksi::class,
    App\Models\LiTemplate::class
];

foreach ($models as $modelClass) {
    if (!class_exists($modelClass)) {
        continue;
    }
    
    $records = $modelClass::all();
    $count = 0;
    
    foreach ($records as $record) {
        $hasHoleInDimensi = false;
        $holeText = '';
        $holeItem = '';
        $holeMethod = '';
        
        for ($i=1; $i<=7; $i++) {
            $dimVal = $record->{"dimensi$i"};
            $dimItem = $record->{"dimensi{$i}_item"};
            
            if (
                ($dimVal && stripos($dimVal, 'HOLE') !== false) || 
                ($dimItem && stripos($dimItem, 'HOLE') !== false)
            ) {
                $hasHoleInDimensi = true;
                $holeText = $dimVal;
                $holeItem = $dimItem;
                $holeMethod = $record->{"dimensi{$i}_method"};
                
                // Shift the remaining dimensions up
                for ($j=$i; $j<7; $j++) {
                    $record->{"dimensi$j"} = $record->{"dimensi".($j+1)};
                    $record->{"dimensi{$j}_item"} = $record->{"dimensi".($j+1)."_item"};
                    $record->{"dimensi{$j}_method"} = $record->{"dimensi".($j+1)."_method"};
                }
                
                // Clear the last one (7th)
                $record->dimensi7 = null;
                $record->dimensi7_item = null;
                $record->dimensi7_method = null;
                break;
            }
        }

        if ($hasHoleInDimensi) {
            // Check if appearance6 already has 'HOLE'
            $app6 = $record->appearance6;
            if ($app6 && stripos($app6, 'HOLE') !== false) {
                // If it already has hole, just update it if needed, or don't shift.
                // It means a previous migration already put it there, but maybe left it in dimensi too?
                // Just clear the dimensi and we're good.
            } else {
                // Shift appearances down. There are appearances from 6 to 14
                for ($k=13; $k>=6; $k--) {
                    $record->{"appearance".($k+1)} = $record->{"appearance$k"};
                }
            }
            $record->appearance6 = trim($holeItem . ' ' . $holeText);
            
            $record->save();
            $count++;
        }
    }
    echo "Migrated $count records in $modelClass\n";
}
