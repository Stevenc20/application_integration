<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Models\RecoveryItem;
use Illuminate\Http\Request;

class RecoveryController extends Controller
{
    /**
     * Display combined recovery page with Queue tab (pending) and History tab (all statuses).
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'queue');
        $query = RecoveryItem::with(['schedule', 'rejector', 'productionPlan'])
            ->orderBy('created_at', 'desc');

        if ($tab === 'queue') {
            $query->where('status', 'waiting_approval');
        }

        // Filters
        if ($tab === 'history' && $status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('source_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('source_date', '<=', $dateTo);
        }
        if ($shift = $request->get('shift')) {
            $query->where('source_shift', $shift);
        }
        if ($press = $request->get('press')) {
            $query->where('press_name', $press);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                  ->orWhere('job_master', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(50)->withQueryString();
        $presses = RecoveryItem::distinct()->pluck('press_name')->sort()->values();
        $statuses = ['waiting_approval', 'approved', 'rejected', 'scheduled', 'in_production', 'completed'];
        $queueCount = RecoveryItem::pending()->count();

        return view('ppc.recovery.index', compact('items', 'presses', 'statuses', 'tab', 'queueCount'));
    }

    /**
     * Reject a single recovery item.
     */
    public function rejectItem(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $item = RecoveryItem::findOrFail($id);

        if ($item->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Item sudah diproses.',
            ]);
        }

        $press = $item->press_name;
        $item->update([
            'status'          => 'rejected',
            'rejected_at'     => now(),
            'rejected_by'     => auth()->id(),
            'rejection_notes' => $request->input('notes'),
        ]);

        // Continuous resimulation
        try {
            $today = now()->format('Y-m-d');
            $timelineGenerator = app(\App\Services\TimelineGenerationService::class);
            $timelineGenerator->regenerateSection($today, 'Shift Pagi', $press);
            $timelineGenerator->regenerateSection($today, 'Shift Malam', $press);
        } catch (\Throwable $e) {
            \Log::warning('Resimulation after reject failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil di-reject.',
        ]);
    }

    /**
     * Reject multiple recovery items.
     */
    public function rejectItems(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:recovery_items,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $itemIds = $request->input('item_ids', []);
        $notes = $request->input('notes');

        $items = RecoveryItem::whereIn('id', $itemIds)
            ->where('status', 'waiting_approval')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan atau sudah diproses.',
            ]);
        }

        $presses = $items->pluck('press_name')->unique()->values()->toArray();

        foreach ($items as $item) {
            $item->update([
                'status'          => 'rejected',
                'rejected_at'     => now(),
                'rejected_by'     => auth()->id(),
                'rejection_notes' => $notes,
            ]);
        }

        // Continuous resimulation
        try {
            $today = now()->format('Y-m-d');
            $timelineGenerator = app(\App\Services\TimelineGenerationService::class);
            foreach ($presses as $press) {
                $timelineGenerator->regenerateSection($today, 'Shift Pagi', $press);
                $timelineGenerator->regenerateSection($today, 'Shift Malam', $press);
            }
        } catch (\Throwable $e) {
            \Log::warning('Resimulation after reject failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => count($items) . ' item berhasil di-reject.',
        ]);
    }

    /**
     * Run cut-off for a specific date and shift (manual trigger).
     */
    public function runCutOff(Request $request)
    {
        $request->validate([
            'date'  => 'required|date',
            'shift' => 'required|string',
        ]);

        $cutOffService = app(\App\Services\CutOffService::class);
        $stats = $cutOffService->processCutOff($request->input('date'), $request->input('shift'));

        return response()->json([
            'success' => true,
            'message' => "Cut-off selesai: {$stats['created']} recovery item dibuat, {$stats['skipped']} dilewati.",
            'stats'   => $stats,
        ]);
    }

    /**
     * Run scheduler for a specific date, shift, and press.
     * Delegates to TimelineGenerationService for regeneration.
     */
    public function runScheduler(Request $request)
    {
        $request->validate([
            'date'  => 'required|date',
            'shift' => 'required|string',
            'press' => 'required|string',
        ]);

        try {
            $timelineGenerator = app(\App\Services\TimelineGenerationService::class);
            $result = $timelineGenerator->regenerateSection(
                $request->input('date'),
                $request->input('shift'),
                $request->input('press')
            );

            return response()->json([
                'success' => true,
                'message' => "Scheduler selesai: {$result['updated']} item diproses." . (!empty($result['overflow']) ? ' ' . count($result['overflow']) . ' item overflow.' : ''),
                'stats'   => $result,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Scheduler failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Scheduler gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check pending recovery alert data for dashboard.
     */
    public function alertData()
    {
        $pendingItems = RecoveryItem::pending()
            ->with('schedule')
            ->get();

        $total = $pendingItems->count();
        $presses = $pendingItems->pluck('press_name')->unique()->values()->toArray();

        return response()->json([
            'total'  => $total,
            'presses' => $presses,
        ]);
    }
}
