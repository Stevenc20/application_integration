<?php

namespace App\Http\Controllers;

use App\Models\NetworkAccessLog;
use App\Models\NetworkLatency;
use App\Models\NetworkPortScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    public function index()
    {
        $hours = 24;
        $since = now()->subHours($hours);
        $since5m = now()->subMinutes(5);

        // Brute-force attempts
        $bruteForce = NetworkAccessLog::select('ip_address', DB::raw('count(*) as attempts'))
            ->where('created_at', '>=', $since)
            ->where('response_status', 401)
            ->groupBy('ip_address')
            ->having('attempts', '>=', 5)
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        // High request rate
        $highRate = NetworkAccessLog::select('ip_address', DB::raw('count(*) as requests'))
            ->where('created_at', '>=', $since5m)
            ->groupBy('ip_address')
            ->having('requests', '>=', 30)
            ->orderByDesc('requests')
            ->limit(10)
            ->get();

        // Probing (404 floods)
        $probing = NetworkAccessLog::select('ip_address', DB::raw('count(*) as attempts'))
            ->where('created_at', '>=', $since)
            ->where('response_status', 404)
            ->groupBy('ip_address')
            ->having('attempts', '>=', 10)
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        // Suspicious IPs from all categories (union-like via collection merge)
        $flaggedIps = collect();
        $bruteForce->each(fn($r) => $flaggedIps->push([
            'ip' => $r->ip_address, 'type' => 'Brute Force', 'count' => $r->attempts,
        ]));
        $highRate->each(fn($r) => $flaggedIps->push([
            'ip' => $r->ip_address, 'type' => 'High Rate', 'count' => $r->requests,
        ]));
        $probing->each(fn($r) => $flaggedIps->push([
            'ip' => $r->ip_address, 'type' => 'Probing', 'count' => $r->attempts,
        ]));

        $flaggedIps = $flaggedIps->sortByDesc('count')->take(20);

        // Hourly request count for chart (last 24h)
        $hourlyStats = NetworkAccessLog::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour"),
            DB::raw('count(*) as total'),
            DB::raw("SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as errors"),
        )
            ->where('created_at', '>=', $since)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Open ports
        $openPorts = NetworkPortScan::where('status', 'open')
            ->orderByDesc('scanned_at')
            ->limit(50)
            ->get();

        // Stats
        $totalRequests = NetworkAccessLog::where('created_at', '>=', $since)->count();
        $errorRate = NetworkAccessLog::where('created_at', '>=', $since)
            ->where('response_status', '>=', 400)->count();
        $uniqueIps = NetworkAccessLog::where('created_at', '>=', $since)
            ->distinct('ip_address')->count('ip_address');

        return view('security.dashboard', compact(
            'flaggedIps', 'hourlyStats', 'openPorts',
            'totalRequests', 'errorRate', 'uniqueIps', 'hours'
        ));
    }

    public function logs(Request $request)
    {
        $query = NetworkAccessLog::query();

        if ($request->filled('ip')) {
            $query->where('ip_address', $request->ip);
        }
        if ($request->filled('status')) {
            $query->where('response_status', $request->status);
        }
        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->method));
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('security.logs', compact('logs'));
    }
}
