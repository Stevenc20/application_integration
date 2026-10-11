<?php

use App\Models\DailyProduction;
use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\ProductionSession;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function anchorJob(string $status = 'running'): JobMaster
{
    $job = JobMaster::create([
        'job_number'  => 'JOB-ANCHOR-' . uniqid(),
        'job_name'    => 'ANCHOR TEST',
        'line'        => 'Press A',
        'status'      => $status,
        'started_at'  => now()->subMinutes(10),
    ]);

    ProductionSession::create([
        'job_master_id' => $job->id,
        'work_date'     => now()->toDateString(),
        'status'        => $status,
        'start_time'    => now()->subMinutes(10),
        'total_seconds' => 600,
    ]);

    return $job;
}

test('pause does not mutate the immutable production start anchor', function () {
    $job = anchorJob('running');
    $startedAt = $job->fresh()->started_at;

    app(ProductionService::class)->pauseJob($job->id);

    $fresh = $job->fresh();
    expect($fresh->status)->toBe('paused');
    expect(Carbon::parse($fresh->started_at)->equalTo(Carbon::parse($startedAt)))->toBeTrue();
});

test('resume does not mutate the immutable production start anchor', function () {
    $job = anchorJob('paused');
    $startedAt = $job->fresh()->started_at;

    app(ProductionService::class)->resumeJob($job->id);

    $fresh = $job->fresh();
    expect($fresh->status)->toBe('running');
    expect(Carbon::parse($fresh->started_at)->equalTo(Carbon::parse($startedAt)))->toBeTrue();
});

test('resume keeps open downtimes untouched (auto-break closure stays client-driven)', function () {
    $job = anchorJob('paused');
    $dt = Downtime::create([
        'job_master_id'  => $job->id,
        'jenis_downtime' => 'break time',
        'source'         => 'MANUAL',
        'start_time'     => now()->subMinutes(5),
    ]);

    app(ProductionService::class)->resumeJob($job->id);

    expect($dt->fresh()->finish_time)->toBeNull();
});

test('finish downtime closes the record and accumulates duration into daily production', function () {
    $job = anchorJob('paused');
    $dt = Downtime::create([
        'job_master_id'  => $job->id,
        'jenis_downtime' => 'break time',
        'source'         => 'MANUAL',
        'start_time'     => now()->subMinutes(5),
    ]);

    app(ProductionService::class)->finishDowntime($dt->id);

    $closed = $dt->fresh();
    expect($closed->finish_time)->not->toBeNull();
    expect($closed->duration_seconds)->toBeGreaterThan(0);

    $daily = DailyProduction::where('job_master_id', $job->id)
        ->whereDate('work_date', now()->toDateString())
        ->first();

    expect($daily)->not->toBeNull();
    expect($daily->downtime_seconds)->toBe($closed->duration_seconds);
});