<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function cleanAppearanceInModel($modelClass) {
    $records = $modelClass::all();
    $cleaned = 0;

    foreach ($records as $record) {
        $modified = false;
        $appearances = [];

        // Collect all valid appearances and extract HOLE
        $holeApp = null;
        for ($i = 6; $i <= 14; $i++) {
            $val = $record->{"appearance{$i}"} ?? '';
            $valTrim = trim($val);
            $lowerVal = strtolower($valTrim);
            
            // If it's empty, or "Date", or numeric, skip it!
            if ($valTrim === '' || $lowerVal === 'date' || str_starts_with($lowerVal, 'rev') || is_numeric($valTrim)) {
                $record->{"appearance{$i}"} = null;
                continue;
            }

            if (stripos($valTrim, 'HOLE') !== false) {
                $holeApp = $valTrim;
                $record->{"appearance{$i}"} = null;
            } else {
                $appearances[] = $valTrim;
            }
        }

        // Rebuild appearance6 to appearance14
        $newAppIndex = 6;
        
        // Put holeApp at the very first (appearance6) if it exists
        if ($holeApp) {
            if ($record->appearance6 !== $holeApp) {
                $record->appearance6 = $holeApp;
                $modified = true;
            }
            $newAppIndex++;
        }

        // Put the rest below it
        foreach ($appearances as $app) {
            if ($record->{"appearance{$newAppIndex}"} !== $app) {
                $record->{"appearance{$newAppIndex}"} = $app;
                $modified = true;
            }
            $newAppIndex++;
        }

        // Nullify the rest
        for ($i = $newAppIndex; $i <= 14; $i++) {
            if ($record->{"appearance{$i}"} !== null) {
                $record->{"appearance{$i}"} = null;
                $modified = true;
            }
        }

        if ($modified) {
            $record->save();
            $cleaned++;
        }
    }
    return $cleaned;
}

$liCleaned = cleanAppearanceInModel(App\Models\LembarInspeksi::class);
$templateCleaned = cleanAppearanceInModel(App\Models\LiTemplate::class);

echo "Cleaned LembarInspeksi: $liCleaned\n";
echo "Cleaned LiTemplate: $templateCleaned\n";
