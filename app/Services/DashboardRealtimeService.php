<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

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
        $shortJob = fn($name) => strtoupper(trim(explode(' ', $name ?? '-')[0])) ?: '-';
        $planShiftText = self::SHIFT_PLAN_MAP[$shift] ?? 'Shift Pagi';

        $shiftStartDt = $shift === 1
            ? Carbon::parse($date)->setTime(7, 30)
            : Carbon::parse($date)->subDay()->setTime(21, 0);
        $shiftEndDt = $shift === 1
            ? Carbon::parse($date)->setTime(21, 0)
            : Carbon::parse($date)->addDay()->setTime(7, 30);

        $workDate = $shift === 2 ? Carbon::parse($date)->subDay()->toDateString() : $date;

        $normalizedPress = strtoupper(preg_replace('/^(PRESS|LINE)\s*/i', '', $lineName));

        $plans = ProductionPlan::where('plan_date', $date)
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
            ->where('shift_name', 'like', $planShiftText . '%')
            ->where('row_type', 'job')
            ->where(function ($q) {
                $q->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                  ->orWhereNull('job_no');
            })
            ->orderBy('row_no')
            ->get();

        if ($plans->isEmpty()) {
            return $this->emptyMetrics($lineName);
        }

        $jobNumbers = $plans->map(function ($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->get()
            ->keyBy('job_number');

        foreach ($plans as $p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);

            if (!$jobMasters->has($identifier)) {
                $jobName = $p->job_master ?: ($p->job_no ?: 'UNKNOWN JOB');
                $newJob = JobMaster::create([
                    'job_number'   => $identifier,
                    'job_name'     => $jobName,
                    'line'         => $p->press_name ?? $lineName,
                    'target_qty'   => (int) ($p->plan ?? 0),
                    'sequence_no'  => $p->row_no ?? 1,
                    'status'       => 'pending',
                    'plan_start'   => $p->start_time ? Carbon::parse($date . ' ' . $p->start_time)->startOfMinute() : null,
                    'plan_end'     => $p->finish_time ? Carbon::parse($date . ' ' . $p->finish_time)->startOfMinute() : null,
                    'capacity'     => (int) ($p->qty_plt ?? 0),
                ]);
                $jobMasters->put($identifier, $newJob);
            }
        }

        $jobIds = $jobMasters->pluck('id');

        $dailyRecords = DailyProduction::with('jobMaster')
            ->where('work_date', $workDate)
            ->whereIn('job_master_id', $jobIds)
            ->get();

        $activeJobIds = $dailyRecords->pluck('job_master_id')->toArray();
        $missingJobs = $jobMasters->whereNotIn('id', $activeJobIds);

        foreach ($missingJobs as $mj) {
            $existingDaily = DailyProduction::where('job_master_id', $mj->id)->first();

            if ($existingDaily) {
                $existingDaily->work_date = $workDate;
                $existingDaily->setRelation('jobMaster', $mj);
                $dailyRecords->push($existingDaily);
            } else {
                $logAgg = \App\Models\ProductionLog::where('job_master_id', $mj->id)
                    ->selectRaw('COALESCE(SUM(ok_qty),0) as total_ok, COALESCE(SUM(repair_qty),0) as total_repair, COALESCE(SUM(reject_qty),0) as total_reject')
                    ->first();

                $downtimeSecs = (int) Downtime::where('job_master_id', $mj->id)
                    ->where('start_time', '>=', $shiftStartDt)
                    ->where('start_time', '<', $shiftEndDt)
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->sum('duration_seconds');

                $virtual = new DailyProduction([
                    'job_master_id'    => $mj->id,
                    'work_date'        => $workDate,
                    'actual_ok'        => (int) $logAgg->total_ok,
                    'actual_qty'       => (int) $logAgg->total_ok,
                    'actual_repair'    => (int) $logAgg->total_repair,
                    'actual_reject'    => (int) $logAgg->total_reject,
                    'runtime_seconds'  => 0,
                    'downtime_seconds' => $downtimeSecs,
                    'line'             => $lineName,
                    'shift'            => self::SHIFT_MAP[$shift] ?? 'Shift Pagi',
                ]);
                $virtual->setRelation('jobMaster', $mj);
                $dailyRecords->push($virtual);
            }
        }

        if ($dailyRecords->isEmpty()) {
            return $this->emptyMetrics($lineName);
        }

        $ok      = (int) $dailyRecords->sum('actual_ok');
        $repair  = (int) $dailyRecords->sum('actual_repair');
        $reject  = (int) $dailyRecords->sum('actual_reject');

        $runningRecord = $dailyRecords->first(function ($dp) {
            return $dp->jobMaster && in_array($dp->jobMaster->status, ['running', 'paused']);
        });

        if (!$runningRecord) {
            $runningRecord = $dailyRecords->filter(function ($dp) {
                return $dp->jobMaster && $dp->jobMaster->started_at !== null;
            })->sortByDesc(function ($dp) {
                return $dp->jobMaster->started_at;
            })->first();
        }

        if (!$runningRecord && $dailyRecords->isNotEmpty()) {
            $runningRecord = $dailyRecords->sortBy(function ($dp) {
                return $dp->jobMaster->sequence_no ?? 9999;
            })->first();
        }

        $hasRunning = $runningRecord !== null && $runningRecord->jobMaster && $runningRecord->jobMaster->status === 'running';
        $currOk     = $hasRunning ? (int) $runningRecord->actual_ok : 0;
        $currRepair = $hasRunning ? (int) $runningRecord->actual_repair : 0;
        $currReject = $hasRunning ? (int) $runningRecord->actual_reject : 0;

        $runtime = 0;
        $currRuntime = 0;
        foreach ($dailyRecords as $dp) {
            $dpRuntime = (float) $dp->runtime_seconds;
            if ($dpRuntime <= 0 && $dp->jobMaster) {
                if ($dp->jobMaster->status === 'running') {
                    $session = \App\Models\ProductionSession::where('job_master_id', $dp->job_master_id)
                        ->where('work_date', $workDate)
                        ->first();
                    if ($session && $session->start_time) {
                        $dpRuntime = (float) $session->total_seconds;
                        if ($session->status === 'running') {
                            $dpRuntime += abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($session->start_time)));
                        }
                    } elseif ($dp->jobMaster->started_at) {
                        $dpRuntime = abs(\Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($dp->jobMaster->started_at)));
                    }
                } elseif ($dp->jobMaster->status === 'paused') {
                    $session = \App\Models\ProductionSession::where('job_master_id', $dp->job_master_id)
                        ->where('work_date', $workDate)
                        ->first();
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
        $dtSecs  = (int) $dailyRecords->sum('downtime_seconds');

        $planQty = (int) $plans->sum('plan');

        if ($planQty <= 0) {
            $planQty = (int) JobMaster::whereIn('id', $jobIds)
                ->whereHas('dailyProduction', function ($q) use ($workDate) {
                    $q->where('work_date', $workDate);
                })
                ->sum('target_qty');
        }

        $allDowntimes = Downtime::with('jobMaster')->whereIn('job_master_id', $jobIds)
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
            $type = strtoupper($dt->jenis_downtime ?? '');
            if (ProductionMetricsService::isExcludedDowntimeType($type)) continue;
            $dtTotalMinutes += $this->downtimeDurationSeconds($dt) / 60;
        }
        $dtTotalMinutes = round($dtTotalMinutes, 1);

        $breakdown = ProductionMetricsService::downtimeBreakdown($allDowntimes);

        $dtMachMinutes = $breakdown['machine'];
        $dtMatMinutes  = $breakdown['material'];
        $dtLogMinutes  = $breakdown['logistic'];
        $dtProdMinutes = $breakdown['production'];
        $dtDiesMinutes = $breakdown['dies'];
        $dtTotalCategorized = $breakdown['total'];

        $currDtTotal = 0;
        $currDtMach  = 0;
        $currDtDies  = 0;
        $currDtMat   = 0;
        $currDtLog   = 0;
        $currDtProd  = 0;
        if ($runningRecord) {
            $runningJobId = $runningRecord->job_master_id;
            $runningDt = $allDowntimes->where('job_master_id', $runningJobId);
            foreach ($runningDt as $dt) {
                $type = strtoupper($dt->jenis_downtime ?? '');
                if (ProductionMetricsService::isExcludedDowntimeType($type)) continue;
                $dur = $this->downtimeDurationSeconds($dt) / 60;
                $currDtTotal += $dur;
                $type = strtoupper($dt->jenis_downtime ?? '');
                if (str_contains($type, 'MACHINE') || str_contains($type, 'MACH')) $currDtMach += $dur;
                elseif (str_contains($type, 'DIES')) $currDtDies += $dur;
                elseif (str_contains($type, 'MATERIAL') || str_contains($type, 'MAT')) $currDtMat += $dur;
                elseif (str_contains($type, 'LOGISTIC') || str_contains($type, 'LOG')) $currDtLog += $dur;
                else $currDtProd += $dur;
            }
            $currDtTotal = round($currDtTotal, 1);
            $currDtMach  = round($currDtMach, 1);
            $currDtDies  = round($currDtDies, 1);
            $currDtMat   = round($currDtMat, 1);
            $currDtLog   = round($currDtLog, 1);
            $currDtProd  = round($currDtProd, 1);
        }
        $currOvertime = round($currDtMach + $currDtDies + $currDtMat + $currDtLog + $currDtProd, 1);

        $dtRows     = [];
        $machRows   = [];
        $diesRows   = [];
        $matRows    = [];
        $logRows    = [];
        $prodRows   = [];
        $repairRows = [];
        $rejectRows = [];

        foreach ($allDowntimes as $dt) {
            $type = strtoupper($dt->jenis_downtime ?? '');
            if (ProductionMetricsService::isExcludedDowntimeType($type)) continue;
            $dur = round($this->downtimeDurationSeconds($dt) / 60, 1);
            $rawJob = $dt->jobMaster?->job_name ?? '-';
            $jobName = $shortJob($rawJob);
            $row = [
                'no'       => count($dtRows) + 1,
                'jenis'    => $dt->jenis_downtime,
                'job'      => $jobName,
                'item'     => $dt->problem ?? '-',
                'problem'  => $dt->problem ?? '-',
                'penyebab' => $dt->penyebab ?? '-',
                'action'   => $dt->action ?? '-',
                'durasi'   => $dur,
            ];
            $dtRows[] = $row;

            if (str_contains($type, 'MACHINE') || str_contains($type, 'MACH')) {
                $machRows[] = $row;
            } elseif (str_contains($type, 'DIES')) {
                $diesRows[] = $row;
            } elseif (str_contains($type, 'MATERIAL') || str_contains($type, 'MAT')) {
                $matRows[] = $row;
            } elseif (str_contains($type, 'LOGISTIC') || str_contains($type, 'LOG')) {
                $logRows[] = $row;
            } else {
                $prodRows[] = $row;
            }
        }

        foreach ($dailyRecords as $dp) {
            if ($dp->actual_repair > 0) {
                $job = $dp->jobMaster;
                $repairRows[] = [
                    'no'      => count($repairRows) + 1,
                    'item'    => $shortJob($job?->job_name),
                    'problem' => 'Defect',
                    'qty'     => (int) $dp->actual_repair,
                ];
            }
            if ($dp->actual_reject > 0) {
                $job = $dp->jobMaster;
                $rejectRows[] = [
                    'no'      => count($rejectRows) + 1,
                    'item'    => $shortJob($job?->job_name),
                    'problem' => 'Reject',
                    'qty'     => (int) $dp->actual_reject,
                ];
            }
        }

        $qtyRows = [];
        $runtimeRows = [];
        foreach ($dailyRecords as $dp) {
            $job = $dp->jobMaster;
            $qtyRows[] = [
                'no'     => count($qtyRows) + 1,
                'item'   => $shortJob($job?->job_name),
                'ok'     => (int) $dp->actual_ok,
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
                'no'      => count($runtimeRows) + 1,
                'item'    => $shortJob($job?->job_name),
                'durasi'  => $jobRuntime . ' m',
            ];
        }

        $runtimeMinutes = round(array_sum(array_map(fn($r) => (float) str_replace(' m', '', $r['durasi']), $runtimeRows)) ?: $runtime / 60, 1);

        $gsph = ProductionMetricsService::gsph($ok, max($runtimeMinutes, 30));
        $currRuntimeMinutes = round($currRuntime / 60, 1);
        $currGsph = $hasRunning ? ProductionMetricsService::gsph($currOk, max($currRuntimeMinutes, 30)) : 0;

        $planGsphItem = (int) round($plans->max('gsph_item') ?: 0);
        $gsphPlan = $planGsphItem > 0 ? $planGsphItem : ProductionMetricsService::gsph($planQty, max($runtimeMinutes, 30));

        $dtProdLabel = round($dtProdMinutes, 2) . ' m';
        $dtTotalLabel = round($dtTotalMinutes, 2) . ' m';
        $dtMachLabel = round($dtMachMinutes, 2) . ' m';
        $dtDiesLabel = round($dtDiesMinutes, 2) . ' m';
        $dtMatLabel  = round($dtMatMinutes, 2) . ' m';
        $dtLogLabel  = round($dtLogMinutes, 2) . ' m';
        $overtimeMinutes = round($dtMachMinutes + $dtDiesMinutes + $dtMatMinutes + $dtLogMinutes + $dtProdMinutes, 1);
        $overtimeLabel = round($overtimeMinutes, 2) . ' m';

        $kpi = [
            ['desc'=>'QTY',      'plan'=>(string)$planQty,           'actual'=>(string)$ok,         'actualLink'=>true, 'current'=>$hasRunning ? (string)$currOk : '-'],
            ['desc'=>'GSPH',     'plan'=>(string)$gsphPlan,          'actual'=>(string)$gsph,       'current'=>$hasRunning ? (string)$currGsph : '-'],
            ['desc'=>'PROD_T',   'plan'=>'0 m',                      'actual'=>$dtProdLabel,        'current'=>$hasRunning ? $currDtProd.' m' : '-', 'popup'=>true],
            ['desc'=>'TOTAL_DT', 'plan'=>'0 m',                      'actual'=>$dtTotalLabel,       'current'=>$hasRunning ? $currDtTotal.' m' : '-', 'popup'=>true, 'danger'=>$dtTotalMinutes > 0],
            ['desc'=>'MACH_T',   'plan'=>'0 m',                      'actual'=>$dtMachLabel,        'current'=>$hasRunning ? $currDtMach.' m' : '-', 'popup'=>true],
            ['desc'=>'DIES_T',   'plan'=>'0 m',                      'actual'=>$dtDiesLabel,        'current'=>$hasRunning ? $currDtDies.' m' : '-', 'popup'=>true],
            ['desc'=>'MAT_T',    'plan'=>'0 m',                      'actual'=>$dtMatLabel,         'current'=>$hasRunning ? $currDtMat.' m' : '-', 'popup'=>true],
            ['desc'=>'LOG_T',    'plan'=>'0 m',                      'actual'=>$dtLogLabel,         'current'=>$hasRunning ? $currDtLog.' m' : '-', 'popup'=>true],

            ['desc'=>'OVERTIME', 'plan'=>'0 m',                      'actual'=>$overtimeLabel,      'current'=>$hasRunning ? $currOvertime.' m' : '-', 'popup'=>true],
            ['desc'=>'REPAIR',   'plan'=>'0 pcs',                    'actual'=>$repair.' pcs',      'actualPct'=>($ok>0?round(($repair/$ok)*100,1):0).'%', 'current'=>$hasRunning ? $currRepair.' pcs' : '-', 'popup'=>true],
            ['desc'=>'REJECT',   'plan'=>'0 pcs',                    'actual'=>$reject.' pcs',      'actualPct'=>($ok>0?round(($reject/$ok)*100,1):0).'%', 'current'=>$hasRunning ? $currReject.' pcs' : '-', 'popup'=>true],
        ];

        $detailData = [
            'QTY'      => ['type' => 'production', $lineName => ['rows' => $qtyRows, 'total' => (string)$ok]],
            'TOTAL_DT' => ['type' => 'dt_summary', $lineName => ['rows' => $dtRows, 'total' => (string)round($dtTotalMinutes, 2)]],
            'MACH_T'   => ['type' => 'dt_detail',  $lineName => ['rows' => $machRows, 'total' => (string)round($dtMachMinutes, 2)]],
            'DIES_T'   => ['type' => 'dt_detail',  $lineName => ['rows' => $diesRows, 'total' => (string)round($dtDiesMinutes, 2)]],
            'MAT_T'    => ['type' => 'dt_detail',  $lineName => ['rows' => $matRows, 'total' => (string)round($dtMatMinutes, 2)]],
            'LOG_T'    => ['type' => 'dt_detail',  $lineName => ['rows' => $logRows, 'total' => (string)round($dtLogMinutes, 2)]],
            'PROD_T'   => ['type' => 'dt_detail',  $lineName => ['rows' => $prodRows, 'total' => (string)round($dtProdMinutes, 2)]],
            'REPAIR'   => ['type' => 'quality',    $lineName => ['rows' => $repairRows, 'total' => (string)$repair]],
            'REJECT'   => ['type' => 'quality',    $lineName => ['rows' => $rejectRows, 'total' => (string)$reject]],
            'OVERTIME' => ['type' => 'dt_summary', $lineName => ['rows' => $dtRows, 'total' => (string)round($overtimeMinutes, 2)]],
        ];

        $jobName = '-';
        if ($hasRunning && $runningRecord?->jobMaster) {
            $raw = $runningRecord->jobMaster->job_name ?? $runningRecord->jobMaster->job_number ?? '';
            $jobName = $shortJob($raw);
        }

        $totalJobs = $plans->count();
        $finishedJobs = $dailyRecords->filter(fn($dp) => $dp->jobMaster && in_array($dp->jobMaster->status, ['finished', 'complete', 'closed']))->count();
        $jobActual = $finishedJobs . '/' . $totalJobs;

        $totalStroke = $ok + $repair + $reject;

        $currStroke = $hasRunning ? ($currOk + $currRepair + $currReject) : '-';

        $meta = [
            'job'        => $jobName,
            'jobPlan'    => (string) $totalJobs,
            'jobActual'  => $jobActual,
            'stroke'     => (string) $totalStroke,
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
        foreach (Cache::getMultiple(/* all keys not possible with file cache */) ?: [] as $key => $val) {
            // fallback handled in stream endpoint
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

    private function downtimeDurationSeconds($dt): float
    {
        if (!empty($dt->duration_seconds)) {
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

    private function emptyMetrics(string $lineName): array
    {
        $kpi = [
            ['desc'=>'QTY',      'plan'=>'0',     'actual'=>'0',     'actualLink'=>true, 'current'=>'-'],
            ['desc'=>'GSPH',     'plan'=>'0',     'actual'=>'0',     'current'=>'-'],
            ['desc'=>'PROD_T',   'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],
            ['desc'=>'TOTAL_DT', 'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],
            ['desc'=>'MACH_T',   'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],
            ['desc'=>'DIES_T',   'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],
            ['desc'=>'MAT_T',    'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],
            ['desc'=>'LOG_T',    'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],

            ['desc'=>'OVERTIME', 'plan'=>'0 m',   'actual'=>'0 m',   'current'=>'-', 'popup'=>true],
            ['desc'=>'REPAIR',   'plan'=>'0 pcs', 'actual'=>'0 pcs', 'actualPct'=>'0%',   'current'=>'0 pcs', 'popup'=>true],
            ['desc'=>'REJECT',   'plan'=>'0 pcs', 'actual'=>'0 pcs', 'actualPct'=>'0%',   'current'=>'0 pcs', 'popup'=>true],
        ];
        $detailData = [
            'QTY'      => ['type' => 'production', $lineName => ['rows' => [], 'total' => '0']],
            'TOTAL_DT' => ['type' => 'dt_summary', $lineName => ['rows' => [], 'total' => '0']],
            'MACH_T'   => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'DIES_T'   => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'MAT_T'    => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'LOG_T'    => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'PROD_T'   => ['type' => 'dt_detail',  $lineName => ['rows' => [], 'total' => '0']],
            'REPAIR'   => ['type' => 'quality',    $lineName => ['rows' => [], 'total' => '0']],
            'REJECT'   => ['type' => 'quality',    $lineName => ['rows' => [], 'total' => '0']],
            'OVERTIME' => ['type' => 'dt_summary', $lineName => ['rows' => [], 'total' => '0']],
        ];
        $meta = [
            'job'        => '-',
            'jobPlan'    => '0',
            'jobActual'  => '0/0',
            'stroke'     => '0',
        ];
        return compact('kpi', 'detailData', 'meta');
    }
}
