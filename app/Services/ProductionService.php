<?php

namespace App\Services;

use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\Dandori;
use App\Models\ProductionLog;
use App\Models\HambatanJalur;
use App\Models\RepairRejectLog;
use App\Models\JobFinishReason;
use App\Models\LineMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardRealtimeService;

class ProductionService
{
    /**
     * Resolve the work date for a job context.
     * Checks if there's an active/existing ProductionSession for this job.
     * Otherwise falls back to current production work date (considering night shift cutoff).
     */
    public function resolveWorkDate(?int $jobId = null, ?string $fallbackDate = null): string
    {
        if ($fallbackDate) {
            return $fallbackDate;
        }

        if ($jobId) {
            $sessionDate = ProductionSession::where('job_master_id', $jobId)
                ->orderByDesc('id')
                ->value('work_date');
            if ($sessionDate) {
                return $sessionDate;
            }
        }

        return ProductionMetricsService::currentProductionDate();
    }
    /**
     * Start a job and its session.
     */
    public function startJob(int $jobId, bool $enqueueOnly = false, ?string $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $enqueueOnly, $workDate) {
            $effectiveWorkDate = $this->resolveWorkDate($jobId, $workDate);
            $session = ProductionSession::firstOrCreate(
                [
                    'job_master_id' => $jobId,
                    'work_date' => $effectiveWorkDate
                ]
            );

            // Clear skipped_at if this plan was previously skipped
            $planId = $this->resolvePlanId($jobId);
            if ($planId) {
                $plan = \App\Models\ProductionPlan::find($planId);
                if ($plan && $plan->skipped_at !== null) {
                    $plan->skipped_at = null;
                    $plan->status = 'approved';
                    $plan->save();
                }
            }

            $session->status = 'running';
            $session->save();

            // Auto cut-off: Stop and complete any other running job on the same line so only 1 job runs at a time
            $currentJobMaster = JobMaster::find($jobId);
            if ($currentJobMaster && $currentJobMaster->line) {
                $otherRunningJobs = JobMaster::where('line', $currentJobMaster->line)
                    ->where('id', '!=', $jobId)
                    ->whereIn(DB::raw('LOWER(status)'), ['running', 'paused'])
                    ->get();

                foreach ($otherRunningJobs as $otherJob) {
                    $this->finishJob($otherJob->id);
                }
            }

            $updateData = ['status' => 'running', 'finished_at' => null];
            
            if ($enqueueOnly) {
                $updateData['started_at'] = null;
            } else {
                $now = now();
                $updateData['started_at'] = $now;
                $session->save();

                // Close any existing open downtime before starting
                $openDowntime = Downtime::where('job_master_id', $jobId)
                    ->whereNull('finish_time')
                    ->first();
                if ($openDowntime) {
                    $openDowntime->update([
                        'finish_time' => $now,
                        'duration_seconds' => abs($now->diffInSeconds(Carbon::parse($openDowntime->start_time)))
                    ]);
                }
            }

            JobMaster::where('id', $jobId)->update($updateData);
            $this->syncPlanStatus($jobId, 'running');

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Start Dandori process for a job.
     */
    public function startDandori(int $jobId, ?string $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $workDate) {
            $workDate = $this->resolveWorkDate($jobId, $workDate);
            $job = JobMaster::findOrFail($jobId);
            
            // Auto cut-off: Stop and complete any other running job on the same line
            if ($job->line) {
                $otherRunningJobs = JobMaster::where('line', $job->line)
                    ->where('id', '!=', $jobId)
                    ->whereIn(DB::raw('LOWER(status)'), ['running', 'paused'])
                    ->get();

                foreach ($otherRunningJobs as $otherJob) {
                    $this->finishJob($otherJob->id);
                }
            }

            $job->update(['status' => 'running']);
            $this->syncPlanStatus($jobId, 'running');

            ProductionSession::firstOrCreate(
                [
                    'job_master_id' => $jobId,
                    'work_date' => $workDate
                ],
                [
                    'status' => 'running',
                    'start_time' => now()
                ]
            );
            
            $now = now();

            // Close any existing open downtime (e.g. auto-idle time) before starting Dandori
            $openDowntime = Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', '!=', 'dandori')
                ->whereNull('finish_time')
                ->first();
            if ($openDowntime) {
                $openDowntime->update([
                    'finish_time' => $now,
                    'duration_seconds' => abs($now->diffInSeconds(Carbon::parse($openDowntime->start_time)))
                ]);
            }
            
            // IDEMPOTENT: Jika sudah ada open downtime dandori, gunakan yang sudah ada
            $downtime = Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', 'dandori')
                ->whereNull('finish_time')
                ->first();

            if (!$downtime) {
                $downtime = Downtime::create([
                    'job_master_id' => $jobId,
                    'jenis_downtime' => 'dandori',
                    'problem' => 'PERSIAPAN (DANDORI)',
                    'start_time' => $now,
                    'penyebab' => '-',
                    'action' => '-',
                    'pic' => 'OPERATOR',
                    'image_path' => null,
                    'image_path_part' => null,
                    'image_path_tooling' => null,
                ]);
            }

            // IDEMPOTENT: Jika sudah ada open record dandori, jangan duplikasi
            $existingDandori = Dandori::where('next_job_id', $jobId)
                ->where('activity', 'DANDORI')
                ->whereNull('finish_time')
                ->first();

            if (!$existingDandori) {
                Dandori::create([
                    'next_job_id' => $jobId,
                    'line'        => $job->line,
                    'shift'       => $this->getShift(),
                    'activity'    => 'DANDORI',
                    'start_time'  => $now,
                    'work_date'   => $workDate,
                    'created_by'  => Auth::id()
                ]);
            }

            $this->signalDashboard($jobId);

            return $downtime;
        });
    }

    /**
     * Finish Dandori process.
     */
    public function cancelDandori(int $jobId, ?string $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $workDate) {
            $job = JobMaster::findOrFail($jobId);
            $workDate = $this->resolveWorkDate($jobId, $workDate);

            // 1. Hapus downtime dandori yang masih berjalan (finish_time is null)
            Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', 'dandori')
                ->whereNull('finish_time')
                ->delete();

            // 2. Hapus first check dandori yang masih berjalan
            Dandori::where('next_job_id', $jobId)
                ->whereNull('finish_time')
                ->delete();

            // 3. Jika belum ada output produksi sama sekali, kembalikan status Job ke pending
            $dp = DailyProduction::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->first();

            $totalActual = ($dp?->actual_ok ?? 0) + ($dp?->actual_repair ?? 0) + ($dp?->actual_reject ?? 0);
            if ($totalActual == 0) {
                $job->update([
                    'status' => 'pending',
                    'started_at' => null
                ]);
                $this->syncPlanStatus($jobId, 'pending');

                // Bersihkan session yang baru dibuat jika belum ada durasi
                ProductionSession::where('job_master_id', $jobId)
                    ->whereDate('work_date', $workDate)
                    ->where(function($q) {
                        $q->whereNull('total_seconds')->orWhere('total_seconds', 0);
                    })
                    ->whereNull('finish_time')
                    ->delete();
            }

            \Illuminate\Support\Facades\Cache::forget('was_in_first_check_' . $jobId);
            $this->signalDashboard($jobId);

            return true;
        });
    }

    public function finishDandori(int $jobId, ?string $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $workDate) {
            $now = now();
            $targetDate = $this->resolveWorkDate($jobId, $workDate);
            $downtimes = Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', 'dandori')
                ->whereNull('finish_time')
                ->get();

            foreach ($downtimes as $downtime) {
                $durationSeconds = abs($now->diffInSeconds(Carbon::parse($downtime->start_time)));
                $downtime->update([
                    'finish_time' => $now,
                    'duration_seconds' => $durationSeconds
                ]);
            }
            
            $dandoris = Dandori::where('next_job_id', $jobId)
                ->whereNull('finish_time')
                ->get();
            
            foreach ($dandoris as $dandori) {
                $duration = Carbon::parse($dandori->start_time)->diffInSeconds($now) / 60;
                $dandori->update([
                    'finish_time' => $now,
                    'duration_minutes' => round($duration, 2)
                ]);
            }

            // After dandori, job starts production
            $job = JobMaster::find($jobId);
            if ($job) {
                $jobUpdate = ['status' => 'running'];
                if (!$job->started_at) {
                    $jobUpdate['started_at'] = $now;
                }
                $job->update($jobUpdate);
                $this->syncPlanStatus($jobId, 'running');
            }
            
            $session = ProductionSession::firstOrCreate(
                ['job_master_id' => $jobId, 'work_date' => $targetDate]
            );
            $sessionUpdate = ['status' => 'running'];
            if (!$session->start_time) {
                $sessionUpdate['start_time'] = $now;
            }
            $session->update($sessionUpdate);

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Start 1st Check process for a job (during dandori).
     */
    public function startFirstCheck(int $jobId, ?string $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $workDate) {
            $workDate = $this->resolveWorkDate($jobId, $workDate);
            $job = JobMaster::findOrFail($jobId);
            $now = now();

            // IDEMPOTENT: Jika sudah ada open 1st check, return record tersebut (hindari duplikasi)
            $existing = Dandori::where('next_job_id', $jobId)
                ->where(function($q) {
                    $q->where('jenis_dandori', '1st_check')
                      ->orWhere('activity', '1ST CHECK');
                })
                ->whereNull('finish_time')
                ->first();

            if ($existing) {
                return $existing;
            }

            $dandori = Dandori::create([
                'next_job_id'   => $jobId,
                'line'          => $job->line,
                'shift'         => $this->getShift(),
                'activity'      => '1ST CHECK',
                'jenis_dandori' => '1st_check',
                'start_time'    => $now,
                'work_date'     => $workDate,
                'created_by'    => Auth::id()
            ]);

            $this->signalDashboard($jobId);

            return $dandori;
        });
    }

    /**
     * Finish 1st Check process.
     */
    public function finishFirstCheck(int $jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $dandoris = Dandori::where('next_job_id', $jobId)
                ->where(function($q) {
                    $q->where('jenis_dandori', '1st_check')
                      ->orWhere('activity', '1ST CHECK');
                })
                ->whereNull('finish_time')
                ->get();

            if ($dandoris->isNotEmpty()) {
                $now = now();
                foreach ($dandoris as $d) {
                    $duration = Carbon::parse($d->start_time)->diffInSeconds($now) / 60;
                    $d->update([
                        'finish_time'     => $now,
                        'duration_minutes' => round($duration, 2)
                    ]);
                }
                return true;
            }
            return false;
        });
    }

    /**
     * Save production log and update daily metrics.
     */
    public function saveProductionLog(int $jobId, array $data, ?string $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $data, $workDate) {
            $workDate = $this->resolveWorkDate($jobId, $workDate);

            $job = JobMaster::find($jobId);
            if ($job && in_array(strtolower($job->status), ['complete', 'finished', 'closed', 'done'])) {
                throw new \InvalidArgumentException("Job sudah selesai/tidak berjalan (Status: {$job->status}). Tidak dapat menambah kuantitas.");
            }

            $planId = $this->resolvePlanId($jobId);
            $plan = $planId ? \App\Models\ProductionPlan::find($planId) : null;
            if ($plan && in_array(strtolower($plan->status), ['complete', 'finished', 'closed', 'done'])) {
                throw new \InvalidArgumentException("Plan sudah selesai (Status: {$plan->status}). Tidak dapat menambah kuantitas.");
            }
            $maxTarget = (int) ($plan?->plan ?? $job?->target_qty ?? 0);

            $incomingOk = (int) ($data['ok_qty'] ?? 0);
            if ($incomingOk > 0 && $maxTarget > 0) {
                $currentOk = (int) ProductionLog::where('job_master_id', $jobId)
                    ->whereDate('created_at', $workDate)->sum('ok_qty');
                if (($currentOk + $incomingOk) > $maxTarget) {
                    $sisa = max(0, $maxTarget - $currentOk);
                    throw new \InvalidArgumentException("Target Plan maksimal {$maxTarget} PCS! Total OK tidak boleh melebihi target (Sisa: {$sisa} PCS).");
                }
            }

            $log = ProductionLog::create([
                'job_master_id' => $jobId,
                'ok_qty' => $data['ok_qty'] ?? 0,
                'repair_qty' => $data['repair_qty'] ?? 0,
                'reject_qty' => $data['reject_qty'] ?? 0,
            ]);

            $daily = DailyProduction::firstOrCreate([
                'job_master_id' => $jobId,
                'work_date' => $workDate
            ]);
            
            $actualQty = ProductionLog::where('job_master_id', $jobId)
                ->whereDate('created_at', $workDate)->sum('ok_qty');
            $actualRepair = ProductionLog::where('job_master_id', $jobId)
                ->whereDate('created_at', $workDate)->sum('repair_qty');
            $actualReject = ProductionLog::where('job_master_id', $jobId)
                ->whereDate('created_at', $workDate)->sum('reject_qty');

            $job = JobMaster::find($jobId);
            $targetQty = $job?->capacity ?? 0;
            
            $efficiency = 0;
            if ($targetQty > 0) {
                $efficiency = round(($actualQty / $targetQty) * 100, 2);
            }

            $runtimeSeconds = $this->calculateRuntime($jobId);

            $daily->update([
                'line' => $job?->line,
                'shift' => $this->getShift(),
                'actual_ok' => $actualQty,
                'actual_qty' => $actualQty,
                'actual_repair' => $actualRepair,
                'actual_reject' => $actualReject,
                'runtime_seconds' => $runtimeSeconds,
                'downtime_seconds' => Downtime::where('job_master_id', $jobId)
                    ->whereDate('created_at', $workDate)->sum('duration_seconds'),
                'efficiency' => $efficiency
            ]);

            // Sync quantities to PPC Production Plan
            $this->syncPlan($jobId, null, $actualQty, $actualRepair, $actualReject);

            $this->signalDashboard($jobId);

            return [
                'log' => $log,
                'actualQty' => $actualQty,
                'efficiency' => $efficiency,
                'runtime_seconds' => $runtimeSeconds
            ];
        });
    }

    /**
     * Pause a job.
     */
    public function pauseJob(int $jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $workDate = $this->resolveWorkDate($jobId);
            $runtime = $this->calculateRuntime($jobId);

            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->first();

            if ($session) {
                $session->total_seconds = $runtime;
                $session->status = 'paused';
                $session->pause_time = now();
                $session->save();
            }

            JobMaster::where('id', $jobId)->update(['status' => 'paused']);
            $this->syncPlanStatus($jobId, 'paused');

            DailyProduction::updateOrCreate(
                ['job_master_id' => $jobId, 'work_date' => $workDate],
                ['runtime_seconds' => $runtime]
            );

            $this->signalDashboard($jobId);

            return $runtime;
        });
    }

    /**
     * Resume a paused job.
     */
    public function resumeJob(int $jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $workDate = $this->resolveWorkDate($jobId);
            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->first();

            if ($session) {
                $session->start_time = now();
                $session->status = 'running';
                $session->save();
            }

            JobMaster::where('id', $jobId)->update(['status' => 'running']);
            $this->syncPlanStatus($jobId, 'running');

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Restart a job (reset metrics for today).
     */
    public function restartJob(int $jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $workDate = $this->resolveWorkDate($jobId);
            $session = ProductionSession::firstOrCreate(
                ['job_master_id' => $jobId, 'work_date' => $workDate]
            );

            $session->total_seconds = 0;
            $session->start_time    = now();
            $session->pause_time    = null;
            $session->finish_time   = null;
            $session->status        = 'running';
            $session->save();

            JobMaster::where('id', $jobId)->update([
                'status'      => 'running',
                'started_at'  => now(),
                'finished_at' => null
            ]);
            $this->syncPlanStatus($jobId, 'running');

            DailyProduction::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->update(['runtime_seconds' => 0]);

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Finish a job and sync metrics.
     */
    public function finishJob(int $jobId, int|string|null $nextJobId = null, bool $skipIdle = false, int|float|null $finalOk = null, int|float|null $finalRepair = null, int|float|null $finalReject = null, array $skippedActions = [], ?string $reasonNotAchieved = null)
    {
        return DB::transaction(function () use ($jobId, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject, $skippedActions, $reasonNotAchieved) {
            $workDate = $this->resolveWorkDate($jobId);

            // Auto-close any active downtimes for this job
            Downtime::where('job_master_id', $jobId)
                ->whereNull('finish_time')
                ->update(['finish_time' => now()]);

            $runtime = $this->calculateRuntime($jobId);

            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->first();

            if ($session) {
                $session->total_seconds = (int) $runtime;
                $session->status = 'finished';
                $session->finish_time = now();
                $session->save();
            }

            $job = JobMaster::find($jobId);
            JobMaster::where('id', $jobId)->update([
                'status' => 'complete',
                'finished_at' => now()
            ]);
            $this->syncPlanStatus($jobId, 'complete');

            if ($finalOk !== null || $finalRepair !== null || $finalReject !== null) {
                // Finalisasi: ambil nilai repair & reject yang valid dari form R&R (harus punya foto evidence)
                $loggedRepair = (int) RepairRejectLog::where('job_master_id', $jobId)
                    ->where('type', 'repair')
                    ->whereDate('created_at', $workDate)
                    ->sum('qty_a');
                $loggedReject = (int) RepairRejectLog::where('job_master_id', $jobId)
                    ->where('type', 'reject')
                    ->whereDate('created_at', $workDate)
                    ->sum('qty_a');

                $resolvedRepair = $loggedRepair > 0 ? $loggedRepair : ($finalRepair ?? 0);
                $resolvedReject = $loggedReject > 0 ? $loggedReject : ($finalReject ?? 0);

                // Replace semua log dengan nilai final yang terverifikasi
                ProductionLog::where('job_master_id', $jobId)
                    ->whereDate('created_at', $workDate)
                    ->delete();
                ProductionLog::create([
                    'job_master_id' => $jobId,
                    'ok_qty'        => $finalOk ?? 0,
                    'repair_qty'    => $resolvedRepair,
                    'reject_qty'    => $resolvedReject,
                ]);
                $totalOk     = $finalOk ?? 0;
                $totalRepair = $resolvedRepair;
                $totalReject = $resolvedReject;
            } else {
                $totalOk     = ProductionLog::where('job_master_id', $jobId)->whereDate('created_at', $workDate)->sum('ok_qty');
                $totalRepair = ProductionLog::where('job_master_id', $jobId)->whereDate('created_at', $workDate)->sum('repair_qty');
                $totalReject = ProductionLog::where('job_master_id', $jobId)->whereDate('created_at', $workDate)->sum('reject_qty');
            }

            DailyProduction::updateOrCreate(
                ['job_master_id' => $jobId, 'work_date' => $workDate],
                [
                    'runtime_seconds' => $runtime,
                    'actual_ok'       => $totalOk,
                    'actual_qty'      => $totalOk,
                    'actual_repair'   => $totalRepair,
                    'actual_reject'   => $totalReject,
                    'efficiency'      => ($job && $job->target_qty > 0) ? ($totalOk / $job->target_qty) * 100 : (($job && $job->capacity > 0) ? ($totalOk / $job->capacity) * 100 : 0)
                ]
            );

            // AUTO-START NEXT JOB WITH DANDORI IF SPECIFIED OR AUTO-DETECT
            if ($nextJobId === 'STOP_SESSION' || $nextJobId === 'FINISH_ONLY') {
                $resolvedNextJobId = null;
            } else {
                $resolvedNextJobId = $nextJobId;
                if (empty($resolvedNextJobId)) {
                    $nextJob = $this->getNextJob($jobId);
                    if ($nextJob) {
                        $resolvedNextJobId = $nextJob->id;
                    }
                }
            }

            if ($resolvedNextJobId) {
                $this->startDandori($resolvedNextJobId);
            }

            $this->signalDashboard($jobId);

            // --- Auto-recovery: queue short-of-plan items ---
            $planId = $this->resolvePlanId($jobId);
            $mismatch = null;
            $autoRecoveryItem = null;
            $job = \App\Models\JobMaster::find($jobId);
            $actualQtyOk = $finalOk !== null ? (float) $finalOk : (float) ($totalOk ?? 0);
            $actualQtyTotal = ($finalOk !== null ? (float) $finalOk : (float) ($totalOk ?? 0)) + ($finalRepair !== null ? (float) $finalRepair : (float) ($totalRepair ?? 0)) + ($finalReject !== null ? (float) $finalReject : (float) ($totalReject ?? 0));

            if ($planId) {
                $parts = explode('-', $job->job_number);
                $embeddedPlanId = end($parts);
                if (is_numeric($embeddedPlanId) && $planId != $embeddedPlanId) {
                    $mismatch = [
                        'embedded_plan_id' => (int) $embeddedPlanId,
                        'resolved_plan_id' => $planId,
                    ];
                }

                $plan = \App\Models\ProductionPlan::find($planId);
                $planQty = (float) (($plan && (float) ($plan->plan ?? 0) > 0) ? $plan->plan : ($job->target_qty ?? 0));
                if ($planQty > 0 && $actualQtyTotal < $planQty) {
                    $recoveryQty = $planQty - $actualQtyOk;
                    if ($recoveryQty < 0) {
                        $recoveryQty = $planQty - $actualQtyTotal;
                    }
                    if ($recoveryQty > 0) {
                        $autoRecoveryItem = $this->createRecoveryItem($plan, $job, $actualQtyOk, $recoveryQty, 'waiting_approval');
                    }
                    if ($reasonNotAchieved && \Illuminate\Support\Facades\Schema::hasTable('job_finish_reasons')) {
                        try {
                            $lineId = null;
                            $lineName = $job->line;
                            if ($lineName) {
                                $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineName)));
                                $lm = LineMaster::whereRaw("REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"])->first();
                                if ($lm) {
                                    $lineId = $lm->id;
                                }
                            }
                            JobFinishReason::create([
                                'job_master_id' => $job->id,
                                'production_plan_id' => $plan ? $plan->id : null,
                                'user_id' => Auth::id(),
                                'line_id' => $lineId,
                                'line_name' => $lineName,
                                'work_date' => $workDate,
                                'shift' => request()->header('X-Shift') ?: request('shift') ?: $this->getShift(),
                                'target_qty' => (int) $planQty,
                                'actual_ok' => (int) ($finalOk ?? $totalOk ?? 0),
                                'actual_repair' => (int) ($finalRepair ?? $totalRepair ?? 0),
                                'actual_reject' => (int) ($finalReject ?? $totalReject ?? 0),
                                'actual_total' => (int) $actualQtyTotal,
                                'reason' => $reasonNotAchieved,
                            ]);
                        } catch (\Throwable $reasonEx) {
                            \Illuminate\Support\Facades\Log::warning('Failed to save JobFinishReason: ' . $reasonEx->getMessage());
                        }
                    }
                }
            }

            if (!$autoRecoveryItem && $job) {
                $planQty = (float) ($job->target_qty ?? 0);
                if ($planQty > 0 && $actualQtyTotal < $planQty) {
                    $recoveryQty = $planQty - $actualQtyOk;
                    if ($recoveryQty < 0) {
                        $recoveryQty = $planQty - $actualQtyTotal;
                    }
                    if ($recoveryQty > 0) {
                        $autoRecoveryItem = $this->createRecoveryItem(null, $job, $actualQtyOk, $recoveryQty, 'waiting_approval', true);
                    }
                    if ($reasonNotAchieved && \Illuminate\Support\Facades\Schema::hasTable('job_finish_reasons')) {
                        try {
                            $lineId = null;
                            $lineName = $job->line;
                            if ($lineName) {
                                $normalized = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineName)));
                                $lm = LineMaster::whereRaw("REPLACE(REPLACE(UPPER(TRIM(line_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$normalized}%"])->first();
                                if ($lm) {
                                    $lineId = $lm->id;
                                }
                            }
                            JobFinishReason::create([
                                'job_master_id' => $job->id,
                                'production_plan_id' => null,
                                'user_id' => Auth::id(),
                                'line_id' => $lineId,
                                'line_name' => $lineName,
                                'work_date' => $workDate,
                                'shift' => request()->header('X-Shift') ?: request('shift') ?: $this->getShift(),
                                'target_qty' => (int) $planQty,
                                'actual_ok' => (int) ($finalOk ?? $totalOk ?? 0),
                                'actual_repair' => (int) ($finalRepair ?? $totalRepair ?? 0),
                                'actual_reject' => (int) ($finalReject ?? $totalReject ?? 0),
                                'actual_total' => (int) $actualQtyTotal,
                                'reason' => $reasonNotAchieved,
                            ]);
                        } catch (\Throwable $reasonEx) {
                            \Illuminate\Support\Facades\Log::warning('Failed to save JobFinishReason: ' . $reasonEx->getMessage());
                        }
                    }
                }
            }

            if ($autoRecoveryItem) {
                $this->notifyPpcUsers($job, $autoRecoveryItem);
            }

            // --- Handle skipped jobs ---
            $skippedResult = [];
            foreach ($skippedActions as $skippedJobId => $action) {
                $skippedJob = \App\Models\JobMaster::find($skippedJobId);
                if (!$skippedJob) continue;

                $skippedPlanId = $this->resolvePlanId($skippedJobId);
                $skippedPlan = $skippedPlanId ? \App\Models\ProductionPlan::find($skippedPlanId) : null;
                $status = $action === 'continue' ? 'continue' : 'waiting_approval';

                if ($skippedPlan) {
                    $planQty = (float) ($skippedPlan->plan ?? 0);
                    $recoveryQty = $planQty;
                    $recoveryItem = $this->createRecoveryItem($skippedPlan, $skippedJob, 0, $recoveryQty, $status);
                    $skippedPlan->skipped_at = now();
                    $skippedPlan->save();
                } else {
                    $recoveryQty = (float) ($skippedJob->target_qty ?? 0);
                    $recoveryItem = $this->createRecoveryItem(null, $skippedJob, 0, $recoveryQty, $status);
                }

                $skippedResult[] = [
                    'job_id' => $skippedJobId,
                    'action' => $action,
                    'recovery_id' => $recoveryItem->id,
                ];

                $this->notifyPpcUsers($skippedJob, $recoveryItem);
            }

            return [
                'runtime' => $runtime,
                'mismatch' => $mismatch,
                'skipped' => $skippedResult,
                'recovery' => $autoRecoveryItem ? [
                    'id' => $autoRecoveryItem->id,
                    'recovery_qty' => $autoRecoveryItem->recovery_qty,
                    'job_no' => $autoRecoveryItem->job_no,
                ] : null
            ];
        });
    }

    // autoStartNextJobAsIdle removed — flow langsung Dandori

    /**
     * Start a downtime event.
     */
    public function startDowntime(int $jobId, array $data)
    {
        return DB::transaction(function () use ($jobId, $data) {
            // Pessimistic locking on JobMaster to serialize concurrent downtime requests
            $job = JobMaster::where('id', $jobId)->lockForUpdate()->first();
            if (!$job) {
                throw new \Exception('Job tidak ditemukan.');
            }

            $dtType = $data['jenis_downtime'] ?? 'other';

            // Idempotency check: if an open downtime with the exact same type already exists, return it
            $existing = Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', $dtType)
                ->whereNull('finish_time')
                ->first();

            if ($existing) {
                return $existing;
            }

            // Auto-break guard: if a manual downtime is currently active, do NOT start auto break
            if (strtolower(trim($dtType)) === 'break time') {
                $activeManual = Downtime::where('job_master_id', $jobId)
                    ->whereNull('finish_time')
                    ->where('jenis_downtime', '!=', 'break time')
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->first();
                if ($activeManual) {
                    // Manual downtime has precedence; Auto Break is not started
                    return null;
                }
            }

            $downtime = Downtime::create([
                'job_master_id' => $jobId,
                'jenis_downtime' => $dtType,
                'problem' => $data['problem'] ?? null,
                'penyebab' => $data['penyebab'] ?? null,
                'action' => $data['action'] ?? null,
                'pic' => $data['pic'] ?? null,
                'source' => $data['source'] ?? null,
                'image_path' => null,
                'image_path_part' => null,
                'image_path_tooling' => null,
                'start_time' => now()
            ]);

            $this->syncHambatanJalur($downtime);

            $this->signalDashboard($jobId);

            return $downtime;
        });
    }

    public function syncHambatanJalur(Downtime $downtime): void
    {
        $map = [
            'mesin' => 'MT',
            'dies' => 'DT',
            'material' => 'MST',
            'logistic' => 'LOGT',
            'produksi' => 'Prot',
        ];

        $jenisHambatan = $map[strtolower($downtime->jenis_downtime)] ?? null;
        if (!$jenisHambatan) {
            return;
        }

        $jobMaster = $downtime->jobMaster;
        $lineName = $jobMaster->line ?? null;
        $jobNo = $jobMaster->job_number ?? null;
        $namaPart = $jobMaster->job_name ?? null;

        $hj = HambatanJalur::where('downtime_id', $downtime->id)->first();

        $attrs = [
            'line_name' => $lineName,
            'mesin' => $lineName,
            'job_no' => $jobNo,
            'nama_part' => $namaPart,
            'jenis_hambatan' => $jenisHambatan,
            'problem' => $downtime->problem,
            'penyebab' => $downtime->penyebab,
            'penanggulangan' => $downtime->action,
            'pic_hambatan' => $downtime->pic,
            'waktu' => $downtime->start_time,
        ];

        if ($hj) {
            $hj->update($attrs);
        } else {
            $attrs['downtime_id'] = $downtime->id;
            $attrs['sub_jenis'] = null;
            $attrs['status'] = 'open';
            HambatanJalur::create($attrs);
        }
    }

    /**
     * Finish a downtime event.
     */
    public function finishDowntime(int $downtimeId)
    {
        return DB::transaction(function () use ($downtimeId) {
            $downtime = Downtime::where('id', $downtimeId)->lockForUpdate()->first();
            if (!$downtime) return null;

            // Robust idempotent check:
            // 1. If both finish_time and valid duration_seconds are set, return immediately.
            if ($downtime->finish_time !== null && $downtime->duration_seconds !== null && $downtime->duration_seconds > 0) {
                return $downtime;
            }

            // 2. If finish_time already exists but duration_seconds is missing/zero:
            //    Preserve the existing finish_time and compute duration strictly from start_time to that finish_time.
            if ($downtime->finish_time !== null) {
                $finishTime = Carbon::parse($downtime->finish_time);
                $startTime = Carbon::parse($downtime->start_time ?: $downtime->created_at ?: $finishTime);
                $durationSeconds = (int) abs($finishTime->diffInSeconds($startTime));

                $downtime->update([
                    'duration_seconds' => $durationSeconds
                ]);
            }
            // 3. If duration_seconds already exists but finish_time is missing:
            //    Backfill finish_time as start_time + duration_seconds (do NOT invent now()).
            elseif ($downtime->duration_seconds !== null && $downtime->duration_seconds > 0) {
                $startTime = Carbon::parse($downtime->start_time ?: $downtime->created_at ?: now());
                $finishTime = $startTime->copy()->addSeconds((int) $downtime->duration_seconds);
                $durationSeconds = (int) $downtime->duration_seconds;

                $downtime->update([
                    'finish_time' => $finishTime
                ]);
            }
            // 4. Clean finish: neither finish_time nor duration_seconds is set yet.
            else {
                $finishTime = now();
                $startTime = Carbon::parse($downtime->start_time ?: $downtime->created_at ?: now());
                $durationSeconds = (int) abs($finishTime->diffInSeconds($startTime));

                $downtime->update([
                    'finish_time' => $finishTime,
                    'duration_seconds' => $durationSeconds
                ]);
            }

            // Resolve target work_date strictly from the active session of this job
            $session = ProductionSession::where('job_master_id', $downtime->job_master_id)
                ->orderByDesc('id')
                ->first();

            $targetWorkDate = $session?->work_date;
            if (!$targetWorkDate) {
                $daily = DailyProduction::where('job_master_id', $downtime->job_master_id)
                    ->orderByDesc('id')
                    ->first();
                $targetWorkDate = $daily?->work_date;
            }

            if (!$targetWorkDate) {
                $targetWorkDate = Carbon::parse($downtime->start_time ?: $downtime->created_at)->toDateString();
            }

            // Calculate shift window bounds for the target session to accurately scope downtimes
            // Shift Pagi: work_date 07:00 -> work_date 19:00
            // Shift Malam: work_date 19:00 -> work_date + 1 day 07:00
            $windowStart = Carbon::parse($targetWorkDate)->setTime(7, 0, 0);
            $windowEnd = Carbon::parse($targetWorkDate)->addDay()->setTime(7, 0, 0);

            // Sum finished downtimes for this job that strictly fall within this session's window
            $totalDowntime = Downtime::where('job_master_id', $downtime->job_master_id)
                ->whereNotNull('finish_time')
                ->where(function ($q) use ($windowStart, $windowEnd, $targetWorkDate) {
                    $q->whereBetween('start_time', [$windowStart, $windowEnd])
                      ->orWhere(function ($sub) use ($windowStart, $windowEnd) {
                          $sub->whereNull('start_time')
                              ->whereBetween('created_at', [$windowStart, $windowEnd]);
                      });
                })
                ->sum('duration_seconds');

            DailyProduction::updateOrCreate(
                ['job_master_id' => $downtime->job_master_id, 'work_date' => $targetWorkDate],
                ['downtime_seconds' => $totalDowntime]
            );

            $this->signalDashboard($downtime->job_master_id);

            return $downtime;
        });
    }

    /**
     * Delete a downtime event and recalculate totals.
     */
    public function deleteDowntime(int $downtimeId)
    {
        return DB::transaction(function () use ($downtimeId) {
            $downtime = Downtime::find($downtimeId);
            if (!$downtime) return false;

            $jobId = $downtime->job_master_id;
            $workDate = $this->resolveWorkDate($jobId);
            $downtime->delete();

            $totalDowntime = Downtime::where('job_master_id', $jobId)
                ->whereDate('created_at', $workDate)
                ->sum('duration_seconds');

            DailyProduction::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->update(['downtime_seconds' => $totalDowntime]);

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Calculate current runtime in seconds for a job.
     */
    public function calculateRuntime(int $jobId)
    {
        $workDate = $this->resolveWorkDate($jobId);
        $session = ProductionSession::where('job_master_id', $jobId)
            ->whereDate('work_date', $workDate)
            ->first();

        if (!$session) return 0;

        $total = (int)$session->total_seconds;
        if ($session->status == 'running' && $session->start_time) {
            $startTime = Carbon::parse($session->start_time);
            $total += max(0, (int)round(abs(now()->floatDiffInSeconds($startTime))));
        }

        return $total;
    }

    /**
     * Save daily production summary data.
     */
    public function saveDailyProduction(int $jobId, array $data)
    {
        return DB::transaction(function () use ($jobId, $data) {
            $runtime = $this->calculateRuntime($jobId);
            $job = JobMaster::find($jobId);

            // Resolve work_date & shift from request/session/plan to avoid jumping to today/current shift
            $workDate = $data['work_date'] ?? request()->header('X-Date') ?? request('date');
            $shiftName = $data['shift'] ?? request()->header('X-Shift') ?? request('shift');

            if (!$workDate || !$shiftName) {
                $sessionCtx = ProductionSession::where('job_master_id', $jobId)->orderByDesc('id')->first();
                if ($sessionCtx) {
                    $workDate = $workDate ?: $sessionCtx->work_date;
                    $shiftName = $shiftName ?: $sessionCtx->shift;
                }
            }

            if (!$workDate) {
                $parts = explode('-', $job?->job_number ?? '');
                $planId = end($parts);
                if (is_numeric($planId)) {
                    $plan = \App\Models\ProductionPlan::find($planId);
                    if ($plan) {
                        $workDate = $workDate ?: ($plan->plan_date ? \Carbon\Carbon::parse($plan->plan_date)->toDateString() : null);
                        $shiftName = $shiftName ?: $plan->shift_name;
                    }
                }
            }

            $workDate = $workDate ?: $this->resolveWorkDate($jobId);
            $shiftName = $shiftName ?: $this->getShift();

            $sessionForDowntime = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', $workDate)
                ->first() ?: ProductionSession::where('job_master_id', $jobId)->orderByDesc('id')->first();

            $downtime = (int) ($sessionForDowntime->downtime_seconds ?? 0);

            $targetQty = $job?->capacity ?? 0;

            // Load existing to prevent zeroing on partial/manual saves
            $existing = DailyProduction::where('job_master_id', $jobId)
                ->where('work_date', $workDate)
                ->first();

            $actualQty = array_key_exists('actual_qty', $data)
                ? (int) ($data['actual_qty'] ?? 0)
                : ($existing->actual_ok ?? $existing->actual_qty ?? 0);

            $repairQty = array_key_exists('repair_qty', $data)
                ? (int) ($data['repair_qty'] ?? 0)
                : ($existing->actual_repair ?? $existing->repair_qty ?? 0);

            $rejectQty = array_key_exists('reject_qty', $data)
                ? (int) ($data['reject_qty'] ?? 0)
                : ($existing->actual_reject ?? $existing->reject_qty ?? 0);

            $efficiency = 0;
            if ($targetQty > 0) {
                $efficiency = round(($actualQty / $targetQty) * 100, 2);
            }

            DailyProduction::updateOrCreate(
                [
                    'job_master_id' => $jobId,
                    'work_date'     => $workDate,
                ],
                [
                    'line'              => $job?->line,
                    'shift'             => $shiftName,
                    'target_qty'        => $targetQty,
                    'actual_qty'        => $actualQty,
                    'actual_ok'         => $actualQty,
                    'actual_repair'     => $repairQty,
                    'actual_reject'     => $rejectQty,
                    'reject_qty'        => $rejectQty,
                    'repair_qty'        => $repairQty,
                    'runtime_seconds'   => $runtime,
                    'downtime_seconds'  => $downtime,
                    'efficiency'        => $efficiency,
                    'remarks'           => $data['remarks'] ?? $existing->remarks ?? null,
                    'saved_by'          => Auth::id(),
                    'status'            => 'complete',
                ]
            );

            // Sync status and quantities to PPC Production Plan
            $this->syncPlan($jobId, 'complete', $actualQty, $repairQty, $rejectQty);

            $this->signalDashboard($jobId);

            return [
                'runtime' => $runtime,
                'efficiency' => $efficiency
            ];
        });
    }


    private function resolvePlanId(int $jobId): ?int
    {
        $job = \App\Models\JobMaster::find($jobId);
        if (!$job) return null;

        $parts = explode('-', $job->job_number);
        $planId = end($parts);

        if (is_numeric($planId)) {
            $exists = \App\Models\ProductionPlan::where('id', $planId)->exists();
            if ($exists) {
                return (int) $planId;
            }
        }

        $jobPrefix = count($parts) > 1 ? implode('-', array_slice($parts, 0, -1)) : $job->job_number;
        
        // Cek sesi aktif untuk mendapatkan work_date yang sebenarnya
        $session = \App\Models\ProductionSession::where('job_master_id', $jobId)->orderByDesc('id')->first();
        $targetDate = $session?->work_date ?: ProductionMetricsService::currentProductionDate();

        $plan = \App\Models\ProductionPlan::whereDate('plan_date', $targetDate)
            ->where('row_type', 'job')
            ->where(function ($q) use ($jobPrefix, $job) {
                $q->where('job_no', $jobPrefix)
                  ->orWhere('job_no', $job->job_number)
                  ->orWhere('job_master', $job->job_name);
            })
            ->first();

        if (!$plan) {
            // Fallback: cari plan terbaru dengan job_no atau job_master yang sesuai
            $plan = \App\Models\ProductionPlan::where('row_type', 'job')
                ->where(function ($q) use ($jobPrefix, $job) {
                    $q->where('job_no', $jobPrefix)
                      ->orWhere('job_no', $job->job_number)
                      ->orWhere('job_master', $job->job_name);
                })
                ->orderByDesc('plan_date')
                ->orderByDesc('id')
                ->first();
        }

        return $plan?->id;
    }

    private function createRecoveryItem(?\App\Models\ProductionPlan $plan, JobMaster $job, int|float $actualOk, float $recoveryQty, string $status, bool $isUnresolvable = false)
    {
        $date = $this->resolveWorkDate($job->id);
        $shiftName = $this->getShift();
        $schedule = \App\Models\RecoverySchedule::firstOrCreate(
            [
                'plan_date' => $date,
                'shift_name' => $shiftName,
                'press_name' => $plan ? $plan->press_name : $job->line,
            ],
            ['status' => 'waiting_approval']
        );

        $attributes = [
            'recovery_schedule_id' => $schedule->id,
            'production_plan_id' => $plan ? $plan->id : null,
            'job_no' => $plan && !empty($plan->job_no) ? $plan->job_no : $job->job_number,
            'job_name' => $job->job_name,
            'job_master' => $job->job_name,
            'press_name' => $plan ? $plan->press_name : $job->line,
            'line' => $job->line,
            'plan_qty' => $plan ? (float) ($plan->plan ?? 0) : (float) ($job->target_qty ?? $recoveryQty),
            'ok' => $actualOk,
            'repair' => 0,
            'reject' => 0,
            'ct_detik' => $plan ? (float) ($plan->ct_detik ?? 0) : 0,
            'dct' => $plan ? (float) ($plan->dct ?? 0) : 0,
            'total_mesin' => $plan ? (int) ($plan->total_mesin ?? 1) : 1,
            'original_date' => $date,
            'original_shift_name' => $shiftName,
            'source_date' => $date,
            'source_shift' => $shiftName,
            'actual_qty' => $actualOk,
            'recovery_qty' => $recoveryQty,
            'queued_at' => now(),
            'status' => $status
        ];

        if ($plan) {
            $existingItem = \App\Models\RecoveryItem::where('production_plan_id', $plan->id)
                ->whereIn('status', ['waiting_approval', 'continue', 'approved', 'scheduled', 'in_production'])
                ->first();

            if ($existingItem) {
                $existingItem->update($attributes);
                return $existingItem;
            }
        }

        return \App\Models\RecoveryItem::create($attributes);
    }

    private function notifyPpcUsers(JobMaster $job, \App\Models\RecoveryItem $recoveryItem)
    {
        $ppcUsers = \App\Models\User::whereRaw("LOWER(role) LIKE '%ppc%'")
            ->orWhereIn('role', ['admin', 'superadmin', 'ppc'])
            ->get();
        foreach ($ppcUsers as $user) {
            $user->notify(new \App\Notifications\ItemTidakTercapaiNotification($job, $recoveryItem));
        }
    }

    /**
     * Determine current work shift based on time.
     */
    private function getShift()
    {
        $h = (int) now()->format('H');
        $m = (int) now()->format('i');
        $timeInMin = $h * 60 + $m;

        // Shift Pagi: 07:30 - 21:00 (450 - 1260)
        // Shift Malam: 21:00 - 07:30
        if ($timeInMin >= 450 && $timeInMin < 1260) {
            return 'Shift Pagi';
        }

        return 'Shift Malam';
    }

    /**
     * Get the next job in sequence or by manual selection.
     */
    public function getNextJob(int $currentJobId, int|string|null $selectedNextId = null)
    {
        if ($selectedNextId) {
            return JobMaster::where('id', $selectedNextId)
                ->whereNotIn(DB::raw('LOWER(status)'), ['complete'])
                ->first();
        }

        $current = JobMaster::find($currentJobId);
        if (!$current) return null;

        // Try resolving the next job using the planned sequence sheet
        $parts = explode('-', $current->job_number);
        $planId = end($parts);

        if (is_numeric($planId)) {
            $currentPlan = \App\Models\ProductionPlan::find($planId);
            if ($currentPlan) {
                $nextPlan = \App\Models\ProductionPlan::where('plan_date', $currentPlan->plan_date)
                    ->where('shift_name', $currentPlan->shift_name)
                    ->where('press_name', $currentPlan->press_name)
                    ->where('row_type', 'job')
                    ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                    ->where('row_no', '>', $currentPlan->row_no)
                    ->orderBy('row_no', 'asc')
                    ->first();

                if ($nextPlan) {
                    $nextIdentifier = $nextPlan->job_no ? ($nextPlan->job_no . '-' . $nextPlan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($nextPlan->job_master) . '-' . $nextPlan->id);
                    $nextJob = JobMaster::where('job_number', $nextIdentifier)
                        ->whereNotIn(DB::raw('LOWER(status)'), ['complete'])
                        ->first();
                    if ($nextJob) {
                        return $nextJob;
                    }
                }
            }
        }

        // Fallback to old query, tetapi batasi agar tetap di shift yang sama bila plan terdeteksi
        $fallbackQuery = JobMaster::whereNotIn(DB::raw('LOWER(status)'), ['complete'])
            ->where('line', $current->line)
            ->where('id', '!=', $currentJobId);

        if (isset($currentPlan) && $currentPlan) {
            $fallbackQuery->where(function($fq) use ($currentPlan) {
                $fq->whereExists(function($sub) use ($currentPlan) {
                    $sub->select(DB::raw(1))
                        ->from('production_plans')
                        ->whereColumn('production_plans.job_no', 'job_masters.job_number')
                        ->whereDate('production_plans.plan_date', $currentPlan->plan_date)
                        ->where('production_plans.shift_name', $currentPlan->shift_name);
                })->orWhereRaw("EXISTS (
                    SELECT 1 FROM production_plans pp
                    WHERE pp.id = CAST(SUBSTRING_INDEX(job_masters.job_number, '-', -1) AS UNSIGNED)
                      AND pp.plan_date = ?
                      AND pp.shift_name = ?
                )", [$currentPlan->plan_date, $currentPlan->shift_name]);
            });
        }

        return $fallbackQuery->orderBy('sequence_no')
            ->orderBy('id')
            ->first();
    }

    /**
     * Sync ProductionPlan status and quantities based on JobMaster
     */
    private function syncPlan(int $jobId, ?string $status = null, int|float|null $ok = null, int|float|null $repair = null, int|float|null $reject = null)
    {
        $jobMaster = \App\Models\JobMaster::find($jobId);
        if (!$jobMaster) return;

        // Ekstrak Plan ID dari job_number (Format: JOB_NO-PLAN_ID atau AUTO-SLUG-PLAN_ID)
        $parts = explode('-', $jobMaster->job_number);
        $planId = end($parts);

        if (is_numeric($planId)) {
            $updateData = [];

            if ($status !== null) {
                $mappedStatus = strtolower($status);
                if ($mappedStatus === 'running' || $mappedStatus === 'paused') {
                    $mappedStatus = 'approved';
                } elseif ($mappedStatus === 'complete') {
                    $mappedStatus = 'completed';
                }

                if (in_array($mappedStatus, ['pending', 'approved', 'completed'])) {
                    $updateData['status'] = $mappedStatus;
                }
            }

            // Jika salah satu kuantitas bernilai null, ambil secara dinamis dari database harian
            if ($ok === null || $repair === null || $reject === null) {
                $workDate = $this->resolveWorkDate($jobId);
                $daily = \App\Models\DailyProduction::where('job_master_id', $jobId)
                    ->whereDate('work_date', $workDate)
                    ->first();
                if ($daily) {
                    $ok = $ok ?? $daily->actual_qty;
                    $repair = $repair ?? ($daily->actual_repair ?: $daily->repair_qty);
                    $reject = $reject ?? ($daily->actual_reject ?: $daily->reject_qty);
                } else {
                    $ok = $ok ?? \App\Models\ProductionLog::where('job_master_id', $jobId)
                        ->whereDate('created_at', $workDate)->sum('ok_qty');
                    $repair = $repair ?? \App\Models\ProductionLog::where('job_master_id', $jobId)
                        ->whereDate('created_at', $workDate)->sum('repair_qty');
                    $reject = $reject ?? \App\Models\ProductionLog::where('job_master_id', $jobId)
                        ->whereDate('created_at', $workDate)->sum('reject_qty');
                }
            }

            $updateData['ok'] = (float) ($ok ?? 0);
            $updateData['repair'] = (float) ($repair ?? 0);
            $updateData['reject'] = (float) ($reject ?? 0);

            // Sync actual times to PPC
            if ($jobMaster->started_at) {
                $updateData['act_start'] = \Carbon\Carbon::parse($jobMaster->started_at)->format('H:i:s');
            }
            if ($jobMaster->finished_at) {
                $updateData['act_finish'] = \Carbon\Carbon::parse($jobMaster->finished_at)->format('H:i:s');
            } elseif (in_array(strtolower($jobMaster->status), ['complete', 'finished', 'closed'])) {
                $updateData['act_finish'] = \Carbon\Carbon::parse($jobMaster->updated_at)->format('H:i:s');
            }

            \App\Models\ProductionPlan::where('id', $planId)->update($updateData);

            // Recovery Lock: if this plan has a recovery_id and ok > 0,
            // set the RecoveryItem status to in_production automatically
            $plan = \App\Models\ProductionPlan::find($planId);
            if ($plan && $plan->recovery_id && (float)($plan->ok ?? 0) > 0) {
                \App\Models\RecoveryItem::where('id', $plan->recovery_id)
                    ->where('status', 'scheduled')
                    ->update(['status' => 'in_production']);
            }
        }
    }

    /**
     * Sync ProductionPlan status based on JobMaster
     */
    private function syncPlanStatus(int $jobId, string $status)
    {
        $this->syncPlan($jobId, $status);
    }

    private function signalDashboard(int $jobId): void
    {
        $job = JobMaster::find($jobId);
        if ($job && $job->line) {
            DashboardRealtimeService::signalUpdate($job->line);
        }
    }
}
