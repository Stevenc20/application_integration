<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LineMaster;
use App\Models\ProductionPlan;

$python = 'C:\\Users\\StevC\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe';
$script = base_path('scripts/read_schedule_stamping.py');
$filePath = base_path('referensi/07. Schedule Stamping 08 Mei 2026.xlsx');
$originalName = '07. Schedule Stamping 08 Mei 2026.xlsx';
$parsedDate = '2026-05-08';

echo "Executing Python Script...\n";
$cmd = [ $python, $script, $filePath, $originalName ];
$process = proc_open($cmd, [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
if (!is_resource($process)) {
    die("Failed to start process.\n");
}

$out = stream_get_contents($pipes[1]);
$err = stream_get_contents($pipes[2]);
proc_close($process);

if ($err) {
    echo "Python STDERR:\n$err\n";
}

$result = json_decode($out, true);
if (!$result || !isset($result['success']) || !$result['success']) {
    die("Error parsing Excel via Python: " . json_encode($result) . "\n");
}

echo "Python Success! Total Sheets Found: {$result['total_sheets']}\n";

$imported = 0;
try {
    \DB::transaction(function () use ($result, $parsedDate, &$imported) {
        $lineMap = LineMaster::pluck('id', 'line_name')->toArray();
        $sheets = array_values($result['sheets'] ?? []);

        foreach ($sheets as $sheetData) {
            $rows = [];
            $shiftName = $sheetData['shift_name'];
            $pressName = $sheetData['press_name'];

            $shiftPrefix = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $shiftName);
            $shiftPrefix = trim($shiftPrefix);

            ProductionPlan::whereDate('plan_date', $parsedDate)
                ->where('press_name', $pressName)
                ->where('shift_name', 'like', $shiftPrefix . '%')
                ->delete();

            $lineId = null;
            $pressKey = strtoupper(str_replace([' ', '-'], '', $pressName));
            foreach ($lineMap as $name => $id) {
                $cleanName = strtoupper(str_replace([' ', '-', 'LINE'], '', $name));
                if ($cleanName === $pressKey || str_contains($pressKey, $cleanName) || str_contains($cleanName, $pressKey)) {
                    $lineId = $id;
                    break;
                }
            }
            if (!$lineId) $lineId = array_values($lineMap)[0] ?? 1;

            foreach ($sheetData['rows'] as $item) {
                $rowType = $item['row_type'] ?? 'job';
                $jn = strtoupper($item['job_no'] ?? '');
                $jm = strtoupper($item['job_master'] ?? '');
                $isBreakDesc = false;
                $breakKeywords = ['ISTIRAHAT', 'JUMAT', 'SORE', 'MALAM', 'CINGKORAK', 'BREAK', 'TOTAL FINISH', 'TOTAL FNISH', 'BREAKTI', 'FINISH'];
                foreach ($breakKeywords as $kw) {
                    if (str_contains($jn, $kw) || str_contains($jm, $kw)) {
                        $isBreakDesc = true;
                        break;
                    }
                }
                if ($isBreakDesc || $rowType === 'break') $rowType = 'break';

                $jm = $item['job_master'] ?? '';
                $jn = $item['job_no'] ?? '';
                
                if (str_contains(strtoupper($jm), 'FINISH') || str_contains(strtoupper($jn), 'FINISH') || 
                    str_contains(strtoupper($jm), 'FNISH') || str_contains(strtoupper($jn), 'FNISH')) {
                    $jm = 'TOTAL FINISH';
                    $jn = 'TOTAL FINISH';
                }

                $rows[] = [
                    'line_master_id' => $lineId,
                    'plan_date'      => $parsedDate,
                    'shift_name'     => $shiftName,
                    'press_name'     => $pressName,
                    'hari'           => safeVal($sheetData['hari']),
                    'tgl'            => safeVal($sheetData['tgl']),
                    'jam'            => safeVal($sheetData['jam']),
                    'revisi'         => safeVal($sheetData['revisi']),
                    'row_no'         => safeVal($item['row_no']),
                    'row_type'       => $rowType,
                    'job_master'     => safeVal($jm),
                    'type_plt'       => safeVal($item['type_plt']),
                    'qty_plt'        => safeVal($item['qty_plt'], 0),
                    'keb_mtl'        => safeVal($item['keb_mtl'], 0),
                    'total_plt'      => safeVal($item['total_plt'], 0),
                    'job_no'         => safeVal($jn),
                    'each_part'      => safeVal($item['each_part']),
                    'plan'           => safeVal($item['plan'], 0),
                    'ok'             => safeVal($item['ok'], 0),
                    'repair'         => safeVal($item['repair'], 0),
                    'reject'         => safeVal($item['reject'], 0),
                    'total_mesin'    => safeVal($item['total_mesin'], 1),
                    'ct_detik'       => safeVal($item['ct_detik'], 0),
                    'process_time'   => safeVal($item['process_time'], 0),
                    'reg_active'     => safeVal($item['reg_active'], 0),
                    'dct'            => safeVal($item['dct'], 0),
                    'mct'            => safeVal($item['mct'], 0),
                    'plan_dct'       => safeVal($item['plan_dct'], 0),
                    'tpt'            => safeVal($item['tpt'], 0),
                    'gsph_item'      => safeVal($item['gsph_item'], 0),
                    'start_time'     => safeVal($item['start_time']),
                    'finish_time'    => safeVal($item['finish_time']),
                    'act_start'      => safeVal($item['act_start']),
                    'act_finish'     => safeVal($item['act_finish']),
                    'keterangan'     => safeVal($item['keterangan']) ?: ($item['row_type'] === 'break' ? safeVal($item['job_no']) : null),
                    'a1'             => safeVal($item['a1'], 0),
                    'a2'             => safeVal($item['a2'], 0),
                    'a3'             => safeVal($item['a3'], 0),
                    'a4'             => safeVal($item['a4'], 0),
                    'dt_menit'       => safeVal($item['dt_menit'], 0),
                    'total_pcs'      => safeVal($item['total_pcs'], 0),
                    'tpt_total'      => safeVal($item['tpt_total'], 0),
                    'status'         => 'pending',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }

            if (!empty($rows)) {
                $chunks = array_chunk($rows, 100);
                foreach ($chunks as $chunk) {
                    ProductionPlan::insert($chunk);
                    $imported += count($chunk);
                }
            }
        }
    });
    echo "SUCCESSFULLY IMPORTED {$imported} ROWS INTO DATABASE!\n";
} catch (\Exception $e) {
    echo "ERROR RUNNING TRANSACTION:\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

function safeVal($v, $default = null) {
    if ($v === null || $v === '' || $v === '?') return $default;
    return $v;
}
