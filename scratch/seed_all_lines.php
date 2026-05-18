<?php

use App\Models\JobMaster;
use App\Models\ProductionLog;
use App\Models\ProductionProcess;
use App\Models\Downtime;
use App\Models\LineMaster;
use Carbon\Carbon;

// Ensure we are in Laravel environment
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting comprehensive data seeding for all lines (Logs & Processes)...\n";

// 1. Get all unique active lines
$lines = LineMaster::where('status', 'active')->select('line_name')->distinct()->get();

if ($lines->isEmpty()) {
    $lineNames = ['Line A', 'Line B', 'Line C', 'Line D', 'Shearing', 'Handwork'];
} else {
    $lineNames = $lines->pluck('line_name')->toArray();
}

foreach ($lineNames as $lineName) {
    echo "Processing $lineName...\n";

    // Create a Job for today
    // We recreate it to ensure it has today's date for our new controller filter
    $job = JobMaster::create([
        'job_number' => 'SIM-' . strtoupper(str_replace(' ', '', $lineName)) . '-' . rand(1000, 9999),
        'job_name'   => 'Live Demo ' . $lineName,
        'line'       => $lineName,
        'capacity'   => rand(800, 1200),
        'status'     => 'running',
        'started_at' => Carbon::now()->subHours(2),
        'created_at' => Carbon::today(),
    ]);

    // 2. Create Production Logs (for Main Dashboard)
    $totalOk = rand(400, 800);
    $totalRepair = rand(5, 15);
    $totalReject = rand(2, 8);

    ProductionLog::create([
        'job_master_id' => $job->id,
        'ok_qty' => $totalOk,
        'repair_qty' => $totalRepair,
        'reject_qty' => $totalReject,
    ]);

    // 3. Create Production Processes (for Overview Dashboard)
    // Overview expects one or multiple records in production_processes
    ProductionProcess::create([
        'user_id' => 4,
        'production_order_number' => 'PO-' . rand(10000, 99999),
        'job_id' => $job->id,
        'process_type' => 'Stamping',
        'line' => $lineName,
        'machine_status' => 'running',
        'shift' => '1',
        'qty_ok' => $totalOk,
        'qty_repair' => $totalRepair,
        'qty_reject' => $totalReject,
        'status' => 'approved',
        'created_at' => Carbon::now(),
    ]);

    // 4. Create Downtimes
    $downtimeTypes = ['Machine', 'Dies', 'Material', 'Manpower'];
    foreach (range(1, 2) as $i) {
        $type = $downtimeTypes[array_rand($downtimeTypes)];
        $duration = rand(600, 2400); 
        
        Downtime::create([
            'job_master_id' => $job->id,
            'jenis_downtime' => $type,
            'problem' => 'Live Issue ' . $type,
            'penyebab' => 'Simulated cause',
            'action' => 'Simulated fix',
            'pic' => 'Admin',
            'start_time' => Carbon::now()->subSeconds($duration + 1000),
            'finish_time' => Carbon::now()->subSeconds(1000),
            'duration_seconds' => $duration,
        ]);
    }

    echo "Successfully seeded $lineName\n";
}

echo "All dashboards should now be populated with live data.\n";
