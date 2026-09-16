<?php

namespace App\Http\Controllers;

use App\Models\ScheduleStamping;
use App\Models\MasterStamping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ScheduleStampingController extends Controller
{
    private $sectHeadOverride = null;

    // ── INDEX ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $allDates  = ScheduleStamping::select('upload_date')->distinct()->orderBy('upload_date', 'desc')->pluck('upload_date');

        // Selected filters - always default to today's date if no date parameter is requested
        $selectedDate = $request->get('date');
        if (!$selectedDate) {
            // Default to today formatted as "DD BULAN YYYY"
            $months = ['','JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
            $selectedDate = date('d') . ' ' . $months[(int)date('m')] . ' ' . date('Y');
        }

        // Available shifts for this date
        $allShifts = collect(); 
        if ($selectedDate) {
            $allShifts = ScheduleStamping::where('upload_date', $selectedDate)
                ->whereNotNull('shift_name')
                ->where('shift_name', 'not like', '%REV-001%')
                ->select('shift_name')
                ->distinct()
                ->orderBy('shift_name')
                ->pluck('shift_name');
        }
        // Always offer Shift Pagi / Shift Malam regardless of existing data
        $defaultShifts = collect(['Shift Pagi', 'Shift Malam']);
        $allShifts = $allShifts->merge($defaultShifts)->unique()->sort()->values();

        $selectedShift = $request->get('shift');
        if (!$selectedShift) {
            $selectedShift = 'Shift Pagi';
        }

        // Available press for this date + shift
        $allPress = collect();
        if ($selectedDate && $selectedShift) {
            $allPress = ScheduleStamping::where('upload_date', $selectedDate)
                ->where('shift_name', $selectedShift)
                ->whereNotNull('press_name')
                ->select('press_name')
                ->distinct()
                ->orderBy('press_name')
                ->pluck('press_name');
        }
        // Always offer PRESS A-D
        $defaultPress = collect(['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D']);
        $allPress = $allPress->merge($defaultPress)->unique()->sort()->values();

        $selectedPress = $request->get('press');
        if (!$selectedPress) {
            $selectedPress = 'PRESS A';
        }

        $search = $request->get('search', '');

        $items = collect();
        $metaInfo = null;
        $summaryRows = collect();

        if ($selectedDate) {
            $query = ScheduleStamping::where('upload_date', $selectedDate);

            if ($selectedShift) $query->where('shift_name', $selectedShift);
            if ($selectedPress) $query->where('press_name', $selectedPress);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('job_master', 'like', "%{$search}%")
                      ->orWhere('job_no', 'like', "%{$search}%")
                      ->orWhere('keterangan', 'like', "%{$search}%");
                });
            }

            // Ambil job rows dalam urutan drag (by sort_order), break rows dipisah
            $allRows   = $query->orderBy('sort_order', 'asc')->get();
            $jobRows   = $allRows->where('row_type', 'job')->values();
            $breakRows = $allRows->where('row_type', 'break')->values();
            $summaryRows = $allRows->where('row_type', 'summary')->values();

            // Sisipkan break rows di antara job rows berdasarkan posisi waktunya
            $items = $this->mergeJobsAndBreaks($jobRows, $breakRows, $selectedShift ?? '');

            // Get meta info for selected
            $metaInfo = ScheduleStamping::where('upload_date', $selectedDate)
                ->where('shift_name', $selectedShift)
                ->where('press_name', $selectedPress)
                ->whereNotNull('press_name')
                ->first();
        }

        $hasData = $items->where('row_type', 'job')->isNotEmpty();

        // Stats
        $jobRows = $items->where('row_type', 'job');
        $totalPlan = $jobRows->sum('plan');
        
        // Helper to extract value from summary rows
        $getSummaryVal = function($keyword) use ($summaryRows) {
            $row = $summaryRows->first(function($item) use ($keyword) {
                $normalizedStr = preg_replace('/\s+/', ' ', strtoupper(trim($item->job_master ?? '')));
                return str_contains($normalizedStr, strtoupper($keyword));
            });
            if ($row) {
                // The values are stored in type_plt (col 4 in Excel) because of the layout
                $val = trim($row->type_plt ?? '');
                // Try to parse float if it contains comma
                $valStr = str_replace(',', '.', $val);
                if (is_numeric($valStr)) {
                    // Format float: if it has decimals, keep 1 or 2, else no decimals
                    $f = floatval($valStr);
                    if (floor($f) == $f) {
                        return number_format($f, 0, ',', '.');
                    } else {
                        return rtrim(rtrim(number_format($f, 2, ',', '.'), '0'), ',');
                    }
                }
                return $val ?: '-';
            }
            return '-';
        };

        $statStroke      = $getSummaryVal('TOTAL STROKE');
        $statTpt         = $getSummaryVal('TOTAL TPT');
        $statTargetGsph  = $getSummaryVal('TARGET GSPH');
        // Exact match for GSPH to avoid matching TARGET GSPH
        $rowGsph = $summaryRows->first(function($item) {
            $normalizedStr = preg_replace('/\s+/', ' ', strtoupper(trim($item->job_master ?? '')));
            return $normalizedStr === 'GSPH';
        });
        $statGsph = '-';
        if ($rowGsph) {
            $val = trim($rowGsph->type_plt ?? '');
            $valStr = str_replace(',', '.', $val);
            if (is_numeric($valStr)) {
                $f = floatval($valStr);
                $statGsph = (floor($f) == $f) ? number_format($f, 0, ',', '.') : rtrim(rtrim(number_format($f, 2, ',', '.'), '0'), ',');
            } else {
                $statGsph = $val ?: '-';
            }
        }
        $statTotalFinish = $getSummaryVal('TOTAL FINISH');

        $currentSectHead = null;
        if ($selectedDate && $selectedShift && $selectedPress) {
            $firstRowWithSectHead = ScheduleStamping::where('upload_date', $selectedDate)
                ->where('shift_name', $selectedShift)
                ->where('press_name', $selectedPress)
                ->whereNotNull('sect_head_ppc')
                ->first();
            if ($firstRowWithSectHead) {
                $currentSectHead = $firstRowWithSectHead->sect_head_ppc;
            }
        }
        if (!$currentSectHead && $selectedShift) {
            $currentSectHead = str_contains(strtoupper($selectedShift), 'MALAM') ? 'Alvyn' : 'Alberta P. S.';
        }

        return view('schedule_stamping', compact(
            'hasData', 'allDates', 'allShifts', 'allPress',
            'selectedDate', 'selectedShift', 'selectedPress',
            'search', 'items', 'metaInfo',
            'totalPlan', 'statStroke', 'statTpt', 'statTargetGsph', 'statGsph', 'statTotalFinish',
            'currentSectHead'
        ));
    }

    public function export(Request $request)
    {
        @set_time_limit(300);

        $date  = $request->get('date');
        // Set shift and press to null to ensure all shifts and presses are always included in the exported Excel
        $shift = null;
        $press = null;
        $search = $request->get('search');
        $this->sectHeadOverride = $request->get('sect_head_ppc');

        if (!$date) {
            $date = ScheduleStamping::orderBy('upload_date', 'desc')->value('upload_date');
        }

        if (!$date) {
            return back()->with('error', 'Tidak ada data untuk diexport.');
        }

        $templatePath = storage_path('app/uploads/00. Master Schedule Stamping.xlsx');
        if (!file_exists($templatePath)) {
            return $this->exportFallback($date, $shift, $press, $search);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);

        // Update Format TTD values with the chosen Sect. Head PPC name
        $formatTtd = $spreadsheet->getSheetByName('Format TTD');
        if ($formatTtd) {
            $sectHeadVal = $this->sectHeadOverride;
            if (!$sectHeadVal) {
                $sectHeadVal = 'Alberta P. S.';
            }
            $formatTtd->setCellValue('P14', $sectHeadVal);
            $formatTtd->setCellValue('P24', $sectHeadVal);
        }

        // Correct Shift Pagi column widths to match reference format
        // (Master template has slightly different widths for Col B and Col E)
        $pagiSheet = $spreadsheet->getSheetByName('Shift Pagi');
        if ($pagiSheet) {
            $pagiSheet->getColumnDimension('B')->setWidth(2.42578125);
            $pagiSheet->getColumnDimension('E')->setWidth(15.7109375);
        }

        // Fetch all jobs and breaks for this date
        $query = ScheduleStamping::where('upload_date', $date);
        if ($shift) {
            $query->where('shift_name', $shift);
        }
        if ($press) {
            $query->where('press_name', $press);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('job_master', 'like', "%{$search}%")
                  ->orWhere('job_no', 'like', "%{$search}%");
            });
        }
        $dbItems = $query->orderBy('sort_order', 'asc')->get();

        if ($dbItems->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diexport.');
        }

        $anyRow = ScheduleStamping::where('upload_date', $date)->whereNotNull('hari')->first();
        $hariText = $anyRow ? $anyRow->hari : '';

        $pagiInserted = ['PRESS A' => 0, 'PRESS B' => 0, 'PRESS C' => 0, 'PRESS D' => 0];
        $malamInserted = ['PRESS A' => 0, 'PRESS B' => 0, 'PRESS C' => 0, 'PRESS D' => 0];

        // Process Shift Pagi & Shift Malam sheets
        foreach (['Shift Pagi', 'Shift Malam'] as $shName) {
            $sheet = $spreadsheet->getSheetByName($shName);
            if (!$sheet) {
                continue;
            }

            // Clear unwanted drawings/pictures from this sheet
            $drawings = $sheet->getDrawingCollection();
            $keysToRemove = [];
            foreach ($drawings as $key => $drawing) {
                if ($drawing->getName() !== 'Picture 2') {
                    $keysToRemove[] = $key;
                }
            }
            rsort($keysToRemove);
            foreach ($keysToRemove as $key) {
                $drawings->offsetUnset($key);
            }

            // Find Press rows dynamically
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

            $insertedRows = [
                'PRESS A' => 0,
                'PRESS B' => 0,
                'PRESS C' => 0,
                'PRESS D' => 0
            ];

            for ($i = 0; $i < count($pressRows); $i++) {
                $pressRow = $pressRows[$i]['row'];
                $pressName = $pressRows[$i]['name'];
                $nextPressRow = ($i + 1 < count($pressRows)) ? $pressRows[$i+1]['row'] : null;

                // Check if this section matches the filter
                $shouldPopulate = true;
                if ($shift && $shift !== $shName) {
                    $shouldPopulate = false;
                }
                if ($press && $press !== $pressName) {
                    $shouldPopulate = false;
                }

                if ($shouldPopulate) {
                    $shiftItems = $dbItems->where('shift_name', $shName)->values();
                    $jobRows = $shiftItems->where('row_type', 'job')->where('press_name', $pressName)->values();
                    $breakRows = $shiftItems->where('row_type', 'break')->where('press_name', $pressName)->values();
                    $pressItems = $this->mergeJobsAndBreaks($jobRows, $breakRows, $shName);
                } else {
                    $pressItems = collect();
                }

                $diff = $this->populatePressSection($sheet, $pressName, $pressRow, $nextPressRow, $pressItems, $date, $hariText, $pressRows, $i);
                $insertedRows[$pressName] = $diff;
            }

            if ($shName === 'Shift Pagi') {
                $pagiInserted = $insertedRows;
            } else {
                $malamInserted = $insertedRows;
            }
        }

        $filename = "Schedule_Stamping_{$date}.xlsx";
        if ($shift && $press) {
            $filename = "Schedule_Stamping_{$date}_{$shift}_{$press}.xlsx";
        } elseif ($shift) {
            $filename = "Schedule_Stamping_{$date}_{$shift}.xlsx";
        } elseif ($press) {
            $filename = "Schedule_Stamping_{$date}_{$press}.xlsx";
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);

        // Save to temp file, shift VML shapes, strip legacy drawing duplicates, then stream
        $tempFile = tempnam(sys_get_temp_dir(), 'sched_export_') . '.xlsx';
        $writer->save($tempFile);
        $this->adjustVmlAndDrawingStructures($tempFile, $pagiInserted, $malamInserted);

        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }

    /**
     * Adjust the row coordinates of dynamic VML Camera Tool shapes based on inserted rows,
     * and strip the legacy EMF signature drawings.
     */
    private function adjustVmlAndDrawingStructures(string $filePath, array $pagiInserted, array $malamInserted): void
    {
        if (!class_exists('ZipArchive')) return;

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return;

        // 1. Read workbook mapping to find VML paths
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        preg_match_all('/<sheet\b[^>]*name="([^"]*)"[^>]*r:id="([^"]*)"/i', $workbookXml, $matches, PREG_SET_ORDER);
        
        $sheetToRid = [];
        foreach ($matches as $match) {
            $sheetToRid[$match[1]] = $match[2];
        }
        
        $workbookRels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        preg_match_all('/<Relationship\b[^>]*Id="([^"]*)"[^>]*Target="([^"]*)"/i', $workbookRels, $matches, PREG_SET_ORDER);
        
        $ridToPath = [];
        foreach ($matches as $match) {
            $ridToPath[$match[1]] = $match[2];
        }

        // We process both Shift Pagi and Shift Malam VML drawings
        $sheetSettings = [
            'Shift Pagi' => [
                'inserted' => $pagiInserted,
                'presses' => [
                    'PRESS A' => 15,
                    'PRESS B' => 99,
                    'PRESS C' => 177,
                    'PRESS D' => 238
                ]
            ],
            'Shift Malam' => [
                'inserted' => $malamInserted,
                'presses' => [
                    'PRESS A' => 7,
                    'PRESS B' => 94,
                    'PRESS C' => 177,
                    'PRESS D' => 246
                ]
            ]
        ];

        foreach ($sheetSettings as $sheetName => $settings) {
            if (!isset($sheetToRid[$sheetName])) continue;
            
            $rid = $sheetToRid[$sheetName];
            $worksheetPath = 'xl/' . $ridToPath[$rid];
            $worksheetDir = dirname($worksheetPath);
            $worksheetBase = basename($worksheetPath);
            $relsPath = $worksheetDir . '/_rels/' . $worksheetBase . '.rels';
            
            $vmlPath = null;
            if ($zip->locateName($relsPath) !== false) {
                $relsXml = $zip->getFromName($relsPath);
                if (preg_match('/<Relationship\b[^>]*Type="[^"]*vmlDrawing"[^>]*Target="([^"]*)"/i', $relsXml, $m)) {
                    $target = $m[1];
                    if (strpos($target, '../') === 0) {
                        $vmlPath = 'xl/' . substr($target, 3);
                    } else {
                        $vmlPath = $worksheetDir . '/' . $target;
                    }
                }
            }

            if ($vmlPath && $zip->locateName($vmlPath) !== false) {
                $vmlContent = $zip->getFromName($vmlPath);
                
                // Parse shapes and adjust anchors
                $vmlContent = preg_replace_callback('/(<v:shape\b[^>]*>)([\s\S]*?)(<\/v:shape>)/i', function($shapeMatches) use ($settings) {
                    $startTag = $shapeMatches[1];
                    $innerContent = $shapeMatches[2];
                    $endTag = $shapeMatches[3];
                    
                    if (preg_match('/<x:Anchor>\s*([^<]*)\s*<\/x:Anchor>/i', $innerContent, $anchorMatch)) {
                        $anchorStr = trim($anchorMatch[1]);
                        $parts = preg_split('/\s*,\s*/', $anchorStr);
                        if (count($parts) === 8) {
                            $col_start = intval($parts[0]);
                            $col_start_off = intval($parts[1]);
                            $row_start = intval($parts[2]); // 0-indexed row
                            $row_start_off = intval($parts[3]);
                            $col_end = intval($parts[4]);
                            $col_end_off = intval($parts[5]);
                            $row_end = intval($parts[6]); // 0-indexed row
                            $row_end_off = intval($parts[7]);
                            
                            $actualRowStart = $row_start + 1;
                            
                            $belongingPress = null;
                            $presses = $settings['presses'];
                            
                            if ($actualRowStart >= $presses['PRESS A'] && $actualRowStart < $presses['PRESS B']) {
                                $belongingPress = 'PRESS A';
                            } elseif ($actualRowStart >= $presses['PRESS B'] && $actualRowStart < $presses['PRESS C']) {
                                $belongingPress = 'PRESS B';
                            } elseif ($actualRowStart >= $presses['PRESS C'] && $actualRowStart < $presses['PRESS D']) {
                                $belongingPress = 'PRESS C';
                            } elseif ($actualRowStart >= $presses['PRESS D']) {
                                $belongingPress = 'PRESS D';
                            }
                            
                            if ($belongingPress) {
                                $shift = 0;
                                $inserted = $settings['inserted'];
                                if ($belongingPress === 'PRESS A') {
                                    $shift = ($inserted['PRESS A'] ?? 0);
                                } elseif ($belongingPress === 'PRESS B') {
                                    $shift = ($inserted['PRESS A'] ?? 0) + ($inserted['PRESS B'] ?? 0);
                                } elseif ($belongingPress === 'PRESS C') {
                                    $shift = ($inserted['PRESS A'] ?? 0) + ($inserted['PRESS B'] ?? 0) + ($inserted['PRESS C'] ?? 0);
                                } elseif ($belongingPress === 'PRESS D') {
                                    $shift = ($inserted['PRESS A'] ?? 0) + ($inserted['PRESS B'] ?? 0) + ($inserted['PRESS C'] ?? 0) + ($inserted['PRESS D'] ?? 0);
                                }
                                
                                if ($shift > 0) {
                                    $row_start += $shift;
                                    $row_end += $shift;
                                    
                                    $newAnchorStr = "\n    {$col_start}, {$col_start_off}, {$row_start}, {$row_start_off}, {$col_end}, {$col_end_off}, {$row_end}, {$row_end_off}";
                                    $innerContent = preg_replace('/<x:Anchor>[^<]*<\/x:Anchor>/i', "<x:Anchor>{$newAnchorStr}</x:Anchor>", $innerContent);
                                }
                            }
                        }
                    }
                    return $startTag . $innerContent . $endTag;
                }, $vmlContent);
                
                $zip->addFromString($vmlPath, $vmlContent);
            }
        }

        // 4. Strip legacy EMF drawings from drawing*.xml files
        $allFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $allFiles[] = $zip->getNameIndex($i);
        }
        foreach ($allFiles as $filePath2) {
            if (!preg_match('/xl\/drawings\/drawing\d+\.xml$/i', $filePath2)) continue;

            $content = $zip->getFromName($filePath2);
            if ($content === false) continue;

            $content = preg_replace(
                '/(<xdr:twoCellAnchor[^>]*>(?:(?!<\/xdr:twoCellAnchor>)[\s\S])*?)r:embed="rId4"((?:(?!<\/xdr:twoCellAnchor>)[\s\S])*<\/xdr:twoCellAnchor>)/u',
                '',
                $content
            );

            $zip->addFromString($filePath2, $content);
        }

        $zip->close();
    }



    private function populatePressSection($sheet, $pressName, $pressRow, $nextPressRow, $items, $date, $hariText, &$pressRows, $index): int
    {
        // 1. Find the header row (where Col C is "NO.")
        $headerRow = null;
        for ($r = $pressRow + 1; $r < $pressRow + 20; $r++) {
            $val = trim($sheet->getCell("C" . $r)->getValue() ?? '');
            if (strtoupper($val) === 'NO.' || strtoupper($val) === 'NO') {
                $headerRow = $r;
                break;
            }
        }

        if (!$headerRow) {
            return 0;
        }

        $dataStartRow = $headerRow + 2;

        // 2. Find the finish row (contains "Total Fnish", "Total Finish", "TOTAL FINISH" in Col I or C)
        $finishRow = null;
        $scanEnd = $nextPressRow ? $nextPressRow : ($sheet->getHighestRow() > $dataStartRow ? $sheet->getHighestRow() : $dataStartRow + 100);
        for ($r = $dataStartRow; $r < $scanEnd; $r++) {
            $valI = trim($sheet->getCell("I" . $r)->getValue() ?? '');
            $valC = trim($sheet->getCell("C" . $r)->getValue() ?? '');
            if (str_contains(strtoupper($valI), 'TOTAL FNISH') || str_contains(strtoupper($valI), 'TOTAL FINISH') || 
                str_contains(strtoupper($valC), 'TOTAL FNISH') || str_contains(strtoupper($valC), 'TOTAL FINISH')) {
                $finishRow = $r;
                break;
            }
        }

        if (!$finishRow) {
            for ($r = $dataStartRow; $r < $scanEnd; $r++) {
                for ($col = 3; $col <= 16; $col++) {
                    $val = trim($sheet->getCellByColumnAndRow($col, $r)->getValue() ?? '');
                    if (str_contains(strtoupper($val), 'TOTAL FNISH') || str_contains(strtoupper($val), 'TOTAL FINISH')) {
                        $finishRow = $r;
                        break 2;
                    }
                }
            }
        }

        if (!$finishRow) {
            $finishRow = $dataStartRow + 40;
        }

        $capacity = $finishRow - $dataStartRow;
        $count = count($items);

        // Update Hari & Tanggal in the section header
        for ($r = $pressRow + 1; $r < $headerRow; $r++) {
            $label = strtoupper(trim($sheet->getCell("C" . $r)->getValue() ?? ''));
            if (str_contains($label, 'TGL') || $label === 'TGL' || $label === 'TGL :') {
                $sheet->setCellValue("D" . $r, ':   ' . $date);
            }
            if (str_contains($label, 'HARI') || $label === 'HARI' || $label === 'HARI :') {
                $sheet->setCellValue("D" . $r, ':   ' . $hariText);
            }
        }

        $diff = 0;
        // 3. Insert rows if needed
        if ($count > $capacity) {
            $diff = $count - $capacity;
            $sheet->insertNewRowBefore($finishRow, $diff);
            $finishRow += $diff;
            // Shift subsequent press rows
            for ($j = $index + 1; $j < count($pressRows); $j++) {
                $pressRows[$j]['row'] += $diff;
            }
        }

        // 4. Clear cells from $dataStartRow to $finishRow - 1
        if ($finishRow > $dataStartRow) {
            $range = "C{$dataStartRow}:AH" . ($finishRow - 1);
            $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
            $sheet->getStyle($range)->getFont()->setBold(false)->setItalic(false);

            for ($r = $dataStartRow; $r < $finishRow; $r++) {
                for ($col = 3; $col <= 34; $col++) {
                    $sheet->setCellValueByColumnAndRow($col, $r, null);
                }
            }
        }

        // 5. Write items
        $currentRow = $dataStartRow;
        foreach ($items as $item) {
            if ($item->row_type === 'job') {
                $sheet->setCellValue("C" . $currentRow, $item->row_no);
            } else {
                $sheet->setCellValue("C" . $currentRow, '');
            }
            $sheet->setCellValue("D" . $currentRow, $item->job_master);
            $sheet->setCellValue("E" . $currentRow, $item->type_plt);
            $sheet->setCellValue("F" . $currentRow, $item->qty_plt);
            $sheet->setCellValue("G" . $currentRow, $item->keb_mtl);
            $sheet->setCellValue("H" . $currentRow, $item->total_plt);
            $sheet->setCellValue("I" . $currentRow, $item->job_no);
            $sheet->setCellValue("K" . $currentRow, $item->each_part);
            $sheet->setCellValue("L" . $currentRow, $item->plan);
            $sheet->setCellValue("M" . $currentRow, $item->ok);
            $sheet->setCellValue("N" . $currentRow, $item->repair);
            $sheet->setCellValue("O" . $currentRow, $item->reject);
            $sheet->setCellValue("P" . $currentRow, $item->total_mesin);
            $sheet->setCellValue("Q" . $currentRow, $item->ct_detik);
            $sheet->setCellValue("R" . $currentRow, $item->process_time);
            $sheet->setCellValue("S" . $currentRow, $item->reg_active);
            $sheet->setCellValue("T" . $currentRow, $item->dct);
            $sheet->setCellValue("U" . $currentRow, $item->mct);
            $sheet->setCellValue("W" . $currentRow, $item->plan_dct);
            $sheet->setCellValue("X" . $currentRow, $item->tpt);
            $sheet->setCellValue("Y" . $currentRow, $item->gsph_item);
            $sheet->setCellValue("Z" . $currentRow, $item->start_time);
            $sheet->setCellValue("AA" . $currentRow, $item->finish_time);
            $sheet->setCellValue("AB" . $currentRow, $item->act_start);
            $sheet->setCellValue("AC" . $currentRow, $item->act_finish);
            $sheet->setCellValue("AD" . $currentRow, $item->keterangan);
            
            $sheet->setCellValue("AE" . $currentRow, $item->a1 ?: '');
            $sheet->setCellValue("AF" . $currentRow, $item->a2 ?: '');
            $sheet->setCellValue("AG" . $currentRow, $item->a3 ?: '');
            $sheet->setCellValue("AH" . $currentRow, $item->a4 ?: '');

            // Styling if it's a break
            if ($item->row_type === 'break') {
                $sheet->getStyle("C{$currentRow}:AH{$currentRow}")->getFont()->setBold(true)->setItalic(true);
                $sheet->getStyle("C{$currentRow}:AH{$currentRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE2E8F0');
            } else {
                foreach (['AE', 'AF', 'AG', 'AH'] as $colLetter) {
                    $val = $sheet->getCell($colLetter . $currentRow)->getValue();
                    if ($val) {
                        $sheet->getStyle($colLetter . $currentRow)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FF0D1B2A');
                        $sheet->getStyle($colLetter . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
                    }
                }
            }

            $currentRow++;
        }

        // No longer call copySignatureBlock manually, we rely on the dynamic Camera Tool shapes
        // $this->copySignatureBlock($sheet, $finishRow + 11, $pressName, $date);

        return $diff;
    }

    private function copySignatureBlock($sheet, $dstStartRow, $pressName, $date)
    {
        $spreadsheet = $sheet->getParent();
        $srcSheet = $spreadsheet->getSheetByName('Format TTD');
        if (!$srcSheet) {
            return;
        }

        $shiftName = $sheet->getTitle();

        // Determine source rows based on shift name
        if (str_contains(strtoupper($shiftName), 'MALAM')) {
            $srcStartRow = 17;
            $srcEndRow = 24;
        } else {
            $srcStartRow = 7;
            $srcEndRow = 14;
        }

        $rowOffset = $dstStartRow - $srcStartRow;

        // Query the custom Sect Head PPC from database if saved, or use override
        $sectHeadVal = null;
        if ($this->sectHeadOverride) {
            $sectHeadVal = $this->sectHeadOverride;
        } elseif ($date && $pressName) {
            $sectHeadVal = \App\Models\ScheduleStamping::where('upload_date', $date)
                ->where('shift_name', $this->normalizeShiftName($shiftName))
                ->where('press_name', $pressName)
                ->whereNotNull('sect_head_ppc')
                ->value('sect_head_ppc');
        }

        // Known Sect. Head PPC names that can appear in the Format TTD template
        $knownSectHeadNames = ['Alberta P. S.', 'Alberta P.S.', 'Alberta', 'Alvyn'];

        // Copy cells row by row, column by column (from column J to Y, i.e., 10 to 25)
        $startCol = 10; // J
        $endCol = 25;   // Y

        for ($r = $srcStartRow; $r <= $srcEndRow; $r++) {
            $dstRow = $r + $rowOffset;
            
            // Copy row height
            $srcHeight = $srcSheet->getRowDimension($r)->getRowHeight();
            if ($srcHeight !== null && $srcHeight !== -1) {
                $sheet->getRowDimension($dstRow)->setRowHeight($srcHeight);
            }

            for ($c = $startCol; $c <= $endCol; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $srcCoord = $colLetter . $r;
                $dstCoord = $colLetter . $dstRow;

                // Copy value
                $srcCell = $srcSheet->getCell($srcCoord);
                $srcValue = $srcCell->getValue();

                // If user selected a Sect. Head PPC, replace any known name in the template
                if ($sectHeadVal && $srcValue !== null && $srcValue !== '') {
                    if (in_array(trim((string)$srcValue), $knownSectHeadNames)) {
                        $sheet->getCell($dstCoord)->setValue($sectHeadVal);
                    } else {
                        $sheet->getCell($dstCoord)->setValue($srcValue);
                    }
                } else {
                    $sheet->getCell($dstCoord)->setValue($srcValue);
                }
            }

            // Copy style in one go for the entire row
            $sheet->duplicateStyle($srcSheet->getStyle("J{$r}:Y{$r}"), "J{$dstRow}:Y{$dstRow}");
        }

        // Copy merged cells
        foreach ($srcSheet->getMergeCells() as $mergedRange) {
            $rangeParts = explode(':', $mergedRange);
            if (count($rangeParts) === 2) {
                $startCell = $rangeParts[0];
                $endCell = $rangeParts[1];
                
                $startRow = intval(preg_replace('/[^0-9]/', '', $startCell));
                $endRow = intval(preg_replace('/[^0-9]/', '', $endCell));
                
                if ($startRow >= $srcStartRow && $endRow <= $srcEndRow) {
                    $startColStr = preg_replace('/[^A-Z]/', '', $startCell);
                    $endColStr = preg_replace('/[^A-Z]/', '', $endCell);
                    
                    $dstStartCell = $startColStr . ($startRow + $rowOffset);
                    $dstEndCell = $endColStr . ($endRow + $rowOffset);
                    
                    $sheet->mergeCells("{$dstStartCell}:{$dstEndCell}");
                }
            }
        }
    }

    private function exportFallback($date, $shift, $press, $search)
    {
        $query = ScheduleStamping::query();
        if ($date)  $query->where('upload_date', $date);
        if ($shift) $query->where('shift_name', $shift);
        if ($press) $query->where('press_name', $press);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('job_master', 'like', "%{$search}%")
                  ->orWhere('job_no', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id', 'asc')->get();
        $items = $items->sortBy('sort_order')->values();
        $jobRows   = $items->where('row_type', 'job')->values();
        $breakRows = $items->where('row_type', 'break')->values();
        $shiftName = $items->first()->shift_name ?? '';
        $items     = $this->mergeJobsAndBreaks($jobRows, $breakRows, $shiftName);

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diexport.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            '#', 'JOB MASTER', 'TYPE', 'QTY/PLT', 'KEB.MTL', 'TOT.PLT', 'JOB NO.',
            'PLAN', 'OK', 'REPAIR', 'REJECT', 'MESIN', 'CT(")', 'PROC.TIME',
            'REG.ACT', 'DCT', 'TPT', 'GSPH', 'START', 'FINISH', 'A-1', 'A-2', 'A-3', 'A-4', 'TOTAL PCS', 'KETERANGAN' 
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        $sheet->getStyle('A1:Z1')->getFont()->setBold(true);
        $sheet->getStyle('A1:Z1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');

        $rowNum = 2;
        foreach ($items as $item) {
            if ($item->row_type === 'break') {
                $sheet->setCellValue('G' . $rowNum, $item->job_no);
                $sheet->setCellValue('P' . $rowNum, $item->dct);
                $sheet->setCellValue('Q' . $rowNum, $item->tpt);
                $sheet->setCellValue('S' . $rowNum, $item->start_time);
                $sheet->setCellValue('T' . $rowNum, $item->finish_time);
                $sheet->setCellValue('U' . $rowNum, $item->a1);
                $sheet->setCellValue('V' . $rowNum, $item->a2);
                $sheet->setCellValue('W' . $rowNum, $item->a3);
                $sheet->setCellValue('X' . $rowNum, $item->a4);
                
                $sheet->getStyle('U' . $rowNum . ':X' . $rowNum)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9');
                
                $sheet->getStyle('A' . $rowNum . ':Z' . $rowNum)->getFont()->setBold(true);
            } else {
                $sheet->setCellValue('A' . $rowNum, $item->row_no);
                $sheet->setCellValue('B' . $rowNum, $item->job_master);
                $sheet->setCellValue('C' . $rowNum, $item->type_plt);
                $sheet->setCellValue('D' . $rowNum, $item->qty_plt);
                $sheet->setCellValue('E' . $rowNum, $item->keb_mtl);
                $sheet->setCellValue('F' . $rowNum, $item->total_plt);
                $sheet->setCellValue('G' . $rowNum, $item->job_no);
                $sheet->setCellValue('H' . $rowNum, $item->plan);
                $sheet->setCellValue('I' . $rowNum, $item->ok);
                $sheet->setCellValue('J' . $rowNum, $item->repair);
                $sheet->setCellValue('K' . $rowNum, $item->reject);
                $sheet->setCellValue('L' . $rowNum, $item->total_mesin);
                $sheet->setCellValue('M' . $rowNum, $item->ct_detik);
                $sheet->setCellValue('N' . $rowNum, $item->process_time);
                $sheet->setCellValue('O' . $rowNum, $item->reg_active);
                $sheet->setCellValue('P' . $rowNum, $item->dct);
                $sheet->setCellValue('Q' . $rowNum, $item->tpt);
                $sheet->setCellValue('R' . $rowNum, $item->gsph_item);
                $sheet->setCellValue('S' . $rowNum, $item->start_time);
                $sheet->setCellValue('T' . $rowNum, $item->finish_time);
                $sheet->setCellValue('U' . $rowNum, $item->a1);
                $sheet->setCellValue('V' . $rowNum, $item->a2);
                $sheet->setCellValue('W' . $rowNum, $item->a3);
                $sheet->setCellValue('X' . $rowNum, $item->a4);
                $sheet->setCellValue('Y' . $rowNum, $item->total_pcs);
                $sheet->setCellValue('Z' . $rowNum, $item->keterangan);
            }
            $rowNum++;
        }

        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Schedule_Stamping_{$date}.xlsx";
        if ($shift && $press) {
            $filename = "Schedule_Stamping_{$date}_{$shift}_{$press}.xlsx";
        } elseif ($shift) {
            $filename = "Schedule_Stamping_{$date}_{$shift}.xlsx";
        } elseif ($press) {
            $filename = "Schedule_Stamping_{$date}_{$press}.xlsx";
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    // ── UPLOAD ─────────────────────────────────────────────────────────────────
    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,xlsm,vnd.openxmlformats-officedocument.spreadsheetml.sheet,vnd.ms-excel|max:51200',
        ]);

        try {
            $file         = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();
            $extension    = strtolower($file->getClientOriginalExtension());
            $uploadDir    = storage_path('app/uploads');
            $dataPath     = $uploadDir . '/schedule_stamping_temp.' . $extension;

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $file->move($uploadDir, 'schedule_stamping_temp.' . $extension);
            @copy($dataPath, $uploadDir . '/debug_schedule_stamping.' . $extension);

            $python = $this->findPython();
            if (!$python) {
                @unlink($dataPath);
                return back()->with('error', 'Python tidak ditemukan. Pastikan Python 3 sudah terinstall.');
            }

            $scriptPath = base_path('python/read_schedule_stamping.py');
            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('error', 'Script python/read_schedule_stamping.py tidak ditemukan.');
            }

            $targetShift = $request->input('target_shift', 'AUTO');
            $output = $this->runPythonScript($python, $scriptPath, $dataPath, $originalName, $targetShift);


            if ($output === null || $output === '') {
                @unlink($dataPath);
                return back()->with('error', 'Script Python tidak menghasilkan output. Cek apakah openpyxl sudah terinstall: pip install openpyxl');
            }

            $jsonStart = strpos($output, '{');
            if ($jsonStart === false) {
                @unlink($dataPath);
                return back()->with('error', 'Output tidak valid: ' . substr($output, 0, 300));
            }

            $result = json_decode(substr($output, $jsonStart), true);
            if (!$result || isset($result['error'])) {
                @unlink($dataPath);
                return back()->with('error', 'Error: ' . ($result['error'] ?? 'Unknown error'));
            }

            if (empty($result['sheets'])) {
                @unlink($dataPath);
                return back()->with('error', 'Tidak ada data ditemukan di file Excel.');
            }

            $uploadDate    = $result['upload_date'];
            $pressOverride = $request->input('press_override'); // manual press selection (optional)
            $imported      = 0;
            $firstShift    = null;
            $firstPress    = null;

            DB::transaction(function () use ($result, $uploadDate, $pressOverride, &$imported, &$firstShift, &$firstPress) {
                foreach ($result['sheets'] as $sectionKey => $sheetData) {
                    // New format key: "ShiftName|||PressName"
                    $rawShiftName = $sheetData['shift_name'] ?? explode('|||', $sectionKey)[0];
                    $pressName    = $pressOverride ?: ($sheetData['press_name'] ?? null);

                    // Normalise shift name: strip Rev suffixes so display filters always
                    // use the canonical 'Shift Pagi' / 'Shift Malam' keys.
                    $shiftName = $this->normalizeShiftName($rawShiftName);

                    if (!$firstShift) {
                        $firstShift = $shiftName;
                        $firstPress = $pressName;
                    }

                    // Delete old data for same date + shift + press
                    $deleteQuery = ScheduleStamping::where('upload_date', $uploadDate)
                        ->where('shift_name', $shiftName);
                    if ($pressName) {
                        $deleteQuery->where('press_name', $pressName);
                    }
                    $deleteQuery->delete();

                    $rows = [];
                    $now  = now();
                    $sortOrderCounter = 0;
                    foreach ($sheetData['rows'] as $item) {
                        $sortOrderCounter++;
                        $rows[] = [
                            'upload_date'  => $uploadDate,
                            'press_name'   => $pressName,
                            'shift_name'   => $shiftName,
                            'hari'         => $sheetData['hari'] ?? null,
                            'tgl'          => $sheetData['tgl'] ?? null,
                            'jam'          => $sheetData['jam'] ?? null,
                            'revisi'       => $sheetData['revisi'] ?? null,
                            'row_no'       => $item['row_no'],
                            'row_type'     => $item['row_type'],
                            'sort_order'   => $sortOrderCounter,
                            'job_master'   => $item['job_master'],
                            'type_plt'     => $item['type_plt'],
                            'qty_plt'      => $item['qty_plt'],
                            'keb_mtl'      => $item['keb_mtl'],
                            'total_plt'    => $item['total_plt'],
                            'job_no'       => $item['job_no'],
                            'each_part'    => $item['each_part'],
                            'plan'         => $item['plan'],
                            'ok'           => $item['ok'],
                            'repair'       => $item['repair'],
                            'reject'       => $item['reject'],
                            'total_mesin'  => $item['total_mesin'],
                            'ct_detik'     => $item['ct_detik'],
                            'process_time' => $item['process_time'],
                            'reg_active'   => $item['reg_active'],
                            'dct'          => $item['dct'],
                            'mct'          => $item['mct'],
                            'plan_dct'     => $item['plan_dct'],
                            'tpt'          => $item['tpt'],
                            'gsph_item'    => $item['gsph_item'],
                            'start_time'   => $item['start_time'],
                            'finish_time'  => $item['finish_time'],
                            'act_start'    => $item['act_start'],
                            'act_finish'   => $item['act_finish'],
                            'keterangan'   => $item['keterangan'],
                            'a1'           => $item['a1'],
                            'a2'           => $item['a2'],
                            'a3'           => $item['a3'],
                            'a4'           => $item['a4'],
                            'dt_menit'     => $item['dt_menit'],
                            'total_pcs'    => $item['total_pcs'],
                            'tpt_total'    => $item['tpt_total'],
                            'sect_head_ppc' => str_contains(strtoupper($shiftName), 'MALAM') ? 'Alvyn' : 'Alberta P. S.',
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }

                    foreach (array_chunk($rows, 100) as $chunk) {
                        ScheduleStamping::insert($chunk);
                    }
                    $imported += count($rows);
                }
            });

            // We keep the uploaded times exactly identical to the Excel file.
            // The user can trigger recalibration manually if needed.

            @unlink($dataPath);

            if ($imported === 0) {
                return back()->with('error', 'Tidak ada data yang berhasil diimport.');
            }

            return redirect()->route('schedule_stamping.index', [
                'date'  => $uploadDate,
                'shift' => $firstShift,
                'press' => $firstPress,
            ])->with('success', "Upload berhasil! {$imported} baris diimport dan dikalibrasi ulang sesuai jam istirahat.");

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function updateInline(Request $request)
    {
        $request->validate([
            'id'    => 'required|integer',
            'field' => 'required|string',
            'value' => 'required',
        ]);

        $item = ScheduleStamping::findOrFail($request->id);
        $field = $request->field;
        $value = $request->value;

        // Fields that trigger recalculation
        $calcFields = ['plan', 'qty_plt', 'ct_detik', 'dct', 'reg_active', 'total_mesin', 'ok', 'repair', 'reject', 'start_time', 'a1', 'a2', 'a3', 'a4'];

        $item->$field = $value;
        if ($item->row_type === 'job') {
            $this->calculateRow($item);
        }
        $item->save();

        if (in_array($field, $calcFields)) {
            $firstItem = ScheduleStamping::where('upload_date', $item->upload_date)
                ->where('shift_name', $item->shift_name)
                ->where('press_name', $item->press_name)
                ->orderByRaw('COALESCE(sort_order, id) ASC')
                ->first();
            if ($firstItem) {
                $this->recalculateAndCascade($firstItem);
            }
        }

        // Return the updated row and potentially subsequent rows if they changed
        // For simplicity, we'll return the updated item and the JS will decide if it needs to reload or update others.
        // But a full refresh is safer for time cascades.
        return response()->json([
            'success' => true,
            'item' => $item->fresh()
        ]);
    }

    /**
     * Add a new empty job row to the schedule manually (no Excel upload needed).
     */
    public function addJob(Request $request)
    {
        $request->validate([
            'date'        => 'required|string',
            'shift'       => 'required|string',
            'press'       => 'required|string',
            'job_no'      => 'required|string|max:100',
            'plan'        => 'nullable|numeric|min:0',
            'ct_detik'    => 'nullable|numeric|min:0',
            'total_mesin' => 'nullable|integer|min:0',
            'dct'         => 'nullable|numeric|min:0',
            'reg_active'  => 'nullable|numeric|min:0',
            'keterangan'  => 'nullable|string|max:255',
            'job_master'  => 'nullable|string|max:100',
            'type_plt'    => 'nullable|string|max:100',
            'qty_plt'     => 'nullable|numeric|min:0',
            'keb_mtl'     => 'nullable|numeric|min:0',
        ]);

        $date  = $request->date;
        $shift = $request->shift;
        $press = $request->press;

        // Try to lookup from master data stamping if not provided or to merge
        $jobNo = trim($request->job_no);
        $master = MasterStamping::where('job_no', $jobNo)->first();

        $jobMaster  = $request->job_master ? trim($request->job_master) : ($master ? $master->job_master : $jobNo);
        $typePlt    = $request->type_plt ? trim($request->type_plt) : ($master ? $master->type_pallet : null);
        $qtyPlt     = $request->qty_plt !== null ? (float)$request->qty_plt : ($master ? (float)$master->qty_pallet : 0.0);
        $ctDetik    = $request->ct_detik !== null ? (float)$request->ct_detik : ($master ? (float)$master->ct_detik : 0.0);
        $dct        = $request->dct !== null ? (float)$request->dct : ($master ? (float)$master->dct : 0.0);
        $regActive  = $request->reg_active !== null ? (float)$request->reg_active : ($master ? (float)$master->reg_active : 0.0);
        $selectedMachines = $request->input('machines', []);
        if (empty($selectedMachines)) {
            $selectedMachines = [1, 2, 3, 4];
        }
        $totalMesin = count($selectedMachines);
        $keterangan = $request->keterangan ? trim($request->keterangan) : ($master ? $master->remarks : '');
        $kebMtl     = $request->keb_mtl !== null ? (float)$request->keb_mtl : 0.0;

        // Ensure standard breaks exist for this section
        $this->ensureStandardBreaks($date, $shift, $press);

        // Determine next row_no
        $maxRowNo = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->where('row_type', 'job')
            ->max('row_no') ?? 0;
        $newRowNo = $maxRowNo + 1;

        // Determine start time: last job's finish_time, or shift default
        $lastJob = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->where('row_type', 'job')
            ->orderBy('sort_order', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob && $lastJob->finish_time) {
            $startTime = $lastJob->finish_time;
        } else {
            // Default start: Shift Pagi = 07:30, Shift Malam = 21:00
            if (str_contains(strtoupper($shift), 'MALAM')) {
                $startTime = '21:00';
            } else {
                $startTime = '07:30';
            }
        }

        // Push start time out of any break
        $anyRow = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->whereNotNull('hari')
            ->value('hari');
        $hari = $anyRow ?? '';
        $fixedBreaks = $this->getFixedBreaks($shift, $hari);
        $startTime = $this->pushIfInBreak($startTime, $shift, $fixedBreaks);

        // Hitung TPT kasar untuk pre-populate a1..a4
        $plan = $request->plan ?? 0;
        $processTime = ($plan * $ctDetik) / 60;
        $tpt = (int)ceil($processTime + $regActive + $dct);

        $a1 = in_array(1, $selectedMachines) ? $tpt : 0;
        $a2 = in_array(2, $selectedMachines) ? $tpt : 0;
        $a3 = in_array(3, $selectedMachines) ? $tpt : 0;
        $a4 = in_array(4, $selectedMachines) ? $tpt : 0;

        // Create the new job row
        $item = ScheduleStamping::create([
            'upload_date'  => $date,
            'shift_name'   => $shift,
            'press_name'   => $press,
            'hari'         => $hari ?: null,
            'row_no'       => $newRowNo,
            'row_type'     => 'job',
            'sort_order'   => ($lastJob ? ($lastJob->sort_order ?? $lastJob->id) : 0) + 1,
            'job_no'       => $jobNo,
            'job_master'   => $jobMaster,
            'type_plt'     => $typePlt,
            'qty_plt'      => $qtyPlt,
            'keb_mtl'      => $kebMtl,
            'plan'         => $plan,
            'ct_detik'     => $ctDetik,
            'total_mesin'  => $totalMesin,
            'dct'          => $dct,
            'reg_active'   => $regActive,
            'keterangan'   => $keterangan,
            'start_time'   => $startTime,
            'a1'           => $a1,
            'a2'           => $a2,
            'a3'           => $a3,
            'a4'           => $a4,
        ]);

        // Calculate derived fields and finish time
        $this->calculateRow($item);
        $item->save();

        // Recalculate cascade from first item
        $firstItem = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->first();
        if ($firstItem) {
            $this->recalculateAndCascade($firstItem);
        }

        return redirect()->route('schedule_stamping.index', [
            'date'  => $date,
            'shift' => $shift,
            'press' => $press,
        ])->with('success', "Job '{$item->job_no}' berhasil ditambahkan ke antrian #{$newRowNo}.");
    }

    /**
     * Delete a specific job row and recalculate the remaining sequence.
     */
    public function deleteJob($id)
    {
        $item = ScheduleStamping::findOrFail($id);

        if ($item->row_type === 'break') {
            return response()->json(['success' => false, 'message' => 'Baris istirahat tidak dapat dihapus.']);
        }

        $date  = $item->upload_date;
        $shift = $item->shift_name;
        $press = $item->press_name;

        $item->delete();

        // Renumber row_no for remaining jobs
        $remaining = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->where('row_type', 'job')
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->get();

        $counter = 1;
        foreach ($remaining as $row) {
            $row->row_no = $counter++;
            $row->save();
        }

        // Recalculate cascade
        $firstItem = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->first();
        if ($firstItem) {
            $this->recalculateAndCascade($firstItem);
        }

        return response()->json(['success' => true, 'message' => 'Job berhasil dihapus.']);
    }

    /**
     * Reorder rows via drag-and-drop.
     * Receives ordered_ids: [id1, id2, id3, ...] representing the desired sequence.
     * We swap IDs in DB so the ORDER BY id reflects the new drag order,
     * then trigger a full recalculation cascade.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'ordered_ids'   => 'required|array|min:1',
            'ordered_ids.*' => 'integer',
        ]);

        $orderedIds = array_values(array_unique($request->ordered_ids));
        if (empty($orderedIds)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided']);
        }

        // Ambil info section dari row pertama
        $firstLoadedRow = ScheduleStamping::whereIn('id', $orderedIds)->first();
        if (!$firstLoadedRow) {
            return response()->json(['success' => false, 'message' => 'Rows not found']);
        }

        $uploadDate = $firstLoadedRow->upload_date;
        $shiftName  = $firstLoadedRow->shift_name;
        $pressName  = $firstLoadedRow->press_name;

        // Simpan sort_order baru — index 0 = urutan pertama
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $jobId) {
                DB::table('schedule_stampings')
                    ->where('id', $jobId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        // Renumber row_no sesuai urutan baru
        $jobRows = ScheduleStamping::where('upload_date', $uploadDate)
            ->where('shift_name', $shiftName)
            ->where('press_name', $pressName)
            ->where('row_type', 'job')
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->get();

        $counter = 1;
        foreach ($jobRows as $row) {
            $row->row_no = $counter++;
            $row->save();
        }

        // CATATAN: drag hanya menyimpan urutan baru (sort_order + row_no).
        // Kalkulasi waktu start/finish dengan memperhitungkan jam istirahat
        // dilakukan oleh tombol "Recalibrate" yang dipanggil secara eksplisit oleh user.

        return response()->json([
            'success' => true,
            'message' => 'Urutan disimpan. Klik Recalibrate untuk menghitung ulang waktu.',
        ]);
    }


    /**
     * Recalculate semua waktu start/finish untuk section tertentu.
     * Dipanggil setelah drag reorder atau penambahan break.
     * Hanya mengubah start_time, finish_time, row_no — tidak mengubah data lain.
     */
    private function recalculateSection($uploadDate, $shiftName, $pressName, $initialStartTime = null)
    {
        // Ensure standard breaks exist before recalculation
        $this->ensureStandardBreaks($uploadDate, $shiftName, $pressName);

        $hari        = ScheduleStamping::where('upload_date', $uploadDate)
            ->where('shift_name', $shiftName)
            ->whereNotNull('hari')->value('hari') ?? '';

        $fixedBreaks = $this->getFixedBreaks($shiftName, $hari);
        $isMalam     = str_contains(strtoupper($shiftName), 'MALAM');

        // Reset break rows ke waktu tetapnya
        ScheduleStamping::where('upload_date', $uploadDate)
            ->where('shift_name', $shiftName)
            ->where('press_name', $pressName)
            ->where('row_type', 'break')
            ->get()
            ->each(function ($br) use ($fixedBreaks, $shiftName) {
                foreach ($fixedBreaks as $b) {
                    if ($this->matchBreakName($br->job_no, $b['name'], $shiftName)) {
                        $br->start_time  = $b['start'];
                        $br->finish_time = $b['finish'];
                        $br->save();
                        break;
                    }
                }
            });

        // Ambil job rows dalam urutan drag
        $jobItems = ScheduleStamping::where('upload_date', $uploadDate)
            ->where('shift_name', $shiftName)
            ->where('press_name', $pressName)
            ->where('row_type', 'job')
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->get();

        // Gunakan representasi menit normalized dari awal shift sebagai tracker internal untuk masing-masing mesin.
        // Ini menghindari ambiguitas perbandingan waktu saat melewati tengah malam dan mendukung paralelisme multi-mesin.
        if ($initialStartTime && $initialStartTime !== '-') {
            $shiftStartMins = $this->timeToMinutesNormalized($initialStartTime, $shiftName);
        } else {
            $shiftStartMins = $this->timeToMinutesNormalized($isMalam ? '21:00' : '07:30', $shiftName);
        }

        $machineFinishMins = [
            1 => $shiftStartMins,
            2 => $shiftStartMins,
            3 => $shiftStartMins,
            4 => $shiftStartMins,
        ];

        foreach ($jobItems as $item) {
            // Tentukan mesin yang digunakan oleh job ini (kolom a1, a2, a3, a4)
            $usedMachines = [];
            if ($item->a1 > 0) $usedMachines[] = 1;
            if ($item->a2 > 0) $usedMachines[] = 2;
            if ($item->a3 > 0) $usedMachines[] = 3;
            if ($item->a4 > 0) $usedMachines[] = 4;

            // Tentukan start (dalam menit normalized)
            if (empty($usedMachines)) {
                // Jika tidak ada mesin tertentu yang dispesifikasikan, tunggu semua mesin yang sedang berjalan selesai
                $startMins = max($machineFinishMins);
            } else {
                // Tunggu semua mesin yang digunakan job ini siap/selesai
                $startMins = max(array_intersect_key($machineFinishMins, array_flip($usedMachines)));
            }

            // Dorong start keluar dari jendela break (loop max 10x untuk break beruntun)
            for ($push = 0; $push < 10; $push++) {
                $pushed = false;
                foreach ($fixedBreaks as $b) {
                    $bStart = $this->timeToMinutesNormalized($b['start'], $shiftName);
                    $bEnd   = $this->timeToMinutesNormalized($b['finish'], $shiftName);
                    if ($startMins >= $bStart && $startMins < $bEnd) {
                        $startMins = $bEnd;
                        $pushed    = true;
                        break; // re-check semua break dari posisi baru
                    }
                }
                if (!$pushed) break;
            }

            $startTime = $this->minutesToTime($startMins);

            // Use direct TPT from DB (which could have been edited or loaded from Excel)
            $tptMurni = (int)($item->tpt ?? 0);
            if ($tptMurni <= 0) {
                $processTime = (float)($item->process_time ?? 0);
                $regActive   = (float)($item->reg_active ?? 0);
                $dct         = (float)($item->dct ?? 0);
                $tptMurni    = (int)ceil($processTime + $regActive + $dct);
            }

            // Hitung finish dengan memperhitungkan jeda istirahat
            $finishMins = $this->calculateFinishMinsWithBreaks($startMins, $tptMurni, $shiftName, $hari);
            $finishTime = $this->minutesToTime($finishMins);

            // Simpan ke DB
            DB::table('schedule_stampings')
                ->where('id', $item->id)
                ->update([
                    'start_time'  => $startTime,
                    'finish_time' => $finishTime,
                    'tpt'         => $tptMurni > 0 ? $tptMurni : $item->tpt,
                    'updated_at'  => now(),
                ]);

            // Update status ketersediaan masing-masing mesin
            if (empty($usedMachines)) {
                foreach ($machineFinishMins as $k => $val) {
                    $machineFinishMins[$k] = $finishMins;
                }
            } else {
                foreach ($usedMachines as $m) {
                    $machineFinishMins[$m] = $finishMins;
                }
            }
        }
    }

    private function recalculateAndCascade(ScheduleStamping $startItem)
    {
        // Delegate to recalculateSection for consistent break-aware logic
        $this->recalculateSection(
            $startItem->upload_date,
            $startItem->shift_name,
            $startItem->press_name
        );
    }

    public function addStandardBreaks(Request $request)
    {
        $date  = $request->get('date');
        $shift = $request->get('shift');
        $press = $request->get('press');

        if (!$date || !$shift || !$press) {
            return back()->with('error', 'Pilih Tanggal, Shift, dan Press terlebih dahulu.');
        }

        $this->ensureStandardBreaks($date, $shift, $press);
        $this->recalculateSection($date, $shift, $press);

        return back()->with('success', 'Waktu istirahat standar berhasil ditambahkan.');
    }

    private function ensureStandardBreaks($date, $shift, $press)
    {
        // Fetch hari from any existing row in this section (to detect Friday)
        $anyRow = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->whereNotNull('hari')
            ->value('hari');
        $hari = $anyRow ?? '';

        $standardBreaks = $this->getFixedBreaks($shift, $hari);
        $standardNames  = array_column($standardBreaks, 'name');

        // ── Step 1: Hapus SEMUA break rows yang sudah ada untuk section ini.
        // Ini memastikan tidak ada duplikat dan tidak ada break lama dengan
        // timing yang salah (misal: BREAKTIME lama di 15:15 yang seharusnya CINGKORAK).
        ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->where('row_type', 'break')
            ->delete();

        // ── Step 2: Buat ulang semua break dari definisi kanonik.
        foreach ($standardBreaks as $b) {
            ScheduleStamping::create([
                'upload_date' => $date,
                'shift_name'  => $shift,
                'press_name'  => $press,
                'row_type'    => 'break',
                'job_no'      => $b['name'],
                'start_time'  => $b['start'],
                'finish_time' => $b['finish'],
                'dct'         => $b['tpt'],
                'tpt'         => $b['tpt'],
                'plan_dct'    => $b['tpt'],
                'a1'          => $b['tpt'],
                'a2'          => $b['tpt'],
                'a3'          => $b['tpt'],
                'a4'          => $b['tpt'],
            ]);
        }
    }

    /**
     * Recalibrate semua section yang ada di DB agar waktu istirahat diperhitungkan.
     * Dijalankan sekali dari UI untuk memperbaiki data lama.
     */
    public function recalibrateAll(Request $request)
    {
        // Ambil semua kombinasi unik (date, shift, press)
        $sections = DB::table('schedule_stampings')
            ->select('upload_date', 'shift_name', 'press_name')
            ->whereNotNull('press_name')
            ->distinct()
            ->get();

        $count = 0;
        foreach ($sections as $s) {
            // Hanya proses section yang punya job rows
            $hasJobs = ScheduleStamping::where('upload_date', $s->upload_date)
                ->where('shift_name', $s->shift_name)
                ->where('press_name', $s->press_name)
                ->where('row_type', 'job')
                ->exists();
            if (!$hasJobs) continue;

            $this->recalculateSection($s->upload_date, $s->shift_name, $s->press_name);
            $count++;
        }

        return back()->with('success', "Berhasil merecalibrate {$count} section. Semua waktu istirahat sudah diperhitungkan.");
    }

    /**
     * Recalibrate satu section tertentu (date + shift + press) dari request.
     */
    public function recalibrateSection(Request $request)
    {
        $date  = $request->get('date');
        $shift = $request->get('shift');
        $press = $request->get('press');

        if (!$date || !$shift || !$press) {
            return back()->with('error', 'Pilih Tanggal, Shift, dan Press terlebih dahulu.');
        }

        $this->recalculateSection($date, $shift, $press);

        return back()->with('success', "Waktu start/finish untuk {$press} {$shift} {$date} berhasil dikalibrasi ulang.");
    }


    private function calculateRow(ScheduleStamping $item)
    {
        if ($item->row_type === 'break') return;

        $plan      = (float)$item->plan;
        $qtyPlt    = (float)$item->qty_plt;
        $ct        = (float)$item->ct_detik;
        $dct       = (float)$item->dct;
        $regActive = (float)$item->reg_active;
        $mesin     = (int)$item->total_mesin;

        // Total Plt: ROUNDUP(Plan/QtyPlt, 1)
        if ($qtyPlt > 0) {
            $item->total_plt = ceil(($plan / $qtyPlt) * 10) / 10;
        }

        // Process Time: (Plan * CT) / 60
        $item->process_time = ($plan * $ct) / 60;
        
        // TPT: ROUNDUP(ProcessTime + RegActive + DCT, 0)
        $item->tpt = ceil($item->process_time + $regActive + $dct);
        
        // GSPH: (Plan / TPT) * 60
        if ($item->tpt > 0) {
            $item->gsph_item = round(($plan / $item->tpt) * 60);
        }

        // Total Pcs: OK + Repair + Reject (Standard actual production logic)
        $item->total_pcs = ($item->ok ?? 0) + ($item->repair ?? 0) + ($item->reject ?? 0);

        // plan_dct: (reg_active + dct) * mesin
        $item->plan_dct = ($regActive + $dct) * $mesin;
        
        // tpt_total: plan * mesin
        $item->tpt_total = $plan * $mesin;

        // Calculate finish time with break awareness
        if ($item->start_time) {
            if ($item->tpt > 0) {
                $item->finish_time = $this->calculateFinishWithBreaks($item->start_time, (int)$item->tpt, $item->shift_name, $item->hari ?? null);
            } else {
                $item->finish_time = $item->start_time;
            }
        }

        // A-1 to A-4 (Allocation based on TPT and total_mesin)
        // Tentukan mesin mana saja yang aktif saat ini
        $activeMachines = [];
        if ($item->a1 > 0) $activeMachines[] = 1;
        if ($item->a2 > 0) $activeMachines[] = 2;
        if ($item->a3 > 0) $activeMachines[] = 3;
        if ($item->a4 > 0) $activeMachines[] = 4;

        // Force reallocation jika total_mesin diubah atau tidak ada mesin yang aktif
        if ($item->isDirty('total_mesin') || empty($activeMachines)) {
            $item->a1 = ($mesin >= 1) ? (int)$item->tpt : 0;
            $item->a2 = ($mesin >= 2) ? (int)$item->tpt : 0;
            $item->a3 = ($mesin >= 3) ? (int)$item->tpt : 0;
            $item->a4 = ($mesin >= 4) ? (int)$item->tpt : 0;
        } else {
            // Skalakan mesin aktif yang ada ke nilai TPT yang baru
            $item->a1 = in_array(1, $activeMachines) ? (int)$item->tpt : 0;
            $item->a2 = in_array(2, $activeMachines) ? (int)$item->tpt : 0;
            $item->a3 = in_array(3, $activeMachines) ? (int)$item->tpt : 0;
            $item->a4 = in_array(4, $activeMachines) ? (int)$item->tpt : 0;
        }
    }

    /**
     * Gabungkan job rows (urutan drag) dengan break rows (posisi waktu tetap).
     * Job rows tetap dalam urutan drag (by id).
     * Break rows disisipkan di antara job rows berdasarkan kapan break itu terjadi
     * relatif terhadap finish time job sebelumnya.
     */
    private function mergeJobsAndBreaks($jobRows, $breakRows, $shiftName)
    {
        if ($breakRows->isEmpty()) {
            return $jobRows;
        }

        // Normalisasi break ke menit dan urutkan
        $breaks = [];
        foreach ($breakRows as $br) {
            $breaks[] = [
                'row' => $br,
                'start' => $this->timeToMinutesNormalized($br->start_time ?? '00:00', $shiftName),
                'end'   => $this->timeToMinutesNormalized($br->finish_time ?? '00:00', $shiftName),
            ];
        }
        usort($breaks, fn($a, $b) => $a['start'] <=> $b['start']);

        $result = collect();
        $usedBreakIds = [];

        foreach ($jobRows as $job) {
            $jobStart = $this->timeToMinutesNormalized($job->start_time ?? '00:00', $shiftName);
            $jobFinish = $this->timeToMinutesNormalized($job->finish_time ?? '00:00', $shiftName);

            // Cari break-break yang overlap/berada di dalam job ini secara ketat (di tengah-tengah job)
            $insideBreaks = [];
            foreach ($breaks as $b) {
                if ($b['start'] > $jobStart && $b['start'] < $jobFinish) {
                    $insideBreaks[] = $b;
                }
            }

            if (empty($insideBreaks)) {
                // Sisipkan break yang terjadi sebelum job ini mulai dan belum dipakai
                foreach ($breaks as $b) {
                    if (in_array($b['row']->id, $usedBreakIds)) continue;
                    if ($b['start'] <= $jobStart) {
                        $result->push($b['row']);
                        $usedBreakIds[] = $b['row']->id;
                    }
                }
                $result->push($job);
            } else {
                // Job ini terbelah oleh satu atau lebih break!
                $currentStart = $jobStart;
                $totalTpt = (float)($job->tpt > 0 ? $job->tpt : ($jobFinish - $jobStart));
                if ($totalTpt <= 0) $totalTpt = 1;

                $accumulatedPlan = 0;
                $splitParts = [];

                foreach ($insideBreaks as $b) {
                    $partDuration = $b['start'] - $currentStart;
                    if ($partDuration > 0) {
                        $partJob = clone $job;
                        $partJob->start_time = $this->minutesToTime($currentStart);
                        $partJob->finish_time = $this->minutesToTime($b['start']);
                        $partJob->tpt = $partDuration;
                        $partJob->is_split = true;
                        $partJob->split_part = 1;
                        $partJob->a1 = $partDuration;
                        $partJob->a4 = $partDuration;
                        $partJob->a2 = $job->a2 ? $partDuration : 0;
                        $partJob->a3 = $job->a3 ? $partDuration : 0;

                        $splitParts[] = [
                            'job' => $partJob,
                            'duration' => $partDuration,
                            'currentStart' => $currentStart,
                        ];
                    }
                    $currentStart = $b['end'];
                }

                // Final part
                $finalDuration = $jobFinish - $currentStart;
                if ($finalDuration > 0) {
                    $partJob = clone $job;
                    $partJob->start_time = $this->minutesToTime($currentStart);
                    $partJob->finish_time = $this->minutesToTime($jobFinish);
                    $partJob->tpt = $finalDuration;
                    $partJob->is_split = true;
                    $partJob->split_part = 2;
                    $partJob->a1 = $finalDuration;
                    $partJob->a4 = $finalDuration;
                    $partJob->a2 = $job->a2 ? $finalDuration : 0;
                    $partJob->a3 = $job->a3 ? $finalDuration : 0;

                    $splitParts[] = [
                        'job' => $partJob,
                        'duration' => $finalDuration,
                        'currentStart' => $currentStart,
                    ];
                }

                $numParts = count($splitParts);
                for ($j = 0; $j < $numParts; $j++) {
                    $pJob = $splitParts[$j]['job'];
                    $dur = $splitParts[$j]['duration'];

                    if ($j === $numParts - 1) {
                        $pJob->plan = max(0, $job->plan - $accumulatedPlan);
                    } else {
                        $pPlan = (int)round(($dur / $totalTpt) * $job->plan);
                        $pJob->plan = $pPlan;
                        $accumulatedPlan += $pPlan;
                    }
                }

                $currentStart = $jobStart;
                $partIndex = 0;

                foreach ($insideBreaks as $b) {
                    $partDuration = $b['start'] - $currentStart;
                    if ($partDuration > 0) {
                        $pJob = $splitParts[$partIndex++]['job'];

                        foreach ($breaks as $ob) {
                            if (in_array($ob['row']->id, $usedBreakIds)) continue;
                            if ($ob['start'] <= $currentStart) {
                                $result->push($ob['row']);
                                $usedBreakIds[] = $ob['row']->id;
                            }
                        }
                        $result->push($pJob);
                    }

                    if (!in_array($b['row']->id, $usedBreakIds)) {
                        $result->push($b['row']);
                        $usedBreakIds[] = $b['row']->id;
                    }

                    $currentStart = $b['end'];
                }

                if ($finalDuration > 0) {
                    $pJob = $splitParts[$partIndex]['job'];
                    $result->push($pJob);
                }
            }
        }

        // Sisipkan sisa break yang belum dipakai
        foreach ($breaks as $b) {
            if (!in_array($b['row']->id, $usedBreakIds)) {
                $result->push($b['row']);
                $usedBreakIds[] = $b['row']->id;
            }
        }

        return $result->values();
    }

    private function getFixedBreaks($shift = null, $hari = null)
    {
        $isJumat = $hari && (str_contains(strtoupper($hari), 'JUMAT') || str_contains(strtoupper($hari), "JUM'AT"));

        if ($shift && str_contains(strtoupper($shift), 'MALAM')) {
            return [
                ['name' => 'ISTIRAHAT MALAM', 'start' => '00:00', 'finish' => '00:45', 'tpt' => 45],
                ['name' => 'BREAKTIME',       'start' => '04:45', 'finish' => '05:00', 'tpt' => 15],
            ];
        }

        // Shift Pagi — hari Jumat istirahat siang lebih panjang
        $siangStart  = $isJumat ? '11:45' : '12:00';
        $siangFinish = $isJumat ? '12:45' : '12:40';
        $siangTpt    = $isJumat ? 60      : 40;
        $siangName   = $isJumat ? 'ISTIRAHAT JUMAT' : 'ISTIRAHAT SIANG';

        return [
            ['name' => $siangName, 'start' => $siangStart,  'finish' => $siangFinish, 'tpt' => $siangTpt],
            ['name' => 'CINGKORAK',       'start' => '15:15',      'finish' => '15:30',      'tpt' => 15],
            ['name' => 'BREAKTIME',       'start' => '16:15',      'finish' => '16:30',      'tpt' => 15],
            ['name' => 'ISTIRAHAT SORE',  'start' => '18:00',      'finish' => '18:30',      'tpt' => 30],
        ];
    }

    private function matchBreakName($jobNo, $breakName, $shiftName)
    {
        $jn = strtoupper(trim($jobNo));
        $bn = strtoupper(trim($breakName));
        
        if ($jn === $bn) return true;
        
        // Remove spaces and special characters
        $jnClean = preg_replace('/[^A-Z]/', '', $jn);
        $bnClean = preg_replace('/[^A-Z]/', '', $bn);
        if ($jnClean === $bnClean) return true;
        
        if (str_contains($jn, 'CINGKORAK') && str_contains($bn, 'CINGKORAK')) return true;
        if ((str_contains($jn, 'BREAKTIME') || str_contains($jn, 'BREAK TIME')) && str_contains($bn, 'BREAKTIME')) return true;
        if (str_contains($jn, 'SORE') && str_contains($bn, 'SORE')) return true;
        
        $isMalam = str_contains(strtoupper($shiftName), 'MALAM');
        if (!$isMalam) {
            if (str_contains($bn, 'SIANG') || str_contains($bn, 'JUMAT')) {
                if ($jn === 'ISTIRAHAT' || str_contains($jn, 'SIANG') || str_contains($jn, 'JUMAT') || str_contains($jn, "JUM'AT") || str_contains($jn, 'LUNCH') || str_contains($jn, 'ISHOMA')) {
                    return true;
                }
            }
        } else {
            if (str_contains($bn, 'MALAM')) {
                if ($jn === 'ISTIRAHAT' || str_contains($jn, 'MALAM')) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function pushIfInBreak($timeStr, $shift = null, $fixedBreaks = null)
    {
        if (!$timeStr || $timeStr === '-') return $timeStr;
        if (!$fixedBreaks) $fixedBreaks = $this->getFixedBreaks($shift);
        
        $mins = $this->timeToMinutesNormalized($timeStr, $shift);
        $pushed = true;
        
        while ($pushed) {
            $pushed = false;
            foreach ($fixedBreaks as $b) {
                $bStart = $this->timeToMinutesNormalized($b['start'], $shift);
                $bEnd   = $this->timeToMinutesNormalized($b['finish'], $shift);
                if ($mins >= $bStart && $mins < $bEnd) {
                    $mins = $bEnd;
                    $pushed = true;
                    break;
                }
            }
        }
        
        return $this->minutesToTime($mins);
    }

    /**
     * Hitung waktu finish (dalam menit normalized) dari startMins + durasi,
     * dengan meloncati semua jendela istirahat yang dilewati.
     *
     * Menggunakan menit normalized (bukan string HH:MM) secara internal
     * sehingga aman untuk Shift Malam yang melewati tengah malam.
     */
    private function calculateFinishMinsWithBreaks(int $startMins, int $duration, $shift = null, $hari = null): int
    {
        $currentTime = $startMins;
        $remaining   = max(0, $duration);

        if ($remaining === 0) return $currentTime;

        $fixedBreaks = $this->getFixedBreaks($shift, $hari);

        // Normalisasi break ke menit dan urutkan
        $breaks = [];
        foreach ($fixedBreaks as $b) {
            $breaks[] = [
                'start' => $this->timeToMinutesNormalized($b['start'], $shift),
                'end'   => $this->timeToMinutesNormalized($b['finish'], $shift),
            ];
        }
        usort($breaks, fn($a, $b) => $a['start'] <=> $b['start']);

        $safetyLimit = 30;
        $iterations  = 0;

        while ($remaining > 0 && $iterations < $safetyLimit) {
            $iterations++;

            // Step 1: Jika currentTime di dalam break, lompat ke akhir break
            $jumped = false;
            foreach ($breaks as $b) {
                if ($currentTime >= $b['start'] && $currentTime < $b['end']) {
                    $currentTime = $b['end'];
                    $jumped = true;
                    break;
                }
            }
            if ($jumped) continue; // cek ulang posisi baru

            // Step 2: Cari break berikutnya yang akan dilewati
            $nextStart = null;
            $nextEnd   = null;
            foreach ($breaks as $b) {
                if ($b['start'] > $currentTime) {
                    if ($nextStart === null || $b['start'] < $nextStart) {
                        $nextStart = $b['start'];
                        $nextEnd   = $b['end'];
                    }
                }
            }

            if ($nextStart !== null) {
                $timeToBreak = $nextStart - $currentTime;
                if ($remaining <= $timeToBreak) {
                    // Selesai sebelum break — tidak perlu lompat
                    $currentTime += $remaining;
                    $remaining    = 0;
                } else {
                    // Melewati break — lanjut setelah break
                    $remaining   -= $timeToBreak;
                    $currentTime  = $nextEnd;
                }
            } else {
                // Tidak ada break lagi — habiskan sisa waktu
                $currentTime += $remaining;
                $remaining    = 0;
            }
        }

        return $currentTime;
    }

    /**
     * Wrapper string-based untuk backward compatibility (dipakai calculateRow).
     */
    private function calculateFinishWithBreaks($startTime, $duration, $shift = null, $hari = null)
    {
        $startMins  = $this->timeToMinutesNormalized($startTime, $shift);
        $finishMins = $this->calculateFinishMinsWithBreaks($startMins, (int)$duration, $shift, $hari);
        return $this->minutesToTime($finishMins);
    }

    private function timeToMinutes($timeStr)
    {
        if (!$timeStr || $timeStr === '-') return 0;
        $timeStr = str_replace('.', ':', $timeStr);
        $parts = explode(':', $timeStr);
        if (count($parts) < 2) return 0;
        return (int)$parts[0] * 60 + (int)$parts[1];
    }

    private function timeToMinutesNormalized($timeStr, $shift = null)
    {
        $mins = $this->timeToMinutes($timeStr);
        // If Shift Malam, times from 00:00 to 11:59 are treated as the next day (+1440 mins)
        // to keep the timeline monotonic starting from 21:00.
        if ($shift && str_contains(strtoupper($shift), 'MALAM')) {
            if ($mins < 720) { // Before 12:00 PM
                $mins += 1440;
            }
        }
        return $mins;
    }

    private function minutesToTime($mins)
    {
        $h = floor($mins / 60) % 24;
        $m = $mins % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    private function addMinutesToTime($timeStr, $minutes)
    {
        if (!$timeStr || $timeStr === '-') return null;
        try {
            $parts = explode(':', $timeStr);
            if (count($parts) < 2) return $timeStr;
            $h = (int)$parts[0];
            $m = (int)$parts[1];
            
            $totalMinutes = $h * 60 + $m + $minutes;
            $newH = floor($totalMinutes / 60) % 24;
            $newM = $totalMinutes % 60;
            
            return sprintf('%02d:%02d', $newH, $newM);
        } catch (\Exception $e) {
            return $timeStr;
        }
    }

    private function getBreakOverlap($startTime, $tpt, $shift = null)
    {
        if (!$startTime || $tpt <= 0) return null;
        $startMins = $this->timeToMinutes($startTime);
        $finishMins = $startMins + $tpt;
        $fixedBreaks = $this->getFixedBreaks($shift);
        
        foreach ($fixedBreaks as $b) {
            $bStart = $this->timeToMinutes($b['start']);
            $bEnd   = $this->timeToMinutes($b['finish']);
            
            // If job starts before break and would naturally end during or after the break start
            if ($startMins < $bStart && $finishMins > $bStart) {
                return [
                    'break_start' => $b['start'],
                    'break_end'   => $b['finish']
                ];
            }
        }
        return null;
    }

    private function getNextJob($allItems, $currentItem)
    {
        $found = false;
        foreach ($allItems as $it) {
            if ($it->id == $currentItem->id) {
                $found = true;
                continue;
            }
            if ($found && $it->row_type === 'job') {
                return $it;
            }
        }
        return null;
    }

    // ── HELPERS ────────────────────────────────────────────────────────────────

    /**
     * Convert any shift name variant (e.g. "Shift Pagi (Rev)", "Shift Pagi Revisi")
     * to the canonical display name used by the UI filters.
     */
    private function normalizeShiftName(string $raw): string
    {
        $u = strtoupper(trim($raw));
        if (str_contains($u, 'PAGI'))  return 'Shift Pagi';
        if (str_contains($u, 'MALAM')) return 'Shift Malam';
        return $raw; // unknown — keep as-is
    }

    private function findPython(): ?string
    {
        $appData     = getenv('LOCALAPPDATA') ?: getenv('APPDATA');
        $userProfile = getenv('USERPROFILE') ?: getenv('HOME');
        $candidates  = ['python', 'python3', 'py'];

        foreach (['Python312','Python311','Python310','Python39','Python38'] as $ver) {
            $n = substr($ver, 6);
            $candidates[] = "C:\\Python{$n}\\python.exe";
            if ($appData)     $candidates[] = "{$appData}\\Programs\\Python\\{$ver}\\python.exe";
            if ($userProfile) $candidates[] = "{$userProfile}\\AppData\\Local\\Programs\\Python\\{$ver}\\python.exe";
        }

        foreach ($candidates as $cmd) {
            if (str_contains($cmd, '\\') && !file_exists($cmd)) continue;
            $out = $this->runCommand([$cmd, '--version']);
            if ($out && str_contains($out, 'Python 3')) return $cmd;
        }
        return null;
    }

    private function runCommand(array $args): ?string
    {
        $desc    = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
        $process = @proc_open($args, $desc, $pipes);
        if (!is_resource($process)) return null;
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return ($out ?? '') . ($err ?? '');
    }

    private function runPythonScript(string $python, string $scriptPath, string $filePath, string $originalName, string $targetShift = 'AUTO'): ?string
    {
        return $this->runCommand([$python, $scriptPath, $filePath, $originalName, $targetShift]);
    }

    public function saveSectHeadPpc(Request $request)
    {
        $request->validate([
            'upload_date' => 'required',
            'shift_name'  => 'required',
            'press_name'  => 'required',
            'value'       => 'required|in:Alberta P. S.,Alvyn',
        ]);

        \App\Models\ScheduleStamping::where('upload_date', $request->upload_date)
            ->where('shift_name', $request->shift_name)
            ->where('press_name', $request->press_name)
            ->update(['sect_head_ppc' => $request->value]);

        return response()->json(['success' => true]);
    }

}