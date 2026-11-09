<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
use App\Models\RecoveryItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $requestDate = request('date');
        $hasScheduleToday = ProductionPlan::whereDate('plan_date', $today)
            ->activePpcRevision()
            ->where('row_type', 'job')
            ->exists();

        if ($requestDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestDate)) {
            $activeDate = $requestDate;
        } elseif ($hasScheduleToday) {
            // Jika hari ini sudah ada schedule, selalu prioritaskan hari ini
            $activeDate = $today;
        } else {
            // Jika hari ini belum ada schedule, fallback ke tanggal schedule terbaru
            $latestPlanDate = ProductionPlan::activePpcRevision()
                ->where('row_type', 'job')
                ->max('plan_date');

            $activeDate = $latestPlanDate ? Carbon::parse($latestPlanDate)->toDateString() : $today;
        }

        $baseQuery = fn () => ProductionPlan::whereDate('plan_date', $activeDate)
            ->activePpcRevision()
            ->where('row_type', 'job');

        // 1. Total Rencana Produksi (Hanya tipe 'job')
        $totalPlans = $baseQuery()->count();

        // 2. Sedang Berjalan (approved / sudah ada aktual ok)
        $running = $baseQuery()->where(function ($q) {
            $q->where('status', 'approved')
              ->orWhere('ok', '>', 0);
        })->count();

        // 3. Sudah Selesai
        $completed = $baseQuery()->where('status', 'completed')->count();

        // 4. Menunggu Approval (Status masih pending)
        $pending = $baseQuery()->where('status', 'pending')->count();

        // 5. Ringkasan schedule per press
        $pressSummary = $baseQuery()
            ->selectRaw("COALESCE(press_name, '—') as press_name, COUNT(*) as jobs, COALESCE(SUM(plan), 0) as plan_qty, COALESCE(SUM(ok), 0) as ok_qty")
            ->groupBy('press_name')
            ->orderBy('press_name')
            ->get()
            ->keyBy('press_name');

        $totalPlanQty = (float) $baseQuery()->sum('plan');
        $totalOkQty   = (float) $baseQuery()->sum('ok');
        $isTodayData  = ($activeDate === $today);

        // 5. Recovery Alert: item pending yang membutuhkan persetujuan
        $recoveryAlert = null;
        $pendingRecoveries = RecoveryItem::pending()
            ->orderByDesc('id')
            ->get();

        if ($pendingRecoveries->isNotEmpty()) {
            $recoveryAlert = [
                'total' => $pendingRecoveries->count(),
                'presses' => $pendingRecoveries->pluck('press_name')->filter()->unique()->values()->toArray(),
                'items' => $pendingRecoveries,
            ];
        }

        // 6. Recovery Summary Stats
        $recoverySummary = [
            'pending'   => RecoveryItem::pending()->count(),
            'approved'  => RecoveryItem::approved()->count(),
            'scheduled' => RecoveryItem::scheduled()->count(),
            'completed' => RecoveryItem::completed()->count(),
            'total_qty' => (float) RecoveryItem::whereIn('status', ['waiting_approval', 'approved', 'scheduled'])->sum('recovery_qty'),
            'by_press'  => RecoveryItem::selectRaw('press_name, COUNT(*) as total, SUM(recovery_qty) as qty')
                ->whereIn('status', ['waiting_approval', 'approved', 'scheduled'])
                ->groupBy('press_name')
                ->orderBy('press_name')
                ->get(),
        ];

        return view('ppc.dashboard', compact(
            'totalPlans',
            'running',
            'completed',
            'pending',
            'recoveryAlert',
            'recoverySummary',
            'activeDate',
            'isTodayData',
            'pressSummary',
            'totalPlanQty',
            'totalOkQty'
        ));
    }
}
