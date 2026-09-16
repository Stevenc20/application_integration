<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ProductionPlan;
use App\Models\MasterShift;
use App\Models\LineMaster;
use App\Models\ShiftSubmission;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Models\User;
use App\Models\JobMaster;
use App\Models\Feature;
use App\Models\RoleFeature;

class EndShiftCancelCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LineMaster::create(['line_code' => 'PRESS_A', 'line_name' => 'Press A']);
        LineMaster::create(['line_code' => 'PRESS_B', 'line_name' => 'Press B']);
        MasterShift::firstOrCreate(
            ['name' => 'Shift Pagi'],
            ['planned_start_time' => '07:30', 'planned_end_time' => '21:00']
        );
        MasterShift::firstOrCreate(
            ['name' => 'Shift Malam'],
            ['planned_start_time' => '21:00', 'planned_end_time' => '07:30']
        );

        $this->user = User::factory()->create(['role' => 'teamleader']);
        $this->user2 = User::factory()->create(['role' => 'teamleader', 'name' => 'Leader B']);

        // Seed features for the FeatureMiddleware gates
        $inputHarian = Feature::firstOrCreate(
            ['feature_code' => 'input_harian'],
            ['feature_name' => 'Input Harian', 'group_name' => 'Operational']
        );
        RoleFeature::firstOrCreate(['role' => 'teamleader', 'feature_id' => $inputHarian->id], ['enabled' => true]);
        RoleFeature::firstOrCreate(['role' => 'leader', 'feature_id' => $inputHarian->id], ['enabled' => true]);

        $dashboard = Feature::firstOrCreate(
            ['feature_code' => 'dashboard'],
            ['feature_name' => 'Dashboard', 'group_name' => 'Operational']
        );
        RoleFeature::firstOrCreate(['role' => 'teamleader', 'feature_id' => $dashboard->id], ['enabled' => true]);
        RoleFeature::firstOrCreate(['role' => 'foreman', 'feature_id' => $dashboard->id], ['enabled' => true]);
    }

    private function makePlan(string $jobNo = 'JOB-123', int $plan = 100, int $ok = 50): ProductionPlan
    {
        return ProductionPlan::create([
            'line_master_id' => LineMaster::where('line_name', 'Press A')->first()->id,
            'plan_date' => now()->format('Y-m-d'),
            'shift_name' => 'Shift Pagi',
            'shift_master_id' => MasterShift::where('name', 'Shift Pagi')->first()->id,
            'press_name' => 'Press A',
            'row_type' => 'job',
            'job_no' => $jobNo,
            'plan' => $plan,
            'ok' => $ok,
        ]);
    }

    public function test_cancel_shift_deletes_recovery_items_and_unlocks_for_resubmit()
    {
        $lineId = LineMaster::first()->id;
        $shiftId = MasterShift::where('name', 'Shift Pagi')->first()->id;
        $date = now()->format('Y-m-d');
        $plan = $this->makePlan();

        $payload = ['date' => $date, 'shift_master_id' => $shiftId];

        // Submit
        $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload)->assertStatus(200);
        $this->assertEquals(1, RecoveryItem::where('production_plan_id', $plan->id)->count());
        $submission = ShiftSubmission::where('line_id', $lineId)->first();
        $this->assertNull($submission->cancelled_at);

        // Cancel
        $response = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/cancel", $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Recovery items removed, submission marked cancelled
        $this->assertEquals(0, RecoveryItem::where('production_plan_id', $plan->id)->count());
        $submission->refresh();
        $this->assertNotNull($submission->cancelled_at);
        $this->assertEquals(1, $submission->cancel_count);
        $this->assertEquals($this->user->id, $submission->cancelled_by);

        // Shift should be editable again => guard unblocks: submit again (resubmit path)
        $response2 = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload);
        $response2->assertStatus(200);
        $this->assertStringContainsString('submit ulang', $response2->json('message'));

        // Recovery recreated, submission reactivated, cancel_count preserved
        $this->assertEquals(1, RecoveryItem::where('production_plan_id', $plan->id)->count());
        $submission->refresh();
        $this->assertNull($submission->cancelled_at);
        $this->assertNull($submission->cancelled_by);
        $this->assertEquals(1, $submission->cancel_count); // NOT reset => only 1 cancel allowed
        $this->assertEquals(1, ShiftSubmission::where('line_id', $lineId)->count()); // same row reused
    }

    public function test_cancel_allowed_only_once_per_shift()
    {
        $lineId = LineMaster::first()->id;
        $shiftId = MasterShift::where('name', 'Shift Pagi')->first()->id;
        $date = now()->format('Y-m-d');
        $this->makePlan();

        $payload = ['date' => $date, 'shift_master_id' => $shiftId];

        // Submit -> cancel -> resubmit
        $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload)->assertStatus(200);
        $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/cancel", $payload)->assertStatus(200);
        $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", $payload)->assertStatus(200);

        // Second cancel rejected
        $response = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/cancel", $payload);
        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_cancel_does_not_touch_other_lines_recovery_items()
    {
        $lineB = LineMaster::where('line_name', 'Press B')->first();
        $shiftId = MasterShift::where('name', 'Shift Pagi')->first()->id;
        $date = now()->format('Y-m-d');

        // Plan belonging to Press B
        $planB = ProductionPlan::create([
            'line_master_id' => $lineB->id,
            'plan_date' => $date,
            'shift_name' => 'Shift Pagi',
            'shift_master_id' => $shiftId,
            'press_name' => 'Press B',
            'row_type' => 'job',
            'job_no' => 'JOB-B',
            'plan' => 100,
            'ok' => 30,
        ]);
        $this->makePlan();

        $lineA = LineMaster::where('line_name', 'Press A')->first();
        $payload = ['date' => $date, 'shift_master_id' => $shiftId];

        $this->actingAs($this->user)->postJson("/operational/shift/{$lineA->id}/submit", $payload)->assertStatus(200);
        $this->assertEquals(1, RecoveryItem::where('production_plan_id', $planB->id)->count());

        // Cancel Line A => Press B recovery untouched
        $this->actingAs($this->user)->postJson("/operational/shift/{$lineA->id}/cancel", $payload)->assertStatus(200);
        $this->assertEquals(1, RecoveryItem::where('production_plan_id', $planB->id)->count());
    }

    public function test_submit_stores_leader_comment()
    {
        $lineId = LineMaster::first()->id;
        $shiftId = MasterShift::where('name', 'Shift Pagi')->first()->id;
        $date = now()->format('Y-m-d');
        $this->makePlan();

        $response = $this->actingAs($this->user)->postJson("/operational/shift/{$lineId}/submit", [
            'date' => $date,
            'shift_master_id' => $shiftId,
            'comment' => 'Mesin telat start 30 menit',
        ]);
        $response->assertStatus(200);

        $submission = ShiftSubmission::where('line_id', $lineId)->first();
        $this->assertEquals('Mesin telat start 30 menit', $submission->comment);
    }

    public function test_next_shift_input_harian_shows_previous_shift_comment()
    {
        $lineId = LineMaster::first()->id;
        $pagiId = MasterShift::where('name', 'Shift Pagi')->first()->id;
        $malamId = MasterShift::where('name', 'Shift Malam')->first()->id;
        $date = now()->format('Y-m-d');

        // Shift Pagi submitted with comment
        ShiftSubmission::create([
            'line_id' => $lineId,
            'work_date' => $date,
            'shift' => 1,
            'shift_master_id' => $pagiId,
            'submitted_at' => now(),
            'submitted_by' => $this->user->id,
            'comment' => 'Stopper sering macet, mohon dicek',
        ]);

        // Shift Malam Input Harian for same line should surface the Pagi comment
        $response = $this->actingAs($this->user2)->get("/operational/input-harian?line=Line%20A&shift=Shift%20Malam&date={$date}");
        $response->assertStatus(200);
        $response->assertSee('Stopper sering macet, mohon dicek');
    }

    public function test_foreman_dashboard_shows_shift_comments()
    {
        $lineId = LineMaster::first()->id;
        $pagiId = MasterShift::where('name', 'Shift Pagi')->first()->id;
        $date = now()->format('Y-m-d');

        ShiftSubmission::create([
            'line_id' => $lineId,
            'work_date' => $date,
            'shift' => 1,
            'shift_master_id' => $pagiId,
            'submitted_at' => now(),
            'submitted_by' => $this->user->id,
            'comment' => 'Persiapan mold perlu direvisi',
        ]);

        $this->user->forceFill(['role' => 'foreman'])->save();

        $response = $this->actingAs($this->user)->get('/foreman/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Persiapan mold perlu direvisi');
    }
}