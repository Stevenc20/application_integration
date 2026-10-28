<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardRealtimeService
{
    const SHIFT_MAP = [
        1 => 'Shift Pagi',
        2 => 'Shift Malam',
    ];

    const SHIFT_PLAN_MAP = [
        1 => 'Shift Pagi',
        2 => 'Shift Malam',
    ];

    public function getLineMetrics(string $lineName, string $date, int $shift): array
    {
        $shortJob = fn ($name) => strtoupper(trim(explode(' ', $name ?? '-')[0])) ?: '-';
        $planShiftText = self::SHIFT_PLAN_MAP[$shift] ?? 'Shift Pagi';

        $shiftStartDt = $shift === 1
            ? Carbon::parse($date)->setTime(7, 30)
            : Carbon::parse($date)->setTime(21, 0);
        $shiftEndDt = $shift === 1
            ? Carbon::parse($date)->setTime(21, 0)
            : Carbon::parse($date)->addDay()->setTime(7, 30);

        $workDate = $date;

        $isMorning = ($shift === 1);
        $plans = $this->getPlansForDateAndShift($date, $isMorning)
            ->filter(fn ($p) => $this->pressMatches($p, $lineName))
            ->values();

        if ($plans->isEmpty()) {
            return $this->emptyMetrics($lineName);
        }

        // Scope to the latest shift revision for this press if multiple revisions exist
        $shiftNames = $plans->pluck('shift_name')->filter()->unique();
        if ($shiftNames->count() > 1) {
            $latestShiftName = $plans->sortByDesc(fn ($p) => [
                $p->updated_at ? $p->updated_at->timestamp : 0,
                (int) $p->id,
            ])->first()?->shift_name;
            if ($latestShiftName) {
                $plans = $plans->filter(fn ($p) => $p->shift_name === $latestShiftName)->values();
            }
        }

        $jobNumbers = $plans->map(function ($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');

            return $jn ? ($jn.'-'.$p->id) : ('AUTO-'.\Illuminate\Support\Str::slug($jm).'-'.$p->id);
        })->toArray();

        $plansByIdentifier = [];
        foreach ($plans as $p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            $identifier = $jn ? ($jn.'-'.$p->id) : ('AUTO-'.\Illuminate\Support\Str::slug($jm).'-'.$p->id);
            $plansByIdentifier[$identifier] = $p;
        }

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->get()
            ->keyBy('job_number');

        $insertJobs = [];
        foreach ($plans as $p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            $identifier = $jn ? ($jn.'-'.$p->id) : ('AUTO-'.\Illuminate\Support\Str::slug($jm).'-'.$p->id);

            if (! $jobMasters->has($identifier)) {
                $jobName = $p->job_master ?: ($p->job_no ?: 'UNKNOWN JOB');
                $insertJobs[] = [
                    'job_number' => $identifier,
                    'job_name' => $jobName,
                    'line' => $p->press_name ?? $lineName,
                    'target_qty' => (int) ($p->plan ?? 0),
                    'sequence_no' => $p->row_no ?? 1,
                    'status' => 'pending',
                    'plan_start' => $p->start_time ? Carbon::parse($date.' '.$p->start_time)->startOfMinute() : null,
                    'plan_end' => $p->finish_time ? Carbon::parse($date.' '.$p->finish_time)->startOfMinute() : null,
                    'capacity' => (int) ($p->qty_plt ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($insertJobs)) {
            JobMaster::insertOrIgnore($insertJobs);
            $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
                ->get()
                ->keyBy('job_number');
        }

        $jobIds = $jobMasters->pluck('id');

        $dailyRecords = DailyProduction::with('jobMaster')
            ->where('work_date', $workDate)
            ->whereIn('job_master_id', $jobIds)
            ->get();

        $activeJobIds = $dailyRecords->pluck('job_master_id')->toArray();
        $missingJobs = $jobMasters->whereNotIn('id', $activeJobIds);

        if ($missingJobs->isNotEmpty()) {
            $missingJobIds = $missingJobs->pluck('id')->toArray();

            // 1 query to get existing dailies strictly for this work_date
            $existingDailiesMap = DailyProduction::where('work_date', $workDate)
                ->whereIn('job_master_id', $missingJobIds)
                ->get()
                ->keyBy('job_master_id');

            // Find which jobs actually have no daily for this work_date
            $stillMissingIds = array_diff($missingJobIds, $existingDailiesMap->keys()->toArray());

            $logAggMap = collect();
            $downtimeSecsMap = collect();

            if (! empty($stillMissingIds)) {
                // 1 query to aggregate production logs for all missing jobs on this work_date
                $logAggMap = \App\Models\ProductionLog::whereIn('job_master_id', $stillMissingIds)
                    ->whereDate('created_at', $workDate)
                    ->groupBy('job_master_id')
                    ->selectRaw('job_master_id, COALESCE(SUM(ok_qty),0) as total_ok, COALESCE(SUM(repair_qty),0) as total_repair, COALESCE(SUM(reject_qty),0) as total_reject')
                    ->get()
                    ->keyBy('job_master_id');

                // 1 query to aggregate downtimes for all missing jobs
                $downtimeSecsMap = Downtime::whereIn('job_master_id', $stillMissingIds)
                    ->where('start_time', '>=', $shiftStartDt)
                    ->where('start_time', '<', $shiftEndDt)
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->groupBy('job_master_id')
                    ->selectRaw('job_master_id, COALESCE(SUM(duration_seconds),0) as total_dt')
                    ->pluck('total_dt', 'job_master_id');
            }

            foreach ($missingJobs as $mj) {
                $existingDaily = $existingDailiesMap->get($mj->id);

                if ($existingDaily) {
                    $existingDaily->work_date = $workDate;
                    $existingDaily->setRelation('jobMaster', $mj);
                    $dailyRecords->push($existingDaily);
                } else {
                    $logAgg = $logAggMap->get($mj->id);
                    $dtSecs = (int) ($downtimeSecsMap->get($mj->id) ?? 0);
                    $matchedPlan = $plansByIdentifier[$mj->job_number ?? ''] ?? null;

                    $virtualOk = (int) ($logAgg?->total_ok ?? ($matchedPlan?->ok ?? 0));
                    $virtualRepair = (int) ($logAgg?->total_repair ?? ($matchedPlan?->repair ?? 0));
                    $virtualReject = (int) ($logAgg?->total_reject ?? ($matchedPlan?->reject ?? 0));

                    $virtual = new DailyProduction([
                        'job_master_id' => $mj->id,
                        'work_date' => $workDate,
                        'actual_ok' => $virtualOk,
                        'actual_qty' => $virtualOk,
                        'actual_repair' => $virtualRepair,
                        'actual_reject' => $virtualReject,
                        'runtime_seconds' => 0,
                        'downtime_seconds' => $dtSecs,
                        'line' => $lineName,
                        'shift' => self::SHIFT_MAP[$shift] ?? 'Shift Pagi',
                    ]);
                    $virtual->setRelation('jobMaster', $mj);
                    $dailyRecords->push($virtual);
                }
            }
        }

        if ($dailyRecords->isEmpty()) {
            return $this->emptyMetrics($lineName);
        }

        $ok = (int) $dailyRecords->sum('actual_ok');
        $repair = (int) $dailyRecords->sum('actual_repair');
        $reject = (int) $dailyRecords->sum('actual_reject');

        $runningRecord = $dailyRecords->first(function ($dp) {
            return $dp->jobMaster && in_array(strtolower((string) $dp->jobMaster->status), ['running', 'paused'], true);
        });

        if (! $runningRecord) {
            $runningRecord = $dailyRecords->filter(function ($dp) {
                return $dp->jobMaster && $dp->jobMaster->started_at !== null;
            })->sortByDesc(function ($dp) {
                return $dp->jobMaster->started_at;
            })->first();
        }

        if (! $runningRecord && $dailyRecords->isNotEmpty()) {
            $runningRecord = $dailyRecords->sortBy(function ($dp) {
                return $dp->jobMaster->sequence_no ?? 9999;
            })->first();
        }

        $hasRunning = $runningRecord !== null
            && $runningRecord->jobMaster
            && in_array(strtolower((string) $runningRecord->jobMaster->status), ['running', 'paused'], true);
        $currOk = $hasRunning ? (int) $runningRecord->actual_ok : 0;
        $currRepair = $hasRunning ? (int) $runningRecord->actual_repair : 0;
        $currReject = $hasRunning ? (int) $runningRecord->actual_reject : 0;

        $sessionsMap = \App\Models\ProductionSession::whereIn('job_master_id', $jobIds)
            ->where('work_date', $workDate)
            ->get()
            ->keyBy('job_master_id');

        $runtime = 0;
        $currRuntime = 0;
        foreach ($dailyRecords as $dp) {
            $dpRuntime = (float) $dp->runtime_seconds;
            if ($dpRuntime <= 0 && $dp->jobMaster) {
                $jmStatus = strtolower((string) $dp->jobMaster->status);
                if ($jmStatus === 'running') {
                    $session = $sessionsMap->get($dp->job_master_id);
                    if ($session && $session->start_time) {
                        $dpRuntime = (float) $session->total_seconds;
                        if ($session->status === 'running') {
                            $dpRuntime += abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($session->start_time)));
                        }
                    } elseif ($dp->jobMaster->started_at) {
                        $dpRuntime = abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($dp->jobMaster->started_at)));
                    }
                } elseif ($jmStatus === 'paused') {
                    $session = $sessionsMap->get($dp->job_master_id);
                    $dpRuntime = (float) ($session->total_seconds ?? 0);
                } elseif ($dp->jobMaster->started_at) {
                    $endAt = $dp->jobMaster->finished_at ?: \Carbon\Carbon::now();
                    $dpRuntime = abs(\Carbon\Carbon::parse($endAt)->diffInSeconds(\Carbon\Carbon::parse($dp->jobMaster->started_at)));
                }
            }
            $runtime += $dpRuntime;
            if ($runningRecord && $dp->job_master_id === $runningRecord->job_master_id) {
                $currRuntime = $dpRuntime;
            }
        }
        $dtSecs = (int) $dailyRecords->sum('downtime_seconds');

        $planQty = (int) $plans->sum('plan');

        if ($planQty <= 0) {
            $planQty = (int) JobMaster::whereIn('id', $jobIds)
                ->whereHas('dailyProduction', function ($q) use ($workDate) {
                    $q->where('work_date', $workDate);
                })
                ->sum('target_qty');
        }

        $allDowntimes = Downtime::with(['jobMaster', 'hambatanJalur'])->whereIn('job_master_id', $jobIds)
            ->where('start_time', '>=', $shiftStartDt)
            ->where('start_time', '<', $shiftEndDt)
            ->get();

        $runtimeMinutes = round($runtime / 60, 1);

        // Fallback: estimate elapsed shift minutes if runtime not yet tracked
        if ($runtimeMinutes <= 0) {
            $now = Carbon::now();
            $shiftHour = $shift === 1 ? 7 : 21;
            $shiftStart = $now->copy()->setTime($shiftHour, 30);
            $elapsed = max(1, $shiftStart->diffInMinutes($now));
            $runtimeMinutes = round($elapsed);
        }

        $dtTotalMinutes = 0;
        foreach ($allDowntimes as $dt) {
            $type = strtoupper(trim($dt->jenis_downtime ?? ''));
            if (ProductionMetricsService::isExcludedDowntimeType($type)) {
                continue;
            }
            $dtTotalMinutes += $this->downtimeDurationSeconds($dt) / 60;
        }
        $dtTotalMinutes = round($dtTotalMinutes, 1);

        $breakdown = ProductionMetricsService::downtimeBreakdown($allDowntimes);

        $dtMachMinutes = $breakdown['machine'];
        $dtMatMinutes = $breakdown['material'];
        $dtLogMinutes = $breakdown['logistic'];
        $dtProdMinutes = $breakdown['production'];
        $dtDiesMinutes = $breakdown['dies'];
        $dtTotalCategorized = $breakdown['total'];

        $currDtTotal = 0;
        $currDtMach = 0;
        $currDtDies = 0;
        $currDtMat = 0;
        $currDtLog = 0;
        $currDtProd = 0;
        if ($runningRecord) {
            $runningJobId = $runningRecord->job_master_id;
            $runningDt = $allDowntimes->where('job_master_id', $runningJobId);
            foreach ($runningDt as $dt) {
                $type = strtoupper(trim($dt->jenis_downtime ?? ''));
                if (ProductionMetricsService::isExcludedDowntimeType($type)) {
                    continue;
                }
                $dur = $this->downtimeDurationSeconds($dt) / 60;
                $currDtTotal += $dur;
                if (str_contains($type, 'MACHINE') || str_contains($type, 'MACH') || str_contains($type, 'MESIN')) {
                    $currDtMach += $dur;
                } elseif (str_contains($type, 'DIES')) {
                    $currDtDies += $dur;
                } elseif (str_contains($type, 'MATERIAL') || str_contains($type, 'MAT')) {
                    $currDtMat += $dur;
                } elseif (str_contains($type, 'LOGISTIC') || str_contains($type, 'LOG')) {
                    $currDtLog += $dur;
                } elseif (str_contains($type, 'PRODUCTION') || str_contains($type, 'PROD')) {
                    $currDtProd += $dur;
                }
            }
            $currDtTotal = round($currDtTotal, 1);
            $currDtMach = round($currDtMach, 1);
            $currDtDies = round($currDtDies, 1);
            $currDtMat = round($currDtMat, 1);
            $currDtLog = round($currDtLog, 1);
            $currDtProd = round($currDtProd, 1);
        }

        $dtRows = [];
        $machRows = [];
        $diesRows = [];
        $matRows = [];
        $logRows = [];
        $prodRows = [];
        $repairRows = [];
        $rejectRows = [];

        foreach ($allDowntimes as $dt) {
            $type = strtoupper(trim($dt->jenis_downtime ?? ''));
            if (ProductionMetricsService::isExcludedDowntimeType($type)) {
                continue;
            }
            $dur = round($this->downtimeDurationSeconds($dt) / 60, 1);
            $rawJob = $dt->jobMaster?->job_name ?? '-';
            $jobName = $shortJob($rawJob);
            $imageUrl = $dt->image_path ? asset('uploads/'.$dt->image_path) : null;
            $row = [
                'id' => $dt->id,
                'no' => count($dtRows) + 1,
                'jenis' => $dt->jenis_downtime,
                'job' => $jobName,
                'item' => $dt->problem ?? '-',
                'problem' => $dt->problem ?? '-',
                'penyebab' => $dt->penyebab ?? '-',
                'action' => $dt->action ?? ($dt->tindakan ?? '-'),
                'tindakan' => $dt->tindakan ?? ($dt->action ?? '-'),
                'pic' => $dt->pic ?? '-',
                'image_url' => $imageUrl,
                'durasi' => $dur,
                'start_time' => $dt->start_time ? \Carbon\Carbon::parse($dt->start_time)->format('H:i') : '-',
                'finish_time' => $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->format('H:i') : 'Sekarang',
                'lhj_id' => $dt->hambatanJalur?->id,
                'lhj_status' => $dt->hambatanJalur?->status,
            ];
            $dtRows[] = $row;

            if (str_contains($type, 'MACHINE') || str_contains($type, 'MACH') || str_contains($type, 'MESIN')) {
                $machRows[] = $row;
            } elseif (str_contains($type, 'DIES')) {
                $diesRows[] = $row;
            } elseif (str_contains($type, 'MATERIAL') || str_contains($type, 'MAT')) {
                $matRows[] = $row;
            } elseif (str_contains($type, 'LOGISTIC') || str_contains($type, 'LOG')) {
                $logRows[] = $row;
            } elseif (str_contains($type, 'PRODUCTION') || str_contains($type, 'PROD')) {
                $prodRows[] = $row;
            }
        }

        $repairRejectLogs = \App\Models\RepairRejectLog::with(['creator', 'jobMaster', 'images'])
            ->whereIn('job_master_id', $jobIds)
            ->where('created_at', '>=', $shiftStartDt)
            ->where('created_at', '<', $shiftEndDt)
            ->get();

        foreach ($repairRejectLogs as $rr) {
            $logType = strtolower(trim($rr->type ?? ''));
            $picName = $rr->creator?->name;
            if (! $picName && ! empty($rr->created_by)) {
                $userRec = \App\Models\User::find($rr->created_by);
                $picName = $userRec?->name;
            }
            if (! $picName) {
                $picName = '-';
            }
            $timeFormatted = $rr->created_at ? \Carbon\Carbon::parse($rr->created_at)->format('H:i') : '-';
            $job = $rr->jobMaster;
            $itemData = [
                'no' => 0,
                'type' => strtoupper($logType),
                'item' => $shortJob($job?->job_name),
                'job' => $job?->job_number ?? '-',
                'defect_name' => $rr->defect_name ?: '-',
                'problem' => $rr->defect_name ?: '-',
                'qty' => (int) ($rr->qty_a ?: 1),
                'repair_category' => $rr->repair_category ?: '-',
                'area_problem' => $rr->area_problem ?: '-',
                'root_cause' => $rr->root_cause ?: '-',
                'countermeasure' => $rr->countermeasure ?: '-',
                'pic' => $picName,
                'time' => $timeFormatted,
                'images' => $rr->images->map(fn ($i) => $i->url ?: asset('uploads/'.$i->image_path))->values()->all(),
            ];

            if ($logType === 'repair') {
                $itemData['no'] = count($repairRows) + 1;
                $repairRows[] = $itemData;
            } elseif ($logType === 'reject') {
                $itemData['no'] = count($rejectRows) + 1;
                $rejectRows[] = $itemData;
            }
        }

        // Fallback jika belum ada RepairRejectLog detail tapi ada dailyRecords repair/reject
        if (empty($repairRows)) {
            foreach ($dailyRecords as $dp) {
                if ($dp->actual_repair > 0) {
                    $job = $dp->jobMaster;
                    $fallbackPic = '-';
                    if (! empty($dp->saved_by)) {
                        $fallbackPic = \App\Models\User::find($dp->saved_by)?->name ?? '-';
                    }
                    $repairRows[] = [
                        'no' => count($repairRows) + 1,
                        'type' => 'REPAIR',
                        'item' => $shortJob($job?->job_name),
                        'job' => $job?->job_number ?? '-',
                        'defect_name' => 'Defect',
                        'problem' => 'Defect',
                        'qty' => (int) $dp->actual_repair,
                        'repair_category' => '-',
                        'area_problem' => '-',
                        'root_cause' => '-',
                        'countermeasure' => '-',
                        'pic' => $fallbackPic,
                        'time' => '-',
                        'images' => [],
                    ];
                }
            }
        }

        if (empty($rejectRows)) {
            foreach ($dailyRecords as $dp) {
                if ($dp->actual_reject > 0) {
                    $job = $dp->jobMaster;
                    $fallbackPic = '-';
                    if (! empty($dp->saved_by)) {
                        $fallbackPic = \App\Models\User::find($dp->saved_by)?->name ?? '-';
                    }
                    $rejectRows[] = [
                        'no' => count($rejectRows) + 1,
                        'type' => 'REJECT',
                        'item' => $shortJob($job?->job_name),
                        'job' => $job?->job_number ?? '-',
                        'defect_name' => 'Reject',
                        'problem' => 'Reject',
                        'qty' => (int) $dp->actual_reject,
                        'repair_category' => '-',
                        'area_problem' => '-',
                        'root_cause' => '-',
                        'countermeasure' => '-',
                        'pic' => $fallbackPic,
                        'time' => '-',
                        'images' => [],
                    ];
                }
            }
        }

        $qtyRows = [];
        $runtimeRows = [];
        foreach ($dailyRecords as $dp) {
            $job = $dp->jobMaster;
            $qtyRows[] = [
                'no' => count($qtyRows) + 1,
                'item' => $shortJob($job?->job_name),
                'ok' => (int) $dp->actual_ok,
                'repair' => (int) $dp->actual_repair,
                'reject' => (int) $dp->actual_reject,
            ];
            $jobRuntimeSeconds = (float) $dp->runtime_seconds;
            if ($jobRuntimeSeconds <= 0 && $job) {
                if ($job->status === 'running') {
                    $session = \App\Models\ProductionSession::where('job_master_id', $job->id)
                        ->where('work_date', $workDate)
                        ->first();
                    if ($session && $session->start_time) {
                        $jobRuntimeSeconds = (float) $session->total_seconds;
                        if ($session->status === 'running') {
                            $jobRuntimeSeconds += abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($session->start_time)));
                        }
                    } elseif ($job->started_at) {
                        $jobRuntimeSeconds = abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($job->started_at)));
                    }
                } elseif ($job->status === 'paused') {
                    $pSession = \App\Models\ProductionSession::where('job_master_id', $job->id)
                        ->where('work_date', $workDate)
                        ->first();
                    $jobRuntimeSeconds = (float) ($pSession->total_seconds ?? 0);
                } elseif ($job->started_at) {
                    $endAt = $job->finished_at ?: \Carbon\Carbon::now();
                    $jobRuntimeSeconds = abs(\Carbon\Carbon::parse($endAt)->diffInSeconds(\Carbon\Carbon::parse($job->started_at)));
                }
            }
            $jobRuntime = round($jobRuntimeSeconds / 60, 1);
            $runtimeRows[] = [
                'no' => count($runtimeRows) + 1,
                'item' => $shortJob($job?->job_name),
                'durasi' => $jobRuntime.' m',
            ];
        }

        $runtimeMinutes = round(array_sum(array_map(fn ($r) => (float) str_replace(' m', '', $r['durasi']), $runtimeRows)) ?: $runtime / 60, 1);

        // Ambil data Dandori (Dies/Variant changeover & 1st-Q check)
        $dandoriModels = Dandori::whereIn('next_job_id', $jobIds)
            ->where(function ($q) use ($shiftStartDt, $shiftEndDt, $workDate) {
                $q->whereBetween('start_time', [$shiftStartDt, $shiftEndDt])
                    ->orWhere('work_date', $workDate);
            })
            ->get();

        $qcheckByJob = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('q_checks')) {
            $qcheckModels = \App\Models\QCheck::whereIn('job_master_id', $jobIds)
                ->whereNotNull('start_time')
                ->whereNotNull('finish_time')
                ->get();
            foreach ($qcheckModels as $qc) {
                $qcheckByJob[$qc->job_master_id] = ($qcheckByJob[$qc->job_master_id] ?? 0.0) + (float) ($qc->duration ?? 0);
            }
        }

        $dandoriTotalMinutes = 0.0;
        $currDandoriMinutes = 0.0;
        foreach ($dandoriModels as $d) {
            $dur = (float) ($d->duration_minutes ?? 0);
            if ($dur <= 0 && $d->start_time) {
                $end = $d->finish_time ? Carbon::parse($d->finish_time) : now();
                $dur = round(abs($end->diffInSeconds(Carbon::parse($d->start_time))) / 60, 2);
            }
            $dandoriTotalMinutes += $dur;
            if ($runningRecord && $d->next_job_id === $runningRecord->job_master_id) {
                $currDandoriMinutes += $dur;
            }
        }
        foreach ($qcheckByJob as $qcJobId => $qcDur) {
            $dandoriTotalMinutes += $qcDur;
            if ($runningRecord && (int) $qcJobId === (int) $runningRecord->job_master_id) {
                $currDandoriMinutes += $qcDur;
            }
        }
        $dandoriTotalMinutes = round($dandoriTotalMinutes, 1);
        $currDandoriMinutes = round($currDandoriMinutes, 1);

        // Determine cumulative set up to and including running item (item belum mulai dikecualikan)
        $includedJobIds = [];
        $runningJobId = null;
        if ($runningRecord && $runningRecord->jobMaster) {
            $runningJobId = $runningRecord->job_master_id;
        }

        // Build ordered list by sequence
        $ordered = $dailyRecords->filter(function ($dp) {
            return $dp->jobMaster !== null;
        })->sortBy(function ($dp) {
            $seq = $dp->jobMaster->sequence_no ?? 9999;

            return $seq;
        })->values();

        foreach ($ordered as $dp) {
            $jm = $dp->jobMaster;
            $isRunningJob = $runningJobId && $dp->job_master_id == $runningJobId;
            $hasStarted = ! empty($jm->started_at) || in_array(strtolower((string) $jm->status), ['running', 'paused', 'completed', 'complete', 'finished', 'done', 'closed'], true);
            $hasProgress = ((int) $dp->actual_ok + (int) $dp->actual_repair + (int) $dp->actual_reject) > 0;
            // Include if processed up to running: include all before running that are processed or started; include running if it exists
            if ($isRunningJob) {
                // include running even if just started with 0 qty? but qty item berjalan is used for current; for actual cumulative include if processed up to running
                if ($hasStarted || $hasProgress || true) { // include running
                    $includedJobIds[] = $dp->job_master_id;
                }
                break; // up to and including running
            }
            if ($hasStarted || $hasProgress) {
                $includedJobIds[] = $dp->job_master_id;
            }
            // if not started and no progress, exclude
        }

        // If no running found or cutoff logic didn't include running properly, fallback: include all except those with no start and no progress
        if (empty($includedJobIds)) {
            foreach ($ordered as $dp) {
                $jm = $dp->jobMaster;
                $hasStarted = ! empty($jm->started_at) || in_array(strtolower((string) $jm->status), ['running', 'paused', 'completed', 'complete', 'finished', 'done', 'closed'], true);
                $hasProgress = ((int) $dp->actual_ok + (int) $dp->actual_repair + (int) $dp->actual_reject) > 0;
                if ($hasStarted || $hasProgress) {
                    $includedJobIds[] = $dp->job_master_id;
                }
            }
            if (empty($includedJobIds) && $runningJobId) {
                $includedJobIds[] = $runningJobId;
            }
        }

        // Sum qty for included jobs (use actual_ok as output qty)
        $okCum = 0;
        foreach ($dailyRecords as $dp) {
            if (in_array($dp->job_master_id, $includedJobIds)) {
                $okCum += (int) $dp->actual_ok;
            }
        }

        // Compute durations for included jobs only
        $runtimeCumSeconds = 0;
        foreach ($dailyRecords as $dp) {
            if (! in_array($dp->job_master_id, $includedJobIds)) {
                continue;
            }
            $dpRuntime = (float) $dp->runtime_seconds;
            if ($dpRuntime <= 0 && $dp->jobMaster) {
                $jmStatus = strtolower((string) $dp->jobMaster->status);
                if ($jmStatus === 'running') {
                    $session = $sessionsMap->get($dp->job_master_id);
                    if ($session && $session->start_time) {
                        $dpRuntime = (float) $session->total_seconds;
                        if ($session->status === 'running') {
                            $dpRuntime += abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($session->start_time)));
                        }
                    } elseif ($dp->jobMaster->started_at) {
                        $dpRuntime = abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($dp->jobMaster->started_at)));
                    }
                } elseif ($jmStatus === 'paused') {
                    $session = $sessionsMap->get($dp->job_master_id);
                    $dpRuntime = (float) ($session->total_seconds ?? 0);
                } elseif ($dp->jobMaster->started_at) {
                    $endAt = $dp->jobMaster->finished_at ?: \Carbon\Carbon::now();
                    $dpRuntime = abs(\Carbon\Carbon::parse($endAt)->diffInSeconds(\Carbon\Carbon::parse($dp->jobMaster->started_at)));
                }
            }
            if ($dpRuntime < 0) {
                $dpRuntime = 0;
            }
            $runtimeCumSeconds += $dpRuntime;
        }
        $runtimeCumMinutes = round($runtimeCumSeconds / 60, 1);
        if ($runtimeCumMinutes < 0) {
            $runtimeCumMinutes = 0;
        }

        // Dandori + 1st check for included jobs
        $dandoriCumMinutes = 0.0;
        foreach ($dandoriModels as $d) {
            if ($d->next_job_id && in_array($d->next_job_id, $includedJobIds)) {
                $dur = (float) ($d->duration_minutes ?? 0);
                if ($dur <= 0 && $d->start_time) {
                    $end = $d->finish_time ? Carbon::parse($d->finish_time) : now();
                    $dur = round(abs($end->diffInSeconds(Carbon::parse($d->start_time))) / 60, 2);
                }
                if ($dur > 0) {
                    $dandoriCumMinutes += $dur;
                }
            }
        }
        foreach ($qcheckByJob as $qcJobId => $qcDur) {
            if (in_array($qcJobId, $includedJobIds) && $qcDur > 0) {
                $dandoriCumMinutes += $qcDur;
            }
        }
        $dandoriCumMinutes = round($dandoriCumMinutes, 1);

        // Downtime for included jobs
        $dtCumMinutes = 0.0;
        foreach ($allDowntimes as $dt) {
            if ($dt->job_master_id && in_array($dt->job_master_id, $includedJobIds)) {
                $type = strtoupper(trim($dt->jenis_downtime ?? ''));
                if (ProductionMetricsService::isExcludedDowntimeType($type)) {
                    continue;
                }
                $dur = $this->downtimeDurationSeconds($dt) / 60;
                if ($dur > 0) {
                    $dtCumMinutes += $dur;
                }
            }
        }
        $dtCumMinutes = round($dtCumMinutes, 1);

        // TPT Aktual = Press Time + Dandori + 1st Check + Downtime (akumulatif sampai item berjalan)
        $actualTptMinutes = round($runtimeCumMinutes + $dandoriCumMinutes + $dtCumMinutes, 1);
        if ($actualTptMinutes < 0) {
            $actualTptMinutes = 0;
        }

        // GSPH ACTUAL = (Total Qty seluruh item yang sudah diproses sampai item berjalan / Total TPT) × 60
        $gsph = ($okCum > 0 && $actualTptMinutes > 0 && is_finite($actualTptMinutes))
            ? (int) round(($okCum / $actualTptMinutes) * 60)
            : 0;

        $currRuntimeMinutes = round($currRuntime / 60, 1);
        // GSPH CURRENT = (Qty item berjalan / (Press Time item berjalan + Dandori item berjalan + 1st Check item berjalan + Downtime item berjalan)) × 60
        $currGsph = 0;
        if ($hasRunning && $runningRecord && $runningRecord->jobMaster) {
            $currQtyRunning = (int) $currOk;
            if ($currQtyRunning <= 0) {
                // if no qty yet, still follow formula; if denominator zero -> 0
                $currQtyRunning = 0;
            }
            $currPressTime = (float) $currRuntimeMinutes;
            $currDandori = (float) $currDandoriMinutes; // includes dandori + 1st check
            $currDowntime = (float) $currDtTotal;
            $currDenom = $currPressTime + $currDandori + $currDowntime;
            if ($currQtyRunning > 0 && $currDenom > 0 && is_finite($currDenom)) {
                $currGsph = (int) round(($currQtyRunning / $currDenom) * 60);
            } else {
                $currGsph = 0;
            }
        }

        $planGsphItem = (int) round($plans->max('gsph_item') ?: 0);
        $gsphPlan = $planGsphItem > 0 ? $planGsphItem : ProductionMetricsService::gsph($planQty, max($runtimeMinutes, 30));

        $dtProdLabel = round($dtProdMinutes, 1).' m';
        $dtTotalLabel = round($dtTotalMinutes, 1).' m';
        $dtMachLabel = round($dtMachMinutes, 1).' m';
        $dtDiesLabel = round($dtDiesMinutes, 1).' m';
        $dtMatLabel = round($dtMatMinutes, 1).' m';
        $dtLogLabel = round($dtLogMinutes, 1).' m';

        $kpi = [
            ['desc' => 'QTY',      'plan' => (string) $planQty,           'actual' => (string) $ok,         'actualLink' => true, 'current' => $hasRunning ? (string) $currOk : '-'],
            ['desc' => 'GSPH',     'plan' => (string) $gsphPlan,          'actual' => (string) $gsph,       'current' => $hasRunning ? (string) $currGsph : '-'],
            ['desc' => 'PROD_T',   'plan' => '0 m',                      'actual' => $dtProdLabel,        'current' => $hasRunning ? $currDtProd.' m' : '-', 'popup' => true],
            ['desc' => 'TOTAL_DT', 'plan' => '0 m',                      'actual' => $dtTotalLabel,       'current' => $hasRunning ? $currDtTotal.' m' : '-', 'popup' => true, 'danger' => $dtTotalMinutes > 0],
            ['desc' => 'MACH_T',   'plan' => '0 m',                      'actual' => $dtMachLabel,        'current' => $hasRunning ? $currDtMach.' m' : '-', 'popup' => true],
            ['desc' => 'DIES_T',   'plan' => '0 m',                      'actual' => $dtDiesLabel,        'current' => $hasRunning ? $currDtDies.' m' : '-', 'popup' => true],
            ['desc' => 'MAT_T',    'plan' => '0 m',                      'actual' => $dtMatLabel,         'current' => $hasRunning ? $currDtMat.' m' : '-', 'popup' => true],
            ['desc' => 'LOG_T',    'plan' => '0 m',                      'actual' => $dtLogLabel,         'current' => $hasRunning ? $currDtLog.' m' : '-', 'popup' => true],

            ['desc' => 'OVERTIME', 'plan' => '0 m',                      'actual' => '0 m',               'current' => $hasRunning ? '0 m' : '-'],
            ['desc' => 'REPAIR',   'plan' => '0 pcs',                    'actual' => $repair.' pcs',      'actualPct' => ($ok > 0 ? round(($repair / $ok) * 100, 1) : 0).'%', 'current' => $hasRunning ? $currRepair.' pcs' : '-', 'popup' => true],
            ['desc' => 'REJECT',   'plan' => '0 pcs',                    'actual' => $reject.' pcs',      'actualPct' => ($ok > 0 ? round(($reject / $ok) * 100, 1) : 0).'%', 'current' => $hasRunning ? $currReject.' pcs' : '-', 'popup' => true],
        ];

        $detailData = [
            'QTY' => ['type' => 'production', $lineName => ['rows' => $qtyRows, 'total' => (string) $ok]],
            'TOTAL_DT' => ['type' => 'dt_summary', $lineName => ['rows' => $dtRows, 'total' => (string) round($dtTotalMinutes, 1)]],
            'MACH_T' => ['type' => 'dt_detail',  $lineName => ['rows' => $machRows, 'total' => (string) round($dtMachMinutes, 1)]],
            'DIES_T' => ['type' => 'dt_detail',  $lineName => ['rows' => $diesRows, 'total' => (string) round($dtDiesMinutes, 1)]],
            'MAT_T' => ['type' => 'dt_detail',  $lineName => ['rows' => $matRows, 'total' => (string) round($dtMatMinutes, 1)]],
            'LOG_T' => ['type' => 'dt_detail',  $lineName => ['rows' => $logRows, 'total' => (string) round($dtLogMinutes, 1)]],
            'PROD_T' => ['type' => 'dt_detail',  $lineName => ['rows' => $prodRows, 'total' => (string) round($dtProdMinutes, 1)]],
            'REPAIR' => ['type' => 'quality',    $lineName => ['rows' => $repairRows, 'total' => (string) $repair]],
            'REJECT' => ['type' => 'quality',    $lineName => ['rows' => $rejectRows, 'total' => (string) $reject]],
        ];

        $jobName = '-';
        if ($hasRunning && $runningRecord?->jobMaster) {
            $jmObj = $runningRecord->jobMaster;
            $matchedPlan = $plansByIdentifier[$jmObj->job_number ?? ''] ?? null;
            if ($matchedPlan && ! empty(trim($matchedPlan->job_no ?? ''))) {
                $jobName = trim($matchedPlan->job_no);
            } else {
                $raw = $jmObj->job_name ?? $jmObj->job_number ?? '';
                $jobName = $shortJob($raw);
            }
        }

        $uniqueBasePlans = $plans->filter(fn ($p) => empty($p->parent_job_id));
        $totalJobs = $uniqueBasePlans->count();

        // Finished jobs: count unique base plans whose parent or child jobs are finished
        $finishedStatuses = ['finished', 'complete', 'completed', 'closed', 'done'];
        $finishedBaseCount = 0;
        foreach ($uniqueBasePlans as $bp) {
            $bjn = trim($bp->job_no ?? '');
            $bjm = trim($bp->job_master ?? '');
            $bIdent = $bjn ? ($bjn.'-'.$bp->id) : ('AUTO-'.\Illuminate\Support\Str::slug($bjm).'-'.$bp->id);
            $bJob = $jobMasters->get($bIdent);
            $isBaseFinished = ($bJob && in_array(strtolower((string) $bJob->status), $finishedStatuses, true))
                || in_array(strtolower((string) $bp->status), $finishedStatuses, true);

            if (! $isBaseFinished) {
                $childPlans = $plans->filter(fn ($cp) => ! empty($cp->parent_job_id) && (int) $cp->parent_job_id === (int) $bp->id);
                foreach ($childPlans as $cp) {
                    $cjn = trim($cp->job_no ?? '');
                    $cjm = trim($cp->job_master ?? '');
                    $cIdent = $cjn ? ($cjn.'-'.$cp->id) : ('AUTO-'.\Illuminate\Support\Str::slug($cjm).'-'.$cp->id);
                    $cJob = $jobMasters->get($cIdent);
                    if (($cJob && in_array(strtolower((string) $cJob->status), $finishedStatuses, true))
                        || in_array(strtolower((string) $cp->status), $finishedStatuses, true)) {
                        $isBaseFinished = true;
                        break;
                    }
                }
            }

            if ($isBaseFinished) {
                $finishedBaseCount++;
            }
        }
        $finishedJobs = $finishedBaseCount;
        if ($totalJobs > 0 && $finishedJobs > $totalJobs) {
            $finishedJobs = $totalJobs;
        }
        $jobActual = $finishedJobs.'/'.$totalJobs;

        $totalStroke = $ok + $repair + $reject;

        $currStroke = $hasRunning ? ($currOk + $currRepair + $currReject) : '-';

        $meta = [
            'job' => $jobName,
            'jobPlan' => (string) $totalJobs,
            'jobActual' => $jobActual,
            'stroke' => (string) $totalStroke,
            'currStroke' => (string) $currStroke,
        ];

        return compact('kpi', 'detailData', 'meta');
    }

    public static function signalUpdate(string $line): void
    {
        if ($line) {
            Cache::put("dash_update_{$line}", now()->timestamp, 60);
        }
    }

    public static function getUpdatedLines(): array
    {
        $lines = [];
        $prefix = 'dash_update_';
        $prefixLen = strlen($prefix);
        foreach (Cache::getMultiple([]) as $key => $val) {
            // all keys not possible with file cache; fallback handled in stream endpoint
        }

        return $lines;
    }

    public static function hasUpdate(string $line): bool
    {
        return Cache::has("dash_update_{$line}");
    }

    public static function consumeUpdate(string $line): ?int
    {
        $ts = Cache::get("dash_update_{$line}");
        if ($ts) {
            Cache::forget("dash_update_{$line}");

            return $ts;
        }

        return null;
    }

    private function downtimeDurationSeconds(Downtime $dt): float
    {
        if (! empty($dt->duration_seconds)) {
            return (float) $dt->duration_seconds;
        }
        if ($dt->finish_time) {
            return (float) abs(Carbon::parse($dt->finish_time)->diffInSeconds(Carbon::parse($dt->start_time)));
        }
        if ($dt->start_time) {
            return (float) abs(Carbon::now()->diffInSeconds(Carbon::parse($dt->start_time)));
        }

        return 0.0;
    }

    private function normalizePressName(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/^(PRESS|LINE)\s+/', '', $normalized);

        return preg_replace('/[\s\-_.]+/', '', $normalized);
    }

    protected static array $plansCache = [];

    protected static ?Collection $allLineMasters = null;

    protected static array $resolvedDates = [];

    public static function flushCache(): void
    {
        self::$plansCache = [];
        self::$allLineMasters = null;
        self::$resolvedDates = [];
    }

    /**
     * Tanggal schedule yang benar-benar punya data. Kalau tanggal yang diminta kosong,
     * mundur ke tanggal schedule terakhir yang punya job supaya dashboard tidak blank.
     */
    private function resolvePlanDate(string $date): string
    {
        if (isset(self::$resolvedDates[$date])) {
            return self::$resolvedDates[$date];
        }

        $hasJobs = ProductionPlan::whereDate('plan_date', $date)
            ->where('row_type', 'job')
            ->exists();

        if ($hasJobs) {
            self::$resolvedDates[$date] = $date;

            return $date;
        }

        $latest = ProductionPlan::where('row_type', 'job')->max('plan_date');
        $resolved = $latest ? Carbon::parse($latest)->toDateString() : $date;
        self::$resolvedDates[$date] = $resolved;

        return $resolved;
    }

    /**
     * Baris job untuk tanggal + shift tertentu.
     */
    private function getPlansForDateAndShift(string $date, bool $isMorning)
    {
        $cacheKey = $date.'_'.($isMorning ? '1' : '2');
        if (isset(self::$plansCache[$cacheKey])) {
            return self::$plansCache[$cacheKey];
        }

        $plans = ProductionPlan::whereDate('plan_date', $date)
            ->where(function ($q) use ($isMorning) {
                if ($isMorning) {
                    $q->where('shift_name', 'like', '%Pagi%')
                        ->orWhere(function ($sq) {
                            $sq->where('shift_name', 'like', '%1%')
                                ->where('shift_name', 'not like', '%Malam%');
                        });
                } else {
                    $q->where('shift_name', 'like', '%Malam%')
                        ->orWhere(function ($sq) {
                            $sq->where('shift_name', 'like', '%2%')
                                ->where('shift_name', 'not like', '%Pagi%');
                        });
                }
            })
            ->where('row_type', 'job')
            ->where(function ($q) {
                $q->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                    ->orWhereNull('job_no');
            })
            ->orderBy('row_no')
            ->get();

        self::$plansCache[$cacheKey] = $plans;

        return $plans;
    }

    private function pressMatches(ProductionPlan $plan, string $lineName): bool
    {
        $target = $this->normalizePressName($lineName);
        if ($target === '') {
            return false;
        }

        $pressRaw = trim((string) ($plan->press_name ?? ''));
        if ($pressRaw !== '' && $this->normalizePressName($pressRaw) === $target) {
            return true;
        }

        if (self::$allLineMasters === null) {
            self::$allLineMasters = LineMaster::all();
        }

        // press_name kadang tersimpan sebagai line_code (mis. "PA"), bukan "PRESS A"
        if ($pressRaw !== '') {
            $master = self::$allLineMasters->firstWhere('line_name', $lineName);
            if ($master && $this->normalizePressName($master->line_code ?? '') === $this->normalizePressName($pressRaw)) {
                return true;
            }
        }

        $lineMasterId = $plan->line_master_id ?? null;
        if ($lineMasterId) {
            $master = self::$allLineMasters->firstWhere('line_name', $lineName);
            if ($master && (string) $lineMasterId === (string) $master->id) {
                return true;
            }
        }

        return false;
    }

    private function emptyMetrics(string $lineName): array
    {
        $kpi = [
            ['desc' => 'QTY',      'plan' => '0',     'actual' => '0',     'actualLink' => true, 'current' => '-'],
            ['desc' => 'GSPH',     'plan' => '0',     'actual' => '0',     'current' => '-'],
            ['desc' => 'PROD_T',   'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],
            ['desc' => 'TOTAL_DT', 'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],
            ['desc' => 'MACH_T',   'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],
            ['desc' => 'DIES_T',   'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],
            ['desc' => 'MAT_T',    'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],
            ['desc' => 'LOG_T',    'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],

            ['desc' => 'OVERTIME', 'plan' => '0 m',   'actual' => '0 m',   'current' => '-'],
            ['desc' => 'REPAIR',   'plan' => '0 pcs', 'actual' => '0 pcs', 'actualPct' => '0%',   'current' => '0 pcs'],
            ['desc' => 'REJECT',   'plan' => '0 pcs', 'actual' => '0 pcs', 'actualPct' => '0%',   'current' => '0 pcs'],
        ];
        $detailData = [
            'QTY' => ['type' => 'production', $lineName => ['rows' => [], 'total' => '0']],
            'TOTAL_DT' => ['type' => 'dt_summary', $lineName => ['rows' => [], 'total' => '0']],
            'MACH_T' => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'DIES_T' => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'MAT_T' => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'LOG_T' => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'PROD_T' => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'REPAIR' => ['type' => 'quality',    $lineName => ['rows' => [], 'total' => '0']],
            'REJECT' => ['type' => 'quality',    $lineName => ['rows' => [], 'total' => '0']],
        ];
        $meta = [
            'job' => '-',
            'jobPlan' => '0',
            'jobActual' => '0/0',
            'stroke' => '0',
        ];

        return compact('kpi', 'detailData', 'meta');
    }
}
