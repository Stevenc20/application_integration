<?php

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\ScheduleBackup;
use App\Models\ScheduleRevision;
use App\Models\Signature;
use App\Models\User;
use App\Services\PpcScheduleMergeService;
use App\Services\ScheduleBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function backupTestLine(string $code = 'L-A', string $name = 'PRESS A'): LineMaster
{
    return LineMaster::firstOrCreate(
        ['line_name' => $name],
        ['line_code' => $code, 'status' => 'active']
    );
}

function backupTestUser(): User
{
    return User::create([
        'name' => 'PPC Admin',
        'nrp' => 'PPC-999',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);
}

function backupSheetRow(array $overrides = []): array
{
    return array_merge([
        'job_master' => 'PART ALPHA',
        'job_no' => 'JA-01',
        'row_no' => 1,
        'plan' => 200,
        'qty_plt' => 20,
        'keb_mtl' => 1,
        'total_plt' => 10,
        'ct' => 5,
        'process_time' => 50,
        'reg_active' => 0,
        'dct' => 0,
        'mct' => 0,
        'plan_dct' => 50,
        'tpt' => 100,
        'gsph' => 200,
        'start_time' => '07:30',
        'finish_time' => '11:30',
        'keterangan' => null,
        'p1' => false,
        'p2' => false,
        'p3' => false,
        'p4' => false,
        'ok' => 0,
        'reject' => 0,
    ], $overrides);
}

test('first schedule import creates an initial backup and subsequent revision import creates a pre_import backup', function () {
    backupTestLine();
    $user = backupTestUser();
    $date = '2026-10-23';
    $mergeService = app(PpcScheduleMergeService::class);

    // 1. Initial import on an empty date
    $mergeService->merge([
        [
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS A',
            'rows' => [
                backupSheetRow(['job_master' => 'PART ALPHA', 'job_no' => 'JA-01', 'row_no' => 1, 'plan' => 200]),
                backupSheetRow(['job_master' => 'PART BETA', 'job_no' => 'JB-02', 'row_no' => 2, 'plan' => 150]),
            ],
        ],
    ], $date, $user->id, ['source_filename' => 'initial_oct23.xlsx']);

    $backupsAfterFirst = ScheduleBackup::whereDate('plan_date', $date)->orderBy('id')->get();
    expect($backupsAfterFirst)->toHaveCount(1)
        ->and($backupsAfterFirst->first()->backup_type)->toBe('initial')
        ->and($backupsAfterFirst->first()->plans_count)->toBe(2)
        ->and($backupsAfterFirst->first()->source_filename)->toBe('initial_oct23.xlsx');

    // 2. Second import (Revision)
    $mergeService->merge([
        [
            'shift_name' => 'Shift Pagi (Rev)',
            'press_name' => 'PRESS A',
            'rows' => [
                backupSheetRow(['job_master' => 'PART ALPHA', 'job_no' => 'JA-01', 'row_no' => 1, 'plan' => 280]),
                backupSheetRow(['job_master' => 'PART GAMMA', 'job_no' => 'JG-03', 'row_no' => 3, 'plan' => 90]),
            ],
        ],
    ], $date, $user->id, ['source_filename' => 'rev1_oct23.xlsx']);

    $backupsAfterSecond = ScheduleBackup::whereDate('plan_date', $date)->orderBy('id')->get();
    expect($backupsAfterSecond)->toHaveCount(2);

    $preImportBackup = $backupsAfterSecond->last();
    expect($preImportBackup->backup_type)->toBe('pre_import')
        ->and($preImportBackup->plans_count)->toBe(2)
        ->and($preImportBackup->source_filename)->toBe('rev1_oct23.xlsx');
});

test('transactional restore recovers original schedule while preserving post-snapshot production actuals, logs, downtimes, and LKH signatures', function () {
    $line = backupTestLine();
    $user = backupTestUser();
    $date = '2026-10-23';
    $mergeService = app(PpcScheduleMergeService::class);
    $backupService = app(ScheduleBackupService::class);

    // Step 1: Initial schedule import (Item A = 200 pcs, Item B = 150 pcs)
    $mergeService->merge([
        [
            'shift_name' => 'Shift Pagi',
            'press_name' => 'PRESS A',
            'rows' => [
                backupSheetRow(['job_master' => 'PART ALPHA', 'job_no' => 'JA-01', 'row_no' => 1, 'plan' => 200, 'start_time' => '07:30', 'finish_time' => '11:30']),
                backupSheetRow(['job_master' => 'PART BETA', 'job_no' => 'JB-02', 'row_no' => 2, 'plan' => 150, 'start_time' => '13:00', 'finish_time' => '16:00']),
            ],
        ],
    ], $date, $user->id, ['source_filename' => 'pagi_awal.xlsx']);

    $initialBackup = ScheduleBackup::whereDate('plan_date', $date)
        ->where('backup_type', 'initial')
        ->firstOrFail();

    $planAlpha = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JA-01')->firstOrFail();
    $planBeta = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JB-02')->firstOrFail();
    $jobAlpha = JobMaster::firstOrCreate(
        ['job_number' => 'JA-01-'.$planAlpha->id],
        [
            'job_name' => $planAlpha->job_master,
            'line' => $planAlpha->press_name,
            'target_qty' => (int) $planAlpha->plan,
            'status' => 'running',
            'started_at' => now()->subHours(2),
        ]
    );

    // Step 2: Simulate Input Harian production activity on PART ALPHA AFTER the initial backup
    $planAlpha->update([
        'ok' => 125,
        'repair' => 4,
        'reject' => 2,
        'total_pcs' => 131,
        'act_start' => '07:35',
        'dt_menit' => 15,
        'status' => 'running',
    ]);

    $session = ProductionSession::create([
        'job_master_id' => $jobAlpha->id,
        'work_date' => $date,
        'start_time' => now()->subHours(2),
        'total_seconds' => 3600,
        'status' => 'running',
    ]);

    $dailyProd = DailyProduction::create([
        'job_master_id' => $jobAlpha->id,
        'work_date' => $date,
        'shift' => 'Shift Pagi',
        'line' => 'PRESS A',
        'target_qty' => 200,
        'actual_qty' => 131,
        'actual_ok' => 125,
        'repair_qty' => 4,
        'reject_qty' => 2,
    ]);

    $prodLog = ProductionLog::create([
        'job_master_id' => $jobAlpha->id,
        'ok_qty' => 125,
        'repair_qty' => 4,
        'reject_qty' => 2,
    ]);

    $downtime = Downtime::create([
        'job_master_id' => $jobAlpha->id,
        'jenis_downtime' => 'Machine',
        'problem' => 'Sensor check',
        'start_time' => now()->subMinutes(45),
        'finish_time' => now()->subMinutes(30),
        'duration_seconds' => 900,
    ]);

    // Step 3: Simulate Leader & Foreman signing LKH AFTER initial backup
    $leaderSig = Signature::create([
        'work_date' => $date,
        'line_name' => 'PRESS A',
        'shift_name' => 'Shift Pagi',
        'role' => 'leader',
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA',
    ]);

    // Step 4: Simulate an erroneous PPC revision import + accidental deletion of PART BETA
    // Revision changes PART ALPHA plan to 500, adds idle PART IDLE-NEW, and adds active PART ACTIVE-NEW
    $mergeService->merge([
        [
            'shift_name' => 'Shift Pagi (Rev)',
            'press_name' => 'PRESS A',
            'rows' => [
                backupSheetRow(['job_master' => 'PART ALPHA', 'job_no' => 'JA-01', 'row_no' => 1, 'plan' => 500, 'start_time' => '08:00', 'finish_time' => '12:30']),
                backupSheetRow(['job_master' => 'PART IDLE NEW', 'job_no' => 'JN-99', 'row_no' => 3, 'plan' => 80]),
                backupSheetRow(['job_master' => 'PART ACTIVE NEW', 'job_no' => 'JN-88', 'row_no' => 4, 'plan' => 60]),
            ],
        ],
    ], $date, $user->id, ['source_filename' => 'wrong_revision.xlsx']);

    // Mark PART ACTIVE NEW as having actual production so restore must NOT delete it
    $planActiveNew = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JN-88')->firstOrFail();
    $planActiveNew->update(['ok' => 30, 'status' => 'running']);

    // Simulate PART BETA being accidentally deleted
    $betaOriginalId = $planBeta->id;
    $planBeta->delete();

    // Verify compareWithLive accurately detects the differences
    $diff = $backupService->compareWithLive($initialBackup);
    expect($diff['counts']['will_restore_missing'])->toBe(1)
        ->and($diff['counts']['will_update_schedule'])->toBe(1)
        ->and($diff['counts']['protected_active_plans'])->toBe(1)
        ->and($diff['counts']['post_snapshot_plans'])->toBe(2);

    // Step 5: Execute Transactional Restore to $initialBackup
    $restoreStats = $backupService->restoreSnapshot($initialBackup, $user->id, true);

    expect($restoreStats['restored_count'])->toBe(1)
        ->and($restoreStats['updated_count'])->toBe(1)
        ->and($restoreStats['pruned_idle_count'])->toBe(1)
        ->and($restoreStats['preserved_active_count'])->toBe(2)
        ->and($restoreStats['pre_restore_backup_id'])->not->toBeNull();

    // Verify PART ALPHA schedule reverted to 200 pcs & 07:30-11:30, WHILE actuals stay intact!
    $planAlpha->refresh();
    expect((float) $planAlpha->plan)->toBe(200.0)
        ->and($planAlpha->start_time)->toBe('07:30')
        ->and($planAlpha->finish_time)->toBe('11:30')
        ->and((float) $planAlpha->ok)->toBe(125.0)
        ->and((float) $planAlpha->repair)->toBe(4.0)
        ->and((float) $planAlpha->reject)->toBe(2.0)
        ->and($planAlpha->status)->toBe('running')
        ->and($planAlpha->act_start)->toBe('07:35');

    // Verify PART BETA was restored with its original ID
    $restoredBeta = ProductionPlan::find($betaOriginalId);
    expect($restoredBeta)->not->toBeNull()
        ->and($restoredBeta->job_no)->toBe('JB-02')
        ->and((float) $restoredBeta->plan)->toBe(150.0);

    // Verify idle post-snapshot item JN-99 was pruned, while active post-snapshot item JN-88 was preserved
    expect(ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JN-99')->exists())->toBeFalse();
    expect(ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JN-88')->exists())->toBeTrue();

    // Verify all Input Harian logs, sessions, downtimes, and LKH signatures are completely intact
    expect(ProductionSession::find($session->id))->not->toBeNull()
        ->and((int) DailyProduction::find($dailyProd->id)->actual_ok)->toBe(125)
        ->and((int) ProductionLog::find($prodLog->id)->ok_qty)->toBe(125)
        ->and((int) Downtime::find($downtime->id)->duration_seconds)->toBe(900)
        ->and(Signature::find($leaderSig->id))->not->toBeNull();

    // Verify ScheduleRevision audit record was created for the restore
    $latestRevision = ScheduleRevision::latest('id')->first();
    expect($latestRevision)->not->toBeNull()
        ->and($latestRevision->action)->toBe('restore');
});

test('schedule backup controller endpoints render history, diff view, manual snapshot, and restore', function () {
    $line = backupTestLine();
    $user = backupTestUser();
    $date = '2026-10-23';

    ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'PART ZETA',
        'job_no' => 'JZ-10',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 300,
        'status' => 'pending',
        'source_type' => 'ppc',
    ]);

    // 1. Create manual snapshot via POST
    $this->actingAs($user)
        ->post(route('ppc.planning.backups.store'), [
            'plan_date' => $date,
            'label' => 'Manual Backup Test',
        ])
        ->assertRedirect();

    $backup = ScheduleBackup::whereDate('plan_date', $date)->where('backup_type', 'manual')->first();
    expect($backup)->not->toBeNull()
        ->and($backup->label)->toBe('Manual Backup Test')
        ->and($backup->plans_count)->toBe(1);

    // 2. View Backup Index
    $this->actingAs($user)
        ->get(route('ppc.planning.backups.index', ['date' => $date]))
        ->assertOk()
        ->assertSee('Manual Backup Test');

    // 3. View Backup Show / Compare
    $this->actingAs($user)
        ->get(route('ppc.planning.backups.show', $backup->id))
        ->assertOk()
        ->assertSee('PART ZETA');

    // 4. Compare JSON endpoint
    $this->actingAs($user)
        ->getJson(route('ppc.planning.backups.compare', $backup->id))
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('diff.counts.unchanged', 1);

    // 5. Modify plan and trigger restore via POST endpoint
    ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JZ-10')->update(['plan' => 999]);

    $this->actingAs($user)
        ->post(route('ppc.planning.backups.restore', $backup->id), [
            'prune_unlisted_idle' => 1,
        ])
        ->assertRedirect(route('ppc.planning.production_plan', ['date' => $date]));

    $revertedPlan = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'JZ-10')->firstOrFail();
    expect((float) $revertedPlan->plan)->toBe(300.0);
});

test('snapshot detail view accurately renders multiple press, shift, urutan row_no, qty plan, and jam plan from database payload', function () {
    $lineA = backupTestLine('L-A', 'PRESS A');
    $lineB = backupTestLine('L-B', 'PRESS B');
    $lineD = backupTestLine('L-D', 'PRESS D');
    $user = backupTestUser();
    $date = '2026-10-23';

    ProductionPlan::create([
        'line_master_id' => $lineA->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'MEMBER FRONT',
        'job_no' => 'MF-101',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 450,
        'start_time' => '07:30',
        'finish_time' => '11:45',
        'status' => 'pending',
        'source_type' => 'ppc',
    ]);

    ProductionPlan::create([
        'line_master_id' => $lineB->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Malam',
        'press_name' => 'PRESS B',
        'job_master' => 'BRACKET REAR',
        'job_no' => 'BR-202',
        'row_no' => 7,
        'row_type' => 'job',
        'plan' => 620,
        'start_time' => '21:00',
        'finish_time' => '02:15',
        'status' => 'pending',
        'source_type' => 'ppc',
    ]);

    ProductionPlan::create([
        'line_master_id' => $lineD->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS D',
        'job_master' => 'REINF PANEL',
        'job_no' => 'RP-303',
        'row_no' => 12,
        'row_type' => 'job',
        'plan' => 890,
        'start_time' => '13:00',
        'finish_time' => '18:30',
        'status' => 'pending',
        'source_type' => 'ppc',
    ]);

    $backup = app(ScheduleBackupService::class)->captureSnapshot($date, 'manual', $user->id, [
        'label' => 'Multi Press Snapshot',
    ]);

    // Also test legacy snapshot payload that only stored raw DB attributes without alias keys
    $rawPayload = $backup->snapshot_payload;
    foreach ($rawPayload['production_plans'] as &$p) {
        unset($p['press'], $p['shift'], $p['urutan_plan'], $p['qty_plan']);
    }
    $backup->update(['snapshot_payload' => $rawPayload]);

    $response = $this->actingAs($user)->get(route('ppc.planning.backups.show', $backup->id));
    $response->assertOk()
        ->assertSee('PRESS A')
        ->assertSee('PRESS B')
        ->assertSee('PRESS D')
        ->assertSee('Shift Pagi')
        ->assertSee('Shift Malam')
        ->assertSee('MF-101')
        ->assertSee('BR-202')
        ->assertSee('RP-303')
        ->assertSee('450')
        ->assertSee('620')
        ->assertSee('890')
        ->assertSee('07:30 - 11:45')
        ->assertSee('21:00 - 02:15')
        ->assertSee('13:00 - 18:30')
        ->assertDontSee('Peringatan: Data Snapshot Tidak Lengkap');
});

test('incomplete snapshot displays warning and blocks restore execution', function () {
    $lineA = backupTestLine('L-A', 'PRESS A');
    $user = backupTestUser();
    $date = '2026-10-23';

    ProductionPlan::create([
        'line_master_id' => $lineA->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'PART CORRUPT',
        'job_no' => 'PC-01',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 100,
        'status' => 'pending',
        'source_type' => 'ppc',
    ]);

    $backup = ScheduleBackup::create([
        'plan_date' => $date,
        'shift_scope' => 'all',
        'press_scope' => 'all',
        'backup_type' => 'manual',
        'label' => 'Incomplete Snapshot',
        'plans_count' => 1,
        'jobs_count' => 0,
        'active_jobs_count' => 0,
        'signatures_count' => 0,
        'snapshot_payload' => [
            'version' => 1,
            'plan_date' => $date,
            'production_plans' => [
                [
                    'id' => 99999,
                    'press_name' => null,
                    'shift_name' => null,
                    'row_no' => null,
                    'row_type' => 'job',
                    'job_no' => 'PC-01',
                    'job_master' => 'PART CORRUPT',
                    'plan' => 500,
                ],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('ppc.planning.backups.show', $backup->id))
        ->assertOk()
        ->assertSee('Peringatan: Data Snapshot Tidak Lengkap')
        ->assertSee('RESTORE DINONAKTIFKAN');

    $this->actingAs($user)
        ->post(route('ppc.planning.backups.restore', $backup->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    // Live plan must remain untouched (100 pcs, not overwritten to 500)
    $livePlan = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'PC-01')->firstOrFail();
    expect((float) $livePlan->plan)->toBe(100.0);
});
