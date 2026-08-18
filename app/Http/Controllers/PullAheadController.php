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
        $line = $request->get('line', 'Line A');
        $currentShift = $request->get('shift', 'Shift Pagi');
        $date = $request->get('date', now()->toDateString());

        // Penentuan cerdas Shift Berikutnya pada Tanggal yang Sama
        $nextDate = $date;
        if (stripos($currentShift, 'Pagi') !== false || stripos($currentShift, 'Shift 1') !== false) {
            $nextShiftPrefix = 'Shift Malam';
        } else {
            $nextShiftPrefix = 'Shift Pagi';
        }

        // Cari nama shift sebenarnya di DB (krn kadang ada suffix e.g. "Shift Malam B")
        $nextShift = ProductionPlan::where('press_name', $line)
            ->where('plan_date', $nextDate)
            ->where('shift_name', 'like', $nextShiftPrefix . '%')
            ->value('shift_name') ?: $nextShiftPrefix;

        // Ambil plan shift berikutnya
        $nextShiftPlans = ProductionPlan::where('press_name', $line)
            ->where('plan_date', $nextDate)
            ->where('shift_name', $nextShift)
            ->where('row_type', 'job')
            ->where('remaining_plan', '>', 0)
            ->orderBy('row_no', 'asc')
            ->get();
            
        // Hitung Available Qty secara real-time
        foreach ($nextShiftPlans as $plan) {
            $plan->available_qty = $this->pullAheadService->calculateAvailableQty($plan);
        }

        // Ambil plan shift aktif (untuk usulan posisi sequence)
        $currentShiftPlans = ProductionPlan::where('press_name', $line)
            ->where('plan_date', $date)
            ->where('shift_name', $currentShift)
            ->where('row_type', 'job')
            ->orderBy('row_no', 'asc')
            ->get(['id', 'row_no', 'job_no', 'job_master']);

        return response()->json([
            'success' => true,
            'next_shift_plans' => $nextShiftPlans,
            'current_shift_plans' => $currentShiftPlans,
            'next_shift_name' => $nextShift
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
