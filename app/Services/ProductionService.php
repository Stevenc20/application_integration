<?php

namespace App\Services;

use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\Dandori;
use App\Models\ProductionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
                $updateData['started_at'] = now();
                $session->start_time = now();
                $session->save();
            }

            JobMaster::where('id', $jobId)->update($updateData);
            $this->syncPlanStatus($jobId, 'running');

            return true;
        });
    }

    /**
     * Start Dandori process for a job.
     */
    public function startDandori($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $job = JobMaster::findOrFail($jobId);
            $job->update(['status' => 'running']);
            $this->syncPlanStatus($jobId, 'running');

            ProductionSession::firstOrCreate(
                [
                    'job_master_id' => $jobId,
                    'work_date' => now()->toDateString()
                ],
                [
                    'status' => 'running',
                    'start_time' => now()
                ]
            );
            
            $now = now();
            
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
                'work_date'   => $now->toDateString(),
                'created_by'  => auth()->id()
            ]);

            return $downtime;
        });
    }

    /**
     * Finish Dandori process.
     */
    public function finishDandori($jobId)
    {
        return DB::transaction(function () use ($jobId) {
            $downtime = Downtime::where('job_master_id', $jobId)
                ->where('jenis_downtime', 'dandori')
                ->whereNull('finish_time')
                ->first();

            if ($downtime) {
                $now = now();
                $downtime->update(['finish_time' => $now]);
                
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

                // After dandori, job starts production
                JobMaster::where('id', $jobId)->update(['started_at' => $now, 'status' => 'running']);
                $this->syncPlanStatus($jobId, 'running');
                
                $session = ProductionSession::firstOrCreate(
                    ['job_master_id' => $jobId, 'work_date' => now()->toDateString()]
                );
                $session->update(['start_time' => $now, 'status' => 'running']);

                return true;
            }
            return false;
        });
    }

    /**
     * Save production log and update daily metrics.
     */
    public function saveProductionLog($jobId, array $data)
    {
        return DB::transaction(function () use ($jobId, $data) {
            $log = ProductionLog::create([
                'job_master_id' => $jobId,
                'ok_qty' => $data['ok_qty'] ?? 0,
                'repair_qty' => $data['repair_qty'] ?? 0,
                'reject_qty' => $data['reject_qty'] ?? 0,
            ]);

            $daily = DailyProduction::firstOrCreate([
                'job_master_id' => $jobId,
                'work_date' => now()->toDateString()
            ]);
            
            $daily->increment('actual_ok', $data['ok_qty'] ?? 0);
            $daily->increment('actual_qty', $data['ok_qty'] ?? 0);
            $daily->increment('actual_repair', $data['repair_qty'] ?? 0);
            $daily->increment('actual_reject', $data['reject_qty'] ?? 0);
            
            $actualQty = ProductionLog::where('job_master_id', $jobId)->sum('ok_qty');
            $job = JobMaster::find($jobId);
            $targetQty = $job?->capacity ?? 0;
            
            $efficiency = 0;
            if ($targetQty > 0) {
                $efficiency = round(($actualQty / $targetQty) * 100, 2);
            }
            
            $daily->update([
                'actual_qty' => $actualQty,
                'efficiency' => $efficiency
            ]);

            return [
                'log' => $log,
                'actualQty' => $actualQty,
                'efficiency' => $efficiency
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

            return true;
        });
    }

    /**
     * Finish a job and sync metrics.
     */
    public function finishJob($jobId, $nextJobId = null)
    {
        return DB::transaction(function () use ($jobId, $nextJobId) {
            $runtime = $this->calculateRuntime($jobId);

            $session = ProductionSession::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
                ->first();

            if ($session) {
                $session->total_seconds = (int) $runtime;
                $session->status = 'complete';
                $session->finish_time = now();
                $session->save();
            }

            $job = JobMaster::find($jobId);
            JobMaster::where('id', $jobId)->update([
                'status' => 'complete',
                'finished_at' => now()
            ]);
            $this->syncPlanStatus($jobId, 'complete');

            $totalOk = ProductionLog::where('job_master_id', $jobId)->sum('ok_qty');
            $totalRepair = ProductionLog::where('job_master_id', $jobId)->sum('repair_qty');
            $totalReject = ProductionLog::where('job_master_id', $jobId)->sum('reject_qty');

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

            // AUTO-START NEXT JOB IF SPECIFIED
            if ($nextJobId) {
                $this->startDandori($nextJobId);
            }

            return $runtime;
        });
    }

    /**
     * Start a downtime event.
     */
    public function startDowntime($jobId, array $data)
    {
        return Downtime::create([
            'job_master_id' => $jobId,
            'jenis_downtime' => $data['jenis_downtime'],
            'problem' => $data['problem'],
            'penyebab' => $data['penyebab'],
            'action' => $data['action'],
            'pic' => $data['pic'],
            'start_time' => now()
        ]);
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
                ->sum('duration_seconds');

            DailyProduction::updateOrCreate(
                ['job_master_id' => $downtime->job_master_id, 'work_date' => now()->toDateString()],
                ['downtime_seconds' => $totalDowntime]
            );

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
                ->sum('duration_seconds');

            DailyProduction::where('job_master_id', $jobId)
                ->whereDate('work_date', now()->toDateString())
                ->update(['downtime_seconds' => $totalDowntime]);

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
            $total += abs(now()->diffInSeconds($startTime));
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

        if ($hour >= 7 && $hour < 19) {
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

        return JobMaster::whereNotIn(DB::raw('LOWER(status)'), ['complete'])
            ->where('line', $current->line)
            ->where('id', '!=', $currentJobId)
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->first();
    }

    /**
     * Sync ProductionPlan status based on JobMaster
     */
    private function syncPlanStatus($jobId, $status)
    {
        $jobMaster = \App\Models\JobMaster::find($jobId);
        if (!$jobMaster) return;

        // Ekstrak Plan ID dari job_number (Format: JOB_NO-PLAN_ID atau AUTO-SLUG-PLAN_ID)
        $parts = explode('-', $jobMaster->job_number);
        $planId = end($parts);

        if (is_numeric($planId)) {
            $mappedStatus = strtolower($status);
            if ($mappedStatus === 'running' || $mappedStatus === 'paused') {
                $mappedStatus = 'approved';
            } elseif ($mappedStatus === 'complete') {
                $mappedStatus = 'completed';
            }

            if (in_array($mappedStatus, ['pending', 'approved', 'completed'])) {
                \App\Models\ProductionPlan::where('id', $planId)->update(['status' => $mappedStatus]);
            }
        }
    }
}
