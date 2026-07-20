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
use Illuminate\Support\Facades\DB;
use App\Services\ProductionService;
use App\Services\DashboardRealtimeService;

class InputHarianController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function saveProductionLog(Request $request, $id)
    {
        $this->guardLockedShift($id);
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
    }

    public function index(Request $request)
    {
        $lineFilter = $request->get('line');
        if (empty($lineFilter) && auth()->check()) {
            $userRole = strtolower(auth()->user()->role);
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

        // SYNC: Pastikan JobMaster terupdate dari Jadwal (cached 60s supaya tidak O(N) write tiap reload)
        $syncCacheKey = 'sync_plan_' . $date . '_' . \Illuminate\Support\Str::slug($currentShift);
        if (!\Illuminate\Support\Facades\Cache::has($syncCacheKey)) {
            $this->syncPlanToJobMaster($date, $currentShift);
            \Illuminate\Support\Facades\Cache::put($syncCacheKey, true, 60);
        }

        // 2. QUERY UTAMA: Langsung dari ProductionPlan (Source of Truth)
        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->whereIn('row_type', ['job', 'break'])
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

        if ($request->filled('status') && $request->status !== '') {
            $planQuery->where(DB::raw('LOWER(status)'), strtolower($request->status));
        }

        if ($currentShift !== 'all') {
            $planQuery->where('shift_name', $latestShiftName);
        }

        // Filter Line (Flexible & Industrial Grade Normalization)
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            
            $planQuery->whereRaw("
                REPLACE(
                    REPLACE(
                        UPPER(TRIM(press_name)),
                        'PRESS ',
                        ''
                    ),
                    'LINE ',
                    ''
                ) LIKE ?
            ", ["%{$normalized}%"]);
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
                'downtimes' => function ($q) use ($date) {
                    $q->where(function ($w) use ($date) {
                        $w->whereDate('start_time', $date)
                          ->orWhereNull('finish_time');
                    });
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

        // DYNAMIC QUEUE REORDERING: Pin active 'Running' job to top, rest maintain strict PPC row order
        $plans = $plans->sort(function($a, $b) {
            $statusA = strtolower($a->job_data?->status ?? $a->status ?? 'pending') === 'running' ? 0 : 1;
            $statusB = strtolower($b->job_data?->status ?? $b->status ?? 'pending') === 'running' ? 0 : 1;

            if ($statusA === $statusB) {
                return $a->row_no <=> $b->row_no;
            }
            return $statusA <=> $statusB;
        })->values();

        // Determine if all jobs are done (for showing "Akhiri Shift" button)
        $jobPlans = $plans->filter(fn($p) => ($p->row_type ?? 'job') === 'job');
        $allJobsDone = $jobPlans->isNotEmpty() && $jobPlans->every(
            fn($p) => optional($p->job_data)->status === 'complete'
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
                $activeJobQuery->whereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"]);
            }

            $activeJob = $activeJobQuery->first();
        } else {
            // Today mode: cari job running realtime
            $activeJob = JobMaster::where(DB::raw('LOWER(status)'), 'running');

            if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
                $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
                $activeJob->whereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"]);
            }

            $activeJob = $activeJob->with([
                    'dailyProduction' => function ($q) use ($date) {
                        $q->where('work_date', $date);
                    },
                    'downtimes',
                    'dandoris'
                ])->first();
        }

        // Production Logs — always filtered by selected date
        $productionLogs = collect();
        $lastInputAt    = null;
        if ($activeJob) {
            $productionLogs = \App\Models\ProductionLog::where('job_master_id', $activeJob->id)
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();
            $lastInputAt = $productionLogs->first()?->created_at ?? ($activeJob->started_at ?? null);
        }

        // 5. Cari Job Pending untuk Queue Selector (Dropdown Standby)
        $scheduledJobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($p->job_master) . '-' . $p->id);
        })->toArray();

        $pendingJobs = JobMaster::whereIn(DB::raw('LOWER(status)'), ['pending', 'running', 'paused'])
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

        // Check if shift is locked (has been submitted)
        $isLocked = false;
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
                ])->exists();
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
            'scheduleContext' => $scheduleContext
        ]);
    }

    private function syncPlanToJobMaster($date, $shift)
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

        foreach ($plans as $seq => $plan) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            
            if (blank($jn) && blank($jm)) continue;
            if ($plan->row_type === 'break') continue;
            if (in_array($jn, ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])) continue;

            // Pastikan identifier UNIK per baris rencana (untuk support split production)
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . Str::slug($jm) . '-' . $plan->id);

            $existing = \App\Models\JobMaster::where('job_number', $identifier)->first();

            $data = [
                'job_name'    => $plan->job_master ?: ($plan->job_no ?: 'UNKNOWN JOB'),
                'line'        => $plan->press_name ?? 'PRESS A',
                'target_qty'  => (int) ($plan->plan ?? 0),
                'sequence_no' => $plan->row_no ?? ($seq + 1), // Gunakan row_no asli dari Excel
                'plan_start'  => $plan->start_time ? (str_contains($plan->start_time, '-') ? Carbon::parse($plan->start_time)->startOfMinute() : Carbon::parse($date . ' ' . $plan->start_time)->startOfMinute()) : null,
                'plan_end'    => $plan->finish_time ? (str_contains($plan->finish_time, '-') ? Carbon::parse($plan->finish_time)->startOfMinute() : Carbon::parse($date . ' ' . $plan->finish_time)->startOfMinute()) : null,
                'capacity'    => (int) ($plan->qty_plt ?? 0),
            ];

            if ($existing) {
                // Jangan paksa update status jika sudah jalan/selesai (Preserve state)
                if (!in_array($existing->status, ['running', 'paused', 'complete', 'finished', 'closed'])) {
                    $existing->update($data);
                } else {
                    $existing->update(array_diff_key($data, ['status' => '']));
                }
            } else {
                \App\Models\JobMaster::create(array_merge($data, [
                    'job_number' => $identifier,
                    'status'     => 'pending',
                ]));
            }
        }
    }

    public function start(Request $request, $id)
    {
        $this->guardLockedShift($id);
        try {
            $this->productionService->startJob($id, $request->has('enqueue_only'));
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function enqueue($id)
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

    public function startDandori(Request $request, $id)
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

    public function finishDandori($jobId)
    {
        $this->guardLockedShift($jobId);
        try {
            // Close any open 1st check before finishing dandori
            $this->productionService->finishFirstCheck($jobId);

            $success = $this->productionService->finishDandori($jobId);
            if ($success) {
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'Dandori tidak ditemukan atau sudah selesai']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function startFirstCheck(Request $request, $id)
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

    public function finishFirstCheck($jobId)
    {
        $this->guardLockedShift($jobId);
        try {
            $success = $this->productionService->finishFirstCheck($jobId);
            if ($success) {
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => '1st Check tidak ditemukan atau sudah selesai']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function pause($id)
    {
        $this->guardLockedShift($id);
        try {
            $runtime = $this->productionService->pauseJob($id);
            return response()->json(['success' => true, 'total_seconds' => $runtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function resume($id)
    {
        $this->guardLockedShift($id);
        try {
            $this->productionService->resumeJob($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function restart($id)
    {
        $this->guardLockedShift($id);
        try {
            $this->productionService->restartJob($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finish(Request $request, $id)
    {
        $this->guardLockedShift($id);
        try {
            $nextJobId = $request->json('next_job_id') ?: $request->input('next_job_id');
            $skipIdle = filter_var($request->json('skip_idle') ?? $request->input('skip_idle', false), FILTER_VALIDATE_BOOLEAN);
            $finalOk = $request->json('ok_qty');
            $finalRepair = $request->json('repair_qty');
            $finalReject = $request->json('reject_qty');
            $runtime = $this->productionService->finishJob($id, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject);

            return response()->json([
                'success' => true,
                'runtime_seconds' => $runtime
            ]);
        } catch (\Throwable $e) {
            \Log::error("Failed to finish job $id: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan job: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
            ], 500);
        }
    }

    public function status($id)
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

    public function saveQty(Request $request, $id)
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

    private function guardLockedShift($jobMasterId)
    {
        $jm = JobMaster::find($jobMasterId);
        if (!$jm || !$jm->line) return;

        $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $jm->line)));
        $lineMaster = LineMaster::whereRaw("
            REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
        ", ["%{$normalized}%"])->first();
        if (!$lineMaster) return;

        $date = request()->header('X-Date') ?: request('date', now()->toDateString());
        $shift = $this->getShiftFromRequest();
        $shiftVal = str_contains(strtoupper($shift), 'MALAM') ? 2 : 1;

        $locked = ShiftSubmission::where([
            'line_id' => $lineMaster->id,
            'work_date' => $date,
            'shift' => $shiftVal,
        ])->exists();

        if ($locked) {
            throw new \Exception('Shift sudah dikunci. Data tidak dapat diubah.');
        }
    }

    public function nextList($id)
    {
        $current = JobMaster::find($id);

        if (!$current) {
            return response()->json([]);
        }

        $jobs = JobMaster::where('id', '!=', $id)
            ->whereIn('status', ['pending', 'paused', 'running'])
            ->where('line', $current->line)
            ->orderBy('sequence_no')
            ->orderBy('job_number')
            ->get();

        $formatted = $jobs->map(function($j) {
            return [
                'id' => $j->id,
                'job_number' => $j->job_number,
                'job_name' => $j->job_name,
                'line' => $j->line,
                'target_qty' => $j->target_qty,
                'label' => "{$j->job_name} - {$j->job_number} (" . ($j->target_qty ?: 0) . " pcs)"
            ];
        });

        return response()->json($formatted);
    }
    
    public function nextProcess(Request $request, $id)
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
        $query = ProductionSession::where('status', 'running')
            ->whereDate('work_date', now()->toDateString())
            ->with('jobMaster');

        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            $query->whereHas('jobMaster', function ($q) use ($normalizedLine) {
                $q->whereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"]);
            });
        }

        $session = $query->first();

        if (!$session || !$session->jobMaster) {
            return response()->json(['running' => false]);
        }
        $isDandori = \App\Models\Downtime::where('job_master_id', $session->jobMaster->id)
            ->where('jenis_downtime', 'dandori')
            ->whereNull('finish_time')
            ->exists();

        $activeDowntime = \App\Models\Downtime::where('job_master_id', $session->jobMaster->id)
            ->whereNull('finish_time')
            ->orderBy('created_at', 'desc')
            ->first();

        $urlParams = [];
        $jobMaster = $session->jobMaster;
        if ($jobMaster) {
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

            if ($plan) {
                $urlParams = [
                    'line'  => $lineParam,
                    'shift' => $plan->shift_name,
                    'date'  => $plan->plan_date ? $plan->plan_date->toDateString() : $session->work_date,
                ];
            } else {
                $urlParams = [
                    'line' => $lineParam,
                    'date' => $session->work_date,
                ];
            }
        }

        return response()->json([
            'running' => true,
            'id' => $session->jobMaster->id,
            'status' => $session->jobMaster->status,
            'is_dandori' => $isDandori,
            'active_downtime_count' => $activeDowntime ? 1 : 0,
            'active_downtime_type' => $activeDowntime ? strtolower($activeDowntime->jenis_downtime) : null,
            'job_name' => $session->jobMaster->job_name,
            'job_number' => $session->jobMaster->job_number,
            'total_seconds' => $session->total_seconds,
            'start_time' => \Carbon\Carbon::parse($session->start_time)->toIso8601String(),
            'url' => route('operational.input_harian', $urlParams) . '#row-' . $session->jobMaster->id
        ]);
    }

    /*
    ====================================================
    DOWNTIME MANAGEMENT
    ====================================================
    */
    public function getDowntimes($job_id)
    {
        $downtimes = Downtime::where('job_master_id', $job_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($downtimes);
    }

    public function startDowntime(Request $request, $job_id)
    {
        $this->guardLockedShift($job_id);
        try {
            $downtime = $this->productionService->startDowntime($job_id, $request->all());
            return response()->json([
                'success' => true,
                'downtime' => $downtime
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finishDowntime($id)
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

    public function updateDowntime(Request $request, $id)
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

    public function deleteDowntime($id)
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

    public function showLogs($id)
    {
        $job = JobMaster::with(['dailyProduction', 'downtimes', 'productionLogs'])->findOrFail($id);
        $logs = ProductionLog::where('job_master_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('operational.log_detail', compact('job', 'logs'));
    }

    public function getQty(Request $request, $id)
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

    public function submitShift(Request $request, $lineId)
    {
        try {
            $date = $request->get('date', now()->toDateString());
            $shift = $request->get('shift');

            $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('row_type', 'job')
                ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

            if ($shift) {
                $planQuery->where('shift_name', 'like', "{$shift}%");
            }

            $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineId)));
            $planQuery->whereRaw("
                REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
            ", ["%{$normalized}%"]);

            $plans = $planQuery->get();

            if ($plans->isEmpty()) {
                return response()->json([
                    'success'    => false,
                    'has_issues' => false,
                    'message'    => 'Belum ada jadwal PPC untuk line/shift ini. Shift tidak bisa difinalisasi.',
                ], 422);
            }

            $issues = [
                'dt'     => [],
                'repair' => [],
                'reject' => [],
                'remain' => [],
            ];

            foreach ($plans as $plan) {
                // Skip children — they are checked inside the parent loop
                if ($plan->parent_job_id) continue;

                $jn = trim($plan->job_no ?? '');
                $jm = trim($plan->job_master ?? '');
                if (blank($jn) && blank($jm)) continue;
                $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);

                $jobMaster = \App\Models\JobMaster::where('job_number', $identifier)
                    ->with(['dailyProduction', 'downtimes', 'repairRejects'])
                    ->first();

                if (!$jobMaster) continue;

                // Collect parent + children JobMasters for comprehensive check
                $allJms = collect([$jobMaster]);
                $children = \App\Models\ProductionPlan::where('parent_job_id', $plan->id)->get();
                foreach ($children as $child) {
                    $childKey = trim($child->job_no ?? '') . '-' . $child->id;
                    $childJm = \App\Models\JobMaster::where('job_number', $childKey)
                        ->with(['downtimes', 'repairRejects'])
                        ->first();
                    if ($childJm) $allJms->push($childJm);
                }

                $itemName = $plan->job_no ?: $plan->job_master;

                // Check DT on all JobMasters (parent + children)
                foreach ($allJms as $jm) {
                    foreach ($jm->downtimes ?? [] as $dt) {
                        if (in_array(trim($dt->jenis_downtime ?? ''), ['dandori', 'idle time', 'idle', 'break time'])) {
                            continue;
                        }
                        if (blank($dt->problem) || blank($dt->penyebab) || blank($dt->action)) {
                            $issues['dt'][] = [
                                'item'          => $itemName,
                                'issue'         => 'problem/penyebab/action belum lengkap',
                                'dt_id'         => $dt->id,
                                'job_master_id' => $jm->id,
                                'plan_id'       => $plan->id,
                            ];
                        }
                    }
                }

                // Check Repair & Reject on all JobMasters + cek actual tanpa RR record
                foreach ($allJms as $jm) {
                    foreach ($jm->repairRejects ?? [] as $rr) {
                        if (blank($rr->area_problem) || blank($rr->root_cause) || blank($rr->countermeasure)) {
                            $key = $rr->type === 'reject' ? 'reject' : 'repair';
                            $issues[$key][] = [
                                'item'          => $itemName,
                                'issue'         => 'area_problem/root_cause/countermeasure belum lengkap',
                                'job_master_id' => $jm->id,
                                'plan_id'       => $plan->id,
                            ];
                        }
                    }
                    if ($jm->dailyProduction && $jm->dailyProduction->actual_repair > 0) {
                        $hasRR = $jm->repairRejects->contains(fn($r) => $r->type === 'repair');
                        if (!$hasRR) {
                            $issues['repair'][] = [
                                'item'          => $itemName,
                                'issue'         => (int)$jm->dailyProduction->actual_repair . ' pcs Repair tanpa catatan',
                                'job_master_id' => $jm->id,
                                'plan_id'       => $plan->id,
                            ];
                        }
                    }
                    if ($jm->dailyProduction && $jm->dailyProduction->actual_reject > 0) {
                        $hasRR = $jm->repairRejects->contains(fn($r) => $r->type === 'reject');
                        if (!$hasRR) {
                            $issues['reject'][] = [
                                'item'          => $itemName,
                                'issue'         => (int)$jm->dailyProduction->actual_reject . ' pcs Reject tanpa catatan',
                                'job_master_id' => $jm->id,
                                'plan_id'       => $plan->id,
                            ];
                        }
                    }
                }

        }

        // Check Remain — skip children & breaks
        foreach ($plans as $p) {
            if ($p->parent_job_id) continue;
            if (($p->row_type ?? 'job') === 'break') continue;
            $pJn = trim($p->job_no ?? '');
            $pJmStr = trim($p->job_master ?? '');
            if (blank($pJn) && blank($pJmStr)) continue;
            $pIdent = $pJn ? ($pJn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($pJmStr) . '-' . $p->id);
            $pJm = \App\Models\JobMaster::where('job_number', $pIdent)->first();
            if (!$pJm) {
                $issues['remain'][] = [
                    'item'          => $p->job_no ?: $p->job_master,
                    'issue'         => 'Item belum dikerjakan (tidak ada JobMaster)',
                    'job_master_id' => null,
                    'plan_id'       => $p->id,
                ];
            } elseif (in_array(strtolower($pJm->status), ['running', 'pending'])) {
                $issues['remain'][] = [
                    'item'          => $p->job_no ?: $p->job_master,
                    'issue'         => "Status masih {$pJm->status}",
                    'job_master_id' => $pJm->id,
                    'plan_id'       => $p->id,
                ];
            }
        }

        if (!empty($issues['dt']) || !empty($issues['repair']) || !empty($issues['reject']) || !empty($issues['remain'])) {
                return response()->json([
                    'success'    => false,
                    'has_issues' => true,
                    'issues'     => $issues,
                ], 422);
            }

            // All valid — create submission record
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineId)));
            $lineMaster = \App\Models\LineMaster::whereRaw("
                REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?
            ", ["%{$normalizedLine}%"])->first();
            if (!$lineMaster) {
                $lineMaster = \App\Models\LineMaster::where('line_name', 'LIKE', "%{$normalizedLine}%")->first();
            }
            \App\Models\ShiftSubmission::create([
                'line_id' => $lineMaster?->id ?? throw new \Exception("Line '{$lineId}' not found"),
                'work_date' => $date,
                'shift' => $shift ? ($shift === 'Shift Malam' ? 2 : 1) : 1,
                'submitted_by' => auth()->id(),
            ]);

            \Illuminate\Support\Facades\Log::info("[SHIFT SUBMIT] Shift submitted", [
                'line' => $lineId,
                'date' => $date,
                'shift' => $shift,
                'by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil disubmit!',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[SHIFT SUBMIT] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function productionAudit(Request $request)
    {
        $date = $request->get('date') ?: now()->toDateString();

        $logs = ProductionLog::with('jobMaster')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->appends(['date' => $date]);

        $totalOk = $logs->getCollection()->sum('ok_qty');
        $totalRepair = $logs->getCollection()->sum('repair_qty');
        $totalReject = $logs->getCollection()->sum('reject_qty');

        // Cumulative OK before this page (logs are desc, so sum older pages)
        $cumulativeOffset = ProductionLog::whereDate('created_at', $date)
            ->where('id', '<', $logs->last()?->id ?? 0)
            ->sum('ok_qty');

        return view('operational.production_audit', compact('logs', 'date', 'totalOk', 'totalRepair', 'totalReject', 'cumulativeOffset'));
    }
}