<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DailyProduction;
use App\Models\ProductionPlan;
use App\Models\LineMaster;
use App\Models\Downtime;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $lines = LineMaster::where('status', 'active')->select('line_name')->distinct()->pluck('line_name');

        $totalOk = DailyProduction::where('work_date', $today)->sum('actual_ok');
        $totalRepair = DailyProduction::where('work_date', $today)->sum('actual_repair');
        $totalReject = DailyProduction::where('work_date', $today)->sum('actual_reject');
        $totalProduction = $totalOk + $totalRepair + $totalReject;

        $targetQty = ProductionPlan::where('plan_date', $today)->where('row_type', 'job')->sum('plan');
        $achievementPercent = $targetQty > 0 ? round(($totalOk / $targetQty) * 100, 1) : 0;
        $rejectRate = $totalProduction > 0 ? round(($totalReject / $totalProduction) * 100, 2) : 0;

        $lineStatuses = [];
        foreach ($lines as $line) {
            $lineOk = DailyProduction::where('work_date', $today)->where('line', $line)->sum('actual_ok');
            $lineTarget = ProductionPlan::where('plan_date', $today)->whereHas('jobMaster', function ($q) use ($line) {
                $q->where('line', $line);
            })->sum('plan');
            $lineStatuses[$line] = [
                'ok' => $lineOk,
                'target' => $lineTarget,
                'achievement' => $lineTarget > 0 ? round(($lineOk / $lineTarget) * 100, 1) : 0,
            ];
        }

        $downtimeToday = Downtime::whereDate('created_at', $today)->sum('duration_seconds');

        $recentProduction = DailyProduction::where('work_date', $today)
            ->with('jobMaster')->latest()->paginate(10);

        return view('manager.dashboard', compact(
            'totalOk', 'totalRepair', 'totalReject', 'totalProduction',
            'targetQty', 'achievementPercent', 'rejectRate',
            'lineStatuses', 'downtimeToday', 'recentProduction', 'today'
        ));
    }
}
