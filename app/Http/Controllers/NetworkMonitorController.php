<?php

namespace App\Http\Controllers;

use App\Models\NetworkAccessLog;
use App\Models\NetworkContainer;
use App\Models\NetworkLatency;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetworkMonitorController extends Controller
{
    public function index()
    {
        $containers = NetworkContainer::orderBy('container_name')->get();
        $recentLogs = NetworkAccessLog::with('user')
            ->latest()
            ->take(50)
            ->get();
        $latencies = NetworkLatency::where('measured_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('measured_at')
            ->get();

        $stats = [
            'total_containers' => NetworkContainer::count(),
            'up_containers' => NetworkContainer::where('status', 'running')->count(),
            'total_requests_24h' => NetworkAccessLog::where('created_at', '>=', Carbon::now()->subHours(24))->count(),
            'avg_response_time' => NetworkAccessLog::where('created_at', '>=', Carbon::now()->subHours(24))->avg('response_time_ms'),
        ];

        return view('network_monitor.index', compact('containers', 'recentLogs', 'latencies', 'stats'));
    }

    public function containers()
    {
        $containers = NetworkContainer::orderBy('container_name')->get();
        return response()->json($containers);
    }

    public function logs(Request $request)
    {
        $period = $request->get('period', 24);
        $query = NetworkAccessLog::where('created_at', '>=', Carbon::now()->subHours($period));

        $total = (clone $query)->count();
        $avgResponse = round((clone $query)->avg('response_time_ms') ?? 0, 1);

        $perHour = (clone $query)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $topIps = (clone $query)
            ->select('ip_address', DB::raw('COUNT(*) as count'))
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $topEndpoints = (clone $query)
            ->select('endpoint', DB::raw('COUNT(*) as count'))
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $statusCodes = (clone $query)
            ->select('response_status', DB::raw('COUNT(*) as count'))
            ->groupBy('response_status')
            ->orderByDesc('count')
            ->get();

        $recent = (clone $query)->with('user')->latest()->take(100)->get();

        return response()->json(compact('total', 'avgResponse', 'perHour', 'topIps', 'topEndpoints', 'statusCodes', 'recent'));
    }

    public function latency()
    {
        $latencies = NetworkLatency::where('measured_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('measured_at')
            ->get();

        $targets = $latencies->groupBy('target')->map(function ($items, $target) {
            return [
                'target' => $target,
                'avg_latency' => round($items->avg('latency_ms'), 1),
                'min_latency' => round($items->min('latency_ms'), 1),
                'max_latency' => round($items->max('latency_ms'), 1),
                'current_status' => $items->last()->status ?? 'unknown',
                'measurements' => $items->count(),
            ];
        })->values();

        $history = $latencies->groupBy('target')->map(function ($items) {
            return [
                'target' => $items->first()->target,
                'data' => $items->map(fn($i) => [
                    'time' => $i->measured_at->format('Y-m-d H:i'),
                    'latency_ms' => $i->latency_ms,
                    'status' => $i->status,
                ]),
            ];
        })->values();

        return response()->json(compact('targets', 'history'));
    }
}
