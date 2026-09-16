<?php

use App\Models\User;
use App\Models\Signature;
use App\Models\LineMaster;
use App\Models\LineAssignment;
use App\Models\ProductionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

function sigCreateLineMaster(string $lineName): LineMaster
{
    return LineMaster::create([
        'line_code' => strtoupper(str_replace(' ', '_', $lineName)),
        'line_name' => $lineName,
        'status' => 'active',
    ]);
}

function sigCreateUser(string $role): User
{
    return User::create([
        'name' => ucfirst($role) . ' Test',
        'nrp' => $role . '-' . uniqid(),
        'password' => 'password',
        'role' => $role,
        'is_active' => 1,
    ]);
}

function sigAssign(int $userId, string $sigRole, string $line, string $shift): void
{
    $data = ['line_name' => $line, 'shift_name' => $shift];
    $data[$sigRole . '_user_id'] = $userId;

    LineAssignment::create($data);
}

function sigMakePlan(LineMaster $line, string $date): void
{
    ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => $line->line_name,
        'row_no' => 10,
        'row_type' => 'job',
        'job_no' => 'SIG-TEST-1',
        'plan' => 100,
        'ok' => 0,
    ]);
}

// ──────────────── 1. AUTHORIZATION: ALLOW ────────────────

describe('Signature Authorization Allow', function () {
    test('leader assigned raw "Line A"/"1" can sign master-standard "PRESS A"/"Shift Pagi"', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');

        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertOk();

        $sig = Signature::first();
        expect($sig)->not->toBeNull();
        expect($sig->line_name)->toBe('PRESS A');
        expect($sig->shift_name)->toBe('Shift Pagi');
    });

    test('leader assigned bare "A"/"2" can sign "PRESS A"/"Shift Malam"', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');

        sigAssign($leader->id, 'leader', 'A', '2');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Malam',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertOk();
    });

    test('leader assigned "PRESS A"/"Shift Pagi" (same format) can sign directly', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');

        sigAssign($leader->id, 'leader', 'PRESS A', 'Shift Pagi');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertOk();
    });

    test('superadmin bypasses line/shift authorization entirely', function () {
        $admin = sigCreateUser('superadmin');

        $this->actingAs($admin)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertOk();
    });

    test('leader without assignment is denied', function () {
        $leader = sigCreateUser('leader');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'Anda tidak memiliki otorisasi untuk Line dan Shift ini');
    });
});

// ──────────────── 2. AUTHORIZATION: ISOLATION (Line + Shift) ────────────────

describe('Signature Isolation', function () {
    test('leader of Line A cannot sign Line B', function () {
        sigCreateLineMaster('PRESS A');
        sigCreateLineMaster('PRESS B');
        $leader = sigCreateUser('leader');

        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS B',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertStatus(403);

        expect(Signature::count())->toBe(0);
    });

    test('leader assigned Shift Pagi cannot sign Shift Malam on the same line', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');

        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Malam',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertStatus(403);
    });

    test('leader assigned Line A cannot sign PRESS A after forging a different shift value', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');

        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-09-16',
                'line_name' => 'PRESS A',
                'shift_name' => '3',
                'signature' => 'data:image/png;base64,AAAA',
            ])
            ->assertStatus(403);
    });
});

// ──────────────── 3. SIGNATURE CHAIN (Leader → Foreman → Supervisor) ────────────────

describe('Signature Chain', function () {
    function sigTeam(): array
    {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        $foreman = sigCreateUser('foreman');
        $supervisor = sigCreateUser('supervisor');

        $assignment = LineAssignment::create([
            'line_name' => 'Line A',
            'shift_name' => '1',
            'leader_user_id' => $leader->id,
            'foreman_user_id' => $foreman->id,
            'supervisor_user_id' => $supervisor->id,
        ]);

        return compact('leader', 'foreman', 'supervisor', 'assignment');
    }

    function sigPayload(string $role): array
    {
        return [
            'role' => $role,
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ];
    }

    test('foreman cannot sign before leader signs', function () {
        $team = sigTeam();

        $this->actingAs($team['foreman'])
            ->postJson('/signature/save', sigPayload('foreman'))
            ->assertStatus(422)
            ->assertJsonPath('error', 'Harap TTD oleh Teamleader terlebih dahulu');
    });

    test('foreman can sign after leader signs', function () {
        $team = sigTeam();

        $this->actingAs($team['leader'])->postJson('/signature/save', sigPayload('teamleader'))->assertOk();
        $this->actingAs($team['foreman'])->postJson('/signature/save', sigPayload('foreman'))->assertOk();
    });

    test('supervisor requires both leader and foreman first', function () {
        $team = sigTeam();

        $this->actingAs($team['supervisor'])
            ->postJson('/signature/save', sigPayload('supervisor'))
            ->assertStatus(422);

        $this->actingAs($team['leader'])->postJson('/signature/save', sigPayload('teamleader'))->assertOk();

        $this->actingAs($team['supervisor'])
            ->postJson('/signature/save', sigPayload('supervisor'))
            ->assertStatus(422);

        $this->actingAs($team['foreman'])->postJson('/signature/save', sigPayload('foreman'))->assertOk();
        $this->actingAs($team['supervisor'])->postJson('/signature/save', sigPayload('supervisor'))->assertOk();

        expect(Signature::count())->toBe(3);
    });

    test('foreman cannot sign a role they do not own', function () {
        $team = sigTeam();

        $this->actingAs($team['leader'])->postJson('/signature/save', sigPayload('teamleader'))->assertOk();
        $this->actingAs($team['foreman'])
            ->postJson('/signature/save', sigPayload('supervisor'))
            ->assertStatus(403);
    });
});

// ──────────────── 4. PENDING NOTIFICATION (bell) ────────────────

describe('Signature Pending Notification', function () {
    test('pending resolves raw assignment into standardized line/shift', function () {
        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));

        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-09-16');

        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->getJson('/signature/pending')
            ->assertOk()
            ->assertJson([
                'pending' => true,
                'role' => 'teamleader',
                'lineName' => 'PRESS A',
                'date' => '2026-09-16',
            ]);
    });

    test('pending returns false once the role has signed for that line/shift', function () {
        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));

        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-09-16');

        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $this->actingAs($leader)
            ->getJson('/signature/pending')
            ->assertOk()
            ->assertJson(['pending' => false]);
    });

    test('pending returns false for users without a matching assignment', function () {
        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));

        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-09-16');

        $user = sigCreateUser('leader');

        $this->actingAs($user)
            ->getJson('/signature/pending')
            ->assertOk()
            ->assertJson(['pending' => false]);
    });
});

// ──────────────── 5. ENDPOINTS (get / status) ────────────────

describe('Signature Endpoints', function () {
    test('status reflects signed roles after save', function () {
        $line = sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $this->actingAs($leader)
            ->getJson('/signature/status?work_date=2026-09-16&line_name=PRESS A&shift_name=Shift Pagi')
            ->assertOk()
            ->assertJson([
                'teamleader' => ['signed' => true, 'available' => true],
                'foreman' => ['signed' => false, 'available' => true],
                'supervisor' => ['signed' => false, 'available' => false],
            ]);
    });

    test('get returns the stored signature data', function () {
        $line = sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $this->actingAs($leader)
            ->getJson('/signature/get?role=teamleader&work_date=2026-09-16&line_name=PRESS A&shift_name=Shift Pagi')
            ->assertOk()
            ->assertJson([
                'signature' => 'data:image/png;base64,AAAA',
            ]);
    });
});

// ──────────────── 6. READ/WRITE AUTHORIZATION (get / status) ────────────────

describe('Signature Read Authorization', function () {
    function readSetup(): array
    {
        sigCreateLineMaster('PRESS A');
        sigCreateLineMaster('PRESS B');
        $leaderA = sigCreateUser('leader');
        $other = sigCreateUser('leader');
        sigAssign($leaderA->id, 'leader', 'Line A', '1');
        return compact('leaderA', 'other');
    }

    test('get returns 403 for an unassigned user on the requested scope', function () {
        $users = readSetup();

        $this->actingAs($users['other'])
            ->getJson('/signature/get?role=teamleader&work_date=2026-09-16&line_name=PRESS A&shift_name=Shift Pagi')
            ->assertStatus(403);
    });

    test('get returns 403 when assigned to a different line', function () {
        $users = readSetup();

        $this->actingAs($users['leaderA'])
            ->getJson('/signature/get?role=teamleader&work_date=2026-09-16&line_name=PRESS B&shift_name=Shift Pagi')
            ->assertStatus(403);
    });

    test('get returns 403 when assigned Shift Pagi but requesting Shift Malam', function () {
        $users = readSetup();

        $this->actingAs($users['leaderA'])
            ->getJson('/signature/get?role=teamleader&work_date=2026-09-16&line_name=PRESS A&shift_name=Shift Malam')
            ->assertStatus(403);
    });

    test('get is allowed on own scope and returns the stored signature', function () {
        $users = readSetup();

        $this->actingAs($users['leaderA'])->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,BBBB',
        ])->assertOk();

        $this->actingAs($users['leaderA'])
            ->getJson('/signature/get?role=teamleader&work_date=2026-09-16&line_name=PRESS A&shift_name=Shift Pagi')
            ->assertOk()
            ->assertJson(['signature' => 'data:image/png;base64,BBBB']);
    });

    test('superadmin can read any scope', function () {
        $leaderA = sigCreateUser('leader');
        sigAssign($leaderA->id, 'leader', 'Line A', '1');
        $this->actingAs($leaderA)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,BBBB',
        ])->assertOk();

        $admin = sigCreateUser('superadmin');
        $this->actingAs($admin)
            ->getJson('/signature/get?role=teamleader&work_date=2026-09-16&line_name=PRESS A&shift_name=Shift Pagi')
            ->assertOk()
            ->assertJson(['signature' => 'data:image/png;base64,BBBB']);
    });

    test('status returns 403 for a scope the user is not authorized for', function () {
        $users = readSetup();

        $this->actingAs($users['leaderA'])
            ->getJson('/signature/status?work_date=2026-09-16&line_name=PRESS B&shift_name=Shift Pagi')
            ->assertStatus(403);
    });
});

// ──────────────── 7. DELETE ISOLATION ────────────────

describe('Signature Delete Isolation', function () {
    function sigDeletePayload(array $overrides = []): array
    {
        return array_merge([
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ], $overrides);
    }

    test('leader can delete an existing signature on own scope', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $this->actingAs($leader)
            ->postJson('/signature/delete', sigDeletePayload())
            ->assertOk();

        expect(Signature::count())->toBe(0);
    });

    test('delete returns 403 on a line the user does not own', function () {
        sigCreateLineMaster('PRESS A');
        sigCreateLineMaster('PRESS B');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/delete', sigDeletePayload(['line_name' => 'PRESS B']))
            ->assertStatus(403);
    });

    test('delete returns 403 on a shift the user does not own', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/delete', sigDeletePayload(['shift_name' => 'Shift Malam']))
            ->assertStatus(403);
    });

    test('delete returns 404 when no signature exists on own scope', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)
            ->postJson('/signature/delete', sigDeletePayload())
            ->assertStatus(404);
    });

    test('leader cannot delete a signature for a role they do not own', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        Signature::create([
            'role' => 'supervisor',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature_data' => 'data:image/png;base64,AAAA',
        ]);

        $this->actingAs($leader)
            ->postJson('/signature/delete', sigDeletePayload(['role' => 'supervisor']))
            ->assertStatus(403);
    });
});

// ──────────────── 8. CANONICALIZATION / DEDUPE ────────────────

describe('Signature Canonicalization', function () {
    test('repeated saves with different raw spellings dedupe to a single canonical row', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $payload = [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'signature' => 'data:image/png;base64,AAAA',
        ];

        $this->actingAs($leader)->postJson('/signature/save', array_merge($payload, [
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ]))->assertOk();

        $this->actingAs($leader)->postJson('/signature/save', array_merge($payload, [
            'line_name' => 'Line A',
            'shift_name' => 'S1',
        ]))->assertOk();

        expect(Signature::count())->toBe(1);
        expect(Signature::first()->line_name)->toBe('PRESS A');
        expect(Signature::first()->shift_name)->toBe('Shift Pagi');
    });

    test('single row dedupe holds even when request raw differs between formats', function () {
        sigCreateLineMaster('PRESS A');
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'A', '2');
        $this->actingAs($leader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Malam',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();
        expect(Signature::count())->toBe(1);
        expect(Signature::first()->line_name)->toBe('PRESS A');
        expect(Signature::first()->shift_name)->toBe('Shift Malam');
    });
});

// ──────────────── 9. LKH EDIT LOCK SCOPED BY LINE + SHIFT ────────────────

describe('LKH Update Cells Scope Lock', function () {
    function lkhGrantFeature(string $role): void
    {
        $feature = \App\Models\Feature::create([
            'feature_code' => 'daily_report',
            'feature_name' => 'Daily Report',
            'group_name' => 'Reports',
        ]);
        \App\Models\RoleFeature::create([
            'role' => $role,
            'feature_id' => $feature->id,
            'enabled' => true,
        ]);
    }

    function lkhTeam(): array
    {
        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-09-16');
        $leader = sigCreateUser('leader');
        $foreman = sigCreateUser('foreman');

        LineAssignment::create([
            'line_name' => 'Line A',
            'shift_name' => '1',
            'leader_user_id' => $leader->id,
            'foreman_user_id' => $foreman->id,
        ]);

        lkhGrantFeature('foreman');

        return compact('leader', 'foreman', 'line');
    }

    test('foreman can edit after leader signs on the same line+shift', function () {
        $team = lkhTeam();

        $this->actingAs($team['leader'])->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $plan = ProductionPlan::where('job_no', 'SIG-TEST-1')->first();

        $this->actingAs($team['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date' => '2026-09-16',
                'line' => 'PRESS A',
                'shift' => 'Shift Pagi',
                'updates' => [['plan_id' => $plan->id, 'field' => 'actual_start', 'value' => '08:00']],
            ])
            ->assertOk();
    });

    test('lock is scoped: leader signed PRESS A/Pagi, edit PRESS B/Malam stays locked', function () {
        $team = lkhTeam();
        sigCreateLineMaster('PRESS B');

        $this->actingAs($team['leader'])->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-09-16',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,AAAA',
        ])->assertOk();

        $plan = ProductionPlan::where('job_no', 'SIG-TEST-1')->first();

        $this->actingAs($team['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date' => '2026-09-16',
                'line' => 'PRESS B',
                'shift' => 'Shift Malam',
                'updates' => [['plan_id' => $plan->id, 'field' => 'actual_start', 'value' => '08:00']],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Edit hanya terbuka setelah TTD Team Leader');
    });

    test('payload without line or shift is rejected', function () {
        $team = lkhTeam();

        $this->actingAs($team['foreman'])
            ->postJson(route('supervisor.reports.daily_production.update_cells'), [
                'date' => '2026-09-16',
                'updates' => [['plan_id' => 1, 'field' => 'actual_start', 'value' => '08:00']],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Payload tidak valid');
    });
});