<?php

namespace App\Http\Middleware;

use App\Models\NetworkAccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NetworkAccessLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (int) ((microtime(true) - $start) * 1000);

        if ($this->shouldLog($request)) {
            NetworkAccessLog::create([
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'response_time_ms' => $duration,
                'response_status' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        $skip = ['_debugbar', 'network-access-logs', 'livewire', 'telescope', 'ignition'];
        foreach ($skip as $s) {
            if (str_contains($request->path(), $s)) return false;
        }
        return true;
    }
}
