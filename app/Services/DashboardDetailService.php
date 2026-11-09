<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
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

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->get()
            ->keyBy('job_number');

        // ONE SCHEDULE = ONE JOB IDENTITY:
        // Only auto-create JobMasters for ROOT schedules (!isChildSlice()).
        // Child slices must NEVER spawn separate JobMaster records when Monitoring TV / Dashboard Rincian QTY opens before Input Harian.
        $insertJobs = [];
        foreach ($plans as $p) {
            if ($p->isChildSlice()) {
                continue;
            }
            $identifier = $p->canonicalJobNumber();
            $effectiveFinish = $p->effectiveFinishTime() ?: $p->finish_time;

            if (! $jobMasters->has($identifier)) {
                $insertJobs[] = [
                    'job_number' => $identifier,
                    'job_name' => $p->job_master ?: ($p->job_no ?: 'UNKNOWN JOB'),
                    'line' => $p->press_name ?? $lineName,
                    'target_qty' => $p->effectiveTargetQty(),
                    'sequence_no' => $p->row_no ?? 1,
                    'status' => 'pending',
                    'plan_start' => $p->start_time ? Carbon::parse($date.' '.$p->start_time)->startOfMinute() : null,
                    'plan_end' => $effectiveFinish ? Carbon::parse($date.' '.$effectiveFinish)->startOfMinute() : null,
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
        $nextDayStr = Carbon::parse($workDate)->addDay()->toDateString();

        $dailyRecordsByJobId = DailyProduction::whereIn('job_master_id', $jobIds)
            ->where(function ($q) use ($workDate, $nextDayStr, $shift) {
                if ($shift === 2) {
                    $q->whereIn('work_date', [$workDate, $nextDayStr]);
                } else {
                    $q->where('work_date', $workDate);
                }
            })
            ->get()
            ->groupBy('job_master_id');

        $sessionsMap = \App\Models\ProductionSession::whereIn('job_master_id', $jobIds)
            ->where(function ($q) use ($workDate, $nextDayStr, $shift) {
                if ($shift === 2) {
                    $q->whereIn('work_date', [$workDate, $nextDayStr]);
                } else {
                    $q->where('work_date', $workDate);
                }
            })
            ->get()
            ->keyBy('job_master_id');

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

        if (\Illuminate\Support\Facades\Schema::hasTable('q_checks')) {
            $qcheckModels = QCheck::whereIn('job_master_id', $jobIds)
                ->whereNotNull('start_time')
                ->whereNotNull('finish_time')
                ->get();

            foreach ($qcheckModels as $qc) {
                $firstCheckMinutes[$qc->job_master_id] = ($firstCheckMinutes[$qc->job_master_id] ?? 0) + $qc->duration;
            }
        }

        $downtimeData = Downtime::with('hambatanJalur')->whereIn('job_master_id', $jobIds)
            ->get();

        $downtimeMinutes = [];
        $downtimeItems = [];
        foreach ($downtimeData as $dt) {
            $typeUpper = strtoupper(trim($dt->jenis_downtime ?? ''));
            if (ProductionMetricsService::isExcludedDowntimeType($typeUpper)) {
                continue;
            }

            $durSeconds = 0.0;
            if (! empty($dt->duration_seconds)) {
                $durSeconds = (float) $dt->duration_seconds;
            } elseif ($dt->finish_time && $dt->start_time) {
                $durSeconds = (float) abs(Carbon::parse($dt->finish_time)->diffInSeconds(Carbon::parse($dt->start_time)));
            } elseif ($dt->start_time) {
                $durSeconds = (float) abs(Carbon::now()->diffInSeconds(Carbon::parse($dt->start_time)));
            }

            if ($durSeconds <= 0) {
                continue;
            }

            $downtimeMinutes[$dt->job_master_id] = ($downtimeMinutes[$dt->job_master_id] ?? 0) + ($durSeconds / 60);

            $startTimeFormatted = $dt->start_time ? Carbon::parse($dt->start_time)->format('H:i') : '-';
            $finishTimeFormatted = $dt->finish_time ? Carbon::parse($dt->finish_time)->format('H:i') : 'Sekarang';
            $imageUrl = $dt->image_url;
            $downtimeItems[$dt->job_master_id][] = [
                'id' => $dt->id,
                'jenis_downtime' => strtoupper($dt->jenis_downtime ?: 'DOWNTIME'),
                'problem' => $dt->problem ?: '-',
                'penyebab' => $dt->penyebab ?: '-',
                'tindakan' => $dt->tindakan ?: ($dt->action ?: '-'),
                'pic' => $dt->pic ?: '-',
                'image_url' => $imageUrl,
                'image_url_part' => $dt->image_url_part,
                'image_url_tooling' => $dt->image_url_tooling,
                'image_path_part' => $dt->image_path_part,
                'image_path_tooling' => $dt->image_path_tooling,
                'image_path' => $dt->image_path,
                'start_time' => $startTimeFormatted,
                'finish_time' => $finishTimeFormatted,
                'duration_minute' => round($durSeconds / 60, 1),
                'lhj_id' => $dt->hambatanJalur?->id,
                'lhj_status' => $dt->hambatanJalur?->status,
            ];
        }

        // ── REPAIR & REJECT LOGS ──
        $allDetailJobIds = $jobIds->toArray();
        if (! empty($allDetailJobIds)) {
            $siblingJobIds = JobMaster::whereIn('id', $allDetailJobIds)
                ->get()
                ->flatMap(function ($j) {
                    return app(\App\Services\ProductionService::class)->getSiblingJobMasters($j->id)->pluck('id');
                })
                ->all();
            $allDetailJobIds = array_values(array_unique(array_merge($allDetailJobIds, $siblingJobIds)));
        }

        $repairRejectLogs = \App\Models\RepairRejectLog::with(['creator', 'images'])
            ->whereIn('job_master_id', $allDetailJobIds)
            ->get();

        $repairItems = [];
        $rejectItems = [];
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

            $timeFormatted = $rr->created_at ? Carbon::parse($rr->created_at)->format('H:i') : '-';
            $itemData = [
                'id' => $rr->id,
                'type' => strtoupper($logType),
                'defect_name' => $rr->defect_name ?: '-',
                'qty' => (int) ($rr->qty_a ?: 1),
                'repair_category' => $rr->repair_category ?: '-',
                'area_problem' => $rr->area_problem ?: '-',
                'root_cause' => $rr->root_cause ?: '-',
                'countermeasure' => $rr->countermeasure ?: '-',
                'pic' => $picName,
                'time' => $timeFormatted,
                'images' => $rr->images->map(fn ($i) => $i->url ?: ('/uploads/' . ltrim($i->image_path, '/')))->values()->all(),
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
        $displayPlans = $plans->filter(fn ($p) => ! $p->isChildSlice())->values();

        $rows = [];
        $no = 0;

        foreach ($displayPlans as $plan) {
            $no++;
            $identifier = $plan->canonicalJobNumber();
            $job = $jobMasters->get($identifier);

            // Kumpulkan anak-anak dari parent_job_id dan split_group
            $children = collect();
            if ($childPlansMap->has($plan->id)) {
                $children = $children->merge($childPlansMap->get($plan->id));
            }
            if (! empty($plan->split_group)) {
                $siblingSplits = $plans->filter(fn ($p) => $p->id !== $plan->id && $p->split_group === $plan->split_group);
                $children = $children->merge($siblingSplits);
            }
            $sameJobs = $plans->filter(fn ($p) => $p->id !== $plan->id && ! empty($p->parent_job_id) && (int) $p->parent_job_id === (int) $plan->id);
            $children = $children->merge($sameJobs)->unique('id')->values();

            // Kumpulkan seluruh JobMaster unik dalam satu schedule group (induk + potongan child historis)
            $groupJobs = collect();
            if ($job) {
                $groupJobs->push($job);
            }
            foreach ($children as $c) {
                $cjn = trim($c->job_no ?? '');
                $cjm = trim($c->job_master ?? '');
                $cIdent = $cjn ? ($cjn.'-'.$c->id) : ('AUTO-'.\Illuminate\Support\Str::slug($cjm).'-'.$c->id);
                $cJob = $jobMasters->get($cIdent);
                if ($cJob && ! $groupJobs->contains('id', $cJob->id)) {
                    $groupJobs->push($cJob);
                }
            }

            if (! $job && $groupJobs->isNotEmpty()) {
                $job = $groupJobs->first();
            }
            $jobId = $job?->id;

            // Target Plan Qty berdasarkan effectiveTargetQty() pada root schedule
            $planQty = $plan->effectiveTargetQty();

            // Akumulasi Actual Qty dari seluruh JobMaster unik dalam group tepat 1 kali
            $actualQty = 0;
            $actualOk = 0;
            $actualRepair = 0;
            $actualReject = 0;
            $runtimeSeconds = 0.0;
            $dandori = 0.0;
            $iqCheck = 0.0;
            $downtime = 0.0;
            $isRunning = false;
            $latestFinishedAt = null;
            $hasAnyDailyInGroup = false;

            $allDtList = [];
            $allRepairList = [];
            $allRejectList = [];

            foreach ($groupJobs as $gJob) {
                $gJobId = $gJob->id;
                $gDailies = $dailyRecordsByJobId->get($gJobId, collect());
                if ($gDailies->isNotEmpty()) {
                    $hasAnyDailyInGroup = true;
                    $actualQty += (int) $gDailies->sum('actual_qty');
                    $actualOk += (int) $gDailies->sum('actual_ok');
                    $actualRepair += (int) $gDailies->sum('actual_repair');
                    $actualReject += (int) $gDailies->sum('actual_reject');
                }

                $runtimeSeconds += $this->resolveRuntimeSeconds($gJob, $gDailies->first(), $sessionsMap);
                $dandori += round($dandoriMinutes[$gJobId] ?? 0, 1);
                $iqCheck += round($firstCheckMinutes[$gJobId] ?? 0, 1);
                $downtime += round($downtimeMinutes[$gJobId] ?? 0, 1);

                if (in_array(strtolower((string) $gJob->status), ['running', 'paused'], true)) {
                    $isRunning = true;
                }
                if ($gJob->finished_at) {
                    $gFin = Carbon::parse($gJob->finished_at);
                    if (! $latestFinishedAt || $gFin->gt($latestFinishedAt)) {
                        $latestFinishedAt = $gFin;
                    }
                }

                if (isset($downtimeItems[$gJobId])) {
                    $allDtList = array_merge($allDtList, $downtimeItems[$gJobId]);
                }
                if (isset($repairItems[$gJobId])) {
                    $allRepairList = array_merge($allRepairList, $repairItems[$gJobId]);
                }
                if (isset($rejectItems[$gJobId])) {
                    $allRejectList = array_merge($allRejectList, $rejectItems[$gJobId]);
                }
            }

            if (! $hasAnyDailyInGroup) {
                // Fallback ke kolom ok/repair/reject pada ProductionPlan hanya jika belum ada DailyProduction sama sekali di group ini
                $actualQty = (int) ($plan->ok ?? 0) + (int) $children->sum('ok');
                $actualOk = (int) ($plan->ok ?? 0) + (int) $children->sum('ok');
                $actualRepair = (int) ($plan->repair ?? 0) + (int) $children->sum('repair');
                $actualReject = (int) ($plan->reject ?? 0) + (int) $children->sum('reject');
            }

            $ctDetik = (float) ($plan->ct_detik ?? 0);
            if ($runtimeSeconds > 0) {
                $pressTime = round($runtimeSeconds / 60, 1);
            } elseif ($ctDetik > 0) {
                $pressTime = round(($actualQty * $ctDetik) / 60, 1);
            } else {
                $pressTime = 0;
            }
            $tpt = round($pressTime + $dandori + $iqCheck + $downtime, 1);

            // GSPH Actual: Total QTY Good / TPT * 60
            $gsphActual = $tpt > 0 ? (int) round(($actualOk / $tpt) * 60) : 0;

            // Plan Finish: Ambil jam selesai efektif dari root plan (termasuk interval setelah istirahat)
            $planFinish = $plan->effectiveFinishTime() ?: $plan->finish_time;

            $actualFinish = $latestFinishedAt ? $latestFinishedAt->format('H:i') : ($plan->act_finish ? substr((string) $plan->act_finish, 0, 5) : '-');

            // Catatan: Jika kuantitas agregat produksi lebih besar dari total kuantitas log manual yang tercatat
            // (misal log manual parsial atau seluruhnya diinput via finalisasi job atau LKH),
            // tambahkan baris selisih agregat tanpa membuat data palsu (Root cause, Countermeasure, PIC, dan Foto tetap '-' dan kosong).
            $loggedRepairTotal = array_sum(array_column($allRepairList, 'qty'));
            if ($actualRepair > $loggedRepairTotal) {
                $unloggedRepairQty = $actualRepair - $loggedRepairTotal;
                $defectLabel = $loggedRepairTotal > 0 ? 'Repair (Sisa Agregat Produksi)' : 'Repair (Agregat Produksi)';
                $allRepairList[] = [
                    'id' => 0,
                    'type' => 'REPAIR',
                    'defect_name' => $defectLabel,
                    'qty' => $unloggedRepairQty,
                    'repair_category' => '-',
                    'area_problem' => '-',
                    'root_cause' => '-',
                    'countermeasure' => '-',
                    'pic' => '-',
                    'time' => '-',
                    'images' => [],
                    'is_aggregate' => true,
                ];
            }

            $loggedRejectTotal = array_sum(array_column($allRejectList, 'qty'));
            if ($actualReject > $loggedRejectTotal) {
                $unloggedRejectQty = $actualReject - $loggedRejectTotal;
                $defectLabel = $loggedRejectTotal > 0 ? 'Reject (Sisa Agregat Produksi)' : 'Reject (Agregat Produksi)';
                $allRejectList[] = [
                    'id' => 0,
                    'type' => 'REJECT',
                    'defect_name' => $defectLabel,
                    'qty' => $unloggedRejectQty,
                    'repair_category' => '-',
                    'area_problem' => '-',
                    'root_cause' => '-',
                    'countermeasure' => '-',
                    'pic' => '-',
                    'time' => '-',
                    'images' => [],
                    'is_aggregate' => true,
                ];
            }

            $rows[] = [
                'no' => $no,
                'job_master_id' => $jobId,
                'is_running' => $isRunning,
                'job_number' => $plan->job_no ?? '-',
                'job_name' => $plan->job_master ?? ($job?->job_name ?? '-'),
                'p1' => (bool) (($plan->p1 ?? false) ?: ($plan->a1 > 0) ?: ($plan->total_mesin >= 1)),
                'p2' => (bool) (($plan->p2 ?? false) ?: ($plan->a2 > 0) ?: ($plan->total_mesin >= 2)),
                'p3' => (bool) (($plan->p3 ?? false) ?: ($plan->a3 > 0) ?: ($plan->total_mesin >= 3)),
                'p4' => (bool) (($plan->p4 ?? false) ?: ($plan->a4 > 0) ?: ($plan->total_mesin >= 4)),
                'plan_qty' => $planQty,
                'good' => $actualOk,
                'repair' => $actualRepair,
                'repair_list' => $allRepairList,
                'reject' => $actualReject,
                'reject_list' => $allRejectList,
                'press_time' => $pressTime,
                'dandori' => $dandori,
                'iq_check' => $iqCheck,
                'downtime' => $downtime,
                'downtime_list' => $allDtList,
                'tpt' => $tpt,
                'plan_finish' => $planFinish ?: '-',
                'actual_finish' => $actualFinish,
                'gsph_plan' => (float) ($plan->gsph_item ?? 0),
                'gsph_actual' => $gsphActual,
            ];
        }

        return $rows;
    }

    private function resolveRuntimeSeconds(?JobMaster $job, ?DailyProduction $daily, Collection $sessionsMap): float
    {
        $seconds = $daily ? (float) ($daily->runtime_seconds ?? 0) : 0.0;
        if ($seconds > 0 || ! $job) {
            return max(0.0, $seconds);
        }

        $status = strtolower((string) $job->status);
        $session = $sessionsMap->get($job->id);

        if ($status === 'running') {
            if ($session && $session->start_time) {
                $seconds = (float) ($session->total_seconds ?? 0);
                if ($session->status === 'running') {
                    $seconds += abs(Carbon::now()->diffInSeconds(Carbon::parse($session->start_time)));
                }
            } elseif ($job->started_at) {
                $seconds = abs(Carbon::now()->diffInSeconds(Carbon::parse($job->started_at)));
            }
        } elseif ($status === 'paused') {
            $seconds = (float) ($session->total_seconds ?? 0);
        } elseif ($job->started_at) {
            $endAt = $job->finished_at ?: Carbon::now();
            $seconds = abs(Carbon::parse($endAt)->diffInSeconds(Carbon::parse($job->started_at)));
        }

        return max(0.0, $seconds);
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
     * Baris job untuk tanggal + shift tertentu (dimemoize agar tidak query berulang per line).
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
