<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobMaster;
use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use Illuminate\Support\Facades\DB;

class SyncShiftStatus extends Command
{
    protected $signature = 'app:sync-shift-status';
    protected $description = 'Set all Shift Pagi items to completed, and set Shift Malam recovery items to running';

    public function handle()
    {
        $this->info('=== SINKRONISASI STATUS SHIFT PAGI & SHIFT MALAM ===');

        // 1. SHIFT PAGI: Semua item Shift Pagi hari ini harus COMPLETED / STOP
        $pagiPlans = ProductionPlan::whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Pagi')
            ->update([
                'status' => 'completed',
            ]);

        // JobMaster yang terkait dengan Shift Pagi (atau yang sudah punya hasil produksi hari ini) di-set complete
        // Kecuali jika job tersebut adalah item recovery Shift Malam
        $malamRecoveryPlanIds = ProductionPlan::whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Malam')
            ->where('source_type', 'recovery')
            ->pluck('id')
            ->toArray();

        $pagiJobs = JobMaster::where(function($q) use ($malamRecoveryPlanIds) {
            foreach ($malamRecoveryPlanIds as $pid) {
                $q->where('job_number', 'NOT LIKE', "%-{$pid}");
            }
        })
        ->whereHas('dailyProduction', function($q) {
            $q->where('work_date', '2026-10-21');
        })
        ->update([
            'status' => 'complete',
            'finished_at' => DB::raw("COALESCE(finished_at, '2026-10-21 16:30:00')"),
        ]);

        $this->info("Shift Pagi selesai: {$pagiPlans} plan di-set 'completed', {$pagiJobs} JobMaster di-set 'complete'.");

        // 2. SHIFT MALAM: Item Recovery yang sudah masuk di-set RUNNING
        $malamPlans = ProductionPlan::whereDate('plan_date', '2026-10-21')
            ->where('shift_name', 'Shift Malam')
            ->where('source_type', 'recovery')
            ->update([
                'status' => 'approved',
            ]);

        // JobMaster untuk item recovery Shift Malam di-set running
        $recoveryJobsUpdated = 0;
        foreach ($malamRecoveryPlanIds as $pid) {
            $rj = JobMaster::where('job_number', 'LIKE', "%-{$pid}")->first();
            if ($rj) {
                $rj->update([
                    'status' => 'running',
                    'started_at' => $rj->started_at ?: '2026-10-21 17:00:00',
                    'finished_at' => null,
                ]);
                $recoveryJobsUpdated++;
            }
        }

        // Update juga status di tabel recovery_items menjadi in_production / scheduled
        $recItems = RecoveryItem::whereIn('production_plan_id', $malamRecoveryPlanIds)
            ->orWhereIn('id', ProductionPlan::whereDate('plan_date', '2026-10-21')->whereNotNull('recovery_id')->pluck('recovery_id'))
            ->update([
                'status' => 'in_production',
            ]);

        $this->info("Shift Malam aktif: {$malamPlans} recovery plan di-set 'approved', {$recoveryJobsUpdated} JobMaster di-set 'running', {$recItems} RecoveryItem di-set 'in_production'.");
        $this->info('Sinkronisasi selesai dengan sempurna!');
        return 0;
    }
}
