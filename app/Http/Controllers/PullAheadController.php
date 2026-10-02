<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PullAheadRequest;
use App\Models\ProductionPlan;
use App\Services\PullAheadService;
use Carbon\Carbon;
use Exception;

class PullAheadController extends Controller
{
    protected $pullAheadService;

    public function __construct(PullAheadService $pullAheadService)
    {
        $this->pullAheadService = $pullAheadService;
    }

    /**
     * Tandai notifikasi modal sebagai telah dibaca
     */
    public function markAsRead(Request $request)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['success' => false]);

        $role = strtolower($user->role);
        
        if (in_array($role, ['ppc', 'manager'])) {
            PullAheadRequest::where('status', 'PENDING')
                ->where('is_read_by_ppc', false)
                ->update(['is_read_by_ppc' => true]);
        } elseif (str_contains($role, 'leader') || str_contains($role, 'supervisor')) {
            PullAheadRequest::whereIn('status', ['APPROVED', 'REJECTED', 'APPLIED'])
                ->where('requested_by', $user->id)
                ->where('is_read_by_leader', false)
                ->update(['is_read_by_leader' => true]);
        }

        return response()->json(['success' => true]);
    }

    // ==========================================
    // LEADER / OPERATIONAL METHODS
    // ==========================================

    /**
     * Mengambil jadwal Shift 2 (Shift berikutnya) untuk di-Tarik
     */
    public function nextShiftData(Request $request)
    {
        $line = $request->get('line', 'PRESS A');
        $cleanLine = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $line)));

        // Helper filter Line yang fleksibel & konsisten dengan InputHarianController
        $applyLineFilter = function($query) use ($cleanLine, $line) {
            if (!empty($cleanLine) && $cleanLine !== 'ALL') {
                $query->where(function($q) use ($cleanLine, $line) {
                    $q->whereRaw("REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS ', ''), 'LINE ', '') LIKE ?", ["%{$cleanLine}%"])
                      ->orWhere('press_name', $line)
                      ->orWhere('press_name', 'PRESS ' . $cleanLine)
                      ->orWhere('press_name', 'Line ' . $cleanLine);
                });
            }
        };

        $currentShift = $request->get('shift', 'Shift Pagi');
        $date = $request->get('date', now()->toDateString());

        // Cek apakah shift saat ini adalah Pagi / Shift 1
        $isCurrentPagi = (stripos($currentShift, 'Pagi') !== false || stripos($currentShift, '1') !== false);

        if ($isCurrentPagi) {
            // Shift Pagi -> berikutnya Shift Malam / Shift 2
            // Cek di hari yang sama terlebih dahulu, baru fallback ke besok
            $targetDates = [$date, Carbon::parse($date)->addDay()->toDateString()];
            $shiftKeywords = ['Malam', '2'];
        } else {
            // Shift Malam -> berikutnya Shift Pagi / Shift 1
            // Cek di besok hari terlebih dahulu, fallback ke hari yang sama
            $targetDates = [Carbon::parse($date)->addDay()->toDateString(), $date];
            $shiftKeywords = ['Pagi', '1'];
        }

        $matchedDate = null;
        $nextShift = null;

        foreach ($targetDates as $targetDate) {
            $baseQuery = ProductionPlan::query();
            $applyLineFilter($baseQuery);
            $baseQuery->whereDate('plan_date', $targetDate)
                      ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                      ->where(function($q) {
                          $q->where('row_type', 'job')
                            ->orWhereNull('row_type');
                      });

            // Cari nama shift aktual yang cocok dengan kata kunci
            $foundShift = (clone $baseQuery)->where(function($q) use ($shiftKeywords) {
                foreach ($shiftKeywords as $kw) {
                    $q->orWhere('shift_name', 'like', "%{$kw}%");
                }
            })->orderByDesc('updated_at')->value('shift_name');

            if ($foundShift) {
                $matchedDate = $targetDate;
                $nextShift = $foundShift;
                break;
            }
        }

        // Fallback jika tidak menemukan keyword spesifik: cari shift apapun yang berbeda dari shift sekarang
        if (!$nextShift) {
            foreach ($targetDates as $targetDate) {
                $baseQuery = ProductionPlan::query();
                $applyLineFilter($baseQuery);
                $foundShift = $baseQuery->whereDate('plan_date', $targetDate)
                    ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                    ->where(function($q) {
                        $q->where('row_type', 'job')
                          ->orWhereNull('row_type');
                    })
                    ->where('shift_name', '!=', $currentShift)
                    ->where('shift_name', 'not like', "{$currentShift}%")
                    ->orderByDesc('updated_at')
                    ->value('shift_name');

                if ($foundShift) {
                    $matchedDate = $targetDate;
                    $nextShift = $foundShift;
                    break;
                }
            }
        }

        if (!$nextShift) {
            $nextShift = $isCurrentPagi ? 'Shift Malam' : 'Shift Pagi';
            $matchedDate = $isCurrentPagi ? $date : Carbon::parse($date)->addDay()->toDateString();
        }

        // Ambil plan shift berikutnya
        $planQuery = ProductionPlan::query();
        $applyLineFilter($planQuery);
        $planQuery->whereDate('plan_date', $matchedDate)
                  ->where('shift_name', $nextShift)
                  ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                  ->where(function($q) {
                      $q->where('row_type', 'job')
                        ->orWhereNull('row_type');
                  })
                  ->orderBy('row_no', 'asc');

        $rawPlans = $planQuery->get();

        // Hitung Available Qty secara real-time dan saring yang habis
        $validPlans = [];
        foreach ($rawPlans as $plan) {
            $plan->available_qty = $this->pullAheadService->calculateAvailableQty($plan);
            if ($plan->available_qty > 0) {
                $validPlans[] = $plan;
            }
        }
        $nextShiftPlans = array_values($validPlans);

        // Ambil plan shift aktif (untuk usulan posisi sequence)
        $currPlanQuery = ProductionPlan::query();
        $applyLineFilter($currPlanQuery);
        $currPlanQuery->whereDate('plan_date', $date)
                      ->where('shift_name', 'like', "{$currentShift}%")
                      ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                      ->where(function($q) {
                          $q->where('row_type', 'job')
                            ->orWhereNull('row_type');
                      })
                      ->orderBy('row_no', 'asc');

        $currentShiftPlans = $currPlanQuery->get(['id', 'row_no', 'job_no', 'job_master']);

        return response()->json([
            'success' => true,
            'next_shift_plans' => $nextShiftPlans,
            'current_shift_plans' => $currentShiftPlans,
            'next_shift_name' => $nextShift,
            'next_shift_date' => $matchedDate
        ]);
    }

    /**
     * Submit Request Pull Ahead dari Leader
     */
    public function submitRequest(Request $request)
    {
        $request->validate([
            'original_plan_id' => 'required|exists:production_plans,id',
            'qty_requested' => 'required|numeric|min:1',
            'proposed_sequence_after' => 'nullable',
            'target_shift' => 'required|string',
            'source_shift' => 'required|string',
        ]);

        try {
            $data = $request->all();
            $data['requested_by'] = auth()->id();
            
            $this->pullAheadService->createRequest($data);

            return response()->json([
                'success' => true,
                'message' => 'Pull Ahead Request berhasil diajukan dan menunggu Approval PPC.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // ==========================================
    // PPC METHODS
    // ==========================================

    /**
     * Menampilkan Dashboard Pull Ahead untuk PPC
     */
    public function indexPpc(Request $request)
    {
        $pendingRequests = PullAheadRequest::with(['originalPlan', 'originalPlan.line', 'requester'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Ambil list item shift berjalan untuk modal PPC (biar PPC bisa atur ulang row_no)
        // Kita butuh list per-request, jadi lebih baik di load via AJAX per request
        // Tapi untuk simplifikasi kita passing pendingRequests dulu
        
        $historyRequests = PullAheadRequest::with(['originalPlan', 'requester', 'approver'])
            ->whereIn('status', ['APPROVED', 'REJECTED', 'APPLIED'])
            ->orderBy('updated_at', 'desc')
            ->limit(100)
            ->get();

        return view('ppc.pull_ahead.index', compact('pendingRequests', 'historyRequests'));
    }

    /**
     * PPC menyetujui Request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'qty_approved' => 'required|numeric|min:1',
            'final_sequence_after' => 'nullable',
        ]);

        $pullRequest = PullAheadRequest::findOrFail($id);

        try {
            $this->pullAheadService->approveRequest(
                $pullRequest, 
                $request->qty_approved, 
                $request->final_sequence_after, 
                auth()->id()
            );

            return redirect()->back()->with('success', 'Request Pull Ahead berhasil di-Approve dan disisipkan ke jadwal.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * PPC menolak Request
     */
    public function reject(Request $request, $id)
    {
        $pullRequest = PullAheadRequest::findOrFail($id);

        try {
            $this->pullAheadService->rejectRequest(
                $pullRequest, 
                auth()->id(),
                $request->input('remarks', 'Ditolak oleh PPC')
            );

            return redirect()->back()->with('success', 'Request Pull Ahead berhasil di-Reject.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}
