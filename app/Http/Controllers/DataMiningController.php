<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DataMiningController extends Controller
{
    protected string $pythonUrl = 'http://127.0.0.1:8001';

    public function index()
    {
        return view('data_mining.index');
    }

    public function getTrend(Request $request, string $metric)
    {
        $response = Http::timeout(15)->get("{$this->pythonUrl}/api/trend/{$metric}", [
            'days' => $request->get('days', 90),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Python service unavailable'], 502);
        }

        return $response->json();
    }

    public function getAnomaly(Request $request)
    {
        $response = Http::timeout(15)->get("{$this->pythonUrl}/api/anomaly/detection", [
            'days' => $request->get('days', 30),
            'threshold' => $request->get('threshold', 2.0),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Python service unavailable'], 502);
        }

        return $response->json();
    }

    public function getPareto(Request $request, string $type)
    {
        $response = Http::timeout(15)->get("{$this->pythonUrl}/api/pareto/{$type}", [
            'days' => $request->get('days', 30),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Python service unavailable'], 502);
        }

        return $response->json();
    }

    public function getSummary(Request $request)
    {
        $response = Http::timeout(15)->get("{$this->pythonUrl}/api/summary", [
            'days' => $request->get('days', 30),
            'threshold' => $request->get('threshold', 2.0),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Python service unavailable'], 502);
        }

        return $response->json();
    }
}
