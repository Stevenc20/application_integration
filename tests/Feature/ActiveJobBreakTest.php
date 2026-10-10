<?php

use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function ajUser(): User
{
    return User::create([
        'name'     => 'Tester',
        'nrp'      => 'AJ-001',
        'password' => bcrypt('secret'),
        'role'     => 'superadmin',
    ]);
}

function ajSession(string $status, array $overrides = []): ProductionSession
{
    $job = JobMaster::create(array_merge([
        'job_number'  => 'JOB-AJ-' . uniqid(),
        'job_name'    => 'AJ TEST JOB',
        'line'        => 'Press A',
        'status'      => $status,
        'started_at'  => now()->subMinutes(10),
    ], Arr::only($overrides, ['line', 'status'])));

    return ProductionSession::create(array_merge([
        'job_master_id' => $job->id,
        'work_date'     => now()->toDateString(),
        'status'        => $status,
        'start_time'    => now()->subMinutes(10),
        'total_seconds' => 600,
    ], Arr::only($overrides, ['status'])));
}

test('active job counts a paused session with an open break downtime as running', function () {
    Carbon::setTestNow('2026-10-08 10:00:00');
    $session = ajSession('paused');

    Downtime::create([
        'job_master_id'   => $session->job_master_id,
        'jenis_downtime'  => 'break time',
        'start_time'      => now()->subMinutes(5),
    ]);

    $response = $this->actingAs(ajUser())->getJson(route('operational.active-job'));

    $response->assertOk()
        ->assertJson(['running' => true, 'id' => $session->job_master_id]);
});

test('active job ignores a paused session when no break downtime is open', function () {
    Carbon::setTestNow('2026-10-08 10:00:00');
    ajSession('paused');

    $response = $this->actingAs(ajUser())->getJson(route('operational.active-job'));

    $response->assertOk()->assertJson(['running' => false]);
});

test('active job ignores a paused session with a non-break downtime open', function () {
    Carbon::setTestNow('2026-10-08 10:00:00');
    $session = ajSession('paused');

    Downtime::create([
        'job_master_id'   => $session->job_master_id,
        'jenis_downtime'  => 'machine trouble',
        'start_time'      => now()->subMinutes(5),
    ]);

    $response = $this->actingAs(ajUser())->getJson(route('operational.active-job'));

    $response->assertOk()->assertJson(['running' => false]);
});

test('active job prefers a running session over a paused one on break', function () {
    Carbon::setTestNow('2026-10-08 10:00:00');
    $paused = ajSession('paused');
    Downtime::create([
        'job_master_id'   => $paused->job_master_id,
        'jenis_downtime'  => 'break time',
        'start_time'      => now()->subMinutes(5),
    ]);
    $running = ajSession('running');

    $response = $this->actingAs(ajUser())->getJson(route('operational.active-job'));

    $response->assertOk()
        ->assertJson(['running' => true, 'id' => $running->job_master_id]);
});

test('active job reports a normally running session', function () {
    Carbon::setTestNow('2026-10-08 10:00:00');
    $session = ajSession('running');

    $response = $this->actingAs(ajUser())->getJson(route('operational.active-job'));

    $response->assertOk()
        ->assertJson(['running' => true, 'id' => $session->job_master_id]);
});
