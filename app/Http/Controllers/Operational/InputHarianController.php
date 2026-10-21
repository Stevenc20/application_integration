<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use Carbon\Carbon;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\LineMaster;
use App\Models\ProductionLog;
use App\Models\ShiftSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ProductionService;
use App\Services\DashboardRealtimeService;

class InputHarianController extends Controller
{
    protected ProductionService $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function saveProductionLog(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $workDate = $request->get('date') ?: now()->toDateString();
            $result = $this->productionService->saveProductionLog($id, $request->all(), $workDate);

            return response()->json([
                'success' => true,
                'message' => 'Log input saved',
                'total_ok' => $result['actualQty'],
                'efficiency' => $result['efficiency'],
                'runtime_seconds' => $result['runtime_seconds'] ?? 0,
                'log' => [
                    'time' => $result['log']->created_at->format('H:i'),
                    'ok' => $result['log']->ok_qty,
                    'repair' => $result['log']->repair_qty,
                    'reject' => $result['log']->reject_qty,
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $lineFilter = $request->get('line');
        if (empty($lineFilter) && Auth::check()) {
            $userRole = strtolower($request->user()?->role ?? '');
            if ($userRole === 'leader a') {
                $lineFilter = 'Line A';
            } elseif ($userRole === 'leader b') {
                $lineFilter = 'Line B';
            } elseif ($userRole === 'leader c') {
                $lineFilter = 'Line C';
            } elseif ($userRole === 'leader d') {
                $lineFilter = 'Line D';
            } elseif ($userRole === 'shearing') {
                $lineFilter = 'Shearing';
            } elseif ($userRole === 'handwork') {
                $lineFilter = 'Handwork';
            } elseif ($userRole === 'supervisor') {
                $lineFilter = 'Line A';
            }
            if ($lineFilter) {
                $request->merge(['line' => $lineFilter]);
            }
        }

        // LOGIKA TANGGAL PRODUKSI (Work Date)
        $hour = (int) now()->format('H');
        $date = $request->get('date') ?: (($hour < 7) ? now()->subDay()->toDateString() : now()->toDateString());

        // Smart fallback: jika auto-detected Malam+kemarin kosong, cek Pagi+hari ini
        // (supaya PPC pre-load jam 5 pagi langsung muncul tanpa ganti filter manual)
        if ($hour < 7 && !$request->has('shift')) {
            $autoShift = $this->getShift();
            $hasData = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $autoShift)
                ->exists();
            if (!$hasData) {
                $todayStr = now()->toDateString();
                $hasTodayPagi = \App\Models\ProductionPlan::whereDate('plan_date', $todayStr)
                    ->where('shift_name', 'like', 'Shift Pagi%')
                    ->exists();
                if ($hasTodayPagi) {
                    $date = $todayStr;
                    $currentShift = 'Shift Pagi';
                }
            }
        }

        // SAFEGUARD: hanya izinkan akses ke tanggal produksi aktif (hari ini atau kemarin jika sebelum jam 7)
        // Data tanggal lewat sudah otomatis di-cleanup oleh scheduler dan tersimpan di Production Analytics
        $activeDate = ($hour < 7) ? now()->subDay()->toDateString() : now()->toDateString();
        if ($request->has('date') && $request->get('date') < $activeDate) {
            return redirect()->route('operational.input_harian', array_merge(
                $request->except('date'),
                ['date' => $activeDate]
            ));
        }

        $lineFilter   = $request->get('line');
        $search       = trim($request->get('search', ''));
        
        // Pilih shift: dari request atau auto-detect
        $currentShift = $request->get('shift', $currentShift ?? 'Shift Pagi');

        // 1. Tentukan SHIFT TERBARU (Revisi Terakhir)
        $latestShiftName = $currentShift;
        if ($currentShift !== 'all') {
            $latestShiftName = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', 'like', "{$currentShift}%")
                ->orderByDesc('updated_at')
                ->value('shift_name') ?: $currentShift;

            // FORCE REDIRECT: Pastikan URL di device (HP/Laptop) selalu pake nama shift yang PALING BARU
            // Ini biar nggak ada "session drift" antara HP dan Laptop.
            if ($request->has('shift') && $request->get('shift') !== $latestShiftName && !str_contains($currentShift, 'REV')) {
                return redirect()->route('operational.input_harian', array_merge($request->query(), [
                    'shift' => $latestShiftName,
                ]));
            }
        }

        // SYNC: Pastikan JobMaster terupdate dari Jadwal
        $this->syncPlanToJobMaster($date, $currentShift);

        // 2. QUERY UTAMA: Langsung dari ProductionPlan (Source of Truth)
        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->whereIn('row_type', ['job', 'break'])
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

        if ($request->filled('status') && $request->status !== '') {
            $filterStatus = strtolower($request->status);
            if (in_array($filterStatus, ['completed', 'complete'])) {
                $planQuery->whereIn(DB::raw('LOWER(status)'), ['complete', 'completed', 'finished', 'closed', 'done']);
            } elseif ($filterStatus === 'running') {
                $planQuery->whereIn(DB::raw('LOWER(status)'), ['running', 'approved']);
            } elseif ($filterStatus === 'pending') {
                $planQuery->where(function($q) {
                    $q->where(DB::raw('LOWER(status)'), 'pending')
                      ->orWhereNull('status');
                });
            } else {
                $planQuery->where(DB::raw('LOWER(status)'), $filterStatus);
            }
        }

        if ($currentShift !== 'all') {
            $planQuery->where('shift_name', $latestShiftName);
        }

        // Filter Line (Flexible & Industrial Grade Normalization)
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            
            $planQuery->where(function($q) use ($normalized, $lineFilter) {
                $q->whereRaw("
                    REPLACE(
                        REPLACE(
                            UPPER(TRIM(COALESCE(press_name, ''))),
                            'PRESS ',
                            ''
                        ),
                        'LINE ',
                        ''
                    ) LIKE ?
                ", ["%{$normalized}%"])
                ->orWhere('press_name', 'LIKE', "%{$lineFilter}%");
            });
        }

        // Filter Search
        if ($search) {
            $planQuery->where(function($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                  ->orWhere('job_master', 'like', "%{$search}%");
            });
        }

        // 3. Ambil data dengan status realtime (Source of Truth: ProductionPlan status is synchronized by Service)
        // DISABLE PAGINATION temporarily for MES Stabilization as requested
        // INDUSTRIAL SORTING: Sort by original PPC upload order first (row_no)
        $plans = $planQuery->orderBy('press_name')->orderBy('row_no', 'asc')->get();

        // ── MERGE BREAK SPLITS ──
        $parentIds = $plans->pluck('id');
        $childPlans = \App\Models\ProductionPlan::whereIn('parent_job_id', $parentIds->filter())
            ->whereDate('plan_date', $date)
            ->where('shift_name', $latestShiftName)
            ->get()
            ->groupBy('parent_job_id');

        $plans = $plans->filter(fn ($p) => !$p->parent_job_id)->values();

        // MAPPING: Hubungkan setiap Plan ke JobMaster-nya (Optimized to fix N+1)
        $jobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();

        // Also include children's identifiers
        foreach ($childPlans as $parentId => $children) {
            foreach ($children as $child) {
                $jn = trim($child->job_no ?? '');
                $jm = trim($child->job_master ?? '');
                $jobNumbers[] = $jn ? ($jn . '-' . $child->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $child->id);
            }
        }

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with([
                'dailyProduction' => function ($q) use ($date) {
                    $q->where('work_date', $date);
                },
                'downtimes' => function ($q) {
                    // No date filter: job is already scoped to plan date via job_number
                },
                'dandoris',
            ])
            ->get()
            ->keyBy('job_number');

        // Merge children's production data into parent's JobMaster
        foreach ($childPlans as $parentId => $children) {
            $parent = $plans->firstWhere('id', (int) $parentId);
            if (!$parent) continue;
            $parentJm = $jobMasters->get(trim($parent->job_no ?? '') . '-' . $parent->id);
            if (!$parentJm) continue;
            foreach ($children as $child) {
                $childKey = trim($child->job_no ?? '') . '-' . $child->id;
                $childJm = $jobMasters->get($childKey);
                if (!$childJm || !$childJm->dailyProduction) continue;
                if (!$parentJm->dailyProduction) continue;
                $parentJm->dailyProduction->actual_ok     = ($parentJm->dailyProduction->actual_ok ?? 0) + ($childJm->dailyProduction->actual_ok ?? 0);
                $parentJm->dailyProduction->actual_repair = ($parentJm->dailyProduction->actual_repair ?? 0) + ($childJm->dailyProduction->actual_repair ?? 0);
                $parentJm->dailyProduction->actual_reject = ($parentJm->dailyProduction->actual_reject ?? 0) + ($childJm->dailyProduction->actual_reject ?? 0);
                foreach ($childJm->downtimes ?? [] as $dt) {
                    $parentJm->downtimes->push($dt);
                }
            }
        }

        // Load production sessions for the selected date to use date-specific timestamps
        $sessionMap = \App\Models\ProductionSession::whereIn(
            'job_master_id',
            $jobMasters->pluck('id')->filter()->unique()->values()->toArray()
        )
            ->whereDate('work_date', $date)
            ->get()
            ->keyBy('job_master_id');

        $plans->transform(function($plan) use ($jobMasters) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);
            $plan->job_data = $jobMasters->get($identifier);
            return $plan;
        });

        // DYNAMIC QUEUE REORDERING: Pin active 'Running' / 'Paused' job to top, rest maintain strict PPC row order
        $plans = $plans->sort(function($a, $b) {
            $statusA = in_array(strtolower($a->job_data?->status ?? $a->status ?? 'pending'), ['running', 'paused']) ? 0 : 1;
            $statusB = in_array(strtolower($b->job_data?->status ?? $b->status ?? 'pending'), ['running', 'paused']) ? 0 : 1;

            if ($statusA === $statusB) {
                return $a->row_no <=> $b->row_no;
            }
            return $statusA <=> $statusB;
        })->values();

        // Determine if all jobs are done (for showing "Akhiri Shift" button)
        $jobPlans = $plans->filter(fn($p) => ($p->row_type ?? 'job') === 'job');
        $allJobsDone = $jobPlans->isNotEmpty() && $jobPlans->every(
            fn($p) => in_array(strtolower(optional($p->job_data)->status ?? ''), ['complete', 'completed', 'finished', 'closed', 'done'])
        );

        // 4. DATE-AWARE ACTIVE JOB
        // Historical only when ALL job items in this date/shift have been processed (have DailyProduction).
        $isHistorical = false;
        if ($date !== now()->toDateString()) {
            $allPlans = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', $latestShiftName)
                ->where('row_type', 'job')
                ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                ->get(['id', 'job_no', 'job_master']);

            if ($allPlans->isNotEmpty()) {
                $jobNumbers = $allPlans->map(fn($p) => (trim($p->job_no ?? '') ?: 'AUTO-' . \Illuminate\Support\Str::slug($p->job_master ?? '')) . '-' . $p->id);
                $processedCount = \App\Models\JobMaster::whereIn('job_number', $jobNumbers)
                    ->whereHas('dailyProduction', fn($q) => $q->whereDate('work_date', $date))
                    ->count();
                $isHistorical = $processedCount === $allPlans->count();
            }
        }

        $activeJob = null;

        if ($request->filled('job_id')) {
            $requestedJob = JobMaster::with([
                'dailyProduction' => function ($q) use ($date) {
                    $q->where('work_date', $date);
                },
                'downtimes',
                'dandoris'
            ])->find($request->query('job_id'));

            if ($requestedJob) {
                $activeJob = $requestedJob;
            }
        }

        if (!$activeJob) {
            if ($isHistorical) {
                // Historical mode: cari job yang punya dailyProduction di tanggal ini
                $activeJobQuery = JobMaster::whereHas('dailyProduction', function ($q) use ($date) {
                    $q->where('work_date', $date);
                })->with([
                    'dailyProduction' => function ($q) use ($date) {
                        $q->where('work_date', $date);
                    },
                    'downtimes',
                ]);

                if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
                    $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
                    $activeJobQuery->where(function($lq) use ($normalizedLine, $lineFilter) {
                        $lq->whereRaw("UPPER(TRIM(line)) = ?", [strtoupper(trim($lineFilter))])
                           ->orWhereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"])
                           ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(line)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalizedLine}%"]);
                    });
                }

                $activeJob = $activeJobQuery->first();
            } else {
                // Today mode: cari job running realtime yang sesuai dengan jadwal aktif hari ini
                $scheduledJobNumbers = $plans->map(function($p) {
                    $jn = trim($p->job_no ?? '');
                    $jm = trim($p->job_master ?? '');
                    return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
                })->toArray();

                $baseActiveQuery = function() use ($lineFilter) {
                    $q = JobMaster::whereIn(DB::raw('LOWER(status)'), ['running', 'paused']);
                    if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
                        $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
                        $q->where(function($lq) use ($normalizedLine, $lineFilter) {
                            $lq->whereRaw("UPPER(TRIM(line)) = ?", [strtoupper(trim($lineFilter))])
                               ->orWhereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"])
                               ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(line)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalizedLine}%"]);
                        });
                    }
                    return $q;
                };

                // Self-healing Dandori: jika job sudah mulai produksi (ada actual_ok/repair/reject atau started_at),
                // pastikan open dandori yang terbengkalai otomatis tertutup agar job tidak dipaksa ke mode dandori.
                $hasProductionProgress = function($jm) use ($date) {
                    $dp = $jm->dailyProduction;
                    $actualTotal = ($dp?->actual_ok ?? 0) + ($dp?->actual_repair ?? 0) + ($dp?->actual_reject ?? 0);
                    return $actualTotal > 0 || !empty($jm->started_at);
                };

                // 1. Coba cari active job yang ada di jadwal $plans saat ini
                $activeJobs = collect();
                if (!empty($scheduledJobNumbers)) {
                    $activeJobs = $baseActiveQuery()
                        ->whereIn('job_number', $scheduledJobNumbers)
                        ->with([
                            'dailyProduction' => function ($q) use ($date) {
                                $q->where('work_date', $date);
                            },
                            'downtimes',
                            'dandoris'
                        ])
                        ->orderByRaw("CASE 
                            WHEN EXISTS (
                                SELECT 1 FROM downtimes 
                                WHERE downtimes.job_master_id = job_masters.id 
                                  AND downtimes.jenis_downtime = 'dandori' 
                                  AND downtimes.finish_time IS NULL
                            ) AND NOT EXISTS (
                                SELECT 1 FROM daily_productions 
                                WHERE daily_productions.job_master_id = job_masters.id 
                                  AND daily_productions.work_date = '{$date}' 
                                  AND (daily_productions.actual_ok > 0 OR daily_productions.actual_repair > 0 OR daily_productions.actual_reject > 0)
                            ) AND job_masters.started_at IS NULL THEN 0 ELSE 1 END")
                        ->orderByDesc('updated_at')
                        ->orderByDesc('id')
                        ->get();
                }

                // 2. Fallback: Jika filter status/search membuat $plans tidak memuat job yang running di line ini,
                // TETAP temukan active job di line ini agar operator console / active job board tidak hilang!
                if ($activeJobs->isEmpty()) {
                    $activeJobs = $baseActiveQuery()
                        ->with([
                            'dailyProduction' => function ($q) use ($date) {
                                $q->where('work_date', $date);
                            },
                            'downtimes',
                            'dandoris'
                        ])
                        ->orderByRaw("CASE 
                            WHEN EXISTS (
                                SELECT 1 FROM downtimes 
                                WHERE downtimes.job_master_id = job_masters.id 
                                  AND downtimes.jenis_downtime = 'dandori' 
                                  AND downtimes.finish_time IS NULL
                            ) AND NOT EXISTS (
                                SELECT 1 FROM daily_productions 
                                WHERE daily_productions.job_master_id = job_masters.id 
                                  AND daily_productions.work_date = '{$date}' 
                                  AND (daily_productions.actual_ok > 0 OR daily_productions.actual_repair > 0 OR daily_productions.actual_reject > 0)
                            ) AND job_masters.started_at IS NULL THEN 0 ELSE 1 END")
                        ->orderByDesc('updated_at')
                        ->orderByDesc('id')
                        ->get();
                }

                $activeJob = $activeJobs->first();

                // Self-healing jika job aktif ternyata sudah produksi namun ada dandori gantung
                if ($activeJob && $hasProductionProgress($activeJob)) {
                    $openDandoris = \App\Models\Downtime::where('job_master_id', $activeJob->id)
                        ->where('jenis_downtime', 'dandori')
                        ->whereNull('finish_time')
                        ->get();
                    if ($openDandoris->isNotEmpty()) {
                        $closeTime = $activeJob->started_at ?: now();
                        foreach ($openDandoris as $odt) {
                            $diffSec = abs(\Carbon\Carbon::parse($closeTime)->diffInSeconds(\Carbon\Carbon::parse($odt->start_time)));
                            $odt->update([
                                'finish_time' => $closeTime,
                                'duration_seconds' => $diffSec,
                            ]);
                        }
                    }
                }
            }
        }

        // Production Logs — always filtered by selected date
        $productionLogs = collect();
        $lastInputAt    = null;
        if ($activeJob) {
            $productionLogs = \App\Models\ProductionLog::where('job_master_id', $activeJob->id)
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            $lastInputAt = $productionLogs->first()?->created_at ?? ($activeJob->started_at ?? null);
        }

        // 5. Cari Job Pending untuk Queue Selector (Dropdown Standby)
        $scheduledJobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($p->job_master) . '-' . $p->id);
        })->toArray();

        $pendingJobs = JobMaster::whereIn(DB::raw('LOWER(status)'), ['pending', 'running'])
            ->whereIn('job_number', $scheduledJobNumbers)
            ->get()
            ->sortBy(function($job) use ($scheduledJobNumbers) {
                return array_search($job->job_number, $scheduledJobNumbers);
            })
            ->values();

        $scheduleContext = ($lineFilter ?: 'SEMUA LINE') . ' &bull; ' . strtoupper($currentShift === 'all' ? 'SEMUA SHIFT' : $currentShift);

        // DEBUG: Mastiin data yang dibaca beneran dari ProductionPlan terbaru
        if ($request->has('debug')) {
            dd([
                'Target Date' => $date,
                'Detected Shift' => $latestShiftName,
                'Plan Items (First 5)' => $plans->map(fn($p) => [
                    'row_no' => $p->row_no,
                    'job_no' => $p->job_no,
                    'job_master' => $p->job_master,
                    'shift' => $p->shift_name
                ])->take(5)->toArray()
            ]);
        }

        // Check if shift is locked (has been submitted & not cancelled)
        $isLocked = false;
        $prevShiftComment = null;
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            $lineMaster = LineMaster::whereRaw("
                REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
            ", ["%{$normalizedLine}%"])->first();
            if ($lineMaster) {
                $shiftVal = str_contains(strtoupper($currentShift), 'MALAM') ? 2 : 1;
                $isLocked = ShiftSubmission::where([
                    'line_id' => $lineMaster->id,
                    'work_date' => $date,
                    'shift' => $shiftVal,
                ])->whereNull('cancelled_at')->exists();

                // Revisi 4: show previous shift's leader comment on the next shift's Input Harian
                $prevShiftComment = null;
                if (strtoupper((string)$currentShift) !== 'ALL') {
                    $prevShiftName = str_contains(strtoupper($currentShift), 'MALAM') ? 'Shift Pagi' : 'Shift Malam';
                    $prevShiftVal = str_contains(strtoupper($prevShiftName), 'MALAM') ? 2 : 1;
                    $prevDate = $date;
                    if ($prevShiftVal === 2) {
                        // previous is Malam -> on yesterday's date
                        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
                    }
                    $prevShiftComment = ShiftSubmission::where('line_id', $lineMaster->id)
                        ->whereDate('work_date', $prevDate)
                        ->where('shift', $prevShiftVal)
                        ->whereNotNull('comment')
                        ->where('comment', '!=', '')
                        ->whereNull('cancelled_at')
                        ->with('submitter')
                        ->orderByDesc('submitted_at')
                        ->first();
                }
            }
        }

        return view('operational.input_harian', [
            'jobs'            => $plans, 
            'pendingJobs'     => $pendingJobs,
            'lines'           => collect([
                                    'Line A', 'Line B', 'Line C', 'Line D', 'Shearing', 'Handwork'
                                 ]),
            'activeJob'       => $activeJob,
            'productionLogs'  => $productionLogs,
            'lastInputAt'     => $lastInputAt,
            'currentShift'    => $currentShift,
            'date'            => $date,
            'isHistorical'    => $isHistorical,
            'isLocked'        => $isLocked,
            'allJobsDone'     => $allJobsDone,
            'sessionMap'      => $sessionMap,
            'scheduleContext' => $scheduleContext,
            'prevShiftComment' => $prevShiftComment,
            'lineFilter'      => $lineFilter,
        ]);
    }

    private function syncPlanToJobMaster(string $date, string $shift)
    {
        // Tentukan Shift Terbaru (Revisi Terakhir)
        $latestShiftName = $shift;
        if ($shift !== 'all') {
            $latestShiftName = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', 'like', "{$shift}%")
                ->orderByDesc('updated_at')
                ->value('shift_name') ?: $shift;
        }

        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'job')
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);
        
        if ($shift !== 'all') {
            $planQuery->where('shift_name', $latestShiftName);
        }
        
        $plans = $planQuery->orderBy('row_no')->get();

        // Batch fetch existing JobMasters to eliminate N+1 query overhead
        $identifiers = [];
        $validPlans = [];
        foreach ($plans as $seq => $plan) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            if (blank($jn) && blank($jm)) continue;
            if ($plan->row_type === 'break') continue;
            if (in_array($jn, ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])) continue;

            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . Str::slug($jm) . '-' . $plan->id);
            $identifiers[] = $identifier;
            $validPlans[] = ['plan' => $plan, 'seq' => $seq, 'identifier' => $identifier];
        }

        $existingJobMasters = \App\Models\JobMaster::whereIn('job_number', $identifiers)->get()->keyBy('job_number');

        foreach ($validPlans as $item) {
            $plan = $item['plan'];
            $seq = $item['seq'];
            $identifier = $item['identifier'];

            $existing = $existingJobMasters->get($identifier);

            $lineName = $plan->press_name ?: ($plan->line ?: 'PRESS A');
            $data = [
                'job_name'    => $plan->job_master ?: ($plan->job_no ?: 'UNKNOWN JOB'),
                'line'        => $lineName,
                'target_qty'  => (int) ($plan->plan ?? 0),
                'sequence_no' => $plan->row_no ?? ($seq + 1), // Gunakan row_no asli dari Excel
                'plan_start'  => $plan->start_time ? (str_contains($plan->start_time, '-') ? Carbon::parse($plan->start_time)->startOfMinute() : Carbon::parse($date . ' ' . $plan->start_time)->startOfMinute()) : null,
                'plan_end'    => $plan->finish_time ? (str_contains($plan->finish_time, '-') ? Carbon::parse($plan->finish_time)->startOfMinute() : Carbon::parse($date . ' ' . $plan->finish_time)->startOfMinute()) : null,
                'capacity'    => (int) ($plan->qty_plt ?? 0),
            ];

            if ($existing) {
                // Check if any attributes changed before updating DB
                $needsUpdate = false;
                foreach ($data as $k => $v) {
                    if ($existing->{$k} != $v) {
                        $needsUpdate = true;
                        break;
                    }
                }
                if ($needsUpdate) {
                    // Jangan paksa update status jika sudah jalan/selesai (Preserve state)
                    if (!in_array($existing->status, ['running', 'paused', 'complete', 'finished', 'closed'])) {
                        $existing->update($data);
                    } else {
                        $existing->update(array_diff_key($data, ['status' => '']));
                    }
                }
            } else {
                \App\Models\JobMaster::create(array_merge($data, [
                    'job_number' => $identifier,
                    'status'     => 'pending',
                ]));
            }
        // Note: Do NOT auto-cutoff running/paused jobs here to prevent terminating ongoing production
        // when the operator switches shifts, lines, or views.
    }
}

    public function start(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $this->productionService->startJob($id, $request->has('enqueue_only'));
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function enqueue(int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $workDate = now()->toDateString();
            $downtime = $this->productionService->startDandori($id, $workDate);
            return response()->json(['success' => true, 'downtime' => $downtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function startDandori(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $workDate = $request->get('date') ?: now()->toDateString();
            $downtime = $this->productionService->startDandori($id, $workDate);
            return response()->json(['success' => true, 'downtime' => $downtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finishDandori(Request $request, int|string $jobId)
    {
        $this->guardLockedShift($jobId);
        try {
            // Close any open 1st check before finishing dandori
            $this->productionService->finishFirstCheck($jobId);

            $workDate = $request->get('date') ?: now()->toDateString();
            $this->productionService->finishDandori($jobId, $workDate);

            // Clear cache was_in_first_check if any exists
            \Illuminate\Support\Facades\Cache::forget('was_in_first_check_' . $jobId);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function startFirstCheck(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $workDate = $request->get('date') ?: now()->toDateString();
            $dandori = $this->productionService->startFirstCheck($id, $workDate);
            return response()->json(['success' => true, 'dandori' => $dandori]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finishFirstCheck(int|string $jobId)
    {
        $this->guardLockedShift($jobId);
        try {
            $this->productionService->finishFirstCheck($jobId);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function pause(int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $runtime = $this->productionService->pauseJob($id);
            return response()->json(['success' => true, 'total_seconds' => $runtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function resume(int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $this->productionService->resumeJob($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function restart(int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $this->productionService->restartJob($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finish(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $nextJobId = $request->json('next_job_id') ?: $request->input('next_job_id');
            $skipIdle = filter_var($request->json('skip_idle') ?? $request->input('skip_idle', false), FILTER_VALIDATE_BOOLEAN);
            $finalOk = $request->json('ok_qty');
            $finalRepair = $request->json('repair_qty');
            $finalReject = $request->json('reject_qty');
            $result = $this->productionService->finishJob($id, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject);
            $runtime = is_array($result) ? ($result['runtime'] ?? 0) : $result;
            $recovery = is_array($result) ? ($result['recovery'] ?? null) : null;

            return response()->json([
                'success' => true,
                'runtime_seconds' => $runtime,
                'recovery' => $recovery,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to finish job $id: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan job: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
            ], 500);
        }
    }

    public function status(int|string $id)
    {
        $job = JobMaster::find($id);
        
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', now()->toDateString())
            ->first();

        // Use base total_seconds. Javascript will calculate and add the running diff.
        $baseSeconds = $session ? (int)$session->total_seconds : 0;

        return response()->json([
            'status'        => $job->status ?? 'pending',
            'total_seconds' => $baseSeconds,
            'start_time'    => ($job->status === 'running' && $session) ? Carbon::parse($session->start_time)->toIso8601String() : null,
        ]);
    }

    public function saveQty(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        try {
            $result = $this->productionService->saveDailyProduction($id, $request->all());
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'runtime_seconds' => $result['runtime'],
                'efficiency' => $result['efficiency']
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getShift()
    {
        $hour = (int) now()->format('H');

        // Shift Pagi: 07:00 - 19:00
        // Shift Malam: 19:00 - 07:00
        if ($hour >= 7 && $hour < 19) {
            return 'Shift Pagi';
        }

        return 'Shift Malam';
    }

    private function getShiftFromRequest()
    {
        return request()->header('X-Shift')
            ?: request('shift')
            ?: $this->getShift();
    }

    private function guardLockedShift(int|string $jobMasterId)
    {
        $jm = JobMaster::find($jobMasterId);
        if (!$jm || !$jm->line) return;

        // If job itself is already completed/finished/closed, reject modifications
        if (in_array(strtolower($jm->status), ['complete', 'finished', 'closed', 'done'])) {
            throw new \Exception("Job sudah selesai/tidak berjalan (Status: {$jm->status}). Data tidak dapat diubah.");
        }

        $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $jm->line)));
        $lineMaster = LineMaster::whereRaw("
            REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
        ", ["%{$normalized}%"])->first();
        if (!$lineMaster) return;

        $date = request()->header('X-Date') ?: request('date');
        $shift = request()->header('X-Shift') ?: request('shift');

        // Resolve from ProductionPlan if not provided in request
        if (!$date || !$shift) {
            $parts = explode('-', $jm->job_number);
            $planId = end($parts);
            if (is_numeric($planId)) {
                $plan = \App\Models\ProductionPlan::find($planId);
                if ($plan) {
                    $date = $date ?: ($plan->plan_date ? \Carbon\Carbon::parse($plan->plan_date)->toDateString() : null);
                    $shift = $shift ?: $plan->shift_name;
                }
            }
        }

        $date = $date ?: now()->toDateString();
        $shift = $shift ?: $this->getShiftFromRequest();
        $shiftVal = str_contains(strtoupper($shift), 'MALAM') ? 2 : 1;

        $locked = ShiftSubmission::where([
            'line_id' => $lineMaster->id,
            'work_date' => $date,
            'shift' => $shiftVal,
        ])->whereNull('cancelled_at')->exists();

        if ($locked) {
            throw new \Exception('Shift sudah dikunci. Data tidak dapat diubah.');
        }
    }

    public function nextList(int|string $id)
    {
        $current = JobMaster::find($id);

        if (!$current) {
            return response()->json([]);
        }

        $jobs = JobMaster::where('id', '!=', $id)
            ->whereIn('status', ['pending', 'paused'])
            ->orderBy('sequence_no')
            ->orderBy('job_number')
            ->get();

        $formatted = $jobs->map(function($j) {
            return [
                'id' => $j->id,
                'job_number' => $j->job_number,
                'job_name' => $j->job_name,
                'label' => "{$j->job_name} - {$j->job_number} (" . ($j->target_qty ?: 0) . " pcs)"
            ];
        });

        return response()->json($formatted);
    }
    
    public function nextProcess(Request $request, int|string $id)
    {
        $this->guardLockedShift($id);
        $nextJobId = $request->get('next_job_id');
        $skipIdle = filter_var($request->get('skip_idle', true), FILTER_VALIDATE_BOOLEAN);
        
        // 1. Finish the current job and optionally start the next one
        $this->productionService->finishJob($id, $nextJobId, $skipIdle);

        // 2. Get the next job
        $next = $this->productionService->getNextJob($id, $request->next_id);

        if ($next) {
            $this->productionService->startJob($next->id);

            return response()->json([
                'success' => true,
                'message' => 'Next process: '.$next->job_number,
                'next_id' => $next->id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua job selesai'
        ]);
    }


    public function activeJob(Request $request)
    {
        $lineFilter = $request->get('line');
        $date = $request->get('date') ?: now()->toDateString();
        $normalizedLine = '';
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
        }

        // Cari JobMaster yang sedang running atau paused di line ini
        $jmQuery = JobMaster::whereIn(DB::raw('LOWER(status)'), ['running', 'paused']);
        if ($normalizedLine) {
            $jmQuery->where(function($q) use ($normalizedLine) {
                $q->whereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"])
                  ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(line)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalizedLine}%"]);
            });
        }

        $hasProductionProgress = function($jm) use ($date) {
            $dp = $jm->dailyProduction;
            $actualTotal = ($dp?->actual_ok ?? 0) + ($dp?->actual_repair ?? 0) + ($dp?->actual_reject ?? 0);
            return $actualTotal > 0 || !empty($jm->started_at);
        };

        $jobMaster = $jmQuery->orderByRaw("CASE 
                WHEN EXISTS (
                    SELECT 1 FROM downtimes 
                    WHERE downtimes.job_master_id = job_masters.id 
                      AND downtimes.jenis_downtime = 'dandori' 
                      AND downtimes.finish_time IS NULL
                ) AND NOT EXISTS (
                    SELECT 1 FROM daily_productions 
                    WHERE daily_productions.job_master_id = job_masters.id 
                      AND daily_productions.work_date = '{$date}' 
                      AND (daily_productions.actual_ok > 0 OR daily_productions.actual_repair > 0 OR daily_productions.actual_reject > 0)
                ) AND job_masters.started_at IS NULL THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$jobMaster) {
            return response()->json(['running' => false]);
        }

        $session = ProductionSession::where('job_master_id', $jobMaster->id)
            ->orderByDesc('id')
            ->first();
        $isDandori = \App\Models\Downtime::where('job_master_id', $jobMaster->id)
            ->where('jenis_downtime', 'dandori')
            ->whereNull('finish_time')
            ->exists();

        // Self-healing: jika job sudah berproduksi tapi flag dandori masih kebuka, sembuhkan
        if ($isDandori && $hasProductionProgress($jobMaster)) {
            $openDandoris = \App\Models\Downtime::where('job_master_id', $jobMaster->id)
                ->where('jenis_downtime', 'dandori')
                ->whereNull('finish_time')
                ->get();
            $closeTime = $jobMaster->started_at ?: now();
            foreach ($openDandoris as $odt) {
                $diffSec = abs(\Carbon\Carbon::parse($closeTime)->diffInSeconds(\Carbon\Carbon::parse($odt->start_time)));
                $odt->update([
                    'finish_time' => $closeTime,
                    'duration_seconds' => $diffSec,
                ]);
            }
            $isDandori = false;
        }

        $activeDowntime = \App\Models\Downtime::where('job_master_id', $jobMaster->id)
            ->whereNull('finish_time')
            ->orderBy('created_at', 'desc')
            ->first();

        $urlParams = [];
        $parts = explode('-', $jobMaster->job_number);
        $planId = end($parts);
        $plan = null;
        if (is_numeric($planId)) {
            $plan = \App\Models\ProductionPlan::find($planId);
        }
        
        $rawLine = $plan ? $plan->press_name : $jobMaster->line;
        $lineParam = $rawLine;
        $upperLine = strtoupper(trim($rawLine));
        if ($upperLine === 'PRESS A' || $upperLine === 'A' || $upperLine === 'LINE A') {
            $lineParam = 'Line A';
        } elseif ($upperLine === 'PRESS B' || $upperLine === 'B' || $upperLine === 'LINE B') {
            $lineParam = 'Line B';
        } elseif ($upperLine === 'PRESS C' || $upperLine === 'C' || $upperLine === 'LINE C') {
            $lineParam = 'Line C';
        } elseif ($upperLine === 'PRESS D' || $upperLine === 'D' || $upperLine === 'LINE D') {
            $lineParam = 'Line D';
        } elseif ($upperLine === 'SHEARING') {
            $lineParam = 'Shearing';
        } elseif ($upperLine === 'HANDWORK') {
            $lineParam = 'Handwork';
        }

        $workDate = $session ? $session->work_date : now()->toDateString();
        if ($plan) {
            $urlParams = [
                'line'  => $lineParam,
                'shift' => $plan->shift_name,
                'date'  => $plan->plan_date ? ($plan->plan_date instanceof \Carbon\Carbon ? $plan->plan_date->toDateString() : (string)$plan->plan_date) : $workDate,
            ];
        } else {
            $urlParams = [
                'line' => $lineParam,
                'date' => $workDate,
            ];
        }

        $startTimeStr = ($session && $session->start_time) 
            ? \Carbon\Carbon::parse($session->start_time)->toIso8601String() 
            : ($jobMaster->started_at ? \Carbon\Carbon::parse($jobMaster->started_at)->toIso8601String() : now()->toIso8601String());

        return response()->json([
            'running' => true,
            'id' => $jobMaster->id,
            'status' => $jobMaster->status,
            'is_dandori' => $isDandori,
            'active_downtime_count' => $activeDowntime ? 1 : 0,
            'active_downtime_type' => $activeDowntime ? strtolower($activeDowntime->jenis_downtime) : null,
            'job_name' => $jobMaster->job_name,
            'job_number' => $jobMaster->job_number,
            'total_seconds' => $session ? $session->total_seconds : 0,
            'start_time' => $startTimeStr,
            'url' => route('operational.input_harian', $urlParams) . '#row-' . $jobMaster->id
        ]);
    }

    /*
    ====================================================
    DOWNTIME MANAGEMENT
    ====================================================
    */
    public function getDowntimes(int|string $job_id)
    {
        $downtimes = Downtime::where('job_master_id', $job_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($downtimes);
    }

    public function startDowntime(Request $request, int|string $job_id)
    {
        $this->guardLockedShift($job_id);
        try {
            $downtime = $this->productionService->startDowntime($job_id, $request->all());
            if (!$downtime) {
                return response()->json([
                    'success' => false,
                    'skipped' => true,
                    'message' => 'Downtime tidak dapat dimulai (downtime lain sedang aktif).'
                ], 200);
            }
            return response()->json([
                'success' => true,
                'downtime' => $downtime
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finishDowntime(int|string $id)
    {
        try {
            $dt = Downtime::find($id);
            if ($dt) $this->guardLockedShift($dt->job_master_id);
            $downtime = $this->productionService->finishDowntime($id);
            if (!$downtime) {
                return response()->json(['success' => false, 'message' => 'Downtime not found']);
            }
            return response()->json([
                'success' => true,
                'downtime' => $downtime
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateDowntime(Request $request, int|string $id)
    {
        $downtime = Downtime::find($id);
        if (!$downtime) return response()->json(['success' => false, 'message' => 'Downtime not found']);

        try {
            $this->guardLockedShift($downtime->job_master_id);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        $downtime->update($request->only(['jenis_downtime', 'problem', 'penyebab', 'action', 'pic']));

        $this->productionService->syncHambatanJalur($downtime);

        $job = \App\Models\JobMaster::find($downtime->job_master_id);
        if ($job && $job->line) {
            DashboardRealtimeService::signalUpdate($job->line);
        }

        return response()->json([
            'success' => true,
            'downtime' => $downtime
        ]);
    }

    public function deleteDowntime(int|string $id)
    {
        try {
            $dt = Downtime::find($id);
            if ($dt) $this->guardLockedShift($dt->job_master_id);
            $success = $this->productionService->deleteDowntime($id);
            if (!$success) {
                return response()->json(['success' => false, 'message' => 'Downtime not found']);
            }
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function showLogs(int|string $id)
    {
        $job = JobMaster::with(['dailyProduction', 'downtimes'])->findOrFail($id);
        $logs = ProductionLog::where('job_master_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('operational.log_detail', compact('job', 'logs'));
    }

    public function getQty(Request $request, int|string $id)
    {
        $date = $request->get('date') ?: now()->toDateString();
        $daily = DailyProduction::where('job_master_id', $id)
            ->where('work_date', $date)
            ->first();

        return response()->json([
            'success'       => true,
            'actual_ok'     => $daily->actual_ok ?? 0,
            'actual_repair' => $daily->actual_repair ?? 0,
            'actual_reject' => $daily->actual_reject ?? 0,
        ]);
    }

    public function submitShift(Request $request, int|string $lineId)
    {
        try {
            $date = $request->get('date', now()->toDateString());
            $comment = $request->input('comment');
            [$lineMaster, $shiftMasterId, $shiftName, $shiftValue] = $this->resolveSubmitContext($request, $lineId);

            // Idempotency: if already submitted and NOT cancelled for this line+date+shift, short-circuit.
            // A cancelled submission may be resubmitted (re-running the cut-off) exactly so the
            // leader can fix data after a correction.
            $existingQuery = \App\Models\ShiftSubmission::where('line_id', $lineMaster->id)
                ->whereDate('work_date', $date);

            if ($shiftMasterId) {
                $existingQuery->where('shift_master_id', $shiftMasterId);
            } else {
                $existingQuery->where('shift', $shiftValue);
            }

            $existing = $existingQuery->first();

            if ($existing && !$existing->cancelled_at) {
                return response()->json([
                    'success' => true,
                    'message' => 'Shift sudah disubmit sebelumnya (Idempotent).',
                ]);
            }

            // ── VALIDATION: Pastikan semua downtime, repair, dan reject terisi lengkap sebelum shift berakhir ──
            $normalizedPress = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineMaster->line_name)));

            // 1. Tentukan SHIFT TERBARU (Revisi Terakhir) yang cocok dengan shift ini
            $latestShiftName = $shiftName;
            if ($shiftName && !str_contains($shiftName, 'all')) {
                $latestShiftName = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                    ->where('shift_name', 'like', "{$shiftName}%")
                    ->orderByDesc('updated_at')
                    ->value('shift_name') ?: $shiftName;
            }

            // 2. Query ProductionPlan KHUSUS jadwal shift dan tanggal saat ini untuk line ini
            $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('row_type', 'job')
                ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

            if ($latestShiftName && !str_contains($latestShiftName, 'all')) {
                $planQuery->where('shift_name', $latestShiftName);
            }

            $planQuery->whereRaw("
                REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
            ", ["%{$normalizedPress}%"]);

            $plans = $planQuery->get();

            $issues = [
                'dt'     => [],
                'repair' => [],
                'reject' => [],
                'remain' => [],
            ];

            $review = [
                'repair' => [],
                'reject' => [],
            ];

            if ($plans->isNotEmpty()) {
                $parentPlans = $plans->filter(fn($p) => !$p->parent_job_id);
                $parentIdentifiers = $parentPlans->map(function($p) {
                    $jn = trim($p->job_no ?? '');
                    $jm = trim($p->job_master ?? '');
                    if (blank($jn) && blank($jm)) return null;
                    return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
                })->filter()->values()->toArray();

                $rawParentJobNos = $parentPlans->pluck('job_no')->filter()->values()->toArray();

                $parentJobMasters = \App\Models\JobMaster::where(function($q) use ($parentIdentifiers, $rawParentJobNos) {
                        $q->whereIn('job_number', $parentIdentifiers);
                        if (!empty($rawParentJobNos)) {
                            $q->orWhereIn('job_number', $rawParentJobNos);
                        }
                    })
                    ->with([
                        'dailyProduction' => function ($q) use ($date) {
                            $q->whereDate('work_date', $date);
                        },
                        'downtimes' => function ($q) use ($date) {
                            $q->whereDate('start_time', $date);
                        },
                        'repairRejects' => function ($q) use ($date) {
                            $q->whereDate('created_at', $date);
                        },
                    ])
                    ->get();

                $parentJobMastersByIdentifier = $parentJobMasters->keyBy('job_number');

                $parentIds = $parentPlans->pluck('id')->filter()->values()->toArray();
                $childPlansMap = \App\Models\ProductionPlan::whereIn('parent_job_id', $parentIds)
                    ->get()
                    ->groupBy('parent_job_id');

                $childIdentifiers = $childPlansMap->flatten()->map(function($c) {
                    $cjn = trim($c->job_no ?? '');
                    return $cjn ? ($cjn . '-' . $c->id) : null;
                })->filter()->values()->toArray();

                $childJobMasters = \App\Models\JobMaster::whereIn('job_number', $childIdentifiers)
                    ->with([
                        'dailyProduction' => function ($q) use ($date) {
                            $q->whereDate('work_date', $date);
                        },
                        'downtimes' => function ($q) use ($date) {
                            $q->whereDate('start_time', $date);
                        },
                        'repairRejects' => function ($q) use ($date) {
                            $q->whereDate('created_at', $date);
                        },
                    ])
                    ->get()
                    ->keyBy('job_number');

                $allDailyJmIds = $parentJobMasters->pluck('id')
                    ->merge($childJobMasters->pluck('id'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $dailyForDate = \App\Models\DailyProduction::whereIn('job_master_id', $allDailyJmIds)
                    ->whereDate('work_date', $date)
                    ->orderBy('id', 'desc')
                    ->get()
                    ->keyBy('job_master_id');

                // ── VALIDATION: Kumpulkan JobMaster yang sesuai jadwal shift ini saja ──
                $allJmsToValidate = collect();

                foreach ($plans as $plan) {
                    if ($plan->parent_job_id) continue;

                    $jn = trim($plan->job_no ?? '');
                    $jm = trim($plan->job_master ?? '');
                    if (blank($jn) && blank($jm)) continue;
                    $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);

                    $jobMaster = $parentJobMastersByIdentifier->get($identifier)
                        ?? $parentJobMasters->first(fn($j) => $jn && $j->job_number === $jn)
                        ?? $parentJobMasters->first(fn($j) => $jn && str_starts_with($j->job_number, $jn . '-'))
                        ?? $parentJobMasters->first(fn($j) => $jm && $j->job_name === $jm);

                    if (!$jobMaster) {
                        $jobMaster = \App\Models\JobMaster::where('job_number', $identifier)
                            ->orWhere('job_number', $jn)
                            ->orWhere('job_number', 'like', $jn . '-%')
                            ->with([
                                'dailyProduction' => function ($q) use ($date) {
                                    $q->whereDate('work_date', $date);
                                },
                                'downtimes' => function ($q) use ($date) {
                                    $q->whereDate('start_time', $date);
                                },
                                'repairRejects' => function ($q) use ($date) {
                                    $q->whereDate('created_at', $date);
                                },
                            ])
                            ->first();
                    }

                    if ($jobMaster) {
                        $jobMaster->validation_item_name = $plan->job_no ?: $plan->job_master;
                        $jobMaster->validation_plan_id = $plan->id;
                        $allJmsToValidate->put($jobMaster->id, $jobMaster);
                    }

                    $children = $childPlansMap->get($plan->id, collect());
                    foreach ($children as $child) {
                        $childKey = trim($child->job_no ?? '') . '-' . $child->id;
                        $childJm = $childJobMasters->get($childKey);
                        if ($childJm) {
                            $childJm->validation_item_name = $child->job_no ?: $child->job_master;
                            $childJm->validation_plan_id = $child->id;
                            $allJmsToValidate->put($childJm->id, $childJm);
                        }
                    }
                }

                // ── VALIDATION LOOP: Periksa downtime, repair, reject ──
                foreach ($allJmsToValidate as $jm) {
                    $itemName = $jm->validation_item_name ?? ($jm->job_name ?: $jm->job_number);
                    $planId = $jm->validation_plan_id ?? 0;

                    foreach ($jm->downtimes ?? [] as $dt) {
                        if ($dt->start_time && \Carbon\Carbon::parse($dt->start_time)->toDateString() !== $date) {
                            continue;
                        }
                        if (in_array(trim($dt->jenis_downtime ?? ''), ['dandori', 'idle time', 'idle', 'break time'])) {
                            continue;
                        }
                        if (blank($dt->problem) || blank($dt->penyebab) || blank($dt->action)) {
                            $issues['dt'][] = [
                                'item'          => $itemName,
                                'issue'         => 'problem/penyebab/action belum lengkap',
                                'dt_id'         => $dt->id,
                                'job_master_id' => $jm->id,
                                'plan_id'       => $planId,
                            ];
                        }
                    }

                    foreach ($jm->repairRejects ?? [] as $rr) {
                        if (blank($rr->area_problem) || blank($rr->root_cause) || blank($rr->countermeasure)) {
                            $key = $rr->type === 'reject' ? 'reject' : 'repair';
                            $issues[$key][] = [
                                'item'          => $itemName,
                                'issue'         => 'area_problem/root_cause/countermeasure belum lengkap',
                                'job_master_id' => $jm->id,
                                'plan_id'       => $planId,
                            ];
                        }
                    }

                    // Ambil actual repair & reject khusus work_date jadwal shift ini
                    $daily = $dailyForDate->get($jm->id);

                    $repairQty = (int)($daily->actual_repair ?? 0);
                    $rejectQty = (int)($daily->actual_reject ?? 0);

                    // Fallback jika belum tersinkron di DailyProduction tapi sudah ada di ProductionLog pada tanggal shift ini
                    if ($repairQty === 0) {
                        $repairQty = (int) \App\Models\ProductionLog::where('job_master_id', $jm->id)
                            ->whereDate('created_at', $date)
                            ->sum('repair_qty');
                    }
                    if ($rejectQty === 0) {
                        $rejectQty = (int) \App\Models\ProductionLog::where('job_master_id', $jm->id)
                            ->whereDate('created_at', $date)
                            ->sum('reject_qty');
                    }

                    // Cek form detail Repair & Reject yang tersimpan
                    $rrLogs = $jm->repairRejects ?? \App\Models\RepairRejectLog::where('job_master_id', $jm->id)
                        ->whereDate('created_at', $date)
                        ->get();
                    $totalRepairLogged = (int) $rrLogs->where('type', 'repair')->sum('qty_a');
                    $totalRejectLogged = (int) $rrLogs->where('type', 'reject')->sum('qty_a');

                    if ($repairQty > 0 || $totalRepairLogged > 0) {
                        $review['repair'][] = [
                            'item'          => $itemName,
                            'issue'         => $totalRepairLogged >= $repairQty
                                ? $totalRepairLogged . ' pcs sudah ada form lengkap'
                                : max(0, $repairQty - $totalRepairLogged) . ' pcs Repair belum ada form (Lapor: ' . $repairQty . ', Form: ' . $totalRepairLogged . ')',
                            'status'        => $totalRepairLogged >= $repairQty ? 'ok' : 'incomplete',
                            'qty'           => $repairQty,
                            'logged'        => $totalRepairLogged,
                            'job_master_id' => $jm->id,
                            'plan_id'       => $planId,
                        ];
                    }

                    if ($rejectQty > 0 || $totalRejectLogged > 0) {
                        $review['reject'][] = [
                            'item'          => $itemName,
                            'issue'         => $totalRejectLogged >= $rejectQty
                                ? $totalRejectLogged . ' pcs sudah ada form lengkap'
                                : max(0, $rejectQty - $totalRejectLogged) . ' pcs Reject belum ada form (Lapor: ' . $rejectQty . ', Form: ' . $totalRejectLogged . ')',
                            'status'        => $totalRejectLogged >= $rejectQty ? 'ok' : 'incomplete',
                            'qty'           => $rejectQty,
                            'logged'        => $totalRejectLogged,
                            'job_master_id' => $jm->id,
                            'plan_id'       => $planId,
                        ];
                    }

                    if ($repairQty > 0) {
                        if ($totalRepairLogged < $repairQty) {
                            $missingRepair = $repairQty - $totalRepairLogged;
                            $issues['repair'][] = [
                                'item'          => $itemName,
                                'issue'         => $missingRepair . ' pcs Repair belum ada form detail (Lapor: ' . $repairQty . ', Form: ' . $totalRepairLogged . ')',
                                'job_master_id' => $jm->id,
                                'plan_id'       => $planId,
                            ];
                        }
                    }

                    if ($rejectQty > 0) {
                        if ($totalRejectLogged < $rejectQty) {
                            $missingReject = $rejectQty - $totalRejectLogged;
                            $issues['reject'][] = [
                                'item'          => $itemName,
                                'issue'         => $missingReject . ' pcs Reject belum ada form detail (Lapor: ' . $rejectQty . ', Form: ' . $totalRejectLogged . ')',
                                'job_master_id' => $jm->id,
                                'plan_id'       => $planId,
                            ];
                        }
                    }
                }

                if (!empty($issues['dt']) || !empty($issues['repair']) || !empty($issues['reject'])) {
                    return response()->json([
                        'success'    => false,
                        'has_issues' => true,
                        'issues'     => $issues,
                    ], 422);
                }
            }

            // ── REVIEW: minta konfirmasi item Repair/Reject shift ini sebelum finalisasi ──
            if (!$request->boolean('confirm_review')
                && (!empty($review['repair']) || !empty($review['reject']))) {
                return response()->json([
                    'success'     => false,
                    'needs_review' => true,
                    'review'      => $review,
                ], 200);
            }

            $cutoffAt = now();
            $cutoffStats = ['created' => 0];

            // All writes in one transaction; if cut-off fails, everything rolls back.
            DB::transaction(function () use ($cutoffAt, $date, $shiftName, $lineMaster, $shiftMasterId, $shiftValue, $existing, $comment, &$cutoffStats) {
                $cutOffService = app(\App\Services\CutOffService::class);
                $cutoffStats = $cutOffService->processCutOff($date, $shiftName, $lineMaster->line_name);

                if ($existing) {
                    // Resubmit after a previous cancellation: reactivate the same row so the
                    // unique (line_id, work_date, shift_master_id) constraint is preserved.
                    // cancel_count is intentionally NOT reset: only one cancellation per shift.
                    $existing->update([
                        'submitted_at' => $cutoffAt,
                        'submitted_by' => Auth::id(),
                        'comment'      => $comment,
                        'cancelled_at' => null,
                        'cancelled_by' => null,
                    ]);
                } else {
                    \App\Models\ShiftSubmission::create([
                        'line_id'         => $lineMaster->id,
                        'work_date'       => $date,
                        'shift'           => $shiftValue,
                        'shift_master_id' => $shiftMasterId,
                        'submitted_at'    => $cutoffAt,
                        'submitted_by'    => Auth::id(),
                        'comment'         => $comment,
                    ]);
                }
            });

            Log::info("[SHIFT SUBMIT] Shift submitted", [
                'line_id' => $lineMaster->id,
                'date' => $date,
                'shift' => $shiftName,
                'by' => Auth::id(),
                'resubmit_after_cancel' => (bool) ($existing && $existing->cancelled_at),
                'comment' => $comment,
                'recovered' => $cutoffStats['created'] ?? 0,
            ]);

            return response()->json([
                'success'   => true,
                'message'   => $existing ? 'Shift berhasil disubmit ulang!' : 'Shift berhasil disubmit!',
                'recovered' => $cutoffStats['created'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('[SHIFT SUBMIT] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an end-of-shift submission. Allowed exactly once per shift.
     *
     * Deletes the RecoveryItems that the cut-off created at submit time for this
     * line's press, unlocks the shift (edits become allowed again), and lets the
     * leader resubmit afterwards. The cut-off is only effective once the shift is
     * actually resubmitted/finalized.
     */
    public function cancelShift(Request $request, int|string $lineId)
    {
        try {
            $date = $request->get('date', now()->toDateString());
            $comment = $request->input('comment');
            [$lineMaster, $shiftMasterId, $shiftName, $shiftValue] = $this->resolveSubmitContext($request, $lineId);

            $submissionQuery = \App\Models\ShiftSubmission::where('line_id', $lineMaster->id)
                ->whereDate('work_date', $date)
                ->whereNull('cancelled_at');

            if ($shiftMasterId) {
                $submissionQuery->where('shift_master_id', $shiftMasterId);
            } else {
                $submissionQuery->where('shift', $shiftValue);
            }

            $submission = $submissionQuery->first();

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shift belum disubmit, tidak dapat dibatalkan.',
                ], 422);
            }

            if ($submission->cancel_count >= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembatalan hanya dapat dilakukan satu kali per shift.',
                ], 403);
            }

            // Delete recovery items created by the cut-off at this submission, scoped to
            // the line's press so other lines' recovery data is untouched.
            $normalizedPress = strtoupper(trim(str_replace(['Press ', 'PRESS ', 'Line ', 'LINE '], '', $lineMaster->line_name)));

            $affectedSchedules = \App\Models\RecoveryItem::whereDate('source_date', $date)
                ->where('source_shift', 'like', $shiftName . '%')
                ->whereNotNull('queued_at')
                ->where('queued_at', $submission->submitted_at)
                ->whereRaw("
                    REPLACE(
                        REPLACE(
                            UPPER(TRIM(press_name)),
                            'PRESS ',
                            ''
                        ),
                        'LINE ',
                        ''
                    ) = ?
                ", [$normalizedPress])
                ->pluck('recovery_schedule_id')
                ->unique()
                ->values();

            \App\Models\RecoveryItem::whereIn('recovery_schedule_id', $affectedSchedules)
                ->whereDate('source_date', $date)
                ->where('source_shift', 'like', $shiftName . '%')
                ->whereNotNull('queued_at')
                ->where('queued_at', $submission->submitted_at)
                ->whereRaw("
                    REPLACE(
                        REPLACE(
                            UPPER(TRIM(press_name)),
                            'PRESS ',
                            ''
                        ),
                        'LINE ',
                        ''
                    ) = ?
                ", [$normalizedPress])
                ->delete();

            // Clean up schedules that became empty for this press/date/shift.
            $emptySchedules = \App\Models\RecoverySchedule::whereIn('id', $affectedSchedules)
                ->whereDoesntHave('items')
                ->get();
            foreach ($emptySchedules as $schedule) {
                $schedule->delete();
            }

            $submission->update([
                'comment'      => $comment ?: $submission->comment,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancel_count' => $submission->cancel_count + 1,
            ]);

            Log::info("[SHIFT CANCEL] Shift submission cancelled", [
                'line_id' => $lineMaster->id,
                'date' => $date,
                'shift' => $shiftName,
                'by' => Auth::id(),
                'submission_id' => $submission->id,
                'recovery_schedules_cleaned' => $emptySchedules->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Akhiri Shift dibatalkan. Data dapat diperbaiki dan disubmit ulang.',
            ]);
        } catch (\Throwable $e) {
            Log::error('[SHIFT CANCEL] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve line master, shift master id, shift name and shift value (1=Pagi, 2=Malam)
     * shared by submitShift and cancelShift. Returns [LineMaster, ?int, string, int].
     */
    private function resolveSubmitContext(Request $request, int|string $lineId): array
    {
        $shiftMasterId = $request->input('shift_master_id');
        $shiftName = $request->input('shift');

        $shiftValue = 1;
        if ($shiftMasterId) {
            $masterShift = \App\Models\MasterShift::find($shiftMasterId);
            if (!$masterShift) {
                throw new \Exception('Shift master tidak ditemukan.');
            }
            $shiftName = $masterShift->name;
            $shiftValue = stripos($shiftName, 'malam') !== false ? 2 : 1;
        } elseif (!$shiftName) {
            throw new \Exception('Shift wajib diisi (shift atau shift_master_id).');
        } else {
            $shiftValue = stripos($shiftName, 'malam') !== false ? 2 : 1;
        }

        $lineMaster = is_numeric($lineId)
            ? \App\Models\LineMaster::find((int)$lineId)
            : null;

        if (!$lineMaster) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineId)));
            $lineMaster = \App\Models\LineMaster::whereRaw("
                REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
            ", ["%{$normalized}%"])->first();
        }
        if (!$lineMaster) {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineId)));
            $lineMaster = \App\Models\LineMaster::where('line_name', 'LIKE', "%{$normalized}%")->first();
        }
        if (!$lineMaster) {
            throw new \Exception("Line '{$lineId}' tidak ditemukan.");
        }

        return [$lineMaster, $shiftMasterId, $shiftName, $shiftValue];
    }

    public function productionAudit()
    {
        // Get all jobs that have started (meaning they have production data)
        $jobs = JobMaster::whereNotNull('started_at')
            ->with(['dailyProduction', 'productionLogs'])
            ->orderBy('started_at', 'desc')
            ->paginate(15);

        return view('operational.production_audit', compact('jobs'));
    }
}