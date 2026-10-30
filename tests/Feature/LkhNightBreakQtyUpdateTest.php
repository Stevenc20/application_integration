<?php

namespace Tests\Feature;

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineAssignment;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\Signature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LkhNightBreakQtyUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function grantFeature(string $role): void
    {
        $feature = \App\Models\Feature::firstOrCreate(
            ['feature_code' => 'daily_report'],
            [
                'feature_name' => 'Daily Report',
                'group_name'   => 'Reports',
            ]
        );
        \App\Models\RoleFeature::firstOrCreate([
            'role'       => $role,
            'feature_id' => $feature->id,
            'enabled'    => true,
        ]);
    }

    public function test_leader_and_foreman_can_update_qty_on_shift_malam_after_midnight_break(): void
    {
        $this->grantFeature('leader');
        $this->grantFeature('foreman');

        $line = LineMaster::create([
            'line_code' => 'PRESS_A',
            'line_name' => 'PRESS A',
            'status'    => 'active',
        ]);

        $leader = User::create([
            'name'      => 'Leader Malam',
            'nrp'       => 'LD-NIGHT-01',
            'password'  => bcrypt('secret'),
            'role'      => 'leader',
            'is_active' => 1,
        ]);

        $foreman = User::create([
            'name'      => 'Foreman Malam',
            'nrp'       => 'FM-NIGHT-01',
            'password'  => bcrypt('secret'),
            'role'      => 'foreman',
            'is_active' => 1,
        ]);

        LineAssignment::create([
            'line_name'       => 'PRESS A',
            'shift_name'      => 'Shift Malam',
            'leader_user_id'  => $leader->id,
            'foreman_user_id' => $foreman->id,
        ]);

        $workDate = '2026-10-30';

        // Plan for Shift Malam on work_date 2026-10-30 (runs from 21:00 to 07:30 next morning 2026-10-31)
        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date'      => $workDate,
            'shift_name'     => 'Shift Malam',
            'press_name'     => 'PRESS A',
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'NIGHT-JOB-01',
            'job_master'     => 'PART NIGHT 01',
            'plan'           => 500,
            'ok'             => 0,
            'source_type'    => 'ppc',
        ]);

        // JobMaster created with suffix pattern
        $job = JobMaster::create([
            'job_number'  => "CUSTOM-PREFIX-{$plan->id}",
            'job_name'    => 'PART NIGHT 01',
            'line'        => 'PRESS A',
            'target_qty'  => 500,
            'status'      => 'running',
            'sequence_no' => 1,
        ]);

        // 1. Leader edits actual QTY before Leader signs (at 01:00 after midnight break 00:00 - 00:45)
        Carbon::setTestNow(Carbon::parse('2026-10-31 01:15:00'));

        $resLeader = $this->actingAs($leader)
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => $workDate,
                'line'    => 'PRESS A',
                'shift'   => 'Shift Malam',
                'updates' => [
                    ['plan_id' => $plan->id, 'field' => 'actual_good', 'value' => '250'],
                    ['plan_id' => $plan->id, 'field' => 'actual_repair', 'value' => '10'],
                    ['plan_id' => $plan->id, 'field' => 'actual_reject', 'value' => '5'],
                ],
            ]);

        $resLeader->assertOk();

        // Verify DailyProduction and ProductionPlan were updated
        $dp = DailyProduction::where('job_master_id', $job->id)
            ->where('work_date', $workDate)
            ->first();

        $this->assertNotNull($dp);
        $this->assertEquals(250, $dp->actual_ok);
        $this->assertEquals(10, $dp->actual_repair);
        $this->assertEquals(5, $dp->actual_reject);
        $this->assertEquals(265, $dp->actual_qty);

        $plan->refresh();
        $this->assertEquals(250, $plan->ok);
        $this->assertEquals(10, $plan->repair);
        $this->assertEquals(5, $plan->reject);

        // 2. Leader signs Shift Malam
        $this->actingAs($leader)->postJson('/signature/save', [
            'role'       => 'teamleader',
            'work_date'  => $workDate,
            'line_name'  => 'PRESS A',
            'shift_name' => 'Shift Malam',
            'signature'  => 'data:image/png;base64,AAAA',
        ])->assertOk();

        // 3. Now Foreman can edit actual QTY after Leader signs
        $resForeman = $this->actingAs($foreman)
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => $workDate,
                'line'    => 'PRESS A',
                'shift'   => 'Shift Malam',
                'updates' => [
                    ['plan_id' => $plan->id, 'field' => 'actual_good', 'value' => '480'],
                ],
            ]);

        $resForeman->assertOk();

        $dp->refresh();
        $this->assertEquals(480, $dp->actual_ok);
        $this->assertEquals(495, $dp->actual_qty);

        $plan->refresh();
        $this->assertEquals(480, $plan->ok);

        Carbon::setTestNow();
    }
}
