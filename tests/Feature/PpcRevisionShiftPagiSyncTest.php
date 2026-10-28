<?php

use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ScheduleRevision;
use App\Models\User;
use App\Services\PpcScheduleMergeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function ppcRevRow(array $overrides = []): array
{
    return array_merge([
        'job_master' => 'JOB A',
        'job_no' => 'A1',
        'row_no' => 1,
        'plan' => 100,
        'qty_plt' => 10,
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
        'finish_time' => '12:00',
        'keterangan' => null,
        'total_mesin' => 1,
        'a1' => 0,
        'a2' => 0,
        'a3' => 0,
        'a4' => 0,
        'ok' => 0,
        'reject' => 0,
        'dt_menit' => 0,
        'total_pcs' => 0,
        'tpt_total' => 0,
    ], $overrides);
}

test('revision sheet sync with delete instruction, qty change, new item, and running item protection', function () {
    $lineA = LineMaster::create(['line_code' => 'L-A', 'line_name' => 'PRESS A', 'status' => 'active']);
    $lineB = LineMaster::create(['line_code' => 'L-B', 'line_name' => 'PRESS B', 'status' => 'active']);
    $date = '2026-10-28';

    // Base items on Press A
    // 1. Unchanged item
    $planUnchanged = ProductionPlan::create([
        'line_master_id' => $lineA->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JM-UNCHANGED',
        'job_no' => 'JOB-UNCHANGED',
        'row_no' => 1,
        'plan' => 200,
        'start_time' => '07:30',
        'finish_time' => '09:00',
        'qty_plt' => 10,
        'keb_mtl' => 1,
        'total_plt' => 10,
        'ct_detik' => 5,
        'process_time' => 50,
        'plan_dct' => 50,
        'tpt' => 100,
        'gsph_item' => 200,
        'a1' => 0,
        'a2' => 0,
        'a3' => 0,
        'a4' => 0,
        'status' => 'pending',
    ]);

    // 2. Qty changed item with actual production progress
    $planQtyChange = ProductionPlan::create([
        'line_master_id' => $lineA->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JM-QTY',
        'job_no' => 'JOB-QTY',
        'row_no' => 2,
        'plan' => 500,
        'ok' => 120,
        'repair' => 5,
        'reject' => 2,
        'status' => 'running',
        'act_start' => '07:30',
    ]);
    $jobQty = JobMaster::create([
        'job_number' => "JM-QTY-JOB-QTY-{$lineA->id}-Pagi-{$planQtyChange->id}",
        'job_name' => 'JM-QTY',
        'part_no' => 'JOB-QTY',
        'line' => 'PRESS A',
        'shift' => 'Pagi',
        'target_qty' => 500,
        'status' => 'running',
        'started_at' => Carbon::parse("{$date} 07:30:00"),
    ]);

    // 3. Item with explicit delete instruction and NO production activity (should be deleted)
    $planToDelete = ProductionPlan::create([
        'line_master_id' => $lineB->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS B',
        'job_master' => 'JM-DEL-1',
        'job_no' => 'JOB-DEL-1',
        'row_no' => 1,
        'plan' => 1000,
        'status' => 'pending',
    ]);
    $jobToDelete = JobMaster::create([
        'job_number' => "JM-DEL-1-JOB-DEL-1-{$lineB->id}-Pagi-{$planToDelete->id}",
        'job_name' => 'JM-DEL-1',
        'part_no' => 'JOB-DEL-1',
        'line' => 'PRESS B',
        'shift' => 'Pagi',
        'target_qty' => 1000,
        'status' => 'pending',
    ]);

    // 4. Item with explicit delete instruction BUT HAS PRODUCTION ACTIVITY (must be protected from deletion)
    $planDeleteProtected = ProductionPlan::create([
        'line_master_id' => $lineB->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS B',
        'job_master' => 'JM-DEL-ACTIVE',
        'job_no' => 'JOB-DEL-ACTIVE',
        'row_no' => 2,
        'plan' => 600,
        'ok' => 50,
        'status' => 'running',
    ]);

    // Perform revision merge
    $user = User::create([
        'name' => 'PPC Revision Tester',
        'nrp' => 'PPC-REV-01',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $service = app(PpcScheduleMergeService::class);
    $result = $service->merge([
        // Sheet Shift Pagi (Rev) for Press A
        [
            'sheet_name' => 'Shift Pagi (Rev)',
            'shift_name' => 'Shift Pagi (Rev)',
            'press_name' => 'PRESS A',
            'rows' => [
                // Unchanged
                ppcRevRow([
                    'job_master' => 'JM-UNCHANGED',
                    'job_no' => 'JOB-UNCHANGED',
                    'row_no' => 1,
                    'plan' => 200,
                    'start_time' => '07:30',
                    'finish_time' => '09:00',
                ]),
                // Qty changed: plan updated to 400
                ppcRevRow([
                    'job_master' => 'JM-QTY',
                    'job_no' => 'JOB-QTY',
                    'row_no' => 2,
                    'plan' => 400,
                    'start_time' => '09:00',
                    'finish_time' => '12:00',
                ]),
                // Brand new item added in revision
                ppcRevRow([
                    'job_master' => 'JM-NEW-ITEM',
                    'job_no' => 'JOB-NEW-ITEM',
                    'row_no' => 3,
                    'plan' => 350,
                    'start_time' => '13:00',
                    'finish_time' => '15:00',
                ]),
            ],
        ],
        // Sheet Shift Pagi (Rev) for Press B
        [
            'sheet_name' => 'Shift Pagi (Rev)',
            'shift_name' => 'Shift Pagi (Rev)',
            'press_name' => 'PRESS B',
            'rows' => [
                // Explicit delete instruction: Delete Plan Shift 1 for unstarted item
                ppcRevRow([
                    'job_master' => 'JM-DEL-1',
                    'job_no' => 'JOB-DEL-1',
                    'row_no' => 1,
                    'plan' => 0,
                    'keterangan' => 'Delete Plan Shift 1',
                ]),
                // Explicit delete instruction: Delete (Material T/a) for active item
                ppcRevRow([
                    'job_master' => 'JM-DEL-ACTIVE',
                    'job_no' => 'JOB-DEL-ACTIVE',
                    'row_no' => 2,
                    'plan' => 0,
                    'keterangan' => 'Delete (Material T/a)',
                ]),
            ],
        ],
    ], $date, $user->id);

    // 1. Verify Unchanged item
    expect($planUnchanged->fresh())->not->toBeNull();
    expect($result['counts']['unchanged'])->toBe(1);

    // 2. Verify Qty update preserves actual progress & updates job master target_qty
    $planQtyChange->refresh();
    expect($planQtyChange->plan)->toBe(400.0)
        ->and($planQtyChange->ok)->toBe(120.0)
        ->and($planQtyChange->repair)->toBe(5.0)
        ->and($planQtyChange->reject)->toBe(2.0)
        ->and($planQtyChange->status)->toBe('running')
        ->and($planQtyChange->act_start)->toBe('07:30');

    expect($jobQty->fresh()->target_qty)->toBe(400);

    // 3. Verify New item is added
    expect($result['counts']['added'])->toBe(1);
    $newItem = ProductionPlan::where('job_no', 'JOB-NEW-ITEM')->first();
    expect($newItem)->not->toBeNull()
        ->and($newItem->plan)->toBe(350.0);

    // 4. Verify unstarted item with Delete Plan Shift 1 is cleanly deleted
    expect(ProductionPlan::find($planToDelete->id))->toBeNull();
    expect(JobMaster::find($jobToDelete->id))->toBeNull();
    expect($result['counts']['deleted'])->toBe(1);
    expect($result['deleted'][0]['job_no'])->toBe('JOB-DEL-1');

    // 5. Verify active item with Delete instruction is PROTECTED and NOT deleted
    expect(ProductionPlan::find($planDeleteProtected->id))->not->toBeNull();
    expect($result['counts']['protected_from_deletion'])->toBe(1);
    expect($result['protected_from_deletion'][0]['job_no'])->toBe('JOB-DEL-ACTIVE');
    expect($planDeleteProtected->fresh()->ok)->toBe(50.0);
});
