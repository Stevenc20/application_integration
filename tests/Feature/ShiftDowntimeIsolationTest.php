<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\User;
use App\Services\ProductionMetricsService;
use App\Services\ProductionShiftTransitionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftDowntimeIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected LineMaster $lineA;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'operator']);
        $this->actingAs($this->user);

        $this->lineA = LineMaster::updateOrCreate(['line_code' => 'PA'], ['line_name' => 'Line A', 'status' => 'active']);
    }

    public function test_shift_pagi_open_downtime_is_closed_on_transition_and_not_active_in_shift_malam(): void
    {
        $date = '2026-10-28';

        // 1. Shift Pagi Plan & Job
        $pagiPlan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-PAGI-1',
            'job_master' => 'AES-PAGI-1',
            'plan' => 500,
            'status' => 'running',
        ]);

        $pagiJob = JobMaster::create([
            'job_number' => 'AES-PAGI-1-' . $pagiPlan->id,
            'job_name' => 'Part Pagi',
            'line' => 'Line A',
            'status' => 'running',
            'target_qty' => 500,
            'started_at' => Carbon::parse("{$date} 08:00:00"),
        ]);

        ProductionSession::create([
            'job_master_id' => $pagiJob->id,
            'work_date' => $date,
            'start_time' => Carbon::parse("{$date} 08:00:00"),
            'status' => 'running',
            'total_seconds' => 3600,
        ]);

        // Open downtime during Shift Pagi at 10:00
        $pagiDt = Downtime::create([
            'job_master_id' => $pagiJob->id,
            'jenis_downtime' => 'mesin',
            'problem' => 'OVERHEAT MESIN',
            'start_time' => Carbon::parse("{$date} 10:00:00"),
            'finish_time' => null,
            'duration_seconds' => 0,
        ]);

        // 2. Shift Malam Plan & Job
        $malamPlan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-MALAM-1',
            'job_master' => 'AES-MALAM-1',
            'plan' => 300,
            'status' => 'pending',
        ]);

        $malamJob = JobMaster::create([
            'job_number' => 'AES-MALAM-1-' . $malamPlan->id,
            'job_name' => 'Part Malam',
            'line' => 'Line A',
            'status' => 'pending',
            'target_qty' => 300,
        ]);

        // Execute transition to Shift Malam
        $transitionService = app(ProductionShiftTransitionService::class);
        $transitionService->transitionShiftSafely('Line A', 'Shift Malam', $date);

        // 3. Verify open downtime of Shift Pagi was cleanly closed
        $pagiDt->refresh();
        $this->assertNotNull($pagiDt->finish_time);
        $this->assertGreaterThan(0, $pagiDt->duration_seconds);

        // 4. Verify Active Job API for Shift Malam does NOT return the Shift Pagi job or its downtime
        $response = $this->getJson("/operational/active-job?line=Line%20A&shift=Shift%20Malam&date={$date}");
        $response->assertStatus(200);

        $data = $response->json();
        // Since Shift Malam job hasn't started yet, running is false
        $this->assertFalse($data['running']);

        // 5. Querying downtimes for Shift Malam schedule must NOT include Shift Pagi downtime
        $dtResponse = $this->getJson("/operational/job/{$pagiJob->id}/downtimes?shift=Shift%20Malam&date={$date}");
        $dtResponse->assertStatus(200);
        $this->assertCount(0, $dtResponse->json());

        // But querying for Shift Pagi must still retain the full historical record!
        $pagiHistory = $this->getJson("/operational/job/{$pagiJob->id}/downtimes?shift=Shift%20Pagi&date={$date}");
        $pagiHistory->assertStatus(200);
        $this->assertCount(1, $pagiHistory->json());
        $this->assertEquals('OVERHEAT MESIN', $pagiHistory->json()[0]['problem']);
    }

    public function test_midnight_transition_preserves_shift_malam_downtimes(): void
    {
        // 21:00 on Oct 28 to 02:00 on Oct 29
        $workDate = '2026-10-28';
        $nowAtMidnight = Carbon::parse('2026-10-29 02:00:00');

        $this->assertEquals('2026-10-28', ProductionMetricsService::currentProductionDate($nowAtMidnight));
        $this->assertEquals('Shift Malam', ProductionMetricsService::currentProductionShift($nowAtMidnight));

        [$start, $end] = ProductionMetricsService::shiftWindow($workDate, 'Shift Malam');
        $this->assertEquals('2026-10-28 21:00:00', $start->toDateTimeString());
        $this->assertEquals('2026-10-29 07:30:00', $end->toDateTimeString());

        // Downtime started at 01:15 AM
        $plan = ProductionPlan::create([
            'line_master_id' => $this->lineA->id,
            'plan_date' => $workDate,
            'shift_name' => 'Shift Malam',
            'press_name' => 'Line A',
            'row_no' => 1,
            'row_type' => 'job',
            'job_no' => 'AES-NIGHT',
            'job_master' => 'AES-NIGHT',
            'plan' => 200,
            'status' => 'running',
        ]);

        $job = JobMaster::create([
            'job_number' => 'AES-NIGHT-' . $plan->id,
            'job_name' => 'Part Night',
            'line' => 'Line A',
            'status' => 'running',
            'target_qty' => 200,
            'started_at' => Carbon::parse('2026-10-28 21:30:00'),
        ]);

        $nightDt = Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => 'dies',
            'problem' => 'DIES CRACK MIDNIGHT',
            'start_time' => Carbon::parse('2026-10-29 01:15:00'),
            'finish_time' => Carbon::parse('2026-10-29 01:45:00'),
            'duration_seconds' => 1800,
        ]);

        $res = $this->getJson("/operational/job/{$job->id}/downtimes?shift=Shift%20Malam&date={$workDate}");
        $res->assertStatus(200);
        $this->assertCount(1, $res->json());
        $this->assertEquals('DIES CRACK MIDNIGHT', $res->json()[0]['problem']);
    }
}
