<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DailyProduction;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalOperators = User::where('role','operator')->count();
        $totalSupervisors = User::where('role','supervisor')->count();

        $totalProduction = DailyProduction::count();
        $totalOk = DailyProduction::sum('actual_ok');
        $totalReject = DailyProduction::sum('actual_reject');

        $totalAll = $totalOk + $totalReject;

        $rejectRate = $totalAll > 0 ? ($totalReject / $totalAll) * 100 : 0;
        $yield = $totalAll > 0 ? ($totalOk / $totalAll) * 100 : 0;

        $recentProduction = DailyProduction::with('jobMaster')->latest('work_date')->paginate(10);

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
