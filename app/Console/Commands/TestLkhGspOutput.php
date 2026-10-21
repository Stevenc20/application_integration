<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Supervisor\ReportController;
use Illuminate\Http\Request;

class TestLkhGspOutput extends Command
{
    protected $signature = 'app:test-lkh-gsph';
    protected $description = 'Test exact LKH data returned by ReportController for 22 Oktober PRESS A';

    public function handle()
    {
        $firstItemDb = DB::table('production_plans')
            ->whereDate('plan_date', '2026-10-22')
            ->where('press_name', 'PRESS A')
            ->where('shift_name', 'Shift Pagi')
            ->where('row_type', 'job')
            ->orderBy('row_no')
            ->first();

        $this->info("1. Di Database production_plans:");
        $this->line("   ID: {$firstItemDb->id}");
        $this->line("   Job: {$firstItemDb->job_no}");
        $this->line("   Plan: {$firstItemDb->plan}");
        $this->line("   TPT: {$firstItemDb->tpt}");
        $this->line("   gsph_item di DB: " . var_export($firstItemDb->gsph_item, true));

        // 2. Simulasi query ReportController
        $request = new Request([
            'date' => '2026-10-22',
            'line' => 'Line A',
            'shift' => 'Shift Pagi',
        ]);

        $controller = app(ReportController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('buildDailyProductionData');
        $method->setAccessible(true);
        $data = $method->invoke($controller, $request);

        $jobsData = $data['jobsData'] ?? [];
        $firstJob = collect($jobsData)->firstWhere('row_type', 'job');

        $this->info("\n2. Di Hasil Olahan LKH (ReportController):");
        if ($firstJob) {
            $this->line("   Job No: {$firstJob['job_no']}");
            $this->line("   Plan Qty: {$firstJob['plan_qty']}");
            $this->line("   TPT Plan: {$firstJob['tpt_plan']}");
            $this->line("   plan_gsph di LKH: " . var_export($firstJob['plan_gsph'], true));
        } else {
            $this->warn("   Job tidak ditemukan di LKH!");
        }

        return 0;
    }
}
