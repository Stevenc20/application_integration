<?php

namespace App\Http\Controllers;

use App\Services\DataMiningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataMiningController extends Controller
{
    protected DataMiningService $dataMiningService;

    public function __construct(DataMiningService $dataMiningService)
    {
        $this->dataMiningService = $dataMiningService;
    }

    protected function getPythonUrl(): string
    {
        return rtrim(env('PYTHON_SERVICE_URL', 'http://172.17.0.1:8001'), '/');
    }

    public function index()
    {
        return view('data_mining.index');
    }

    public function getTrend(Request $request, string $metric)
    {
        $days = (int) $request->get('days', 30);
        try {
            $response = Http::timeout(3)->get("{$this->getPythonUrl()}/api/trend/{$metric}", [
                'days' => $days,
            ]);

            if ($response->successful() && is_array($response->json())) {
                return response()->json($response->json());
            }
        } catch (\Throwable $e) {
            Log::warning("[DATA MINING] Python API unavailable for trend: " . $e->getMessage() . ". Using native PHP fallback.");
        }

        // Native PHP Fallback
        return response()->json($this->dataMiningService->calculateTrend($metric, $days));
    }

    public function getAnomaly(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $threshold = (float) $request->get('threshold', 2.0);

        try {
            $response = Http::timeout(3)->get("{$this->getPythonUrl()}/api/anomaly/detection", [
                'days' => $days,
                'threshold' => $threshold,
            ]);

            if ($response->successful() && is_array($response->json())) {
                return response()->json($response->json());
            }
        } catch (\Throwable $e) {
            Log::warning("[DATA MINING] Python API unavailable for anomaly: " . $e->getMessage() . ". Using native PHP fallback.");
        }

        // Native PHP Fallback
        return response()->json($this->dataMiningService->detectAnomalies($days, $threshold));
    }

    public function getPareto(Request $request, string $type)
    {
        $days = (int) $request->get('days', 30);

        try {
            $response = Http::timeout(3)->get("{$this->getPythonUrl()}/api/pareto/{$type}", [
                'days' => $days,
            ]);

            if ($response->successful() && is_array($response->json())) {
                return response()->json($response->json());
            }
        } catch (\Throwable $e) {
            Log::warning("[DATA MINING] Python API unavailable for pareto: " . $e->getMessage() . ". Using native PHP fallback.");
        }

        // Native PHP Fallback
        return response()->json($this->dataMiningService->calculatePareto($type, $days));
    }

    public function getSummary(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $threshold = (float) $request->get('threshold', 2.0);

        try {
            $response = Http::timeout(3)->get("{$this->getPythonUrl()}/api/summary", [
                'days' => $days,
                'threshold' => $threshold,
            ]);

            if ($response->successful() && is_array($response->json())) {
                return response()->json($response->json());
            }
        } catch (\Throwable $e) {
            Log::warning("[DATA MINING] Python API unavailable for summary: " . $e->getMessage() . ". Using native PHP fallback.");
        }

        // Native PHP Fallback
        return response()->json($this->dataMiningService->getSummary($days, $threshold));
    }
}
