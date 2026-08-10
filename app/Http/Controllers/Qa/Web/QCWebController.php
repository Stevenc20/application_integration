<?php

namespace App\Http\Controllers\Qa\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItemCheck;
use App\Models\Qpr;
use Carbon\Carbon;

class QCWebController extends Controller
{
    public function worklist()
    {
        return view('qa.qc.worklist');
    }

    public function rapor(Request $request)
    {
        $user = auth()->user();
        
        if (in_array($user->role, ['Admin', 'Leader', 'Supervisor', 'Foreman', 'Group Leader'])) {
            return $this->raporLeader($request);
        }
        
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));
        $hasFilter = $request->filled('month') || $request->filled('year');
        
        // --- 1. Total Inspeksi (ItemCheck) ---
        $itemChecksQuery = ItemCheck::where('operator_id', $user->id)
            ->whereIn('status', ['finished', 'approved', 'locked']);
            
        if ($hasFilter) {
            if ($request->filled('month')) $itemChecksQuery->whereMonth('tanggal', $selectedMonth);
            if ($request->filled('year')) $itemChecksQuery->whereYear('tanggal', $selectedYear);
        }
        
        $itemChecks = $itemChecksQuery->orderBy('created_at', 'desc')->get();
            
        $totalInspeksi = $itemChecks->count();
        
        // --- 2. Total Temuan NG ---
        $totalNgFound = 0;
        foreach ($itemChecks as $ic) {
            if ($ic->hasNg()) {
                $totalNgFound++;
            }
        }
        
        // --- 3. Total Laporan QPR ---
        $qprQuery = Qpr::where('created_by', $user->id);
        if ($hasFilter) {
            if ($request->filled('month')) $qprQuery->whereMonth('tanggal', $selectedMonth);
            if ($request->filled('year')) $qprQuery->whereYear('tanggal', $selectedYear);
        }
        $totalQpr = (clone $qprQuery)->count();
        
        $approvedQpr = $qprQuery->where(function($query) {
                $query->where('status', 'Close')
                      ->orWhere('status', 'CLOSE')
                      ->orWhere('status', 'Finished');
            })->count();
        
        // --- 4. Trend 6 Bulan Terakhir ---
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::today()->startOfMonth()->subMonths($i);
            $monthName = $date->translatedFormat('M Y');
            
            $count = ItemCheck::where('operator_id', $user->id)
                ->whereIn('status', ['finished', 'approved', 'locked'])
                ->whereYear('tanggal', $date->year)
                ->whereMonth('tanggal', $date->month)
                ->count();
                
            $monthlyTrend[] = [
                'month' => $monthName,
                'count' => $count
            ];
        }
        
        // Normalize chart heights for CSS Grid
        $maxCount = max(array_column($monthlyTrend, 'count'));
        if ($maxCount == 0) $maxCount = 1; // Prevent division by zero
        foreach ($monthlyTrend as &$trend) {
            $trend['percentage'] = round(($trend['count'] / $maxCount) * 100);
        }
        
        // --- 5. Aktivitas Terakhir ---
        $recentInspeksi = ItemCheck::with(['masterTemplate'])
            ->where('operator_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $recentQprs = Qpr::where('created_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('qa.qc.rapor', compact(
            'user',
            'totalInspeksi',
            'totalNgFound',
            'totalQpr',
            'approvedQpr',
            'monthlyTrend',
            'recentInspeksi',
            'recentQprs'
        ));
    }

    public function raporLeader(Request $request)
    {
        $user = auth()->user();
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));

        // Ambil semua operator aktif
        $operators = \App\Models\User::where('role', 'Operator')->where('is_active', true)->get();
        
        $itemChecks = ItemCheck::whereIn('status', ['finished', 'approved', 'locked'])
            ->whereMonth('tanggal', $selectedMonth)
            ->whereYear('tanggal', $selectedYear)
            ->get();
            
        $qprs = Qpr::whereMonth('tanggal', $selectedMonth)
            ->whereYear('tanggal', $selectedYear)
            ->get();
            
        $leaderboard = [];
        
        foreach ($operators as $op) {
            $opItemChecks = $itemChecks->where('operator_id', $op->id);
            $totalInspeksi = $opItemChecks->count();
            
            $totalNg = 0;
            foreach ($opItemChecks as $ic) {
                if ($ic->hasNg()) $totalNg++;
            }
            
            $opQprs = $qprs->where('created_by', $op->id);
            $totalQpr = $opQprs->count();
            
            // Skor keaktifan
            $score = $totalInspeksi + ($totalNg * 2) + ($totalQpr * 5);
            
            if ($totalInspeksi > 0 || $totalQpr > 0) {
                $leaderboard[] = [
                    'operator' => $op,
                    'totalInspeksi' => $totalInspeksi,
                    'totalNg' => $totalNg,
                    'totalQpr' => $totalQpr,
                    'score' => $score
                ];
            }
        }
        
        usort($leaderboard, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return view('qa.qc.rapor-leader', compact('user', 'selectedMonth', 'selectedYear', 'leaderboard'));
    }
}
