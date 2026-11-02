<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use App\Models\PullAheadRequest;
use App\Models\DailyProduction;
use Illuminate\Support\Facades\DB;

class RepairPullAheadStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pull-ahead:repair-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbaiki plan dan status JobMaster/ProductionPlan shift malam atau shift lain yang keliru complete padahal belum diproduksi.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== REPAIR PRODUCTION STATUS & PLAN QTY ===");

        // 1. Perbaiki berdasarkan PullAheadRequest
        $requests = PullAheadRequest::whereIn('status', ['APPLIED', 'APPROVED'])->get();
        $this->info("Ditemukan " . $requests->count() . " PullAheadRequest.");

        foreach ($requests as $req) {
            $originalPlan = ProductionPlan::find($req->original_plan_id);
            if (!$originalPlan) {
                continue;
            }

            $qtyApproved = (float)($req->qty_approved ?: $req->qty_requested);
            $baseQty = (float)($originalPlan->original_plan ?: ($originalPlan->plan + $qtyApproved));
            $expectedRemaining = max(0, $baseQty - $qtyApproved);

            $this->info("PullAhead Plan Asal ID {$originalPlan->id}: {$originalPlan->job_no} | Base: {$baseQty} | Ditarik: {$qtyApproved} | Sisa Seharusnya: {$expectedRemaining}");

            $originalPlan->original_plan = $baseQty;
            $originalPlan->plan = $expectedRemaining;
            $originalPlan->remaining_plan = $expectedRemaining;
            $originalPlan->status = 'pending';
            $originalPlan->save();

            // Sync JobMaster yang berakhiran -{originalPlan->id}
            $origJms = JobMaster::where('job_number', 'LIKE', "%-{$originalPlan->id}")->get();
            foreach ($origJms as $jm) {
                $this->warn("  -> Reset JobMaster #{$jm->id} ({$jm->job_number}) status: '{$jm->status}' -> 'pending', target_qty: {$expectedRemaining}");
                $jm->target_qty = (int)$expectedRemaining;
                $jm->status = 'pending';
                $jm->finished_at = null;
                $jm->save();
            }
        }

        // 2. Scan SEMUA JobMaster & ProductionPlan di Shift Malam (atau shift aktif) yang statusnya COMPLETE tapi actual_ok/actual_qty = 0 dan belum pernah diproduksi
        $this->info("\nMemeriksa JobMaster yang keliru 'complete' padahal actual_ok = 0...");
        
        $falseCompleteJms = JobMaster::whereIn(DB::raw('LOWER(status)'), ['complete', 'completed', 'finished', 'closed'])
            ->get();

        $fixedJmCount = 0;
        foreach ($falseCompleteJms as $jm) {
            // Cek apakah ada daily production dengan actual > 0
            $daily = DailyProduction::where('job_master_id', $jm->id)->first();
            $actQty = (float)($daily ? ($daily->actual_ok + $daily->actual_repair + $daily->actual_reject) : 0);

            // Ekstrak plan_id jika ada
            $parts = explode('-', $jm->job_number);
            $planId = end($parts);
            $plan = is_numeric($planId) ? ProductionPlan::find($planId) : null;
            $planOk = (float)($plan->ok ?? 0);

            // Jika actual_ok = 0 dan plan_ok = 0, ini adalah item yang belum diproduksi dan salah di-complete!
            if ($actQty <= 0 && $planOk <= 0) {
                $this->warn("Reset False Complete JobMaster ID {$jm->id} ({$jm->job_number} / {$jm->job_name}): status -> 'pending'");
                $jm->status = 'pending';
                $jm->finished_at = null;
                $jm->save();

                if ($plan) {
                    $plan->status = 'pending';
                    $plan->act_finish = null;
                    $plan->save();
                }
                $fixedJmCount++;
            }
        }

        $this->info("\nSelesai! Sebanyak {$fixedJmCount} JobMaster false-complete berhasil dikembalikan ke status 'pending'.");
        return 0;
    }
}
