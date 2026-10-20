<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * Hitung jumlah request baru yang belum dibaca (untuk polling notifikasi)
     */
    public function pendingCount(Request $request)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['success' => false, 'count' => 0], 403);

if ($user->isRole(['ppc', 'manager'])) {
            $count = PullAheadRequest::where('status', 'PENDING')
                ->where('is_read_by_ppc', false)
                ->count();
            $url    = route('ppc.pull_ahead.index');
            $label  = 'Tinjau Request';
            $message = "Ada $count Request Pull Ahead baru dari Leader yang menunggu Approval.";
        } else {
            $count = PullAheadRequest::whereIn('status', ['APPROVED', 'REJECTED', 'APPLIED'])
                ->where('requested_by', $user->id)
                ->where('is_read_by_leader', false)
                ->count();
            $url    = route('operational.input_harian');
            $label  = 'Buka Jadwal';
            $message = "Ada $count update status pada Request Pull Ahead Anda.";
        }

        return response()->json([
            'success' => true,
            'count'   => $count,
            'url'     => $url,
            'label'   => $label,
            'message' => $message,
        ]);
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
        $cleanLine = strtoupper(trim(preg_replace('/^(PRESS|LINE)\s*/i', '', (string)$line)));

        // Helper filter Line yang fleksibel & konsisten dengan InputHarianController
        $applyLineFilter = function($query) use ($cleanLine, $line) {
            if (!empty($cleanLine) && $cleanLine !== 'ALL') {
                $query->where(function($q) use ($cleanLine, $line) {
                    $q->whereRaw("REPLACE(REPLACE(REPLACE(UPPER(TRIM(press_name)), 'PRESS', ''), 'LINE', ''), ' ', '') LIKE ?", ["%{$cleanLine}%"])
                      ->orWhere('press_name', $line)
                      ->orWhere('press_name', 'PRESS ' . $cleanLine)
                      ->orWhere('press_name', 'Line ' . $cleanLine)
                      ->orWhere('press_name', 'PRESS' . $cleanLine)
                      ->orWhere('press_name', 'Line' . $cleanLine);
                });
            }
        };

        $currentShift = $request->get('shift', 'Shift Pagi');
        $date = $request->get('date', now()->toDateString());

        // Cek apakah shift saat ini adalah Pagi / Shift 1
        $isCurrentPagi = (stripos($currentShift, 'Pagi') !== false || stripos($currentShift, '1') !== false);
        $tomorrow = Carbon::parse($date)->addDay()->toDateString();

        // 1. Tentukan target shift berikutnya secara strictly berurutan:
        // Jika sedang di Shift Pagi -> target mutlak shift berikutnya adalah Shift Malam pada TANGGAL YANG SAMA ($date).
        // Jika sedang di Shift Malam -> target mutlak shift berikutnya adalah Shift Pagi pada BESOK HARI ($tomorrow).
        $targetShiftName = $isCurrentPagi ? 'Shift Malam' : 'Shift Pagi';
        $targetDate = $isCurrentPagi ? $date : $tomorrow;
        $targetKeywords = $isCurrentPagi ? ['Malam', '2'] : ['Pagi', '1'];

        // Cari item dari shift berikutnya sesuai target tepat
        $planQuery = ProductionPlan::query();
        $applyLineFilter($planQuery);
        $planQuery->whereDate('plan_date', $targetDate)
                  ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                  ->where(function($q) {
                      $q->where('row_type', 'job')
                        ->orWhereNull('row_type');
                  })
                  ->where('shift_name', '!=', $currentShift)
                  ->where('shift_name', 'not like', "{$currentShift}%")
                  ->where(function($q) use ($targetKeywords) {
                      foreach ($targetKeywords as $kw) {
                          $q->orWhere('shift_name', 'like', "%{$kw}%");
                      }
                  })
                  ->orderBy('row_no', 'asc');

        $rawPlans = $planQuery->get();
        $matchedDate = $targetDate;
        $nextShift = $rawPlans->isNotEmpty() ? $rawPlans->first()->shift_name : $targetShiftName;
        $emptyMessage = null;

        if ($rawPlans->isEmpty()) {
            $formattedTargetDate = Carbon::parse($targetDate)->translatedFormat('d M Y');
            $emptyMessage = "Jadwal {$targetShiftName} untuk tanggal {$formattedTargetDate} belum di-upload oleh PPC.";
        }

        // Hitung Available Qty secara real-time dan jamin item tetap muncul
        $validPlans = [];
        foreach ($rawPlans as $plan) {
            try {
                $avail = $this->pullAheadService->calculateAvailableQty($plan);
            } catch (\Throwable $e) {
                Log::warning('[PullAhead] calculateAvailableQty gagal: ' . $e->getMessage());
                $avail = (int)($plan->plan ?: ($plan->target_qty ?: 1));
            }
            if ($avail <= 0) {
                $avail = (int)($plan->plan ?: ($plan->target_qty ?: 1));
            }
            $plan->available_qty = $avail;
            $validPlans[] = $plan;
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

        if ($currentShiftPlans->isEmpty()) {
            $currFallback = ProductionPlan::query();
            $applyLineFilter($currFallback);
            $currentShiftPlans = $currFallback->whereDate('plan_date', $date)
                ->whereNotIn('job_no', ['TOTAL FINISH', 'TOTAL FNISH', 'FINISH'])
                ->where(function($q) {
                    $q->where('row_type', 'job')
                      ->orWhereNull('row_type');
                })
                ->orderBy('row_no', 'asc')
                ->get(['id', 'row_no', 'job_no', 'job_master']);
        }

        return response()->json([
            'success' => true,
            'next_shift_plans' => $nextShiftPlans,
            'current_shift_plans' => $currentShiftPlans,
            'next_shift_name' => $nextShift,
            'next_shift_date' => $matchedDate,
            'empty_message' => $emptyMessage
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
        } catch (\Throwable $e) {
            Log::error('[PullAhead] submitRequest gagal: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'trace'   => $e->getTraceAsString(),
            ]);

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

        // PPC yang sudah membuka dashboard dianggap sudah membaca request ini
        PullAheadRequest::where('status', 'PENDING')
            ->where('is_read_by_ppc', false)
            ->update(['is_read_by_ppc' => true]);
            
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
