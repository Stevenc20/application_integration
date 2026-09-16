<?php

namespace App\Http\Controllers\Presdir;

use App\Http\Controllers\Controller;
use App\Models\DailyProduction;
use App\Models\ProductionPlan;
use App\Models\LineMaster;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $lines = LineMaster::where('status', 'active')->select('line_name')->distinct()->pluck('line_name');

        $totalOk = DailyProduction::where('work_date', $today)->sum('actual_ok');
        $totalRepair = DailyProduction::where('work_date', $today)->sum('actual_repair');
        $totalReject = DailyProduction::where('work_date', $today)->sum('actual_reject');
        $totalProduction = $totalOk + $totalRepair + $totalReject;

        $targetQty = ProductionPlan::where('plan_date', $today)->where('row_type', 'job')->sum('plan');
        $achievementPercent = $targetQty > 0 ? round(($totalOk / $targetQty) * 100, 1) : 0;
        $okRate = $totalProduction > 0 ? round(($totalOk / $totalProduction) * 100, 1) : 0;
        $rejectRate = $totalProduction > 0 ? round(($totalReject / $totalProduction) * 100, 1) : 0;

        $monthlyOk = DailyProduction::whereBetween('work_date', [$monthStart, $today])->sum('actual_ok');
        $monthlyTarget = ProductionPlan::whereBetween('plan_date', [$monthStart, $today])->where('row_type', 'job')->sum('plan');
        $monthlyAchievement = $monthlyTarget > 0 ? round(($monthlyOk / $monthlyTarget) * 100, 1) : 0;

        $lineSnapshots = [];
        foreach ($lines as $line) {
            $lineOk = DailyProduction::where('work_date', $today)->where('line', $line)->sum('actual_ok');
            $lineTarget = ProductionPlan::where('plan_date', $today)->whereHas('jobMaster', function ($q) use ($line) {
                $q->where('line', $line);
            })->sum('plan');
            $lineSnapshots[$line] = [
                'ok' => $lineOk,
                'target' => $lineTarget,
                'achievement' => $lineTarget > 0 ? round(($lineOk / $lineTarget) * 100, 1) : 0,
                'status' => $lineTarget > 0 ? ($lineOk >= $lineTarget ? 'on_track' : 'behind') : 'no_target',
            ];
        }

        $totalUsers = User::count();
        $totalOperators = User::where('role', 'operator')->count();

        return view('presdir.dashboard', compact(
            'totalOk', 'totalRepair', 'totalReject', 'totalProduction',
            'targetQty', 'achievementPercent', 'okRate', 'rejectRate',
            'monthlyAchievement', 'lineSnapshots',
            'totalUsers', 'totalOperators', 'today'
        ));
    }
}
