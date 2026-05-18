<?php

namespace App\Http\Controllers\Ppc;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlan;
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
        // Atau bisa juga check act_start jika sudah mulai ditarik operator
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

        return view('ppc.dashboard', compact(
            'totalPlans',
            'running',
            'completed',
            'pending'
        ));
    }
}
