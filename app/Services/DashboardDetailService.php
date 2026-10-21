<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\Dandori;
use App\Models\QCheck;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardDetailService
{
    const SHIFT_MAP = [
        1 => 'Shift Pagi',
        2 => 'Shift Malam',
    ];

    public function getLineDetail(string $lineName, string $date, int $shift): array
    {
        $planShiftText = self::SHIFT_MAP[$shift] ?? 'Shift Pagi';

        $workDate = $date;

        $isMorning = ($shift === 1);
        $plans = $this->getPlansForDateAndShift($date, $isMorning)
            ->filter(fn ($p) => $this->pressMatches($p, $lineName))
            ->values();

        if ($plans->isEmpty()) {
            return [];
        }

        $jobNumbers = $plans->map(function ($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->get()
            ->keyBy('job_number');

        $insertJobs = [];
        foreach ($plans as $p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);

            if (! $jobMasters->has($identifier)) {
                $insertJobs[] = [
                    'job_number'  => $identifier,
                    'job_name'    => $p->job_master ?: ($p->job_no ?: 'UNKNOWN JOB'),
                    'line'        => $p->press_name ?? $lineName,
                    'target_qty'  => (int) ($p->plan ?? 0),
                    'sequence_no' => $p->row_no ?? 1,
                    'status'      => 'pending',
                    'plan_start'  => $p->start_time ? Carbon::parse($date . ' ' . $p->start_time)->startOfMinute() : null,
                    'plan_end'    => $p->finish_time ? Carbon::parse($date . ' ' . $p->finish_time)->startOfMinute() : null,
                    'capacity'    => (int) ($p->qty_plt ?? 0),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        if (!empty($insertJobs)) {
            JobMaster::insertOrIgnore($insertJobs);
            $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
                ->get()
                ->keyBy('job_number');
        }

        $jobIds = $jobMasters->pluck('id');

        $dailyRecords = DailyProduction::where('work_date', $workDate)
            ->whereIn('job_master_id', $jobIds)
            ->get()
            ->keyBy('job_master_id');

        if ($dailyRecords->isEmpty()) {
            $fallbackDate = Carbon::parse($workDate)->subDay()->toDateString();
            $dailyRecords = DailyProduction::where('work_date', $fallbackDate)
                ->whereIn('job_master_id', $jobIds)
                ->get()
                ->keyBy('job_master_id');
        }

        $dandoriModels = Dandori::whereIn('next_job_id', $jobIds)->get();

        $dandoriMinutes = [];
        $firstCheckMinutes = [];
        foreach ($dandoriModels as $d) {
            $jId = $d->next_job_id;
            $dur = (float) ($d->duration_minutes ?? 0);
            if ($dur <= 0 && $d->start_time) {
                $end = $d->finish_time ? Carbon::parse($d->finish_time) : now();
                $dur = round(abs($end->diffInSeconds(Carbon::parse($d->start_time))) / 60, 2);
            }
            $jenis = strtolower(trim($d->jenis_dandori ?? ''));
            $act = strtoupper(trim($d->activity ?? ''));

            if ($jenis === '1st_check' || $jenis === '1st check' || $act === '1ST CHECK') {
                $firstCheckMinutes[$jId] = ($firstCheckMinutes[$jId] ?? 0) + $dur;
            } else {
                $dandoriMinutes[$jId] = ($dandoriMinutes[$jId] ?? 0) + $dur;
            }
        }

        $qcheckModels = QCheck::whereIn('job_master_id', $jobIds)
            ->whereNotNull('start_time')
            ->whereNotNull('finish_time')
            ->get();

        foreach ($qcheckModels as $qc) {
            $firstCheckMinutes[$qc->job_master_id] = ($firstCheckMinutes[$qc->job_master_id] ?? 0) + $qc->duration;
        }

        $downtimeData = Downtime::with('hambatanJalur')->whereIn('job_master_id', $jobIds)
            ->whereNotNull('duration_seconds')
            ->get();

        $downtimeMinutes = [];
        $downtimeItems = [];
        foreach ($downtimeData as $dt) {
            $jenis = strtolower($dt->jenis_downtime ?? '');
            if (in_array($jenis, ['dandori', 'idle time', 'idle', 'break time'])) {
                continue;
            }
            $downtimeMinutes[$dt->job_master_id] = ($downtimeMinutes[$dt->job_master_id] ?? 0) + ($dt->duration_seconds / 60);

            $startTimeFormatted = $dt->start_time ? Carbon::parse($dt->start_time)->format('H:i') : '-';
            $finishTimeFormatted = $dt->finish_time ? Carbon::parse($dt->finish_time)->format('H:i') : 'Sekarang';
            $downtimeItems[$dt->job_master_id][] = [
                'id'              => $dt->id,
                'jenis_downtime'  => strtoupper($dt->jenis_downtime ?: 'DOWNTIME'),
                'problem'         => $dt->problem ?: '-',
                'penyebab'        => $dt->penyebab ?: '-',
                'tindakan'        => $dt->tindakan ?: '-',
                'pic'             => $dt->pic ?: '-',
                'start_time'      => $startTimeFormatted,
                'finish_time'     => $finishTimeFormatted,
                'duration_minute' => round($dt->duration_seconds / 60, 1),
                'lhj_id'          => $dt->hambatanJalur?->id,
                'lhj_status'      => $dt->hambatanJalur?->status,
            ];
        }

        // ── REPAIR & REJECT LOGS ──
        $repairRejectLogs = \App\Models\RepairRejectLog::with('creator')
            ->whereIn('job_master_id', $jobIds)
            ->get();

        $repairItems = [];
        $rejectItems = [];
        foreach ($repairRejectLogs as $rr) {
            $logType = strtolower(trim($rr->type ?? ''));
            $picName = $rr->creator?->name;
            if (!$picName && !empty($rr->created_by)) {
                $userRec = \App\Models\User::find($rr->created_by);
                $picName = $userRec?->name;
            }
            if (!$picName) {
                $picName = '-';
            }

            $timeFormatted = $rr->created_at ? Carbon::parse($rr->created_at)->format('H:i') : '-';
            $itemData = [
                'id'              => $rr->id,
                'type'            => strtoupper($logType),
                'defect_name'     => $rr->defect_name ?: '-',
                'qty'             => (int) ($rr->qty_a ?: 1),
                'repair_category' => $rr->repair_category ?: '-',
                'area_problem'    => $rr->area_problem ?: '-',
                'root_cause'      => $rr->root_cause ?: '-',
                'countermeasure'  => $rr->countermeasure ?: '-',
                'pic'             => $picName,
                'time'            => $timeFormatted,
            ];

            if ($logType === 'repair') {
                $repairItems[$rr->job_master_id][] = $itemData;
            } elseif ($logType === 'reject') {
                $rejectItems[$rr->job_master_id][] = $itemData;
            }
        }

        // ── MERGE BREAK SPLITS / CONSECUTIVE SAME JOB INTERVALS ──
        // Menggabungkan baris yang terpecah karena jeda istirahat menjadi 1 baris produksi
        $parentIds = $plans->pluck('id')->filter()->toArray();
        $childPlansMap = ProductionPlan::whereIn('parent_job_id', $parentIds)
            ->whereDate('plan_date', $date)
            ->get()
            ->groupBy('parent_job_id');

        // Filter out child plans agar hanya parent (atau non-child) yang menjadi baris utama
        $displayPlans = $plans->filter(fn ($p) => empty($p->parent_job_id))->values();

        $rows = [];
        $no = 0;

        foreach ($displayPlans as $plan) {
            $no++;
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);

            $job = $jobMasters->get($identifier);
            $daily = $job ? ($dailyRecords->get($job->id) ?? null) : null;

            // Kumpulkan anak-anak dari parent_job_id
            $children = collect();
            if ($childPlansMap->has($plan->id)) {
                $children = $children->merge($childPlansMap->get($plan->id));
            }

            // Juga deteksi jika ada split item yang memiliki split_group sama
            if (!empty($plan->split_group)) {
                $siblingSplits = $plans->filter(fn($p) => $p->id !== $plan->id && $p->split_group === $plan->split_group);
                $children = $children->merge($siblingSplits);
            }

            // Atau jika ada baris berurutan dengan JOB NO & JOB MASTER yang sama persis di line & shift ini
            $sameJobs = $plans->filter(fn($p) => $p->id !== $plan->id && !empty($p->parent_job_id) && $p->parent_job_id === $plan->id);
            $children = $children->merge($sameJobs)->unique('id');

            // Akumulasi Plan Qty
            $planQty = (int) ($plan->plan ?? 0);
            foreach ($children as $c) {
                $planQty += (int) ($c->plan ?? 0);
            }
            if ($plan->original_plan && $plan->original_plan > $planQty) {
                $planQty = (int) $plan->original_plan;
            }

            // Akumulasi Actual Qty dari parent & children
            $actualQty = $daily ? (int) $daily->actual_qty : 0;
            $actualOk = $daily ? (int) $daily->actual_ok : 0;
            $actualRepair = $daily ? (int) ($daily->actual_repair ?? 0) : 0;
            $actualReject = $daily ? (int) ($daily->actual_reject ?? 0) : 0;

            $jobId = $job?->id;
            $dandori = $jobId ? round($dandoriMinutes[$jobId] ?? 0, 1) : 0;
            $iqCheck = $jobId ? round($firstCheckMinutes[$jobId] ?? 0, 1) : 0;
            $downtime = $jobId ? round($downtimeMinutes[$jobId] ?? 0, 1) : 0;

            $isRunning = $job && in_array(strtolower($job->status), ['running', 'paused']);
            $latestFinishedAt = $job?->finished_at ? Carbon::parse($job->finished_at) : null;

            foreach ($children as $c) {
                $cjn = trim($c->job_no ?? '');
                $cjm = trim($c->job_master ?? '');
                $cIdent = $cjn ? ($cjn . '-' . $c->id) : ('AUTO-' . \Illuminate\Support\Str::slug($cjm) . '-' . $c->id);
                $cJob = $jobMasters->get($cIdent);
                if ($cJob) {
                    $cDaily = $dailyRecords->get($cJob->id);
                    if ($cDaily) {
                        $actualQty += (int) $cDaily->actual_qty;
                        $actualOk += (int) $cDaily->actual_ok;
                        $actualRepair += (int) ($cDaily->actual_repair ?? 0);
                        $actualReject += (int) ($cDaily->actual_reject ?? 0);
                    }
                    $cJobId = $cJob->id;
                    $dandori += round($dandoriMinutes[$cJobId] ?? 0, 1);
                    $iqCheck += round($firstCheckMinutes[$cJobId] ?? 0, 1);
                    $downtime += round($downtimeMinutes[$cJobId] ?? 0, 1);

                    if (in_array(strtolower($cJob->status), ['running', 'paused'])) {
                        $isRunning = true;
                    }
                    if ($cJob->finished_at) {
                        $cFin = Carbon::parse($cJob->finished_at);
                        if (!$latestFinishedAt || $cFin->gt($latestFinishedAt)) {
                            $latestFinishedAt = $cFin;
                        }
                    }
                }
            }

            $ctDetik = (float) ($plan->ct_detik ?? 0);
            $pressTime = $ctDetik > 0 ? round(($actualQty * $ctDetik) / 60, 1) : 0;
            $tpt = round($pressTime + $dandori + $iqCheck + $downtime, 1);

            // GSPH Actual: Total QTY Good / TPT * 60
            $gsphActual = $tpt > 0 ? (int) round(($actualOk / $tpt) * 60) : 0;

            // Plan Finish: Ambil jam selesai terakhir dari interval (termasuk interval setelah istirahat)
            $planFinish = $plan->finish_time;
            if ($children->isNotEmpty()) {
                $lastChildFinish = $children->sortByDesc('finish_time')->first()?->finish_time;
                if ($lastChildFinish && strcmp($lastChildFinish, $planFinish) > 0) {
                    $planFinish = $lastChildFinish;
                }
            }

            $actualFinish = $latestFinishedAt ? $latestFinishedAt->format('H:i') : '-';

            $allDtList = [];
            $allRepairList = [];
            $allRejectList = [];

            if ($jobId) {
                if (isset($downtimeItems[$jobId])) {
                    $allDtList = array_merge($allDtList, $downtimeItems[$jobId]);
                }
                if (isset($repairItems[$jobId])) {
                    $allRepairList = array_merge($allRepairList, $repairItems[$jobId]);
                }
                if (isset($rejectItems[$jobId])) {
                    $allRejectList = array_merge($allRejectList, $rejectItems[$jobId]);
                }
            }
            foreach ($children as $c) {
                $cjn = trim($c->job_no ?? '');
                $cjm = trim($c->job_master ?? '');
                $cIdent = $cjn ? ($cjn . '-' . $c->id) : ('AUTO-' . \Illuminate\Support\Str::slug($cjm) . '-' . $c->id);
                $cJob = $jobMasters->get($cIdent);
                if ($cJob) {
                    $cJobId = $cJob->id;
                    if (isset($downtimeItems[$cJobId])) {
                        $allDtList = array_merge($allDtList, $downtimeItems[$cJobId]);
                    }
                    if (isset($repairItems[$cJobId])) {
                        $allRepairList = array_merge($allRepairList, $repairItems[$cJobId]);
                    }
                    if (isset($rejectItems[$cJobId])) {
                        $allRejectList = array_merge($allRejectList, $rejectItems[$cJobId]);
                    }
                }
            }

            $rows[] = [
                'no'               => $no,
                'job_master_id'    => $jobId,
                'is_running'       => $isRunning,
                'job_number'       => $plan->job_no ?? '-',
                'job_name'         => $plan->job_master ?? ($job?->job_name ?? '-'),
                'p1'               => (bool) (($plan->p1 ?? false) ?: ($plan->a1 > 0) ?: ($plan->total_mesin >= 1)),
                'p2'               => (bool) (($plan->p2 ?? false) ?: ($plan->a2 > 0) ?: ($plan->total_mesin >= 2)),
                'p3'               => (bool) (($plan->p3 ?? false) ?: ($plan->a3 > 0) ?: ($plan->total_mesin >= 3)),
                'p4'               => (bool) (($plan->p4 ?? false) ?: ($plan->a4 > 0) ?: ($plan->total_mesin >= 4)),
                'plan_qty'         => $planQty,
                'good'             => $actualOk,
                'repair'           => $actualRepair,
                'repair_list'      => $allRepairList,
                'reject'           => $actualReject,
                'reject_list'      => $allRejectList,
                'press_time'       => $pressTime,
                'dandori'          => $dandori,
                'iq_check'         => $iqCheck,
                'downtime'         => $downtime,
                'downtime_list'    => $allDtList,
                'tpt'              => $tpt,
                'plan_finish'      => $planFinish ?: '-',
                'actual_finish'    => $actualFinish,
                'gsph_plan'        => (float) ($plan->gsph_item ?? 0),
                'gsph_actual'      => $gsphActual,
            ];
        }

        return $rows;
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

    /**
     * Baris job untuk tanggal + shift tertentu (dimemoize agar tidak query berulang per line).
     */
    private function getPlansForDateAndShift(string $date, bool $isMorning)
    {
        $cacheKey = $date . '_' . ($isMorning ? '1' : '2');
        if (isset(self::$plansCache[$cacheKey])) {
            return self::$plansCache[$cacheKey];
        }

        $plans = ProductionPlan::whereDate('plan_date', $date)
            ->where(function ($q) use ($isMorning) {
                if ($isMorning) {
                    $q->where('shift_name', 'like', '%Pagi%')
                      ->orWhere('shift_name', 'like', '%1%');
                } else {
                    $q->where('shift_name', 'like', '%Malam%')
                      ->orWhere('shift_name', 'like', '%2%');
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

        if (trim((string) ($plan->press_name ?? '')) !== ''
            && $this->normalizePressName($plan->press_name) === $target) {
            return true;
        }

        if (self::$allLineMasters === null) {
            self::$allLineMasters = LineMaster::all();
        }

        // press_name kadang tersimpan sebagai line_code (mis. "PA"), bukan "PRESS A"
        if (trim((string) ($plan->press_name ?? '')) !== '') {
            $master = self::$allLineMasters->firstWhere('line_name', $lineName);
            if ($master && $this->normalizePressName($master->line_code ?? '') === $this->normalizePressName($plan->press_name)) {
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
}
