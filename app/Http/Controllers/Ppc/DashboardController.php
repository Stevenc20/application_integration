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

        // Tanggal aktif: pakai tanggal request, jika tidak ada pakai tanggal terakhir
        // yang punya schedule (supaya hasil upload PPC tetap terlihat).
        $requestDate = request('date');
        $hasScheduleOnRequestDate = false;

        if ($requestDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestDate)) {
            $activeDate = $requestDate;
            $hasScheduleOnRequestDate = ProductionPlan::whereDate('plan_date', $activeDate)
                ->where('row_type', 'job')
                ->exists();
        } else {
            $activeDate = $today;
        }

        if (! $hasScheduleOnRequestDate) {
            $latestPlanDate = ProductionPlan::where('row_type', 'job')
                ->max('plan_date');

            if ($latestPlanDate) {
                $latestPlanDate = Carbon::parse($latestPlanDate)->toDateString();
                if (! $requestDate || $latestPlanDate !== Carbon::parse($requestDate)->toDateString()) {
                    $activeDate = $latestPlanDate;
                }
            }
        }

        $baseQuery = fn () => ProductionPlan::whereDate('plan_date', $activeDate)
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

        // 5. Recovery Alert: item pending dari hari sebelumnya
        $recoveryAlert = null;
        $pendingRecoveries = RecoveryItem::pending()
            ->where(function ($q) use ($today) {
                $q->whereDate('original_date', '<', $today)
                  ->orWhereDate('source_date', '<', $today);
            })
            ->get();

        if ($pendingRecoveries->isNotEmpty()) {
            $recoveryAlert = [
                'total' => $pendingRecoveries->count(),
                'presses' => $pendingRecoveries->pluck('press_name')->unique()->values()->toArray(),
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
