<?php

use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cutoffTestLine(): LineMaster
{
    return LineMaster::create([
        'line_code' => 'L-CUTOFF',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);
}

function cutoffTestUser(string $role = 'ppc'): User
{
    return User::create([
        'name' => 'Cutoff Test User',
        'nrp' => 'PPC-CUT-001',
        'password' => 'secret',
        'role' => $role,
    ]);
}

function cutoffTestPlan(LineMaster $line, array $overrides = []): ProductionPlan
{
    return ProductionPlan::create(array_merge([
        'line_master_id' => $line->id,
        'plan_date' => '2026-10-27',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'CUTOFF MASTER',
        'job_no' => 'CUT-001',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 500,
        'status' => 'pending',
        'source_type' => 'ppc',
        'start_time' => '15:30',
        'finish_time' => '17:00',
    ], $overrides));
}

it('allows PPC user to delete selected cut off items with no production activity', function () {
    $user = cutoffTestUser('ppc');
    $line = cutoffTestLine();

    $plan1 = cutoffTestPlan($line, ['job_no' => 'CUT-101', 'plan' => 300]);
    $plan2 = cutoffTestPlan($line, ['job_no' => 'CUT-102', 'plan' => 400]);
    $plan3 = cutoffTestPlan($line, ['job_no' => 'CUT-103', 'plan' => 500]);

    $recovery1 = RecoveryItem::create([
        'production_plan_id' => $plan1->id,
        'original_date' => '2026-10-27',
        'original_shift' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_no' => 'CUT-101',
        'job_master' => 'CUTOFF MASTER',
        'target_qty' => 300,
        'status' => 'waiting_approval',
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('ppc.planning.production_plan.destroy_cutoff'), [
            'plan_ids' => [$plan1->id, $plan2->id],
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'deleted_ids' => [$plan1->id, $plan2->id],
        ]);

    expect(ProductionPlan::whereKey($plan1->id)->exists())->toBeFalse();
    expect(ProductionPlan::whereKey($plan2->id)->exists())->toBeFalse();
    expect(RecoveryItem::whereKey($recovery1->id)->exists())->toBeFalse();

    // Plan 3 unselected must remain
    expect(ProductionPlan::whereKey($plan3->id)->exists())->toBeTrue();
});

it('rejects deletion if any selected plan has production activity or progress', function () {
    $user = cutoffTestUser('ppc');
    $line = cutoffTestLine();

    $activePlan = cutoffTestPlan($line, [
        'job_no' => 'CUT-ACTIVE',
        'ok' => 50,
        'status' => 'running',
    ]);

    $cleanPlan = cutoffTestPlan($line, [
        'job_no' => 'CUT-CLEAN',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('ppc.planning.production_plan.destroy_cutoff'), [
            'plan_ids' => [$activePlan->id, $cleanPlan->id],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    // Active item and clean item must not be deleted due to transaction rollback
    expect(ProductionPlan::whereKey($activePlan->id)->exists())->toBeTrue();
    expect(ProductionPlan::whereKey($cleanPlan->id)->exists())->toBeTrue();
});

it('returns 422 if plan_ids is empty', function () {
    $user = cutoffTestUser('ppc');

    $response = $this->actingAs($user)
        ->postJson(route('ppc.planning.production_plan.destroy_cutoff'), [
            'plan_ids' => [],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Tidak ada item cut off yang dipilih.',
        ]);
});

it('prevents unauthorized non-PPC roles without permission from deleting cut off plans', function () {
    $operator = cutoffTestUser('operator');
    $line = cutoffTestLine();
    $plan = cutoffTestPlan($line);

    $response = $this->actingAs($operator)
        ->postJson(route('ppc.planning.production_plan.destroy_cutoff'), [
            'plan_ids' => [$plan->id],
        ]);

    // Feature / role middleware restricts non-authorized roles
    expect($response->status())->toBeIn([401, 403, 404]);
    expect(ProductionPlan::whereKey($plan->id)->exists())->toBeTrue();
});
