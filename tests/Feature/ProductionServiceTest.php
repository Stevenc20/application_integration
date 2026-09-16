<?php

use App\Models\JobMaster;
use App\Models\Downtime;
use App\Models\ProductionSession;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('finishing a job without skipIdle auto-starts the next job as idle (creates downtime record)', function () {
    $service = new ProductionService();

    // Create current job
    $job1 = JobMaster::create([
        'job_number' => 'JOB-001',
        'job_name' => 'Job 1',
        'line' => 'LINE-A',
        'capacity' => 100,
        'status' => 'running',
        'sequence_no' => 1,
        'started_at' => now(),
    ]);

    // Create next job
    $job2 = JobMaster::create([
        'job_number' => 'JOB-002',
        'job_name' => 'Job 2',
        'line' => 'LINE-A',
        'capacity' => 100,
        'status' => 'pending',
        'sequence_no' => 2,
    ]);

    // Start production session for job 1
    ProductionSession::create([
        'job_master_id' => $job1->id,
        'work_date' => now()->toDateString(),
        'status' => 'running',
        'start_time' => now(),
    ]);

    // Finish job 1 without skipIdle (defaults to false)
    $service->finishJob($job1->id, $job2->id, false);

    // Assert job 1 is complete
    $job1->refresh();
    expect($job1->status)->toBe('complete');

    // Assert job 2 is running
    $job2->refresh();
    expect($job2->status)->toBe('running');

    // Assert a downtime record of type 'idle time' was created for job 2
    $downtime = Downtime::where('job_master_id', $job2->id)->first();
    expect($downtime)->not->toBeNull();
    expect($downtime->jenis_downtime)->toBe('idle time');
    expect($downtime->problem)->toBe('MENUNGGU PROSES MULAI (IDLE TIME)');
});

test('finishing a job with skipIdle true starts next job neutrally (no downtime record created)', function () {
    $service = new ProductionService();

    // Create current job
    $job1 = JobMaster::create([
        'job_number' => 'JOB-003',
        'job_name' => 'Job 3',
        'line' => 'LINE-A',
        'capacity' => 100,
        'status' => 'running',
        'sequence_no' => 3,
        'started_at' => now(),
    ]);

    // Create next job
    $job2 = JobMaster::create([
        'job_number' => 'JOB-004',
        'job_name' => 'Job 4',
        'line' => 'LINE-A',
        'capacity' => 100,
        'status' => 'pending',
        'sequence_no' => 4,
    ]);

    // Start production session for job 3
    ProductionSession::create([
        'job_master_id' => $job1->id,
        'work_date' => now()->toDateString(),
        'status' => 'running',
        'start_time' => now(),
    ]);

    // Finish job 3 with skipIdle = true
    $service->finishJob($job1->id, $job2->id, true);

    // Assert job 3 is complete
    $job1->refresh();
    expect($job1->status)->toBe('complete');

    // Assert job 4 is running
    $job2->refresh();
    expect($job2->status)->toBe('running');

    // Assert no downtime record was created for job 4
    $downtime = Downtime::where('job_master_id', $job2->id)->first();
    expect($downtime)->toBeNull();
});

test('saving a production log closes any active idle downtime', function () {
    $service = new ProductionService();

    $job = JobMaster::create([
        'job_number' => 'JOB-005',
        'job_name' => 'Job 5',
        'line' => 'LINE-A',
        'capacity' => 100,
        'status' => 'running',
        'started_at' => now(),
    ]);

    // Create active production session
    ProductionSession::create([
        'job_master_id' => $job->id,
        'work_date' => now()->toDateString(),
        'status' => 'running',
        'start_time' => now(),
    ]);

    // Create open idle downtime
    $downtime = Downtime::create([
        'job_master_id' => $job->id,
        'jenis_downtime' => 'idle time',
        'problem' => 'MENUNGGU PROSES MULAI (IDLE TIME)',
        'start_time' => now()->subMinutes(10),
    ]);

    // Save production log
    $service->saveProductionLog($job->id, [
        'ok_qty' => 10,
        'repair_qty' => 0,
        'reject_qty' => 0,
    ]);

    // Assert downtime is closed
    $downtime->refresh();
    expect($downtime->finish_time)->not->toBeNull();
    expect($downtime->duration_seconds)->toBeGreaterThan(0);
});
