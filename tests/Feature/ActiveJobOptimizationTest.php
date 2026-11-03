<?php

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function activeJobUser(): User
{
    return User::create([
        'name' => 'Active Job Tester',
        'nrp' => 'AJ-TEST-001',
        'password' => bcrypt('secret'),
        'role' => 'superadmin',
    ]);
}

test('activeJob returns running job with date range query and accurate payload', function () {
    $line = LineMaster::create(['line_code' => 'PA', 'line_name' => 'Line A', 'status' => 'active']);
    $date = '2026-11-03';

    $plan = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date . ' 07:30:00',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'Line A',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'AJ-JOB-001',
        'job_master' => 'JM-AJ-001',
        'plan' => 500,
        'status' => 'running',
    ]);

    $job = JobMaster::create([
        'job_number' => 'AJ-JOB-001-' . $plan->id,
        'job_name' => 'JM-AJ-001',
        'line' => 'Line A',
        'status' => 'running',
        'target_qty' => 500,
        'started_at' => Carbon::parse("{$date} 08:00:00"),
    ]);

    ProductionSession::create([
        'job_master_id' => $job->id,
        'work_date' => $date,
        'start_time' => Carbon::parse("{$date} 08:00:00"),
        'status' => 'running',
        'total_seconds' => 1800,
    ]);

    $user = activeJobUser();

    $response = $this->actingAs($user)
        ->getJson("/operational/active-job?line=Line%20A&shift=Shift%20Pagi&date={$date}");

    $response->assertOk()
        ->assertJson([
            'running' => true,
            'id' => $job->id,
            'status' => 'running',
            'job_name' => 'JM-AJ-001',
            'job_number' => 'AJ-JOB-001-' . $plan->id,
            'is_dandori' => false,
        ]);
});

test('activeJob handles date boundaries and does not leak adjacent days', function () {
    $line = LineMaster::create(['line_code' => 'PA', 'line_name' => 'Line A', 'status' => 'active']);
    $today = '2026-11-03';
    $yesterday = '2026-11-02';

    // Job on yesterday
    $oldPlan = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $yesterday . ' 23:59:59',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'Line A',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'OLD-JOB',
        'job_master' => 'OLD-JM',
        'plan' => 300,
        'status' => 'running',
    ]);

    $oldJob = JobMaster::create([
        'job_number' => 'OLD-JOB-' . $oldPlan->id,
        'job_name' => 'OLD-JM',
        'line' => 'Line A',
        'status' => 'running',
        'target_qty' => 300,
        'started_at' => Carbon::parse("{$yesterday} 08:00:00"),
    ]);

    ProductionSession::create([
        'job_master_id' => $oldJob->id,
        'work_date' => $yesterday,
        'start_time' => Carbon::parse("{$yesterday} 08:00:00"),
        'status' => 'running',
        'total_seconds' => 1000,
    ]);

    $user = activeJobUser();

    // Querying for today (2026-11-03) should NOT return yesterday's job
    $response = $this->actingAs($user)
        ->getJson("/operational/active-job?line=Line%20A&shift=Shift%20Pagi&date={$today}");

    $response->assertOk()
        ->assertJson([
            'running' => false,
        ]);
});

test('activeJob handles line filtering and isolates lines cleanly', function () {
    $lineA = LineMaster::create(['line_code' => 'PA', 'line_name' => 'Line A', 'status' => 'active']);
    $lineB = LineMaster::create(['line_code' => 'PB', 'line_name' => 'Line B', 'status' => 'active']);
    $date = '2026-11-03';

    $planB = ProductionPlan::create([
        'line_master_id' => $lineB->id,
        'plan_date' => $date . ' 08:00:00',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'Line B',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'LINE-B-JOB',
        'job_master' => 'JM-B',
        'plan' => 400,
        'status' => 'running',
    ]);

    $jobB = JobMaster::create([
        'job_number' => 'LINE-B-JOB-' . $planB->id,
        'job_name' => 'JM-B',
        'line' => 'Line B',
        'status' => 'running',
        'target_qty' => 400,
        'started_at' => Carbon::parse("{$date} 08:00:00"),
    ]);

    ProductionSession::create([
        'job_master_id' => $jobB->id,
        'work_date' => $date,
        'start_time' => Carbon::parse("{$date} 08:00:00"),
        'status' => 'running',
        'total_seconds' => 500,
    ]);

    $user = activeJobUser();

    // Query Line A -> must be false
    $respA = $this->actingAs($user)
        ->getJson("/operational/active-job?line=Line%20A&shift=Shift%20Pagi&date={$date}");
    $respA->assertOk()->assertJson(['running' => false]);

    // Query Line B -> must be true and return Job B
    $respB = $this->actingAs($user)
        ->getJson("/operational/active-job?line=Line%20B&shift=Shift%20Pagi&date={$date}");
    $respB->assertOk()->assertJson([
        'running' => true,
        'id' => $jobB->id,
    ]);
});

test('activeJob correctly identifies paused status and open dandori state', function () {
    $line = LineMaster::create(['line_code' => 'PA', 'line_name' => 'Line A', 'status' => 'active']);
    $date = '2026-11-03';

    $plan = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date . ' 07:30:00',
        'shift_name' => 'Shift Pagi',
        'press_name' => 'Line A',
        'row_no' => 1,
        'row_type' => 'job',
        'job_no' => 'PAUSED-JOB',
        'job_master' => 'JM-PAUSED',
        'plan' => 500,
        'status' => 'paused',
    ]);

    $job = JobMaster::create([
        'job_number' => 'PAUSED-JOB-' . $plan->id,
        'job_name' => 'JM-PAUSED',
        'line' => 'Line A',
        'status' => 'paused',
        'target_qty' => 500,
        'started_at' => null,
    ]);

    ProductionSession::create([
        'job_master_id' => $job->id,
        'work_date' => $date,
        'start_time' => Carbon::parse("{$date} 08:00:00"),
        'status' => 'paused',
        'total_seconds' => 1200,
    ]);

    Downtime::create([
        'job_master_id' => $job->id,
        'jenis_downtime' => 'dandori',
        'problem' => 'CHANGE DIES',
        'start_time' => Carbon::parse("{$date} 09:00:00"),
        'finish_time' => null,
        'duration_seconds' => 0,
    ]);

    $user = activeJobUser();

    $response = $this->actingAs($user)
        ->getJson("/operational/active-job?line=Line%20A&shift=Shift%20Pagi&date={$date}");

    $response->assertOk()
        ->assertJson([
            'running' => true,
            'id' => $job->id,
            'status' => 'paused',
            'is_dandori' => true,
        ]);
});
