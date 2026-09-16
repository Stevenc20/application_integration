<?php

use App\Models\User;
use App\Models\Signature;
use App\Models\LineMaster;
use App\Models\LineAssignment;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use App\Models\Dandori;
use App\Models\Downtime;
use App\Models\QCheck;
use App\Models\DailyProduction;
use App\Models\LkhCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

// ── helpers (reuse same pattern as SignatureControllerTest) ──────────────

function lkhCreateLine(string $name = 'PRESS A'): LineMaster
{
    return LineMaster::create([
        'line_code' => strtoupper(str_replace(' ', '_', $name)),
        'line_name' => $name,
        'status'    => 'active',
    ]);
}

function lkhCreateUser(string $role): User
{
    return User::create([
        'name'     => ucfirst($role) . ' Test',
        'nrp'      => $role . '-' . uniqid(),
        'password' => 'password',
        'role'     => $role,
        'is_active'=> 1,
    ]);
}

function lkhfGrantFeature(string $role): void
{
    $feature = \App\Models\Feature::create([
        'feature_code' => 'daily_report',
        'feature_name' => 'Daily Report',
        'group_name'   => 'Reports',
    ]);
    \App\Models\RoleFeature::create([
        'role'       => $role,
        'feature_id' => $feature->id,
        'enabled'    => true,
    ]);
}

function lkhMakePlan(LineMaster $line, string $date): ProductionPlan
{
    return ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date'      => $date,
        'shift_name'     => 'Shift Pagi',
        'press_name'     => $line->line_name,
        'row_no'         => 10,
        'row_type'       => 'job',
        'job_no'         => 'LKH-TEST-1',
        'job_master'     => 'LKH TEST',
        'plan'           => 100,
        'ok'             => 0,
        'source_type'    => 'ppc',
    ]);
}

/**
 * Build a team (foreman, leader, line, plan, JobMaster) and sign the leader.
 * Returns compact('foreman', 'leader', 'plan', 'job', 'line').
 */
function lkhSetupTeam(string $date = '2026-09-16'): array
{
    $line   = lkhCreateLine('PRESS A');
    $plan   = lkhMakePlan($line, $date);
    $leader = lkhCreateUser('leader');
    $foreman= lkhCreateUser('foreman');

    LineAssignment::create([
        'line_name'      => 'Line A',
        'shift_name'     => '1',
        'leader_user_id' => $leader->id,
        'foreman_user_id'=> $foreman->id,
    ]);

    $job = JobMaster::create([
        'job_number' => 'LKH-TEST-1-' . $plan->id,
        'job_name'   => 'Test Job',
        'line'       => $line->line_name,
        'status'     => 'active',
    ]);

    lkhfGrantFeature('foreman');

    // leader signs
    Signature::create([
        'role'           => 'teamleader',
        'work_date'      => $date,
        'line_name'      => $line->line_name,
        'shift_name'     => 'Shift Pagi',
        'signature_data' => 'data:image/png;base64,AAAA',
    ]);

    return compact('foreman', 'leader', 'plan', 'job', 'line');
}

// ── 1. Uchi Dandori: edit dandori_dies_variant ───────────────────────

describe('Foreman LKH Edit — Uchi Dandori', function () {

    test('foreman can update dandori_dies_variant and value persists', function () {
        $t = lkhSetupTeam();

        // seed 1 dandori (non-1st_check) → initial 10 min
        Dandori::create([
            'next_job_id'      => $t['job']->id,
            'work_date'        => '2026-09-16',
            'activity'         => 'Changeover',
            'jenis_dandori'    => 'dandori',
            'duration_minutes' => 10,
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:10:00',
        ]);

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '15']],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        expect((float) Dandori::where('next_job_id', $t['job']->id)->sum('duration_minutes'))->toBe(15.0);
        expect(LkhCorrection::where('field', 'dandori_dies_variant')->count())->toBe(1);
    });

    test('dandori_qcheck edit adjusts 1st_check dandori bucket', function () {
        $t = lkhSetupTeam();

        // seed a 1st_check dandori (5 min) — qcheck base = 0
        Dandori::create([
            'next_job_id'      => $t['job']->id,
            'work_date'        => '2026-09-16',
            'activity'         => '1st Check',
            'jenis_dandori'    => '1st_check',
            'duration_minutes' => 5,
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:05:00',
        ]);

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_qcheck', 'value' => '10']],
            ])
            ->assertOk();

        $firstCheck = Dandori::where('next_job_id', $t['job']->id)
            ->where('jenis_dandori', '1st_check')
            ->sum('duration_minutes');
        expect((float) $firstCheck)->toBe(10.0);
    });

    test('dandori_total applies delta to dies bucket (qcheck clamped)', function () {
        $t = lkhSetupTeam();

        Dandori::create([
            'next_job_id'      => $t['job']->id,
            'work_date'        => '2026-09-16',
            'activity'         => 'Changeover',
            'jenis_dandori'    => 'dandori',
            'duration_minutes' => 20,
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:20:00',
        ]);

        // total = 20 (dies) + 0 (qcheck) = 20; set total → 30 ⇒ dies bucket should go to 30
        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_total', 'value' => '30']],
            ])
            ->assertOk();

        expect((float) Dandori::where('next_job_id', $t['job']->id)
            ->where('jenis_dandori', '!=', '1st_check')
            ->sum('duration_minutes'))->toBe(30.0);
    });
});

// ── 2. Down Time: edit dt_* fields ─────────────────────────────────

describe('Foreman LKH Edit — Down Time', function () {

    test('foreman can update dt_machine and downtime record is adjusted', function () {
        $t = lkhSetupTeam();

        Downtime::create([
            'job_master_id'    => $t['job']->id,
            'jenis_downtime'   => 'Machine',
            'problem'          => 'Breakdown',
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:20:00',
            'duration_seconds' => 1200, // 20 min
        ]);

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dt_machine', 'value' => '15']],
            ])
            ->assertOk();

        $dt = Downtime::where('job_master_id', $t['job']->id)->where('jenis_downtime', 'Machine')->first();
        expect($dt->duration_seconds)->toBe(900); // 15 * 60
    });

    test('dt production creates new record when bucket empty and goal > 0', function () {
        $t = lkhSetupTeam();

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dt_production', 'value' => '5']],
            ])
            ->assertOk();

        $dt = Downtime::where('job_master_id', $t['job']->id)
            ->where('problem', 'Koreksi LKH')
            ->first();
        expect($dt)->not->toBeNull();
        expect($dt->duration_seconds)->toBe(300);
    });
});

// ── 3. Persist: values survive refresh (re-resolve matches report) ──

describe('Foreman LKH Edit — Persistence', function () {

    test('dandori edit persists and report re-resolves new value', function () {
        $t = lkhSetupTeam();

        Dandori::create([
            'next_job_id'      => $t['job']->id,
            'work_date'        => '2026-09-16',
            'activity'         => 'Changeover',
            'jenis_dandori'    => 'dandori',
            'duration_minutes' => 8,
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:08:00',
        ]);

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '25']],
            ])
            ->assertOk();

        // Verify DB value is correct after the edit
        $dandori = Dandori::where('next_job_id', $t['job']->id)->first();
        expect((float) $dandori->duration_minutes)->toBe(25.0);
    });

    test('response HTML fragment contains updated mins value', function () {
        $t = lkhSetupTeam();

        Dandori::create([
            'next_job_id'      => $t['job']->id,
            'work_date'        => '2026-09-16',
            'activity'         => 'Changeover',
            'jenis_dandori'    => 'dandori',
            'duration_minutes' => 12,
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:12:00',
        ]);

        $res = $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '18']],
            ])
            ->assertOk()
            ->json();

        // The HTML fragment should contain the new data-value for the cell
        expect($res['html'])->toContain('data-value="18"');
    });
});

// ── 4. Authz: Line B blocked (wrong scope), Shift Malam blocked, no TTD blocked, role guard ──

describe('Foreman LKH Edit — Authorization', function () {

    test('line B with leader signed on line A is rejected (scope check)', function () {
        $t = lkhSetupTeam();

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS B',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '10']],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Edit hanya terbuka setelah TTD Team Leader');
    });

    test('shift malam with leader signed on shift pagi is rejected', function () {
        $t = lkhSetupTeam();

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Malam',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '10']],
            ])
            ->assertStatus(422);
    });

    test('no TTD at all is rejected', function () {
        $line  = lkhCreateLine('PRESS A');
        $plan  = lkhMakePlan($line, '2026-09-16');
        $user  = lkhCreateUser('foreman');
        lkhfGrantFeature('foreman');

        $this->actingAs($user)
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $plan->id, 'field' => 'dandori_dies_variant', 'value' => '10']],
            ])
            ->assertStatus(422);
    });

    test('non-foreman/superadmin role gets 403', function () {
        $user = lkhCreateUser('operator');

        $this->actingAs($user)
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => 1, 'field' => 'dandori_dies_variant', 'value' => '10']],
            ])
            ->assertStatus(403);
    });
});

// ── 5. Regression: actual_good still works ──────────────────────────

describe('Foreman LKH Edit — Regression', function () {

    test('actual_good edit still works alongside new minutes fields', function () {
        $t = lkhSetupTeam();

        DailyProduction::create([
            'job_master_id' => $t['job']->id,
            'work_date'     => '2026-09-16',
            'actual_ok'     => 50,
            'actual_repair' => 5,
            'actual_reject' => 3,
            'actual_qty'    => 58,
        ]);

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [
                    ['plan_id' => $t['plan']->id, 'field' => 'actual_good', 'value' => '60'],
                ],
            ])
            ->assertOk();

        expect(DailyProduction::where('job_master_id', $t['job']->id)->first()->actual_ok)->toBe(60);
    });
});

// ── 6. Validation: negatives, non-numeric, out-of-range ─────────────

describe('Foreman LKH Edit — Validation', function () {

    test('negative value is rejected', function () {
        $t = lkhSetupTeam();

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '-5']],
            ])
            ->assertStatus(422);
    });

    test('non-numeric value is rejected', function () {
        $t = lkhSetupTeam();

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => 'abc']],
            ])
            ->assertStatus(422);
    });

    test('value > 1440 is rejected', function () {
        $t = lkhSetupTeam();

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '1500']],
            ])
            ->assertStatus(422);
    });

    test('value equal to current produces no change (422 no valid change)', function () {
        $t = lkhSetupTeam();

        Dandori::create([
            'next_job_id'      => $t['job']->id,
            'work_date'        => '2026-09-16',
            'activity'         => 'Changeover',
            'jenis_dandori'    => 'dandori',
            'duration_minutes' => 10,
            'start_time'       => '2026-09-16 08:00:00',
            'finish_time'      => '2026-09-16 08:10:00',
        ]);

        $this->actingAs($t['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date'    => '2026-09-16',
                'line'    => 'PRESS A',
                'shift'   => 'Shift Pagi',
                'updates' => [['plan_id' => $t['plan']->id, 'field' => 'dandori_dies_variant', 'value' => '10']],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Tidak ada perubahan yang valid disimpan');
    });
});
