<?php

use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\MasterShift;
use App\Models\ProductionPlan;
use App\Models\RepairRejectLog;
use App\Models\ShiftSubmission;
use App\Models\User;
use App\Services\CutOffService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function ssFixture(string $date)
{
    Carbon::setTestNow($date . ' 10:00:00');
    $line = LineMaster::create(['line_code' => 'PRESS_A', 'line_name' => 'Press A']);
    $shift = MasterShift::firstOrCreate(
    ['name' => 'Shift Pagi'],
    ['planned_start_time' => '07:30', 'planned_end_time' => '21:00']
);
    $user = User::create([
        'name' => 'UAT', 'nrp' => 'SS-001', 'password' => bcrypt('secret'), 'role' => 'superadmin',
    ]);

    $plan = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date'      => $date,
        'shift_name'     => 'Shift Pagi',
        'shift_master_id' => $shift->id,
        'press_name'     => 'Press A',
        'row_type'       => 'job',
        'job_no'         => 'JOB-1',
        'plan'           => 100,
    ]);

    $jm = JobMaster::create([
        'job_number' => 'JOB-1-' . $plan->id,
        'job_name'   => 'Job Satu',
        'line'       => 'Press A',
        'status'     => 'running',
        'started_at' => Carbon::parse($date . ' 08:00:00'),
    ]);

    return compact('date', 'line', 'shift', 'user', 'plan', 'jm');
}

function ssOldDowntime(int $jmId): Downtime
{
    return Downtime::create([
        'job_master_id'  => $jmId,
        'jenis_downtime' => 'mesin rusak',
        'problem'        => null,
        'penyebab'       => null,
        'action'         => null,
        'start_time'     => Carbon::parse('2026-08-04 09:00:00'),
        'finish_time'    => Carbon::parse('2026-08-04 09:30:00'),
    ]);
}

function ssOldRepair(int $jmId): RepairRejectLog
{
    $rr = RepairRejectLog::create([
        'job_master_id'  => $jmId,
        'type'           => 'repair',
        'defect_name'    => 'BODY RUSAK',
        'qty_a'          => 5,
        'area_problem'   => null,
        'root_cause'     => null,
        'countermeasure' => null,
    ]);
    RepairRejectLog::where('id', $rr->id)->update(['created_at' => Carbon::parse('2026-08-04 09:00:00')]);
    return $rr;
}

function ssTodayDowntime(int $jmId, string $date): Downtime
{
    return Downtime::create([
        'job_master_id'  => $jmId,
        'jenis_downtime' => 'mesin rusak',
        'problem'        => null,
        'penyebab'       => null,
        'action'         => null,
        'start_time'     => Carbon::parse($date . ' 08:30:00'),
    ]);
}

test('akhiri shift mengabaikan downtime & repair/reject dari hari sebelumnya (scope jadwal sekarang)', function () {
    $f = ssFixture('2026-08-05');
    ssOldDowntime($f['jm']->id);
    ssOldRepair($f['jm']->id);

    $this->mock(CutOffService::class, function ($mock) {
        $mock->shouldReceive('processCutOff')->andReturn(['created' => 0]);
    });

    $resp = $this->actingAs($f['user'])->postJson(
        '/operational/shift/' . $f['line']->id . '/submit',
        ['date' => '2026-08-05', 'shift' => 'Shift Pagi']
    );

    $resp->assertOk()->assertJson(['success' => true]);
    expect(ShiftSubmission::count())->toBe(1);
});

test('akhiri shift hanya menandai downtime tidak lengkap pada jadwal hari ini (bukan riwayat lama)', function () {
    $f = ssFixture('2026-08-05');
    ssOldDowntime($f['jm']->id);
    ssTodayDowntime($f['jm']->id, '2026-08-05');

    $resp = $this->actingAs($f['user'])->postJson(
        '/operational/shift/' . $f['line']->id . '/submit',
        ['date' => '2026-08-05', 'shift' => 'Shift Pagi']
    );

    $resp->assertStatus(422)->assertJson(['success' => false, 'has_issues' => true]);
    $issues = $resp->json('issues');
    expect($issues['dt'])->toHaveCount(1);
    expect($issues['dt'][0]['job_master_id'])->toBe($f['jm']->id);
});

test('akhiri shift hanya menandai repair/reject tidak lengkap pada jadwal hari ini', function () {
    $f = ssFixture('2026-08-05');
    ssOldRepair($f['jm']->id);

    RepairRejectLog::create([
        'job_master_id'  => $f['jm']->id,
        'type'           => 'repair',
        'defect_name'    => 'BODY RUSAK',
        'qty_a'          => 3,
        'area_problem'   => null,
        'root_cause'     => null,
        'countermeasure' => null,
    ]);

    $resp = $this->actingAs($f['user'])->postJson(
        '/operational/shift/' . $f['line']->id . '/submit',
        ['date' => '2026-08-05', 'shift' => 'Shift Pagi']
    );

    $resp->assertStatus(422)->assertJson(['success' => false, 'has_issues' => true]);
    $issues = $resp->json('issues');
    expect($issues['repair'])->toHaveCount(1);
    expect($issues['repair'][0]['job_master_id'])->toBe($f['jm']->id);
});