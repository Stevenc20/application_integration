<?php

use App\Models\JobMaster;
use App\Models\ProductionLog;
use App\Models\Downtime;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$job = JobMaster::where('job_number', 'JOB-LINEA-001')->first();

if (!$job) {
    echo "Job not found. Creating one...\n";
    $job = JobMaster::create([
        'job_number' => 'JOB-LINEA-001',
        'job_name' => 'Part Test A',
        'line' => 'Line A',
        'capacity' => 1000,
        'status' => 'running',
        'sequence_no' => 1,
        'started_at' => now(),
    ]);
}

echo "Adding logs for Job ID: " . $job->id . "\n";

ProductionLog::create(['job_master_id' => $job->id, 'ok_qty' => 150, 'repair_qty' => 5, 'reject_qty' => 2]);
ProductionLog::create(['job_master_id' => $job->id, 'ok_qty' => 200, 'repair_qty' => 2, 'reject_qty' => 1]);

// Add some downtimes
Downtime::create([
    'job_master_id' => $job->id,
    'jenis_downtime' => 'Machine',
    'problem' => 'Overheat',
    'duration_seconds' => 600, // 10 mins
    'start_time' => now()->subMinutes(30),
    'finish_time' => now()->subMinutes(20),
]);

Downtime::create([
    'job_master_id' => $job->id,
    'jenis_downtime' => 'Dies',
    'problem' => 'Cleaning',
    'duration_seconds' => 300, // 5 mins
    'start_time' => now()->subMinutes(10),
    'finish_time' => now()->subMinutes(5),
]);

echo "Done!\n";
