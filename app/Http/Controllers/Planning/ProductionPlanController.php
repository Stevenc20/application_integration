<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\LineMaster;
use App\Models\JobMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    public function index(Request $request)
    {
        // 1. DATA CONTEXT: Cari data terbaru hanya untuk default awal
        $maxDateRecord = ProductionPlan::orderBy('plan_date', 'desc')->first();
        $maxDate       = $maxDateRecord ? $maxDateRecord->plan_date->toDateString() : now()->toDateString();
        
        $latestImport = ProductionPlan::orderBy('created_at', 'desc')->first();
        $lastImportAt = $latestImport ? $latestImport->created_at->format('d M Y H:i') : '—';

        // 2. SELECTED DATE: 
        // Hanya auto-seek ke maxDate jika parameter 'date' BENAR-BENAR KOSONG di URL.
        // Jika ada di URL, biarkan user melihat apa yang mereka minta (biarpun kosong).
        $rawDate = $request->get('date');
        $date    = $rawDate ?: $maxDate;

        $currentPress = strtoupper($request->get('press', 'PRESS A'));
        $currentShift = $request->get('shift', 'Pagi'); 

        // 3. GET DATA CONTEXT
        $allAvailableForDate = ProductionPlan::whereDate('plan_date', $date)->get();
        $hasDataOnDate       = $allAvailableForDate->count() > 0;

        // Smart Fallback Press & Shift (Hanya bantu pilihkan tab yang ADA datanya di tanggal terpilih)
        if ($hasDataOnDate) {
            $existingPresses = $allAvailableForDate->pluck('press_name')->unique()->toArray();
            $matchFound      = false;
            foreach($existingPresses as $ep) {
                if (stripos($ep, $currentPress) !== false) {
                    $currentPress = $ep; 
                    $matchFound   = true;
                    break;
                }
            }
            if (!$matchFound && $currentPress !== 'ALL') {
                $currentPress = $existingPresses[0];
            }
            
            // Re-fetch shifts for the new currentPress
            $availableShifts = $allAvailableForDate
                ->when($currentPress !== 'ALL', fn($q) => $q->where('press_name', $currentPress))
                ->pluck('shift_name')
                ->unique()
                ->toArray();

            // Smart Shift Detection
            if (!in_array($currentShift, $availableShifts) && count($availableShifts) > 0) {
                foreach ($availableShifts as $s) {
                    if (stripos($s, $currentShift) !== false) {
                        $currentShift = $s;
                        break;
                    }
                }
                if (!in_array($currentShift, $availableShifts)) $currentShift = $availableShifts[0];
            }
        } else {
            $availableShifts = [];
        }

        // 4. MAIN QUERY: Strict matching
        $query = ProductionPlan::with('line')
            ->whereDate('plan_date', $date);

        if ($currentPress !== 'ALL') $query->where('press_name', $currentPress);
        if ($currentShift !== 'ALL') $query->where('shift_name', $currentShift);

        // Filter status
        if ($request->filled('status') && $request->status !== '') {
            $query->where(DB::raw('LOWER(status)'), strtolower($request->status));
        }

        // 5. PACKAGING METADATA
        $activeFilters = [
            'date'        => $date,
            'press'       => $currentPress,
            'shift'       => $currentShift,
            'has_data'    => $hasDataOnDate,
            'max_date'    => $maxDate,
            'last_import' => $lastImportAt
        ];

        // 1. Get ALL rows for calculations (Full set)
        // PRIORITY: RUNNING first, then by row_no
        $allPlans = $query->orderByRaw("CASE WHEN LOWER(status) = 'running' THEN 0 ELSE 1 END")
            ->orderBy('row_no', 'asc')
            ->get();

        // 2. Paginate rows for display to prevent DOM lag (100 rows per page)
        $plans = $query->orderByRaw("CASE WHEN LOWER(status) = 'running' THEN 0 ELSE 1 END")
            ->orderBy('row_no', 'asc')
            ->where(function($q) {
                $q->where('row_type', '!=', 'total_finish')
                  ->where('job_master', 'NOT LIKE', '%TOTAL FINISH%')
                  ->where('job_no', 'NOT LIKE', '%TOTAL FINISH%');
            })
            ->paginate(100)
            ->withQueryString();

        // Find special row: TOTAL FINISH (from all data)
        $totalFinishRow = $allPlans->filter(function($row) {
            $jm = strtoupper($row->job_master ?? '');
            $jn = strtoupper($row->job_no ?? '');
            $combined = $jm . ' ' . $jn;
            return ($row->row_type === 'total_finish') || str_contains($combined, 'TOTAL FINISH') || str_contains($combined, 'TOTAL FNISH') || str_contains($combined, 'FINISH');
        })->first();

        // FALLBACK: Generate automatic summary if explicit row is missing
        if (!$totalFinishRow && count($allPlans) > 0) {
            $jobPlans = $allPlans->where('row_type', 'job');
            
            $totalFinishRow = (object)[
                'id'          => 0,
                'plan'        => $jobPlans->sum('plan'),
                'ok'          => $jobPlans->sum('ok'),
                'repair'      => $jobPlans->sum('repair'),
                'reject'      => $jobPlans->sum('reject'),
                'total_mesin' => $jobPlans->sum('total_mesin'),
                'ct_detik'    => round($jobPlans->where('ct_detik', '>', 0)->avg('ct_detik'), 1),
                'tpt'         => $jobPlans->sum('tpt'),
                'process_time' => round($jobPlans->sum('process_time'), 1),
                'reg_active'  => $jobPlans->sum('reg_active'),
                'dct'         => $jobPlans->sum('dct'),
                'mct'         => $jobPlans->sum('mct'),
                'plan_dct'    => $jobPlans->sum('plan_dct'),
                'gsph_item'   => $jobPlans->sum('gsph_item'),
                'start_time'  => $allPlans->first()->start_time ?? '—',
                'finish_time' => $allPlans->last()->finish_time ?? '—',
                'row_type'    => 'total_finish',
                'a1'          => $jobPlans->sum('a1'),
                'a2'          => $jobPlans->sum('a2'),
                'a3'          => $jobPlans->sum('a3'),
                'a4'          => $jobPlans->sum('a4'),
                'dt_menit'    => $jobPlans->sum('dt_menit'),
                'job_master'  => 'TOTAL FINISH',
                'job_no'      => 'TOTAL FINISH',
                'keterangan'  => ''
            ];
        }

        // Card summaries from all data
        $cardSummaries = $allPlans->filter(function($row) use ($totalFinishRow) {
            if ($totalFinishRow && $row->id === $totalFinishRow->id) return false;
            $combined = strtoupper(($row->job_master ?? '') . ' ' . ($row->job_no ?? ''));
            return str_contains($combined, 'PLAN') || str_contains($combined, 'STROKE') || 
                   str_contains($combined, 'TPT') || str_contains($combined, 'GSPH') || 
                   str_contains($combined, 'TOTAL PCS');
        });

        $totalJobs = $allPlans->where('row_type', 'job')
            ->filter(function($p) {
                return (!empty($p->job_no) && $p->job_no !== '—') || (!empty($p->job_master) && $p->job_master !== '—');
            })
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
            ->count();

        $lines = LineMaster::where('status', 'active')->orderBy('line_name')->get();

        return view(
            'ppc.planning.production_plan',
            compact('plans', 'lines', 'date', 'currentPress', 'totalFinishRow', 'cardSummaries', 'currentShift', 'availableShifts', 'totalJobs', 'activeFilters')
        );
    }

    public function import(Request $request)
    {
        \Log::info("--- PRODUCTION PLAN IMPORT (PYTHON ENGINE) STARTED ---");
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,xlsm|max:51200',
        ]);

        try {
            $file         = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();
            $extension    = strtolower($file->getClientOriginalExtension());
            $uploadDir    = storage_path('app/uploads');
            $dataPath     = $uploadDir . '/prod_plan_temp.' . $extension;

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $file->move($uploadDir, 'prod_plan_temp.' . $extension);

            $python = $this->findPython();
            if (!$python) {
                @unlink($dataPath);
                return back()->with('error', 'Python tidak ditemukan di server.');
            }

            $scriptPath = base_path('scripts/read_schedule_stamping.py');
            if (!file_exists($scriptPath)) {
                @unlink($dataPath);
                return back()->with('error', 'Script Python tidak ditemukan.');
            }

            $output = $this->runPythonScript($python, $scriptPath, $dataPath, $originalName);
            
            if (!$output) {
                @unlink($dataPath);
                return back()->with('error', 'Gagal mendapatkan output dari engine Python.');
            }

            // Clean output from any potential leading/trailing whitespace or debug text
            $output = trim($output);
            $jsonStart = strpos($output, '{');
            if ($jsonStart !== false) {
                $output = substr($output, $jsonStart);
            }

            $result = json_decode($output, true);
            
            if (!$result || isset($result['error'])) {
                \Log::error("Python Result Error: " . ($output ?: 'Empty output'));
                @unlink($dataPath);
                return back()->with('error', 'Error Parsing: ' . ($result['error'] ?? 'Format JSON tidak valid atau script crash.'));
            }

            // PRIORITIZE MANUALLY SELECTED DATE FROM MODAL
            $manualDate = $request->get('date');
            if ($manualDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $manualDate)) {
                $parsedDate = $manualDate;
            } else {
                // Fallback to python extracted date
                $uploadDateStr = $result['upload_date'];
                $parsedDate = $this->parseIndoDate($uploadDateStr)->format('Y-m-d');
            }
            
            $imported = 0;
            \DB::transaction(function () use ($result, $parsedDate, &$imported) {
                $lineMap = LineMaster::pluck('id', 'line_name')->toArray();

                // NORMALIZE associative sheets to indexed array
                $sheets = array_values($result['sheets'] ?? []);

                foreach ($sheets as $sheetData) {
                    $rows = [];
                    $shiftName = $sheetData['shift_name'];
                    $pressName = $sheetData['press_name'];
                    
                    // SMART CLEANUP: Hanya hapus data untuk Line & Shift yang sedang diimport ini
                    // Gunakan prefix (e.g. "SHIFT PAGI") agar "SHIFT PAGI REV-001" juga terhapus
                    $shiftPrefix = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $shiftName);
                    $shiftPrefix = trim($shiftPrefix);

                    ProductionPlan::whereDate('plan_date', $parsedDate)
                        ->where('press_name', $pressName)
                        ->where('shift_name', 'like', $shiftPrefix . '%')
                        ->delete();
                    
                    // Fuzzy match line_master_id
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
                        
                        // Normalization: Ensure 'TOTAL FINISH' is standard
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
                            'hari'           => $this->safeVal($sheetData['hari']),
                            'tgl'            => $this->safeVal($sheetData['tgl']),
                            'jam'            => $this->safeVal($sheetData['jam']),
                            'revisi'         => $this->safeVal($sheetData['revisi']),
                            'row_no'         => $this->safeVal($item['row_no']),
                            'row_type'       => $rowType,
                            'job_master'     => $this->safeVal($jm),
                            'type_plt'       => $this->safeVal($item['type_plt']),
                            'qty_plt'        => $this->safeVal($item['qty_plt'], 0),
                            'keb_mtl'        => $this->safeVal($item['keb_mtl'], 0),
                            'total_plt'      => $this->safeVal($item['total_plt'], 0),
                            'job_no'         => $this->safeVal($jn),
                            'each_part'      => $this->safeVal($item['each_part']),
                            'plan'           => $this->safeVal($item['plan'], 0),
                            'ok'             => $this->safeVal($item['ok'], 0),
                            'repair'         => $this->safeVal($item['repair'], 0),
                            'reject'         => $this->safeVal($item['reject'], 0),
                            'total_mesin'    => $this->safeVal($item['total_mesin'], 1),
                            'ct_detik'       => $this->safeVal($item['ct_detik'], 0),
                            'process_time'   => $this->safeVal($item['process_time'], 0),
                            'reg_active'     => $this->safeVal($item['reg_active'], 0),
                            'dct'            => $this->safeVal($item['dct'], 0),
                            'mct'            => $this->safeVal($item['mct'], 0),
                            'plan_dct'       => $this->safeVal($item['plan_dct'], 0),
                            'tpt'            => $this->safeVal($item['tpt'], 0),
                            'gsph_item'      => $this->safeVal($item['gsph_item'], 0),
                            'start_time'     => $this->safeVal($item['start_time']),
                            'finish_time'    => $this->safeVal($item['finish_time']),
                            'act_start'      => $this->safeVal($item['act_start']),
                            'act_finish'     => $this->safeVal($item['act_finish']),
                            'keterangan'     => $this->safeVal($item['keterangan']) ?: ($item['row_type'] === 'break' ? $this->safeVal($item['job_no']) : null),
                            'a1'             => $this->safeVal($item['a1'], 0),
                            'a2'             => $this->safeVal($item['a2'], 0),
                            'a3'             => $this->safeVal($item['a3'], 0),
                            'a4'             => $this->safeVal($item['a4'], 0),
                            'dt_menit'       => $this->safeVal($item['dt_menit'], 0),
                            'total_pcs'      => $this->safeVal($item['total_pcs'], 0),
                            'tpt_total'      => $this->safeVal($item['tpt_total'], 0),
                            'status'         => 'pending',
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ];
                    }

                    foreach (array_chunk($rows, 100) as $chunk) {
                        ProductionPlan::insert($chunk);
                    }
                    $imported += count($rows);
                }
            });

            @unlink($dataPath);
            // Auto-redirect to the first parsed sheet for convenience
            if (!empty($result['sheets'])) {
                $firstKey = array_key_first($result['sheets']);
                $firstSheet = $result['sheets'][$firstKey];
                return redirect()->route('ppc.planning.production_plan', [
                    'date'  => $parsedDate,
                    'press' => $firstSheet['press_name'] ?? null,
                    'shift' => $firstSheet['shift_name'] ?? null,
                ])->with('success', "Import Berhasil! {$imported} data diproses.");
            }

            return back()->with('success', "Import Berhasil! {$imported} data diproses.");

        } catch (\Throwable $e) {
            \Log::error("Python Import Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    private function findPython(): ?string
    {
        $candidates = [
            'C:\\Users\\StevC\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
            'python', 'python3', 'py', 'C:\\Python312\\python.exe', 'C:\\Python311\\python.exe'
        ];
        foreach ($candidates as $cmd) {
            $process = @proc_open([$cmd, '--version'], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
            if (is_resource($process)) {
                $out = stream_get_contents($pipes[1]);
                proc_close($process);
                if (str_contains($out, 'Python 3')) return $cmd;
            }
        }
        return 'python'; 
    }

    private function runPythonScript($python, $script, $file, $orig)
    {
        $cmd = [ $python, $script, $file, $orig ];
        $process = proc_open($cmd, [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        if (!is_resource($process)) return null;
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        proc_close($process);
        return $out ?: $err;
    }

    private function safeVal($val, $default = null)
    {
        if (is_null($val) || $val === '') return $default;
        return $val;
    }

    private function parseIndoDate($dateStr)
    {
        if (!$dateStr) return now();
        
        $dateStr = strtoupper($dateStr);
        $months = [
            'JANUARI' => '01', 'FEBRUARI' => '02', 'MARET' => '03', 'APRIL' => '04',
            'MEI' => '05', 'MAY' => '05', 'JUNI' => '06', 'JULI' => '07', 'AGUSTUS' => '08',
            'SEPTEMBER' => '09', 'OKTOBER' => '10', 'NOVEMBER' => '11', 'DESEMBER' => '12',
            'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04', 'MEI' => '05', 'JUN' => '06',
            'JUL' => '07', 'AGU' => '08', 'SEP' => '09', 'OKT' => '10', 'NOV' => '11', 'DES' => '12'
        ];

        foreach ($months as $name => $num) {
            if (str_contains($dateStr, $name)) {
                $dateStr = str_replace($name, $num, $dateStr);
                break;
            }
        }

        try {
            // Match DD MM YYYY or DD-MM-YYYY or DD/MM/YYYY
            if (preg_match('/(\d{1,2})[\s\-\/](\d{1,2})[\s\-\/](\d{4})/', $dateStr, $m)) {
                return \Carbon\Carbon::createFromDate($m[3], $m[2], $m[1]);
            }
            
            $clean = preg_replace('/[^0-9\- ]/', '', $dateStr);
            $clean = trim(preg_replace('/\s+/', '-', $clean));
            return \Carbon\Carbon::parse($clean);
        } catch (\Exception $e) {
            return now();
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'line_master_id' => 'required|exists:line_masters,id',
            'target_qty'     => 'required|integer|min:1',
            'plan_date'      => 'required|date',
            'status'         => 'required|in:pending,approved,completed',
            'notes'          => 'nullable|string',
        ]);

        $plan = ProductionPlan::create($data);
        $this->syncToJobMaster($plan);

        return response()->json([
            'success' => true,
            'message' => 'Plan berhasil ditambahkan'
        ]);
    }

    public function show($id)
    {
        $plan = ProductionPlan::findOrFail($id);

        return response()->json([
            'success' => true,
            'plan' => $plan
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = ProductionPlan::findOrFail($id);

        $data = $request->validate([
            'line_master_id' => 'required|exists:line_masters,id',
            'target_qty'     => 'required|integer|min:1',
            'plan_date'      => 'required|date',
            'status'         => 'required|in:pending,approved,completed',
            'notes'          => 'nullable|string',
        ]);

        $plan->update($data);
        $this->syncToJobMaster($plan);

        return response()->json([
            'success' => true,
            'message' => 'Plan berhasil diupdate'
        ]);
    }

    public function destroy($id)
    {
        ProductionPlan::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan berhasil dihapus'
        ]);
    }
    public function updateInline(Request $request)
    {
        $request->validate([
            'id'    => 'required|exists:production_plans,id',
            'field' => 'required|string',
            'value' => 'required',
        ]);

        $plan = ProductionPlan::findOrFail($request->id);
        $field = $request->field;
        $plan->$field = $request->value;
        $plan->save();

        // Sync to job master if it's already approved
        if ($plan->status === 'approved') {
            $this->syncToJobMaster($plan);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'plan'    => $plan
        ]);
    }

    private function syncToJobMaster(ProductionPlan $plan)
    {
        // Hanya sinkronkan jika status adalah 'approved'
        if ($plan->status !== 'approved') {
            return;
        }

        // Gunakan job_no dari excel jika ada, jika tidak generate
        $jobNumber = $plan->job_no;
        if (!$jobNumber) {
            $dateStr = $plan->plan_date ? $plan->plan_date->format('Ymd') : now()->format('Ymd');
            $lineName = $plan->press_name ?? ($plan->line ? $plan->line->line_name : 'UNKNOWN');
            $jobNumber = 'JOB-' . $dateStr . '-' . strtoupper(Str::slug($lineName));
        }

        // Map data plan ke JobMaster
        JobMaster::updateOrCreate(
            ['job_number' => $jobNumber],
            [
                'job_name'    => $plan->job_master ?? ('Plan: ' . ($plan->press_name ?? 'Line')),
                'line'        => $plan->press_name ?? ($plan->line ? $plan->line->line_name : null),
                'capacity'    => $plan->qty_plt ?? 0,
                'status'      => 'active',
                'sequence_no' => $plan->row_no ?? 1,
                'plan_start'  => $plan->start_time,
                'plan_end'    => $plan->finish_time,
                // Kita simpan target_qty (PLAN di excel)
                'target_qty'  => $plan->plan ?? 0,
            ]
        );
    }
}