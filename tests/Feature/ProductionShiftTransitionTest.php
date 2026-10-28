<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Services\ProductionShiftTransitionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionShiftTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected LineMaster $lineA;
    protected LineMaster $lineB;
    protected LineMaster $lineC;
    protected LineMaster $lineD;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lineA = LineMaster::updateOrCreate(['line_code' => 'PA'], ['line_name' => 'Line A', 'status' => 'active']);
        $this->lineB = LineMaster::updateOrCreate(['line_code' => 'PB'], ['line_name' => 'Line B', 'status' => 'active']);
        $this->lineC = LineMaster::updateOrCreate(['line_code' => 'PC'], ['line_name' => 'Line C', 'status' => 'active']);
        $this->lineD = LineMaster::updateOrCreate(['line_code' => 'PD'], ['line_name' => 'Line D', 'status' => 'active']);
    }

    public function test_shift_pagi_unfinished_session_is_paused_safely_when_shift_malam_starts(): void
    {
        $date = '2026-10-28';

        // 1. Plan Shift Pagi (Line A)
        $pagiPlan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-001',
            'job_master' => 'AES-001',
            'plan' => 500,
            'status' => 'running',
        ]);

        $pagiJob = JobMaster::create([
            'job_number' => 'AES-001-' . $pagiPlan->id,
            'job_name' => 'Part AES-001',
            'line' => 'Line A',
            'status' => 'running',
            'target_qty' => 500,
            'started_at' => Carbon::parse("{$date} 08:00:00"),
        ]);

        $daily = DailyProduction::create([
            'job_master_id' => $pagiJob->id,
            'work_date' => $date,
            'actual_ok' => 200,
            'actual_repair' => 0,
            'actual_reject' => 0,
            'runtime_seconds' => 3600,
        ]);

        $session = ProductionSession::create([
            'job_master_id' => $pagiJob->id,
            'work_date' => $date,
            'shift' => 'Shift Pagi',
            'start_time' => Carbon::parse("{$date} 08:00:00"),
            'status' => 'running',
            'total_seconds' => 3600,
        ]);

        // 2. Plan Shift Malam (Line A)
        $malamPlan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-002',
            'job_master' => 'AES-002',
            'plan' => 300,
            'status' => 'pending',
        ]);

        $malamJob = JobMaster::create([
            'job_number' => 'AES-002-' . $malamPlan->id,
            'job_name' => 'Part AES-002',
            'line' => 'Line A',
            'status' => 'pending',
            'target_qty' => 300,
        ]);

        $transitionService = app(ProductionShiftTransitionService::class);

        // Inspect audit first
        $audit = $transitionService->inspectActiveSessions('Line A', 'Shift Malam', $date);
        $this->assertEquals(1, $audit['stale_shift_jobs_count']);
        $this->assertEquals($pagiJob->id, $audit['stale_shift_jobs'][0]['job_id']);

        // Execute safe transition
        $result = $transitionService->transitionShiftSafely('Line A', 'Shift Malam', $date);

        // Assertions: Stale Shift Pagi job must be PAUSED, NOT complete, NOT deleted, runtime preserved
        $pagiJob->refresh();
        $this->assertEquals('paused', strtolower($pagiJob->status));

        $session->refresh();
        $this->assertEquals('paused', $session->status);
        $this->assertNotNull($session->pause_time);

        $daily->refresh();
        $this->assertEquals(200, $daily->actual_ok); // Data aktual utuh!

        // Idempotency: Running it again should do nothing
        $secondRun = $transitionService->transitionShiftSafely('Line A', 'Shift Malam', $date);
        $this->assertEquals('none_needed', $secondRun['action_taken']);
    }

    public function test_shift_pagi_finished_session_is_marked_complete_on_shift_transition(): void
    {
        $date = '2026-10-28';

        // Plan Shift Pagi (Line B) with 100% achieved target
        $pagiPlan = ProductionPlan::create([
            'line_master_id' => $this->lineB->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line B',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-003',
            'job_master' => 'AES-003',
            'plan' => 400,
            'status' => 'running',
        ]);

        $pagiJob = JobMaster::create([
            'job_number' => 'AES-003-' . $pagiPlan->id,
            'job_name' => 'Part AES-003',
            'line' => 'Line B',
            'status' => 'running',
            'target_qty' => 400,
            'started_at' => Carbon::parse("{$date} 08:00:00"),
        ]);

        DailyProduction::create([
            'job_master_id' => $pagiJob->id,
            'work_date' => $date,
            'actual_ok' => 400,
            'actual_repair' => 0,
            'actual_reject' => 0,
            'runtime_seconds' => 7200,
        ]);

        ProductionSession::create([
            'job_master_id' => $pagiJob->id,
            'work_date' => $date,
            'shift' => 'Shift Pagi',
            'start_time' => Carbon::parse("{$date} 08:00:00"),
            'status' => 'running',
            'total_seconds' => 7200,
        ]);

        $transitionService = app(ProductionShiftTransitionService::class);
        $result = $transitionService->transitionShiftSafely('Line B', 'Shift Malam', $date);

        $pagiJob->refresh();
        $this->assertEquals('complete', strtolower($pagiJob->status));
        $this->assertCount(1, $result['completed_jobs']);
    }

    public function test_lines_isolation_during_transition(): void
    {
        $date = '2026-10-28';

        // Line C: Shift Pagi running
        $planC = ProductionPlan::create([
            'line_master_id' => $this->lineC->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line C',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-C1',
            'job_master' => 'AES-C1',
            'plan' => 500,
            'status' => 'running',
        ]);
        $jobC = JobMaster::create([
            'job_number' => 'AES-C1-' . $planC->id,
            'job_name' => 'Part C1',
            'line' => 'Line C',
            'status' => 'running',
            'target_qty' => 500,
            'started_at' => Carbon::parse("{$date} 08:00:00"),
        ]);

        // Line D: Shift Malam already running
        $planD = ProductionPlan::create([
            'line_master_id' => $this->lineD->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line D',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-D1',
            'job_master' => 'AES-D1',
            'plan' => 500,
            'status' => 'running',
        ]);
        $jobD = JobMaster::create([
            'job_number' => 'AES-D1-' . $planD->id,
            'job_name' => 'Part D1',
            'line' => 'Line D',
            'status' => 'running',
            'target_qty' => 500,
            'started_at' => Carbon::parse("{$date} 21:30:00"),
        ]);

        $transitionService = app(ProductionShiftTransitionService::class);

        // When reconciling Line C only
        $transitionService->transitionShiftSafely('Line C', 'Shift Malam', $date);

        $jobC->refresh();
        $jobD->refresh();

        $this->assertEquals('paused', strtolower($jobC->status));
        $this->assertEquals('running', strtolower($jobD->status)); // Line D unaffected!
    }
}
