<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProductionProcess;

class DashboardController extends Controller
{
    public function index()
    {
        // ======================
        // USER DATA
        // ======================
        $totalUsers = User::count();
        $totalOperators = User::where('role','operator')->count();
        $totalSupervisors = User::where('role','supervisor')->count();

        // ======================
        // PRODUCTION DATA
        // ======================
        $totalProduction = ProductionProcess::count();
        $totalOk = ProductionProcess::sum('qty_ok');
        $totalReject = ProductionProcess::sum('qty_reject');

        $totalAll = $totalOk + $totalReject;

        // ======================
        // KPI CALCULATION
        // ======================
        $rejectRate = $totalAll > 0 ? ($totalReject / $totalAll) * 100 : 0;
        $yield = $totalAll > 0 ? ($totalOk / $totalAll) * 100 : 0;

        // ======================
        // PAGINATED PRODUCTION DATA
        // ======================
        $recentProduction = ProductionProcess::latest()->paginate(10);

        // ======================
        // RETURN VIEW
        // ======================
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalOperators',
            'totalSupervisors',
            'totalProduction',
            'totalOk',
            'totalReject',
            'rejectRate',
            'yield',
            'recentProduction'
        ));
    }
}