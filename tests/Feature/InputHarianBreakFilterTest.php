<?php

use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function ihLineMaster(): void
{
    LineMaster::firstOrCreate(
        ['line_code' => 'IH-1'],
        ['line_name' => 'LINE-A', 'kapasitas' => 100, 'type_line' => 'printing']
    );
}

function ihPlan(array $overrides = []): ProductionPlan
{
    $defaults = [
        'line_master_id' => LineMaster::where('line_code', 'IH-1')->first()->id,
        'plan_date'      => '2026-08-05',
        'shift_name'     => 'Shift Pagi',
        'press_name'     => 'LINE-A',
        'hari'           => 'rabu',
        'tgl'            => '05/08/2026',
        'jam'            => 'S1',
        'revisi'         => '0',
        'row_no'         => 1,
        'row_type'       => 'job',
        'job_no'         => 'IH-JOB',
        'job_master'     => 'IH JOB',
        'plan'           => 100,
        'ct_detik'       => 10,
        'dct'            => 5,
        'ok'             => 0,
        'status'         => 'pending',
        'source_type'    => 'ppc',
    ];
    return ProductionPlan::create(array_merge($defaults, $overrides));
}

test('input harian hides a phantom break the schedule never reaches, keeps legit breaks and jobs', function () {
    Carbon::setTestNow('2026-08-05 10:00:00');
    ihLineMaster();

    $job1 = ihPlan(['job_no' => 'IH-JOB-A', 'job_master' => 'IH JOB A', 'row_no' => 1, 'start_time' => '08:00', 'finish_time' => '11:00']);
    $legitBreak = ihPlan(['row_type' => 'break', 'job_no' => 'ISTIRAHAT SIANG', 'row_no' => 2, 'start_time' => '11:00', 'finish_time' => '11:15']);
    $job2 = ihPlan(['job_no' => 'IH-JOB-B', 'job_master' => 'IH JOB B', 'row_no' => 3, 'start_time' => '11:15', 'finish_time' => '14:00']);
    $phantomBreak = ihPlan(['row_type' => 'break', 'job_no' => 'ISTIRAHAT SORE', 'row_no' => 4, 'start_time' => '18:00', 'finish_time' => '18:30']);

    $user = User::create([
        'name'     => 'Tester',
        'nrp'      => 'IH-001',
        'password' => bcrypt('secret'),
        'role'     => 'superadmin',
    ]);

    $response = $this->actingAs($user)->get(route('operational.input_harian', [
        'date'  => '2026-08-05',
        'shift' => 'Shift Pagi',
    ]));

    $response->assertOk();

    $jobs = $response->viewData('jobs');
    $ids = collect($jobs)->pluck('id');

    expect($ids)->toContain($job1->id);
    expect($ids)->toContain($job2->id);
    expect($ids)->toContain($legitBreak->id);
    expect($ids)->not->toContain($phantomBreak->id);
});

test('input harian keeps a break carved out of a running job', function () {
    Carbon::setTestNow('2026-08-05 10:00:00');
    ihLineMaster();

    $job1 = ihPlan(['job_no' => 'IH-JOB-A', 'job_master' => 'IH JOB A', 'row_no' => 1, 'start_time' => '11:34', 'finish_time' => '12:56']);
    $carvedBreak = ihPlan(['row_type' => 'break', 'job_no' => 'ISTIRAHAT SIANG', 'row_no' => 2, 'start_time' => '12:00', 'finish_time' => '12:40']);
    $job1Cont = ihPlan(['job_no' => 'IH-JOB-A', 'job_master' => 'IH JOB A', 'row_no' => 3, 'start_time' => '12:40', 'finish_time' => '12:56']);

    $user = User::create([
        'name'     => 'Tester',
        'nrp'      => 'IH-002',
        'password' => bcrypt('secret'),
        'role'     => 'superadmin',
    ]);

    $response = $this->actingAs($user)->get(route('operational.input_harian', [
        'date'  => '2026-08-05',
        'shift' => 'Shift Pagi',
    ]));

    $response->assertOk();

    $jobs = $response->viewData('jobs');
    $ids = collect($jobs)->pluck('id');

    expect($ids)->toContain($job1->id);
    expect($ids)->toContain($job1Cont->id);
    expect($ids)->toContain($carvedBreak->id);
});
