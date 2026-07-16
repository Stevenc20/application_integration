<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/uploads/00. Master Schedule Stamping.xlsx');

foreach (['Shift Pagi', 'Shift Malam'] as $sheetName) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    if (!$sheet) continue;
    echo "=== Sheet: $sheetName ===\n";
    
    // Find Press rows
    $pressRows = [];
    $highestRow = $sheet->getHighestRow();
    for ($r = 1; $r <= $highestRow; $r++) {
        $val = trim($sheet->getCell("C" . $r)->getValue() ?? '');
        if (preg_match('/^PRESS\s+[A-Z]$/i', $val)) {
            $pressRows[] = [
                'row' => $r,
                'name' => strtoupper($val)
            ];
        }
    }
    
    echo "Presses found:\n";
    foreach ($pressRows as $pr) {
        echo "  Row {$pr['row']}: {$pr['name']}\n";
    }
    
    // Find drawings
    $drawings = $sheet->getDrawingCollection();
    echo "Drawings:\n";
    foreach ($drawings as $i => $drawing) {
        echo "  Drawing $i: name='{$drawing->getName()}', coordinates='{$drawing->getCoordinates()}'\n";
    }
    echo "\n";
}
