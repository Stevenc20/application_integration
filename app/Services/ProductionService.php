<?php

namespace App\Services;

use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\Dandori;
use App\Models\ProductionLog;
use App\Models\HambatanJalur;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\DashboardRealtimeService;

class ProductionService
{
    /**
     * Start a job and its session.
     */
    public function startJob($jobId, $enqueueOnly = false)
    {
        return DB::transaction(function () use ($jobId, $enqueueOnly) {
            $session = ProductionSession::firstOrCreate(
                [
                    'job_master_id' => $jobId,
                    'work_date' => now()->toDateString()
                ]
            );

            $session->status = 'running';
            $session->save();

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

                // Close any existing open dandori before starting
                Dandori::where('next_job_id', $jobId)
                    ->whereNull('finish_time')
                    ->update(['finish_time' => $now]);
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
    public function startDandori($jobId, $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $workDate) {
            $workDate = $workDate ?: now()->toDateString();
            $job = JobMaster::findOrFail($jobId);
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
                ->whereNull('finish_time')
                ->first();
            if ($openDowntime) {
                $openDowntime->update([
                    'finish_time' => $now,
                    'duration_seconds' => abs($now->diffInSeconds(Carbon::parse($openDowntime->start_time)))
                ]);
            }
            
            $downtime = Downtime::create([
                'job_master_id' => $jobId,
                'jenis_downtime' => 'dandori',
                'problem' => 'PERSIAPAN (DANDORI)',
                'start_time' => $now,
                'penyebab' => '-',
                'action' => '-',
                'pic' => 'OPERATOR'
            ]);

            Dandori::create([
                'next_job_id' => $jobId,
                'line'        => $job->line,
                'shift'       => $this->getShift(),
                'activity'    => 'DANDORI',
                'start_time'  => $now,
                'work_date'   => $workDate,
                'created_by'  => auth()->id()
            ]);

            $this->signalDashboard($jobId);

            return $downtime;
        });
    }

    /**
     * Finish Dandori process.
     */
    public function finishDandori($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $now = now();

            // 1. Close dandori Downtime if still open
            $downtime = Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', 'dandori')
                ->whereNull('finish_time')
                ->first();

            if ($downtime) {
                $durationSeconds = abs($now->diffInSeconds(Carbon::parse($downtime->start_time)));
                $downtime->update([
                    'finish_time' => $now,
                    'duration_seconds' => $durationSeconds
                ]);
            }

            // 2. Close Dandori record ALWAYS (independent of Downtime)
            $dandori = Dandori::where('next_job_id', $jobId)
                ->whereNull('finish_time')
                ->first();

            if ($dandori) {
                $duration = Carbon::parse($dandori->start_time)->diffInSeconds($now) / 60;
                $dandori->update([
                    'finish_time' => $now,
                    'duration_minutes' => round($duration, 2)
                ]);
            }

            // 3. After dandori, job starts production
            JobMaster::where('id', $jobId)->update(['started_at' => $now, 'status' => 'running']);
            $this->syncPlanStatus($jobId, 'running');

            $session = ProductionSession::firstOrCreate(
                ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
            );
            $session->update(['start_time' => $now, 'status' => 'running']);

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Start 1st Check process for a job (during dandori).
     */
    public function startFirstCheck($jobId, $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $workDate) {
            $workDate = $workDate ?: now()->toDateString();
            $job = JobMaster::findOrFail($jobId);
            $now = now();

            $dandori = Dandori::create([
                'next_job_id'   => $jobId,
                'line'          => $job->line,
                'shift'         => $this->getShift(),
                'activity'      => '1ST CHECK',
                'jenis_dandori' => '1st_check',
                'start_time'    => $now,
                'work_date'     => $workDate,
                'created_by'    => auth()->id()
            ]);

            $this->signalDashboard($jobId);

            return $dandori;
        });
    }

    /**
     * Finish 1st Check process.
     */
    public function finishFirstCheck($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $dandori = Dandori::where('next_job_id', $jobId)
                ->where('jenis_dandori', '1st_check')
                ->whereNull('finish_time')
                ->first();

            if ($dandori) {
                $now = now();
                $duration = Carbon::parse($dandori->start_time)->diffInSeconds($now) / 60;
                $dandori->update([
                    'finish_time'     => $now,
                    'duration_minutes' => round($duration, 2)
                ]);
                return true;
            }
            return false;
        });
    }

    /**
     * Save production log and update daily metrics.
     */
    public function saveProductionLog($jobId, array $data, $workDate = null)
    {
        return DB::transaction(function () use ($jobId, $data, $workDate) {
            $workDate = $workDate ?: now()->toDateString();
            
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

            // Delta-based: tambahkan delta ke nilai yang sudah ada (tidak SUM dari logs)
            // SUM-based salah karena 5-log trimming menghapus history
            $actualQty = ($daily->actual_ok ?? 0) + ($data['ok_qty'] ?? 0);
            $actualRepair = ($daily->actual_repair ?? 0) + ($data['repair_qty'] ?? 0);
            $actualReject = ($daily->actual_reject ?? 0) + ($data['reject_qty'] ?? 0);

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
                    ->whereDate('created_at', now())
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->sum('duration_seconds'),
                'efficiency' => $efficiency
            ]);

            // Sync quantities to PPC Production Plan
            $this->syncPlan($jobId, null, $actualQty, $actualRepair, $actualReject);

            $this->signalDashboard($jobId);

            // Hanya simpan 5 record log terbaru per job, hapus yang lama
            $logIds = ProductionLog::where('job_master_id', $jobId)
                ->whereDate('created_at', now())
                ->orderBy('created_at', 'desc')
                ->pluck('id')
                ->take(5);
            ProductionLog::where('job_master_id', $jobId)
                ->whereDate('created_at', now())
                ->whereNotIn('id', $logIds)
                ->delete();

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
    public function pauseJob($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $runtime = $this->calculateRuntime($jobId);

            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
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
                ['job_master_id' => $jobId, 'work_date' => now()->toDateString()],
                ['runtime_seconds' => $runtime]
            );

            $this->signalDashboard($jobId);

            return $runtime;
        });
    }

    /**
     * Resume a paused job.
     */
    public function resumeJob($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
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
    public function restartJob($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $session = ProductionSession::firstOrCreate(
                ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
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
                ->whereDate('work_date', now()->toDateString())
                ->update(['runtime_seconds' => 0]);

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Finish a job and sync metrics.
     */
    public function finishJob($jobId, $nextJobId = null, $skipIdle = false, $finalOk = null, $finalRepair = null, $finalReject = null)
    {
        return DB::transaction(function () use ($jobId, $nextJobId, $skipIdle, $finalOk, $finalRepair, $finalReject) {
            // Auto-close any active downtimes for this job
            Downtime::where('job_master_id', $jobId)
                ->whereNull('finish_time')
                ->update(['finish_time' => now()]);

            // Auto-close any active dandoris for this job
            Dandori::where('next_job_id', $jobId)
                ->whereNull('finish_time')
                ->update(['finish_time' => now()]);

            $runtime = $this->calculateRuntime($jobId);

            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
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
                // Finalisasi: replace semua log dengan nilai final
                ProductionLog::where('job_master_id', $jobId)
                    ->whereDate('created_at', now())
                    ->delete();
                ProductionLog::create([
                    'job_master_id' => $jobId,
                    'ok_qty'        => $finalOk ?? 0,
                    'repair_qty'    => $finalRepair ?? 0,
                    'reject_qty'    => $finalReject ?? 0,
                ]);
                $totalOk     = $finalOk ?? 0;
                $totalRepair = $finalRepair ?? 0;
                $totalReject = $finalReject ?? 0;
            } else {
                $totalOk     = ProductionLog::where('job_master_id', $jobId)->sum('ok_qty');
                $totalRepair = ProductionLog::where('job_master_id', $jobId)->sum('repair_qty');
                $totalReject = ProductionLog::where('job_master_id', $jobId)->sum('reject_qty');
            }

            DailyProduction::updateOrCreate(
                ['job_master_id' => $jobId, 'work_date' => now()->toDateString()],
                [
                    'runtime_seconds' => $runtime,
                    'actual_ok'       => $totalOk,
                    'actual_qty'      => $totalOk,
                    'actual_repair'   => $totalRepair,
                    'actual_reject'   => $totalReject,
                    'efficiency'      => ($job && $job->capacity > 0) ? ($totalOk / $job->capacity) * 100 : 0
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

            return $runtime;
        });
    }

    // autoStartNextJobAsIdle removed — flow langsung Dandori

    /**
     * Start a downtime event.
     */
    public function startDowntime($jobId, array $data)
    {
        return DB::transaction(function () use ($jobId, $data) {
            $downtime = Downtime::create([
                'job_master_id' => $jobId,
                'jenis_downtime' => $data['jenis_downtime'],
                'problem' => $data['problem'],
                'penyebab' => $data['penyebab'],
                'action' => $data['action'],
                'pic' => $data['pic'],
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
    public function finishDowntime($downtimeId)
    {
        return DB::transaction(function () use ($downtimeId) {
            $downtime = Downtime::find($downtimeId);
            if (!$downtime) return null;

            $finishTime = now();
            $startTime = Carbon::parse($downtime->start_time);
            $durationSeconds = abs($finishTime->diffInSeconds($startTime));

            $downtime->update([
                'finish_time' => $finishTime,
                'duration_seconds' => $durationSeconds
            ]);

            $totalDowntime = Downtime::where('job_master_id', $downtime->job_master_id)
                ->whereDate('created_at', now()->toDateString())
                ->where('jenis_downtime', '!=', 'dandori')
                ->sum('duration_seconds');

            DailyProduction::updateOrCreate(
                ['job_master_id' => $downtime->job_master_id, 'work_date' => now()->toDateString()],
                ['downtime_seconds' => $totalDowntime]
            );

            $this->signalDashboard($downtime->job_master_id);

            return $downtime;
        });
    }

    /**
     * Delete a downtime event and recalculate totals.
     */
    public function deleteDowntime($downtimeId)
    {
        return DB::transaction(function () use ($downtimeId) {
            $downtime = Downtime::find($downtimeId);
            if (!$downtime) return false;

            $jobId = $downtime->job_master_id;
            $downtime->delete();

            $totalDowntime = Downtime::where('job_master_id', $jobId)
                ->whereDate('created_at', now()->toDateString())
                ->where('jenis_downtime', '!=', 'dandori')
                ->sum('duration_seconds');

            DailyProduction::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
                ->update(['downtime_seconds' => $totalDowntime]);

            $this->signalDashboard($jobId);

            return true;
        });
    }

    /**
     * Calculate current runtime in seconds for a job.
     */
    public function calculateRuntime($jobId)
    {
        $session = ProductionSession::where('job_master_id', $jobId)
            ->whereDate('work_date', now()->toDateString())
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
    public function saveDailyProduction($jobId, array $data)
    {
        return DB::transaction(function () use ($jobId, $data) {
            $runtime = $this->calculateRuntime($jobId);
            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
                ->first();

            $downtime = (int) ($session->downtime_seconds ?? 0);
            $job = JobMaster::find($jobId);

            $targetQty = $job?->capacity ?? 0;
            $actualQty = (int) ($data['actual_qty'] ?? 0);

            $efficiency = 0;
            if ($targetQty > 0) {
                $efficiency = round(($actualQty / $targetQty) * 100, 2);
            }

            DailyProduction::updateOrCreate(
                [
                    'job_master_id' => $jobId,
                    'work_date'     => now()->toDateString()
                ],
                [
                    'line'              => $job?->line,
                    'shift'             => $this->getShift(),
                    'target_qty'        => $targetQty,
                    'actual_qty'        => $actualQty,
                    'actual_ok'         => $actualQty,
                    'actual_repair'     => (int) ($data['repair_qty'] ?? 0),
                    'actual_reject'     => (int) ($data['reject_qty'] ?? 0),
                    'reject_qty'        => (int) ($data['reject_qty'] ?? 0),
                    'repair_qty'        => (int) ($data['repair_qty'] ?? 0),
                    'runtime_seconds'   => $runtime,
                    'downtime_seconds'  => $downtime,
                    'efficiency'        => $efficiency,
                    'remarks'           => $data['remarks'] ?? null,
                    'saved_by'          => auth()->id(),
                    'status'            => 'complete',
                ]
            );

            // Sync status and quantities to PPC Production Plan
            $this->syncPlan($jobId, 'complete', $actualQty, (int) ($data['repair_qty'] ?? 0), (int) ($data['reject_qty'] ?? 0));

            $this->signalDashboard($jobId);

            return [
                'runtime' => $runtime,
                'efficiency' => $efficiency
            ];
        });
    }

    /**
     * Determine current work shift based on time.
     */
    private function getShift()
    {
        $hour = (int) now()->format('H');

        if ($hour >= 7 && $hour < 21) {
            return 'Shift Pagi';
        }

        return 'Shift Malam';
    }

    /**
     * Get the next job in sequence or by manual selection.
     */
    public function getNextJob($currentJobId, $selectedNextId = null)
    {
        if ($selectedNextId) {
            return JobMaster::where('id', $selectedNextId)
                ->whereNotIn(DB::raw('LOWER(status)'), ['complete'])
                ->first();
        }

        $current = JobMaster::find($currentJobId);
        if (!$current) return null;

        $parts = explode('-', $current->job_number);
        $planId = end($parts);

        if (is_numeric($planId)) {
            $currentPlan = \App\Models\ProductionPlan::find($planId);
            if ($currentPlan) {
                $planQuery = \App\Models\ProductionPlan::where('plan_date', $currentPlan->plan_date)
                    ->where('shift_name', $currentPlan->shift_name)
                    ->where('press_name', $currentPlan->press_name)
                    ->where('row_type', 'job')
                    ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

                $nextPlan = (clone $planQuery)
                    ->where('row_no', '>', $currentPlan->row_no)
                    ->orderBy('row_no', 'asc')
                    ->first();

                if (!$nextPlan) {
                    $nextPlan = (clone $planQuery)
                        ->orderBy('row_no', 'asc')
                        ->first();
                }

                if ($nextPlan && $nextPlan->id !== $currentPlan->id) {
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

        $allLineJobs = JobMaster::whereNotIn(DB::raw('LOWER(status)'), ['complete'])
            ->where('line', $current->line)
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->get();

        $currentIdx = $allLineJobs->search(fn($j) => $j->id == $currentJobId);
        if ($currentIdx !== false && $allLineJobs->count() > 1) {
            $total = $allLineJobs->count();
            for ($i = 1; $i < $total; $i++) {
                $candidate = $allLineJobs[($currentIdx + $i) % $total];
                if ($candidate->id != $currentJobId) {
                    return $candidate;
                }
            }
        }

        return $allLineJobs->first(fn($j) => $j->id != $currentJobId);
    }

    /**
     * Sync ProductionPlan status and quantities based on JobMaster
     */
    private function syncPlan($jobId, $status = null, $ok = null, $repair = null, $reject = null)
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
                $daily = \App\Models\DailyProduction::where('job_master_id', $jobId)
                    ->whereDate('work_date', now()->toDateString())
                    ->first();
                if ($daily) {
                    $ok = $ok ?? $daily->actual_qty;
                    $repair = $repair ?? ($daily->actual_repair ?: $daily->repair_qty);
                    $reject = $reject ?? ($daily->actual_reject ?: $daily->reject_qty);
                } else {
                    $ok = $ok ?? \App\Models\ProductionLog::where('job_master_id', $jobId)
                        ->whereDate('created_at', now())->sum('ok_qty');
                    $repair = $repair ?? \App\Models\ProductionLog::where('job_master_id', $jobId)
                        ->whereDate('created_at', now())->sum('repair_qty');
                    $reject = $reject ?? \App\Models\ProductionLog::where('job_master_id', $jobId)
                        ->whereDate('created_at', now())->sum('reject_qty');
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
    private function syncPlanStatus($jobId, $status)
    {
        $this->syncPlan($jobId, $status);
    }

    private function signalDashboard($jobId): void
    {
        $job = JobMaster::find($jobId);
        if ($job && $job->line) {
            DashboardRealtimeService::signalUpdate($job->line);
        }
    }
}
