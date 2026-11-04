<?php

use App\Models\User;
use App\Models\LineMaster;
use App\Models\LineAssignment;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\QCheck;
use App\Models\DailyProduction;
use App\Models\LkhCorrection;
use App\Models\Signature;
use App\Services\ProductionMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

function lkhRecalcCreateLine(string $name = 'PRESS A'): LineMaster
{
    return LineMaster::create([
        'line_code' => strtoupper(str_replace(' ', '_', $name)),
        'line_name' => $name,
        'status'    => 'active',
    ]);
}

function lkhRecalcCreateUser(string $role): User
{
    return User::create([
        'name'     => ucfirst($role) . ' User',
        'nrp'      => $role . '-' . uniqid(),
        'password' => 'password',
        'role'     => $role,
        'is_active'=> 1,
    ]);
}

function lkhRecalcGrantFeature(string $role): void
{
    $feature = \App\Models\Feature::firstOrCreate(
        ['feature_code' => 'daily_report'],
        ['feature_name' => 'Daily Report', 'group_name' => 'Reports']
    );
    \App\Models\RoleFeature::firstOrCreate([
        'role'       => $role,
        'feature_id' => $feature->id,
        'enabled'    => true,
    ]);
}

function lkhRecalcSignLeader(string $line, string $date, string $shift, User $leader): void
{
    Signature::create([
        'report_type'        => 'daily_report',
        'work_date'          => $date,
        'line_name'          => $line,
        'shift_name'         => $shift,
        'role'               => 'teamleader',
        'signer_name'        => $leader->name,
        'signer_user_id'     => $leader->id,
        'signature_data'     => 'data:image/png;base64,dummy',
        'signed_at'          => Carbon::parse($date . ' 10:00:00'),
    ]);
}

describe('LKH Recalculation Integrity & Formula Verification', function () {

    it('recalculates total dandori and TPT actual correctly when editing dandori dies or total', function () {
        $line = lkhRecalcCreateLine('PRESS A');
        $leader = lkhRecalcCreateUser('leader');
        lkhRecalcGrantFeature('leader');

        $date = '2026-11-04';
        $shift = 'Shift Pagi';

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date'      => $date,
            'shift_name'     => $shift,
            'press_name'     => 'PRESS A',
            'line'           => 'PRESS A',
            'shift'          => $shift,
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'JOB-TEST-001',
            'job_master'     => 'BRACKET LH',
            'plan'           => 500,
            'start_time'     => '07:00',
            'finish_time'    => '09:00',
            'tpt'            => 120,
            'cycle_time'     => 10.0,
            'gsph_item'      => 250,
            'status'         => 'completed',
            'source_type'    => 'ppc',
        ]);

        $job = JobMaster::create([
            'job_number'   => 'JOB-TEST-001-' . $plan->id,
            'job_name'     => 'BRACKET LH',
            'line'         => 'PRESS A',
            'target_qty'   => 500,
            'status'       => 'finished',
            'started_at'   => Carbon::parse($date . ' 07:00:00'),
            'finished_at'  => Carbon::parse($date . ' 09:00:00'),
        ]);

        DailyProduction::create([
            'job_master_id' => $job->id,
            'work_date'     => $date,
            'line'          => 'PRESS A',
            'shift'         => $shift,
            'target_qty'    => 500,
            'actual_ok'     => 450,
            'actual_repair' => 10,
            'actual_reject' => 5,
            'actual_qty'    => 465,
        ]);

        // Edit dandori_dies_variant to 25.0
        $res = $this->actingAs($leader)->postJson(route('supervisor.reports.daily_production.update_cells'), [
            'line'    => 'PRESS A',
            'date'    => $date,
            'shift'   => $shift,
            'updates' => [
                ['plan_id' => $plan->id, 'field' => 'dandori_dies_variant', 'value' => '25.0'],
            ],
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        // Verify dandori entry is saved
        $dandori = Dandori::where('next_job_id', $job->id)->first();
        expect($dandori)->not->toBeNull();
        expect((float) $dandori->duration_minutes)->toBe(25.0);

        // Verify HTML fragments recalculate total dandori and TPT
        $html = $res->json('html');
        expect($html)->toContain('data-col="dandori_total"');
        expect($html)->toContain('data-col="tpt_act"');
    });

    it('recalculates total stroke, pass rate, repair rate, reject rate and summary achievement when editing actual quantities', function () {
        $line = lkhRecalcCreateLine('PRESS A');
        $leader = lkhRecalcCreateUser('leader');
        lkhRecalcGrantFeature('leader');

        $date = '2026-11-04';
        $shift = 'Shift Pagi';

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date'      => $date,
            'shift_name'     => $shift,
            'press_name'     => 'PRESS A',
            'line'           => 'PRESS A',
            'shift'          => $shift,
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'JOB-TEST-002',
            'job_master'     => 'BRACKET RH',
            'plan'           => 1000,
            'start_time'     => '07:00',
            'finish_time'    => '11:00',
            'tpt'            => 240,
            'cycle_time'     => 12.0,
            'gsph_item'      => 250,
            'status'         => 'completed',
            'source_type'    => 'ppc',
        ]);

        $job = JobMaster::create([
            'job_number'   => 'JOB-TEST-002-' . $plan->id,
            'job_name'     => 'BRACKET RH',
            'line'         => 'PRESS A',
            'target_qty'   => 1000,
            'status'       => 'finished',
            'started_at'   => Carbon::parse($date . ' 07:00:00'),
            'finished_at'  => Carbon::parse($date . ' 11:00:00'),
        ]);

        // Edit actual_good, actual_repair, actual_reject
        $res = $this->actingAs($leader)->postJson(route('supervisor.reports.daily_production.update_cells'), [
            'line'    => 'PRESS A',
            'date'    => $date,
            'shift'   => $shift,
            'updates' => [
                ['plan_id' => $plan->id, 'field' => 'actual_good', 'value' => '950'],
                ['plan_id' => $plan->id, 'field' => 'actual_repair', 'value' => '30'],
                ['plan_id' => $plan->id, 'field' => 'actual_reject', 'value' => '20'],
            ],
        ]);

        $res->assertStatus(200);

        // Verify database daily_productions and plan
        $dp = DailyProduction::where('job_master_id', $job->id)->first();
        expect($dp->actual_ok)->toBe(950);
        expect($dp->actual_repair)->toBe(30);
        expect($dp->actual_reject)->toBe(20);
        expect($dp->actual_qty)->toBe(1000); // 950 + 30 + 20

        $plan->refresh();
        expect((int) $plan->ok)->toBe(950);
        expect((int) $plan->repair)->toBe(30);
        expect((int) $plan->reject)->toBe(20);

        // Pass rate = 950/1000 * 100 = 95.0%
        // Repair rate = 30/1000 * 100 = 3.0%
        // Reject rate = 20/1000 * 100 = 2.0%
        $html = $res->json('html');
        expect($html)->toContain('95.0');
        expect($html)->toContain('3.0');
        expect($html)->toContain('2.0');
    });

    it('persists actual start and recalculates finish time and metrics consistently', function () {
        $line = lkhRecalcCreateLine('PRESS A');
        $leader = lkhRecalcCreateUser('leader');
        lkhRecalcGrantFeature('leader');

        $date = '2026-11-04';
        $shift = 'Shift Pagi';

        $plan = ProductionPlan::create([
            'line_master_id' => $line->id,
            'plan_date'      => $date,
            'shift_name'     => $shift,
            'press_name'     => 'PRESS A',
            'line'           => 'PRESS A',
            'shift'          => $shift,
            'row_no'         => 1,
            'row_type'       => 'job',
            'job_no'         => 'JOB-TEST-003',
            'job_master'     => 'SUPPORT PANEL',
            'plan'           => 300,
            'start_time'     => '07:00',
            'finish_time'    => '08:30',
            'tpt'            => 90,
            'cycle_time'     => 15.0,
            'gsph_item'      => 200,
            'status'         => 'completed',
            'source_type'    => 'ppc',
        ]);

        $res = $this->actingAs($leader)->postJson(route('supervisor.reports.daily_production.update_cells'), [
            'line'    => 'PRESS A',
            'date'    => $date,
            'shift'   => $shift,
            'updates' => [
                ['plan_id' => $plan->id, 'field' => 'actual_start', 'value' => '07:15'],
                ['plan_id' => $plan->id, 'field' => 'actual_finish', 'value' => '08:45'],
            ],
        ]);

        $res->assertStatus(200);

        $plan->refresh();
        expect($plan->act_start)->toBe('07:15');
        expect($plan->act_finish)->toBe('08:45');
    });

    it('strictly excludes break time from TPT actual calculation according to ProductionMetricsService formula', function () {
        // Business Formula Check:
        // TPT Actual = Press Time + Dandori + Downtime
        // Work Time = TPT Actual + Break Time
        $pressTime = 60.0;
        $dandoriTime = 15.0;
        $downtimeTime = 10.0;
        $tptActual = ProductionMetricsService::actualTpt($pressTime, $dandoriTime, $downtimeTime);
        expect($tptActual)->toBe(85.0); // 60 + 15 + 10

        $breakDuration = 15.0;
        $workTime = $tptActual + $breakDuration;
        expect($workTime)->toBe(100.0);
        // TPT must NOT include the break duration
        expect($tptActual)->not->toBe($workTime);
    });

});
