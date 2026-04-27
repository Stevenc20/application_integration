<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionProcess;

class OperatorDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $totalOk = ProductionProcess::whereDate('created_at', $today)->sum('qty_ok');
        $totalReject = ProductionProcess::whereDate('created_at', $today)->sum('qty_reject');
        $totalRepair = ProductionProcess::whereDate('created_at', $today)->sum('qty_repair');

        $totalProduction = $totalOk + $totalReject + $totalRepair;

        $recentProductions = ProductionProcess::latest()->take(5)->get();

        return view('operator.dashboard', compact(
            'totalOk',
            'totalReject',
            'totalRepair',
            'totalProduction',
            'recentProductions'
        ));
    }
}