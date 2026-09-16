<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyProduction;

class OperatorDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $totalOk = DailyProduction::where('work_date', $today)->sum('actual_ok');
        $totalReject = DailyProduction::where('work_date', $today)->sum('actual_reject');
        $totalRepair = DailyProduction::where('work_date', $today)->sum('actual_repair');

        $totalProduction = $totalOk + $totalReject + $totalRepair;

        $recentProductions = DailyProduction::with('jobMaster')->where('work_date', $today)->latest()->take(5)->get();

        // Revisi 4: leader end-of-shift comments shown on the dashboard
        $shiftComments = \App\Models\ShiftSubmission::with('submitter', 'line')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->whereNull('cancelled_at')
            ->orderByDesc('submitted_at')
            ->take(10)
            ->get();

        return view('operator.dashboard', compact(
            'totalOk',
            'totalReject',
            'totalRepair',
            'totalProduction',
            'recentProductions',
            'shiftComments'
        ));
    }
}
