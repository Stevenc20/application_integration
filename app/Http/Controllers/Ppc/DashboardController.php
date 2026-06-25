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

        // 1. Total Rencana Produksi (Hanya tipe 'job')
        $totalPlans = ProductionPlan::whereDate('plan_date', $today)
            ->where('row_type', 'job')
            ->count();

        // 2. Sedang Berjalan (Sudah di-approve dan kemungkinan sudah ada di JobMaster)
        $running = ProductionPlan::whereDate('plan_date', $today)
            ->where('row_type', 'job')
            ->where('status', 'approved')
            ->count();

        // 3. Sudah Selesai
        $completed = ProductionPlan::whereDate('plan_date', $today)
            ->where('row_type', 'job')
            ->where('status', 'completed')
            ->count();

        // 4. Menunggu Approval (Status masih pending)
        $pending = ProductionPlan::whereDate('plan_date', $today)
            ->where('row_type', 'job')
            ->where('status', 'pending')
            ->count();

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
            'recoverySummary'
        ));
    }
}
