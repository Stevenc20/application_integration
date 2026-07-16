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

        return view('operator.dashboard', compact(
            'totalOk',
            'totalReject',
            'totalRepair',
            'totalProduction',
            'recentProductions'
        ));
    }
}
