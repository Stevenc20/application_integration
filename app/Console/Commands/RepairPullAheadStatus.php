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
        $requests = PullAheadRequest::all();
        $this->info("Total PullAheadRequest terdaftar: " . $requests->count());

        foreach ($requests as $req) {
            $originalPlan = ProductionPlan::find($req->original_plan_id);
            if (!$originalPlan) {
                continue;
            }

            $qtyApproved = (float)($req->qty_approved ?: $req->qty_requested);
            $baseQty = (float)($originalPlan->original_plan ?: ($originalPlan->plan + $qtyApproved));
            if ($baseQty <= $qtyApproved && (float)$originalPlan->plan == 1000) {
                // Kasus default 1000 jika baseQty terbaca sama
                $baseQty = 1000;
            }
            $expectedRemaining = max(0, $baseQty - $qtyApproved);

            $this->info("PullAhead Plan Asal ID {$originalPlan->id}: {$originalPlan->job_no} | Base: {$baseQty} | Ditarik: {$qtyApproved} | Sisa Seharusnya: {$expectedRemaining}");

            $originalPlan->original_plan = $baseQty;
            $originalPlan->plan = $expectedRemaining;
            $originalPlan->remaining_plan = $expectedRemaining;
            $originalPlan->status = 'pending';
            $originalPlan->act_finish = null;
            $originalPlan->save();

            // Sync JobMaster yang berkaitan
            $origJms = JobMaster::where('job_number', 'LIKE', "%-{$originalPlan->id}")
                ->orWhere('job_number', $originalPlan->job_no)
                ->get();

            foreach ($origJms as $jm) {
                $this->warn("  -> Reset JobMaster #{$jm->id} ({$jm->job_number}) status: '{$jm->status}' -> 'pending', target_qty: {$expectedRemaining}");
                $jm->target_qty = (int)$expectedRemaining;
                $jm->status = 'pending';
                $jm->finished_at = null;
                $jm->save();
            }
        }

        // 2. Pencarian Langsung Item RC-2764 FNS di Shift Malam
        $rcPlans = ProductionPlan::where(function($q) {
                $q->where('job_no', 'LIKE', '%RC-2764%')
                  ->orWhere('job_master', 'LIKE', '%RC-2764%');
            })
            ->where(function($q) {
                $q->where('shift_name', 'LIKE', '%Malam%')
                  ->orWhere('shift_name', 'LIKE', '%2%');
            })
            ->get();

        foreach ($rcPlans as $rcPlan) {
            $this->info("Menemukan Plan Shift Malam RC-2764: ID {$rcPlan->id}, Plan saat ini: {$rcPlan->plan}");
            // Jika plan masih 1000, ubah ke 500 karena 500 sudah selesai di pagi
            if ((float)$rcPlan->plan > 500 || $rcPlan->original_plan === null) {
                $this->warn("  -> Menyesuaikan Plan ID {$rcPlan->id} ke 500 pcs!");
                $rcPlan->original_plan = 1000;
                $rcPlan->plan = 500;
                $rcPlan->remaining_plan = 500;
                $rcPlan->status = 'pending';
                $rcPlan->act_finish = null;
                $rcPlan->save();

                $rcJms = JobMaster::where('job_number', 'LIKE', "%-{$rcPlan->id}")
                    ->orWhere('job_name', 'LIKE', '%RC-2764%')
                    ->get();
                foreach ($rcJms as $jm) {
                    $jm->target_qty = 500;
                    $jm->status = 'pending';
                    $jm->finished_at = null;
                    $jm->save();
                    $this->warn("  -> JobMaster #{$jm->id} target_qty diubah ke 500 & status direset ke 'pending'");
                }
            }
        }

        // 3. Scan SEMUA JobMaster yang statusnya COMPLETE tapi actual_ok/actual_qty = 0 dan belum pernah diproduksi
        $this->info("\nMemeriksa JobMaster yang keliru 'complete' padahal actual_ok = 0...");
        
        $falseCompleteJms = JobMaster::whereIn(DB::raw('LOWER(status)'), ['complete', 'completed', 'finished', 'closed'])
            ->get();

        $fixedJmCount = 0;
        foreach ($falseCompleteJms as $jm) {
            $daily = DailyProduction::where('job_master_id', $jm->id)->first();
            $actQty = (float)($daily ? ($daily->actual_ok + $daily->actual_repair + $daily->actual_reject) : 0);

            $parts = explode('-', $jm->job_number);
            $planId = end($parts);
            $plan = is_numeric($planId) ? ProductionPlan::find($planId) : null;
            $planOk = (float)($plan->ok ?? 0);

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

        // 4. Tutup paksa job orphan/nyangkut dari shift sebelumnya (misal AES-006 atau job running/paused di line manapun yang melintasi shift)
        $this->info("\nMemeriksa job nyangkut di line produksi (A, B, C, D)...");
        try {
            $shiftTransition = app(\App\Services\ProductionShiftTransitionService::class);
            $res = $shiftTransition->transitionShiftSafely('ALL');
            $this->info("Cutoff transisi shift selesai dieksekusi: " . json_encode($res['completed_jobs'] ?? []));
        } catch (\Throwable $e) {
            $this->error("Transisi shift error: " . $e->getMessage());
        }

        // Khusus item AES-006 di Line B jika masih ada status running/paused
        $aesJobs = JobMaster::where(function($q) {
                $q->where('job_number', 'LIKE', '%AES-006%')
                  ->orWhere('job_name', 'LIKE', '%AES-006%');
            })
            ->whereIn(DB::raw('LOWER(status)'), ['running', 'paused'])
            ->get();
        foreach ($aesJobs as $aj) {
            $this->warn("Menutup job nyangkut {$aj->job_number} (ID: {$aj->id}) di {$aj->line}...");
            try {
                app(\App\Services\ProductionService::class)->finishJob($aj->id, 'SHIFT_CUTOFF');
                $this->info("  -> Sukses ditutup!");
            } catch (\Throwable $e) {
                $aj->update(['status' => 'completed', 'finished_at' => now()]);
                $this->info("  -> Fallback update complete: " . $e->getMessage());
            }
        }

        $this->info("\nSelesai! Semua data telah diperiksa dan diselaraskan.");
        return 0;
    }
}
