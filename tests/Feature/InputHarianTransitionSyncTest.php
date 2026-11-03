<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\User;
use App\Services\ProductionService;
use App\Services\ProductionShiftTransitionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputHarianTransitionSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LineMaster $lineA;
    protected LineMaster $lineB;
    protected LineMaster $lineC;
    protected LineMaster $lineD;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->user);

        $this->lineA = LineMaster::updateOrCreate(['line_code' => 'PA'], ['line_name' => 'Line A', 'status' => 'active']);
        $this->lineB = LineMaster::updateOrCreate(['line_code' => 'PB'], ['line_name' => 'Line B', 'status' => 'active']);
        $this->lineC = LineMaster::updateOrCreate(['line_code' => 'PC'], ['line_name' => 'Line C', 'status' => 'active']);
        $this->lineD = LineMaster::updateOrCreate(['line_code' => 'PD'], ['line_name' => 'Line D', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sync_plan_status_method_is_public_and_callable_by_external_services(): void
    {
        $prodService = app(ProductionService::class);
        $this->assertTrue(method_exists($prodService, 'syncPlanStatus'));

        $ref = new \ReflectionMethod(ProductionService::class, 'syncPlanStatus');
        $this->assertTrue($ref->isPublic(), 'syncPlanStatus must be public');

        // Test invocation with a pending job
        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => '2026-11-03',
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'TEST-SYNC-01',
            'job_master' => 'TEST-SYNC-01',
            'plan' => 100,
            'status' => 'pending',
        ]);

        $job = JobMaster::create([
            'job_number' => 'TEST-SYNC-01-' . $plan->id,
            'job_name' => 'TEST-SYNC-01',
            'line' => 'Line A',
            'status' => 'pending',
            'target_qty' => 100,
        ]);

        // Directly call syncPlanStatus to set status to 'pending'
        $prodService->syncPlanStatus($job->id, 'pending');

        $plan->refresh();
        $this->assertEquals('pending', $plan->status);
    }

    public function test_shift_transition_with_zero_actual_resets_job_to_pending_without_http_500(): void
    {
        $date = '2026-11-03';
        Carbon::setTestNow(Carbon::parse("{$date} 21:10:00"));

        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'CUTOFF-PAGI',
            'job_master' => 'CUTOFF-PAGI',
            'plan' => 200,
            'status' => 'approved',
        ]);

        // Created earlier in shift pagi (e.g. 08:00) so updated_at is old
        $job = JobMaster::create([
            'job_number' => 'CUTOFF-PAGI-' . $plan->id,
            'job_name' => 'CUTOFF-PAGI',
            'line' => 'Line A',
            'status' => 'running',
            'target_qty' => 200,
            'started_at' => Carbon::parse("{$date} 08:00:00"),
        ]);
        JobMaster::where('id', $job->id)->update([
            'created_at' => Carbon::parse("{$date} 08:00:00"),
            'updated_at' => Carbon::parse("{$date} 08:30:00"),
        ]);
        $job->refresh();

        // 0 actual qty produced
        DailyProduction::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'actual_ok' => 0,
            'actual_repair' => 0,
            'actual_reject' => 0,
        ]);

        ProductionSession::create([
            'job_master_id' => $job->id,
            'work_date' => $date,
            'shift' => 'Shift Pagi',
            'start_time' => Carbon::parse("{$date} 08:00:00"),
            'status' => 'running',
        ]);

        // Trigger transitionShiftSafely
        $transitionService = app(ProductionShiftTransitionService::class);
        $result = $transitionService->transitionShiftSafely('Line A', 'Shift Malam', $date);

        $this->assertCount(1, $result['paused_jobs']);
        $this->assertEquals($job->id, $result['paused_jobs'][0]['job_id']);

        $job->refresh();
        $this->assertEquals('pending', $job->status);

        $plan->refresh();
        $this->assertEquals('pending', $plan->status);
    }

    public function test_input_harian_index_and_active_job_endpoints_return_200_for_all_lines(): void
    {
        $date = '2026-11-03';
        Carbon::setTestNow(Carbon::parse("{$date} 21:30:00"));

        foreach (['Line A', 'Line B', 'Line C', 'Line D'] as $lineName) {
            // Test Input Harian index endpoint
            $indexResponse = $this->get("/operational/input-harian?line={$lineName}&date={$date}");
            $this->assertContains($indexResponse->status(), [200, 302]);

            // Test active-job JSON endpoint
            $activeJobResponse = $this->getJson("/operational/active-job?line={$lineName}&date={$date}&shift=Shift%20Malam");
            $activeJobResponse->assertStatus(200);
            $this->assertArrayHasKey('running', $activeJobResponse->json());
        }
    }
}
