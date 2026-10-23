<?php

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\QCheck;
use App\Models\User;
use App\Services\DashboardDetailService;
use App\Services\DashboardRealtimeService;
use App\Services\LineStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    DashboardRealtimeService::flushCache();
    DashboardDetailService::flushCache();

    // Seed active lines including extra lines (E, F, K) to verify Monitoring TV only renders Press A/B/C/D
    LineMaster::updateOrCreate(['line_code' => 'PA'], ['line_name' => 'Line A', 'status' => 'active']);
    LineMaster::updateOrCreate(['line_code' => 'PB'], ['line_name' => 'Line B', 'status' => 'active']);
    LineMaster::updateOrCreate(['line_code' => 'PC'], ['line_name' => 'Line C', 'status' => 'active', 'production_end' => '22:00']);
    LineMaster::updateOrCreate(['line_code' => 'PD'], ['line_name' => 'Line D', 'status' => 'active', 'production_start' => '12:45']);
    LineMaster::updateOrCreate(['line_code' => 'PE'], ['line_name' => 'Line E', 'status' => 'active']);
    LineMaster::updateOrCreate(['line_code' => 'PK'], ['line_name' => 'Line K', 'status' => 'active']);
});

it('preserves Press A reference baseline logic across KPI, Detail, and Status', function () {
    $date = '2026-10-23';
    Carbon::setTestNow(Carbon::parse($date.' 10:30:00'));

    $lineA = LineMaster::where('line_code', 'PA')->first();

    // Item 1: Finished job on Press A
    $plan1 = ProductionPlan::create([
        'line_master_id' => $lineA->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'GT-1001',
        'job_master' => 'PART A1',
        'plan' => 500,
        'ct_detik' => 6.0,
        'process_time' => 50,
        'dct' => 15,
        'tpt' => 65,
        'gsph_item' => 462,
        'start_time' => '07:30',
        'finish_time' => '08:35',
        'status' => 'completed',
    ]);

    $job1 = JobMaster::create([
        'job_number' => 'GT-1001-'.$plan1->id,
        'job_name' => 'PART A1',
        'line' => 'PRESS A',
        'target_qty' => 500,
        'sequence_no' => 1,
        'status' => 'completed',
        'started_at' => Carbon::parse($date.' 07:45:00'),
        'finished_at' => Carbon::parse($date.' 08:35:00'),
    ]);

    DailyProduction::create([
        'job_master_id' => $job1->id,
        'work_date' => $date,
        'line' => 'PRESS A',
        'shift' => 'Shift Pagi',
        'actual_ok' => 500,
        'actual_qty' => 500,
        'actual_repair' => 5,
        'actual_reject' => 2,
        'runtime_seconds' => 3000, // 50m
        'downtime_seconds' => 0,
    ]);

    Dandori::create([
        'next_job_id' => $job1->id,
        'line' => 'PRESS A',
        'work_date' => $date,
        'activity' => 'DANDORI',
        'jenis_dandori' => 'setup',
        'start_time' => Carbon::parse($date.' 07:30:00'),
        'finish_time' => Carbon::parse($date.' 07:40:00'),
        'duration_minutes' => 10,
    ]);

    Dandori::create([
        'next_job_id' => $job1->id,
        'line' => 'PRESS A',
        'work_date' => $date,
        'activity' => '1ST CHECK',
        'jenis_dandori' => '1st_check',
        'start_time' => Carbon::parse($date.' 07:40:00'),
        'finish_time' => Carbon::parse($date.' 07:45:00'),
        'duration_minutes' => 5,
    ]);

    // Item 2: Running job on Press A
    $plan2 = ProductionPlan::create([
        'line_master_id' => $lineA->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'row_no' => 2,
        'row_type' => 'job',
        'job_no' => 'GT-1002',
        'job_master' => 'PART A2',
        'plan' => 400,
        'ct_detik' => 6.0,
        'process_time' => 40,
        'dct' => 15,
        'tpt' => 55,
        'gsph_item' => 436,
        'start_time' => '08:35',
        'finish_time' => '09:30',
        'status' => 'running',
    ]);

    $job2 = JobMaster::create([
        'job_number' => 'GT-1002-'.$plan2->id,
        'job_name' => 'PART A2',
        'line' => 'PRESS A',
        'target_qty' => 400,
        'sequence_no' => 2,
        'status' => 'running',
        'started_at' => Carbon::parse($date.' 08:50:00'),
    ]);

    ProductionSession::create([
        'job_master_id' => $job2->id,
        'work_date' => $date,
        'shift' => 'Shift Pagi',
        'status' => 'running',
        'start_time' => Carbon::parse($date.' 10:10:00'),
        'total_seconds' => 0,
    ]);

    DailyProduction::create([
        'job_master_id' => $job2->id,
        'work_date' => $date,
        'line' => 'PRESS A',
        'shift' => 'Shift Pagi',
        'actual_ok' => 200,
        'actual_qty' => 200,
        'actual_repair' => 3,
        'actual_reject' => 1,
        'runtime_seconds' => 1200, // 20m
        'downtime_seconds' => 300,
    ]);

    Downtime::create([
        'job_master_id' => $job2->id,
        'jenis_downtime' => 'dies',
        'problem' => 'Sensor Dies',
        'start_time' => Carbon::parse($date.' 09:00:00'),
        'finish_time' => Carbon::parse($date.' 09:05:00'),
        'duration_seconds' => 300, // 5m
    ]);

    $realtime = app(DashboardRealtimeService::class)->getLineMetrics('Line A', $date, 1);
    $detail = app(DashboardDetailService::class)->getLineDetail('Line A', $date, 1);
    $statuses = LineStatusService::getStatuses(1, $date);

    $kpiByDesc = collect($realtime['kpi'])->keyBy('desc');

    expect($realtime['meta']['job'])->toBe('GT-1002')
        ->and($realtime['meta']['jobPlan'])->toBe('2')
        ->and($realtime['meta']['jobActual'])->toBe('1/2')
        ->and($realtime['meta']['stroke'])->toBe('711') // (500+200) + (5+3) + (2+1)
        ->and($realtime['meta']['currStroke'])->toBe('204') // 200 + 3 + 1
        ->and($kpiByDesc['QTY']['plan'])->toBe('900')
        ->and($kpiByDesc['QTY']['current'])->toBe('200')
        ->and($kpiByDesc['QTY']['actual'])->toBe('700')
        ->and($kpiByDesc['REPAIR']['current'])->toBe('3 pcs')
        ->and($kpiByDesc['REPAIR']['actual'])->toBe('8 pcs')
        ->and($kpiByDesc['REJECT']['current'])->toBe('1 pcs')
        ->and($kpiByDesc['REJECT']['actual'])->toBe('3 pcs')
        ->and($kpiByDesc['TOTAL_DT']['current'])->toBe('5 m')
        ->and($kpiByDesc['TOTAL_DT']['actual'])->toBe('5 m')
        ->and($kpiByDesc['DIES_T']['current'])->toBe('5 m')
        ->and($kpiByDesc['DIES_T']['actual'])->toBe('5 m')
        ->and($statuses['Line A']['label'])->toBe('PRODUCTION')
        ->and($detail)->toHaveCount(2)
        ->and($detail[0]['good'])->toBe(500)
        ->and($detail[0]['press_time'])->toBe(50.0)
        ->and($detail[0]['dandori'])->toBe(10.0)
        ->and($detail[0]['iq_check'])->toBe(5.0)
        ->and($detail[0]['tpt'])->toBe(65.0)
        ->and($detail[1]['good'])->toBe(200)
        ->and($detail[1]['downtime'])->toBe(5.0);
});

it('ensures Press D is 100% consistent between Input Harian and Monitoring TV across all parameters including paused jobs, QCheck, open downtime, and break splits', function () {
    $date = '2026-10-23';
    Carbon::setTestNow(Carbon::parse($date.' 14:30:00'));

    $lineD = LineMaster::where('line_code', 'PD')->first();

    // Simulate an older revision on the same shift to verify Monitoring TV and Input Harian both pick the latest revision
    ProductionPlan::create([
        'line_master_id' => $lineD->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS D',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'OLD-REV-D',
        'job_master' => 'PART OLD D',
        'plan' => 999,
        'ct_detik' => 5.0,
        'updated_at' => Carbon::parse($date.' 07:00:00'),
    ]);

    // Latest revision on Press D: Item 1 split across breaktime (parent + child), finished
    $parentPlanD1 = ProductionPlan::create([
        'line_master_id' => $lineD->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi (Rev)',
        'press_name' => 'PRESS D',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'GD-4001',
        'job_master' => 'PART D1',
        'plan' => 180,
        'original_plan' => 300,
        'ct_detik' => 6.0,
        'gsph_item' => 450,
        'start_time' => '12:45',
        'finish_time' => '13:15',
        'status' => 'completed',
        'updated_at' => Carbon::parse($date.' 12:00:00'),
    ]);

    $childPlanD1 = ProductionPlan::create([
        'line_master_id' => $lineD->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi (Rev)',
        'press_name' => 'PRESS D',
        'parent_job_id' => $parentPlanD1->id,
        'row_no' => 2,
        'row_type' => 'job',
        'job_no' => 'GD-4001',
        'job_master' => 'PART D1',
        'plan' => 120,
        'ct_detik' => 6.0,
        'gsph_item' => 450,
        'start_time' => '13:30',
        'finish_time' => '13:50',
        'status' => 'completed',
        'updated_at' => Carbon::parse($date.' 12:00:00'),
    ]);

    $jobD1 = JobMaster::create([
        'job_number' => 'GD-4001-'.$parentPlanD1->id,
        'job_name' => 'PART D1',
        'line' => 'PRESS D', // Stored as 'PRESS D' while LineMaster is 'Line D'
        'target_qty' => 300,
        'sequence_no' => 1,
        'status' => 'completed',
        'started_at' => Carbon::parse($date.' 12:55:00'),
        'finished_at' => Carbon::parse($date.' 13:50:00'),
    ]);

    DailyProduction::create([
        'job_master_id' => $jobD1->id,
        'work_date' => $date,
        'line' => 'PRESS D',
        'shift' => 'Shift Pagi',
        'actual_ok' => 300,
        'actual_qty' => 300,
        'actual_repair' => 4,
        'actual_reject' => 2,
        'runtime_seconds' => 1800, // 30m
    ]);

    Dandori::create([
        'next_job_id' => $jobD1->id,
        'line' => 'PRESS D',
        'work_date' => $date,
        'activity' => 'DANDORI',
        'jenis_dandori' => 'setup',
        'start_time' => Carbon::parse($date.' 12:45:00'),
        'finish_time' => Carbon::parse($date.' 12:53:00'),
        'duration_minutes' => 8,
    ]);

    QCheck::create([
        'job_master_id' => $jobD1->id,
        'line' => 'PRESS D',
        'shift' => 'Shift Pagi',
        'start_time' => Carbon::parse($date.' 12:53:00'),
        'finish_time' => Carbon::parse($date.' 12:55:00'),
        'duration' => 2,
    ]);

    // Item 2 on Press D: Currently PAUSED due to an open Machine Downtime
    $planD2 = ProductionPlan::create([
        'line_master_id' => $lineD->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi (Rev)',
        'press_name' => 'PRESS D',
        'row_no' => 3,
        'row_type' => 'job',
        'job_no' => 'GD-4002',
        'job_master' => 'PART D2',
        'plan' => 250,
        'ct_detik' => 6.0,
        'gsph_item' => 420,
        'start_time' => '13:50',
        'finish_time' => '14:40',
        'status' => 'paused',
        'updated_at' => Carbon::parse($date.' 12:00:00'),
    ]);

    $jobD2 = JobMaster::create([
        'job_number' => 'GD-4002-'.$planD2->id,
        'job_name' => 'PART D2',
        'line' => 'PRESS D',
        'target_qty' => 250,
        'sequence_no' => 3,
        'status' => 'paused',
        'started_at' => Carbon::parse($date.' 14:00:00'),
    ]);

    DailyProduction::create([
        'job_master_id' => $jobD2->id,
        'work_date' => $date,
        'line' => 'PRESS D',
        'shift' => 'Shift Pagi',
        'actual_ok' => 120,
        'actual_qty' => 120,
        'actual_repair' => 2,
        'actual_reject' => 1,
        'runtime_seconds' => 720, // 12m
    ]);

    Dandori::create([
        'next_job_id' => $jobD2->id,
        'line' => 'PRESS D',
        'work_date' => $date,
        'activity' => 'DANDORI',
        'jenis_dandori' => 'setup',
        'start_time' => Carbon::parse($date.' 13:50:00'),
        'finish_time' => Carbon::parse($date.' 13:57:00'),
        'duration_minutes' => 7,
    ]);

    QCheck::create([
        'job_master_id' => $jobD2->id,
        'line' => 'PRESS D',
        'shift' => 'Shift Pagi',
        'start_time' => Carbon::parse($date.' 13:57:00'),
        'finish_time' => Carbon::parse($date.' 14:00:00'),
        'duration' => 3,
    ]);

    // Open machine downtime for 6 minutes (14:24 -> 14:30 now, duration_seconds defaults to 0 while open)
    Downtime::create([
        'job_master_id' => $jobD2->id,
        'jenis_downtime' => 'machine',
        'problem' => 'Clutch Slip',
        'start_time' => Carbon::parse($date.' 14:24:00'),
        'finish_time' => null,
        'duration_seconds' => 0,
    ]);

    $realtime = app(DashboardRealtimeService::class)->getLineMetrics('Line D', $date, 1);
    $detail = app(DashboardDetailService::class)->getLineDetail('Line D', $date, 1);
    $statuses = LineStatusService::getStatuses(1, $date);

    $kpiByDesc = collect($realtime['kpi'])->keyBy('desc');

    // Verify KPI Left Table & Meta match Input Harian
    expect($realtime['meta']['job'])->toBe('GD-4002')
        ->and($realtime['meta']['jobPlan'])->toBe('2') // 2 unique base jobs (split merged)
        ->and($realtime['meta']['jobActual'])->toBe('1/2') // 1 base job completed
        ->and($realtime['meta']['stroke'])->toBe('429') // (300+120) + (4+2) + (2+1) = 429
        ->and($realtime['meta']['currStroke'])->toBe('123') // 120 + 2 + 1
        ->and($kpiByDesc['QTY']['plan'])->toBe('550') // 300 + 250 (old revision 999 excluded!)
        ->and($kpiByDesc['QTY']['current'])->toBe('120')
        ->and($kpiByDesc['QTY']['actual'])->toBe('420')
        ->and($kpiByDesc['REPAIR']['current'])->toBe('2 pcs')
        ->and($kpiByDesc['REPAIR']['actual'])->toBe('6 pcs')
        ->and($kpiByDesc['REJECT']['current'])->toBe('1 pcs')
        ->and($kpiByDesc['REJECT']['actual'])->toBe('3 pcs')
        ->and($kpiByDesc['TOTAL_DT']['current'])->toBe('6 m')
        ->and($kpiByDesc['TOTAL_DT']['actual'])->toBe('6 m')
        ->and($kpiByDesc['MACH_T']['current'])->toBe('6 m')
        ->and($kpiByDesc['MACH_T']['actual'])->toBe('6 m')
        // GSPH Current for GD-4002: 120 / (12m press + 7m dandori + 3m qcheck + 6m dt = 28m) * 60 = 257
        ->and($kpiByDesc['GSPH']['current'])->toBe('257')
        // GSPH Actual cumulative (GD-4001 + GD-4002): 420 / ((30+12)m press + (8+2+7+3)m dandori/qcheck + 6m dt = 68m) * 60 = 371
        ->and($kpiByDesc['GSPH']['actual'])->toBe('371')
        // Status for Line D must be DOWNTIME (normalized from 'PRESS D' to 'Line D')
        ->and($statuses['Line D']['label'])->toBe('DOWNTIME');

    // Verify Detail Right Table matches Input Harian & Left Table
    expect($detail)->toHaveCount(2)
        ->and($detail[0]['job_number'])->toBe('GD-4001')
        ->and($detail[0]['plan_qty'])->toBe(300)
        ->and($detail[0]['good'])->toBe(300)
        ->and($detail[0]['repair'])->toBe(4)
        ->and($detail[0]['reject'])->toBe(2)
        ->and($detail[0]['press_time'])->toBe(30.0)
        ->and($detail[0]['dandori'])->toBe(8.0)
        ->and($detail[0]['iq_check'])->toBe(2.0)
        ->and($detail[0]['downtime'])->toBe(0.0)
        ->and($detail[0]['tpt'])->toBe(40.0)
        ->and($detail[0]['plan_finish'])->toBe('13:50')
        ->and($detail[0]['actual_finish'])->toBe('13:50')
        ->and($detail[0]['gsph_actual'])->toBe(450)
        ->and($detail[1]['job_number'])->toBe('GD-4002')
        ->and($detail[1]['is_running'])->toBeTrue()
        ->and($detail[1]['plan_qty'])->toBe(250)
        ->and($detail[1]['good'])->toBe(120)
        ->and($detail[1]['repair'])->toBe(2)
        ->and($detail[1]['reject'])->toBe(1)
        ->and($detail[1]['press_time'])->toBe(12.0)
        ->and($detail[1]['dandori'])->toBe(7.0)
        ->and($detail[1]['iq_check'])->toBe(3.0)
        ->and($detail[1]['downtime'])->toBe(6.0)
        ->and($detail[1]['tpt'])->toBe(28.0)
        ->and($detail[1]['gsph_actual'])->toBe(257);
});

it('ensures Press B and Press C are consistent across Shift Pagi and Shift Malam including after midnight (00:00 - 07:30)', function () {
    $workDate = '2026-10-23';
    // Simulate Shift Malam at 01:30 AM on the next calendar day (work_date is still 2026-10-23)
    Carbon::setTestNow(Carbon::parse('2026-10-24 01:30:00'));

    $lineB = LineMaster::where('line_code', 'PB')->first();
    $lineC = LineMaster::where('line_code', 'PC')->first();

    // Also create a Shift Pagi (REV 2) plan on the same date to ensure Shift Malam does NOT accidentally match "%2%" from "Shift Pagi (REV 2)"
    ProductionPlan::create([
        'line_master_id' => $lineB->id,
        'plan_date' => $workDate,
        'shift_name' => 'Shift Pagi (REV 2)',
        'press_name' => 'PRESS B',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'MORNING-LEAK-B',
        'job_master' => 'MORNING PART B',
        'plan' => 888,
        'ct_detik' => 6.0,
    ]);

    // Press B Shift Malam running in 1ST CHECK state
    $planB = ProductionPlan::create([
        'line_master_id' => $lineB->id,
        'plan_date' => $workDate,
        'shift_name' => 'Shift Malam',
        'press_name' => 'PRESS B',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'GB-2001',
        'job_master' => 'PART B1',
        'plan' => 350,
        'ct_detik' => 6.0,
        'gsph_item' => 400,
        'start_time' => '21:30',
        'finish_time' => '22:30',
        'status' => 'running',
    ]);

    $jobB = JobMaster::create([
        'job_number' => 'GB-2001-'.$planB->id,
        'job_name' => 'PART B1',
        'line' => 'PRESS B',
        'target_qty' => 350,
        'sequence_no' => 1,
        'status' => 'running',
        'started_at' => Carbon::parse($workDate.' 21:45:00'),
    ]);

    DailyProduction::create([
        'job_master_id' => $jobB->id,
        'work_date' => $workDate,
        'line' => 'PRESS B',
        'shift' => 'Shift Malam',
        'actual_ok' => 210,
        'actual_qty' => 210,
        'actual_repair' => 2,
        'actual_reject' => 0,
        'runtime_seconds' => 1260, // 21m
    ]);

    // Open QCheck on Press B -> Status must be 1ST CHECK and job must NOT be auto-closed after midnight!
    QCheck::create([
        'job_master_id' => $jobB->id,
        'line' => 'PRESS B',
        'shift' => 'Shift Malam',
        'start_time' => Carbon::parse('2026-10-24 01:20:00'),
        'finish_time' => null,
    ]);

    // Press C Shift Malam in BREAKTIME state + Material Downtime logged earlier in Shift Malam
    $planC = ProductionPlan::create([
        'line_master_id' => $lineC->id,
        'plan_date' => $workDate,
        'shift_name' => 'Shift Malam',
        'press_name' => 'PRESS C',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'GC-3001',
        'job_master' => 'PART C1',
        'plan' => 400,
        'ct_detik' => 6.0,
        'gsph_item' => 410,
        'start_time' => '21:30',
        'finish_time' => '22:40',
        'status' => 'paused',
    ]);

    $jobC = JobMaster::create([
        'job_number' => 'GC-3001-'.$planC->id,
        'job_name' => 'PART C1',
        'line' => 'PRESS C',
        'target_qty' => 400,
        'sequence_no' => 1,
        'status' => 'paused',
        'started_at' => Carbon::parse($workDate.' 21:40:00'),
    ]);

    DailyProduction::create([
        'job_master_id' => $jobC->id,
        'work_date' => $workDate,
        'line' => 'PRESS C',
        'shift' => 'Shift Malam',
        'actual_ok' => 180,
        'actual_qty' => 180,
        'actual_repair' => 1,
        'actual_reject' => 3,
        'runtime_seconds' => 1080, // 18m
    ]);

    // Material downtime on 2026-10-23 22:15 (must be captured by Shift 2 window 2026-10-23 21:00 -> 2026-10-24 07:30)
    Downtime::create([
        'job_master_id' => $jobC->id,
        'jenis_downtime' => 'material',
        'problem' => 'Coil Habis',
        'start_time' => Carbon::parse($workDate.' 22:15:00'),
        'finish_time' => Carbon::parse($workDate.' 22:25:00'),
        'duration_seconds' => 600, // 10m
    ]);

    // Active break time downtime on Press C (excluded from TOTAL_DT, sets status to BREAKTIME)
    Downtime::create([
        'job_master_id' => $jobC->id,
        'jenis_downtime' => 'break time',
        'problem' => 'Istirahat Malam',
        'start_time' => Carbon::parse('2026-10-24 00:00:00'),
        'finish_time' => null,
    ]);

    $statuses = LineStatusService::getStatuses(2, $workDate);

    // Ensure Shift Malam jobs were NOT auto-closed after midnight
    expect($jobB->fresh()->status)->toBe('running')
        ->and($jobC->fresh()->status)->toBe('paused')
        ->and($statuses['Line B']['label'])->toBe('1ST CHECK')
        ->and($statuses['Line C']['label'])->toBe('BREAKTIME');

    $realtimeB = app(DashboardRealtimeService::class)->getLineMetrics('Line B', $workDate, 2);
    $detailB = app(DashboardDetailService::class)->getLineDetail('Line B', $workDate, 2);
    $kpiB = collect($realtimeB['kpi'])->keyBy('desc');

    expect($realtimeB['meta']['job'])->toBe('GB-2001')
        ->and($kpiB['QTY']['plan'])->toBe('350') // MORNING-LEAK-B (888) excluded!
        ->and($kpiB['QTY']['current'])->toBe('210')
        ->and($kpiB['QTY']['actual'])->toBe('210')
        ->and($detailB)->toHaveCount(1)
        ->and($detailB[0]['good'])->toBe(210);

    $realtimeC = app(DashboardRealtimeService::class)->getLineMetrics('Line C', $workDate, 2);
    $detailC = app(DashboardDetailService::class)->getLineDetail('Line C', $workDate, 2);
    $kpiC = collect($realtimeC['kpi'])->keyBy('desc');

    expect($realtimeC['meta']['job'])->toBe('GC-3001')
        ->and($kpiC['QTY']['plan'])->toBe('400')
        ->and($kpiC['QTY']['current'])->toBe('180')
        ->and($kpiC['QTY']['actual'])->toBe('180')
        ->and($kpiC['TOTAL_DT']['current'])->toBe('10 m') // 10m material dt included, break time excluded
        ->and($kpiC['TOTAL_DT']['actual'])->toBe('10 m')
        ->and($kpiC['MAT_T']['current'])->toBe('10 m')
        ->and($kpiC['MAT_T']['actual'])->toBe('10 m')
        ->and($detailC)->toHaveCount(1)
        ->and($detailC[0]['downtime'])->toBe(10.0)
        ->and($detailC[0]['tpt'])->toBe(28.0); // 18m press + 10m downtime
});

it('verifies Filter Semua vs individual Press A, B, C, D endpoints return identical consistent data and only Press A/B/C/D lines', function () {
    $date = '2026-10-23';
    Carbon::setTestNow(Carbon::parse($date.' 15:00:00'));

    $user = User::create([
        'name' => 'Supervisor Test',
        'nrp' => '9991',
        'role' => 'superadmin',
        'password' => bcrypt('password'),
    ]);

    foreach (['A', 'B', 'C', 'D'] as $idx => $letter) {
        $lm = LineMaster::where('line_code', 'P'.$letter)->first();
        $plan = ProductionPlan::create([
            'line_master_id' => $lm->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS '.$letter,
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => "JOB-{$letter}-01",
            'job_master' => "PART {$letter}",
            'plan' => ($idx + 1) * 100,
            'ct_detik' => 6.0,
            'gsph_item' => 400 + ($idx * 10),
            'start_time' => '13:00',
            'finish_time' => '14:00',
            'status' => 'running',
        ]);

        $jm = JobMaster::create([
            'job_number' => "JOB-{$letter}-01-{$plan->id}",
            'job_name' => "PART {$letter}",
            'line' => 'PRESS '.$letter,
            'target_qty' => ($idx + 1) * 100,
            'sequence_no' => 1,
            'status' => 'running',
            'started_at' => Carbon::parse($date.' 13:10:00'),
        ]);

        DailyProduction::create([
            'job_master_id' => $jm->id,
            'work_date' => $date,
            'line' => 'PRESS '.$letter,
            'shift' => 'Shift Pagi',
            'actual_ok' => ($idx + 1) * 50,
            'actual_qty' => ($idx + 1) * 50,
            'actual_repair' => $idx + 1,
            'actual_reject' => $idx,
            'runtime_seconds' => ($idx + 1) * 300,
        ]);
    }

    // 1. Monitor View only passes the 4 Press lines (Line A, Line B, Line C, Line D) in canonical order
    $monitorResp = $this->actingAs($user)->get(route('supervisor.monitor'));
    $monitorResp->assertOk();
    $viewLines = $monitorResp->viewData('lines')->values()->all();
    expect($viewLines)->toBe(['Line A', 'Line B', 'Line C', 'Line D']);

    // 2. All-lines API payload
    $allApi = $this->actingAs($user)->getJson(route('supervisor.dashboard.api', ['date' => $date, 'shift' => 1]))->assertOk()->json();
    $allDetail = $this->actingAs($user)->getJson(route('supervisor.dashboard.detail', ['date' => $date, 'shift' => 1]))->assertOk()->json();

    expect(array_keys($allApi['line_kpi']))->toBe(['Line A', 'Line B', 'Line C', 'Line D']);

    // 3. Individual line filter ('A', 'B', 'C', 'D', 'PRESS D', 'Line D') matches All-lines data 100%
    foreach (['A', 'B', 'C', 'D'] as $letter) {
        $lineKey = 'Line '.$letter;
        $singleApi = $this->actingAs($user)->getJson(route('supervisor.dashboard.api', [
            'date' => $date,
            'shift' => 1,
            'line' => $letter,
        ]))->assertOk()->json();

        $singleDetail = $this->actingAs($user)->getJson(route('supervisor.dashboard.detail', [
            'date' => $date,
            'shift' => 1,
            'line' => 'PRESS '.$letter,
        ]))->assertOk()->json();

        expect($singleApi['line_kpi'][$lineKey])->toBe($allApi['line_kpi'][$lineKey])
            ->and($singleApi['line_meta'][$lineKey])->toBe($allApi['line_meta'][$lineKey])
            ->and($singleDetail['detail'][$lineKey])->toBe($allDetail['detail'][$lineKey]);
    }
});
