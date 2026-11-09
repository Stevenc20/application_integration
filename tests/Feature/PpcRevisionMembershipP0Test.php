<?php

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\RepairRejectLog;
use App\Models\ScheduleRevision;
use App\Models\User;
use App\Services\DashboardRealtimeService;
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

function membershipLine(string $name = 'PRESS A', string $code = 'L-A'): LineMaster
{
    return LineMaster::create([
        'line_code' => $code,
        'line_name' => $name,
        'status' => 'active',
    ]);
}

function membershipPlan(LineMaster $line, string $date, array $overrides = []): ProductionPlan
{
    return ProductionPlan::create(array_merge([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => $line->line_name,
        'job_master' => 'JOB A',
        'job_no' => 'A1',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 100,
        'status' => 'pending',
        'source_type' => 'ppc',
    ], $overrides));
}

function membershipRow(array $overrides = []): array
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

function membershipSheet(string $press, array $rows, string $shift = 'Shift Pagi (Rev)'): array
{
    return [
        'shift_name' => $shift,
        'press_name' => $press,
        'rows' => $rows,
    ];
}

test('omitted pending plan and job become inactive without being deleted', function () {
    $date = '2026-11-09';
    $line = membershipLine();

    $planA = membershipPlan($line, $date);
    $planB = membershipPlan($line, $date, [
        'job_master' => 'JOB B',
        'job_no' => 'B1',
        'row_no' => 2,
    ]);
    $jobB = JobMaster::create([
        'job_number' => 'B1-'.$planB->id,
        'job_name' => 'JOB B',
        'line' => 'PRESS A',
        'status' => 'pending',
        'target_qty' => 100,
    ]);

    $result = app(PpcScheduleMergeService::class)->merge([
        membershipSheet('PRESS A', [membershipRow()]),
    ], $date);

    $planB->refresh();
    $jobB->refresh();

    expect($planB)->not->toBeNull()
        ->and($planB->ppc_revision_active)->toBeFalse()
        ->and($planB->ppc_revision_id)->toBe($result['revision_id'])
        ->and($jobB->ppc_revision_active)->toBeFalse()
        ->and($jobB->ppc_revision_id)->toBe($result['revision_id']);

    $activeIds = ProductionPlan::activePpcRevision()
        ->whereDate('plan_date', $date)
        ->pluck('id')
        ->all();

    expect($activeIds)->toContain($planA->id)
        ->and($activeIds)->not->toContain($planB->id)
        ->and(JobMaster::activePpcRevision()->pluck('id')->all())->not->toContain($jobB->id);

    expect(collect($result['not_in_revision'])->pluck('job_no')->all())->toBe(['B1']);
});

test('omitted running plan keeps actuals and remains operationally visible as history', function () {
    $date = '2026-11-09';
    $line = membershipLine();

    $planA = membershipPlan($line, $date);
    $running = membershipPlan($line, $date, [
        'job_master' => 'JOB RUN',
        'job_no' => 'RUN-1',
        'row_no' => 2,
        'status' => 'running',
        'ok' => 35,
        'act_start' => '07:35',
    ]);
    $job = JobMaster::create([
        'job_number' => 'RUN-1-'.$running->id,
        'job_name' => 'JOB RUN',
        'line' => 'PRESS A',
        'status' => 'running',
        'target_qty' => 100,
        'started_at' => now(),
    ]);

    app(PpcScheduleMergeService::class)->merge([
        membershipSheet('PRESS A', [membershipRow()]),
    ], $date);

    $running->refresh();
    $job->refresh();

    expect($running)->not->toBeNull()
        ->and($running->ppc_revision_active)->toBeFalse()
        ->and($running->status)->toBe('running')
        ->and((float) $running->ok)->toBe(35.0)
        ->and($running->act_start)->toBe('07:35')
        ->and($job->ppc_revision_active)->toBeFalse()
        ->and(app(PpcScheduleMergeService::class)->planHasProductionActivity($running))->toBeTrue();

    $orphanPlan = ProductionPlan::find($running->id);
    expect($orphanPlan)->not->toBeNull();
});

test('an omitted plan reactivates when it is listed again in a later revision', function () {
    $date = '2026-11-09';
    $line = membershipLine();

    $planA = membershipPlan($line, $date);
    $planB = membershipPlan($line, $date, [
        'job_master' => 'JOB B',
        'job_no' => 'B1',
        'row_no' => 2,
    ]);

    $service = app(PpcScheduleMergeService::class);

    $first = $service->merge([
        membershipSheet('PRESS A', [membershipRow()]),
    ], $date);

    expect($planB->fresh()->ppc_revision_active)->toBeFalse();

    $second = $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(),
            membershipRow([
                'job_master' => 'JOB B',
                'job_no' => 'B1',
                'row_no' => 2,
                'plan' => 150,
            ]),
        ]),
    ], $date);

    $planB->refresh();

    expect($planB->ppc_revision_active)->toBeTrue()
        ->and($planB->ppc_revision_id)->toBe($second['revision_id'])
        ->and((float) $planB->plan)->toBe(150.0)
        ->and(ProductionPlan::whereDate('plan_date', $date)->count())->toBe(2)
        ->and($first['revision_id'])->not->toBe($second['revision_id']);
});

test('revision membership is scoped to the imported shift and press only', function () {
    $date = '2026-11-09';
    $lineA = membershipLine('PRESS A', 'L-A');
    $lineB = membershipLine('PRESS B', 'L-B');

    membershipPlan($lineA, $date);
    $planB = membershipPlan($lineB, $date, [
        'press_name' => 'PRESS B',
        'job_master' => 'JOB B',
        'job_no' => 'B1',
    ]);

    app(PpcScheduleMergeService::class)->merge([
        membershipSheet('PRESS A', [membershipRow()]),
    ], $date);

    expect($planB->fresh()->ppc_revision_active)->toBeNull();
});

test('running target changes are blocked and captured in the revision audit', function () {
    $date = '2026-11-09';
    $line = membershipLine();

    $running = membershipPlan($line, $date, [
        'status' => 'running',
        'ok' => 20,
        'act_start' => '07:30',
        'start_time' => '07:30',
        'finish_time' => '12:00',
    ]);

    $result = app(PpcScheduleMergeService::class)->merge([
        membershipSheet('PRESS A', [
            membershipRow(['plan' => 999, 'start_time' => '08:00', 'finish_time' => '13:00']),
        ]),
    ], $date);

    $running->refresh();

    expect((float) $running->plan)->toBe(100.0)
        ->and($running->start_time)->toBe('07:30')
        ->and($running->finish_time)->toBe('12:00')
        ->and(collect($result['blocked_changes'])->pluck('field')->all())
        ->toContain('plan', 'start_time', 'finish_time');

    $revision = ScheduleRevision::find($result['revision_id']);

    expect($revision)->not->toBeNull()
        ->and($revision->section_scope)->toBe([['shift' => 'Pagi', 'press' => 'PRESS A']])
        ->and($revision->snapshot_after['status'])->toBe('completed')
        ->and($revision->snapshot_after['blocked_changes'])->toHaveCount(3)
        ->and($revision->snapshot_after['counts']['blocked_changes'])->toBe(3);
});

test('1 & 2. revision with added recovery at top and 2-or-3 break-split slices preserves root schedule identity', function () {
    $date = '2026-11-09';
    $line = membershipLine('PRESS A', 'L-A');
    $service = app(PpcScheduleMergeService::class);

    // Base Shift Pagi:
    // - Job G-031 WIP split into 2 slices across 12:00-13:00 break (rows 1, 2=break, 3)
    // - Job GT-5154 FNS split into 3 slices across 2 breaks (rows 4, 5=break, 6, 7=break, 8)
    $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'PART G031', 'job_no' => 'G-031 WIP', 'row_no' => 1, 'plan' => 160, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '12:00']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 1', 'row_no' => 2, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '12:00', 'finish_time' => '13:00']),
            membershipRow(['job_master' => 'PART G031', 'job_no' => 'G-031 WIP', 'row_no' => 3, 'plan' => 540, 'dct' => 0, 'start_time' => '13:00', 'finish_time' => '15:00']),
            membershipRow(['job_master' => 'PART GT5154', 'job_no' => 'GT-5154 FNS', 'row_no' => 4, 'plan' => 200, 'dct' => 15, 'start_time' => '15:00', 'finish_time' => '16:30']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 2', 'row_no' => 5, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '16:30', 'finish_time' => '16:45']),
            membershipRow(['job_master' => 'PART GT5154', 'job_no' => 'GT-5154 FNS', 'row_no' => 6, 'plan' => 300, 'dct' => 0, 'start_time' => '16:45', 'finish_time' => '18:30']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 3', 'row_no' => 7, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '18:30', 'finish_time' => '19:00']),
            membershipRow(['job_master' => 'PART GT5154', 'job_no' => 'GT-5154 FNS', 'row_no' => 8, 'plan' => 250, 'dct' => 0, 'start_time' => '19:00', 'finish_time' => '20:00']),
        ], 'Shift Pagi'),
    ], $date);

    $baseG031Slices = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'G-031 WIP')->orderBy('row_no')->get();
    $baseGtSlices = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'GT-5154 FNS')->orderBy('row_no')->get();

    expect($baseG031Slices)->toHaveCount(2)
        ->and($baseG031Slices[0]->parent_job_id)->toBeNull()
        ->and($baseG031Slices[1]->parent_job_id)->toBe($baseG031Slices[0]->id)
        ->and((float) $baseG031Slices[0]->original_plan)->toBe(700.0);

    expect($baseGtSlices)->toHaveCount(3)
        ->and($baseGtSlices[0]->parent_job_id)->toBeNull()
        ->and($baseGtSlices[1]->parent_job_id)->toBe($baseGtSlices[0]->id)
        ->and($baseGtSlices[2]->parent_job_id)->toBe($baseGtSlices[0]->id)
        ->and((float) $baseGtSlices[0]->original_plan)->toBe(750.0);

    $rootG031Id = $baseG031Slices[0]->id;
    $childG031Id = $baseG031Slices[1]->id;
    $rootGtId = $baseGtSlices[0]->id;
    $childGt1Id = $baseGtSlices[1]->id;
    $childGt2Id = $baseGtSlices[2]->id;

    // Upload Shift Pagi (Rev): 2 Recovery items inserted at rows 1 & 2, shifting all row_no down (+2) and updating slice quantities
    $revResult = $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'PART REC 1', 'job_no' => 'REC-001', 'row_no' => 1, 'plan' => 100, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '08:30', 'keterangan' => 'Recovery']),
            membershipRow(['job_master' => 'PART REC 2', 'job_no' => 'REC-002', 'row_no' => 2, 'plan' => 120, 'dct' => 15, 'start_time' => '08:30', 'finish_time' => '09:30', 'keterangan' => 'Recovery']),
            membershipRow(['job_master' => 'PART G031', 'job_no' => 'G-031 WIP', 'row_no' => 3, 'plan' => 350, 'dct' => 15, 'start_time' => '09:30', 'finish_time' => '12:00']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 1', 'row_no' => 4, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '12:00', 'finish_time' => '13:00']),
            membershipRow(['job_master' => 'PART G031', 'job_no' => 'G-031 WIP', 'row_no' => 5, 'plan' => 250, 'dct' => 0, 'start_time' => '13:00', 'finish_time' => '15:00']),
            membershipRow(['job_master' => 'PART GT5154', 'job_no' => 'GT-5154 FNS', 'row_no' => 6, 'plan' => 300, 'dct' => 15, 'start_time' => '15:00', 'finish_time' => '16:30']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 2', 'row_no' => 7, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '16:30', 'finish_time' => '16:45']),
            membershipRow(['job_master' => 'PART GT5154', 'job_no' => 'GT-5154 FNS', 'row_no' => 8, 'plan' => 300, 'dct' => 0, 'start_time' => '16:45', 'finish_time' => '18:30']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 3', 'row_no' => 9, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '18:30', 'finish_time' => '19:00']),
            membershipRow(['job_master' => 'PART GT5154', 'job_no' => 'GT-5154 FNS', 'row_no' => 10, 'plan' => 200, 'dct' => 0, 'start_time' => '19:00', 'finish_time' => '20:00']),
        ], 'Shift Pagi (Rev)'),
    ], $date);

    expect($revResult['counts']['added'])->toBe(2)
        ->and($revResult['counts']['not_in_revision'])->toBe(0);

    $afterG031 = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'G-031 WIP')->orderBy('row_no')->get();
    $afterGt = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'GT-5154 FNS')->orderBy('row_no')->get();

    expect($afterG031->pluck('id')->all())->toBe([$rootG031Id, $childG031Id])
        ->and($afterG031[0]->parent_job_id)->toBeNull()
        ->and($afterG031[1]->parent_job_id)->toBe($rootG031Id)
        ->and($afterG031[0]->session_no)->toBe(1)
        ->and($afterG031[1]->session_no)->toBe(2)
        ->and($afterG031[0]->effectiveTargetQty())->toBe(600);

    expect($afterGt->pluck('id')->all())->toBe([$rootGtId, $childGt1Id, $childGt2Id])
        ->and($afterGt[0]->parent_job_id)->toBeNull()
        ->and($afterGt[1]->parent_job_id)->toBe($rootGtId)
        ->and($afterGt[2]->parent_job_id)->toBe($rootGtId)
        ->and($afterGt[0]->session_no)->toBe(1)
        ->and($afterGt[1]->session_no)->toBe(2)
        ->and($afterGt[2]->session_no)->toBe(3)
        ->and($afterGt[0]->effectiveTargetQty())->toBe(800);
});

test('3 & 4. two independent schedules with same job_no remain separate even when row_no shifts', function () {
    $date = '2026-11-09';
    $line = membershipLine('PRESS A', 'L-A');
    $service = app(PpcScheduleMergeService::class);

    // Base schedule: RC-2770 appears at row 1 (morning batch) and again at row 3 (afternoon batch after another job)
    $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'PART RC', 'job_no' => 'RC-2770', 'row_no' => 1, 'plan' => 400, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '09:30']),
            membershipRow(['job_master' => 'PART OTHER', 'job_no' => 'OTH-01', 'row_no' => 2, 'plan' => 300, 'dct' => 15, 'start_time' => '09:30', 'finish_time' => '11:30']),
            membershipRow(['job_master' => 'PART RC', 'job_no' => 'RC-2770', 'row_no' => 3, 'plan' => 500, 'dct' => 15, 'start_time' => '13:00', 'finish_time' => '15:30']),
        ], 'Shift Pagi'),
    ], $date);

    $rcPlans = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'RC-2770')->orderBy('row_no')->get();
    expect($rcPlans)->toHaveCount(2);
    $firstRcId = $rcPlans[0]->id;
    $secondRcId = $rcPlans[1]->id;

    // Revision: Recovery inserted at row 1, shifting RC-2770 #1 to row 2 (plan 450) and RC-2770 #2 to row 4 (plan 550)
    $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'PART REC', 'job_no' => 'REC-10', 'row_no' => 1, 'plan' => 100, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '08:00']),
            membershipRow(['job_master' => 'PART RC', 'job_no' => 'RC-2770', 'row_no' => 2, 'plan' => 450, 'dct' => 15, 'start_time' => '08:00', 'finish_time' => '10:00']),
            membershipRow(['job_master' => 'PART OTHER', 'job_no' => 'OTH-01', 'row_no' => 3, 'plan' => 300, 'dct' => 15, 'start_time' => '10:00', 'finish_time' => '12:00']),
            membershipRow(['job_master' => 'PART RC', 'job_no' => 'RC-2770', 'row_no' => 4, 'plan' => 550, 'dct' => 15, 'start_time' => '13:30', 'finish_time' => '16:00']),
        ], 'Shift Pagi (Rev)'),
    ], $date);

    $afterRcPlans = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'RC-2770')->orderBy('row_no')->get();
    expect($afterRcPlans)->toHaveCount(2)
        ->and($afterRcPlans[0]->id)->toBe($firstRcId)
        ->and($afterRcPlans[1]->id)->toBe($secondRcId)
        ->and($afterRcPlans[0]->parent_job_id)->toBeNull()
        ->and($afterRcPlans[1]->parent_job_id)->toBeNull()
        ->and((float) $afterRcPlans[0]->plan)->toBe(450.0)
        ->and((float) $afterRcPlans[1]->plan)->toBe(550.0);
});

test('5. PLAN = 0 with VLOOKUP CT > 0 but without START/FINISH or with defer note is ignored as note and never becomes an active job', function () {
    $date = '2026-11-09';
    $line = membershipLine('PRESS A', 'L-A');
    $service = app(PpcScheduleMergeService::class);

    // Base schedule has AES-029 FNS as an active job
    $basePlan = membershipPlan($line, $date, [
        'job_master' => 'PART AES029',
        'job_no' => 'AES-029 FNS',
        'row_no' => 2,
        'plan' => 400,
    ]);

    // Revision sets AES-029 FNS to PLAN = 0, start_time = '', finish_time = '', ct = 4.5 (from VLOOKUP), ket = 'Ok Proses Shift 2'
    $result = $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'JOB A', 'job_no' => 'A1', 'row_no' => 1, 'plan' => 100]),
            membershipRow([
                'job_master' => 'PART AES029',
                'job_no' => 'AES-029 FNS',
                'row_no' => 2,
                'plan' => 0,
                'ct' => 4.5,
                'start_time' => '',
                'finish_time' => '',
                'keterangan' => 'Ok Proses Shift 2',
            ]),
        ], 'Shift Pagi (Rev)'),
    ], $date);

    $basePlan->refresh();
    expect($basePlan->ppc_revision_active)->toBeFalse()
        ->and($result['counts']['rows_imported'])->toBe(1)
        ->and(ProductionPlan::activePpcRevision()->whereDate('plan_date', $date)->pluck('job_no')->all())->toBe(['A1']);
});

test('6 & 7. DELETE instruction removes all slices of a multi-slice root schedule, protects active schedules, and keeps independent schedules with same job_no intact', function () {
    $date = '2026-11-09';
    $line = membershipLine('PRESS A', 'L-A');
    $service = app(PpcScheduleMergeService::class);

    // Base schedule:
    // - Schedule 1 of GT-5154: split across break (rows 1 & 3), unstarted -> should be deleted completely (both slices)
    // - Schedule 2 of GT-5154: independent batch at row 5 -> should remain intact!
    // - Schedule 3 (ACTIVE-SPLIT): split across break (rows 6 & 8) with activity -> should be protected (both slices kept & marked inactive)
    $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'PV-006B', 'job_no' => 'GT-5154', 'row_no' => 1, 'plan' => 120, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '12:00']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT', 'row_no' => 2, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '12:00', 'finish_time' => '13:00']),
            membershipRow(['job_master' => 'PV-006B', 'job_no' => 'GT-5154', 'row_no' => 3, 'plan' => 180, 'dct' => 0, 'start_time' => '13:00', 'finish_time' => '14:30']),
            membershipRow(['job_master' => 'PART MID', 'job_no' => 'MID-01', 'row_no' => 4, 'plan' => 200, 'dct' => 15, 'start_time' => '14:30', 'finish_time' => '16:00']),
            membershipRow(['job_master' => 'PV-006B', 'job_no' => 'GT-5154', 'row_no' => 5, 'plan' => 250, 'dct' => 15, 'start_time' => '16:00', 'finish_time' => '17:30']),
            membershipRow(['job_master' => 'PART ACT', 'job_no' => 'ACT-SPLIT', 'row_no' => 6, 'plan' => 300, 'dct' => 15, 'start_time' => '17:30', 'finish_time' => '18:30']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 2', 'row_no' => 7, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '18:30', 'finish_time' => '19:00']),
            membershipRow(['job_master' => 'PART ACT', 'job_no' => 'ACT-SPLIT', 'row_no' => 8, 'plan' => 200, 'dct' => 0, 'start_time' => '19:00', 'finish_time' => '20:00']),
        ], 'Shift Pagi'),
    ], $date);

    $gtPlans = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'GT-5154')->orderBy('row_no')->get();
    expect($gtPlans)->toHaveCount(3);
    $firstSliceId = $gtPlans[0]->id;
    $secondSliceId = $gtPlans[1]->id;
    $independentGtId = $gtPlans[2]->id;

    $actPlans = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'ACT-SPLIT')->orderBy('row_no')->get();
    expect($actPlans)->toHaveCount(2);
    $actPlans[0]->update(['status' => 'running', 'ok' => 50, 'act_start' => '17:35']);

    // Revision: Delete first GT-5154 (multi-slice) and Delete ACT-SPLIT (active multi-slice), while keeping independent GT-5154
    $revResult = $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'PART MID', 'job_no' => 'MID-01', 'row_no' => 1, 'plan' => 200, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '09:00']),
            membershipRow(['job_master' => 'PV-006B', 'job_no' => 'GT-5154', 'row_no' => 2, 'plan' => 250, 'dct' => 15, 'start_time' => '16:00', 'finish_time' => '17:30']),
            membershipRow(['job_master' => 'PV-006B', 'job_no' => 'GT-5154', 'row_no' => 10, 'plan' => 0, 'start_time' => '', 'finish_time' => '', 'keterangan' => 'Delete Plan Shift 1']),
            membershipRow(['job_master' => 'PART ACT', 'job_no' => 'ACT-SPLIT', 'row_no' => 11, 'plan' => 0, 'start_time' => '', 'finish_time' => '', 'keterangan' => 'Delete Plan Shift 1']),
        ], 'Shift Pagi (Rev)'),
    ], $date);

    // Both slices of the first GT-5154 schedule are deleted
    expect(ProductionPlan::find($firstSliceId))->toBeNull()
        ->and(ProductionPlan::find($secondSliceId))->toBeNull()
        ->and($revResult['counts']['deleted'])->toBe(2);

    // Independent GT-5154 schedule remains active and untouched
    $remainingGt = ProductionPlan::find($independentGtId);
    expect($remainingGt)->not->toBeNull()
        ->and($remainingGt->isActivePpcRevision())->toBeTrue()
        ->and((float) $remainingGt->plan)->toBe(250.0);

    // Active multi-slice schedule is protected from deletion (both slices preserved in DB, marked inactive)
    expect(ProductionPlan::find($actPlans[0]->id))->not->toBeNull()
        ->and(ProductionPlan::find($actPlans[1]->id))->not->toBeNull()
        ->and($actPlans[0]->fresh()->ppc_revision_active)->toBeFalse()
        ->and($actPlans[1]->fresh()->ppc_revision_active)->toBeFalse()
        ->and((float) $actPlans[0]->fresh()->ok)->toBe(50.0)
        ->and($revResult['counts']['protected_from_deletion'])->toBe(2);
});

test('8, 9 & 10. running and completed jobs protect target/time/history, respect DailyProduction actual=0 and RepairRejectLog, and render blocked_changes in UI', function () {
    $date = '2026-11-09';
    $line = membershipLine('PRESS A', 'L-A');
    $service = app(PpcScheduleMergeService::class);

    // 1. Job with DailyProduction actual_ok = 0 (must still be recognized as having activity!)
    $planZeroDp = membershipPlan($line, $date, [
        'job_master' => 'JOB DP ZERO',
        'job_no' => 'DP-0',
        'row_no' => 1,
        'plan' => 300,
        'start_time' => '07:30',
        'finish_time' => '09:30',
    ]);
    $jobZeroDp = JobMaster::create([
        'job_number' => 'DP-0-'.$planZeroDp->id,
        'job_name' => 'JOB DP ZERO',
        'line' => 'PRESS A',
        'status' => 'pending',
        'target_qty' => 300,
    ]);
    DailyProduction::create([
        'job_master_id' => $jobZeroDp->id,
        'work_date' => $date,
        'actual_qty' => 0,
        'actual_ok' => 0,
        'actual_repair' => 0,
        'actual_reject' => 0,
    ]);

    // 2. Completed job with RepairRejectLog
    $planCompleted = membershipPlan($line, $date, [
        'job_master' => 'JOB DONE',
        'job_no' => 'DONE-1',
        'row_no' => 2,
        'plan' => 500,
        'ok' => 500,
        'status' => 'completed',
        'act_start' => '09:30',
        'act_finish' => '12:00',
        'start_time' => '09:30',
        'finish_time' => '12:00',
    ]);
    $jobCompleted = JobMaster::create([
        'job_number' => 'DONE-1-'.$planCompleted->id,
        'job_name' => 'JOB DONE',
        'line' => 'PRESS A',
        'status' => 'complete',
        'target_qty' => 500,
        'started_at' => Carbon::parse($date.' 09:30:00'),
        'finished_at' => Carbon::parse($date.' 12:00:00'),
    ]);
    RepairRejectLog::create([
        'job_master_id' => $jobCompleted->id,
        'type' => 'repair',
        'defect_name' => 'Burry',
        'qty_a' => 2,
    ]);

    expect($service->jobHasActivity($jobZeroDp))->toBeTrue()
        ->and($service->planHasProductionActivity($planZeroDp))->toBeTrue()
        ->and($service->jobHasActivity($jobCompleted))->toBeTrue();

    $result = $service->merge([
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'JOB DP ZERO', 'job_no' => 'DP-0', 'row_no' => 1, 'plan' => 450, 'start_time' => '08:00', 'finish_time' => '10:00']),
            membershipRow(['job_master' => 'JOB DONE', 'job_no' => 'DONE-1', 'row_no' => 2, 'plan' => 650, 'start_time' => '10:00', 'finish_time' => '13:00']),
        ], 'Shift Pagi (Rev)'),
    ], $date);

    $planZeroDp->refresh();
    $planCompleted->refresh();

    expect((float) $planZeroDp->plan)->toBe(300.0)
        ->and($planZeroDp->start_time)->toBe('07:30')
        ->and((float) $planCompleted->plan)->toBe(500.0)
        ->and((float) $planCompleted->ok)->toBe(500.0)
        ->and($planCompleted->status)->toBe('completed')
        ->and($result['counts']['blocked_changes'])->toBe(6);

    $lockStates = collect($result['blocked_changes'])->pluck('lock_state', 'job_no')->all();
    expect($lockStates['DP-0'])->toBe('running')
        ->and($lockStates['DONE-1'])->toBe('completed');

    // Verify UI renders blocked_changes and default modal date = $date
    $user = User::create([
        'name' => 'PPC Viewer',
        'nrp' => 'PPC-VW',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['import_preview' => $result['preview']])
        ->get(route('ppc.planning.production_plan', ['date' => $date, 'press' => 'PRESS A', 'shift' => 'Shift Pagi']))
        ->assertOk();

    $html = $response->getContent();
    expect($html)->toContain('Blocked changes: 6')
        ->and($html)->toContain('Perubahan Diblokir / Terkunci Aktivitas (6)')
        ->and($html)->toContain('name="date" value="'.$date.'"');
});

test('11, 12, 13, 14 & 15. modal date follows opened date, case-insensitive shift filter isolates shifts/dates, repeated imports are idempotent, and Dashboard + Input Harian show 1 root schedule', function () {
    Carbon::setTestNow('2026-11-09 09:00:00');
    $date = '2026-11-09';
    $otherDate = '2026-11-08';
    $line = membershipLine('PRESS A', 'L-A');

    // Existing Shift Malam plan on same date and Shift Pagi plan on another date
    $nightPlan = membershipPlan($line, $date, [
        'shift_name' => 'Shift Malam',
        'job_master' => 'NIGHT PART',
        'job_no' => 'NGT-01',
        'row_no' => 1,
        'plan' => 250,
    ]);
    $prevDatePlan = membershipPlan($line, $otherDate, [
        'shift_name' => 'Shift Pagi',
        'job_master' => 'PREV PART',
        'job_no' => 'PRV-01',
        'row_no' => 1,
        'plan' => 180,
    ]);

    // Build Excel file with both 'Shift Pagi (Rev)' (containing a break-split job) and 'Shift Malam'
    $spreadsheet = new Spreadsheet;
    $sheet1 = $spreadsheet->getActiveSheet();
    $sheet1->setTitle('Shift Pagi (Rev)');
    $sheet1->setCellValue('C1', 'HARI');
    $sheet1->setCellValue('D1', 'SENIN');
    $sheet1->setCellValue('C2', 'TGL');
    $sheet1->setCellValue('D2', '09 NOVEMBER 2026');
    $sheet1->setCellValue('C3', 'JAM');
    $sheet1->setCellValue('D3', 'PAGI');
    $sheet1->setCellValue('C4', 'PRESS A');

    $headers = [
        'NO.', 'JOB MASTER', 'TYPE PLT', 'QTY/PLT', 'KEB. MTL', 'TOTAL PLT',
        'JOB NO.', 'EACH PART', 'PLAN (PCS)', 'OK', 'REPAIR', 'REJECT',
        'TOTAL MESIN', 'CT (DETIK)', 'PROCESS TIME', 'REG. ACTIVE', 'DCT',
        'MCT', 'PLAN DCT', 'TPT', 'GSPH', 'START', 'FINISH', 'KETERANGAN',
    ];
    $sheet1->fromArray($headers, null, 'A5');
    $sheet1->fromArray([
        [1, 'PART SPLIT A', null, 10, 1, 10, 'SPL-01', null, 300, 0, 0, 0, 1, 5, 50, 0, 15, 0, 50, 100, 200, '07:30', '12:00', null],
        [2, 'BREAK', null, 0, 0, 0, 'ISTIRAHAT', null, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, '12:00', '13:00', 'ISTIRAHAT'],
        [3, 'PART SPLIT A', null, 10, 1, 10, 'SPL-01', null, 200, 0, 0, 0, 1, 5, 50, 0, 0, 0, 50, 100, 200, '13:00', '15:00', null],
    ], null, 'A6');

    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Shift Malam');
    $sheet2->setCellValue('C1', 'HARI');
    $sheet2->setCellValue('D1', 'SENIN');
    $sheet2->setCellValue('C2', 'TGL');
    $sheet2->setCellValue('D2', '09 NOVEMBER 2026');
    $sheet2->setCellValue('C3', 'JAM');
    $sheet2->setCellValue('D3', 'MALAM');
    $sheet2->setCellValue('C4', 'PRESS A');
    $sheet2->fromArray($headers, null, 'A5');
    $sheet2->fromArray([
        [1, 'NIGHT PART', null, 10, 1, 10, 'NGT-01', null, 999, 0, 0, 0, 1, 5, 50, 0, 15, 0, 50, 100, 200, '21:00', '02:00', null],
    ], null, 'A6');

    $path = tempnam(sys_get_temp_dir(), 'ppc_rev_').'.xlsx';
    $path2 = tempnam(sys_get_temp_dir(), 'ppc_rev2_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    copy($path, $path2);
    $spreadsheet->disconnectWorksheets();

    $user = User::create([
        'name' => 'PPC User',
        'nrp' => 'PPC-15',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);

    // Upload with uppercase/mixed-case shift_filter = 'PAGI'
    $file1 = new UploadedFile($path, '09 November 2026.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    $this->actingAs($user)
        ->post(route('ppc.planning.production_plan.import'), [
            'excel_file' => $file1,
            'date' => $date,
            'shift_filter' => 'PAGI',
        ])
        ->assertRedirect();

    // Shift Malam and previous date must remain untouched
    expect((float) $nightPlan->fresh()->plan)->toBe(250.0)
        ->and((float) $prevDatePlan->fresh()->plan)->toBe(180.0);

    // Repeat upload of the exact same revision (idempotency check)
    $file2 = new UploadedFile($path2, '09 November 2026.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    $this->actingAs($user)
        ->post(route('ppc.planning.production_plan.import'), [
            'excel_file' => $file2,
            'date' => $date,
            'shift_filter' => 'Shift Pagi',
        ])
        ->assertRedirect();

    $splPlans = ProductionPlan::whereDate('plan_date', $date)->where('job_no', 'SPL-01')->orderBy('row_no')->get();
    expect($splPlans)->toHaveCount(2)
        ->and($splPlans[0]->parent_job_id)->toBeNull()
        ->and($splPlans[1]->parent_job_id)->toBe($splPlans[0]->id)
        ->and($splPlans[0]->effectiveTargetQty())->toBe(500);

    // Open Input Harian: only 1 root JobMaster is created with target_qty = 500
    $this->actingAs($user)
        ->get(route('operational.input_harian', ['date' => $date, 'shift' => 'Shift Pagi', 'line' => 'PRESS A']))
        ->assertOk();

    $splJobs = JobMaster::where('line', 'PRESS A')->where('job_number', 'like', 'SPL-01-%')->get();
    expect($splJobs)->toHaveCount(1)
        ->and($splJobs[0]->job_number)->toBe('SPL-01-'.$splPlans[0]->id)
        ->and((int) $splJobs[0]->target_qty)->toBe(500);

    // DashboardRealtimeService also counts 1 root job with target 500
    $rtPayload = app(DashboardRealtimeService::class)->getLineMetrics('PRESS A', $date, 1);
    $qtyKpi = collect($rtPayload['kpi'] ?? [])->firstWhere('desc', 'QTY');
    expect($qtyKpi['plan'] ?? null)->toBe('500')
        ->and((string) ($rtPayload['meta']['jobPlan'] ?? ''))->toBe('1');

    @unlink($path);
    @unlink($path2);
});

test('real Excel simulation (28 Oktober 2026 Shift Pagi -> Shift Pagi Rev) and schema guard verification', function () {
    $date = '2026-10-28';
    $lineA = LineMaster::create([
        'line_code' => 'L-1',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);

    $service = app(PpcScheduleMergeService::class);
    $service->assertRevisionSchemaReady();

    // Exact PRESS A rows from 25. Stamping 28-Oktober-2026.xlsx (Shift Pagi vs Shift Pagi (Rev))
    $baseSheets = [
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => 'BLK RC-2764, RC-2770, AES-012', 'job_no' => 'BLK RC-2764, RC-2770, AES-012', 'row_no' => 1, 'plan' => 800, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '09:20']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 1', 'row_no' => 2, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '09:20', 'finish_time' => '09:30']),
            membershipRow(['job_master' => '57111/12-BZ110', 'job_no' => 'AES-015 WIP', 'row_no' => 3, 'plan' => 500, 'dct' => 15, 'start_time' => '09:30', 'finish_time' => '10:39']),
            membershipRow(['job_master' => '57104-BZ050', 'job_no' => 'AES-008 WIP', 'row_no' => 4, 'plan' => 480, 'dct' => 15, 'start_time' => '10:39', 'finish_time' => '11:36']),
            membershipRow(['job_master' => '53205-BZ170', 'job_no' => 'G-031 WIP', 'row_no' => 5, 'plan' => 160, 'dct' => 15, 'start_time' => '11:36', 'finish_time' => '12:00']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 2', 'row_no' => 6, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '12:00', 'finish_time' => '12:45']),
            membershipRow(['job_master' => '53205-BZ170', 'job_no' => 'G-031 WIP', 'row_no' => 7, 'plan' => 540, 'dct' => 0, 'start_time' => '12:45', 'finish_time' => '14:04']),
            membershipRow(['job_master' => 'T4023/24', 'job_no' => 'IB-061', 'row_no' => 14, 'plan' => 1000, 'dct' => 15, 'start_time' => '18:30', 'finish_time' => '20:00']),
        ], 'Shift Pagi'),
    ];

    $revSheets = [
        membershipSheet('PRESS A', [
            membershipRow(['job_master' => '57165/66-BZ050', 'job_no' => 'AES-047 BLK', 'row_no' => 1, 'plan' => 750, 'dct' => 15, 'start_time' => '07:30', 'finish_time' => '08:15', 'keterangan' => 'Recovery']),
            membershipRow(['job_master' => '61783/84-BZ030', 'job_no' => 'AAS-060 WIP', 'row_no' => 2, 'plan' => 680, 'dct' => 15, 'start_time' => '08:15', 'finish_time' => '09:10', 'keterangan' => 'Recovery']),
            membershipRow(['job_master' => 'BLK RC-2764, RC-2770, AES-012', 'job_no' => 'BLK RC-2764, RC-2770, AES-012', 'row_no' => 5, 'plan' => 430, 'dct' => 15, 'start_time' => '10:30', 'finish_time' => '12:00']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 2', 'row_no' => 6, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '12:00', 'finish_time' => '12:45']),
            membershipRow(['job_master' => 'BLK RC-2764, RC-2770, AES-012', 'job_no' => 'BLK RC-2764, RC-2770, AES-012', 'row_no' => 7, 'plan' => 370, 'dct' => 0, 'start_time' => '12:45', 'finish_time' => '13:30']),
            membershipRow(['job_master' => '57111/12-BZ110', 'job_no' => 'AES-015 WIP', 'row_no' => 8, 'plan' => 500, 'dct' => 15, 'start_time' => '13:30', 'finish_time' => '14:39']),
            membershipRow(['job_master' => '57104-BZ050', 'job_no' => 'AES-008 WIP', 'row_no' => 9, 'plan' => 480, 'dct' => 15, 'start_time' => '14:39', 'finish_time' => '15:36']),
            membershipRow(['job_master' => '53205-BZ170', 'job_no' => 'G-031 WIP', 'row_no' => 13, 'plan' => 350, 'dct' => 15, 'start_time' => '16:30', 'finish_time' => '18:00']),
            membershipRow(['job_master' => 'BREAK', 'job_no' => 'ISTIRAHAT 3', 'row_no' => 16, 'row_type' => 'break', 'plan' => 0, 'ct' => 0, 'start_time' => '18:00', 'finish_time' => '18:30']),
            membershipRow(['job_master' => '53205-BZ170', 'job_no' => 'G-031 WIP', 'row_no' => 17, 'plan' => 250, 'dct' => 0, 'start_time' => '18:30', 'finish_time' => '19:15']),
            membershipRow(['job_master' => 'T4023/24', 'job_no' => 'IB-061', 'row_no' => 20, 'plan' => 0, 'ct' => 4.2, 'start_time' => '', 'finish_time' => '', 'keterangan' => 'Delete (Material T/a)']),
        ], 'Shift Pagi (Rev)'),
    ];

    // 1. Import Base Shift Pagi (PRESS A)
    $service->merge($baseSheets, $date);
    $baseG031 = ProductionPlan::whereDate('plan_date', $date)->where('press_name', 'PRESS A')->where('job_no', 'G-031 WIP')->orderBy('row_no')->get();
    $baseBlk = ProductionPlan::whereDate('plan_date', $date)->where('press_name', 'PRESS A')->where('job_no', 'BLK RC-2764, RC-2770, AES-012')->orderBy('row_no')->get();

    expect($baseG031)->toHaveCount(2)
        ->and($baseG031[0]->parent_job_id)->toBeNull()
        ->and($baseG031[1]->parent_job_id)->toBe($baseG031[0]->id);
    $origG031RootId = $baseG031[0]->id;
    $origG031ChildId = $baseG031[1]->id;
    $origBlkRootId = $baseBlk[0]->id;

    // 2. Import Shift Pagi (Rev) (PRESS A)
    $service->merge($revSheets, $date);
    $revG031 = ProductionPlan::whereDate('plan_date', $date)->where('press_name', 'PRESS A')->where('job_no', 'G-031 WIP')->orderBy('row_no')->get();
    $revBlk = ProductionPlan::whereDate('plan_date', $date)->where('press_name', 'PRESS A')->where('job_no', 'BLK RC-2764, RC-2770, AES-012')->orderBy('row_no')->get();

    // Root & child IDs of G-031 WIP remain identical even though row_no shifted from 5,7 to 13,17!
    expect($revG031)->toHaveCount(2)
        ->and($revG031[0]->id)->toBe($origG031RootId)
        ->and($revG031[1]->id)->toBe($origG031ChildId)
        ->and($revG031[0]->effectiveTargetQty())->toBe(600);

    // BLK RC-2764 was 1 row in Base (plan 800) and split into 2 rows (430 + 370 = 800) in Rev:
    // Root ID stays identical, and 2nd slice is linked as child!
    expect($revBlk)->toHaveCount(2)
        ->and($revBlk[0]->id)->toBe($origBlkRootId)
        ->and($revBlk[0]->parent_job_id)->toBeNull()
        ->and($revBlk[1]->parent_job_id)->toBe($origBlkRootId)
        ->and($revBlk[0]->effectiveTargetQty())->toBe(800);

    // IB-061 had Delete (Material T/a) in Shift Pagi (Rev) -> deleted cleanly
    expect(ProductionPlan::whereDate('plan_date', $date)->where('press_name', 'PRESS A')->where('job_no', 'IB-061')->exists())->toBeFalse();
});
