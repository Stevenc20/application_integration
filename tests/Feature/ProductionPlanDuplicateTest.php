<?php

use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanDuplicateAudit;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Models\User;
use App\Services\ProductionPlanDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dupLine(): LineMaster
{
    return LineMaster::create([
        'line_code' => 'L-DUP',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);
}

function dupPlan(LineMaster $line, array $overrides = []): ProductionPlan
{
    return ProductionPlan::create(array_merge([
        'line_master_id' => $line->id,
        'plan_date' => '2026-10-26',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB DUP',
        'job_no' => 'DUP-1',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 1000,
        'status' => 'pending',
        'source_type' => 'ppc',
        'start_time' => '07:30',
        'finish_time' => '12:00',
    ], $overrides));
}

function dupUser(): User
{
    return User::create([
        'name' => 'PPC Duplicate Tester',
        'nrp' => 'DUP-001',
        'password' => 'secret',
        'role' => 'superadmin',
    ]);
}

function dupService(): ProductionPlanDuplicateService
{
    return app(ProductionPlanDuplicateService::class);
}

it('groups only items with an identical schedule slot', function () {
    $line = dupLine();
    $a = dupPlan($line);
    $b = dupPlan($line);
    $later = dupPlan($line, ['start_time' => '13:00', 'finish_time' => '17:00']);
    $night = dupPlan($line, ['shift_name' => 'Shift Malam']);
    $otherPress = dupPlan($line, ['press_name' => 'PRESS B']);

    $groups = dupService()->scan('2026-10-26', 'PRESS A', 'Pagi');

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['count'])->toBe(2)
        ->and($groups[0]['confidence'])->toBe('exact')
        ->and(collect($groups[0]['members'])->pluck('id')->all())->toEqualCanonicalizing([$a->id, $b->id]);

    $memberIds = collect($groups[0]['members'])->pluck('id')->all();
    expect($memberIds)->not->toContain($later->id)
        ->and($memberIds)->not->toContain($night->id)
        ->and($memberIds)->not->toContain($otherPress->id);
});

it('ignores break and total rows', function () {
    $line = dupLine();
    dupPlan($line, ['row_type' => 'break', 'job_no' => 'BREAK', 'job_master' => 'ISTIRAHAT']);
    dupPlan($line, ['row_type' => 'break', 'job_no' => 'BREAK', 'job_master' => 'ISTIRAHAT']);
    dupPlan($line, ['row_type' => 'total', 'job_no' => 'TOTAL FINISH', 'job_master' => 'TOTAL']);

    expect(dupService()->scan('2026-10-26', 'PRESS A', 'Pagi'))->toBe([]);
});

it('deletes only the selected duplicate and records the audit trail', function () {
    $line = dupLine();
    $user = dupUser();
    $target = dupPlan($line);
    $kept = dupPlan($line);

    $result = dupService()->deleteDuplicate($target->id, $kept->id, 'Import ganda revisi PPC', $user->id);

    expect($result['success'])->toBeTrue()
        ->and(ProductionPlan::find($target->id))->toBeNull()
        ->and(ProductionPlan::find($kept->id))->not->toBeNull();

    $audit = ProductionPlanDuplicateAudit::first();

    expect($audit)->not->toBeNull()
        ->and($audit->production_plan_id)->toBe($target->id)
        ->and($audit->duplicate_of_id)->toBe($kept->id)
        ->and($audit->deleted_by)->toBe($user->id)
        ->and($audit->reason)->toBe('Import ganda revisi PPC')
        ->and($audit->snapshot['job_no'])->toBe('DUP-1')
        ->and($audit->snapshot['duplicate_of_id'])->toBe($kept->id);
});

it('blocks deletion when the item already has production activity', function () {
    $line = dupLine();
    $user = dupUser();
    $active = dupPlan($line, ['ok' => 10]);
    $clean = dupPlan($line);

    $result = dupService()->deleteDuplicate($active->id, $clean->id, 'Coba hapus item aktif', $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('blocked')
        ->and($result['blockers'])->not->toBeEmpty()
        ->and(ProductionPlan::find($active->id))->not->toBeNull()
        ->and(ProductionPlanDuplicateAudit::count())->toBe(0);
});

it('blocks deletion when the item is not pending', function () {
    $line = dupLine();
    $user = dupUser();
    $approved = dupPlan($line, ['status' => 'approved']);
    $clean = dupPlan($line);

    $result = dupService()->deleteDuplicate($approved->id, $clean->id, 'Coba hapus item approved', $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('blocked')
        ->and(ProductionPlan::find($approved->id))->not->toBeNull();
});

it('blocks deletion when a recovery item still references the plan', function () {
    $line = dupLine();
    $user = dupUser();
    $target = dupPlan($line);
    $peer = dupPlan($line);

    $schedule = RecoverySchedule::create([
        'plan_date' => '2026-10-26',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'status' => 'waiting_approval',
    ]);

    RecoveryItem::create([
        'recovery_schedule_id' => $schedule->id,
        'production_plan_id' => $target->id,
        'job_no' => $target->job_no,
        'job_master' => $target->job_master,
        'press_name' => $target->press_name,
        'plan_qty' => 100,
    ]);

    $result = dupService()->deleteDuplicate($target->id, $peer->id, 'Coba hapus item recovery', $user->id);

    expect($result['success'])->toBeFalse()
        ->and($result['code'])->toBe('blocked')
        ->and(implode(' ', $result['blockers']))->toContain('Recovery')
        ->and(ProductionPlan::find($target->id))->not->toBeNull();
});

it('allows deleting the clean item while keeping the peer that has activity', function () {
    $line = dupLine();
    $user = dupUser();
    $clean = dupPlan($line);
    $active = dupPlan($line, ['ok' => 50]);

    $result = dupService()->deleteDuplicate($clean->id, $active->id, 'Hapus duplikat tanpa progres', $user->id);

    expect($result['success'])->toBeTrue()
        ->and(ProductionPlan::find($clean->id))->toBeNull()
        ->and(ProductionPlan::find($active->id))->not->toBeNull();
});

it('requires manual review confirmation when schedule details differ', function () {
    $line = dupLine();
    $user = dupUser();
    $target = dupPlan($line, ['finish_time' => '12:00']);
    $peer = dupPlan($line, ['finish_time' => '12:30']);

    $first = dupService()->deleteDuplicate($target->id, $peer->id, 'Perbedaan finish time', $user->id);
    expect($first['success'])->toBeFalse()
        ->and($first['code'])->toBe('needs_review')
        ->and(ProductionPlan::find($target->id))->not->toBeNull();

    $second = dupService()->deleteDuplicate($target->id, $peer->id, 'Perbedaan finish time', $user->id, true);
    expect($second['success'])->toBeTrue()
        ->and(ProductionPlan::find($target->id))->toBeNull()
        ->and(ProductionPlan::find($peer->id))->not->toBeNull();
});

it('exposes the duplicate scan and guarded delete endpoints', function () {
    $line = dupLine();
    $user = dupUser();
    $target = dupPlan($line);
    $kept = dupPlan($line);

    $this->actingAs($user)
        ->getJson(route('ppc.planning.production_plan.duplicates', [
            'date' => '2026-10-26',
            'press' => 'PRESS A',
            'shift' => 'Pagi',
        ]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.group_count', 1)
        ->assertJsonPath('data.0.count', 2);

    $this->actingAs($user)
        ->deleteJson(route('ppc.planning.production_plan.duplicate_delete', $target->id), [
            'duplicate_of_id' => $kept->id,
            'reason' => 'Import ganda lewat endpoint',
            'confirm_identity' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(ProductionPlan::find($target->id))->toBeNull()
        ->and(ProductionPlan::find($kept->id))->not->toBeNull()
        ->and(ProductionPlanDuplicateAudit::count())->toBe(1);
});

it('renders the duplicate checker entry point on the planning page', function () {
    $user = dupUser();

    $this->actingAs($user)
        ->get(route('ppc.planning.production_plan', [
            'date' => '2026-10-26',
            'press' => 'PRESS A',
            'shift' => 'Pagi',
        ]))
        ->assertOk()
        ->assertSee('CEK DUPLIKAT', false)
        ->assertSee('duplicateModal', false);
});

it('rejects the delete endpoint without reason and identity confirmation', function () {
    $line = dupLine();
    $user = dupUser();
    $target = dupPlan($line);
    $kept = dupPlan($line);

    $this->actingAs($user)
        ->deleteJson(route('ppc.planning.production_plan.duplicate_delete', $target->id), [
            'duplicate_of_id' => $kept->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason', 'confirm_identity']);

    expect(ProductionPlan::find($target->id))->not->toBeNull();
});
