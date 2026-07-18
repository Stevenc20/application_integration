<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportReadFilter implements IReadFilter
{
    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if ($row > 1000) return false;

        $colIndex = Coordinate::columnIndexFromString($columnAddress);
        if ($colIndex > 40) return false;

        return true;
    }
}
