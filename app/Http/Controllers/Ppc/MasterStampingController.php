<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Models\MasterStamping;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterStampingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $shift = $request->get('shift', ''); // 'pagi' or 'malam'
        $perPage = 50;

        $query = MasterStamping::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                  ->orWhere('job_master', 'like', "%{$search}%")
                  ->orWhere('part_no', 'like', "%{$search}%")
                  ->orWhere('part_name', 'like', "%{$search}%")
                  ->orWhere('customer', 'like', "%{$search}%");
            });
        }

        if ($shift === 'pagi') {
            $query->where('is_shift_pagi', true);
        } elseif ($shift === 'malam') {
            $query->where('is_shift_malam', true);
        }

        $items = $query->orderBy('job_no', 'asc')->paginate($perPage)->appends($request->query());
        
        // Count total records in DB
        $totalDb = MasterStamping::count();

        return view('master_stamping', compact('items', 'search', 'totalDb', 'shift'));
    }

    public function import(Request $request)
    {
        $filePath = null;

        if ($request->hasFile('excel_file')) {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls,xlsm,vnd.openxmlformats-officedocument.spreadsheetml.sheet,vnd.ms-excel|max:51200',
            ]);
            $file = $request->file('excel_file');
            $filePath = $file->getRealPath();
        } else {
            $filePath = storage_path('app/uploads/00. Master Schedule Stamping.xlsx');
        }

        if (!$filePath || !file_exists($filePath)) {
            return back()->with('error', 'File Master Schedule Stamping tidak ditemukan.');
        }

        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '300');

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $this->getSheetByNameCaseInsensitive($spreadsheet, 'MASTER');

            if (!$sheet) {
                $availableSheets = $spreadsheet->getSheetNames();
                return back()->with('error', 'Sheet MASTER tidak ditemukan di file Excel. Sheet yang tersedia: ' . implode(', ', $availableSheets));
            }

            // Load Shift Pagi and Shift Malam list sheets (Sheet1 and Sheet2 reference sheets)
            $sheetShift1 = $this->getSheetByNameCaseInsensitive($spreadsheet, 'master untuk shift 1');
            $sheetShift2 = $this->getSheetByNameCaseInsensitive($spreadsheet, 'master untuk shift 2');

            $shift1Jobs = [];
            if ($sheetShift1) {
                $maxRowS1 = $sheetShift1->getHighestRow();
                for ($row = 7; $row <= $maxRowS1; $row++) {
                    $job = $this->getCellValue($sheetShift1, 6, $row); // Col F (JOB NO UNTUK PO)
                    if ($job) {
                        $shift1Jobs[strtoupper($job)] = true;
                    }
                    $jobMaster = $this->getCellValue($sheetShift1, 5, $row); // Col E (JOB MASTER SCHEDULE)
                    if ($jobMaster) {
                        $shift1Jobs[strtoupper($jobMaster)] = true;
                    }
                }
            }

            $shift2Jobs = [];
            if ($sheetShift2) {
                $maxRowS2 = $sheetShift2->getHighestRow();
                for ($row = 7; $row <= $maxRowS2; $row++) {
                    $job = $this->getCellValue($sheetShift2, 6, $row); // Col F (JOB NO UNTUK PO)
                    if ($job) {
                        $shift2Jobs[strtoupper($job)] = true;
                    }
                    $jobMaster = $this->getCellValue($sheetShift2, 5, $row); // Col E (JOB MASTER SCHEDULE)
                    if ($jobMaster) {
                        $shift2Jobs[strtoupper($jobMaster)] = true;
                    }
                }
            }

            $maxRow = $sheet->getHighestRow();
            $imported = 0;

            // Truncate table first to do a clean reload
            MasterStamping::truncate();

            $batchSize = 100;
            $batchData = [];

            for ($row = 23; $row <= $maxRow; $row++) {
                // Check if row has job_no or job_master, if both are empty we skip
                $jobNo = $this->getCellValue($sheet, 7, $row); // Col G
                $jobMaster = $this->getCellValue($sheet, 8, $row); // Col H

                if (empty($jobNo) && empty($jobMaster)) {
                    continue;
                }

                $prosesLine = $this->getCellValue($sheet, 3, $row); // Col C
                $mach = $this->getCellValue($sheet, 5, $row); // Col E
                $partNo = $this->getCellValue($sheet, 9, $row); // Col I
                $irmNumber = $this->getCellValue($sheet, 10, $row); // Col J
                $partName = $this->getCellValue($sheet, 11, $row); // Col K
                $qtyUnit = $this->getCellValue($sheet, 12, $row); // Col L
                $total = $this->getCellValue($sheet, 15, $row); // Col O
                $typePallet = $this->getCellValue($sheet, 31, $row); // Col AE
                $qtyPallet = $this->getCellValue($sheet, 33, $row); // Col AG
                $ctDetik = $this->getCellValue($sheet, 43, $row); // Col AQ
                $dct = $this->getCellValue($sheet, 44, $row); // Col AR
                $regActive = $this->getCellValue($sheet, 45, $row); // Col AS
                $mct = $this->getCellValue($sheet, 47, $row); // Col AU
                $tpt = $this->getCellValue($sheet, 84, $row); // Col CF
                $customer = $this->getCellValue($sheet, 78, $row); // Col BZ
                $remarks = $this->getCellValue($sheet, 83, $row); // Col CE

                $inShiftPagi = isset($shift1Jobs[strtoupper($jobNo ?? '')]) || isset($shift1Jobs[strtoupper($jobMaster ?? '')]);
                $inShiftMalam = isset($shift2Jobs[strtoupper($jobNo ?? '')]) || isset($shift2Jobs[strtoupper($jobMaster ?? '')]);

                $baseRow = [
                    'proses_line'    => $prosesLine,
                    'mach'           => $mach,
                    'job_no'         => $jobNo,
                    'job_master'     => $jobMaster,
                    'part_no'        => $partNo,
                    'irm_number'     => $irmNumber,
                    'part_name'      => $partName,
                    'qty_unit'       => $this->safeFloat($qtyUnit),
                    'total'          => $this->safeFloat($total),
                    'type_pallet'    => $typePallet,
                    'qty_pallet'     => $this->safeFloat($qtyPallet),
                    'ct_detik'       => $this->safeFloat($ctDetik),
                    'dct'            => $this->safeFloat($dct),
                    'reg_active'     => $this->safeFloat($regActive),
                    'mct'            => $this->safeFloat($mct),
                    'tpt'            => $this->safeFloat($tpt),
                    'customer'       => $customer,
                    'remarks'        => $remarks,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                if ($inShiftPagi && $inShiftMalam) {
                    $batchData[] = array_merge($baseRow, [
                        'is_shift_pagi'  => true,
                        'is_shift_malam' => false,
                    ]);
                    $imported++;

                    $batchData[] = array_merge($baseRow, [
                        'is_shift_pagi'  => false,
                        'is_shift_malam' => true,
                    ]);
                    $imported++;
                } elseif ($inShiftPagi) {
                    $batchData[] = array_merge($baseRow, [
                        'is_shift_pagi'  => true,
                        'is_shift_malam' => false,
                    ]);
                    $imported++;
                } elseif ($inShiftMalam) {
                    $batchData[] = array_merge($baseRow, [
                        'is_shift_pagi'  => false,
                        'is_shift_malam' => true,
                    ]);
                    $imported++;
                } else {
                    $batchData[] = array_merge($baseRow, [
                        'is_shift_pagi'  => false,
                        'is_shift_malam' => false,
                    ]);
                    $imported++;
                }

                if (count($batchData) >= $batchSize) {
                    MasterStamping::insert($batchData);
                    $batchData = [];
                }
            }

            if (count($batchData) > 0) {
                MasterStamping::insert($batchData);
            }

            return back()->with('success', "Berhasil mengimport {$imported} data master stamping dari file Excel.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    public function searchAjax(Request $request)
    {
        $search = $request->get('q', '');
        $shift = $request->get('shift', ''); // can be 'pagi' or 'malam'
        if (!$search) {
            return response()->json([]);
        }

        $query = MasterStamping::query()
            ->where(function($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                  ->orWhere('job_master', 'like', "%{$search}%");
            });

        if ($shift === 'pagi') {
            $query->where('is_shift_pagi', true);
        } elseif ($shift === 'malam') {
            $query->where('is_shift_malam', true);
        }

        $items = $query->limit(20)->get();

        return response()->json($items);
    }

    private function getCellValue($sheet, $col, $row)
    {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
        if (!$cell) return null;

        $val = $cell->getValue();
        if (is_string($val) && strpos($val, '=') === 0) {
            try {
                $val = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                // fallback to formula or null
            }
        }
        return $val !== null ? trim((string)$val) : null;
    }

    private function safeFloat($val)
    {
        if ($val === null || $val === '') return null;
        $val = str_replace(',', '.', $val);
        return is_numeric($val) ? floatval($val) : null;
    }

    private function getSheetByNameCaseInsensitive($spreadsheet, $name)
    {
        $sheetNames = $spreadsheet->getSheetNames();
        $lowerName = strtolower($name);
        foreach ($sheetNames as $sheetName) {
            if (strtolower($sheetName) === $lowerName) {
                return $spreadsheet->getSheetByName($sheetName);
            }
        }
        return null;
    }
}
