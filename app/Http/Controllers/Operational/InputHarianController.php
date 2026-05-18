<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use Carbon\Carbon;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;
use App\Services\ProductionService;

class InputHarianController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function saveProductionLog(Request $request, $id)
    {
        $result = $this->productionService->saveProductionLog($id, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Log input saved',
            'total_ok' => $result['actualQty'],
            'efficiency' => $result['efficiency'],
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
            }
            if ($lineFilter) {
                $request->merge(['line' => $lineFilter]);
            }
        }

        // LOGIKA TANGGAL PRODUKSI (Work Date)
        $date = $request->get('date');

        if (!$date) {
            // Default ke tanggal terakhir yang ada datanya
            $date = \App\Models\ProductionPlan::max('plan_date');
            
            // Jika benar-benar kosong (DB kosong), baru default ke hari ini
            if (!$date) {
                $hour = (int) now()->format('H');
                $date = ($hour < 7) ? now()->subDay()->toDateString() : now()->toDateString();
            } else {
                // max() bisa return object Carbon atau string, pastikan string
                $date = \Carbon\Carbon::parse($date)->toDateString();
            }
        }
        
        $lineFilter   = $request->get('line');
        $search       = trim($request->get('search', ''));
        
        // Pilih shift: dari request atau auto-detect
        $currentShift = $request->get('shift', $this->getShift());
        
        // SYNC: Pastikan JobMaster terupdate dari Jadwal
        $this->syncPlanToJobMaster($date, $currentShift);

        // 1. Tentukan SHIFT TERBARU (Revisi Terakhir)
        $latestShiftName = $currentShift;
        if ($currentShift !== 'all') {
            $latestShiftName = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->where('shift_name', 'like', "{$currentShift}%")
                ->orderByDesc('updated_at')
                ->value('shift_name') ?: $currentShift;

            // FORCE REDIRECT: Pastikan URL di device (HP/Laptop) selalu pake nama shift yang PALING BARU
            // Ini biar nggak ada "session drift" antara HP dan Laptop.
            if ($request->get('shift') !== $latestShiftName && !str_contains($currentShift, 'REV')) {
                return redirect()->route('operational.input_harian', array_merge($request->query(), [
                    'shift' => $latestShiftName,
                    'date'  => $date
                ]));
            }
        }

        // 2. QUERY UTAMA: Langsung dari ProductionPlan (Source of Truth)
        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'job')
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
        // INDUSTRIAL SORTING: Pin 'Running' to top, others follow original PPC order (row_no)
        $plans = $planQuery->orderByRaw("
            CASE 
                WHEN LOWER(status) = 'running' THEN 0 
                ELSE 1
            END
        ")
            ->orderBy('row_no', 'asc')
            ->get();

        // DEBUG POINT (Uncomment to trace missing items)
        // dd($plans->map(fn($p) => ['job' => $p->job_no, 'status' => $p->status, 'line' => $p->press_name])->toArray());

        // MAPPING: Hubungkan setiap Plan ke JobMaster-nya (Optimized to fix N+1)
        $jobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with(['dailyProduction', 'downtimes'])
            ->get()
            ->keyBy('job_number');

        $plans->transform(function($plan) use ($jobMasters) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);
            $plan->job_data = $jobMasters->get($identifier);
            return $plan;
        });

        // 4. Cari Job Aktif (RUNNING) untuk Dashboard Header
        $activeJob = JobMaster::where(DB::raw('LOWER(status)'), 'running');
        
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            $activeJob->whereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"]);
        }

        $activeJob = $activeJob->with(['dailyProduction', 'downtimes'])
            ->first();

        $productionLogs = collect();
        $lastInputAt    = null;
        if ($activeJob) {
            $productionLogs = \App\Models\ProductionLog::where('job_master_id', $activeJob->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            $lastInputAt = $productionLogs->first()?->created_at ?? $activeJob->started_at;
        }

        // 5. Cari Job Pending untuk Queue Selector (Dropdown Standby)
        $scheduledJobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($p->job_master) . '-' . $p->id);
        })->toArray();

        $pendingJobs = JobMaster::whereIn(DB::raw('LOWER(status)'), ['pending', 'running'])
            ->whereIn('job_number', $scheduledJobNumbers)
            ->get();

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
                'plan_start'  => $plan->start_time ? (str_contains($plan->start_time, '-') ? Carbon::parse($plan->start_time) : Carbon::parse($date . ' ' . $plan->start_time)) : null,
                'plan_end'    => $plan->finish_time ? (str_contains($plan->finish_time, '-') ? Carbon::parse($plan->finish_time) : Carbon::parse($date . ' ' . $plan->finish_time)) : null,
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
        try {
            $this->productionService->startJob($id, $request->has('enqueue_only'));
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function startDandori($id)
    {
        try {
            $downtime = $this->productionService->startDandori($id);
            return response()->json(['success' => true, 'downtime' => $downtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finishDandori($jobId)
    {
        try {
            $success = $this->productionService->finishDandori($jobId);
            if ($success) {
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'Dandori tidak ditemukan atau sudah selesai']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function pause($id)
    {
        try {
            $runtime = $this->productionService->pauseJob($id);
            return response()->json(['success' => true, 'total_seconds' => $runtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function resume($id)
    {
        try {
            $this->productionService->resumeJob($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function restart($id)
    {
        try {
            $this->productionService->restartJob($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function finish(Request $request, $id)
    {
        try {
            $runtime = $this->productionService->finishJob($id);

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

    public function nextList($id)
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
    
    public function nextProcess(Request $request, $id)
    {
        $nextJobId = $request->get('next_job_id');
        
        // 1. Finish the current job and optionally start the next one
        $this->productionService->finishJob($id, $nextJobId);

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


    public function activeJob()
    {
        $session = ProductionSession::where('status', 'running')
            ->whereDate('work_date', now()->toDateString())
            ->with('jobMaster')
            ->first();

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
            'url' => route('operational.input_harian') . '#row-' . $session->jobMaster->id
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

        $downtime->update($request->only(['jenis_downtime', 'problem', 'penyebab', 'action', 'pic']));

        return response()->json([
            'success' => true,
            'downtime' => $downtime
        ]);
    }

    public function deleteDowntime($id)
    {
        try {
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
        $job = JobMaster::with(['dailyProduction', 'downtimes'])->findOrFail($id);
        $logs = ProductionLog::where('job_master_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('operational.log_detail', compact('job', 'logs'));
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