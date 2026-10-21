<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\Dandori;
use App\Models\Downtime;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Carbon\Carbon;

class ProductionAnalyticsTimelineTest extends TestCase
{
    use DatabaseMigrations;

    public function test_timeline_segments_duration_and_navigation_links()
    {
        $planDate = '2026-10-21';

        // Create LineMaster
        $lineMaster = \App\Models\LineMaster::create([
            'line_name' => 'Line A',
            'line_code' => 'LA',
            'status' => 'active',
        ]);

        // 1. Create a dummy ProductionPlan
        $plan = ProductionPlan::create([
            'line_master_id' => $lineMaster->id,
            'plan_date' => $planDate,
            'press_name' => 'PRESS A',
            'shift_name' => 'Shift 1',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'TEST-PLAN-9999',
            'job_master' => 'AES-028 WIP',
            'plan' => 240,
            'start_time' => '08:00:00',
            'finish_time' => '10:00:00',
        ]);

        // 2. Create corresponding JobMaster
        $job = JobMaster::create([
            'job_number' => 'TEST-PLAN-9999-' . $plan->id,
            'job_name' => 'AES-028 WIP',
            'line' => 'A',
            'status' => 'complete',
            'target_qty' => 240,
            'started_at' => $planDate . ' 08:00:00',
            'finished_at' => $planDate . ' 10:00:00',
        ]);

        // 3. Create ProductionSession (1 hr = 3600s)
        ProductionSession::create([
            'job_master_id' => $job->id,
            'start_time' => $planDate . ' 08:30:00',
            'finish_time' => $planDate . ' 09:30:00',
            'total_seconds' => 3600,
            'status' => 'finished',
            'work_date' => $planDate,
        ]);

        // 4. Create Dandori (10 min = 600s) and 1st Check (5 min = 300s)
        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'A',
            'shift' => 'Shift 1',
            'activity' => 'PERSIAPAN DANDORI',
            'jenis_dandori' => 'dandori',
            'start_time' => $planDate . ' 08:00:00',
            'finish_time' => $planDate . ' 08:10:00',
            'duration_minutes' => 10,
            'work_date' => $planDate,
        ]);

        Dandori::create([
            'next_job_id' => $job->id,
            'line' => 'A',
            'shift' => 'Shift 1',
            'activity' => '1ST CHECK',
            'jenis_dandori' => '1st_check',
            'start_time' => $planDate . ' 08:10:00',
            'finish_time' => $planDate . ' 08:15:00',
            'duration_minutes' => 5,
            'work_date' => $planDate,
        ]);

        // 5. Create pure Downtime, Try Out, and Break Time
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'machine',
            'problem' => 'Mesin macet',
            'start_time' => $planDate . ' 08:20:00',
            'finish_time' => $planDate . ' 08:25:00',
            'duration_seconds' => 300, // 5 min
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'try out',
            'problem' => 'Trial dies',
            'start_time' => $planDate . ' 08:25:00',
            'finish_time' => $planDate . ' 08:28:00',
            'duration_seconds' => 180, // 3 min
        ]);

        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'break time',
            'problem' => 'Istirahat',
            'start_time' => $planDate . ' 09:30:00',
            'finish_time' => $planDate . ' 09:45:00',
            'duration_seconds' => 900, // 15 min
        ]);

        // 6. Test rendering _overview view
        $plan->job_data = $job->load(['productionSessions', 'dandoris', 'dandoriSessions', 'downtimes']);
        $plans = collect([$plan]);

        $rendered = view('analytics._overview', [
            'stats' => (object)[
                'total_qty' => 240,
                'total_target' => 240,
                'total_ok' => 240,
                'total_repair' => 0,
                'total_reject' => 0,
                'total_runtime' => 3600,
                'total_downtime' => 300,
                'oee' => 100,
                'availability' => 100,
                'performance' => 100,
                'quality' => 100
            ],
            'dailyTrend' => collect(),
            'achievement' => 100,
            'downtimeAgg' => (object)[
                'idle_sec' => 0,
                'repair_sec' => 0,
                'dandori_sec' => 600,
                'tryout_sec' => 180,
                'break_sec' => 900
            ],
            'plans' => $plans,
            'totalPlanTarget' => 240,
            'dateFrom' => $planDate,
            'dateTo' => $planDate,
            'line' => 'A',
            'shift' => 'Shift 1',
            'status' => ''
        ])->render();

        // Check Durasi Legenda
        $this->assertStringContainsString('Prod 01:00', $rendered, 'Prod should be 1 hr (01:00)');
        $this->assertStringContainsString('Downtime 00:05', $rendered, 'Downtime should be 5 min (00:05)');
        $this->assertStringContainsString('Dandori 00:10', $rendered, 'Dandori should be 10 min (00:10)');
        $this->assertStringContainsString('1st Check 00:05', $rendered, '1st Check should be 5 min (00:05)');
        $this->assertStringContainsString('Try Out 00:03', $rendered, 'Try Out should be 3 min (00:03)');
        $this->assertStringContainsString('Break Time 00:15', $rendered, 'Break Time should be 15 min (00:15)');

        // Check Navigation Links carrying job_id, line, date
        $expectedDandoriLink = htmlspecialchars(route('operational.dandori', ['job_id' => $job->id, 'line' => 'A', 'shift' => 'Shift 1', 'date' => $planDate]));
        $this->assertStringContainsString($expectedDandoriLink, $rendered, 'Dandori button must carry item id, line, shift, date');

        $expectedDtLink = htmlspecialchars(route('monitoring.history', ['type' => 'downtime', 'job_id' => $job->id, 'plan_id' => $plan->id, 'line' => 'A', 'date' => $planDate]));
        $this->assertStringContainsString($expectedDtLink, $rendered, 'DT button must carry item id, plan_id, line, date');

        $expectedTryoutLink = htmlspecialchars(route('monitoring.history', ['type' => 'downtime', 'job_id' => $job->id, 'plan_id' => $plan->id, 'jenis' => 'try out', 'line' => 'A', 'date' => $planDate]));
        $this->assertStringContainsString($expectedTryoutLink, $rendered, 'Try Out button must carry item id, plan_id, jenis, line, date');

        $expectedBreakLink = htmlspecialchars(route('monitoring.history', ['type' => 'break', 'job_id' => $job->id, 'plan_id' => $plan->id, 'line' => 'A', 'date' => $planDate]));
        $this->assertStringContainsString($expectedBreakLink, $rendered, 'Break button must carry item id, plan_id, line, date');

        // 7. Test rendering _job_detail view
        $renderedDetail = view('analytics._job_detail', [
            'job' => $job,
            'sessions' => $job->productionSessions,
            'dandoriSessions' => $job->dandoriSessions,
            'dandoris' => $job->dandoris,
        ])->render();

        $this->assertStringContainsString('Prod 01:00', $renderedDetail);
        $this->assertStringContainsString('Down 00:05', $renderedDetail);
        $this->assertStringContainsString('Dandori 00:10', $renderedDetail);
        $this->assertStringContainsString('1st Check 00:05', $renderedDetail);
        $this->assertStringContainsString('Try Out 00:03', $renderedDetail);
        $this->assertStringContainsString('Break 00:15', $renderedDetail);

        // Verify that monitoring/history query works with try out and plan_id/job_id
        $request = \Illuminate\Http\Request::create(route('monitoring.history', [
            'type' => 'downtime',
            'job_id' => $job->id,
            'plan_id' => $plan->id,
            'jenis' => 'try out',
            'line' => 'A',
            'date' => $planDate,
        ]));
        $controller = new \App\Http\Controllers\Admin\MonitoringController();
        $view = $controller->history('downtime', $request);
        $this->assertEquals('downtime', $view->getData()['type']);
        $this->assertTrue($view->getData()['records']->contains(function($r) {
            return $r->problem === 'Trial dies';
        }));
    }
}
