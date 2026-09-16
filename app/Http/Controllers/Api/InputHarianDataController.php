<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\ProductionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InputHarianDataController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $lineFilter = $request->get('line');
        $shift = $request->get('shift');

        $planQuery = ProductionPlan::whereDate('plan_date', $date)
            ->whereIn('row_type', ['job', 'break'])
            ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH']);

        if ($shift && $shift !== 'all') {
            $planQuery->where('shift_name', $shift);
        }

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

        $plans = $planQuery->orderBy('row_no', 'asc')->get();

        $jobNumbers = $plans->map(function($p) {
            $jn = trim($p->job_no ?? '');
            $jm = trim($p->job_master ?? '');
            return $jn ? ($jn . '-' . $p->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $p->id);
        })->toArray();

        $jobMasters = JobMaster::whereIn('job_number', $jobNumbers)
            ->with([
                'dailyProduction' => function ($q) use ($date) {
                    $q->where('work_date', $date);
                },
                'downtimes',
                'dandoris',
            ])
            ->get()
            ->keyBy('job_number');

        $sessionMap = ProductionSession::whereIn(
            'job_master_id',
            $jobMasters->pluck('id')->filter()->unique()->values()->toArray()
        )
            ->whereDate('work_date', $date)
            ->get()
            ->keyBy('job_master_id');

        // Build data structures
        $jobMasterData = [];
        $jobDowntimeHistory = [];
        $runningDowntimes = [];

        foreach ($plans as $plan) {
            $jn = trim($plan->job_no ?? '');
            $jm = trim($plan->job_master ?? '');
            $identifier = $jn ? ($jn . '-' . $plan->id) : ('AUTO-' . \Illuminate\Support\Str::slug($jm) . '-' . $plan->id);
            $jd = $jobMasters->get($identifier);
            if (!$jd) continue;

            $session = $sessionMap->get($jd->id);
            $dailyProd = $jd->dailyProduction;

            $startedAt = null;
            if ($session && $session->start_time) {
                $startedAt = Carbon::parse($session->start_time)->timestamp * 1000;
            } elseif ($jd->started_at) {
                $startedAt = Carbon::parse($jd->started_at)->timestamp * 1000;
            }

            $finishedAt = null;
            if ($session && $session->finish_time) {
                $finishedAt = Carbon::parse($session->finish_time)->timestamp * 1000;
            } elseif ($jd->finished_at) {
                $finishedAt = Carbon::parse($jd->finished_at)->timestamp * 1000;
            }

            $activeDandori = $jd->downtimes
                ->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')
                ->whereNull('finish_time')
                ->first();
            $firstDandori = $jd->downtimes
                ->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')
                ->sortBy('start_time')
                ->first();

            $jobMasterData[(string)$jd->id] = [
                'id' => $jd->id,
                'status' => $jd->status,
                'plan_start' => $jd->plan_start
                    ? Carbon::parse($jd->plan_start)->timestamp * 1000
                    : Carbon::parse($date . ' 07:40')->timestamp * 1000,
                'plan_end' => $jd->plan_end
                    ? Carbon::parse($jd->plan_end)->timestamp * 1000
                    : Carbon::parse($date . ' 10:40')->timestamp * 1000,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'base_seconds' => $dailyProd ? (int)$dailyProd->runtime_seconds : 0,
                'target_qty' => (int)($plan->plan ?? 0),
                'actual_ok' => $dailyProd?->actual_ok ?? 0,
                'actual_repair' => $dailyProd?->actual_repair ?? 0,
                'actual_reject' => $dailyProd?->actual_reject ?? 0,
                'dandori_start' => $activeDandori
                    ? Carbon::parse($activeDandori->start_time)->timestamp * 1000
                    : null,
                'first_dandori_start' => $firstDandori
                    ? Carbon::parse($firstDandori->start_time)->timestamp * 1000
                    : null,
                'tpt' => (float)($plan->tpt ?? 0),
                'line' => $plan->press_name ?? '',
            ];

            $history = [];
            foreach ($jd->downtimes as $dt) {
                $history[] = [
                    'id' => $dt->id,
                    'start' => Carbon::parse($dt->start_time)->timestamp * 1000,
                    'end' => $dt->finish_time
                        ? Carbon::parse($dt->finish_time)->timestamp * 1000
                        : null,
                    'type' => $dt->jenis_downtime,
                    'problem' => $dt->problem,
                ];
            }
            foreach ($jd->dandoris->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check') as $d) {
                if ($d->finish_time) {
                    $history[] = [
                        'id' => 'fc_' . $d->id,
                        'start' => Carbon::parse($d->start_time)->timestamp * 1000,
                        'end' => Carbon::parse($d->finish_time)->timestamp * 1000,
                        'type' => '1st_check',
                        'problem' => null,
                    ];
                }
            }
            $jobDowntimeHistory[(string)$jd->id] = $history;

            foreach ($jd->downtimes->whereNull('finish_time') as $rdt) {
                $dtTypeLower = strtolower($rdt->jenis_downtime);
                $btnType = 'downtime';
                if ($dtTypeLower === 'try out') $btnType = 'tryout';
                elseif ($dtTypeLower === 'downtime') $btnType = 'downtime';
                elseif ($dtTypeLower === 'break time') $btnType = 'break';
                elseif ($dtTypeLower === 'dandori') $btnType = 'dandori';

                $key = $jd->id . '_' . $btnType;
                $runningDowntimes[$key] = [
                    'id' => $rdt->id,
                    'start' => Carbon::parse($rdt->start_time)->toIso8601String(),
                    'jobId' => $jd->id,
                    'btnType' => $btnType,
                    'dtType' => $rdt->jenis_downtime,
                    'problem' => $rdt->problem ?? '',
                ];
            }

            foreach ($jd->dandoris->whereNull('finish_time')->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check') as $fc) {
                $key = $jd->id . '_firstcheck';
                $runningDowntimes[$key] = [
                    'id' => 'fc_' . $fc->id,
                    'start' => Carbon::parse($fc->start_time)->toIso8601String(),
                    'jobId' => $jd->id,
                    'btnType' => 'firstcheck',
                    'dtType' => '1st_check',
                    'problem' => '',
                ];
            }
        }

        // Active job data
        $activeJob = JobMaster::where(DB::raw('LOWER(status)'), 'running');
        if ($lineFilter && strtoupper($lineFilter) !== 'ALL') {
            $normalizedLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $lineFilter)));
            $activeJob->whereRaw("UPPER(line) LIKE ?", ["%{$normalizedLine}%"]);
        }
        $activeJob = $activeJob->with([
            'dailyProduction' => function ($q) use ($date) {
                $q->where('work_date', $date);
            },
            'downtimes',
            'dandoris',
        ])->first();

        $productionLogs = [];
        $lastInputAt = null;
        if ($activeJob) {
            $logs = ProductionLog::where('job_master_id', $activeJob->id)
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            $productionLogs = $logs->map(fn($l) => [
                'id' => $l->id,
                'ok_qty' => $l->ok_qty,
                'repair_qty' => $l->repair_qty,
                'reject_qty' => $l->reject_qty,
                'time' => $l->created_at->format('H:i'),
            ]);
            $lastInputAt = $logs->first()?->created_at?->toIso8601String()
                ?? ($activeJob->started_at
                    ? Carbon::parse($activeJob->started_at)->toIso8601String()
                    : null);
        }

        return response()->json([
            'jobMasterData' => (object)$jobMasterData,
            'jobDowntimeHistory' => (object)$jobDowntimeHistory,
            'runningDowntimes' => (object)$runningDowntimes,
            'activeJob' => $activeJob ? [
                'id' => $activeJob->id,
                'status' => $activeJob->status,
                'started_at' => $activeJob->started_at
                    ? Carbon::parse($activeJob->started_at)->timestamp * 1000
                    : null,
            ] : null,
            'productionLogs' => $productionLogs,
            'lastInputAt' => $lastInputAt,
            'config' => [
                'currentActiveId' => $activeJob?->id,
                'currentStatus' => $activeJob?->status ?? 'none',
                'lastInputAt' => $lastInputAt,
            ],
        ]);
    }
}
