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
        $date    = $rawDate ?: $maxDate;

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

        return view(
            'ppc.planning.production_plan',
            compact('plans', 'lines', 'date', 'currentPress', 'totalFinishRow', 'cardSummaries', 'currentShift', 'availableShifts', 'totalJobs', 'activeFilters', 'pendingRecoveries', 'pendingRecoveryItems')
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

                // Skip cleanup if there are IN_PRODUCTION recovery items on this date
                $hasInProduction = ProductionPlan::whereDate('plan_date', $parsedDate)
                    ->where('source_type', 'recovery')
                    ->whereHas('recoveryItem', function ($q) {
                        $q->where('status', 'in_production');
                    })
                    ->exists();

                if (!$hasInProduction) {
                    foreach (array_keys($uniqueShifts) as $shiftToDelete) {
                        ProductionPlan::whereDate('plan_date', $parsedDate)
                            ->where('shift_name', $shiftToDelete)
                            ->where('source_type', 'ppc')
                            ->delete();
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

                foreach ($sheets as $sheetData) {
                    $originalShift = $sheetData['shift_name'] ?? '';
                    $cleanShift = trim(ucwords(strtolower(preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', $originalShift))));
                    $groupKey = $cleanShift . '||' . ($sheetData['press_name'] ?? '');

                    // Skip non-REV sheet jika ada versi REV di grup yang sama
                    if (!preg_match('/REV|REVISI/i', $originalShift) && !empty($hasRevInGroup[$groupKey])) {
                        continue;
                    }
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

                    foreach ($sheetData['rows'] as $item) {
                        $rowType = $item['row_type'] ?? 'job';
                        $jn = strtoupper($item['job_no'] ?? '');
                        $jm = strtoupper($item['job_master'] ?? '');
                        
                        if ($rowType === 'note') {
                            // Keep as note
                        } else {
                            $isBreakDesc = false;
                            $breakKeywords = ['ISTIRAHAT', 'JUMAT', 'SORE', 'MALAM', 'CINGKORAK', 'BREAK', 'TOTAL FINISH', 'TOTAL FNISH', 'BREAKTI'];
                            foreach ($breakKeywords as $kw) {
                                if (str_contains($jn, $kw) || str_contains($jm, $kw)) {
                                    $isBreakDesc = true;
                                    break;
                                }
                            }
                        if ($isBreakDesc || $rowType === 'break') {
                            $rowType = 'break';
                        }
                        
                        // Extra check: if it's a known summary keyword but not a break, force to note
                            $noteKeywords = ['PLAN', 'TOTAL STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH', 'TOTAL PCS', 'DELETE PLAN SHIFT 1', 'TOTAL'];
                            $isNoteDesc = false;
                            foreach ($noteKeywords as $kw) {
                                if (str_replace(' ', '', $jn) === str_replace(' ', '', $kw) || str_replace(' ', '', $jm) === str_replace(' ', '', $kw)) {
                                    $isNoteDesc = true;
                                    break;
                                }
                            }
                            if ($isNoteDesc) {
                                $rowType = 'note';
                            }
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

                        $rows[] = [
                            'line_master_id' => $lineId,
                            'plan_date'      => $parsedDate,
                            'shift_name'     => $shiftName,
                            'press_name'     => $pressName,
                            'hari'           => $this->safeVal($sheetData['hari']),
                            'tgl'            => $this->safeVal($sheetData['tgl']),
                            'jam'            => $this->safeVal($sheetData['jam']),
                            'revisi'         => $this->safeVal($sheetData['revisi']),
                            'row_no'         => ($this->safeVal($item['row_no']) ?: 0) + $recoveryRowNo,
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

                    foreach (array_chunk($rows, 100) as $chunk) {
                        ProductionPlan::insert($chunk);
                    }

                    $imported += count($rows);
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
            // Hanya source_type='ppc' — jangan sentuh recovery items
            $oldPlansCount = ProductionPlan::whereDate('plan_date', '<', $parsedDate)
                ->where('source_type', 'ppc')
                ->delete();
            if ($oldPlansCount > 0) {
                \Log::info("Import cleanup: {$oldPlansCount} old PPC plans deleted (before {$parsedDate})");
            }

            @unlink($dataPath);

            $timelineGenerator = app(TimelineGenerationService::class);
            $sections = ProductionPlan::whereDate('plan_date', $parsedDate)
                ->select('shift_name', 'press_name')
                ->distinct()
                ->get();
            foreach ($sections as $section) {
                if ($section->shift_name) {
                    $timelineGenerator->regenerateSection(
                        $parsedDate,
                        $section->shift_name,
                        $section->press_name
                    );
                }
            }

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

        $count = $query->count();
        $query->delete();

        return redirect()->route('ppc.planning.production_plan', ['date' => $date])
            ->with('success', "Clear Data Selesai! $count record untuk tanggal $date" . ($shift ? " (Shift $shift)" : '') . " telah dihapus.");
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

        if (empty($itemIds)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada item yang dipilih.']);
        }

        $items = RecoveryItem::whereIn('id', $itemIds)
            ->where('status', 'waiting_approval')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Item tidak ditemukan atau sudah diproses.']);
        }

        // Approve selected items only — no cascade to other items in same schedule
        RecoveryItem::whereIn('id', $items->pluck('id'))
            ->update([
                'status' => 'approved',
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => count($items) . ' item berhasil di-approve.',
        ]);
    }



    public function recoveryHistory()
    {
        $rejectedItems = RecoveryItem::rejected()
            ->with('schedule')
            ->orderBy('original_date', 'desc')
            ->orderBy('press_name')
            ->get();

        return view('ppc.planning.recovery_history', compact('rejectedItems'));
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

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                ProductionPlan::where('id', $id)->update(['row_no' => $index + 1]);
            }
        });

        // Regenerate timeline so breaks and timing are recalculated
        $firstPlan = ProductionPlan::find($ids[0] ?? null);
        if ($firstPlan) {
            try {
                $timelineGenerator = app(TimelineGenerationService::class);
                $timelineGenerator->regenerateForPlan($firstPlan);
                ProductionPlanUpdated::dispatch($firstPlan->fresh(), 'row_no');
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
}
