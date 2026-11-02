<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionPlan;
use App\Models\JobMaster;
use App\Models\PullAheadRequest;
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
    protected $description = 'Perbaiki plan dan status JobMaster/ProductionPlan shift asal yang ditarik shift (misal shift malam) yang keliru menjadi complete atau plannya belum berkurang.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== REPAIR PULL AHEAD STATUS & PLAN QTY ===");

        // Cari semua pull ahead requests yang statusnya APPLIED atau APPROVED
        $requests = PullAheadRequest::whereIn('status', ['APPLIED', 'APPROVED'])->get();

        if ($requests->isEmpty()) {
            $this->warn("Tidak ditemukan record PullAheadRequest dengan status APPLIED/APPROVED.");
            
            // Cek manual jika ada plan RC-2764 FNS di shift malam
            $manualNightPlans = ProductionPlan::where('job_no', 'like', '%RC-2764%')
                ->orWhere('job_master', 'like', '%RC-2764%')
                ->get();
                
            foreach ($manualNightPlans as $p) {
                $this->info("Check plan ID {$p->id}: {$p->job_no} | Date: {$p->plan_date} | Shift: {$p->shift_name} | Plan: {$p->plan} | Status: {$p->status}");
            }
            return 0;
        }

        $fixedCount = 0;

        foreach ($requests as $req) {
            $originalPlan = ProductionPlan::find($req->original_plan_id);
            if (!$originalPlan) {
                continue;
            }

            $qtyApproved = (float)($req->qty_approved ?: $req->qty_requested);
            $baseQty = (float)($originalPlan->original_plan ?: ($originalPlan->plan + $qtyApproved));

            // Sisa plan seharusnya
            $expectedRemaining = max(0, $baseQty - $qtyApproved);

            $this->info("Memeriksa Plan Asal ID: {$originalPlan->id} ({$originalPlan->job_no}) [{$originalPlan->shift_name}]");
            $this->line("  Base Qty: {$baseQty}, Qty Ditarik: {$qtyApproved}, Sisa Seharusnya: {$expectedRemaining}");

            $updated = false;

            if ($originalPlan->original_plan === null) {
                $originalPlan->original_plan = $baseQty;
                $updated = true;
            }

            // Jika plan belum dikurangi
            if ((float)$originalPlan->plan > $expectedRemaining && $expectedRemaining > 0) {
                $this->warn("  -> Memperbarui plan dari {$originalPlan->plan} ke {$expectedRemaining}");
                $originalPlan->plan = $expectedRemaining;
                $originalPlan->remaining_plan = $expectedRemaining;
                $updated = true;
            }

            // Jika status keliru completed padahal actual ok belum memenuhi plan
            if (in_array(strtolower($originalPlan->status), ['completed', 'complete']) && (float)($originalPlan->ok ?? 0) < $originalPlan->plan) {
                $this->warn("  -> Mengembalikan status plan dari '{$originalPlan->status}' ke 'pending'");
                $originalPlan->status = 'pending';
                $updated = true;
            }

            if ($updated) {
                $originalPlan->save();
            }

            // Cek dan sync ke JobMaster asal
            $origIdentifier = $originalPlan->job_no 
                ? ($originalPlan->job_no . '-' . $originalPlan->id) 
                : ('AUTO-' . \Illuminate\Support\Str::slug($originalPlan->job_master) . '-' . $originalPlan->id);

            $origJm = JobMaster::where('job_number', $origIdentifier)->first();
            if ($origJm) {
                $jmUpdated = false;
                if ((float)$origJm->target_qty != (float)$originalPlan->plan) {
                    $this->warn("  -> Memperbarui JobMaster target_qty ke {$originalPlan->plan}");
                    $origJm->target_qty = (int) $originalPlan->plan;
                    $jmUpdated = true;
                }

                if (in_array(strtolower($origJm->status), ['complete', 'completed', 'finished', 'closed'])) {
                    $this->warn("  -> Mengembalikan status JobMaster dari '{$origJm->status}' ke 'pending'");
                    $origJm->status = 'pending';
                    $jmUpdated = true;
                }

                if ($jmUpdated) {
                    $origJm->save();
                }
            }

            $fixedCount++;
        }

        $this->info("\nSelesai! Sebanyak {$fixedCount} data request diperiksa dan diselaraskan.");
        return 0;
    }
}
