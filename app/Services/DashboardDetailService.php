<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
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
        $workDate = $shift === 2 ? Carbon::parse($date)->subDay()->toDateString() : $date;

        $normalizedPress = strtoupper(preg_replace('/^(PRESS|LINE)\s*/i', '', $lineName));

        $plans = ProductionPlan::where(function ($q) use ($date, $workDate) {
                $q->where('plan_date', $date)->orWhere('plan_date', $workDate);
            })
            ->whereRaw("REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') = ?", [$normalizedPress])
            ->where('shift_name', 'like', $planShiftText . '%')
            ->where('row_type', 'job')
            ->where(function ($q) {
                $q->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                  ->orWhereNull('job_no');
            })
            ->orderBy('row_no')
            ->get();

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

        $jobIds = $jobMasters->pluck('id');

        $dailyRecords = DailyProduction::where('work_date', $workDate)
            ->whereIn('job_master_id', $jobIds)
            ->get()
            ->keyBy('job_master_id');

        $dandoriMinutes = Dandori::whereIn('next_job_id', $jobIds)
            ->whereNotNull('finish_time')
            ->selectRaw('next_job_id, COALESCE(SUM(duration_minutes), 0) as total')
            ->groupBy('next_job_id')
            ->pluck('total', 'next_job_id');

        $qcheckModels = QCheck::whereIn('job_master_id', $jobIds)
            ->whereNotNull('start_time')
            ->whereNotNull('finish_time')
            ->get();

        $qcheckMinutes = [];
        foreach ($qcheckModels as $qc) {
            $qcheckMinutes[$qc->job_master_id] = ($qcheckMinutes[$qc->job_master_id] ?? 0) + $qc->duration;
        }

        $downtimeData = Downtime::whereIn('job_master_id', $jobIds)
            ->whereNotNull('duration_seconds')
            ->get();

        $downtimeMinutes = [];
        foreach ($downtimeData as $dt) {
            $jenis = strtolower($dt->jenis_downtime ?? '');
            if (in_array($jenis, ['dandori', 'idle time', 'idle', 'break time'])) {
                continue;
            }
            $downtimeMinutes[$dt->job_master_id] = ($downtimeMinutes[$dt->job_master_id] ?? 0) + ($dt->duration_seconds / 60);
        }

        $rows = [];
        $no = 0;

        foreach ($plans as $plan) {
            $no++;
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);

            $job = $jobMasters->get($identifier);
            $daily = $job ? ($dailyRecords->get($job->id) ?? null) : null;

            $actualQty = $daily ? (int) $daily->actual_qty : 0;
            $actualOk = $daily ? (int) $daily->actual_ok : 0;
            $actualRepair = $daily ? (int) ($daily->actual_repair ?? 0) : 0;
            $actualReject = $daily ? (int) ($daily->actual_reject ?? 0) : 0;

            $ctDetik = (float) ($plan->ct_detik ?? 0);
            $pressTime = $ctDetik > 0 ? round(($actualQty * $ctDetik) / 60, 1) : 0;

            $jobId = $job?->id;
            $dandori = $jobId ? (float) ($dandoriMinutes->get($jobId, 0)) : 0;
            $iqCheck = $jobId ? round($qcheckMinutes[$jobId] ?? 0, 1) : 0;
            $downtime = $jobId ? round($downtimeMinutes[$jobId] ?? 0, 1) : 0;

            $tpt = round($pressTime + $dandori + $iqCheck + $downtime, 1);

            $planFinish = $plan->finish_time;
            $actualFinish = $job?->finished_at ? Carbon::parse($job->finished_at)->format('H:i') : '-';

            $rows[] = [
                'no'               => $no,
                'job_number'       => $plan->job_no ?? '-',
                'p1'               => (bool) (($plan->p1 ?? false) ?: ($plan->a1 > 0) ?: ($plan->total_mesin >= 1)),
                'p2'               => (bool) (($plan->p2 ?? false) ?: ($plan->a2 > 0) ?: ($plan->total_mesin >= 2)),
                'p3'               => (bool) (($plan->p3 ?? false) ?: ($plan->a3 > 0) ?: ($plan->total_mesin >= 3)),
                'p4'               => (bool) (($plan->p4 ?? false) ?: ($plan->a4 > 0) ?: ($plan->total_mesin >= 4)),
                'plan_qty'         => (int) ($plan->plan ?? 0),
                'good'             => $actualOk,
                'repair'           => $actualRepair,
                'reject'           => $actualReject,
                'press_time'       => $pressTime,
                'dandori'          => $dandori,
                'iq_check'         => $iqCheck,
                'downtime'         => $downtime,
                'tpt'              => $tpt,
                'plan_finish'      => $planFinish ?: '-',
                'actual_finish'    => $actualFinish,
            ];
        }

        return $rows;
    }
}
