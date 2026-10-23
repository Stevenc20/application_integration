<?php

use App\Models\LineAssignment;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\Signature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        'name' => ucfirst($role).' Test',
        'nrp' => $role.'-'.uniqid(),
        'password' => 'password',
        'role' => $role,
        'is_active' => 1,
    ]);
}

function sigAssign(int $userId, string $sigRole, string $line, string $shift): void
{
    $data = ['line_name' => $line, 'shift_name' => $shift];
    $data[$sigRole.'_user_id'] = $userId;

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
        $feature = \App\Models\Feature::firstOrCreate(
            ['feature_code' => 'daily_report'],
            [
                'feature_name' => 'Daily Report',
                'group_name' => 'Reports',
            ]
        );
        \App\Models\RoleFeature::firstOrCreate([
            'role' => $role,
            'feature_id' => $feature->id,
        ], [
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

// ──────────────── 10. SIGNER IDENTITY LOCK & 23 OCTOBER 2026 BUGFIX ────────────────

describe('LKH Signer Identity Lock (23 October 2026 Bugfix)', function () {
    test('stores actual signer identity (user_id, signer_name, signer_role, signed_at) and locks it on LKH 23 October 2026', function () {
        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-10-23');
        lkhGrantFeature('leader');
        lkhGrantFeature('foreman');
        lkhGrantFeature('supervisor');

        $leaderActual = User::create([
            'name' => 'Budi Santoso (TL A)',
            'nrp' => 'TL-20261023-A',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => 1,
        ]);
        $foremanActual = User::create([
            'name' => 'Andi Wijaya (Foreman)',
            'nrp' => 'FM-20261023-A',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $supervisorActual = User::create([
            'name' => 'Rudi Hartono (SPV)',
            'nrp' => 'SPV-20261023-A',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);

        LineAssignment::create([
            'line_name' => 'Line A',
            'shift_name' => '1',
            'leader_user_id' => $leaderActual->id,
            'foreman_user_id' => $foremanActual->id,
            'supervisor_user_id' => $supervisorActual->id,
        ]);

        // 1. Team Leader signs slot 1
        $this->actingAs($leaderActual)
            ->postJson('/signature/save', [
                'role' => 'teamleader',
                'work_date' => '2026-10-23',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,TL_SIG_20261023',
            ])
            ->assertOk()
            ->assertJsonPath('signer_name', 'Budi Santoso (TL A)')
            ->assertJsonPath('signed_by_user_id', $leaderActual->id);

        // 2. Foreman signs slot 2
        $this->actingAs($foremanActual)
            ->postJson('/signature/save', [
                'role' => 'foreman',
                'work_date' => '2026-10-23',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,FM_SIG_20261023',
            ])
            ->assertOk()
            ->assertJsonPath('signer_name', 'Andi Wijaya (Foreman)')
            ->assertJsonPath('signed_by_user_id', $foremanActual->id);

        // 3. Supervisor signs slot 3
        $this->actingAs($supervisorActual)
            ->postJson('/signature/save', [
                'role' => 'supervisor',
                'work_date' => '2026-10-23',
                'line_name' => 'PRESS A',
                'shift_name' => 'Shift Pagi',
                'signature' => 'data:image/png;base64,SPV_SIG_20261023',
            ])
            ->assertOk()
            ->assertJsonPath('signer_name', 'Rudi Hartono (SPV)')
            ->assertJsonPath('signed_by_user_id', $supervisorActual->id);

        // Verify DB persistence of all 3 slots
        $tlSig = Signature::where('role', 'teamleader')->where('work_date', '2026-10-23')->first();
        $fmSig = Signature::where('role', 'foreman')->where('work_date', '2026-10-23')->first();
        $spvSig = Signature::where('role', 'supervisor')->where('work_date', '2026-10-23')->first();

        expect($tlSig->signed_by_user_id)->toBe($leaderActual->id)
            ->and($tlSig->signer_name)->toBe('Budi Santoso (TL A)')
            ->and($tlSig->signed_at)->not->toBeNull();
        expect($fmSig->signed_by_user_id)->toBe($foremanActual->id)
            ->and($fmSig->signer_name)->toBe('Andi Wijaya (Foreman)')
            ->and($fmSig->signed_at)->not->toBeNull();
        expect($spvSig->signed_by_user_id)->toBe($supervisorActual->id)
            ->and($spvSig->signer_name)->toBe('Rudi Hartono (SPV)')
            ->and($spvSig->signed_at)->not->toBeNull();
    });

    test('opening LKH or regenerating PDF as different logged-in users never changes locked signer identities on 23 October 2026', function () {
        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-10-23');
        lkhGrantFeature('leader');
        lkhGrantFeature('foreman');
        lkhGrantFeature('supervisor');

        $leaderActual = User::create([
            'name' => 'Budi Santoso (TL A)',
            'nrp' => 'TL-ACTUAL',
            'password' => 'password',
            'role' => 'leader a',
            'is_active' => 1,
        ]);
        $foremanActual = User::create([
            'name' => 'Andi Wijaya (Foreman 1)',
            'nrp' => 'FM-ACTUAL',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $supervisorActual = User::create([
            'name' => 'Rudi Hartono (SPV 1)',
            'nrp' => 'SPV-ACTUAL',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);

        // Different users with the same roles who later open the LKH / export PDF
        $otherLeader = User::create([
            'name' => 'Doni Pratama (Other Leader)',
            'nrp' => 'TL-OTHER',
            'password' => 'password',
            'role' => 'leader b',
            'is_active' => 1,
        ]);
        $otherForeman = User::create([
            'name' => 'Hendra Kusuma (Other Foreman)',
            'nrp' => 'FM-OTHER',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $otherSupervisor = User::create([
            'name' => 'Surya Saputra (Other SPV)',
            'nrp' => 'SPV-OTHER',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);

        LineAssignment::create([
            'line_name' => 'Line A',
            'shift_name' => '1',
            'leader_user_id' => $leaderActual->id,
            'foreman_user_id' => $foremanActual->id,
            'supervisor_user_id' => $supervisorActual->id,
        ]);

        $this->actingAs($leaderActual)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,TL_SIG',
        ])->assertOk();

        $this->actingAs($foremanActual)->postJson('/signature/save', [
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,FM_SIG',
        ])->assertOk();

        $this->actingAs($supervisorActual)->postJson('/signature/save', [
            'role' => 'supervisor',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,SPV_SIG',
        ])->assertOk();

        // Open LKH web page as Other Foreman, Other Supervisor, and Other Leader
        foreach ([$otherForeman, $otherSupervisor, $otherLeader] as $viewer) {
            $response = $this->actingAs($viewer)
                ->get(route('supervisor.reports.daily_production', [
                    'date' => '2026-10-23',
                    'line' => 'PRESS A',
                    'shift' => 'Shift Pagi',
                ]))
                ->assertOk();

            $sigStatus = $response->viewData('signatureStatus');
            expect($sigStatus['teamleader']['name'])->toBe('Budi Santoso (TL A)')
                ->and($sigStatus['foreman']['name'])->toBe('Andi Wijaya (Foreman 1)')
                ->and($sigStatus['supervisor']['name'])->toBe('Rudi Hartono (SPV 1)');

            $html = $response->getContent();
            expect($html)->toContain('id="ttd_teamleader" name="ttd_teamleader"')
                ->and($html)->toContain('value="Budi Santoso (TL A)"')
                ->and($html)->toContain('value="Andi Wijaya (Foreman 1)"')
                ->and($html)->toContain('value="Rudi Hartono (SPV 1)"')
                ->and($html)->not->toContain('value="Doni Pratama (Other Leader)"')
                ->and($html)->not->toContain('value="Hendra Kusuma (Other Foreman)"')
                ->and($html)->not->toContain('value="Surya Saputra (Other SPV)"');
        }
    });

    test('another user cannot overwrite an already-valid signature via normal submit', function () {
        sigCreateLineMaster('PRESS A');

        $foreman1 = User::create([
            'name' => 'Foreman Satu',
            'nrp' => 'FM-001',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $foreman2 = User::create([
            'name' => 'Foreman Dua',
            'nrp' => 'FM-002',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $leader = sigCreateUser('leader');
        sigAssign($leader->id, 'leader', 'Line A', '1');

        $this->actingAs($leader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,LEADER_SIG',
        ])->assertOk();

        // Foreman 1 signs
        $this->actingAs($foreman1)->postJson('/signature/save', [
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,FOREMAN1_SIG',
        ])->assertOk();

        // Foreman 2 tries to overwrite Foreman 1's signature via normal submit -> rejected with 403
        $this->actingAs($foreman2)->postJson('/signature/save', [
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,FOREMAN2_OVERWRITE',
        ])->assertStatus(403);

        // Verify Foreman 1's identity and signature remain intact
        $sig = Signature::where('role', 'foreman')->where('work_date', '2026-10-23')->first();
        expect($sig->signed_by_user_id)->toBe($foreman1->id)
            ->and($sig->signer_name)->toBe('Foreman Satu')
            ->and($sig->signature_data)->toBe('data:image/png;base64,FOREMAN1_SIG');
    });

    test('historical signatures without stored signer_name resolve deterministically from ShiftSubmission and LineAssignment instead of active user', function () {
        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-10-23');
        lkhGrantFeature('foreman');

        $submitterLeader = User::create([
            'name' => 'Agus Salim (Shift Submitter)',
            'nrp' => 'TL-SUBMITTER',
            'password' => 'password',
            'role' => 'leader a',
            'is_active' => 1,
        ]);
        $assignedForeman = User::create([
            'name' => 'Bambang Pamungkas (Assigned Foreman)',
            'nrp' => 'FM-ASSIGNED',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $viewerForeman = User::create([
            'name' => 'Viewer Foreman Lain',
            'nrp' => 'FM-VIEWER',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);

        LineAssignment::create([
            'line_name' => 'Line A',
            'shift_name' => '1',
            'leader_user_id' => $submitterLeader->id,
            'foreman_user_id' => $assignedForeman->id,
        ]);

        \App\Models\ShiftSubmission::create([
            'line_id' => $line->id,
            'work_date' => '2026-10-23',
            'shift' => 1,
            'submitted_at' => '2026-10-23 15:00:00',
            'submitted_by' => $submitterLeader->id,
        ]);

        // Simulate legacy pre-migration signatures on 2026-10-23 (null signer_name & signed_by_user_id)
        Signature::create([
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature_data' => 'data:image/png;base64,LEGACY_TL',
        ]);
        Signature::create([
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature_data' => 'data:image/png;base64,LEGACY_FM',
        ]);

        // Viewed by a different foreman (`$viewerForeman`)
        $response = $this->actingAs($viewerForeman)
            ->get(route('supervisor.reports.daily_production', [
                'date' => '2026-10-23',
                'line' => 'PRESS A',
                'shift' => 'Shift Pagi',
            ]))
            ->assertOk();

        $sigStatus = $response->viewData('signatureStatus');
        expect($sigStatus['teamleader']['name'])->toBe('Agus Salim (Shift Submitter)')
            ->and($sigStatus['foreman']['name'])->toBe('Bambang Pamungkas (Assigned Foreman)')
            ->and($sigStatus['foreman']['name'])->not->toBe('Viewer Foreman Lain');
    });

    test('PDF template preserves mandatory 1. Team Leader -> 2. Foreman -> 3. Supervisor order with locked names', function () {
        $html = view('reports.daily_production_pdf', [
            'jobsData' => [],
            'totals' => [
                'plan_qty' => 100,
                'actual_good' => 100,
                'actual_repair' => 0,
                'actual_reject' => 0,
                'total_stroke' => 100,
                'total_loading_time' => 480.0,
                'total_operating_time' => 450.0,
                'press_time' => 400.0,
                'total_dandori' => 20.0,
                'total_qcheck' => 10.0,
                'downtime_total' => 20.0,
                'total_report_tpt' => 450.0,
                'total_panel_record_ct' => 10.0,
                'total_break_time' => 30.0,
                'total_work_time' => 480.0,
                'downtime_prod' => 5.0,
                'downtime_dies' => 5.0,
                'downtime_mach' => 5.0,
                'downtime_matl' => 5.0,
                'downtime_log' => 0.0,
                'downtime_ubp' => 0.0,
                'tpt_plan' => 450.0,
                'tpt_act' => 450.0,
            ],
            'summary' => [
                'oee' => 95.0,
                'availability' => 95.0,
                'performance' => 100.0,
                'quality' => 100.0,
            ],
            'lineNamesUnique' => collect(['PRESS A']),
            'selectedLineName' => 'PRESS A',
            'selectedShift' => 'Shift Pagi',
            'latestShiftName' => 'Shift Pagi',
            'date' => '2026-10-23',
            'operationalHours' => '8',
            'shiftDisplayStart' => Carbon::parse('2026-10-23 07:30:00'),
            'shiftDisplayEnd' => Carbon::parse('2026-10-23 21:00:00'),
            'signatureStatus' => [
                'teamleader' => ['signed' => true, 'available' => true, 'name' => 'Budi Santoso (TL A)'],
                'foreman' => ['signed' => true, 'available' => true, 'name' => 'Andi Wijaya (Foreman)'],
                'supervisor' => ['signed' => true, 'available' => true, 'name' => 'Rudi Hartono (SPV)'],
            ],
            'canEdit' => false,
            'ttdLocked' => true,
            'userRole' => 'supervisor',
            'authorizedScope' => true,
        ])->render();

        $posTlLabel = strpos($html, 'Team Leader');
        $posFmLabel = strpos($html, 'Foreman');
        $posSpvLabel = strpos($html, 'Supervisor');

        $posTlName = strpos($html, 'Budi Santoso (TL A)');
        $posFmName = strpos($html, 'Andi Wijaya (Foreman)');
        $posSpvName = strpos($html, 'Rudi Hartono (SPV)');

        expect($posTlLabel)->toBeLessThan($posFmLabel)
            ->and($posFmLabel)->toBeLessThan($posSpvLabel)
            ->and($posTlName)->toBeLessThan($posFmName)
            ->and($posFmName)->toBeLessThan($posSpvName);
    });

    test('resolves Sapriadi (Supervisor) and Giyono (Team Leader) over seeded Eko Supervisor / Candra Leader on 23 October 2026', function () {
        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-10-23');
        lkhGrantFeature('supervisor');
        lkhGrantFeature('foreman');
        lkhGrantFeature('leader');

        // Seeded default dummy users created first (lowest IDs, just like DatabaseSeeder)
        $candraSeeded = User::create([
            'name' => 'Candra Leader',
            'nrp' => '3456',
            'password' => 'password',
            'role' => 'leader a',
            'is_active' => 1,
        ]);
        $dediSeeded = User::create([
            'name' => 'Dedi Foreman',
            'nrp' => '4567',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $ekoSeeded = User::create([
            'name' => 'Eko Supervisor',
            'nrp' => '5678',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);

        // Real operational users created after seeder (including Bayu & Tejo with higher IDs)
        $giyonoLeader = User::create([
            'name' => 'Giyono',
            'nrp' => 'TL-GIYONO',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => 1,
        ]);
        $tejoLeader = User::create([
            'name' => 'Tejo',
            'nrp' => 'TL-TEJO',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => 1,
        ]);
        $sapriadiSpv = User::create([
            'name' => 'Sapriadi',
            'nrp' => 'SPV-SAPRIADI',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);
        $bayuSpv = User::create([
            'name' => 'Bayu BR',
            'nrp' => 'SPV-BAYU',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);

        // Historical signatures created on 2026-10-23 before migration (or where Bayu/Tejo/Eko was previously backfilled)
        $tlSig = Signature::create([
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature_data' => 'data:image/png;base64,GIYONO_SIG',
            'signed_by_user_id' => $tejoLeader->id,
            'signer_name' => 'Tejo',
            'signer_role' => 'leader',
        ]);
        $fmSig = Signature::create([
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature_data' => 'data:image/png;base64,FM_SIG',
        ]);
        $spvSig = Signature::create([
            'role' => 'supervisor',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature_data' => 'data:image/png;base64,SAPRIADI_SIG',
            'signed_by_user_id' => $bayuSpv->id,
            'signer_name' => 'Bayu BR',
            'signer_role' => 'supervisor',
        ]);

        // Even when opened by Eko Supervisor or Dedi Foreman, LKH displays Giyono and Sapriadi
        $response = $this->actingAs($ekoSeeded)
            ->get(route('supervisor.reports.daily_production', [
                'date' => '2026-10-23',
                'line' => 'PRESS A',
                'shift' => 'Shift Pagi',
            ]))
            ->assertOk();

        $sigStatus = $response->viewData('signatureStatus');
        expect($sigStatus['teamleader']['name'])->toBe('Giyono')
            ->and($sigStatus['supervisor']['name'])->toBe('Sapriadi')
            ->and($sigStatus['supervisor']['name'])->not->toBe('Eko Supervisor');

        // And the DB record is self-healed to lock Sapriadi & Giyono permanently
        expect($tlSig->fresh()->signer_name)->toBe('Giyono')
            ->and($tlSig->fresh()->signed_by_user_id)->toBe($giyonoLeader->id)
            ->and($spvSig->fresh()->signer_name)->toBe('Sapriadi')
            ->and($spvSig->fresh()->signed_by_user_id)->toBe($sapriadiSpv->id);
    });

    test('only the actual signer (or superadmin) can delete a signature across Team Leader, Foreman, and Supervisor', function () {
        $line = sigCreateLineMaster('PRESS A');
        sigMakePlan($line, '2026-10-23');
        lkhGrantFeature('supervisor');
        lkhGrantFeature('foreman');
        lkhGrantFeature('leader');

        $giyonoLeader = User::create([
            'name' => 'Giyono',
            'nrp' => 'TL-GIYONO-DEL',
            'password' => 'password',
            'role' => 'leader a',
            'is_active' => 1,
        ]);
        $otherLeader = User::create([
            'name' => 'Tejo',
            'nrp' => 'TL-TEJO-DEL',
            'password' => 'password',
            'role' => 'leader a',
            'is_active' => 1,
        ]);

        $foremanActual = User::create([
            'name' => 'Sukoyo',
            'nrp' => 'FM-SUKOYO-DEL',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);
        $otherForeman = User::create([
            'name' => 'Dedi Foreman',
            'nrp' => 'FM-DEDI-DEL',
            'password' => 'password',
            'role' => 'foreman',
            'is_active' => 1,
        ]);

        $sapriadiSpv = User::create([
            'name' => 'Sapriadi',
            'nrp' => 'SPV-SAPRIADI-DEL',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);
        $ekoSpv = User::create([
            'name' => 'Eko Supervisor',
            'nrp' => 'SPV-EKO-DEL',
            'password' => 'password',
            'role' => 'supervisor',
            'is_active' => 1,
        ]);

        LineAssignment::create([
            'line_name' => 'Line A',
            'shift_name' => '1',
            'leader_user_id' => $giyonoLeader->id,
            'foreman_user_id' => $foremanActual->id,
            'supervisor_user_id' => $sapriadiSpv->id,
        ]);

        // 1. Giyono signs Team Leader
        $this->actingAs($giyonoLeader)->postJson('/signature/save', [
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,GIYONO_SIG',
        ])->assertOk();

        // Other Team Leader (Tejo) CANNOT delete Giyono's signature
        $this->actingAs($otherLeader)->postJson('/signature/delete', [
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ])->assertStatus(403);

        // 2. Sukoyo signs Foreman
        $this->actingAs($foremanActual)->postJson('/signature/save', [
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,SUKOYO_SIG',
        ])->assertOk();

        // Other Foreman (Dedi) CANNOT delete Sukoyo's signature
        $this->actingAs($otherForeman)->postJson('/signature/delete', [
            'role' => 'foreman',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ])->assertStatus(403);

        // Even Giyono (actual Team Leader signer) CANNOT delete his own signature once Foreman has already signed
        $this->actingAs($giyonoLeader)->postJson('/signature/delete', [
            'role' => 'teamleader',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ])->assertStatus(422);

        // 3. Sapriadi signs Supervisor
        $this->actingAs($sapriadiSpv)->postJson('/signature/save', [
            'role' => 'supervisor',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
            'signature' => 'data:image/png;base64,SAPRIADI_SIG',
        ])->assertOk();

        // Eko Supervisor CANNOT delete Sapriadi's signature
        $this->actingAs($ekoSpv)->postJson('/signature/delete', [
            'role' => 'supervisor',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ])->assertStatus(403);

        // And on the LKH page viewed by Eko Supervisor, the delete button for Supervisor is hidden
        $ekoView = $this->actingAs($ekoSpv)
            ->get(route('supervisor.reports.daily_production', [
                'date' => '2026-10-23',
                'line' => 'PRESS A',
                'shift' => 'Shift Pagi',
            ]))
            ->assertOk()
            ->getContent();

        expect($ekoView)->toContain('id="btn_delete_ttd_supervisor"')
            ->and(Signature::where('work_date', '2026-10-23')->count())->toBe(3);

        // Sapriadi (actual Supervisor signer) CAN delete his own Supervisor signature
        $this->actingAs($sapriadiSpv)->postJson('/signature/delete', [
            'role' => 'supervisor',
            'work_date' => '2026-10-23',
            'line_name' => 'PRESS A',
            'shift_name' => 'Shift Pagi',
        ])->assertOk();

        expect(Signature::where('work_date', '2026-10-23')->where('role', 'supervisor')->exists())->toBeFalse();
    });
});
