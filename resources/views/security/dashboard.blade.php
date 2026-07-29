@extends('layouts.supervisor')

@section('title', 'Security Dashboard')

@section('content')
<style>
    .sec-card {
        background: white; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .sec-badge {
        display: inline-block; padding: 2px 8px; border-radius: 12px;
        font-size: 10px; font-weight: 700;
    }
</style>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Security Dashboard</h1>
            <p class="text-sm text-slate-400 mt-1">Pemantauan keamanan jaringan real-time ({{ $hours }} jam terakhir)</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="sec-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Requests</span>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalRequests) }}</p>
        </div>
        <div class="sec-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Error Rate</span>
            <p class="text-2xl font-black mt-1 {{ $errorRate > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($errorRate) }}</p>
        </div>
        <div class="sec-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Unique IPs</span>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $uniqueIps }}</p>
        </div>
        <div class="sec-card">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Open Ports</span>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $openPorts->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Flagged IPs --}}
        <div class="sec-card">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Suspicious IPs</h3>
            @if($flaggedIps->isEmpty())
                <p class="text-sm text-emerald-600 font-semibold">Tidak ada IP mencurigakan ✓</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead><tr class="text-left text-slate-400 border-b border-slate-200">
                            <th class="py-1 pr-2">IP</th><th class="py-1 pr-2">Type</th><th class="py-1 pr-2 text-right">Count</th>
                        </tr></thead>
                        <tbody>
                            @foreach($flaggedIps as $ip)
                                <tr class="border-b border-slate-100">
                                    <td class="py-1 pr-2 font-mono text-slate-800">{{ $ip['ip'] }}</td>
                                    <td class="py-1 pr-2">
                                        <span class="sec-badge {{ $ip['type'] === 'Brute Force' ? 'bg-red-100 text-red-700' : ($ip['type'] === 'High Rate' ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700') }}">
                                            {{ $ip['type'] }}
                                        </span>
                                    </td>
                                    <td class="py-1 pr-2 text-right font-bold">{{ $ip['count'] }}×</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Open Ports --}}
        <div class="sec-card">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Open Ports</h3>
                <a href="{{ url('network-monitor') }}" class="text-xs text-blue-600 font-medium">Network Monitor →</a>
            </div>
            @if($openPorts->isEmpty())
                <p class="text-sm text-slate-400">Belum ada data port scan. Jalankan <code class="bg-slate-100 px-1 rounded">php artisan network:scan-ports</code></p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead><tr class="text-left text-slate-400 border-b border-slate-200">
                            <th class="py-1 pr-2">Target</th><th class="py-1 pr-2">Port</th><th class="py-1 pr-2">Service</th><th class="py-1 pr-2 text-right">Response</th>
                        </tr></thead>
                        <tbody>
                            @foreach($openPorts as $port)
                                <tr class="border-b border-slate-100">
                                    <td class="py-1 pr-2 font-mono text-slate-700">{{ $port->target }}</td>
                                    <td class="py-1 pr-2"><span class="font-bold text-slate-800">{{ $port->port }}</span><span class="text-slate-400">/{{ $port->protocol }}</span></td>
                                    <td class="py-1 pr-2 text-slate-600">{{ $port->service_name ?: '-' }}</td>
                                    <td class="py-1 pr-2 text-right text-slate-400">{{ $port->response_time_ms ? $port->response_time_ms . 'ms' : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Hourly Request Chart --}}
    <div class="sec-card">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Request Activity (24h)</h3>
        <div class="overflow-x-auto">
            <canvas id="requestChart" style="min-width:500px;height:200px"></canvas>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('requestChart').getContext('2d'), {
    type: 'bar', data: {
        labels: @json($hourlyStats->pluck('hour')->map(fn($h) => substr($h, 11, 5))),
        datasets: [
            { label: 'Total', data: @json($hourlyStats->pluck('total')), backgroundColor: '#2563eb', borderRadius: 4 },
            { label: 'Errors', data: @json($hourlyStats->pluck('errors')), backgroundColor: '#dc2626', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } } }
    }
});
</script>
@endpush
@endsection