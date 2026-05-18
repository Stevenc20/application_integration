<?php

namespace App\Http\Controllers;

use App\Models\ScheduleStamping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleStampingController extends Controller
{
    // ── INDEX ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $allDates  = ScheduleStamping::select('upload_date')->distinct()->orderBy('upload_date', 'desc')->pluck('upload_date');
        $hasData   = $allDates->isNotEmpty();

        // Selected filters
        $selectedDate = $request->get('date');
        if (!$selectedDate && $hasData) {
            $selectedDate = $allDates->first();
        }

        // Available shifts for this date
        $allShifts = collect();
        if ($selectedDate) {
            $allShifts = ScheduleStamping::where('upload_date', $selectedDate)
                ->whereNotNull('shift_name')
                ->select('shift_name')
                ->distinct()
                ->orderBy('shift_name')
                ->pluck('shift_name');
        }
        $selectedShift = $request->get('shift');
        if ($selectedShift && !$allShifts->contains($selectedShift)) {
            $selectedShift = $allShifts->first();
        } elseif (!$selectedShift && $allShifts->isNotEmpty()) {
            $selectedShift = $allShifts->first();
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
        $selectedPress = $request->get('press');
        if ($selectedPress && !$allPress->contains($selectedPress)) {
            $selectedPress = $allPress->first();
        } elseif (!$selectedPress && $allPress->isNotEmpty()) {
            $selectedPress = $allPress->first();
        }

        $search = $request->get('search', '');

        $items = collect();
        $metaInfo = null;

        if ($hasData && $selectedDate) {
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

            // [FIX] Force update standard breaks to 12:40 and recalculate
            if ($selectedShift && $selectedPress) {
                $this->ensureStandardBreaks($selectedDate, $selectedShift, $selectedPress);
                
                $firstItem = ScheduleStamping::where('upload_date', $selectedDate)
                    ->where('shift_name', $selectedShift)
                    ->where('press_name', $selectedPress)
                    ->orderBy('id')
                    ->first();
                if ($firstItem) {
                    $this->recalculateAndCascade($firstItem);
                }
            }

            $items = $query->orderByRaw('start_time IS NULL, start_time ASC')->orderBy('id')->get();

            // Get meta info for selected
            $metaInfo = ScheduleStamping::where('upload_date', $selectedDate)
                ->where('shift_name', $selectedShift)
                ->where('press_name', $selectedPress)
                ->whereNotNull('press_name')
                ->first();
        }

        // Stats
        $jobRows   = $items ?? collect();
        if ($jobRows instanceof \Illuminate\Database\Eloquent\Collection || $jobRows instanceof \Illuminate\Support\Collection) {
            $jobRows = $jobRows->where('row_type', 'job');
        }
        $totalPlan = $jobRows->sum('plan');
        $totalPcs  = $jobRows->sum('total_pcs');
        $totalJobs = $jobRows->count();

        return view('schedule_stamping', compact(
            'hasData', 'allDates', 'allShifts', 'allPress',
            'selectedDate', 'selectedShift', 'selectedPress',
            'search', 'items', 'metaInfo',
            'totalPlan', 'totalPcs', 'totalJobs'
        ));
    }

    public function export(Request $request)
    {
        $date  = $request->get('date');
        $shift = $request->get('shift');
        $press = $request->get('press');
        $search = $request->get('search');

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

        $items = $query->orderByRaw('start_time IS NULL, start_time ASC')->orderBy('id')->get();

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

        // Style header
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
                
                // Style A-1 to A-4 as grey
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

        // Auto size columns
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Schedule_Stamping_{$date}_{$shift}_{$press}.xlsx";
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

            $scriptPath = base_path('read_schedule_stamping.py');
            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('error', 'Script read_schedule_stamping.py tidak ditemukan di root project.');
            }

            $output = $this->runPythonScript($python, $scriptPath, $dataPath, $originalName);

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
                    $shiftName = $sheetData['shift_name'] ?? explode('|||', $sectionKey)[0];
                    $pressName = $pressOverride ?: ($sheetData['press_name'] ?? null);

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
                    foreach ($sheetData['rows'] as $item) {
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

            // After upload, trigger a recalculation for each section to ensure breaks are respected
            $sections = ScheduleStamping::where('upload_date', $uploadDate)
                ->select('shift_name', 'press_name')
                ->distinct()
                ->get();
                
            foreach ($sections as $sec) {
                // Ensure standard breaks exist for each section
                $this->ensureStandardBreaks($uploadDate, $sec->shift_name, $sec->press_name);

                $firstItem = ScheduleStamping::where('upload_date', $uploadDate)
                    ->where('shift_name', $sec->shift_name)
                    ->where('press_name', $sec->press_name)
                    ->orderBy('id')
                    ->first();
                if ($firstItem) {
                    $this->recalculateAndCascade($firstItem);
                }
            }

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
        $calcFields = ['plan', 'qty_plt', 'ct_detik', 'dct', 'reg_active', 'total_mesin', 'ok', 'repair', 'reject', 'start_time'];

        $item->$field = $value;
        $item->save();

        if (in_array($field, $calcFields)) {
            $firstItem = ScheduleStamping::where('upload_date', $item->upload_date)
                ->where('shift_name', $item->shift_name)
                ->where('press_name', $item->press_name)
                ->orderBy('id', 'asc')
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

    private function recalculateAndCascade(ScheduleStamping $startItem)
    {
        $fixedBreaks = $this->getFixedBreaks();

        // Fetch ALL items for this section in order of start time to interleave breaks correctly
        $allItems = ScheduleStamping::where('upload_date', $startItem->upload_date)
            ->where('shift_name', $startItem->shift_name)
            ->where('press_name', $startItem->press_name)
            ->orderByRaw('start_time IS NULL, start_time ASC')
            ->orderBy('id', 'asc')
            ->get();

        $prevFinish = null;
        $foundStart = false;

        foreach ($allItems as $item) {
            // Check if this is the start item or after it
            if ($item->id == $startItem->id) {
                $foundStart = true;
            }

            if (!$foundStart) {
                $prevFinish = $item->finish_time;
                continue;
            }

            if ($item->row_type === 'break') {
                // Break rows are for display and don't push the next job's start time.
                // Jobs will naturally jump over breaks using getFixedBreaks() in calculateRow().
                continue;
            }

            // It's a job row. Determine its start time.
            if ($prevFinish) {
                // If the previous job/break finished, this job starts there, 
                // but jump if it starts inside a break.
                $item->start_time = $this->pushIfInBreak($prevFinish, $fixedBreaks);
            }

            // Recalculate TPT, Finish Time, etc.
            $this->calculateRow($item);

            // Special logic: if this job overlaps a break and is continued in the next row, cap it.
            if ($item->row_type === 'job') {
                $overlap = $this->getBreakOverlap($item->start_time, $item->tpt);
                if ($overlap) {
                    $nextJob = $this->getNextJob($allItems, $item);
                    if ($nextJob && $nextJob->job_no === $item->job_no) {
                        // Cap at break start
                        $item->finish_time = $overlap['break_start'];
                    }
                }
            }

            $item->save();
            $prevFinish = $item->finish_time;
        }
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

        // Trigger recalculation from the first item to ensure jobs jump over new breaks
        $firstItem = ScheduleStamping::where('upload_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->orderBy('id')
            ->first();
        if ($firstItem) {
            $this->recalculateAndCascade($firstItem);
        }

        return back()->with('success', 'Waktu istirahat standar berhasil ditambahkan.');
    }

    private function ensureStandardBreaks($date, $shift, $press)
    {
        $breaks = [
            ['name' => 'ISTIRAHAT SIANG', 'start' => '12:00', 'finish' => '12:40', 'tpt' => 40],
            ['name' => 'BREAKTIME',       'start' => '15:15', 'finish' => '15:30', 'tpt' => 15],
            ['name' => 'ISTIRAHAT SORE',  'start' => '18:00', 'finish' => '18:30', 'tpt' => 30],
        ];

        foreach ($breaks as $b) {
            $existing = ScheduleStamping::where('upload_date', $date)
                ->where('shift_name', $shift)
                ->where('press_name', $press)
                ->where('job_no', $b['name'])
                ->first();

            if (!$existing) {
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
            } else {
                // Update if any timing or TPT is different
                if ($existing->start_time !== $b['start'] || 
                    $existing->finish_time !== $b['finish'] || 
                    $existing->dct != $b['tpt']) {
                    
                    $existing->update([
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
        }
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
        if ($item->start_time && $item->tpt > 0) {
            $item->finish_time = $this->calculateFinishWithBreaks($item->start_time, (int)$item->tpt);
        }

        // A-1 to A-4 (Allocation based on TPT)
        $item->a1 = (int)$item->tpt;
        $item->a4 = (int)$item->tpt;
        if ($mesin >= 4) {
            $item->a2 = (int)$item->tpt;
            $item->a3 = (int)$item->tpt;
        } else {
            $item->a2 = 0; $item->a3 = 0;
        }
    }

    private function getFixedBreaks()
    {
        return [
            ['start' => '12:00', 'finish' => '12:40'],
            ['start' => '15:15', 'finish' => '15:30'],
            ['start' => '18:00', 'finish' => '18:30'],
        ];
    }

    private function pushIfInBreak($timeStr, $fixedBreaks = null)
    {
        if (!$timeStr || $timeStr === '-') return $timeStr;
        if (!$fixedBreaks) $fixedBreaks = $this->getFixedBreaks();
        
        $mins = $this->timeToMinutes($timeStr);
        foreach ($fixedBreaks as $b) {
            $bStart = $this->timeToMinutes($b['start']);
            $bEnd   = $this->timeToMinutes($b['finish']);
            if ($mins >= $bStart && $mins < $bEnd) {
                return $b['finish'];
            }
        }
        return $timeStr;
    }

    private function calculateFinishWithBreaks($startTime, $duration)
    {
        $currentTime = $this->timeToMinutes($startTime);
        $remaining = (int)$duration;
        $fixedBreaks = $this->getFixedBreaks();

        // Ensure breaks are sorted by start time
        usort($fixedBreaks, function($a, $b) {
            return $this->timeToMinutes($a['start']) <=> $this->timeToMinutes($b['start']);
        });

        while ($remaining > 0) {
            $finishWithoutInterruption = $currentTime + $remaining;
            $foundBreak = false;

            foreach ($fixedBreaks as $b) {
                $bStart = $this->timeToMinutes($b['start']);
                $bEnd   = $this->timeToMinutes($b['finish']);

                // Case 1: Currently inside a break (should be handled by pushIfInBreak, but safety first)
                if ($currentTime >= $bStart && $currentTime < $bEnd) {
                    $currentTime = $bEnd;
                    $foundBreak = true;
                    break;
                }

                // Case 2: Job starts before break and crosses the start of the break
                if ($currentTime < $bStart && $finishWithoutInterruption > $bStart) {
                    $workBeforeBreak = $bStart - $currentTime;
                    $remaining -= $workBeforeBreak;
                    $currentTime = $bEnd; // Jump to end of break
                    $foundBreak = true;
                    break;
                }
            }

            if (!$foundBreak) {
                $currentTime += $remaining;
                $remaining = 0;
            }
        }

        return $this->minutesToTime($currentTime);
    }

    private function timeToMinutes($timeStr)
    {
        if (!$timeStr || $timeStr === '-') return 0;
        $parts = explode(':', $timeStr);
        if (count($parts) < 2) return 0;
        return (int)$parts[0] * 60 + (int)$parts[1];
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

    private function getBreakOverlap($startTime, $tpt)
    {
        if (!$startTime || $tpt <= 0) return null;
        $startMins = $this->timeToMinutes($startTime);
        $finishMins = $startMins + $tpt;
        $fixedBreaks = $this->getFixedBreaks();
        
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

    private function runPythonScript(string $python, string $scriptPath, string $filePath, string $originalName): ?string
    {
        return $this->runCommand([$python, $scriptPath, $filePath, $originalName]);
    }
}
