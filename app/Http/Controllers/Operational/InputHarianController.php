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
use Illuminate\Support\Facades\Cache;
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
            $workDate = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();
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
            } elseif (str_starts_with($userRole, 'leader')) {
                $suffix = trim(substr($userRole, 6));
                if (in_array(strtoupper($suffix), ['A', 'B', 'C', 'D'])) {
                    $lineFilter = 'Line ' . strtoupper($suffix);
                }
            }

            if (!$lineFilter) {
                $assignment = \App\Models\LineAssignment::where('leader_user_id', Auth::id())->first();
                if ($assignment) {
                    $lineFilter = $assignment->line_name;
                }
            }

            // Fallback default hanya jika bukan supervisor/admin/ppc/management (user tanpa role line spesifik)
            if (!$lineFilter && !in_array($userRole, ['supervisor', 'spv', 'admin', 'superadmin', 'ppc', 'manager', 'kadiv', 'direktur', 'quality'])) {
                $lineFilter = 'Line A';
            }

            if ($lineFilter) {
                $request->merge(['line' => $lineFilter]);
            }
        }

        // LOGIKA TANGGAL PRODUKSI (Work Date) & SHIFT - Shift Pagi: 07:30-21:00 | Shift Malam: 21:00-07:30
        $date = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();

        $autoShift = $this->getShift(); // 07:30 - 21:00 -> Shift Pagi, selain itu -> Shift Malam
        $currentShift = $request->filled('shift') ? $request->get('shift') : $autoShift;

        // SAFEGUARD: hanya izinkan akses ke tanggal produksi aktif (hari ini atau kemarin jika sebelum 07:30)
        // Data tanggal lewat sudah otomatis di-cleanup oleh scheduler dan tersimpan di Production Analytics
        $activeDate = \App\Services\ProductionMetricsService::currentProductionDate();
        if ($request->has('date') && $request->get('date') < $activeDate) {
            return redirect()->route('operational.input_harian', array_merge(
                $request->except('date'),
                ['date' => $activeDate]
            ));
        }

        $lineFilter   = $request->get('line');
        $search       = trim($request->get('search', ''));

        // 1. Tentukan SHIFT TERBARU (Revisi Terakhir)
        $latestShiftName = $currentShift;
        if ($currentShift !== 'all') {
            $latestShiftName = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->activePpcRevision()
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

        // LOCK & TRACKING SESSION LEADER: Simpan sesi kerja aktif (Line, Tanggal, Shift ke-berapa)
        if (Auth::check()) {
            $uRole = strtolower(Auth::user()->role ?? '');
            if (str_starts_with($uRole, 'leader') || in_array($uRole, ['leader', 'teamleader', 'group leader'])) {
                $shiftNum = (str_contains(strtolower((string) $currentShift), 'malam') || $currentShift === '2' || $currentShift === 'Shift 2') ? 2 : 1;
                $cleanLine = \App\Support\SignatureScopeNormalizer::standardLine($lineFilter ?? '');

                session([
                    'active_leader_line'         => $cleanLine,
                    'active_leader_date'         => $date,
                    'active_leader_shift_number' => $shiftNum,
                    'active_leader_shift_name'   => $currentShift,
                ]);

                // Kunci cache sesi leader aktif selama 18 jam
                $cacheKey = 'active_leader_' . str_replace(' ', '_', strtolower($cleanLine)) . '_' . $date . '_' . $shiftNum;
                Cache::put($cacheKey, Auth::id(), now()->addHours(18));
            }
        }

        // RECONCILE: Pastikan sesi shift sebelumnya yang masih running/paused di-transisikan secara aman
        // (job shift lampau yang melewati cutoff shift otomatis dihentikan/ditutup agar line bersih untuk shift baru)
        if ($currentShift !== 'all') {
            app(\App\Services\ProductionShiftTransitionService::class)->transitionShiftSafely($lineFilter, $latestShiftName, $date);
        } else {
            // Jika filter 'all', jalankan rekonsiliasi cutoff berdasarkan active shift aktual di jam sekarang
            $activeShiftNow = \App\Services\ProductionMetricsService::currentProductionShift();
            app(\App\Services\ProductionShiftTransitionService::class)->transitionShiftSafely($lineFilter, $activeShiftNow, $date);
        }

        // 2. QUERY UTAMA: Langsung dari ProductionPlan (Source of Truth)
        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->activePpcRevision()
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
        $plans = $planQuery->orderBy('row_no', 'asc')->orderBy('id', 'asc')->get();

        // ── MERGE BREAK SPLITS ──
        $parentIds = $plans->pluck('id');
        $childPlans = \App\Models\ProductionPlan::whereIn('parent_job_id', $parentIds->filter())
            ->activePpcRevision()
            ->whereDate('plan_date', $date)
            ->where('shift_name', $latestShiftName)
            ->orderBy('row_no', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('parent_job_id');

        $plans = $plans->filter(fn ($p) => !$p->parent_job_id)->values();

        // Hide phantom breaks that overlap a running job or sit outside the
        // schedule span. Group per press+shift so one section cannot leak into another.
        $breakValidator = app(\App\Services\BreakTimelineValidator::class);
        $keptPlanIds = $plans
            ->groupBy(fn ($p) => (string) ($p->press_name ?? '') . '|' . (string) ($p->shift_name ?? ''))
            ->flatMap(fn ($group) => $breakValidator->filterOverlappingBreaks(
                $breakValidator->filterValidPlans($group)
            ))
            ->pluck('id');
        $plans = $plans->filter(fn ($p) => $keptPlanIds->contains($p->id))->values();

        // Identifier PPC: gabungkan parent + child (split istirahat) lalu urutkan
        // berdasarkan row_no PPC agar antrean sama persis dengan timeline PPC.
        $scheduledJobNumbers = $plans->concat($childPlans->flatten(1))
            ->sortBy(fn($p) => [(int) ($p->row_no ?? 999999), (int) $p->id])
            ->map(function($p) {
                $jn = trim($p->job_no ?? '');
                $jm = trim($p->job_master ?? '');
                return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
            })
            ->unique()
            ->values()
            ->toArray();

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
                'repairRejects.images',
            ])
            ->get()
            ->keyBy('job_number');

        // Load production sessions for the selected date to use date-specific timestamps
        $sessionMap = \App\Models\ProductionSession::whereIn(
            'job_master_id',
            $jobMasters->pluck('id')->filter()->unique()->values()->toArray()
        )
            ->whereDate('work_date', $date)
            ->get()
            ->keyBy('job_master_id');

        // Restore root plan target & finish time when break-split children exist
        foreach ($plans as $planRow) {
            if (($planRow->row_type ?? 'job') === 'job') {
                $planRow->plan = $planRow->effectiveTargetQty();
                $planRow->finish_time = $planRow->effectiveFinishTime() ?: $planRow->finish_time;
                $effectiveTpt = $planRow->effectiveTptMinutes();
                if ($effectiveTpt > 0) {
                    $planRow->tpt = $effectiveTpt;
                }
            }
        }

        // Merge children's production data into parent's JobMaster
        foreach ($childPlans as $parentId => $children) {
            $parent = $plans->firstWhere('id', (int) $parentId);
            if (!$parent) continue;
            $pJn = trim($parent->job_no ?? '');
            $pJm = trim($parent->job_master ?? '');
            $parentKey = $pJn ? ($pJn . '-' . $parent->id) : ('AUTO-' . \Illuminate\Support\Str::slug($pJm) . '-' . $parent->id);
            $parentJm = $jobMasters->get($parentKey);

            // Jika parent belum memiliki JobMaster tetapi child sudah punya JobMaster dan actual produksi (kasus historis),
            // promosikan klon child pertama sebagai wadah agregat agar actual & sesi child tetap tampil utuh pada baris parent.
            if (!$parentJm) {
                foreach ($children as $childCandidate) {
                    $cJn = trim($childCandidate->job_no ?? '');
                    $cJm = trim($childCandidate->job_master ?? '');
                    $cKey = $cJn ? ($cJn . '-' . $childCandidate->id) : ('AUTO-' . \Illuminate\Support\Str::slug($cJm) . '-' . $childCandidate->id);
                    $foundChildJm = $jobMasters->get($cKey);
                    if ($foundChildJm) {
                        $parentJm = clone $foundChildJm;
                        $parentJm->target_qty = $parent->effectiveTargetQty();
                        $parentJm->setRelation('downtimes', collect($foundChildJm->downtimes ? $foundChildJm->downtimes->all() : []));
                        $parentJm->setRelation('dandoris', collect($foundChildJm->dandoris ? $foundChildJm->dandoris->all() : []));
                        $parentJm->setRelation('repairRejects', collect($foundChildJm->repairRejects ? $foundChildJm->repairRejects->all() : []));
                        if ($foundChildJm->dailyProduction) {
                            $parentJm->setRelation('dailyProduction', clone $foundChildJm->dailyProduction);
                        }
                        $jobMasters->put($parentKey, $parentJm);
                        break;
                    }
                }
            }

            if (!$parentJm) continue;
            $parentJm->target_qty = $parent->effectiveTargetQty();
            $seenChildJmIds = [$parentJm->id];

            foreach ($children as $child) {
                $cJn = trim($child->job_no ?? '');
                $cJm = trim($child->job_master ?? '');
                $childKey = $cJn ? ($cJn . '-' . $child->id) : ('AUTO-' . \Illuminate\Support\Str::slug($cJm) . '-' . $child->id);
                $childJm = $jobMasters->get($childKey);
                if (!$childJm || in_array($childJm->id, $seenChildJmIds, true)) continue;
                $seenChildJmIds[] = $childJm->id;

                // Sync status from child if child is running/paused or complete
                $cStatus = strtolower($childJm->status ?? '');
                $pStatus = strtolower($parentJm->status ?? '');
                if (in_array($cStatus, ['running', 'paused']) && !in_array($pStatus, ['running', 'paused'])) {
                    $parentJm->status = $childJm->status;
                } elseif (in_array($cStatus, ['complete', 'completed', 'finished', 'closed', 'done']) && $pStatus === 'pending') {
                    $parentJm->status = $childJm->status;
                }

                // Earliest started_at & latest finished_at across slices
                if ($childJm->started_at && (!$parentJm->started_at || \Carbon\Carbon::parse($childJm->started_at)->lt(\Carbon\Carbon::parse($parentJm->started_at)))) {
                    $parentJm->started_at = $childJm->started_at;
                }
                if ($childJm->finished_at && (!$parentJm->finished_at || \Carbon\Carbon::parse($childJm->finished_at)->gt(\Carbon\Carbon::parse($parentJm->finished_at)))) {
                    $parentJm->finished_at = $childJm->finished_at;
                }

                if ($childJm->dailyProduction) {
                    if (!$parentJm->dailyProduction) {
                        $parentJm->setRelation('dailyProduction', clone $childJm->dailyProduction);
                    } else {
                        $parentJm->dailyProduction->actual_ok        = ($parentJm->dailyProduction->actual_ok ?? 0) + ($childJm->dailyProduction->actual_ok ?? 0);
                        $parentJm->dailyProduction->actual_repair    = ($parentJm->dailyProduction->actual_repair ?? 0) + ($childJm->dailyProduction->actual_repair ?? 0);
                        $parentJm->dailyProduction->actual_reject    = ($parentJm->dailyProduction->actual_reject ?? 0) + ($childJm->dailyProduction->actual_reject ?? 0);
                        $parentJm->dailyProduction->actual_qty       = ($parentJm->dailyProduction->actual_qty ?? 0) + ($childJm->dailyProduction->actual_qty ?? 0);
                        $parentJm->dailyProduction->runtime_seconds  = ($parentJm->dailyProduction->runtime_seconds ?? 0) + ($childJm->dailyProduction->runtime_seconds ?? 0);
                        $parentJm->dailyProduction->downtime_seconds = ($parentJm->dailyProduction->downtime_seconds ?? 0) + ($childJm->dailyProduction->downtime_seconds ?? 0);
                    }
                }

                $childSession = $sessionMap->get($childJm->id);
                if ($childSession) {
                    $parentSession = $sessionMap->get($parentJm->id);
                    if (!$parentSession) {
                        $sessionMap->put($parentJm->id, clone $childSession);
                    } else {
                        $parentSession->total_seconds = ($parentSession->total_seconds ?? 0) + ($childSession->total_seconds ?? 0);
                    }
                }

                foreach ($childJm->downtimes ?? [] as $dt) {
                    $parentJm->downtimes->push($dt);
                }
                foreach ($childJm->dandoris ?? [] as $dd) {
                    $parentJm->dandoris->push($dd);
                }
                foreach ($childJm->repairRejects ?? [] as $rr) {
                    $parentJm->repairRejects->push($rr);
                }
            }
        }

        $plans->transform(function($plan) use ($jobMasters) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);
            $plan->job_data = $jobMasters->get($identifier);
            return $plan;
        });

        // Pin active 'Running' / 'Paused' job ke baris paling atas agar terlihat sedang jalan.
        // Saat job berstatus 'completed' (atau status selain running/paused), otomatis kembali ke urutan nomor antrean PPC murni (row_no, lalu id).
        $plans = $plans->sort(function ($a, $b) {
            $isRunA = in_array(strtolower($a->job_data?->status ?? $a->status ?? 'pending'), ['running', 'paused']) ? 0 : 1;
            $isRunB = in_array(strtolower($b->job_data?->status ?? $b->status ?? 'pending'), ['running', 'paused']) ? 0 : 1;

            if ($isRunA === $isRunB) {
                $rowA = (int) ($a->row_no ?? 999999);
                $rowB = (int) ($b->row_no ?? 999999);
                if ($rowA === $rowB) {
                    return $a->id <=> $b->id;
                }
                return $rowA <=> $rowB;
            }

            return $isRunA <=> $isRunB;
        })->values();

        // Determine if all jobs are done (for showing "Akhiri Shift" button)
        $jobPlans = $plans->filter(fn($p) => ($p->row_type ?? 'job') === 'job');
        $allJobsDone = $jobPlans->isNotEmpty() && $jobPlans->every(
            fn($p) => in_array(strtolower(optional($p->job_data)->status ?? ''), ['complete', 'completed', 'finished', 'closed', 'done'])
        );

        // 4. DATE-AWARE ACTIVE JOB
        // Historical only when ALL job items in this date/shift have been processed (have DailyProduction).
        $isHistorical = false;
        $currentWorkDate = \App\Services\ProductionMetricsService::currentProductionDate();
        if ($date < $currentWorkDate) {
            $allPlans = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->activePpcRevision()
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
        $orphanRunningJob = null;

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

                // Jika user membuka specific job_id, sinkronkan lineFilter ke line milik job tersebut
                // agar tabel antrean di bawah langsung menampilkan line yang relevan dengan job yang dibuka
                if (!empty($requestedJob->line)) {
                    $jobLineRaw = strtoupper(trim($requestedJob->line));
                    $targetLine = match(true) {
                        str_contains($jobLineRaw, 'A') => 'Line A',
                        str_contains($jobLineRaw, 'B') => 'Line B',
                        str_contains($jobLineRaw, 'C') => 'Line C',
                        str_contains($jobLineRaw, 'D') => 'Line D',
                        str_contains($jobLineRaw, 'SHEAR') => 'Shearing',
                        str_contains($jobLineRaw, 'HAND') => 'Handwork',
                        default => $requestedJob->line,
                    };
                    $lineFilter = $targetLine;
                    $request->merge(['line' => $targetLine]);
                }
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
                // cari active job di line ini.
                // Prioritas 1: Job yang cocok dengan shift & work_date yang sedang aktif.
                // Prioritas 2: Job running/paused apa pun yang memang sedang berjalan di line ini (agar bar tidak hilang/kumat).
                if ($activeJobs->isEmpty()) {
                    $fallbackQuery = $baseActiveQuery()
                        ->with([
                            'dailyProduction' => function ($q) use ($date) {
                                $q->where('work_date', $date);
                            },
                            'downtimes',
                            'dandoris'
                        ]);

                    // Jika shift saat ini spesifik (misal Shift Malam atau Shift Pagi), utamakan job milik shift tersebut
                    if ($currentShift !== 'all' && !empty($latestShiftName)) {
                        $shiftMatchingQuery = clone $fallbackQuery;
                        $shiftMatchingQuery->where(function($fq) use ($date, $latestShiftName) {
                            $fq->whereExists(function($sub) use ($date, $latestShiftName) {
                                $sub->select(DB::raw(1))
                                    ->from('production_plans')
                                    ->whereColumn('production_plans.job_no', 'job_masters.job_number')
                                    ->whereDate('production_plans.plan_date', $date)
                                    ->where('production_plans.shift_name', $latestShiftName);
                            });

                            if (DB::connection()->getDriverName() === 'mysql') {
                                $fq->orWhereRaw("EXISTS (
                                    SELECT 1 FROM production_plans pp
                                    WHERE pp.id = CAST(SUBSTRING_INDEX(job_masters.job_number, '-', -1) AS UNSIGNED)
                                      AND pp.plan_date = ?
                                      AND pp.shift_name = ?
                                )", [$date, $latestShiftName]);
                            }
                        });

                        $activeJobs = $shiftMatchingQuery
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

                    // Jika belum ketemu juga, ambil running/paused job yang ada di line ini
                    if ($activeJobs->isEmpty()) {
                        $activeJobs = $fallbackQuery
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
                }

                // Pilih job aktif paling awal menurut urutan PPC, bukan yang terakhir di-update.
                if ($activeJobs->isNotEmpty() && !empty($scheduledJobNumbers)) {
                    $ppcOrder = array_flip($scheduledJobNumbers);
                    $activeJobs = $activeJobs->sortBy(
                        fn($job) => $ppcOrder[$job->job_number] ?? PHP_INT_MAX
                    )->values();
                }

                $activeJob = $activeJobs->first();
                if (!$activeJob) {
                    $orphanRunningJob = $baseActiveQuery()
                        ->with([
                            'dailyProduction' => function ($q) use ($date) {
                                $q->where('work_date', $date);
                            },
                            'downtimes',
                            'dandoris'
                        ])
                        ->orderByDesc('updated_at')
                        ->first();
                }

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

        // Production Logs — filtered by shift window so Shift Malam logs after 00:00 remain visible
        $productionLogs = collect();
        $lastInputAt    = null;
        if ($activeJob) {
            // Ensure activeJob displays full root schedule target qty and aggregates sibling actuals if split
            $effectiveActiveTarget = $activeJob->effectiveTargetQty();
            if ($effectiveActiveTarget > 0) {
                $activeJob->target_qty = $effectiveActiveTarget;
            }
            $activeGroupIds = $this->productionService->getScheduleGroupJobMasterIds($activeJob->id);
            if (count($activeGroupIds) > 1) {
                $otherGroupIds = array_values(array_diff($activeGroupIds, [$activeJob->id]));
                if (!empty($otherGroupIds)) {
                    $otherDailies = \App\Models\DailyProduction::whereIn('job_master_id', $otherGroupIds)
                        ->whereDate('work_date', $date)
                        ->get();
                    foreach ($otherDailies as $odp) {
                        if (!$activeJob->dailyProduction) {
                            $activeJob->setRelation('dailyProduction', clone $odp);
                        } else {
                            $activeJob->dailyProduction->actual_ok     = ($activeJob->dailyProduction->actual_ok ?? 0) + ($odp->actual_ok ?? 0);
                            $activeJob->dailyProduction->actual_repair = ($activeJob->dailyProduction->actual_repair ?? 0) + ($odp->actual_repair ?? 0);
                            $activeJob->dailyProduction->actual_reject = ($activeJob->dailyProduction->actual_reject ?? 0) + ($odp->actual_reject ?? 0);
                            $activeJob->dailyProduction->actual_qty    = ($activeJob->dailyProduction->actual_qty ?? 0) + ($odp->actual_qty ?? 0);
                        }
                    }
                }
            }

            [$shiftWinStart, $shiftWinEnd] = \App\Services\ProductionMetricsService::shiftWindow(
                $date,
                $currentShift !== 'all' ? $currentShift : $this->getShift()
            );
            $productionLogs = \App\Models\ProductionLog::whereIn('job_master_id', $activeGroupIds)
                ->where('created_at', '>=', $shiftWinStart)
                ->where('created_at', '<', $shiftWinEnd)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            $lastInputAt = $productionLogs->first()?->created_at ?? ($activeJob->started_at ?? null);
        }

        // 5. Cari Job Pending untuk Queue Selector (Dropdown Standby)
        // Hanya tampilkan identitas jadwal induk ($plans sudah difilter !parent_job_id) agar potongan istirahat tidak muncul ganda.
        $rootScheduledJobNumbers = $plans
            ->filter(fn($p) => ($p->row_type ?? 'job') === 'job')
            ->sortBy(fn($p) => [(int) ($p->row_no ?? 999999), (int) $p->id])
            ->map(function($p) {
                $jn = trim($p->job_no ?? '');
                $jm = trim($p->job_master ?? '');
                return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
            })
            ->unique()
            ->values()
            ->toArray();

        $ppcQueueOrder = array_flip($rootScheduledJobNumbers);

        $pendingJobs = JobMaster::whereIn(DB::raw('LOWER(status)'), ['pending', 'running', 'paused'])
            ->whereIn('job_number', $rootScheduledJobNumbers)
            ->get()
            ->each(function($job) {
                $eff = $job->effectiveTargetQty();
                if ($eff > 0) {
                    $job->target_qty = $eff;
                }
            })
            ->sortBy(fn($job) => $ppcQueueOrder[$job->job_number] ?? PHP_INT_MAX)
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

        $isOrphanActiveJob = false;
        if ($activeJob && !empty($scheduledJobNumbers)) {
            $isOrphanActiveJob = !in_array($activeJob->job_number, $scheduledJobNumbers);
            // Jika identifier job master menggunakan ID child plan atau format matching, pastikan dicek juga ke base plan ID
            if ($isOrphanActiveJob) {
                $parts = explode('-', $activeJob->job_number);
                $activePlanId = end($parts);
                if (is_numeric($activePlanId)) {
                    $activePlan = \App\Models\ProductionPlan::find((int) $activePlanId);
                    if ($activePlan && ($activePlan->plan_date == $date || $activePlan->shift_name == $latestShiftName)) {
                        $isOrphanActiveJob = false;
                    }
                }
            }
        }

        // Ambil info revisi schedule terbaru dari PPC untuk notifikasi Leader
        $latestScheduleRevision = \App\Models\ScheduleRevision::whereDate('plan_date', $date)
            ->where(function ($q) use ($currentShift) {
                if ($currentShift !== 'all') {
                    $clean = preg_replace('/\bshift\s*/i', '', $currentShift);
                    $q->where('shift_name', 'LIKE', "%{$clean}%")
                      ->orWhere('shift_name', 'LIKE', "%{$currentShift}%");
                }
            })
            ->with('creator')
            ->orderByDesc('id')
            ->first();

        return view('operational.input_harian', [
            'jobs'            => $plans, 
            'pendingJobs'     => $pendingJobs,
            'lines'           => collect([
                                    'Line A', 'Line B', 'Line C', 'Line D', 'Shearing', 'Handwork'
                                 ]),
            'activeJob'       => $activeJob,
            'orphanRunningJob' => $orphanRunningJob,
            'isOrphanActiveJob' => $isOrphanActiveJob,
            'latestScheduleRevision' => $latestScheduleRevision,
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
                ->activePpcRevision()
                ->where('shift_name', 'like', "{$shift}%")
                ->orderByDesc('updated_at')
                ->value('shift_name') ?: $shift;
        }

        $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->activePpcRevision()
            ->where('row_type', 'job')
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);
        
        if ($shift !== 'all') {
            $planQuery->where('shift_name', $latestShiftName);
        }
        
        $plans = $planQuery->orderBy('row_no')->get();

        // Batch fetch existing JobMasters to eliminate N+1 query overhead
        // ONE SCHEDULE = ONE JOB IDENTITY: Only sync root schedule rows (!isChildSlice()).
        $identifiers = [];
        $validPlans = [];
        foreach ($plans as $seq => $plan) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            if (blank($jn) && blank($jm)) continue;
            if ($plan->row_type === 'break') continue;
            if (in_array($jn, ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])) continue;
            if ($plan->isChildSlice()) continue;

            $identifier = $plan->canonicalJobNumber();
            $identifiers[] = $identifier;
            $validPlans[] = ['plan' => $plan, 'seq' => $seq, 'identifier' => $identifier];
        }

        $existingJobMasters = \App\Models\JobMaster::whereIn('job_number', $identifiers)->get()->keyBy('job_number');
        $mergeService = app(\App\Services\PpcScheduleMergeService::class);

        foreach ($validPlans as $item) {
            $plan = $item['plan'];
            $seq = $item['seq'];
            $identifier = $item['identifier'];

            $existing = $existingJobMasters->get($identifier);

            $lineName = $plan->press_name ?: ($plan->line ?: 'PRESS A');
            $effectiveFinish = $plan->effectiveFinishTime() ?: $plan->finish_time;
            $data = [
                'job_name'    => $plan->job_master ?: ($plan->job_no ?: 'UNKNOWN JOB'),
                'line'        => $lineName,
                'target_qty'  => $plan->effectiveTargetQty(),
                'sequence_no' => $plan->row_no ?? ($seq + 1), // Gunakan row_no asli dari Excel
                'plan_start'  => $plan->start_time ? (str_contains($plan->start_time, '-') ? Carbon::parse($plan->start_time)->startOfMinute() : Carbon::parse($date . ' ' . $plan->start_time)->startOfMinute()) : null,
                'plan_end'    => $effectiveFinish ? (str_contains($effectiveFinish, '-') ? Carbon::parse($effectiveFinish)->startOfMinute() : Carbon::parse($date . ' ' . $effectiveFinish)->startOfMinute()) : null,
                'capacity'    => (int) ($plan->qty_plt ?? 0),
                'ppc_revision_id' => $plan->ppc_revision_id,
                'ppc_revision_active' => true,
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
                    // Job yang sudah punya aktivitas produksi tidak boleh diubah target/jadwalnya
                    // secara diam-diam oleh revisi PPC. Status dan actuals juga selalu dipertahankan.
                    if (!in_array($existing->status, ['running', 'paused', 'complete', 'finished', 'closed'])
                        && ! $mergeService->jobHasActivity($existing)) {
                        $existing->update($data);
                    } else {
                        $existing->update(array_diff_key($data, [
                            'target_qty' => true,
                            'plan_start' => true,
                            'plan_end' => true,
                            'status' => true,
                        ]));
                    }
                }
            } else {
                \App\Models\JobMaster::create(array_merge($data, [
                    'job_number' => $identifier,
                    'status'     => 'pending',
                ]));
            }
        }

        // Cleanup: Jika ada JobMaster berstatus pending yang terhubung ke baris ProductionPlan pada tanggal ini
        // namun ID plan-nya sudah dihapus oleh PPC (misal revisi PPC menghapus item tersebut),
        // hapus JobMaster orphan tersebut agar tidak muncul di antrean atau dropdown proses selanjutnya.
        if (!empty($identifiers)) {
            $candidateJobs = \App\Models\JobMaster::where('status', 'pending')
                ->where('job_number', 'like', '%-%')
                ->whereNotIn('job_number', $identifiers)
                ->get();

            if ($candidateJobs->isNotEmpty()) {
                $planIds = [];
                foreach ($candidateJobs as $job) {
                    $parts = explode('-', $job->job_number);
                    $pid = end($parts);
                    if (is_numeric($pid)) {
                        $planIds[] = (int) $pid;
                    }
                }
                $existingPlanIds = empty($planIds) ? [] : \App\Models\ProductionPlan::whereIn('id', array_unique($planIds))->pluck('id')->all();
                $existingPlanIdMap = array_flip($existingPlanIds);

                foreach ($candidateJobs as $job) {
                    $parts = explode('-', $job->job_number);
                    $pid = end($parts);
                    if (is_numeric($pid) && !isset($existingPlanIdMap[(int) $pid])) {
                        if (!$mergeService->jobHasActivity($job)) {
                            $job->delete();
                        }
                    }
                }
            }
        }
        // Note: Do NOT auto-cutoff running/paused jobs here to prevent terminating ongoing production
        // when the operator switches shifts, lines, or views.
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
            $workDate = \App\Services\ProductionMetricsService::currentProductionDate();
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
            $workDate = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();
            $downtime = $this->productionService->startDandori($id, $workDate);
            return response()->json(['success' => true, 'downtime' => $downtime]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancelDandori(Request $request, int|string $jobId)
    {
        $this->guardLockedShift($jobId);
        try {
            $workDate = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();
            $this->productionService->cancelDandori($jobId, $workDate);
            return response()->json(['success' => true, 'message' => 'Dandori berhasil dibatalkan dan job kembali standby.']);
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

            $workDate = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();
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
            $workDate = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();
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
        try {
            $this->guardLockedShift($id);
            $nextJobId = $request->json('next_job_id') ?: $request->input('next_job_id');
            $skipIdle = filter_var($request->json('skip_idle') ?? $request->input('skip_idle', false), FILTER_VALIDATE_BOOLEAN);
            $finalOk = $request->json('ok_qty');
            $finalRepair = $request->json('repair_qty');
            $finalReject = $request->json('reject_qty');
            $reasonNotAchieved = $request->json('reason_not_achieved') ?? $request->input('reason_not_achieved');

            if ($reasonNotAchieved !== null) {
                $reasonNotAchieved = trim((string)$reasonNotAchieved);
                if ($reasonNotAchieved === '') {
                    $reasonNotAchieved = null;
                }
            }

            $result = $this->productionService->finishJob($id, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject, [], $reasonNotAchieved);
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

    public function forceStop(Request $request, int|string $id)
    {
        try {
            $job = JobMaster::find($id);
            if (!$job) {
                return response()->json(['success' => false, 'message' => 'Job tidak ditemukan'], 404);
            }

            // Close any open downtimes
            $now = Carbon::now();
            $openDts = Downtime::where('job_master_id', $job->id)->whereNull('finish_time')->get();
            foreach ($openDts as $odt) {
                $startDt = Carbon::parse($odt->start_time);
                $odt->update([
                    'finish_time' => $now,
                    'duration_seconds' => abs($now->diffInSeconds($startDt)),
                ]);
            }

            // Close open dandori in dandoris table if exists
            \App\Models\Dandori::where('next_job_id', $job->id)->whereNull('finish_time')->update([
                'finish_time' => $now,
            ]);

            // Calculate runtime
            $runtime = $this->productionService->calculateRuntime($job->id);

            // Close open production session with valid enum ('finished')
            $session = ProductionSession::where('job_master_id', $job->id)
                ->whereIn('status', ['running', 'paused'])
                ->first();
            if ($session) {
                $session->update([
                    'total_seconds' => $runtime,
                    'status' => 'finished',
                    'finish_time' => $now,
                    'pause_time' => $now,
                ]);
            }

            // Update daily production runtime
            $workDate = $this->productionService->resolveWorkDate($job->id);
            $daily = DailyProduction::where('job_master_id', $job->id)->where('work_date', $workDate)->first();
            if ($daily) {
                $daily->update(['runtime_seconds' => $runtime]);
            }

            // Mark job complete in JobMaster and sync with ProductionPlan
            $this->productionService->finishJob($job->id);

            \App\Services\DashboardRealtimeService::flushCache();
            if ($job->line) {
                \App\Services\DashboardRealtimeService::signalUpdate($job->line);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesi job ' . $job->job_number . ' berhasil dihentikan.',
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to force stop job $id: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghentikan job: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status(int|string $id)
    {
        $job = JobMaster::find($id);
        $workDate = $this->productionService->resolveWorkDate($id);
        
        $session = ProductionSession::where('job_master_id', $id)
            ->whereDate('work_date', $workDate)
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
        $h = (int) now()->format('H');
        $m = (int) now()->format('i');
        $timeInMin = $h * 60 + $m;

        // Shift Pagi: 07:30 - 21:00
        // Shift Malam: 21:00 - 07:30
        if ($timeInMin >= 450 && $timeInMin < 1260) {
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

        $date = $date ?: \App\Services\ProductionMetricsService::currentProductionDate();
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

        $rootCurrent = $current->resolveRootJobMaster();
        $siblingJobs = $this->productionService->getSiblingJobMasters($rootCurrent->id);
        $excludeIds = $siblingJobs->pluck('id')->push($current->id)->push($rootCurrent->id)->unique()->values()->all();

        $query = JobMaster::whereNotIn('id', $excludeIds)
            ->whereIn('status', ['pending', 'paused']);

        if ($rootCurrent->line) {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $rootCurrent->line)));
            $query->whereRaw("REPLACE(REPLACE(UPPER(TRIM(line)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalizedLine}%"]);
        }

        $currentPlan = $rootCurrent->production_plan ?: $current->production_plan;
        $jobs = $query->orderBy('sequence_no')
            ->orderBy('job_number')
            ->get()
            ->filter(function (JobMaster $j) use ($currentPlan) {
                $plan = $j->production_plan;
                if ($plan) {
                    // Never expose child slices as selectable Next Items
                    if ($plan->isChildSlice()) {
                        return false;
                    }
                    // If current job is bound to a specific plan_date & shift_name, scope candidates to the same date & shift
                    if ($currentPlan && $currentPlan->plan_date && $plan->plan_date) {
                        if (\Carbon\Carbon::parse($plan->plan_date)->toDateString() !== \Carbon\Carbon::parse($currentPlan->plan_date)->toDateString()) {
                            return false;
                        }
                    }
                    if ($currentPlan && $currentPlan->shift_name && $plan->shift_name) {
                        if ($plan->shift_name !== $currentPlan->shift_name) {
                            return false;
                        }
                    }
                }

                return true;
            })
            ->values();

        $formatted = $jobs->map(function ($j) {
            $effQty = $j->effectiveTargetQty() ?: ($j->target_qty ?: 0);

            return [
                'id' => $j->id,
                'job_number' => $j->job_number,
                'job_name' => $j->job_name,
                'label' => "{$j->job_name} - {$j->job_number} ({$effQty} pcs)"
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

        // 2. Get the next job (automatically skips internal break-split child slices via P0 getNextJob)
        $next = $this->productionService->getNextJob($id, $request->next_id ?: $nextJobId);
        if ($next) {
            $next = $next->resolveRootJobMaster();
        }

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
        $date = $request->get('date') ?: \App\Services\ProductionMetricsService::currentProductionDate();

        // 1. Line Resolution: Jika user login memiliki role leader/line spesifik atau LineAssignment dan parameter line kosong
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
            } elseif (str_starts_with($userRole, 'leader')) {
                $suffix = trim(substr($userRole, 6));
                if (in_array(strtoupper($suffix), ['A', 'B', 'C', 'D'])) {
                    $lineFilter = 'Line ' . strtoupper($suffix);
                }
            }
            if (!$lineFilter) {
                $assignment = \App\Models\LineAssignment::where('leader_user_id', Auth::id())->first();
                if ($assignment) {
                    $lineFilter = $assignment->line_name;
                }
            }
        }

        $normalizedLine = '';
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
        }

        // Cari JobMaster yang sedang running, atau paused karena break/dandori.
        // Paused tanpa aktivitas aktif bukan job yang sedang berjalan.
        $jmQuery = JobMaster::where(function ($q) {
            $q->where(DB::raw('LOWER(status)'), 'running')
              ->orWhere(function ($paused) {
                  $paused->where(DB::raw('LOWER(status)'), 'paused')
                      ->whereExists(function ($sub) {
                          $sub->select(DB::raw(1))
                              ->from('downtimes')
                              ->whereColumn('downtimes.job_master_id', 'job_masters.id')
                              ->whereIn(DB::raw('LOWER(TRIM(COALESCE(downtimes.jenis_downtime, "")))'), ['break time', 'break', 'istirahat', 'dandori'])
                              ->whereNull('downtimes.finish_time');
                      });
              });
        });
        if ($normalizedLine) {
            $jmQuery->where(function($q) use ($normalizedLine, $lineFilter) {
                $q->whereRaw("UPPER(TRIM(line)) = ?", [strtoupper(trim($lineFilter))])
                  ->orWhereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"])
                  ->orWhereRaw("REPLACE(REPLACE(UPPER(TRIM(line)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalizedLine}%"]);
            });
        }

        $hasProductionProgress = function($jm) use ($date) {
            $dp = $jm->dailyProduction;
            $actualTotal = ($dp?->actual_ok ?? 0) + ($dp?->actual_repair ?? 0) + ($dp?->actual_reject ?? 0);
            return $actualTotal > 0 || !empty($jm->started_at);
        };

        $startOfDay = Carbon::parse($date)->startOfDay()->toDateTimeString();
        $nextDay    = Carbon::parse($date)->addDay()->startOfDay()->toDateTimeString();

        $jobMaster = $jmQuery->where(function($q) use ($date, $startOfDay, $nextDay, $request) {
                $requestedShift = $request->get('shift');
                if ($requestedShift && $requestedShift !== 'all') {
                    $q->whereExists(function($sub) use ($startOfDay, $nextDay, $requestedShift) {
                        $sub->select(DB::raw(1))
                            ->from('production_plans')
                            ->whereColumn('production_plans.job_no', 'job_masters.job_number')
                            ->where('production_plans.plan_date', '>=', $startOfDay)
                            ->where('production_plans.plan_date', '<', $nextDay)
                            ->where('production_plans.shift_name', 'like', "{$requestedShift}%");
                    })->orWhere(function($sub) use ($startOfDay, $nextDay, $requestedShift) {
                        $planIds = \App\Models\ProductionPlan::where('plan_date', '>=', $startOfDay)
                            ->where('plan_date', '<', $nextDay)
                            ->where('shift_name', 'like', "{$requestedShift}%")
                            ->pluck('id')
                            ->toArray();
                        if (!empty($planIds)) {
                            $sub->where(function($inQ) use ($planIds) {
                                foreach ($planIds as $pid) {
                                    $inQ->orWhere('job_number', 'LIKE', "%-{$pid}");
                                }
                            });
                        } else {
                            $sub->whereRaw('0 = 1');
                        }
                    });
                }
            })->orderByRaw("CASE 
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

        $workDate = $session ? $session->work_date : \App\Services\ProductionMetricsService::currentProductionDate();
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
            'can_force_stop' => true,
            'stop_url' => route('operational.job.forceStop', ['id' => $jobMaster->id]),
            'url' => route('operational.input_harian', $urlParams) . '#row-' . $jobMaster->id
        ]);
    }

    /*
    ====================================================
    DOWNTIME MANAGEMENT
    ====================================================
    */
    public function getDowntimes(Request $request, int|string $job_id)
    {
        $query = Downtime::where('job_master_id', $job_id);

        $shift = $request->query('shift');
        $date = $request->query('date');

        if ($date && $shift && $shift !== 'all') {
            [$shiftStart, $shiftEnd] = \App\Services\ProductionMetricsService::shiftWindow($date, $shift);
            $query->where('start_time', '>=', $shiftStart)
                  ->where('start_time', '<', $shiftEnd);
        }

        $downtimes = $query->orderBy('created_at', 'desc')->get();

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

        $request->validate([
            'jenis_downtime' => 'required|string|not_in:downtime',
            'foto_part' => 'nullable|file|max:10240',
            'foto_tooling' => 'nullable|file|max:10240',
        ], [
            'jenis_downtime.required' => 'Wajib memilih jenis downtime terlebih dahulu.',
            'jenis_downtime.not_in' => 'Wajib memilih jenis downtime yang valid.',
        ]);

        $fields = $request->only(['jenis_downtime', 'problem', 'penyebab', 'action', 'pic']);

        // Helper aman dan konsisten untuk menyimpan file upload downtime
        $saveDowntimeFile = function ($file, string $category) use ($downtime) {
            $originalName = $file->getClientOriginalName();
            $safeName = time() . '_' . uniqid() . '_' . $category . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalName);
            $subDir = 'downtime/' . $downtime->id;
            $relPath = $subDir . '/' . $safeName;

            // Prioritas 1: Simpan ke disk 'uploads' (public/uploads) yang langsung disajikan web server tanpa symlink/serve block
            try {
                if (!\Illuminate\Support\Facades\Storage::disk('uploads')->exists($subDir)) {
                    \Illuminate\Support\Facades\Storage::disk('uploads')->makeDirectory($subDir);
                }
                $storedUploads = \Illuminate\Support\Facades\Storage::disk('uploads')->putFileAs($subDir, $file, $safeName);
                if ($storedUploads) {
                    $uplAbsPath = public_path('uploads/' . $relPath);
                    if (file_exists($uplAbsPath)) {
                        @chmod($uplAbsPath, 0664);
                    }
                    return $relPath;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Storage disk uploads putFileAs failed: ' . $e->getMessage() . ', trying public disk fallback.');
            }

            // Prioritas 2: Simpan ke Storage disk 'public' (storage/app/public)
            try {
                if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($subDir)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($subDir);
                }
                $storedPublic = \Illuminate\Support\Facades\Storage::disk('public')->putFileAs($subDir, $file, $safeName);
                if ($storedPublic) {
                    $pubAbsPath = storage_path('app/public/' . $relPath);
                    if (file_exists($pubAbsPath)) {
                        @chmod($pubAbsPath, 0664);
                    }
                    return $relPath;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Storage disk public putFileAs failed: ' . $e->getMessage() . ', trying direct copy fallback.');
            }

            // Prioritas 3: Direct fallback copy ke public/uploads lalu storage/app/public
            $candidates = [
                public_path('uploads/' . $relPath),
                storage_path('app/public/' . $relPath),
            ];

            foreach ($candidates as $targetAbsPath) {
                $targetDir = dirname($targetAbsPath);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                if (is_dir($targetDir) && is_writable($targetDir)) {
                    $copied = false;
                    if (file_exists($file->getRealPath())) {
                        $copied = @copy($file->getRealPath(), $targetAbsPath);
                    }
                    if (!$copied) {
                        $content = @file_get_contents($file->getRealPath());
                        if ($content !== false) {
                            $copied = @file_put_contents($targetAbsPath, $content) !== false;
                        }
                    }
                    if ($copied && file_exists($targetAbsPath)) {
                        @chmod($targetAbsPath, 0664);
                        return $relPath;
                    }
                }
            }

            throw new \RuntimeException("Gagal menyimpan file foto {$category}. Direktori server tidak dapat ditulis. Pastikan permission public/uploads atau storage/app/public dapat diakses proses PHP.");
        };

        try {
            // 1. Foto Part
            if ($request->hasFile('foto_part')) {
                $partPath = $saveDowntimeFile($request->file('foto_part'), 'part');
                $fields['image_path_part'] = $partPath;
                $fields['image_path'] = $partPath; // Backward-compatibility
            }

            // 2. Foto Tooling
            if ($request->hasFile('foto_tooling')) {
                $toolingPath = $saveDowntimeFile($request->file('foto_tooling'), 'tooling');
                $fields['image_path_tooling'] = $toolingPath;
                if (empty($fields['image_path'])) {
                    $fields['image_path'] = $toolingPath; // Fallback jika foto part tidak ada
                }
            }

            // 3. Fallback foto legacy jika dikirim via key 'foto'
            if ($request->hasFile('foto') && empty($fields['image_path_part'])) {
                $legacyPath = $saveDowntimeFile($request->file('foto'), 'part');
                $fields['image_path_part'] = $legacyPath;
                $fields['image_path'] = $legacyPath;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Downtime photo upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Gagal menyimpan foto downtime. Silakan periksa ukuran file dan coba lagi.'
            ], 422);
        }

        // Pastikan kolom foto diset null jika tidak ada pada record dan belum terisi agar aman dari database non-nullable/no-default
        if (!isset($fields['image_path_part']) && $downtime->image_path_part === null) {
            $fields['image_path_part'] = null;
        }
        if (!isset($fields['image_path_tooling']) && $downtime->image_path_tooling === null) {
            $fields['image_path_tooling'] = null;
        }
        if (!isset($fields['image_path']) && $downtime->image_path === null) {
            $fields['image_path'] = null;
        }

        $downtime->update($fields);

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
        $date = $request->get('date') ?: $this->productionService->resolveWorkDate($id);
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
            $date = $request->get('date', \App\Services\ProductionMetricsService::currentProductionDate());
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
                    ->activePpcRevision()
                    ->where('shift_name', 'like', "{$shiftName}%")
                    ->orderByDesc('updated_at')
                    ->value('shift_name') ?: $shiftName;
            }

            // 2. Query ProductionPlan KHUSUS jadwal shift dan tanggal saat ini untuk line ini
            $planQuery = \App\Models\ProductionPlan::whereDate('plan_date', $date)
                ->activePpcRevision()
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
                [$shiftStart, $shiftEnd] = \App\Services\ProductionMetricsService::shiftWindow($date, $latestShiftName ?: $shiftName);
                $isNightShift = ((int) $shiftValue === 2) || str_contains(strtoupper((string) ($latestShiftName ?: $shiftName)), 'MALAM');
                $nextDayDate = \Carbon\Carbon::parse($date)->addDay()->toDateString();
                $validWorkDates = $isNightShift ? [$date, $nextDayDate] : [$date];

                $parentPlans = $plans->filter(fn ($p) => ! $p->isChildSlice())->values();
                $parentIdentifiers = $parentPlans->map(function ($p) {
                    $jn = trim($p->job_no ?? '');
                    $jm = trim($p->job_master ?? '');
                    if (blank($jn) && blank($jm)) {
                        return null;
                    }

                    return $p->canonicalJobNumber();
                })->filter()->values()->toArray();

                $rawParentJobNos = $parentPlans->pluck('job_no')->filter()->values()->toArray();

                $parentJobMasters = \App\Models\JobMaster::where(function ($q) use ($parentIdentifiers, $rawParentJobNos) {
                        $q->whereIn('job_number', $parentIdentifiers);
                        if (! empty($rawParentJobNos)) {
                            $q->orWhereIn('job_number', $rawParentJobNos);
                        }
                    })
                    ->with([
                        'dailyProduction' => function ($q) use ($validWorkDates) {
                            $q->whereIn('work_date', $validWorkDates);
                        },
                        'downtimes' => function ($q) use ($shiftStart, $shiftEnd, $date) {
                            $q->where(function ($sq) use ($shiftStart, $shiftEnd, $date) {
                                $sq->whereBetween('start_time', [$shiftStart, $shiftEnd])
                                    ->orWhereDate('start_time', $date);
                            });
                        },
                        'repairRejects' => function ($q) use ($shiftStart, $shiftEnd, $date) {
                            $q->where(function ($sq) use ($shiftStart, $shiftEnd, $date) {
                                $sq->whereBetween('created_at', [$shiftStart, $shiftEnd])
                                    ->orWhereDate('created_at', $date);
                            });
                        },
                    ])
                    ->get();

                $parentJobMastersByIdentifier = $parentJobMasters->keyBy('job_number');

                $parentIds = $parentPlans->pluck('id')->filter()->values()->toArray();
                $childPlansMap = \App\Models\ProductionPlan::whereIn('parent_job_id', $parentIds)
                    ->get()
                    ->groupBy('parent_job_id');

                $allChildSlices = $childPlansMap->flatten()->merge(
                    $plans->filter(fn ($p) => $p->isChildSlice())
                )->unique('id')->values();

                $childIdentifiers = $allChildSlices->map(function ($c) {
                    $cjn = trim($c->job_no ?? '');
                    $cjm = trim($c->job_master ?? '');
                    if (blank($cjn) && blank($cjm)) {
                        return null;
                    }

                    return $cjn ? ($cjn.'-'.$c->id) : ('AUTO-'.\Illuminate\Support\Str::slug($cjm).'-'.$c->id);
                })->filter()->values()->toArray();

                $childJobMasters = \App\Models\JobMaster::whereIn('job_number', $childIdentifiers)
                    ->with([
                        'dailyProduction' => function ($q) use ($validWorkDates) {
                            $q->whereIn('work_date', $validWorkDates);
                        },
                        'downtimes' => function ($q) use ($shiftStart, $shiftEnd, $date) {
                            $q->where(function ($sq) use ($shiftStart, $shiftEnd, $date) {
                                $sq->whereBetween('start_time', [$shiftStart, $shiftEnd])
                                    ->orWhereDate('start_time', $date);
                            });
                        },
                        'repairRejects' => function ($q) use ($shiftStart, $shiftEnd, $date) {
                            $q->where(function ($sq) use ($shiftStart, $shiftEnd, $date) {
                                $sq->whereBetween('created_at', [$shiftStart, $shiftEnd])
                                    ->orWhereDate('created_at', $date);
                            });
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

                $dailiesByJmId = \App\Models\DailyProduction::whereIn('job_master_id', $allDailyJmIds)
                    ->whereIn('work_date', $validWorkDates)
                    ->orderBy('id', 'desc')
                    ->get()
                    ->groupBy('job_master_id');

                // ── VALIDATION: Evaluasi SATU jadwal utuh per root plan (ONE SCHEDULE = ONE JOB IDENTITY) ──
                foreach ($parentPlans as $plan) {
                    $jn = trim($plan->job_no ?? '');
                    $jm = trim($plan->job_master ?? '');
                    if (blank($jn) && blank($jm)) {
                        continue;
                    }
                    $identifier = $plan->canonicalJobNumber();

                    $jobMaster = $parentJobMastersByIdentifier->get($identifier)
                        ?? $parentJobMasters->first(fn ($j) => $jn && $j->job_number === $jn);

                    if (! $jobMaster) {
                        $jobMaster = \App\Models\JobMaster::where('job_number', $identifier)
                            ->when($jn !== '', fn ($q) => $q->orWhere('job_number', $jn))
                            ->with([
                                'dailyProduction' => function ($q) use ($validWorkDates) {
                                    $q->whereIn('work_date', $validWorkDates);
                                },
                                'downtimes' => function ($q) use ($shiftStart, $shiftEnd, $date) {
                                    $q->where(function ($sq) use ($shiftStart, $shiftEnd, $date) {
                                        $sq->whereBetween('start_time', [$shiftStart, $shiftEnd])
                                            ->orWhereDate('start_time', $date);
                                    });
                                },
                                'repairRejects' => function ($q) use ($shiftStart, $shiftEnd, $date) {
                                    $q->where(function ($sq) use ($shiftStart, $shiftEnd, $date) {
                                        $sq->whereBetween('created_at', [$shiftStart, $shiftEnd])
                                            ->orWhereDate('created_at', $date);
                                    });
                                },
                            ])
                            ->first();
                    }

                    // Kumpulkan seluruh JobMaster dalam satu schedule group (root + child slices)
                    $groupJms = collect();
                    if ($jobMaster) {
                        $groupJms->push($jobMaster);
                    }

                    $children = $childPlansMap->get($plan->id, collect());
                    if (! empty($plan->split_group)) {
                        $splitSiblings = $plans->filter(fn ($p) => $p->id !== $plan->id && $p->split_group === $plan->split_group);
                        $children = $children->merge($splitSiblings)->unique('id');
                    }

                    foreach ($children as $child) {
                        $cjn = trim($child->job_no ?? '');
                        $cjm = trim($child->job_master ?? '');
                        $childKey = $cjn ? ($cjn.'-'.$child->id) : ('AUTO-'.\Illuminate\Support\Str::slug($cjm).'-'.$child->id);
                        $childJm = $childJobMasters->get($childKey);
                        if ($childJm && ! $groupJms->contains('id', $childJm->id)) {
                            $groupJms->push($childJm);
                        }
                    }

                    if ($groupJms->isEmpty()) {
                        continue;
                    }

                    $primaryJm = $jobMaster ?: $groupJms->first();
                    $itemName = $plan->job_no ?: ($plan->job_master ?: ($primaryJm->job_name ?: $primaryJm->job_number));
                    $planId = $plan->id;
                    $groupJmIds = $groupJms->pluck('id')->unique()->values()->all();

                    // 1. Evaluasi Downtimes di seluruh slice dalam schedule group
                    $seenDtIds = [];
                    foreach ($groupJms as $gJm) {
                        foreach ($gJm->downtimes ?? [] as $dt) {
                            if (isset($seenDtIds[$dt->id])) {
                                continue;
                            }
                            $seenDtIds[$dt->id] = true;

                            if ($dt->start_time) {
                                $dtStart = \Carbon\Carbon::parse($dt->start_time);
                                $inWindow = $dtStart->betweenIncluded($shiftStart, $shiftEnd)
                                    || $dtStart->toDateString() === $date;
                                if (! $inWindow) {
                                    continue;
                                }
                            }
                            if (in_array(strtolower(trim($dt->jenis_downtime ?? '')), ['dandori', 'idle time', 'idle', 'break time'], true)) {
                                continue;
                            }
                            if (blank($dt->problem) || blank($dt->penyebab) || blank($dt->action)) {
                                $issues['dt'][] = [
                                    'item'          => $itemName,
                                    'issue'         => 'problem/penyebab/action belum lengkap',
                                    'dt_id'         => $dt->id,
                                    'job_master_id' => $primaryJm->id,
                                    'plan_id'       => $planId,
                                ];
                            }
                        }
                    }

                    // 2. Evaluasi kelengkapan form Repair & Reject di seluruh slice dalam schedule group
                    $groupRrLogs = collect();
                    $seenRrIds = [];
                    foreach ($groupJms as $gJm) {
                        foreach ($gJm->repairRejects ?? [] as $rr) {
                            if (isset($seenRrIds[$rr->id])) {
                                continue;
                            }
                            $seenRrIds[$rr->id] = true;
                            $groupRrLogs->push($rr);

                            $key = $rr->type === 'reject' ? 'reject' : 'repair';
                            $missingFields = [];
                            if (blank($rr->area_problem) || blank($rr->root_cause) || blank($rr->countermeasure)) {
                                $missingFields[] = 'area_problem/root_cause/countermeasure belum lengkap';
                            }
                            if ($rr->images()->count() === 0) {
                                $missingFields[] = 'Foto bukti (evidence) '.ucfirst($key).' wajib diunggah';
                            }
                            if (! empty($missingFields)) {
                                $issues[$key][] = [
                                    'item'          => $itemName,
                                    'issue'         => implode(' & ', $missingFields),
                                    'job_master_id' => $primaryJm->id,
                                    'plan_id'       => $planId,
                                ];
                            }
                        }
                    }

                    // 3. Akumulasi actual repair & reject untuk 1 schedule group utuh tepat 1 kali
                    $repairQty = 0;
                    $rejectQty = 0;
                    foreach ($groupJmIds as $gId) {
                        $jmDailies = $dailiesByJmId->get($gId, collect());
                        if ($jmDailies->isNotEmpty()) {
                            $repairQty += (int) $jmDailies->sum('actual_repair');
                            $rejectQty += (int) $jmDailies->sum('actual_reject');
                        }
                    }

                    // Fallback jika belum tersinkron di DailyProduction tapi sudah ada di ProductionLog pada window shift ini
                    if ($repairQty === 0) {
                        $repairQty = (int) \App\Models\ProductionLog::whereIn('job_master_id', $groupJmIds)
                            ->where(function ($q) use ($shiftStart, $shiftEnd, $date) {
                                $q->whereBetween('created_at', [$shiftStart, $shiftEnd])
                                    ->orWhereDate('created_at', $date);
                            })
                            ->sum('repair_qty');
                    }
                    if ($rejectQty === 0) {
                        $rejectQty = (int) \App\Models\ProductionLog::whereIn('job_master_id', $groupJmIds)
                            ->where(function ($q) use ($shiftStart, $shiftEnd, $date) {
                                $q->whereBetween('created_at', [$shiftStart, $shiftEnd])
                                    ->orWhereDate('created_at', $date);
                            })
                            ->sum('reject_qty');
                    }

                    $totalRepairLogged = (int) $groupRrLogs->where('type', 'repair')->sum('qty_a');
                    $totalRejectLogged = (int) $groupRrLogs->where('type', 'reject')->sum('qty_a');

                    if ($repairQty > 0 || $totalRepairLogged > 0) {
                        $review['repair'][] = [
                            'item'          => $itemName,
                            'issue'         => $totalRepairLogged >= $repairQty
                                ? $totalRepairLogged.' pcs sudah ada form lengkap'
                                : max(0, $repairQty - $totalRepairLogged).' pcs Repair belum ada form (Lapor: '.$repairQty.', Form: '.$totalRepairLogged.')',
                            'status'        => $totalRepairLogged >= $repairQty ? 'ok' : 'incomplete',
                            'qty'           => $repairQty,
                            'logged'        => $totalRepairLogged,
                            'job_master_id' => $primaryJm->id,
                            'plan_id'       => $planId,
                        ];
                    }

                    if ($rejectQty > 0 || $totalRejectLogged > 0) {
                        $review['reject'][] = [
                            'item'          => $itemName,
                            'issue'         => $totalRejectLogged >= $rejectQty
                                ? $totalRejectLogged.' pcs sudah ada form lengkap'
                                : max(0, $rejectQty - $totalRejectLogged).' pcs Reject belum ada form (Lapor: '.$rejectQty.', Form: '.$totalRejectLogged.')',
                            'status'        => $totalRejectLogged >= $rejectQty ? 'ok' : 'incomplete',
                            'qty'           => $rejectQty,
                            'logged'        => $totalRejectLogged,
                            'job_master_id' => $primaryJm->id,
                            'plan_id'       => $planId,
                        ];
                    }

                    if ($repairQty > 0 && $totalRepairLogged < $repairQty) {
                        $missingRepair = $repairQty - $totalRepairLogged;
                        $issues['repair'][] = [
                            'item'          => $itemName,
                            'issue'         => $missingRepair.' pcs Repair belum ada form detail (Lapor: '.$repairQty.', Form: '.$totalRepairLogged.')',
                            'job_master_id' => $primaryJm->id,
                            'plan_id'       => $planId,
                        ];
                    }

                    if ($rejectQty > 0 && $totalRejectLogged < $rejectQty) {
                        $missingReject = $rejectQty - $totalRejectLogged;
                        $issues['reject'][] = [
                            'item'          => $itemName,
                            'issue'         => $missingReject.' pcs Reject belum ada form detail (Lapor: '.$rejectQty.', Form: '.$totalRejectLogged.')',
                            'job_master_id' => $primaryJm->id,
                            'plan_id'       => $planId,
                        ];
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
            $date = $request->get('date', \App\Services\ProductionMetricsService::currentProductionDate());
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