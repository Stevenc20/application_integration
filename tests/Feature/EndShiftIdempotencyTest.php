<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ProductionPlan;
use App\Models\MasterShift;
use App\Models\LineMaster;
use App\Models\ShiftSubmission;
use App\Models\RecoveryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\JobMaster;
use App\Models\ProductionLog;

class EndShiftIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Master Data
        LineMaster::create(['line_code' => 'PRESS_A', 'line_name' => 'Press A']);
        MasterShift::firstOrCreate(
            ['name' => 'Shift Pagi'],
            ['planned_start_time' => '07:30', 'planned_end_time' => '21:00']
        );
        
        // Ensure user
        $this->user = User::factory()->create(['role' => 'teamleader']);
    }

    public function test_end_shift_is_idempotent_and_recovery_not_duplicated()
    {
        $lineId = LineMaster::first()->id;
        $shiftId = MasterShift::first()->id;
        $date = now()->format('Y-m-d');

        $plan = ProductionPlan::create([
            'line_master_id' => $lineId,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi REV 1',
            'shift_master_id' => $shiftId,
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-123',
            'plan' => 100,
            'ok' => 50, 
        ]);

        $payload = [
            'date' => $date,
            'shift_master_id' => $shiftId,
        ];

        // 1. First Request
        $response1 = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload);
        $response1->assertStatus(200);

        $this->assertEquals(1, RecoveryItem::where('production_plan_id', $plan->id)->count());
        $this->assertEquals(1, ShiftSubmission::where('line_id', $lineId)->count());

        // 2. Second Request (Retry / Idempotency)
        $response2 = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload);
        $response2->assertStatus(200);
        $response2->assertJsonPath('message', 'Shift sudah disubmit sebelumnya (Idempotent).');
        
        // Verify no duplicate recovery and no duplicate submission
        $this->assertEquals(1, RecoveryItem::where('production_plan_id', $plan->id)->count());
        $this->assertEquals(1, ShiftSubmission::where('line_id', $lineId)->count());
    }

    public function test_transaction_boundary_rolls_back_if_recovery_fails()
    {
        $lineId = LineMaster::first()->id;
        $shiftId = MasterShift::first()->id;
        $date = now()->format('Y-m-d');

        $plan = ProductionPlan::create([
            'line_master_id' => $lineId,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'shift_master_id' => $shiftId,
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-123',
            'plan' => 100,
            'ok' => 50,
        ]);

        // Mock the CutOffService to throw an exception
        $this->mock(\App\Services\CutOffService::class, function ($mock) {
            $mock->shouldReceive('processCutOff')->andThrow(new \Exception('Database connection lost during recovery'));
        });

        $payload = [
            'date' => $date,
            'shift_master_id' => $shiftId,
        ];

        $response = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload);
        
        // Should return 500
        $response->assertStatus(500);
        
        // Assert transaction rolled back (No submission, no recovery)
        $this->assertEquals(0, ShiftSubmission::count(), 'Shift submission should have been rolled back');
        $this->assertEquals(0, RecoveryItem::count(), 'Recovery should have been rolled back');
    }

    public function test_actual_cutoff_time_is_used()
    {
        $lineId = LineMaster::first()->id;
        $shiftId = MasterShift::first()->id;
        $date = now()->format('Y-m-d');

        $jobMaster = JobMaster::create([
            'job_number' => 'JOB-123',
            'job_name' => 'Part A',
        ]);

        $plan = ProductionPlan::create([
            'line_master_id' => $lineId,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'shift_master_id' => $shiftId,
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => 'JOB-123',
            'job_master' => 'Part A',
            'plan' => 100,
            'ok' => 100, // Total OK in plan is 100
        ]);

        // Create log BEFORE cutoff (ok = 50)
        ProductionLog::create([
            'job_master_id' => $jobMaster->id,
            'ok_qty' => 50,
            'created_at' => now()->subMinutes(30)
        ]);

        // Create log AFTER cutoff (ok = 50)
        ProductionLog::create([
            'job_master_id' => $jobMaster->id,
            'ok_qty' => 50,
            'created_at' => now()->addMinutes(30)
        ]);
        
        // If actual cutoff is right now, it should only count the 50 from 30 mins ago.
        // So RecoveryQty should be 100 - 50 = 50.
        // NOT 100 - 100 = 0.

        $payload = [
            'date' => $date,
            'shift_master_id' => $shiftId,
        ];

        $response = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload);
        $response->assertStatus(200);

        $recovery = RecoveryItem::where('production_plan_id', $plan->id)->first();
        $this->assertNotNull($recovery);
        $this->assertEquals(50, $recovery->recovery_qty); // Proves actual cutoff math works!
    }
}
