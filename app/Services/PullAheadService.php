<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\PullAheadRequest;
use Illuminate\Support\Facades\DB;
use Exception;

class PullAheadService
{
    /**
     * Hitung sisa Qty yang bisa di-request (belum di-pending oleh request lain)
     */
    public function calculateAvailableQty(ProductionPlan $plan)
    {
        $pendingRequestsQty = PullAheadRequest::where('original_plan_id', $plan->id)
            ->where('status', 'PENDING')
            ->sum('qty_requested');

        // Gunakan remaining_plan jika ada dan positif, jika tidak fallback ke plan atau target_qty
        if ($plan->remaining_plan !== null && (int)$plan->remaining_plan > 0) {
            $baseQty = (int)$plan->remaining_plan;
        } elseif ($plan->plan !== null && (int)$plan->plan > 0) {
            $baseQty = (int)$plan->plan;
        } elseif ($plan->target_qty !== null && (int)$plan->target_qty > 0) {
            $baseQty = (int)$plan->target_qty;
        } else {
            $baseQty = (int)($plan->remaining_plan ?? $plan->plan ?? $plan->target_qty ?? 0);
        }

        $available = $baseQty - $pendingRequestsQty;
        if ($available <= 0 && (float)($plan->plan ?? 0) > 0 && $pendingRequestsQty == 0) {
            $available = (float)$plan->plan;
        }
        return $available > 0 ? (int)$available : 0;
    }

    /**
     * Buat request Pull Ahead baru
     */
    public function createRequest(array $data)
    {
        $plan = ProductionPlan::findOrFail($data['original_plan_id']);

        $availableQty = $this->calculateAvailableQty($plan);
        if ($availableQty <= 0) {
            $availableQty = (int)($plan->remaining_plan ?? $plan->plan ?? $plan->target_qty ?? 1);
        }

        if ($data['qty_requested'] > $availableQty) {
            throw new Exception("Qty yang diminta ({$data['qty_requested']}) melebihi Qty yang tersedia ({$availableQty}).");
        }

        $request = PullAheadRequest::create([
            'original_plan_id'        => $plan->id,
            'requested_by'            => $data['requested_by'],
            'source_shift'            => $data['source_shift'],
            'target_shift'            => $data['target_shift'],
            'qty_requested'           => $data['qty_requested'],
            'proposed_sequence_after' => $data['proposed_sequence_after'] ?? null,
            'status'                  => 'PENDING'
        ]);

        return $request;
    }

    /**
     * PPC menyetujui request, tentukan qty final & posisi sequence
     */
    public function approveRequest(PullAheadRequest $request, $qtyApproved, $finalSequenceAfterId, $approverId)
    {
        DB::beginTransaction();
        try {
            $originalPlan = $request->originalPlan;
            $baseQty = $originalPlan->remaining_plan !== null ? $originalPlan->remaining_plan : ($originalPlan->plan ?: $originalPlan->target_qty);

            if ($qtyApproved > $baseQty) {
                throw new Exception("Qty Approved melebihi Sisa Plan asli.");
            }

            // 1. Kurangi remaining plan di shift asal
            $originalPlan->remaining_plan = max(0, $baseQty - $qtyApproved);
            $originalPlan->save();

            // 2. Tentukan row_no untuk di shift target (berdasarkan final_sequence_after)
            $targetRowNo = 1;
            if ($finalSequenceAfterId) {
                $afterPlan = ProductionPlan::find($finalSequenceAfterId);
                if ($afterPlan) {
                    $targetRowNo = $afterPlan->row_no + 1;
                    
                    // Shift ke bawah semua plan di shift target (hari, line, shift yg sama) yg >= targetRowNo
                    $shiftQuery = ProductionPlan::whereDate('plan_date', $afterPlan->plan_date)
                        ->where('shift_name', $request->target_shift)
                        ->where('row_no', '>=', $targetRowNo);

                    if (!empty($afterPlan->line_master_id)) {
                        $shiftQuery->where('line_master_id', $afterPlan->line_master_id);
                    } elseif (!empty($afterPlan->press_name)) {
                        $shiftQuery->where('press_name', $afterPlan->press_name);
                    }
                    $shiftQuery->increment('row_no');
                }
            } else {
                // Jika tidak ada usulan/keputusan, taruh di paling bawah
                $maxRowQuery = ProductionPlan::whereDate('plan_date', $originalPlan->plan_date)
                    ->where('shift_name', $request->target_shift);

                if (!empty($originalPlan->line_master_id)) {
                    $maxRowQuery->where('line_master_id', $originalPlan->line_master_id);
                } elseif (!empty($originalPlan->press_name)) {
                    $maxRowQuery->where('press_name', $originalPlan->press_name);
                }
                $maxRow = $maxRowQuery->max('row_no');
                $targetRowNo = $maxRow ? $maxRow + 1 : 1;
            }

            // 3. Buat Plan baru di Shift 1
            $newPlan = $originalPlan->replicate();
            $newPlan->shift_name = $request->target_shift;
            $newPlan->row_no = $targetRowNo;
            $newPlan->plan = $qtyApproved;
            $newPlan->original_plan = $qtyApproved;
            $newPlan->remaining_plan = $qtyApproved;
            $newPlan->total_pcs = $qtyApproved * ($originalPlan->each_part ?: 1);
            $newPlan->source_type = 'pull_ahead'; // Beri flag khusus
            $newPlan->save();

            // 4. Update status Request
            $request->update([
                'new_plan_id'          => $newPlan->id,
                'qty_approved'         => $qtyApproved,
                'final_sequence_after' => $finalSequenceAfterId,
                'approved_by'          => $approverId,
                'status'               => 'APPLIED'
            ]);

            DB::commit();

            // TODO: Kirim notifikasi ke Leader & Spv bahwa request di-approve
            
            return $request;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * PPC menolak request
     */
    public function rejectRequest(PullAheadRequest $request, $approverId, $remarks = null)
    {
        $request->update([
            'status'      => 'REJECTED',
            'approved_by' => $approverId,
            'remarks'     => $remarks
        ]);

        // TODO: Kirim notifikasi penolakan ke Leader

        return $request;
    }
}
