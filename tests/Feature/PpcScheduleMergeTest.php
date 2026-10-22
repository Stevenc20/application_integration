<?php

use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ScheduleRevision;
use App\Models\User;
use App\Services\PpcScheduleMergeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function ppcLine(): LineMaster
{
    return LineMaster::create([
        'line_code' => 'L-A',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);
}

function ppcRow(array $overrides = []): array
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
        'p1' => false,
        'p2' => false,
        'p3' => false,
        'p4' => false,
        'ok' => 0,
        'reject' => 0,
    ], $overrides);
}

function ppcSheet(string $shift, string $press, array $rows): array
{
    return [
        'shift_name' => $shift,
        'press_name' => $press,
        'rows' => $rows,
    ];
}

function ppcPlan(LineMaster $line, string $date, array $overrides = []): ProductionPlan
{
    return ProductionPlan::create(array_merge([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB A',
        'job_no' => 'A1',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 100,
        'status' => 'pending',
        'source_type' => 'ppc',
    ], $overrides));
}

function ppcJob(ProductionPlan $plan, array $overrides = []): JobMaster
{
    return JobMaster::create(array_merge([
        'job_number' => $plan->job_no.'-'.$plan->id,
        'job_name' => $plan->job_master,
        'line' => $plan->press_name,
        'status' => 'pending',
        'target_qty' => (int) $plan->plan,
    ], $overrides));
}

function ppcExcelFixture(string $path): void
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Shift Pagi (Rev)');

    $sheet->setCellValue('C1', 'HARI');
    $sheet->setCellValue('D1', 'KAMIS');
    $sheet->setCellValue('C2', 'TGL');
    $sheet->setCellValue('D2', '23 OKTOBER 2026');
    $sheet->setCellValue('C3', 'JAM');
    $sheet->setCellValue('D3', 'PAGI');
    $sheet->setCellValue('C4', 'PRESS A');

    $headers = [
        'NO.', 'JOB MASTER', 'TYPE PLT', 'QTY/PLT', 'KEB. MTL', 'TOTAL PLT',
        'JOB NO.', 'EACH PART', 'PLAN (PCS)', 'OK', 'REPAIR', 'REJECT',
        'TOTAL MESIN', 'CT (DETIK)', 'PROCESS TIME', 'REG. ACTIVE', 'DCT',
        'MCT', 'PLAN DCT', 'TPT', 'GSPH', 'START', 'FINISH', 'KETERANGAN',
    ];
    $sheet->fromArray($headers, null, 'A5');

    $rows = [
        [1, 'JOB A', null, 10, 1, 10, 'A1', null, 120, 0, 0, 0, 1, 5, 50, 0, 0, 0, 50, 100, 200, '07:30', '12:00', null],
        [2, 'JOB D', null, 8, 1, 8, 'D1', null, 90, 0, 0, 0, 1, 6, 60, 0, 0, 0, 60, 90, 180, '13:00', '17:00', null],
    ];
    $sheet->fromArray($rows, null, 'A6');

    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();
}

test('import endpoint merges an uploaded revision and flashes the diff preview', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $planA = ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 100]);
    $planB = ppcPlan($line, $date, [
        'job_master' => 'JOB B',
        'job_no' => 'B1',
        'row_no' => 3,
        'plan' => 80,
        'ok' => 15,
        'status' => 'running',
    ]);

    $user = User::create([
        'name' => 'PPC Import',
        'nrp' => 'PPC-IMP',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'ppc_').'.xlsx';
    ppcExcelFixture($path);

    $file = new UploadedFile(
        $path,
        '23 Oktober 2026.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $this->actingAs($user)
        ->post(route('ppc.planning.production_plan.import'), [
            'excel_file' => $file,
            'date' => $date,
            'shift_filter' => 'all',
        ])
        ->assertRedirect()
        ->assertSessionHas('import_preview');

    $planA->refresh();
    $planB->refresh();

    expect($planA->plan)->toBe(120.0);
    expect($planB->plan)->toBe(80.0);
    expect($planB->ok)->toBe(15.0);
    expect($planB->status)->toBe('running');
    expect(ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'D1')->exists())->toBeTrue();
    expect(ProductionPlan::whereDate('plan_date', $date)->count())->toBe(3);
});

test('revision merge keeps unlisted plans and production actuals while updating listed plans', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $planA = ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]);
    $planB = ppcPlan($line, $date, ['job_master' => 'JOB B', 'job_no' => 'B1', 'row_no' => 2]);
    $planC = ppcPlan($line, $date, ['job_master' => 'JOB C', 'job_no' => 'C1', 'row_no' => 3]);

    $jobB = ppcJob($planB, ['status' => 'running', 'started_at' => now()->subHour()]);
    $planB->update([
        'ok' => 50,
        'repair' => 2,
        'reject' => 1,
        'status' => 'running',
        'act_start' => '07:30',
        'total_pcs' => 53,
    ]);

    $user = User::create([
        'name' => 'PPC Tester',
        'nrp' => 'PPC-001',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $result = app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 120]),
            ppcRow(['job_master' => 'JOB B', 'job_no' => 'B1', 'row_no' => 2, 'plan' => 130]),
            ppcRow(['job_master' => 'JOB D', 'job_no' => 'D1', 'row_no' => 4, 'plan' => 90]),
        ]),
    ], $date, $user->id);

    expect($result['counts'])->toMatchArray([
        'added' => 1,
        'updated' => 2,
        'unchanged' => 0,
        'not_in_revision' => 1,
        'rows_imported' => 3,
    ]);

    expect($planA->fresh()->plan)->toBe(120.0);
    expect($planC->fresh())->not->toBeNull();

    $planB->refresh();
    expect($planB->plan)->toBe(130.0)
        ->and($planB->ok)->toBe(50.0)
        ->and($planB->repair)->toBe(2.0)
        ->and($planB->reject)->toBe(1.0)
        ->and($planB->status)->toBe('running')
        ->and($planB->act_start)->toBe('07:30');

    expect($jobB->fresh()->status)->toBe('running');
    expect(app(PpcScheduleMergeService::class)->planHasProductionActivity($planB->fresh()))->toBeTrue();
    expect(ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'D1')->exists())->toBeTrue();

    $revision = ScheduleRevision::latest('id')->first();
    expect($revision)->not->toBeNull()
        ->and($revision->snapshot_before['plans'])->toHaveCount(3)
        ->and($revision->snapshot_after['counts']['added'])->toBe(1)
        ->and($revision->snapshot_after['counts']['not_in_revision'])->toBe(1);

    expect(collect($result['not_in_revision'])->pluck('job_no')->all())->toBe(['C1']);
});

test('job number change renames linked job master instead of creating a duplicate', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $plan = ppcPlan($line, $date, ['job_master' => 'JOB X', 'job_no' => 'OLD', 'row_no' => 1]);
    $job = ppcJob($plan);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB X', 'job_no' => 'NEW', 'row_no' => 1, 'plan' => 100]),
        ]),
    ], $date);

    $plan->refresh();
    $job->refresh();

    expect($plan->job_no)->toBe('NEW');
    expect($job->job_number)->toBe('NEW-'.$plan->id);
    expect(JobMaster::where('job_number', 'OLD-'.$plan->id)->exists())->toBeFalse();
    expect(JobMaster::where('job_number', 'NEW-'.$plan->id)->count())->toBe(1);
});

test('repeated merge import is idempotent', function () {
    $line = ppcLine();
    $date = '2026-10-23';
    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]);

    $sheets = [
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 120]),
        ]),
    ];

    app(PpcScheduleMergeService::class)->merge($sheets, $date);
    $second = app(PpcScheduleMergeService::class)->merge($sheets, $date);

    expect($second['counts'])->toMatchArray([
        'added' => 0,
        'updated' => 0,
        'unchanged' => 1,
        'not_in_revision' => 0,
    ]);
    expect(ProductionPlan::whereDate('plan_date', $date)->count())->toBe(1);
});

test('import for one date never deletes plans from other dates', function () {
    $line = ppcLine();
    $previous = '2026-10-22';
    $date = '2026-10-23';

    $oldPlan = ppcPlan($line, $previous, ['job_master' => 'JOB OLD', 'job_no' => 'O1']);
    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1']);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 120]),
        ]),
    ], $date);

    expect($oldPlan->fresh())->not->toBeNull();
    expect(ProductionPlan::whereDate('plan_date', $previous)->count())->toBe(1);
});

test('unlisted plans without production activity are preserved, not deleted', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]);
    $planB = ppcPlan($line, $date, ['job_master' => 'JOB B', 'job_no' => 'B1', 'row_no' => 2]);

    $result = app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 120]),
        ]),
    ], $date);

    expect($planB->fresh())->not->toBeNull();
    expect($result['counts']['not_in_revision'])->toBe(1);
    expect($result['not_in_revision'][0]['has_activity'])->toBeFalse();
});

test('delete marker inside revision never removes an existing plan', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $plan = ppcPlan($line, $date, ['job_master' => 'JOB B', 'job_no' => 'B1', 'row_no' => 2]);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'DELETE', 'job_no' => 'B1', 'row_no' => 2]),
        ]),
    ], $date);

    expect($plan->fresh())->not->toBeNull();
});

test('a revision sheet supersedes its base sheet for the same shift and press', function () {
    $line = ppcLine();
    $date = '2026-10-23';
    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 100]);

    $result = app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 200]),
        ]),
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 120]),
        ]),
    ], $date);

    expect($result['counts']['rows_imported'])->toBe(1);
    expect(ProductionPlan::whereDate('plan_date', $date)->count())->toBe(1);
    expect(ProductionPlan::whereDate('plan_date', $date)->first()->plan)->toBe(120.0);
});

test('importing one shift never touches plans from another shift', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $nightPlan = ppcPlan($line, $date, [
        'shift_name' => 'Shift Malam',
        'job_master' => 'JOB NIGHT',
        'job_no' => 'N1',
        'row_no' => 1,
        'plan' => 90,
        'ok' => 10,
    ]);
    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 100]);

    $result = app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 120]),
        ]),
    ], $date);

    $nightPlan->refresh();
    expect($nightPlan->plan)->toBe(90.0)
        ->and($nightPlan->ok)->toBe(10.0)
        ->and($nightPlan->shift_name)->toBe('Shift Malam');
    expect($result['counts']['not_in_revision'])->toBe(0);
});

test('a changed break time updates the existing break instead of duplicating it', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    ppcPlan($line, $date, [
        'job_master' => 'BREAK',
        'job_no' => 'ISTIRAHAT',
        'row_no' => 2,
        'row_type' => 'break',
        'start_time' => '12:00',
        'finish_time' => '13:00',
    ]);

    $result = app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow([
                'job_master' => 'BREAK',
                'job_no' => 'ISTIRAHAT',
                'row_no' => 2,
                'start_time' => '12:30',
                'finish_time' => '13:30',
            ]),
        ]),
    ], $date);

    $breaks = ProductionPlan::whereDate('plan_date', $date)->where('row_type', 'break')->get();
    expect($breaks)->toHaveCount(1);
    expect($breaks->first()->start_time)->toBe('12:30');
    expect($result['counts']['updated'])->toBe(1);
});

test('manual delete and clear data refuse plans that already have production activity', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $activePlan = ppcPlan($line, $date, [
        'job_master' => 'JOB ACTIVE',
        'job_no' => 'ACT-1',
        'row_no' => 1,
        'ok' => 25,
        'status' => 'running',
    ]);
    $idlePlan = ppcPlan($line, $date, [
        'job_master' => 'JOB IDLE',
        'job_no' => 'IDLE-1',
        'row_no' => 2,
    ]);

    $user = User::create([
        'name' => 'PPC Admin',
        'nrp' => 'PPC-ADM',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('ppc.planning.production_plan.destroy', $activePlan->id))
        ->assertStatus(422);
    expect($activePlan->fresh())->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('ppc.planning.production_plan.clear', ['date' => $date]), [
            'date' => $date,
            'shift' => 'Pagi',
        ])
        ->assertRedirect();

    expect($activePlan->fresh())->not->toBeNull();
    expect($idlePlan->fresh())->toBeNull();
});

test('adding a new revision row does not remove existing rows with the same job master', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $first = ppcPlan($line, $date, ['job_master' => 'JOB SPLIT', 'job_no' => 'S1', 'row_no' => 1]);
    $second = ppcPlan($line, $date, [
        'job_master' => 'JOB SPLIT',
        'job_no' => 'S1',
        'row_no' => 2,
        'start_time' => '13:00',
        'finish_time' => '17:00',
    ]);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB SPLIT', 'job_no' => 'S1', 'row_no' => 2, 'start_time' => '13:00', 'finish_time' => '17:00', 'plan' => 100]),
            ppcRow(['job_master' => 'JOB SPLIT', 'job_no' => 'S1', 'row_no' => 3, 'start_time' => '17:00', 'finish_time' => '21:00', 'plan' => 80]),
        ]),
    ], $date);

    expect($first->fresh())->not->toBeNull();
    expect($second->fresh())->not->toBeNull();
    expect(ProductionPlan::whereDate('plan_date', $date)->where('job_master', 'JOB SPLIT')->count())->toBe(3);
});

test('newly added revision rows do not pre-create job masters', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]),
            ppcRow(['job_master' => 'JOB NEW', 'job_no' => 'N1', 'row_no' => 2]),
        ]),
    ], $date);

    expect(ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'N1')->exists())->toBeTrue();
    expect(JobMaster::where('job_number', 'like', 'N1-%')->exists())->toBeFalse();
});

test('allocation flags follow a1-a4 and total finish rows keep their actuals', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $job = ppcPlan($line, $date, ['job_master' => 'JOB FLAG', 'job_no' => 'F1', 'row_no' => 1]);
    $total = ppcPlan($line, $date, [
        'job_master' => 'TOTAL FINISH',
        'job_no' => 'TOTAL FINISH',
        'row_no' => 9,
        'row_type' => 'total_finish',
        'plan' => 100,
        'ok' => 40,
    ]);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow([
                'job_master' => 'JOB FLAG',
                'job_no' => 'F1',
                'row_no' => 1,
                'a1' => 5,
                'a2' => 0,
                'a3' => 2,
                'a4' => 0,
            ]),
            ppcRow([
                'job_master' => 'TOTAL FINISH',
                'job_no' => 'TOTAL FINISH',
                'row_no' => 9,
                'row_type' => 'total_finish',
                'plan' => 250,
            ]),
        ]),
    ], $date);

    $job->refresh();
    expect((bool) $job->p1)->toBeTrue();
    expect((bool) $job->p2)->toBeFalse();
    expect((bool) $job->p3)->toBeTrue();
    expect((bool) $job->p4)->toBeFalse();
    expect((float) $job->a1)->toBe(5.0);

    $total->refresh();
    expect((float) $total->plan)->toBe(250.0);
    expect((int) $total->ok)->toBe(40);
});

test('ten old plans survive with all actuals when two are revised and one is added', function () {
    $line = ppcLine();
    $date = '2026-10-23';

    $rows = [];
    for ($i = 1; $i <= 10; $i++) {
        $rows[] = ppcRow([
            'job_master' => 'JOB '.$i,
            'job_no' => 'J'.$i,
            'row_no' => $i,
        ]);
    }

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', $rows),
    ], $date);

    $oldPlans = ProductionPlan::whereDate('plan_date', $date)->orderBy('row_no')->get();
    expect($oldPlans)->toHaveCount(10);

    $actuals = [];
    foreach ($oldPlans as $index => $plan) {
        $plan->update([
            'ok' => 10 + $index,
            'repair' => 2,
            'reject' => 3,
            'status' => 'running',
            'act_start' => '07:30',
            'total_pcs' => 15 + $index,
            'dt_menit' => 5,
            'tpt_total' => 100 + $index,
        ]);

        $actuals[$plan->id] = [
            'ok' => (float) (10 + $index),
            'repair' => 2.0,
            'reject' => 3.0,
            'status' => 'running',
            'act_start' => '07:30',
            'total_pcs' => (float) (15 + $index),
            'dt_menit' => 5.0,
            'tpt_total' => (float) (100 + $index),
        ];
    }

    $jobOne = ppcJob($oldPlans[0]);
    $jobFive = ppcJob($oldPlans[4]);

    $revision = $rows;
    $revision[0] = ppcRow(['job_master' => 'JOB 1', 'job_no' => 'J1', 'row_no' => 1, 'plan' => 120]);
    $revision[1] = ppcRow(['job_master' => 'JOB 2', 'job_no' => 'J2', 'row_no' => 2, 'plan' => 130]);
    $revision[] = ppcRow(['job_master' => 'JOB NEW', 'job_no' => 'J11', 'row_no' => 11, 'plan' => 90]);

    $result = app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', $revision),
    ], $date);

    expect($result['counts'])->toMatchArray([
        'added' => 1,
        'updated' => 2,
        'unchanged' => 8,
        'not_in_revision' => 0,
        'rows_imported' => 11,
    ]);

    expect(ProductionPlan::whereDate('plan_date', $date)->count())->toBe(11);

    foreach ($oldPlans as $plan) {
        $fresh = $plan->fresh();
        expect($fresh)->not->toBeNull();

        foreach ($actuals[$plan->id] as $field => $value) {
            $actual = $fresh->getAttribute($field);
            is_numeric($value)
                ? expect((float) $actual)->toBe((float) $value)
                : expect($actual)->toBe($value);
        }
    }

    expect($oldPlans[0]->fresh()->plan)->toBe(120.0);
    expect($oldPlans[1]->fresh()->plan)->toBe(130.0);
    expect($jobOne->fresh()->job_number)->toBe('J1-'.$oldPlans[0]->id);
    expect($jobFive->fresh()->job_number)->toBe('J5-'.$oldPlans[4]->id);

    $newPlan = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'J11')->first();
    expect($newPlan)->not->toBeNull();
    expect((float) $newPlan->plan)->toBe(90.0);
    expect(JobMaster::where('job_number', 'J11-'.$newPlan->id)->exists())->toBeFalse();
    expect(JobMaster::count())->toBe(2);
});

test('uploading the 23 october schedule keeps 22 october plans and job masters', function () {
    $line = ppcLine();

    $oldPlan = ppcPlan($line, '2026-10-22', [
        'job_master' => 'JOB OLD',
        'job_no' => 'O1',
        'row_no' => 1,
        'ok' => 12,
        'status' => 'running',
    ]);
    $oldJob = ppcJob($oldPlan);

    $user = User::create([
        'name' => 'PPC Upload',
        'nrp' => 'PPC-UP',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'ppc_').'.xlsx';
    ppcExcelFixture($path);

    $file = new UploadedFile(
        $path,
        '23 Oktober 2026.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $this->actingAs($user)
        ->post(route('ppc.planning.production_plan.import'), [
            'excel_file' => $file,
            'date' => '2026-10-23',
            'shift_filter' => 'all',
        ])
        ->assertRedirect()
        ->assertSessionHas('import_preview');

    expect($oldPlan->fresh())->not->toBeNull();
    expect((float) $oldPlan->fresh()->ok)->toBe(12.0);
    expect($oldJob->fresh())->not->toBeNull();
    expect(ProductionPlan::whereDate('plan_date', '2026-10-22')->count())->toBe(1);
    expect(ProductionPlan::whereDate('plan_date', '2026-10-23')->count())->toBeGreaterThan(0);
});

test('a new revision row gets its job master in input harian normal flow', function () {
    Carbon::setTestNow('2026-10-23 10:00:00');

    $line = ppcLine();
    $date = '2026-10-23';

    ppcPlan($line, $date, ['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]);

    app(PpcScheduleMergeService::class)->merge([
        ppcSheet('Shift Pagi (Rev)', 'PRESS A', [
            ppcRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1]),
            ppcRow(['job_master' => 'JOB NEW', 'job_no' => 'N1', 'row_no' => 2]),
        ]),
    ], $date);

    $newPlan = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'N1')->first();
    expect($newPlan)->not->toBeNull();
    expect(JobMaster::where('job_number', 'N1-'.$newPlan->id)->exists())->toBeFalse();

    $user = User::create([
        'name' => 'Input Harian',
        'nrp' => 'IH-NEW',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $this->actingAs($user)
        ->get(route('operational.input_harian', [
            'date' => $date,
            'shift' => 'Shift Pagi',
        ]))
        ->assertOk();

    $job = JobMaster::where('job_number', 'N1-'.$newPlan->id)->first();
    expect($job)->not->toBeNull();
    expect($job->job_name)->toBe('JOB NEW');
    expect((int) $job->target_qty)->toBe(100);
});
