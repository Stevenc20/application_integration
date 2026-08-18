<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = App\Models\ItemCheck::all();
$count = 0;
foreach($items as $li) {
    $hasHoleInDimensi = false;
    $holeText = '';
    $holeMethod = '';
    $holeItem = '';
    
    for ($i=1; $i<=5; $i++) {
        $dimVal = $li->{"dimensi$i"};
        if ($dimVal && stripos($dimVal, 'HOLE') !== false) {
            $hasHoleInDimensi = true;
            $holeText = $dimVal;
            $holeItem = $li->{"dimensi{$i}_item"};
            $holeMethod = $li->{"dimensi{$i}_method"};
            
            // Shift the remaining dimensions up
            for ($j=$i; $j<5; $j++) {
                $li->{"dimensi$j"} = $li->{"dimensi".($j+1)};
                $li->{"dimensi{$j}_item"} = $li->{"dimensi".($j+1)."_item"};
                $li->{"dimensi{$j}_method"} = $li->{"dimensi".($j+1)."_method"};
            }
            // Clear the last one
            $li->dimensi5 = null;
            $li->dimensi5_item = null;
            $li->dimensi5_method = null;
            break;
        }
    }

    if ($hasHoleInDimensi) {
        $placed = false;
        // The user wants it as the FIRST appearance!
        // We need to shift appearances down
        for ($k=13; $k>=6; $k--) {
            $li->{"appearance".($k+1)} = $li->{"appearance$k"};
        }
        $li->appearance6 = rtrim($holeItem . ' ' . $holeText); // Combine item and text like "JUMLAH HOLE : 12 HOLES"
        
        $li->save();
        $count++;
    }
}
echo "Migrated $count ItemCheck templates to move HOLE to Appearance 6.\n";
