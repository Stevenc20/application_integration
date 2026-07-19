<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Events\ProductionPlanUpdated;
use App\Models\ProductionPlan;
use App\Services\ProductionMetricsService;
use App\Services\TimelineGenerationService;
use App\Models\LineMaster;
use App\Models\JobMaster;
use App\Models\MasterStamping;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Services\ExcelScheduleParser;
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

        $rawDate = $request->get('date');
        $date    = $rawDate ?: now()->toDateString();

        // Sync LKH quantities to ProductionPlan for display
        try {
            $plansForDate = ProductionPlan::whereDate('plan_date', $date)->get();
            foreach ($plansForDate as $plan) {
                $jn = trim($plan->job_no ?? '');
                $jm = trim($plan->job_master ?? '');
                if (blank($jn) && blank($jm)) continue;
                
                $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);
                $job = \App\Models\JobMaster::where('job_number', $identifier)->first();
                
                if ($job) {
                    $daily = \App\Models\DailyProduction::where('job_master_id', $job->id)
                        ->whereDate('work_date', $date)
                        ->first();
                    
                    if ($daily) {
                        $ok = $daily->actual_qty;
                        $repair = $daily->actual_repair ?: $daily->repair_qty;
                        $reject = $daily->actual_reject ?: $daily->reject_qty;
                    } else {
                        $ok = \App\Models\ProductionLog::where('job_master_id', $job->id)->sum('ok_qty');
                        $repair = \App\Models\ProductionLog::where('job_master_id', $job->id)->sum('repair_qty');
                        $reject = \App\Models\ProductionLog::where('job_master_id', $job->id)->sum('reject_qty');
                    }
                    
                    $plan->update([
                        'ok' => (float) $ok,
                        'repair' => (float) $repair,
                        'reject' => (float) $reject
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to auto-sync PPC quantities: " . $e->getMessage());
        }

        $currentPress = strtoupper($request->get('press', 'PRESS A'));
        $hasExplicitShift = $request->has('shift');
        $currentShift = $request->get('shift', 'Pagi'); 

        // 3. GET DATA CONTEXT
        $allAvailableForDate = ProductionPlan::whereDate('plan_date', $date)->get();
        $hasDataOnDate       = $allAvailableForDate->count() > 0;

        // Smart Fallback Press & Shift
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

            // Fuzzy match shift: cari nama shift di DB yang mengandung keyword (Pagi→Shift Pagi)
            if (!in_array($currentShift, $availableShifts) && count($availableShifts) > 0) {
                foreach ($availableShifts as $s) {
                    if (stripos($s, $currentShift) !== false) {
                        $currentShift = $s;
                        break;
                    }
                }
                // Jangan fallback ke shift lain jika user eksplisit klik shift tertentu
                if (!in_array($currentShift, $availableShifts) && !$hasExplicitShift) {
                    $currentShift = $availableShifts[0];
                }
            }
        } else {
            $availableShifts = [];
        }

        // 4. PACKAGING METADATA
        $activeFilters = [
            'date'        => $date,
            'press'       => $currentPress,
            'shift'       => $currentShift,
            'has_data'    => $hasDataOnDate,
            'max_date'    => $maxDate,
            'last_import' => $lastImportAt
        ];

        // =====================================
        // QUERY 1: ALL ROWS (for totals calculation)
        // No extra filters — includes total_finish, note, etc.
        // =====================================
        $allPlans = ProductionPlan::with('line')
            ->whereDate('plan_date', $date);

        if ($currentPress !== 'ALL') $allPlans->where('press_name', $currentPress);
        if ($currentShift !== 'ALL') $allPlans->where('shift_name', $currentShift);
        if ($request->filled('status') && $request->status !== '') {
            $allPlans->where(DB::raw('LOWER(status)'), strtolower($request->status));
        }
        $allPlans = $allPlans->orderByRaw("CASE WHEN LOWER(status) = 'running' THEN 0 ELSE 1 END")
            ->orderBy('row_no', 'asc')
            ->get();

        // =====================================
        // QUERY 2: DISPLAY ROWS (for table, with drag-drop)
        // Exclude total_finish, note, summary, and ghost rows
        // =====================================
        $plans = ProductionPlan::with('line')
            ->whereDate('plan_date', $date);

        if ($currentPress !== 'ALL') $plans->where('press_name', $currentPress);
        if ($currentShift !== 'ALL') $plans->where('shift_name', $currentShift);
        if ($request->filled('status') && $request->status !== '') {
            $plans->where(DB::raw('LOWER(status)'), strtolower($request->status));
        }
        $plans = $plans->orderByRaw("CASE WHEN LOWER(status) = 'running' THEN 0 ELSE 1 END")
            ->orderBy('row_no', 'asc')
            ->where(function($q) {
                $q->where('row_type', '!=', 'total_finish')
                  ->where('row_type', '!=', 'note')
                  ->where(function($q2) {
                      $q2->whereNull('job_master')
                         ->orWhere('job_master', 'NOT LIKE', '%TOTAL FINISH%');
                  })
                  ->where(function($q2) {
                      $q2->whereNull('job_no')
                         ->orWhere('job_no', 'NOT LIKE', '%TOTAL FINISH%');
                  });
            })
            ->get();

        // =====================================
        // TOTAL FINISH ROW (from ALL data, so sums are accurate)
        // =====================================
        $totalFinishRow = $allPlans->filter(function($row) {
            $jm = strtoupper($row->job_master ?? '');
            $jn = strtoupper($row->job_no ?? '');
            $combined = $jm . ' ' . $jn;
            return ($row->row_type === 'total_finish') || str_contains($combined, 'TOTAL FINISH') || str_contains($combined, 'TOTAL FNISH') || str_contains($combined, 'FINISH');
        })->first();

        // FALLBACK: Generate automatic summary if explicit row is missing
        if (!$totalFinishRow && $allPlans->count() > 0) {
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
                'gsph_item'   => ProductionMetricsService::gsph(
                    (int) $jobPlans->sum('plan'),
                    (float) $jobPlans->sum('tpt')
                ),
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

        $pendingRecoveries = \App\Models\RecoverySchedule::with('items')
            ->where('status', 'waiting_approval')
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingRecoveryItems = RecoveryItem::pending()
            ->with('schedule')
            ->where(function ($q) use ($date) {
                $q->where('original_date', '<', $date)  // only show on days AFTER the original date
                  ->orWhere('source_date', '<', $date);
            })
            ->whereHas('schedule', function ($q) {
                $q->where('status', 'waiting_approval');
            })
            ->orderByRaw('COALESCE(source_date, original_date) desc')
            ->orderBy('press_name')
            ->orderBy('job_master')
            ->get();

        // QUERY OVERFLOW: items that went through timeline engine but couldn't fit
        $overflowQuery = ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'job')
            ->whereNull('start_time')
            ->whereNull('finish_time')
            ->where('source_type', 'ppc')
            ->whereNotNull('job_no')
            ->where('job_no', '!=', '')
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

        if ($currentPress !== 'ALL') $overflowQuery->where('press_name', $currentPress);
        if ($currentShift !== 'ALL') $overflowQuery->where('shift_name', $currentShift);

        $overflowItems = $overflowQuery->get();
        $overflowByPress = $overflowItems->groupBy('press_name');
        $overflowCount = $overflowItems->count();

        // Press config for production boundaries (extended shift, jebol alert)
        $pressMeta = null;
        if ($currentPress !== 'ALL') {
            $pressMeta = \App\Models\LineMaster::where(DB::raw('REPLACE(REPLACE(UPPER(line_name), " ", ""), "-", "")'), 'LIKE', '%' . str_replace([' ', '-', 'LINE'], '', $currentPress) . '%')
                ->select('line_name', 'production_start', 'production_end')
                ->first();
        }

        // Boundary overflow: items with finish_time past press production_end
        $boundaryOverflowIds = [];
        if ($pressMeta && $pressMeta->production_end) {
            $endMins = \App\Models\MasterBreakTime::timeToMinutes($pressMeta->production_end);
            $boundaryOverflow = $plans->filter(function($plan) use ($endMins) {
                if (($plan->row_type ?? 'job') !== 'job') return false;
                if (!$plan->finish_time) return false;
                if (($plan->source_type ?? 'ppc') === 'recovery') return false;
                return \App\Models\MasterBreakTime::timeToMinutes($plan->finish_time) >= $endMins;
            })->values();
            $boundaryOverflowIds = $boundaryOverflow->pluck('id')->toArray();

            $existingIds = $overflowItems->pluck('id')->toArray();
            $newOverflow = $boundaryOverflow->whereNotIn('id', $existingIds);
            if ($newOverflow->isNotEmpty()) {
                $overflowItems = $overflowItems->concat($newOverflow);
                $overflowByPress = $overflowItems->groupBy('press_name');
                $overflowCount = $overflowItems->count();
            }
        }

        return view(
            'ppc.planning.production_plan',
            compact('plans', 'lines', 'date', 'currentPress', 'totalFinishRow', 'cardSummaries', 'currentShift', 'availableShifts', 'totalJobs', 'activeFilters', 'pendingRecoveries', 'pendingRecoveryItems', 'overflowItems', 'overflowByPress', 'overflowCount', 'pressMeta', 'boundaryOverflowIds')
        );
    }

    public function import(Request $request)
    {
        set_time_limit(120);
        \Log::info("--- PRODUCTION PLAN IMPORT STARTED ---");
        $request->validate([
            'excel_file' => 'required|file|max:51200|extensions:xlsx,xls,xlsm',
        ]);

        try {
            $file         = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();
            $extension    = strtolower($file->getClientOriginalExtension());
            $uploadDir    = storage_path('app/uploads');
            $dataPath     = $uploadDir . '/prod_plan_temp.' . $extension;

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $file->move($uploadDir, 'prod_plan_temp.' . $extension);

            $result = null;
            $parserSource = null;

            // ATTEMPT 1: Python engine
            $python = $this->findPython();
            if ($python) {
                $scriptPath = base_path('scripts/read_schedule_stamping.py');
                if (file_exists($scriptPath)) {
                    $output = $this->runPythonScript($python, $scriptPath, $dataPath, $originalName);

                    \Log::info('[IMPORT] PYTHON RAW OUTPUT type=' . gettype($output) . ' strlen=' . strlen((string)($output ?? '')));
                    \Log::info('[IMPORT] PYTHON RAW CONTENT: ' . mb_substr((string)($output ?? ''), 0, 2000));

                    if ($output) {
                        $output = trim($output);
                        $jsonStart = strpos($output, '{');
                        if ($jsonStart !== false) $output = substr($output, $jsonStart);
                        $parsed = json_decode($output, true);
                        if ($parsed && !isset($parsed['error'])) {
                            $result = $parsed;
                            $parserSource = 'python';
                            \Log::info('[IMPORT] Python engine succeeded.');
                        } else {
                            \Log::warning("Python engine failed, falling back to PHP: " . ($parsed['error'] ?? 'Invalid JSON'));
                        }
                    } else {
                        \Log::warning("Python engine produced no output, falling back to PHP.");
                    }
                }
            } else {
                \Log::info('[IMPORT] Python not found, using PHP fallback.');
            }

            // ATTEMPT 2: PHP PhpSpreadsheet fallback
            if (!$result) {
                try {
                    $parser = app(ExcelScheduleParser::class);
                    $result = $parser->parse($dataPath, $originalName);
                    $parserSource = 'php';
                    if (isset($result['error'])) {
                        \Log::error("PHP Excel Parser Error: " . $result['error']);
                        @unlink($dataPath);
                        return back()->with('error', 'Error Parsing Excel: ' . $result['error']);
                    }
                    \Log::info('[IMPORT] PHP ExcelParser succeeded.');
                } catch (\Throwable $e) {
                    \Log::error("PHP ExcelParser crashed: " . $e->getMessage());
                    @unlink($dataPath);
                    return back()->with('error', 'Gagal membaca Excel: ' . $e->getMessage());
                }
            }

            if (!$result) {
                @unlink($dataPath);
                return back()->with('error', 'Gagal mendapatkan output dari engine Python maupun PHP fallback.');
            }

            // [TRACE JSON] Dump parser output to storage for UAT
            file_put_contents(storage_path('logs/import_dump.json'), json_encode([
                'source' => $parserSource,
                'data' => $result,
            ], JSON_PRETTY_PRINT));

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

            // APPLY SHIFT FILTER FROM MODAL
            $shiftFilter = $request->get('shift_filter', 'all');
            $sheets = array_values($result['sheets'] ?? []);
            if ($shiftFilter !== 'all') {
                $sheets = array_values(array_filter($sheets, function ($s) use ($shiftFilter) {
                    $name = strtolower($s['shift_name'] ?? '');
                    return str_contains($name, $shiftFilter);
                }));
            }
            $result['sheets'] = $sheets;

            \DB::transaction(function () use ($result, $parsedDate, &$imported) {
                $lineMap = LineMaster::pluck('id', 'line_name')->toArray();

                $sheets = array_values($result['sheets'] ?? []);
                if (empty($sheets)) return;

                // COLLECT UNIQUE SHIFT NAMES FOR CLEANUP
                $uniqueShifts = [];
                foreach ($sheets as $s) {
                    $raw = $s['shift_name'] ?? '';
                    $shiftName = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $raw);
                    $shiftName = trim(ucwords(strtolower($shiftName)));
                    if ($shiftName) $uniqueShifts[$shiftName] = true;
                }

                foreach (array_keys($uniqueShifts) as $shiftToDelete) {
                    // 1. DELETE old PPC baseline rows (source_type = 'ppc')
                    $ppcPlanIds = ProductionPlan::whereDate('plan_date', $parsedDate)
                        ->where('shift_name', $shiftToDelete)
                        ->where('source_type', 'ppc')
                        ->pluck('id');

                    if ($ppcPlanIds->isNotEmpty()) {
                        // Disassociate recovery queue items — DON'T delete them
                        \App\Models\RecoveryItem::whereIn('production_plan_id', $ppcPlanIds)
                            ->update(['production_plan_id' => null]);
                    }

                    ProductionPlan::whereDate('plan_date', $parsedDate)
                        ->where('shift_name', $shiftToDelete)
                        ->where('source_type', 'ppc')
                        ->delete();

                    // 2. DELETE recovery-generated timeline rows (source_type = 'recovery')
                    //    BUT keep plans for items that are already in_production or completed
                    $recoveryPlanIds = ProductionPlan::whereDate('plan_date', $parsedDate)
                        ->where('shift_name', $shiftToDelete)
                        ->where('source_type', 'recovery')
                        ->whereDoesntHave('recoveryItem', function ($q) {
                            $q->whereIn('status', ['in_production', 'completed']);
                        })
                        ->pluck('id');

                    if ($recoveryPlanIds->isNotEmpty()) {
                        \App\Models\RecoveryItem::whereIn('production_plan_id', $recoveryPlanIds)
                            ->update(['production_plan_id' => null]);
                    }

                    ProductionPlan::whereDate('plan_date', $parsedDate)
                        ->where('shift_name', $shiftToDelete)
                        ->where('source_type', 'recovery')
                        ->whereDoesntHave('recoveryItem', function ($q) {
                            $q->whereIn('status', ['in_production', 'completed']);
                        })
                        ->delete();

                    // 3. DELETE timeline-generated breaks (source_type = null from regenerateSection)
                    ProductionPlan::whereDate('plan_date', $parsedDate)
                        ->where('shift_name', $shiftToDelete)
                        ->where('row_type', 'break')
                        ->whereNull('source_type')
                        ->delete();

                    // Revert approved/scheduled RecoveryItems back to waiting_approval queue
                    // First get the IDs of items to revert (all, not just those with null production_plan_id)
                    $revertRecoveryIds = \App\Models\RecoveryItem::whereDate('source_date', $parsedDate)
                        ->where('source_shift', $shiftToDelete)
                        ->whereIn('status', ['approved', 'scheduled'])
                        ->pluck('id');

                    if ($revertRecoveryIds->isNotEmpty()) {
                        // Reset any ProductionPlan rows linked to these RecoveryItems
                        // This handles orphaned plans on other dates/shifts that weren't deleted above
                        \App\Models\ProductionPlan::whereIn('recovery_id', $revertRecoveryIds)
                            ->where('source_type', 'recovery')
                            ->update(['source_type' => 'ppc', 'recovery_id' => null]);

                        // Revert the RecoveryItems
                        \App\Models\RecoveryItem::whereIn('id', $revertRecoveryIds)
                            ->update(['status' => 'waiting_approval']);
                    }
                }

                // PRE-PASS: deteksi grup (cleanShift||press) yang punya sheet REV
                $hasRevInGroup = [];
                foreach ($sheets as $s) {
                    $raw = $s['shift_name'] ?? '';
                    $clean = trim(ucwords(strtolower(preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $raw))));
                    $key = $clean . '||' . ($s['press_name'] ?? '');
                    if (preg_match('/REV|REVISI/i', $raw)) {
                        $hasRevInGroup[$key] = true;
                    }
                }

                $importedJobKeys = [];
                $importedBreakKeys = [];
                foreach ($sheets as $sheetData) {
                    $originalShift = $sheetData['shift_name'] ?? '';
                    $cleanShift = trim(ucwords(strtolower(preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $originalShift))));
                    $groupKey = $cleanShift . '||' . ($sheetData['press_name'] ?? '');
                    $isRevSheet = (bool) preg_match('/REV|REVISI/i', $originalShift);

                    // Non-Rev sheet: process but skip rows already imported from Rev
                    // Rev sheet tetap diproses normal
                    $rows = [];
                    $shiftName = $sheetData['shift_name'];
                    $shiftName = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $shiftName);
                    $shiftName = trim(ucwords(strtolower($shiftName)));
                    $pressName = $sheetData['press_name'];
                    
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

                    // INSERT EXCEL ITEMS
                    $excelRowNos = [];
                    $totalReceived = count($sheetData['rows']);
                    $skippedMeta = 0;

                    foreach ($sheetData['rows'] as $item) {
                        \Log::info('[TRACE ROW PARSER]', [
                            'raw_row_no' => $item['row_no'] ?? 'KEY_MISSING',
                            'type'       => gettype($item['row_no'] ?? null),
                            'job_no'     => $item['job_no'] ?? null,
                            'row_type'   => $item['row_type'] ?? null,
                            'start'      => $item['start_time'] ?? null,
                            'finish'     => $item['finish_time'] ?? null,
                        ]);

                        // ── SAFETY NET: skip rows that are clearly Excel metadata, not jobs ──
                        $rawJm = $item['job_master'] ?? '';
                        $rawJn = $item['job_no'] ?? '';
                        $isMeta = (
                            str_starts_with($rawJm, ':') ||                                   // ": Selasa Pagi", ": 23-Juni-2026"
                            strtoupper(trim($rawJm)) === 'JOB MASTER' ||                       // "JOB MASTER" header row
                            strtoupper(trim($rawJn)) === 'JOB NO.'                             // "JOB NO." header row
                        );
                        if ($isMeta) {
                            \Log::info('[IMPORT] Skipped metadata row', ['job_master' => $rawJm, 'job_no' => $rawJn]);
                            $skippedMeta++;
                            continue;
                        }

                        // ── Skip rows marked as "Delete" in keterangan (deleted schedule items) ──
                        if (str_contains(strtoupper($item['keterangan'] ?? ''), 'DELETE')) {
                            // Track Rev sheet job key BEFORE skipping, so non-Rev dedup can match
                            if ($isRevSheet && $rawJn && $rawJm) {
                                $jobKey = $lineId . '||' . $rawJm . '||' . $rawJn;
                                $importedJobKeys[$jobKey] = true;
                            }
                            \Log::info('[IMPORT] Skipped deleted row', ['keterangan' => $item['keterangan'], 'job_no' => $rawJn]);
                            $skippedMeta++;
                            continue;
                        }

                        $rowType = $item['row_type'] ?? 'job';
                        $jn = strtoupper($item['job_no'] ?? '');
                        $jm = strtoupper($item['job_master'] ?? '');
                        
                        $planQty = floatval($item['plan'] ?? 0);
                        $processTime = floatval($item['process_time'] ?? 0);
                        $ctDetik = floatval($item['ct_detik'] ?? 0);
                        $qtyPlt = floatval($item['qty_plt'] ?? 0);

                        $isBreakDesc = false;
                        $breakKeywords = ['ISTIRAHAT', 'CINGKORAK', 'BREAKTIME', 'BREAK TIME', 'ISHOMA'];
                        foreach ($breakKeywords as $kw) {
                            if (str_contains($jn, $kw) || str_contains($jm, $kw) || str_contains(strtoupper($item['keterangan'] ?? ''), $kw)) {
                                $isBreakDesc = true;
                                break;
                            }
                        }

                        $isSummaryPattern = str_contains($jm, 'TOTAL FINISH') || str_contains($jn, 'TOTAL FINISH') || 
                                            str_contains($jm, 'TOTAL FNISH') || str_contains($jn, 'TOTAL FNISH');

                        // Structure-Driven RowClassifier
                        if ($isSummaryPattern) {
                            $rowType = 'total_finish';
                        } elseif ($isBreakDesc || $rowType === 'break') {
                            $rowType = 'break';
                            // Safety net: skip non-break rows misclassified as break (e.g. info/note row containing keyword like ISTIRAHAT)
                            // Legitimate breaks always have proper start_time; misclassified rows have empty start_time
                            if (empty($item['start_time'])) {
                                \Log::info('[IMPORT] Skipped break misclassification', ['job_no' => $rawJn]);
                                $skippedMeta++;
                                continue;
                            }
                        } elseif ($planQty > 0 || $ctDetik > 0) {
                            $rowType = 'job';
                        } elseif ($planQty == 0 && $ctDetik == 0 && $processTime == 0) {
                            // Zero production metrics -> Note / Info / Spacer (not a real job)
                            $rowType = 'note';
                        } else {
                            $rowType = 'job';
                        }

                    $jm = $item['job_master'] ?? '';
                    $jn = $item['job_no'] ?? '';
                    
                    // Normalization: only force TOTAL FINISH for break rows (not for legitimate job data)
                    if ($rowType === 'break' && (str_contains(strtoupper($jm), 'FINISH') || str_contains(strtoupper($jn), 'FINISH') || 
                        str_contains(strtoupper($jm), 'FNISH') || str_contains(strtoupper($jn), 'FNISH'))) {
                        $jm = 'TOTAL FINISH';
                        $jn = 'TOTAL FINISH';
                    }

                        $planQty = (int) $this->safeVal($item['plan'], 0);
                        $ctDetik = (float) $this->safeVal($item['ct_detik'], 0);
                        $processTime = $rowType === 'job'
                            ? (int) $this->safeVal($item['process_time'], ProductionMetricsService::calculateProcessTime($ctDetik, $planQty))
                            : 0;

                        // Dedup: skip non-Rev row if same job_master+job_no already imported from Rev
                        if (!$isRevSheet && $rowType === 'job' && $jm && $jn) {
                            $jobKey = $lineId . '||' . $jm . '||' . $jn;
                            if (isset($importedJobKeys[$jobKey])) {
                                $skippedMeta++;
                                continue;
                            }
                        }
                        // Track job key for Rev sheets
                        if ($isRevSheet && $rowType === 'job' && $jm && $jn) {
                            $jobKey = $lineId . '||' . $jm . '||' . $jn;
                            $importedJobKeys[$jobKey] = true;
                        }

                        // Dedup break rows: skip if same line+press+keterangan+start+finish already imported
                        if ($rowType === 'break') {
                            $breakKeterangan = strtoupper(trim($item['job_no'] ?? $item['keterangan'] ?? ''));
                            $breakStart = $item['start_time'] ?? '';
                            $breakFinish = $item['finish_time'] ?? '';
                            $breakKey = $lineId . '||break||' . $breakKeterangan . '||' . $breakStart . '||' . $breakFinish;
                            \Log::info('[BREAK DEDUP]', [
                                'sheetType'    => $isRevSheet ? 'Rev' : 'non-Rev',
                                'keterangan'   => $item['keterangan'] ?? null,
                                'job_no'       => $item['job_no'] ?? null,
                                'breakLabel'   => $breakKeterangan,
                                'start'        => $breakStart,
                                'finish'       => $breakFinish,
                                'breakKey'     => $breakKey,
                                'alreadyExists'=> isset($importedBreakKeys[$breakKey]),
                                'lineId'       => $lineId,
                                'press'        => $pressName,
                            ]);
                            if (isset($importedBreakKeys[$breakKey])) {
                                $skippedMeta++;
                                continue;
                            }
                            $importedBreakKeys[$breakKey] = true;
                        }

                        $finalRowNo = $this->safeVal($item['row_no']) ?: 0;
                        
                        \Log::info('[TRACE IMPORT]', [
                            'job'          => $jn,
                            'raw_row_no'   => $item['row_no'] ?? 'KEY_MISSING',
                            'final_row_no' => $finalRowNo,
                            'row_type'     => $rowType,
                        ]);

                        $rows[] = [
                            'line_master_id' => $lineId,
                            'plan_date'      => $parsedDate,
                            'shift_name'     => $shiftName,
                            'press_name'     => $pressName,
                            'hari'           => $this->safeVal($sheetData['hari']),
                            'tgl'            => $this->safeVal($sheetData['tgl']),
                            'jam'            => $this->safeVal($sheetData['jam']),
                            'revisi'         => $this->safeVal($sheetData['revisi']),
                            'row_no'         => $finalRowNo,
                            'source_type'    => 'ppc',
                            'row_type'       => $rowType,
                            'job_master'     => $this->safeVal($jm),
                            'type_plt'       => $this->safeVal($item['type_plt']),
                            'qty_plt'        => $this->safeVal($item['qty_plt'], 0),
                            'keb_mtl'        => $this->safeVal($item['keb_mtl'], 0),
                            'total_plt'      => $this->safeVal($item['total_plt'], 0),
                            'job_no'         => $this->safeVal($jn),
                            'each_part'      => $this->safeVal($item['each_part']),
                            'plan'           => $planQty,
                            'ok'             => $this->safeVal($item['ok'], 0),
                            'repair'         => 0, // repair is actual daily data, not from schedule
                            'reject'         => $this->safeVal($item['reject'], 0),
                            'total_mesin'    => $this->safeVal($item['total_mesin'], 1),
                            'p1'             => ($this->safeVal($item['a1'] ?? 0, 0) > 0),
                            'p2'             => ($this->safeVal($item['a2'] ?? 0, 0) > 0),
                            'p3'             => ($this->safeVal($item['a3'] ?? 0, 0) > 0),
                            'p4'             => ($this->safeVal($item['a4'] ?? 0, 0) > 0),
                            'ct_detik'       => $ctDetik,
                            'process_time'   => $processTime,
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

                    $inserted = count($rows);
                    foreach (array_chunk($rows, 100) as $chunk) {
                        ProductionPlan::insert($chunk);
                    }

                    \Log::info('[IMPORT] Section stats', [
                        'shift' => $shiftName,
                        'press' => $pressName,
                        'rows_from_python' => $totalReceived,
                        'meta_skipped' => $skippedMeta,
                        'inserted' => $inserted,
                    ]);

                    $imported += $inserted;
                }

                // Log Python's own row counts if available
                if (isset($result['log'])) {
                    \Log::info('[IMPORT] Python parser stats', $result['log']);
                }
 
            });

            // LOG SCHEDULE REVISION
            try {
                $shiftNames = collect($sheets)->map(function ($s) {
                    $sn = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $s['shift_name'] ?? '');
                    return trim(ucwords(strtolower($sn)));
                })->unique()->values()->implode(', ');

                \App\Models\ScheduleRevision::create([
                    'plan_date' => $parsedDate,
                    'shift_name' => $shiftNames ?: '-',
                    'action' => 'import',
                    'snapshot_after' => ['sheets' => count($sheets), 'rows_imported' => $imported],
                    'created_by' => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                \Log::warning("Failed to log schedule revision: " . $e->getMessage());
            }

            // CLEANUP DATA LAMA: Hapus PPC production plan untuk tanggal sebelum yang diimport
            // Hanya source_type='ppc' — jangan sentuh recovery items dan plan yang punya recovery
            $activeRecoveryPlanIds = \App\Models\RecoveryItem::pluck('production_plan_id')->filter()->unique();
            $oldPlansCount = ProductionPlan::whereDate('plan_date', '<', $parsedDate)
                ->where('source_type', 'ppc')
                ->whereNotIn('id', $activeRecoveryPlanIds)
                ->delete();
            if ($oldPlansCount > 0) {
                \Log::info("Import cleanup: {$oldPlansCount} old PPC plans deleted (before {$parsedDate})");
            }

            @unlink($dataPath);

            \Log::info('[IMPORT] Baseline saved — no timeline regeneration (per SRS Step 1)', [
                'date' => $parsedDate,
                'imported' => $imported,
            ]);

            // Auto-redirect to the first parsed sheet for convenience
            if (!empty($result['sheets'])) {
                $firstKey = array_key_first($result['sheets']);
                $firstSheet = $result['sheets'][$firstKey];
                $redirectShift = $firstSheet['shift_name'] ?? null;
                if ($redirectShift) {
                    $redirectShift = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $redirectShift);
                    $redirectShift = trim(ucwords(strtolower($redirectShift)));
                }
                return redirect()->route('ppc.planning.production_plan', [
                    'date'  => $parsedDate,
                    'press' => $firstSheet['press_name'] ?? null,
                    'shift' => $redirectShift,
                ])->with('success', "Import Berhasil! {$imported} data diproses.");
            }

            return back()->with('success', "Import Berhasil! {$imported} data diproses.");

        } catch (\Throwable $e) {
            \Log::error("Python Import Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function clearDataForm()
    {
        return view('ppc.planning.clear_data');
    }

    public function clearData(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'shift' => 'nullable|in:Pagi,Malam,',
        ]);

        $date = $request->input('date');
        $shift = $request->input('shift');

        $query = ProductionPlan::whereDate('plan_date', $date);

        if ($shift) {
            $query->where('shift_name', 'like', "Shift $shift%");
        }

        $plansToDelete = (clone $query)->pluck('id');
        if ($plansToDelete->isNotEmpty()) {
            \App\Models\RecoveryItem::whereIn('production_plan_id', $plansToDelete)
                ->delete();
        }

        $count = $query->count();
        $query->delete();

        return redirect()->route('ppc.planning.production_plan', ['date' => $date])
            ->with('success', "Clear Data Selesai! $count record untuk tanggal $date" . ($shift ? " (Shift $shift)" : '') . " telah dihapus.");
    }

    public function cancelApproval(Request $request)
    {
        $itemIds = $request->input('item_ids', []);

        if (empty($itemIds)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada item yang dipilih.']);
        }

        $processedCount = 0;
        $failedCount = 0;
        $targets = [];

        DB::transaction(function () use ($itemIds, &$processedCount, &$failedCount, &$targets) {
            $items = RecoveryItem::whereIn('id', $itemIds)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $plan = ProductionPlan::where('recovery_id', $item->id)->first();
                
                if ($plan) {
                    // Strict cancelation check based on operational reality
                    $hasStarted = ($plan->ok > 0 || $plan->repair > 0 || $plan->reject > 0 || $plan->status === 'running');
                    
                    if ($hasStarted) {
                        $failedCount++;
                        continue;
                    }

                    // Save target context to recalculate the timeline later
                    $key = $plan->plan_date . '||' . $plan->press_name;
                    $targets[$key] = [
                        'date' => $plan->plan_date,
                        'shift' => $plan->shift_name,
                        'press' => $plan->press_name
                    ];

                    $plan->delete();
                }

                $item->update([
                    'status' => 'waiting_approval',
                    'updated_at' => now(),
                ]);

                // Safety net: reset any surviving ProductionPlan rows linked to this RecoveryItem
                \App\Models\ProductionPlan::where('recovery_id', $item->id)
                    ->where('source_type', 'recovery')
                    ->update(['source_type' => 'ppc', 'recovery_id' => null]);

                $processedCount++;
            }
        });

        // Regenerate sections using the target contexts
        if ($processedCount > 0) {
            try {
                $timelineGenerator = app(TimelineGenerationService::class);
                foreach ($targets as $target) {
                    $timelineGenerator->regenerateSection($target['date'], $target['shift'], $target['press']);
                }
            } catch (\Throwable $e) {
                \Log::warning('Resimulation after cancel failed: ' . $e->getMessage());
            }
        }

        if ($processedCount === 0) {
            return response()->json(['success' => false, 'message' => 'Semua item ditolak untuk cancel karena sudah diproduksi atau tidak ditemukan.']);
        }

        $msg = $processedCount . ' item berhasil di-cancel.';
        if ($failedCount > 0) {
            $msg .= ' (' . $failedCount . ' item ditolak karena sudah mulai diproduksi).';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function approveRecovery($id)
    {
        $schedule = \App\Models\RecoverySchedule::with('items')->findOrFail($id);

        if ($schedule->status !== 'waiting_approval') {
            return response()->json(['success' => false, 'message' => 'Status recovery sudah diproses.']);
        }

        $schedule->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Recovery approved.']);
    }

    public function rejectRecovery($id)
    {
        $schedule = \App\Models\RecoverySchedule::with('items')->findOrFail($id);

        if ($schedule->status !== 'waiting_approval') {
            return response()->json(['success' => false, 'message' => 'Status recovery sudah diproses.']);
        }

        $schedule->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Recovery rejected.']);
    }

    public function approveItems(Request $request)
    {
        $itemIds = $request->input('item_ids', []);
        $targetDate = $request->input('target_date');
        $targetShift = $request->input('target_shift');
        $targetPress = $request->input('target_press');

        if (empty($itemIds)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada item yang dipilih.']);
        }

        if (empty($targetDate) || empty($targetShift) || empty($targetPress)) {
            return response()->json(['success' => false, 'message' => 'Context planning tidak ditemukan. Silakan refresh halaman.']);
        }

        $processedCount = 0;

        DB::transaction(function () use ($itemIds, $targetDate, $targetShift, $targetPress, &$processedCount) {
            // Fetch and lock items to prevent race conditions (Duplicate protection)
            $items = RecoveryItem::whereIn('id', $itemIds)
                ->where('status', 'waiting_approval')
                ->lockForUpdate()
                ->orderBy('source_date', 'asc')
                ->orderBy('source_shift', 'asc')
                ->orderBy('original_row_no', 'asc')
                ->get();

            if ($items->isEmpty()) {
                return;
            }
            
            $processedCount = $items->count();

            // Dapatkan hari untuk targetDate
            $hari = \Carbon\Carbon::parse($targetDate)->locale('id')->isoFormat('dddd');
            
            // Dapatkan line_master_id dari jadwal yang ada di hari tersebut, atau fallback
            $existingPlan = ProductionPlan::whereDate('plan_date', $targetDate)
                ->where('press_name', $targetPress)
                ->first();
            $lineMasterId = $existingPlan ? $existingPlan->line_master_id : 1;

            foreach ($items as $index => $item) {

                $processTime = (int)ceil((($item->ct_detik ?? 0) * $item->recovery_qty) / 60.0);

                ProductionPlan::create([
                    'plan_date'      => $targetDate,
                    'shift_name'     => $targetShift,
                    'press_name'     => $targetPress,
                    'line_master_id' => $lineMasterId,
                    'hari'           => $hari,
                    'row_type'       => 'job',
                    'row_no'         => 0,
                    'job_no'         => $item->job_no,
                    'job_master'     => $item->job_master,
                    'plan'           => $item->recovery_qty,
                    'original_plan'  => $item->plan_qty,
                    'remaining_plan' => $item->recovery_qty,
                    'ct_detik'       => $item->ct_detik,
                    'dct'            => $item->dct,
                    'total_mesin'    => (int)($item->total_mesin ?? 1),
                    'process_time'   => $processTime,
                    'recovery_id'    => $item->id,
                    'source_type'    => 'recovery',
                    'status'         => 'pending',
                ]);

                $item->update([
                    'status' => 'scheduled',
                    'updated_at' => now(),
                ]);
            }
        });

        if ($processedCount === 0) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan atau sudah diproses oleh user lain.']);
        }

        // Regenerate sections using the target context
        try {
            $timelineGenerator = app(TimelineGenerationService::class);
            $timelineGenerator->regenerateSection($targetDate, $targetShift, $targetPress);
        } catch (\Throwable $e) {
            \Log::warning('Resimulation after approve failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $processedCount . ' item berhasil di-approve.',
        ]);
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
        return null; 
    }

    private function runPythonScript($python, $script, $file, $orig)
    {
        $cmd = [$python, $script, $file, $orig];
        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        if (!is_resource($process)) return null;
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $out = '';
        $err = '';
        $start = time();

        while (true) {
            $out .= stream_get_contents($pipes[1]);
            $err .= stream_get_contents($pipes[2]);

            $status = proc_get_status($process);
            if (!$status['running']) {
                $out .= stream_get_contents($pipes[1]);
                $err .= stream_get_contents($pipes[2]);
                break;
            }

            if (time() - $start > 30) {
                \Log::warning('[IMPORT] Python script timeout after 30s, killing process.');
                proc_terminate($process, 9);
                stream_set_blocking($pipes[1], true);
                stream_set_blocking($pipes[2], true);
                $out .= stream_get_contents($pipes[1]);
                $err .= stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                return null;
            }

            usleep(100000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        \Log::info("[IMPORT] proc_close exit_code={$exitCode} stdout_strlen=" . strlen($out) . " stderr_strlen=" . strlen($err));
        if ($err) {
            \Log::warning("[IMPORT] PYTHON STDERR: " . mb_substr($err, 0, 1000));
        }

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

        $plan  = ProductionPlan::findOrFail($request->id);
        $field = $request->field;

        if ($field === 'ct_detik' && !$request->boolean('manual_override')) {
            return response()->json([
                'success' => false,
                'message' => 'CT hanya bisa diubah lewat mode manual override.',
            ], 422);
        }

        // Simpan field yang diinput user
        $plan->$field = $request->value;
        $plan->save();

        // AUTO-RECALCULATE: Jika field yang diubah mempengaruhi metrics turunan,
        // hitung ulang process_time, tpt, dan gsph_item secara otomatis.
        $metricsFields = ['ct_detik', 'plan', 'reg_active', 'dct', 'mct'];
        if (in_array($field, $metricsFields)) {
            $plan->refresh(); // Ambil data terbaru dari DB setelah save di atas
            $planQty = (int)   ($plan->plan     ?? 0);
            $ct      = (float) ($plan->ct_detik ?? 0);

            $plan->process_time = ProductionMetricsService::calculateProcessTime($ct, $planQty);
            $plan->tpt          = ProductionMetricsService::planTptMinutes($plan);
            $plan->gsph_item    = ProductionMetricsService::gsph($planQty, $plan->tpt);
            $plan->save();

            // Regenerate timeline agar start_time / finish_time ikut terupdate
            try {
                $timelineGenerator = app(TimelineGenerationService::class);
                $timelineGenerator->regenerateForPlan($plan);
            } catch (\Throwable $e) {
                \Log::warning("Timeline regeneration skipped after updateInline: " . $e->getMessage());
            }
        }

        ProductionPlanUpdated::dispatch($plan->fresh(), $field);

        // Sync to job master if it's already approved
        if ($plan->status === 'approved') {
            $this->syncToJobMaster($plan->fresh());
        }

        $plan = $plan->fresh();

        return response()->json([
            'success'      => true,
            'message'      => 'Data berhasil diperbarui',
            'plan'         => $plan,
            'process_time' => $plan->process_time,
            'tpt'          => $plan->tpt,
            'gsph'         => $plan->gsph_item,
            'start_time'   => $plan->start_time,
            'finish_time'  => $plan->finish_time,
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:production_plans,id',
        ]);

        $ids = $request->ids;

        // AUDIT 1: Pastikan array $ids dari Javascript benar-benar masuk
        \Log::info('[REORDER IDS]', [
            'ids' => $ids
        ]);

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                ProductionPlan::where('id', $id)->update(['row_no' => $index + 1]);
            }

            $recoveryPlans = ProductionPlan::whereIn('id', $ids)
                ->whereNotNull('recovery_id')
                ->select('id', 'recovery_id')
                ->get()
                ->keyBy('id');

            $recoveryOrder = 0;
            $recoveryIds = [];
            foreach ($ids as $id) {
                if (isset($recoveryPlans[$id])) {
                    $recoveryIds[] = $recoveryPlans[$id]->recovery_id;
                    RecoveryItem::where('id', $recoveryPlans[$id]->recovery_id)
                        ->update(['sort_order' => ++$recoveryOrder]);
                }
            }

            \Log::info('[REORDER] RecoveryItem sort_order updated', [
                'count' => count($recoveryIds),
                'orders' => RecoveryItem::whereIn('id', $recoveryIds)
                    ->pluck('sort_order', 'id')
                    ->toArray()
            ]);
        });

        $firstPlan = ProductionPlan::find($ids[0] ?? null);

        if ($firstPlan) {
            // AUDIT 2: Buktikan database row_no benar-benar berubah sebelum masuk ke Timeline
            $rows = ProductionPlan::whereDate('plan_date', $firstPlan->plan_date)
                ->where('shift_name', $firstPlan->shift_name)
                ->where('press_name', $firstPlan->press_name)
                ->orderBy('row_no')
                ->pluck('id', 'row_no');
                
            \Log::info('[REORDER AFTER UPDATE]', $rows->toArray());

            try {
                $timelineGenerator = app(TimelineGenerationService::class);
                $timelineGenerator->regenerateForPlan($firstPlan);

                $dispatchPlan = ProductionPlan::whereDate('plan_date', $firstPlan->plan_date)
                    ->where('shift_name', $firstPlan->shift_name)
                    ->where('press_name', $firstPlan->press_name)
                    ->where('row_type', 'job')
                    ->orderBy('id')
                    ->first();
                if ($dispatchPlan) {
                    ProductionPlanUpdated::dispatch($dispatchPlan, 'row_no');
                }
            } catch (\Throwable $e) {
                \Log::warning("Timeline regeneration after reorder: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }

    public function recalculate($id)
    {
        $plan = ProductionPlan::findOrFail($id);
        
        // Recalculate derived fields
        $planQty = (int) ($plan->plan ?? 0);
        $ct = (float) ($plan->ct_detik ?? 0);
        
        $plan->process_time = ProductionMetricsService::calculateProcessTime($ct, $planQty);
        $plan->tpt = ProductionMetricsService::planTptMinutes($plan);
        $plan->gsph_item = ProductionMetricsService::gsph($planQty, $plan->tpt);
        $plan->save();

        // Trigger timeline generation
        $timelineGenerator = app(TimelineGenerationService::class);
        $timelineGenerator->regenerateForPlan($plan);

        $plan = $plan->fresh();

        return response()->json([
            'success' => true,
            'process_time' => $plan->process_time,
            'tpt' => $plan->tpt,
            'gsph' => $plan->gsph_item,
            'start_time' => $plan->start_time,
            'finish_time' => $plan->finish_time,
        ]);
    }

    public function addJob(Request $request)
    {
        $request->validate([
            'plan_date'   => 'required|date',
            'shift_name'  => 'required|string|max:50',
            'press_name'  => 'required|string|max:50',
            'job_no'      => 'required|string|max:100',
            'plan'        => 'nullable|numeric|min:0',
            'ct_detik'    => 'nullable|numeric|min:0',
            'dct'         => 'nullable|numeric|min:0',
            'reg_active'  => 'nullable|numeric|min:0',
            'keterangan'  => 'nullable|string|max:255',
            'machines'    => 'nullable|array',
            'machines.*'  => 'integer|in:1,2,3,4',
        ]);

        $date      = $request->plan_date;
        $shift     = $request->shift_name;
        $press     = $request->press_name;
        $jobNo     = trim($request->job_no);

        // Lookup master data stamping for auto-fill
        $master = MasterStamping::where('job_no', $jobNo)->first();

        $jobMaster  = $master ? $master->job_master : $jobNo;
        $typePlt    = $master ? $master->type_pallet : null;
        $qtyPlt     = $master ? (float)$master->qty_pallet : 0;
        $ctDetik    = $request->ct_detik !== null ? (float)$request->ct_detik : ($master ? (float)$master->ct_detik : 0);
        $dct        = $request->dct !== null ? (float)$request->dct : ($master ? (float)$master->dct : 0);
        $regActive  = $request->reg_active !== null ? (float)$request->reg_active : ($master ? (float)$master->reg_active : 0);

        $selectedMachines = $request->input('machines', []);
        if (empty($selectedMachines)) {
            $selectedMachines = [1, 2, 3, 4];
        }
        $totalMesin = count($selectedMachines);
        $keterangan = $request->keterangan ? trim($request->keterangan) : ($master ? $master->remarks : '');

        // Fuzzy match line_master_id from press_name
        $lineId = null;
        $lineMap = LineMaster::where('status', 'active')->pluck('id', 'line_name')->toArray();
        $pressKey = strtoupper(str_replace([' ', '-', 'LINE'], '', $press));
        foreach ($lineMap as $name => $id) {
            $cleanName = strtoupper(str_replace([' ', '-', 'LINE'], '', $name));
            if ($cleanName === $pressKey || str_contains($pressKey, $cleanName) || str_contains($cleanName, $pressKey)) {
                $lineId = $id;
                break;
            }
        }
        if (!$lineId) $lineId = reset($lineMap) ?: 1;

        // Determine next row_no for this section
        $maxRowNo = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->whereIn('row_type', ['job', 'break'])
            ->max('row_no') ?? 0;
        $newRowNo = $maxRowNo + 1;

        // Calculate process time (simple formula)
        $planQty = $request->plan !== null ? (int)$request->plan : 0;
        $processTime = ProductionMetricsService::calculateProcessTime($ctDetik, $planQty);

        // Map machines to p1-p4 booleans
        $p1 = in_array(1, $selectedMachines);
        $p2 = in_array(2, $selectedMachines);
        $p3 = in_array(3, $selectedMachines);
        $p4 = in_array(4, $selectedMachines);

        // Determine last finish_time for start_time
        $lastJob = ProductionPlan::whereDate('plan_date', $date)
            ->where('shift_name', $shift)
            ->where('press_name', $press)
            ->where('row_type', 'job')
            ->orderBy('row_no', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob && $lastJob->finish_time) {
            $startTime = $lastJob->finish_time;
        } else {
            $startTime = str_contains(strtoupper($shift), 'MALAM') ? '21:00' : '07:30';
        }

        $plan = ProductionPlan::create([
            'line_master_id' => $lineId,
            'plan_date'      => $date,
            'shift_name'     => $shift,
            'press_name'     => $press,
            'row_no'         => $newRowNo,
            'row_type'       => 'job',
            'job_master'     => $jobMaster,
            'type_plt'       => $typePlt,
            'qty_plt'        => $qtyPlt,
            'total_plt'      => $qtyPlt > 0 && $planQty > 0 ? ceil($planQty / $qtyPlt) : 0,
            'job_no'         => $jobNo,
            'plan'           => $planQty,
            'total_mesin'    => $totalMesin,
            'p1'             => $p1,
            'p2'             => $p2,
            'p3'             => $p3,
            'p4'             => $p4,
            'ct_detik'       => $ctDetik,
            'process_time'   => $processTime,
            'reg_active'     => $regActive,
            'dct'            => $dct,
            'start_time'     => $startTime,
            'keterangan'     => $keterangan,
            'status'         => 'pending',
        ]);

        // Recalculate derived metrics using the now-persisted model
        $plan->process_time = ProductionMetricsService::calculateProcessTime($plan->ct_detik, $plan->plan);
        $plan->tpt          = ProductionMetricsService::planTptMinutes($plan);
        $plan->gsph_item    = ProductionMetricsService::gsph((int)$plan->plan, $plan->tpt);
        $plan->a1           = $plan->p1 ? $plan->tpt : 0;
        $plan->a2           = $plan->p2 ? $plan->tpt : 0;
        $plan->a3           = $plan->p3 ? $plan->tpt : 0;
        $plan->a4           = $plan->p4 ? $plan->tpt : 0;
        $plan->save();

        // Regenerate timeline
        try {
            $timelineGenerator = app(TimelineGenerationService::class);
            $timelineGenerator->regenerateForPlan($plan);
        } catch (\Throwable $e) {
            \Log::warning("Timeline regeneration after addJob: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil ditambahkan',
            'plan'    => $plan->fresh(),
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

    /**
     * Move one or more recovery items to a different shift, date, or press.
     * §4 SRS: Recovery harus dapat dipindahkan ke Shift/Hari lain.
     */
    public function movePlanItem(Request $request)
    {
        $request->validate([
            'plan_ids'     => 'required|array|min:1',
            'plan_ids.*'   => 'exists:production_plans,id',
            'target_date'  => 'required|date',
            'target_shift' => 'required|string|max:50',
            'target_press' => 'required|string|max:50',
        ]);

        $plans = ProductionPlan::whereIn('id', $request->plan_ids)->get();

        // Only allow moving recovery items
        foreach ($plans as $plan) {
            if ($plan->source_type !== 'recovery') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya item Recovery yang dapat dipindahkan ke shift/hari lain.',
                ], 422);
            }
        }

        // Track source sections for regeneration (deduplicate)
        $sourceSections = [];
        foreach ($plans as $plan) {
            $key = $plan->plan_date->format('Y-m-d') . '||' . $plan->shift_name . '||' . $plan->press_name;
            $sourceSections[$key] = [
                'date'  => $plan->plan_date->format('Y-m-d'),
                'shift' => $plan->shift_name,
                'press' => $plan->press_name,
            ];
        }

        $targetDate = $request->target_date;
        $targetShift = $request->target_shift;
        $targetPress = $request->target_press;

        $hari = \Carbon\Carbon::parse($targetDate)->locale('id')->isoFormat('dddd');

        // Get line_master_id for the target press
        $lineMap = LineMaster::pluck('id', 'line_name')->toArray();
        $lineId = null;
        $pressKey = strtoupper(str_replace([' ', '-', 'LINE'], '', $targetPress));
        foreach ($lineMap as $name => $id) {
            $cleanName = strtoupper(str_replace([' ', '-', 'LINE'], '', $name));
            if ($cleanName === $pressKey || str_contains($pressKey, $cleanName) || str_contains($cleanName, $pressKey)) {
                $lineId = $id;
                break;
            }
        }

        DB::transaction(function () use ($plans, $targetDate, $targetShift, $targetPress, $hari, $lineId) {
            foreach ($plans as $plan) {
                $plan->update([
                    'plan_date'      => $targetDate,
                    'shift_name'     => $targetShift,
                    'press_name'     => $targetPress,
                    'line_master_id' => $lineId ?? $plan->line_master_id,
                    'hari'           => $hari,
                    'row_no'         => 0,
                    'start_time'     => null,
                    'finish_time'    => null,
                ]);

                // Disassociate any recovery item so it stays in the queue
                if ($plan->recovery_id) {
                    \App\Models\RecoveryItem::where('id', $plan->recovery_id)
                        ->where('production_plan_id', $plan->id)
                        ->update(['production_plan_id' => null]);
                }
            }
        });

        // Regenerate all source sections and target section
        $timelineGenerator = app(TimelineGenerationService::class);
        foreach ($sourceSections as $section) {
            try {
                $timelineGenerator->regenerateSection($section['date'], $section['shift'], $section['press']);
            } catch (\Throwable $e) {
                \Log::warning('Resimulation after move (source): ' . $e->getMessage());
            }
        }
        try {
            $timelineGenerator->regenerateSection($targetDate, $targetShift, $targetPress);
        } catch (\Throwable $e) {
            \Log::warning('Resimulation after move (target): ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => count($plans) . ' item berhasil dipindahkan.',
        ]);
    }

    public function forceOverflow(Request $request)
    {
        $planIds = $request->input('plan_ids', []);
        $action  = $request->input('action'); // 'force_timeline' | 'to_recovery'

        if (empty($planIds)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada item dipilih.']);
        }

        $plans = ProductionPlan::whereIn('id', $planIds)->get();

        if ($action === 'force_timeline') {
            // Tetap di timeline: hapus flag cutoff, timing diatur ulang
            foreach ($plans as $plan) {
                $plan->update([
                    'start_time'  => null,
                    'finish_time' => null,
                ]);
            }
            // Trigger regenerate agar item di-schedule ulang
            $first = $plans->first();
            if ($first) {
                $timelineGenerator = app(TimelineGenerationService::class);
                $timelineGenerator->regenerateSection(
                    $first->plan_date,
                    $first->shift_name,
                    $first->press_name
                );
            }
            return response()->json([
                'success' => true,
                'message' => count($plans) . ' item tetap di timeline.',
            ]);
        }

        if ($action === 'to_recovery') {
            // Pindah ke recovery queue: ubah source_type
            foreach ($plans as $plan) {
                $plan->update([
                    'source_type' => 'recovery',
                    'start_time'  => null,
                    'finish_time' => null,
                    'row_no'      => null,
                ]);
            }
            $first = $plans->first();
            if ($first) {
                $timelineGenerator = app(TimelineGenerationService::class);
                $timelineGenerator->regenerateSection(
                    $first->plan_date,
                    $first->shift_name,
                    $first->press_name
                );
            }
            return response()->json([
                'success' => true,
                'message' => count($plans) . ' item dipindah ke recovery queue.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Action tidak dikenali.']);
    }
}

