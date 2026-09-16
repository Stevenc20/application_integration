<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/uploads/07. Schedule Stamping 08 Mei 2026.xlsx');

foreach (['Shift Pagi', 'Shift Malam'] as $sheetName) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    if (!$sheet) continue;
    echo "=== Sheet: $sheetName ===\n";
    
    $drawings = $sheet->getDrawingCollection();
    echo "Total drawings: " . count($drawings) . "\n";
    foreach ($drawings as $i => $drawing) {
        echo "Drawing $i:\n";
        echo "  Name: " . $drawing->getName() . "\n";
        echo "  Description: " . $drawing->getDescription() . "\n";
        echo "  Coordinates: " . $drawing->getCoordinates() . "\n";
        echo "  Width: " . $drawing->getWidth() . "\n";
        echo "  Height: " . $drawing->getHeight() . "\n";
    }
}
