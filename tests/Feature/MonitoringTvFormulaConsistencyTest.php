<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\QCheck;
use App\Models\ScheduleBackup;
use App\Services\DashboardDetailService;
use App\Services\DashboardRealtimeService;
use App\Services\ScheduleBackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTvFormulaConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DashboardRealtimeService::flushCache();
        DashboardDetailService::flushCache();

        LineMaster::updateOrCreate(['line_code' => 'PA'], ['line_name' => 'Line A', 'status' => 'active']);
        LineMaster::updateOrCreate(['line_code' => 'PB'], ['line_name' => 'Line B', 'status' => 'active']);
        LineMaster::updateOrCreate(['line_code' => 'PC'], ['line_name' => 'Line C', 'status' => 'active']);
        LineMaster::updateOrCreate(['line_code' => 'PD'], ['line_name' => 'Line D', 'status' => 'active']);
    }

    public function test_stroke_calculation_multiplies_machine_count_per_item()
    {
        $date = '2026-10-28';
        Carbon::setTestNow(Carbon::parse($date.' 10:00:00'));

        $lineB = LineMaster::where('line_code', 'PB')->first();

        // Item 1: Uses 4 machines (e.g. B-1..B-4 all active or total_mesin = 4)
        $plan1 = ProductionPlan::create([
            'line_master_id' => $lineB->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS B',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'RCS-018',
            'job_master' => 'RCS-018',
            'plan' => 500,
            'total_mesin' => 4,
            'a1' => 55,
            'a2' => 55,
            'a3' => 55,
            'a4' => 55,
            'gsph_item' => 500,
            'status' => 'completed',
        ]);

        $job1 = JobMaster::create([
            'job_number' => "RCS-018-{$plan1->id}",
            'job_name' => 'RCS-018',
            'line' => 'PRESS B',
            'target_qty' => 500,
            'sequence_no' => 1,
            'status' => 'completed',
        ]);

        DailyProduction::create([
            'job_master_id' => $job1->id,
            'work_date' => $date,
            'line' => 'PRESS B',
            'shift' => 'Shift Pagi',
            'actual_ok' => 500,
            'actual_qty' => 500,
            'actual_repair' => 10,
            'actual_reject' => 5,
            'runtime_seconds' => 3000,
        ]);

        // Item 2: Uses only 2 machines (e.g. B-1 and B-2 active, total_mesin = 2)
        $plan2 = ProductionPlan::create([
            'line_master_id' => $lineB->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS B',
            'row_no' => 2,
            'row_type' => 'job',
            'job_no' => '61621-BZ',
            'job_master' => '61621-BZ-150',
            'plan' => 600,
            'total_mesin' => 2,
            'a1' => 65,
            'a2' => 65,
            'a3' => 0,
            'a4' => 0,
            'gsph_item' => 600,
            'status' => 'running',
        ]);

        $job2 = JobMaster::create([
            'job_number' => "61621-BZ-{$plan2->id}",
            'job_name' => '61621-BZ-150',
            'line' => 'PRESS B',
            'target_qty' => 600,
            'sequence_no' => 2,
            'status' => 'running',
            'started_at' => Carbon::parse($date.' 09:00:00'),
        ]);

        DailyProduction::create([
            'job_master_id' => $job2->id,
            'work_date' => $date,
            'line' => 'PRESS B',
            'shift' => 'Shift Pagi',
            'actual_ok' => 200,
            'actual_qty' => 200,
            'actual_repair' => 4,
            'actual_reject' => 2,
            'runtime_seconds' => 1200,
        ]);

        $realtime = app(DashboardRealtimeService::class)->getLineMetrics('Line B', $date, 1);

        // Verification:
        // Plan Stroke: (500 plan * 4 machines) + (600 plan * 2 machines) = 2000 + 1200 = 3200
        $this->assertEquals('3200', $realtime['meta']['strokePlan']);

        // Actual Stroke:
        // Item 1: (500 + 10 + 5) * 4 machines = 515 * 4 = 2060
        // Item 2: (200 + 4 + 2) * 2 machines = 206 * 2 = 412
        // Total Actual Stroke = 2060 + 412 = 2472
        $this->assertEquals('2472', $realtime['meta']['stroke']);

        // Current Stroke (Item 2 running): (200 + 4 + 2) * 2 machines = 412
        $this->assertEquals('412', $realtime['meta']['currStroke']);

        Carbon::setTestNow();
    }

    public function test_current_and_actual_gsph_formulas_with_zero_safety()
    {
        $date = '2026-10-28';
        Carbon::setTestNow(Carbon::parse($date.' 11:00:00'));

        $lineA = LineMaster::where('line_code', 'PA')->first();

        // Item 1 finished: 400 ok, 40m runtime, 10m dandori, 10m dt -> tpt 60m
        $plan1 = ProductionPlan::create([
            'line_master_id' => $lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'PA-01',
            'job_master' => 'PART A1',
            'plan' => 400,
            'total_mesin' => 4,
            'gsph_item' => 400,
            'status' => 'completed',
        ]);

        $job1 = JobMaster::create([
            'job_number' => "PA-01-{$plan1->id}",
            'job_name' => 'PART A1',
            'line' => 'PRESS A',
            'target_qty' => 400,
            'sequence_no' => 1,
            'status' => 'completed',
        ]);

        DailyProduction::create([
            'job_master_id' => $job1->id,
            'work_date' => $date,
            'line' => 'PRESS A',
            'shift' => 'Shift Pagi',
            'actual_ok' => 400,
            'actual_qty' => 400,
            'runtime_seconds' => 2400, // 40m
        ]);

        Dandori::create([
            'next_job_id' => $job1->id,
            'line' => 'PRESS A',
            'work_date' => $date,
            'activity' => 'DANDORI',
            'duration_minutes' => 10,
        ]);

        Downtime::create([
            'job_master_id' => $job1->id,
            'jenis_downtime' => 'machine',
            'duration_seconds' => 600, // 10m
            'start_time' => Carbon::parse($date.' 08:00:00'),
            'finish_time' => Carbon::parse($date.' 08:10:00'),
        ]);

        // Item 2 running: 100 ok, 15m runtime, 5m dandori, 0m dt -> duration 20m
        $plan2 = ProductionPlan::create([
            'line_master_id' => $lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS A',
            'row_no' => 2,
            'row_type' => 'job',
            'job_no' => 'PA-02',
            'job_master' => 'PART A2',
            'plan' => 300,
            'total_mesin' => 4,
            'gsph_item' => 300,
            'status' => 'running',
        ]);

        $job2 = JobMaster::create([
            'job_number' => "PA-02-{$plan2->id}",
            'job_name' => 'PART A2',
            'line' => 'PRESS A',
            'target_qty' => 300,
            'sequence_no' => 2,
            'status' => 'running',
            'started_at' => Carbon::parse($date.' 10:00:00'),
        ]);

        DailyProduction::create([
            'job_master_id' => $job2->id,
            'work_date' => $date,
            'line' => 'PRESS A',
            'shift' => 'Shift Pagi',
            'actual_ok' => 100,
            'actual_qty' => 100,
            'runtime_seconds' => 900, // 15m
        ]);

        Dandori::create([
            'next_job_id' => $job2->id,
            'line' => 'PRESS A',
            'work_date' => $date,
            'activity' => 'DANDORI',
            'duration_minutes' => 5,
        ]);

        $realtime = app(DashboardRealtimeService::class)->getLineMetrics('Line A', $date, 1);
        $kpiByDesc = collect($realtime['kpi'])->keyBy('desc');

        // CURRENT GSPH:
        // Item berjalan: 100 qty / (15m press + 5m dandori + 0m dt) * 60 = (100 / 20) * 60 = 300
        $this->assertEquals('300', $kpiByDesc['GSPH']['current']);

        // ACTUAL GSPH:
        // Total kumulatif qty up to running item: 400 + 100 = 500 ok
        // Total kumulatif duration: Item 1 (40+10+10 = 60m) + Item 2 (15+5 = 20m) = 80m
        // Actual GSPH = (500 / 80) * 60 = 375
        $this->assertEquals('375', $kpiByDesc['GSPH']['actual']);

        Carbon::setTestNow();
    }

    public function test_schedule_backup_comparison_matches_break_rows()
    {
        $date = '2026-10-28';
        $lineA = LineMaster::where('line_code', 'PA')->first();

        $planBreak = ProductionPlan::create([
            'line_master_id' => $lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS A',
            'row_no' => 3,
            'row_type' => 'break',
            'job_no' => 'ISTIRAHAT SIANG',
            'job_master' => 'ISTIRAHAT SIANG',
            'start_time' => '11:45',
            'finish_time' => '12:25',
        ]);

        $backupService = app(ScheduleBackupService::class);
        $backup = $backupService->captureSnapshot($date, 'manual_backup', null);

        // Modify row_no slightly in live but keep break label & times
        $planBreak->update(['row_no' => 4]);

        $comparison = $backupService->compareWithLive($backup);

        // Break row should be matched rather than reported as missing
        $this->assertEquals(0, $comparison['counts']['will_restore_missing']);
        $this->assertEquals(0, $comparison['counts']['post_snapshot_plans']);
    }
}
