<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\RundownIncoming;

class ChartController extends Controller
{
    public function index()
    {
        // Ambil tanggal terakhir dari RundownIncoming untuk kategori FINISH PART
        $latestDate = RundownIncoming::where('category', 'FINISH PART')->orderBy('id', 'desc')->value('sheet_date');
        
        $query = RundownIncoming::where('category', 'FINISH PART');
        if ($latestDate) {
            $query->where('sheet_date', $latestDate);
        }

        $hasData = (clone $query)->count() > 0;

        // 1. Stacked bar: Status per Customer
        $perCustomer = (clone $query)->select(
            'customer',
            DB::raw('SUM(CASE WHEN status = "OVER" THEN 1 ELSE 0 END) as over_stock'),
            DB::raw('SUM(CASE WHEN status = "STANDAR" THEN 1 ELSE 0 END) as limited'),
            DB::raw('SUM(CASE WHEN status = "CRITICAL" THEN 1 ELSE 0 END) as zero_stock'),
            DB::raw('COUNT(*) as total')
        )->groupBy('customer')->orderBy('customer')->get();

        // 2. Donut: Status distribution (Ganti remarks ke status)
        $remarksData = (clone $query)->select(DB::raw('status as remarks'), DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        // 3. Bar: Vendor distribution (Ganti proses ke vendor)
        $prosesData = (clone $query)->select(DB::raw('vendor as proses'), DB::raw('COUNT(*) as total'))
            ->groupBy('vendor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 4. Line: Strength avg per customer
        $strengthAvg = (clone $query)->select('customer', DB::raw('AVG(strength) as avg_strength'))
            ->groupBy('customer')
            ->orderBy('customer')
            ->get();

        // 5. Summary totals
        $totalOver    = (clone $query)->where('status', 'OVER')->count();
        $totalLimited = (clone $query)->where('status', 'STANDAR')->count();
        $totalZero    = (clone $query)->where('status', 'CRITICAL')->count();
        $totalAll     = (clone $query)->count();

        return view('chart', compact(
            'hasData', 'perCustomer', 'remarksData', 'prosesData',
            'strengthAvg', 'totalOver', 'totalLimited', 'totalZero', 'totalAll'
        ));
    }
}