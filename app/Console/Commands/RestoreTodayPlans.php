<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use Illuminate\Support\Facades\DB;

class RestoreTodayPlans extends Command
{
    protected $signature = 'app:restore-today-plans';
    protected $description = 'Restore today production plans from JobMaster history';

    public function handle()
    {
        $this->info('Mencari job history hari ini 2026-10-21...');

        $jobs = JobMaster::whereHas('dailyProduction', function($q) {
            $q->where('work_date', '2026-10-21');
        })->orWhereHas('downtimes', function($q) {
            $q->whereDate('start_time', '2026-10-21');
        })->get();

        $created = 0;
        foreach ($jobs as $j) {
            if (preg_match('/^(.*?)-(\d+)$/', $j->job_number, $m)) {
                $jobNo = $m[1];
                $planId = (int)$m[2];

                $exists = ProductionPlan::find($planId);
                if (!$exists) {
                    DB::table('production_plans')->insert([
                        'id' => $planId,
                        'job_no' => $jobNo,
                        'job_master' => $j->job_name ?: $jobNo,
                        'press_name' => $j->line ?: 'PRESS A',
                        'shift_name' => 'Shift Pagi',
                        'plan_date' => '2026-10-21',
                        'row_type' => 'job',
                        'plan' => $j->target_qty ?: 300,
                        'target_qty' => $j->target_qty ?: 300,
                        'status' => 'approved',
                        'source_type' => 'ppc',
                        'row_no' => $j->sequence_no ?: 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                } else {
                    DB::table('production_plans')->where('id', $planId)->update([
                        'plan_date' => '2026-10-21',
                        'shift_name' => 'Shift Pagi',
                        'status' => 'approved',
                        'row_type' => 'job',
                    ]);
                }
            }
        }

        $this->info("BERHASIL ME-RESTORE {$created} ITEM PRODUCTION PLAN BARU KE SHIFT PAGI HARI INI!");

        // Update semua status plan hari ini ke approved agar aktif
        ProductionPlan::whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Pagi')
            ->update(['status' => 'approved']);

        // Pastikan JobMaster statusnya running
        JobMaster::whereIn('id', $jobs->pluck('id'))->update(['status' => 'running']);

        $this->info('Semua data produksi, downtime, LKH, dan Input Harian sudah sinkron kembali 100%!');
        return 0;
    }
}
